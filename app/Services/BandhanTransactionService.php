<?php

namespace App\Services;

use App\Helpers\BandhanLogger;
use App\Helpers\BandhanPayment;
use App\Models\BandhanTransaction;
use App\Models\BandhanTransactionDetail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BandhanTransactionService
{
    /**
     * Map action IDs to descriptive folder names and action categories.
     * Right column represents active Annapurna Action IDs:
     * 3713 -> LotValidationUpload
     * 3715 -> LotValidationInfo
     * 3716 -> LotValidationResponse / Det
     * 3717 -> LotTransactionUpload
     * 3718 -> LotTransactionInfo
     * 3719 -> LotTransactionResponse / Det
     */
    protected array $actionFolders = [
        // Annapurna (Current / Active Scheme)
        '3713' => 'bandhan/3713',
        '3715' => 'bandhan/3715',
        '3716' => 'bandhan/3716',
        '3717' => 'bandhan/3717',
        '3718' => 'bandhan/3718',
        '3719' => 'bandhan/3719',

        // Lakshmi Bhandar (Old Scheme)
        '1060' => 'bandhan/1060',
        '1068' => 'bandhan/1068',
        '1069' => 'bandhan/1069',
        '1072' => 'bandhan/1072',
        '1073' => 'bandhan/1073',
        '1074' => 'bandhan/1074',

        // Legacy Scheme
        '2036' => 'bandhan/2036',
        '2037' => 'bandhan/2037',
        '2038' => 'bandhan/2038',
    ];

    /**
     * Resolve the storage folder for a given ActionId and ensure it exists.
     */
    public function getActionFolder(string $actionId): string
    {
        $folder = $this->actionFolders[$actionId] ?? "bandhan/{$actionId}";
        if (!Storage::disk('local')->exists($folder)) {
            Storage::disk('local')->makeDirectory($folder);
        }
        return $folder;
    }

    /**
     * Ensure all standard action directories exist in storage.
     */
    public function ensureActionFoldersExist(): void
    {
        foreach ($this->actionFolders as $folder) {
            if (!Storage::disk('local')->exists($folder)) {
                Storage::disk('local')->makeDirectory($folder);
            }
        }
        // Legacy fallback folders
        if (!Storage::disk('local')->exists('bandhanbentransactionencdata')) {
            Storage::disk('local')->makeDirectory('bandhanbentransactionencdata');
        }
        if (!Storage::disk('local')->exists('bandhanbeneficiaryencdata')) {
            Storage::disk('local')->makeDirectory('bandhanbeneficiaryencdata');
        }
    }

    /**
     * Global function / helper to log raw response and decoded response for any action.
     * Can be invoked from any action method.
     */
    public function logActionResponse(string|int $actionId, $response, ?string $lotNumber = null, array $context = []): array
    {
        return BandhanLogger::logResponse($actionId, $response, $lotNumber, $context);
    }

    /**
     * Main dispatch method: parse payload, store raw files in action folder,
     * and invoke the dedicated handler function.
     */
    public function processCallback(array $data, ?string $rawContent = null)
    {
        $parsed = $this->parsePayload($data, $rawContent);
        $actionId = $parsed['actionId'];

        // Automatically store encrypted & decrypted payload in the action's dedicated folder
        $storedFiles = $this->storeActionPayloadFiles($parsed, $data, $rawContent);
        $parsed['storedFiles'] = $storedFiles;

        switch ($actionId) {
            // ─────────────────────────────────────────────────────────────
            // 1. LOT VALIDATION UPLOAD (Annapurna: 3713 | Old: 1060 | Legacy: 2036)
            // ─────────────────────────────────────────────────────────────
            case '3713':
            case '1060':
            case '2036':
                return $this->handleLotValidationUpload($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // 2. LOT VALIDATION INFO (Annapurna: 3715 | Old: 1068 | Legacy: 2037)
            // ─────────────────────────────────────────────────────────────
            case '3715':
            case '1068':
            case '2037':
                return $this->handleLotValidationInfo($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // 3. LOT VALIDATION RESPONSE / DET (Annapurna: 3716 | Old: 1069 | Legacy: 2038)
            // ─────────────────────────────────────────────────────────────
            case '3716':
            case '1069':
            case '2038':
                return $this->handleLotValidationResponse($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // 4. LOT TRANSACTION UPLOAD (Annapurna: 3717 | Old: 1072)
            // ─────────────────────────────────────────────────────────────
            case '3717':
            case '1072':
                return $this->handleLotTransactionUpload($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // 5. LOT TRANSACTION INFO (Annapurna: 3718 | Old: 1073)
            // ─────────────────────────────────────────────────────────────
            case '3718':
            case '1073':
                return $this->handleLotTransactionInfo($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // 6. LOT TRANSACTION RESPONSE / DET (Annapurna: 3719 | Old: 1074)
            // ─────────────────────────────────────────────────────────────
            case '3719':
            case '1074':
                return $this->handleLotTransactionResponse($parsed, $data, $rawContent);

            // ─────────────────────────────────────────────────────────────
            // DEFAULT / UNKNOWN ACTION HANDLER
            // ─────────────────────────────────────────────────────────────
            default:
                return $this->handleDefaultAction($parsed);
        }
    }

    /**
     * ACTION 3713 (Right column) / 1060: Lot Validation Upload
     * Decrypts beneficiary records, saves files to bandhan/3713/, and inserts into database.
     */
    public function handleLotValidationUpload(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $triggeredByUserId = $parsed['triggeredByUserId'];
        $applicationId = $parsed['applicationId'];
        $lotNumber = $parsed['lotNumber'];
        $recordCount = $parsed['recordCount'];
        $decryptedPlainText = $parsed['storedFiles']['decryptedPlainText'] ?? null;
        $totalBeneficiariesCount = $parsed['storedFiles']['totalBeneficiariesCount'] ?? 0;
        $validLines = $parsed['storedFiles']['validLines'] ?? [];
        $encryptedRelativePath = $parsed['storedFiles']['encryptedRelativePath'] ?? null;
        $decryptedRelativePath = $parsed['storedFiles']['decryptedRelativePath'] ?? null;
        $encryptedFileName = $parsed['storedFiles']['encryptedFileName'] ?? null;
        $decryptedFileName = $parsed['storedFiles']['decryptedFileName'] ?? null;

        $transactionRecord = null;

        // Perform Database Insertion for validation upload
        if (!empty($decryptedPlainText)) {
            DB::beginTransaction();
            try {
                $transactionRecord = BandhanTransaction::create([
                    'triggered_by_user_id' => $triggeredByUserId,
                    'application_id'       => $applicationId,
                    'action_id'            => $actionId,
                    'lot_number'           => $lotNumber,
                    'record_count'         => $recordCount ?? $totalBeneficiariesCount,
                    'raw_payload_path'     => $encryptedRelativePath ?? "bandhan/{$actionId}/{$encryptedFileName}",
                    'decrypted_file_path'  => $decryptedRelativePath ? ($decryptedRelativePath) : null,
                    'decrypted_data'       => $decryptedPlainText,
                    'status'               => 'SUCCESS',
                ]);

                if ($totalBeneficiariesCount > 0 && !empty($validLines)) {
                    $recordsToInsert = [];
                    $now = now();
                    $batchSize = 1000;

                    foreach ($validLines as $line) {
                        $trimmed = trim($line);
                        $cols = explode('|', $trimmed);

                        $recordsToInsert[] = [
                            'bandhan_transaction_id' => $transactionRecord->id,
                            'lot_number'             => $lotNumber,
                            'transaction_id'         => isset($cols[0]) && $cols[0] !== '' ? $cols[0] : null,
                            'beneficiary_name'       => isset($cols[1]) && $cols[1] !== '' ? $cols[1] : null,
                            'column_3'               => isset($cols[2]) && $cols[2] !== '' ? $cols[2] : null,
                            'account_number'         => isset($cols[3]) && $cols[3] !== '' ? $cols[3] : null,
                            'beneficiary_id'         => isset($cols[4]) && $cols[4] !== '' ? $cols[4] : null,
                            'raw_row'                => $trimmed,
                            'created_at'             => $now,
                            'updated_at'             => $now,
                        ];

                        if (count($recordsToInsert) >= $batchSize) {
                            BandhanTransactionDetail::insert($recordsToInsert);
                            $recordsToInsert = [];
                        }
                    }

                    if (!empty($recordsToInsert)) {
                        BandhanTransactionDetail::insert($recordsToInsert);
                    }
                }

                DB::commit();
            } catch (\Exception $dbEx) {
                DB::rollBack();
                Log::error("DB Insert Error during Bandhan callback (ActionId {$actionId}): " . $dbEx->getMessage());
            }
        }

        if ($actionId === '1060') {
            $responseData = $this->buildStandardSuccessResponse($parsed);
            $response = response()->json(json_encode($responseData), 200);
            $this->logActionResponse($actionId, $response, $lotNumber, ['transaction_id' => $transactionRecord?->id]);
            return $response;
        }

        $responseData = [
            'status'  => 'success',
            'message' => "Action {$actionId} payload decrypted, stored in folder bandhan/{$actionId}/, and processed successfully.",
            'data'    => [
                'transaction_id'          => $transactionRecord ? $transactionRecord->id : null,
                'action_id'               => $actionId,
                'lot_number'              => $lotNumber,
                'encrypted_file'          => $encryptedFileName,
                'decrypted_file'          => $decryptedFileName,
                'folder'                  => "bandhan/{$actionId}",
                'decrypted_records_count' => $totalBeneficiariesCount,
            ]
        ];
        $response = response()->json($responseData, 200);
        $this->logActionResponse($actionId, $response, $lotNumber, ['transaction_id' => $transactionRecord?->id]);
        return $response;
    }

    /**
     * ACTION 3715 (Right column) / 1068: Lot Validation Info
     * Queries database/storage for lot validation statistics (total, success, rejected).
     */
    public function handleLotValidationInfo(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $lotNumber = $parsed['lotNumber'];
        $recordCount = $parsed['recordCount'];

        $totalRecord = 0;
        $successCount = 0;
        $rejectedCount = 0;

        if (!empty($lotNumber)) {
            $numericLot = (int)preg_replace('/\D/', '', (string)$lotNumber);
            // 1. Check av_lot_master from pgsql_payment
            try {
                $master = DB::connection('pgsql_payment')->table('fldc_main.av_lot_master')
                    ->where('lot_no', $numericLot)
                    ->orWhere('lot_no', (string)$lotNumber)
                    ->first(['success_count', 'failed_count', 'ben_count']);
                if ($master && $master->ben_count) {
                    $totalRecord = (int)$master->ben_count;
                    $successCount = (int)$master->success_count;
                    $rejectedCount = (int)$master->failed_count;
                }
            } catch (\Exception $e) {}

            // 2. Check BandhanTransactionDetail
            if ($totalRecord === 0) {
                $detailsCount = BandhanTransactionDetail::where('lot_number', $lotNumber)->count();
                if ($detailsCount > 0) {
                    $totalRecord = $detailsCount;
                    // Check if explicit responses were recorded
                    $dbRej = BandhanTransactionDetail::where('lot_number', $lotNumber)
                        ->where('column_3', 'N')
                        ->count();
                    if ($dbRej > 0) {
                        $rejectedCount = $dbRej;
                    } else {
                        // Natural simulated mix (~3% rejected)
                        $rejectedCount = (int)ceil($totalRecord * 0.03);
                    }
                    $successCount = max(0, $totalRecord - $rejectedCount);
                } else {
                    $tx = BandhanTransaction::where('lot_number', $lotNumber)->latest()->first();
                    if ($tx && $tx->record_count) {
                        $totalRecord = (int)$tx->record_count;
                        $rejectedCount = (int)ceil($totalRecord * 0.03);
                        $successCount = max(0, $totalRecord - $rejectedCount);
                    }
                }
            }
        }

        // Fallback to storage files in folder if database does not contain the lot yet
        if ($totalRecord === 0 && !empty($lotNumber)) {
            $lotSuffixClean = '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber);
            $searchFolders = ["bandhan/{$actionId}", 'bandhan/3713', 'bandhanbentransactionencdata'];
            
            foreach ($searchFolders as $sFolder) {
                if (Storage::disk('local')->exists($sFolder)) {
                    $files = Storage::disk('local')->files($sFolder);
                    $matchingFiles = array_filter($files, fn($f) => str_contains($f, "decrypted{$lotSuffixClean}"));
                    if (!empty($matchingFiles)) {
                        $latestFile = end($matchingFiles);
                        $fileData = Storage::disk('local')->get($latestFile);
                        $lines = array_filter(preg_split('/\r\n|\r|\n/', trim($fileData)), fn($l) => trim($l) !== '');
                        $totalRecord = count($lines);
                        $rej = 0;
                        foreach ($lines as $line) {
                            $cols = explode('|', trim($line));
                            $status = $cols[2] ?? 'Y';
                            $statusCode = $cols[4] ?? '00';
                            $nameStatus = $cols[5] ?? 'Y';
                            $nameStatusCode = $cols[6] ?? '00';

                            $res = $this->evaluateValidationResult($status, $nameStatus, $statusCode, $nameStatusCode);
                            if ($res === 'N') {
                                $rej++;
                            }
                        }
                        $rejectedCount = $rej;
                        $successCount = max(0, $totalRecord - $rejectedCount);
                        break;
                    }
                }
            }
        }

        if ($totalRecord === 0 && !empty($recordCount)) {
            $totalRecord = (int)$recordCount;
            $successCount = $totalRecord;
            $rejectedCount = 0;
        }

        $recordsList = [
            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? ""), "Dt" => ""],
            ["Fn" => "totalRecord", "Fv" => (string)$totalRecord, "Dt" => ""],
            ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
            ["Fn" => "successCount", "Fv" => (string)$successCount, "Dt" => ""],
            ["Fn" => "rejectedCount", "Fv" => (string)$rejectedCount, "Dt" => ""]
        ];

        $responseData = $this->buildTupleResponse($parsed, $recordsList);
        $response = response()->json(json_encode($responseData), 200);
        $this->logActionResponse($actionId, $response, $lotNumber, ['totalRecord' => $totalRecord, 'successCount' => $successCount]);
        return $response;
    }

    /**
     * Official Validation Response status codes and descriptions from Bank/UIDAI specification.
     */
    protected array $validationFailureTypes = [
        ['code' => 'U1', 'remarks' => 'Pi (basic) attributes of demographic data did not match'],
        ['code' => '2',  'remarks' => 'Not available in DB'],
        ['code' => '4',  'remarks' => 'Cancelled by UIDAI'],
        ['code' => 'X8', 'remarks' => 'Invalid Aadhaar Number'],
        ['code' => 'FA', 'remarks' => 'Aadhaar deactivated due to deceased status'],
        ['code' => 'X7', 'remarks' => 'Aadhaar suspended (Aadhaar is not in authenticatable status)'],
        ['code' => '3',  'remarks' => 'Not Valid Aadhaar number'],
        ['code' => '20', 'remarks' => 'Format Error'],
        ['code' => 'X9', 'remarks' => 'Aadhaar cancelled (Aadhaar is no in authenticatable status)'],
        ['code' => 'KT', 'remarks' => 'Aadhaar locked by Aadhaar number holder for all authentications'],
        ['code' => '1',  'remarks' => 'Inactive'],
    ];

    /**
     * Generate simulated validation status ensuring at least 5 of every status code.
     *
     * @param int $index
     * @param int $totalCount
     * @return array{status: string, remarks: string, statusCode: string}
     */
    public function getSimulatedValidationStatus(int $index, int $totalCount = 0): array
    {
        $failCount = count($this->validationFailureTypes); // 11
        $reservedFailureSlots = $failCount * 5; // 55 slots (5 of each failure type)

        // First 55 records: guaranteed at least 5 of each of the 11 failure types
        if ($index < $reservedFailureSlots) {
            $typeIdx = (int)floor($index / 5);
            $type = $this->validationFailureTypes[$typeIdx % $failCount];
            return [
                'status'     => 'N',
                'remarks'    => $type['remarks'],
                'statusCode' => $type['code'],
            ];
        }

        // Periodic failures throughout the rest of the lot (every 35th record)
        if ($index % 35 === 0) {
            $typeIdx = (int)(($index / 35) % $failCount);
            $type = $this->validationFailureTypes[$typeIdx];
            return [
                'status'     => 'N',
                'remarks'    => $type['remarks'],
                'statusCode' => $type['code'],
            ];
        }

        // All other records are Approved / Successful (code 00)
        return [
            'status'     => 'Y',
            'remarks'    => 'Approved',
            'statusCode' => '00',
        ];
    }

    /**
     * Evaluate overall validation result ('Y' or 'N') based on account validation status,
     * name validation status, and status codes.
     *
     * @param string|null $status         Account validation status ('Y' / 'N')
     * @param string|null $nameStatus     Name validation status ('Y' / 'N' / empty)
     * @param string|null $statusCode     Account status code (e.g. '00', 'U1', '01', '51')
     * @param string|null $nameStatusCode Name status code (e.g. '00', '01', '99')
     * @return string                     'Y' for successful validation, 'N' for failure/rejection
     */
    public function evaluateValidationResult(?string $status, ?string $nameStatus = null, ?string $statusCode = null, ?string $nameStatusCode = null): string
    {
        $status = strtoupper(trim((string)$status));
        $nameStatus = strtoupper(trim((string)$nameStatus));
        $statusCode = trim((string)$statusCode);
        $nameStatusCode = trim((string)$nameStatusCode);

        // 1. Account validation status must be 'Y'
        if ($status !== 'Y') {
            return 'N';
        }

        // 2. Account status code: '00' or empty is success, any error code (e.g. 'U1', '01', '51') is failure
        if ($statusCode !== '' && $statusCode !== '00') {
            return 'N';
        }

        // 3. Name validation: if present, 'N' or error code (e.g. '01', '99') indicates mismatch/failure
        if ($nameStatus === 'N') {
            return 'N';
        }
        if ($nameStatusCode !== '' && $nameStatusCode !== '00') {
            return 'N';
        }

        return 'Y';
    }

    /**
     * Execute fldc_main.validation_lot_response(in_dist_code, in_lot_no, in_response)
     * either directly as Postgres function or via equivalent Eloquent/DB queries.
     *
     * @param string|int  $lotNo
     * @param string      $responseText
     * @param int|null    $distCode
     * @return array      Summary containing success_count, failed_count, and total_records
     */
    public function executeValidationLotResponseProcedure(string|int $lotNo, string $responseText, ?int $distCode = null): array
    {
        $numericLotNo = (int)preg_replace('/\D/', '', (string)$lotNo);
        $inDistCode = $distCode;

        // Resolve district code if not provided
        if (empty($inDistCode)) {
            try {
                $master = DB::connection('pgsql_payment')->table('fldc_main.av_lot_master')
                    ->where('lot_no', $numericLotNo)
                    ->orWhere('lot_no', (string)$lotNo)
                    ->first(['lgd_district_code']);
                if ($master && !empty($master->lgd_district_code)) {
                    $inDistCode = (int)$master->lgd_district_code;
                }
            } catch (\Exception $e) {
                Log::warning("Could not fetch lgd_district_code from fldc_main.av_lot_master: " . $e->getMessage());
            }

            if (empty($inDistCode)) {
                try {
                    $detail = DB::connection('pgsql_payment')->table('fldc_main.av_lot_details')
                        ->where('lot_no', $numericLotNo)
                        ->orWhere('lot_no', (string)$lotNo)
                        ->first(['lgd_district_code']);
                    if ($detail && !empty($detail->lgd_district_code)) {
                        $inDistCode = (int)$detail->lgd_district_code;
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not fetch lgd_district_code from fldc_main.av_lot_details: " . $e->getMessage());
                }
            }

            if (empty($inDistCode)) {
                $inDistCode = (int)config('bandhan.default_dist_code', 0);
            }
        }

        // 1. Try calling the PostgreSQL function directly
        try {
            DB::connection('pgsql_payment')->statement(
                'SELECT fldc_main.validation_lot_response(?, ?, ?)',
                [$inDistCode, $numericLotNo, $responseText]
            );
            Log::info("Successfully executed fldc_main.validation_lot_response({$inDistCode}, {$numericLotNo})");
        } catch (\Exception $dbFuncEx) {
            Log::warning("Direct call to fldc_main.validation_lot_response failed: " . $dbFuncEx->getMessage() . " - Proceeding with PHP query execution");

            // 2. PHP Query Execution fallback matching the exact SQL logic
            try {
                $lines = array_filter(preg_split('/\r\n|\r|\n/', trim($responseText)), fn($l) => trim($l) !== '');
                $successCount = 0;
                $failedCount = 0;

                DB::connection('pgsql_payment')->beginTransaction();

                foreach ($lines as $line) {
                    $cols = explode('|', trim($line));
                    $ldId = !empty($cols[0]) ? (int)$cols[0] : null;
                    $familySerial = !empty($cols[1]) ? (int)$cols[1] : null;
                    $status = trim($cols[2] ?? '');
                    $remarks = trim($cols[3] ?? '');
                    $statusCode = trim($cols[4] ?? '');
                    $nameStatus = trim($cols[5] ?? '');
                    $nameStatusCode = trim($cols[6] ?? '');
                    $nameResponse = trim($cols[7] ?? '');

                    // Calculate final status per condition matrix
                    $finalStatus = $this->evaluateValidationResult($status, $nameStatus, $statusCode, $nameStatusCode);

                    if ($finalStatus === 'Y') {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }

                    if ($familySerial) {
                        // Update fldc_main.av_lot_details
                        DB::connection('pgsql_payment')->table('fldc_main.av_lot_details')
                            ->where('family_serial', $familySerial)
                            ->where('lot_no', $numericLotNo)
                            ->when($inDistCode > 0, fn($q) => $q->where('lgd_district_code', $inDistCode))
                            ->whereNull('status')
                            ->where('response_status', 'N')
                            ->update([
                                'status'            => $finalStatus,
                                'status_code'       => $statusCode,
                                'remarks'           => $remarks,
                                'name_status'       => $nameStatus,
                                'name_status_code'  => $nameStatusCode,
                                'name_response'     => $nameResponse,
                                'av_account_status' => $status,
                                'updated_at'        => now(),
                                'response_status'   => 'P',
                            ]);

                        // Update payment.ben_payment_details
                        $accValidatedVal = ($finalStatus === 'Y') ? 2 : 3;
                        DB::connection('pgsql_payment')->table('payment.ben_payment_details')
                            ->where('family_serial', $familySerial)
                            ->when($inDistCode > 0, fn($q) => $q->where('lgd_district_code', $inDistCode))
                            ->where('acc_validated', 1)
                            ->update(['acc_validated' => $accValidatedVal]);
                    }
                }

                // Insert into fldc_main.validation_failed_details
                $failedRows = DB::connection('pgsql_payment')->table('fldc_main.av_lot_details as av')
                    ->join('payment.ben_payment_details as b', 'av.family_serial', '=', 'b.family_serial')
                    ->where('av.lot_no', $numericLotNo)
                    ->where('av.status', 'N')
                    ->where('av.response_status', 'P')
                    ->when($inDistCode > 0, fn($q) => $q->where('av.lgd_district_code', $inDistCode)->where('b.lgd_district_code', $inDistCode))
                    ->select([
                        'av.lgd_district_code', 'av.local_body_code', 'av.lot_no', 'av.app_serial',
                        'av.family_serial', 'av.family_id', 'av.member_id', 'av.status_code',
                        'av.remarks', 'av.aadhaar_no', 'av.pmt_mode', 'av.name_status',
                        'av.name_status_code', 'av.name_response', 'av.av_account_status',
                        'b.mobile_no', 'b.member_name'
                    ])->get();

                if ($failedRows->isNotEmpty()) {
                    $failedInserts = [];
                    $now = now();
                    foreach ($failedRows as $row) {
                        $failedType = 4;
                        if ($row->name_status === 'N' && $row->av_account_status === 'Y' && in_array($row->name_status_code, ['01', '99'])) {
                            $failedType = 3;
                        } elseif ($row->av_account_status === 'N') {
                            $failedType = 1;
                        }

                        $failedInserts[] = [
                            'lgd_district_code' => $row->lgd_district_code,
                            'local_body_code'   => $row->local_body_code,
                            'lot_no'            => $row->lot_no,
                            'app_serial'        => $row->app_serial,
                            'family_serial'     => $row->family_serial,
                            'family_id'         => $row->family_id,
                            'member_id'         => $row->member_id,
                            'status_code'       => $row->status_code,
                            'remarks'           => $row->remarks,
                            'aadhaar_no'        => $row->aadhaar_no,
                            'pmt_mode'          => $row->pmt_mode,
                            'failed_type'       => $failedType,
                            'edited_status'     => 0,
                            'created_at'        => $now,
                            'name_status'       => $row->name_status,
                            'name_status_code'  => $row->name_status_code,
                            'name_response'     => $row->name_response,
                            'mobile_no'         => $row->mobile_no,
                            'member_name'       => $row->member_name,
                        ];
                    }
                    DB::connection('pgsql_payment')->table('fldc_main.validation_failed_details')->insert($failedInserts);
                }

                // Update response_status = 'Y'
                DB::connection('pgsql_payment')->table('fldc_main.av_lot_details')
                    ->where('lot_no', $numericLotNo)
                    ->when($inDistCode > 0, fn($q) => $q->where('lgd_district_code', $inDistCode))
                    ->whereNotNull('status')
                    ->where('response_status', 'P')
                    ->update(['response_status' => 'Y']);

                // Update fldc_main.av_lot_master
                DB::connection('pgsql_payment')->table('fldc_main.av_lot_master')
                    ->where('lot_no', $numericLotNo)
                    ->when($inDistCode > 0, fn($q) => $q->where('lgd_district_code', $inDistCode))
                    ->update([
                        'success_count'  => $successCount,
                        'failed_count'   => $failedCount,
                        'lot_status'     => DB::raw("CASE WHEN ben_count != {$successCount} + {$failedCount} THEN 'P' ELSE 'C' END"),
                        'response_count' => DB::raw("COALESCE(response_count, 0) + 1"),
                        'updated_at'     => now(),
                    ]);

                DB::connection('pgsql_payment')->commit();
            } catch (\Exception $fallbackEx) {
                DB::connection('pgsql_payment')->rollBack();
                Log::error("PHP DB query execution error for validation lot response: " . $fallbackEx->getMessage());
            }
        }

        // Fetch latest master counts
        $finalSuccess = 0;
        $finalFailed = 0;
        try {
            $masterRec = DB::connection('pgsql_payment')->table('fldc_main.av_lot_master')
                ->where('lot_no', $numericLotNo)
                ->when($inDistCode > 0, fn($q) => $q->where('lgd_district_code', $inDistCode))
                ->first(['success_count', 'failed_count']);
            if ($masterRec) {
                $finalSuccess = (int)$masterRec->success_count;
                $finalFailed = (int)$masterRec->failed_count;
            }
        } catch (\Exception $e) {}

        return [
            'dist_code'     => $inDistCode,
            'lot_no'        => $numericLotNo,
            'success_count' => $finalSuccess,
            'failed_count'  => $finalFailed,
            'total_record'  => $finalSuccess + $finalFailed,
        ];
    }

    /**
     * ACTION 3716 (Right column) / 1069: Lot Validation Response / Det
     * Pulls validation status rows from database, encrypts payload, and saves to bandhan/3716/
     * with dynamic successCount, rejectedCount, and totalRecord calculations.
     */
    public function handleLotValidationResponse(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $lotNumber = $parsed['lotNumber'];
        $encryptedData = $parsed['encryptedData'];
        $folder = $this->getActionFolder($actionId);

        if (empty($encryptedData)) {
            $lotNo = $lotNumber;
            if ($lotNo === null) {
                $errResponse = response()->json([
                    'status' => 'error',
                    'message' => 'Lot number not found in request data'
                ], 400);
                $this->logActionResponse($actionId, $errResponse, $lotNo);
                return $errResponse;
            }

            $timestamp = date('Ymd_His');
            $filename = "enc_beneficiary_response_{$lotNo}_{$timestamp}.txt";

            try {
                // Check fldc_main.av_lot_details first, fallback to lb_main.av_lot_details
                $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('fldc_main.av_lot_details')
                    ->where('lot_no', $lotNo)
                    ->orWhere('lot_no', (int)preg_replace('/\D/', '', (string)$lotNo))
                    ->get();
                if ($lotBeneficiaryDetails->isEmpty()) {
                    $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('lb_main.av_lot_details')
                        ->where('lot_no', $lotNo)
                        ->get();
                }
            } catch (\Exception $e) {
                Log::warning("Could not query pgsql_payment av_lot_details: " . $e->getMessage());
                $lotBeneficiaryDetails = collect();
            }

            $finalData = [];
            $successCount = 0;
            $rejectedCount = 0;
            $pendingCount = 0;

            // Check if dynamic simulation counts were requested
            $reqSuccess = $rawData['responseSuccess'] ?? $rawData['successCount'] ?? $parsed['methodArgs']['responseSuccess'] ?? $parsed['methodArgs']['successCount'] ?? null;
            $reqFailed = $rawData['responseFailed'] ?? $rawData['rejectedCount'] ?? $parsed['methodArgs']['responseFailed'] ?? $parsed['methodArgs']['rejectedCount'] ?? null;
            $reqPending = $rawData['responsePending'] ?? $rawData['pendingCount'] ?? $parsed['methodArgs']['responsePending'] ?? $parsed['methodArgs']['pendingCount'] ?? null;

            $hasSimulationCounts = ($reqSuccess !== null || $reqFailed !== null);
            $successLimit = $reqSuccess !== null ? (int)$reqSuccess : null;
            $failedLimit = $reqFailed !== null ? (int)$reqFailed : null;
            $pendingLimit = $reqPending !== null ? (int)$reqPending : null;

            $failTypeCount = count($this->validationFailureTypes);

            if ($lotBeneficiaryDetails->isEmpty()) {
                // Fallback to local BandhanTransactionDetail
                $localDetails = BandhanTransactionDetail::where('lot_number', $lotNo)->get();
                if ($localDetails->isNotEmpty()) {
                    $counter = 0;
                    $localTotal = count($localDetails);
                    foreach ($localDetails as $detail) {
                        $ldId = $detail->transaction_id ?? $detail->id ?? '';
                        $familySerial = $detail->beneficiary_id ?? '';

                        if ($hasSimulationCounts) {
                            if ($successLimit !== null && $counter < $successLimit) {
                                $status = 'Y';
                                $remarks = 'Approved';
                                $statusCode = '00';
                                $nameStatus = '';
                                $nameStatusCode = '';
                                $nameResponse = '';
                                $successCount++;
                            } elseif ($failedLimit !== null && $counter < (($successLimit ?? 0) + $failedLimit)) {
                                $rejIdx = $counter - ($successLimit ?? 0);
                                $typeIdx = (int)floor($rejIdx / 5) % $failTypeCount;
                                $fail = $this->validationFailureTypes[$typeIdx];
                                $status = 'N';
                                $remarks = $fail['remarks'];
                                $statusCode = $fail['code'];
                                $nameStatus = '';
                                $nameStatusCode = '';
                                $nameResponse = '';
                                $rejectedCount++;
                            } else {
                                if ($pendingLimit !== null && $counter >= (($successLimit ?? 0) + ($failedLimit ?? 0) + $pendingLimit)) {
                                    break;
                                }
                                $status = 'N';
                                $remarks = 'Pending Validation';
                                $statusCode = '51';
                                $nameStatus = '';
                                $nameStatusCode = '';
                                $nameResponse = '';
                                $pendingCount++;
                            }
                        } else {
                            // Guaranteed at least 5 of every status code from the official table
                            $sim = $this->getSimulatedValidationStatus($counter, $localTotal);
                            $status = $sim['status'];
                            $remarks = $sim['remarks'];
                            $statusCode = $sim['statusCode'];
                            $nameStatus = '';
                            $nameStatusCode = '';
                            $nameResponse = '';

                            if ($status === 'Y') {
                                $successCount++;
                            } else {
                                $rejectedCount++;
                            }
                        }

                        $row = "{$ldId}|{$familySerial}|{$status}|{$remarks}|{$statusCode}|{$nameStatus}|{$nameStatusCode}|{$nameResponse}\n";
                        $finalData[] = $row;
                        $counter++;
                    }
                } else {
                    $errResponse = response()->json([
                        'status' => 'error',
                        'message' => 'No beneficiary details found for lot number: ' . $lotNo
                    ], 404);
                    $this->logActionResponse($actionId, $errResponse, $lotNo);
                    return $errResponse;
                }
            } else {
                $counter = 0;
                $dbTotal = count($lotBeneficiaryDetails);
                foreach ($lotBeneficiaryDetails as $detail) {
                    $ldId = $detail->ld_id ?? $detail->transaction_id ?? $detail->id ?? '';
                    $familySerial = $detail->family_serial ?? $detail->ben_id ?? $detail->beneficiary_id ?? '';

                    if ($hasSimulationCounts) {
                        if ($successLimit !== null && $counter < $successLimit) {
                            $status = 'Y';
                            $remarks = 'Approved';
                            $statusCode = '00';
                            $nameStatus = '';
                            $nameStatusCode = '';
                            $nameResponse = '';
                            $successCount++;
                        } elseif ($failedLimit !== null && $counter < (($successLimit ?? 0) + $failedLimit)) {
                            $rejIdx = $counter - ($successLimit ?? 0);
                            $typeIdx = (int)floor($rejIdx / 5) % $failTypeCount;
                            $fail = $this->validationFailureTypes[$typeIdx];
                            $status = 'N';
                            $remarks = $fail['remarks'];
                            $statusCode = $fail['code'];
                            $nameStatus = '';
                            $nameStatusCode = '';
                            $nameResponse = '';
                            $rejectedCount++;
                        } else {
                            if ($pendingLimit !== null && $counter >= (($successLimit ?? 0) + ($failedLimit ?? 0) + $pendingLimit)) {
                                break;
                            }
                            $status = 'N';
                            $remarks = 'Pending Validation';
                            $statusCode = '51';
                            $nameStatus = '';
                            $nameStatusCode = '';
                            $nameResponse = '';
                            $pendingCount++;
                        }
                    } else {
                        $hasDbStatus = !empty($detail->status) || !empty($detail->av_account_status);
                        if ($hasDbStatus) {
                            $rawStatus = strtoupper(trim($detail->status ?? $detail->av_account_status));
                            $remarks = trim($detail->remarks ?? '');
                            $statusCode = trim((string)($detail->status_code ?? $detail->acc_status_code ?? '00'));
                            $nameStatus = strtoupper(trim($detail->name_status ?? ''));
                            $nameStatusCode = trim((string)($detail->name_status_code ?? ''));
                            $nameResponse = trim($detail->name_response ?? '');

                            $result = $this->evaluateValidationResult($rawStatus, $nameStatus, $statusCode, $nameStatusCode);
                            if ($result === 'N') {
                                $status = 'N';
                                $remarks = $remarks !== '' ? $remarks : 'Pi (basic) attributes of demographic data did not match';
                                $statusCode = ($statusCode !== '' && $statusCode !== '00') ? $statusCode : 'U1';
                                $rejectedCount++;
                            } else {
                                $status = 'Y';
                                $remarks = $remarks !== '' ? $remarks : 'Approved';
                                $statusCode = $statusCode !== '' ? $statusCode : '00';
                                $successCount++;
                            }
                        } else {
                            // Guaranteed at least 5 of every status code from the official table
                            $sim = $this->getSimulatedValidationStatus($counter, $dbTotal);
                            $status = $sim['status'];
                            $remarks = $sim['remarks'];
                            $statusCode = $sim['statusCode'];
                            $nameStatus = '';
                            $nameStatusCode = '';
                            $nameResponse = '';

                            if ($status === 'Y') {
                                $successCount++;
                            } else {
                                $rejectedCount++;
                            }
                        }
                    }

                    $row = "{$ldId}|{$familySerial}|{$status}|{$remarks}|{$statusCode}|{$nameStatus}|{$nameStatusCode}|{$nameResponse}\n";
                    $finalData[] = $row;
                    $counter++;
                }
            }

            $totalRecord = count($finalData);
            $plainTextBeneficiaryData = implode('', $finalData);
            
            // Save in action folder & backwards-compatible folder
            Storage::disk('local')->put("{$folder}/{$filename}", $plainTextBeneficiaryData);
            Storage::disk('local')->put("bandhanbeneficiaryencdata/{$filename}", $plainTextBeneficiaryData);

            $compressed = gzcompress($plainTextBeneficiaryData, 9);
            $base64Data = base64_encode($compressed);
            $encryptedOut = BandhanPayment::encryptCode($base64Data);

            $recordsList = [
                ["Fn" => "Record", "Fv" => $encryptedOut, "Dt" => ""],
                ["Fn" => "lotNumber", "Fv" => (string)$lotNo, "Dt" => ""],
                ["Fn" => "totalRecord", "Fv" => (string)$totalRecord, "Dt" => ""],
                ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                ["Fn" => "successCount", "Fv" => (string)$successCount, "Dt" => ""],
                ["Fn" => "rejectedCount", "Fv" => (string)$rejectedCount, "Dt" => ""],
                ["Fn" => "pendingCount", "Fv" => (string)$pendingCount, "Dt" => ""]
            ];

            $responseData = $this->buildTupleResponse($parsed, $recordsList, 0);
            $response = response()->json(json_encode($responseData), 200);
            $this->logActionResponse($actionId, $response, $lotNo, [
                'totalRecord'   => $totalRecord,
                'successCount'  => $successCount,
                'rejectedCount' => $rejectedCount,
                'pendingCount'  => $pendingCount,
            ]);
            return $response;
        }

        // When encrypted data was provided by bank (push callback)
        $decryptedPlainText = $parsed['storedFiles']['decryptedPlainText'] ?? null;
        $procResult = [];

        if (!empty($decryptedPlainText)) {
            // Execute the fldc_main.validation_lot_response procedure
            $procResult = $this->executeValidationLotResponseProcedure($lotNumber, $decryptedPlainText);
        }

        $validLines = $parsed['storedFiles']['validLines'] ?? [];
        $totalRecord = count($validLines);
        $successCount = $procResult['success_count'] ?? 0;
        $rejectedCount = $procResult['failed_count'] ?? 0;

        if ($successCount === 0 && $rejectedCount === 0 && !empty($validLines)) {
            foreach ($validLines as $line) {
                $parts = explode('|', trim($line));
                $status = $parts[2] ?? 'Y';
                $statusCode = $parts[4] ?? '00';
                $nameStatus = $parts[5] ?? 'Y';
                $nameStatusCode = $parts[6] ?? '00';

                $result = $this->evaluateValidationResult($status, $nameStatus, $statusCode, $nameStatusCode);
                if ($result === 'N') {
                    $rejectedCount++;
                } else {
                    $successCount++;
                }
            }
        }

        $response = response()->json([
            'status'  => 'success',
            'message' => "Action {$actionId} validation lot response processed and executed via validation_lot_response procedure successfully.",
            'data'    => [
                'action_id'       => $actionId,
                'lot_number'      => $lotNumber,
                'folder'          => $folder,
                'totalRecord'     => $totalRecord,
                'successCount'    => $successCount,
                'rejectedCount'   => $rejectedCount,
                'proc_result'     => $procResult,
                'encrypted_file'  => $parsed['storedFiles']['encryptedFileName'] ?? null,
                'decrypted_file'  => $parsed['storedFiles']['decryptedFileName'] ?? null,
            ]
        ], 200);

        $this->logActionResponse($actionId, $response, $lotNumber, [
            'totalRecord'   => $totalRecord,
            'successCount'  => $successCount,
            'rejectedCount' => $rejectedCount,
            'proc_result'   => $procResult,
        ]);
        return $response;
    }

    /**
     * ACTION 3717 (Right column) / 1072: Lot Transaction Upload
     * Receives and processes lot transaction upload, saving files into bandhan/3717/.
     */
    public function handleLotTransactionUpload(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $folder = $this->getActionFolder($actionId);
        $lotNumber = $parsed['lotNumber'];

        if ($actionId === '1072') {
            $responseData = $this->buildStandardSuccessResponse($parsed, 'This lot number is already exists');
            $response = response()->json(json_encode($responseData), 200);
            $this->logActionResponse($actionId, $response, $lotNumber);
            return $response;
        }

        $response = response()->json([
            'status'  => 'success',
            'message' => "Action {$actionId} lot transaction upload processed and saved into {$folder}/.",
            'data'    => [
                'action_id'               => $actionId,
                'lot_number'              => $lotNumber,
                'folder'                  => $folder,
                'encrypted_file'          => $parsed['storedFiles']['encryptedFileName'] ?? null,
                'decrypted_file'          => $parsed['storedFiles']['decryptedFileName'] ?? null,
                'decrypted_records_count' => $parsed['storedFiles']['totalBeneficiariesCount'] ?? 0,
            ]
        ], 200);
        $this->logActionResponse($actionId, $response, $lotNumber);
        return $response;
    }

    /**
     * ACTION 3718 (Right column) / 1073: Lot Transaction Info
     * Queries lot transaction status and counts, returning formatted response.
     */
    public function handleLotTransactionInfo(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $lotNumber = $parsed['lotNumber'];
        $recordCount = $parsed['recordCount'];

        $recordsList = [
            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? "T303202604132443"), "Dt" => ""],
            ["Fn" => "totalRecord", "Fv" => (string)($recordCount ?? "16"), "Dt" => ""],
            ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
            ["Fn" => "successCount", "Fv" => (string)($recordCount ?? "16"), "Dt" => ""],
            ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""]
        ];

        $responseData = $this->buildTupleResponse($parsed, $recordsList);
        $response = response()->json(json_encode($responseData), 200);
        $this->logActionResponse($actionId, $response, $lotNumber, ['recordCount' => $recordCount]);
        return $response;
    }

    /**
     * ACTION 3719 (Right column) / 1074: Lot Transaction Response / Det
     * Pulls transaction detail rows from database, encrypts payload, and saves to bandhan/3719/.
     */
    public function handleLotTransactionResponse(array $parsed, array $rawData, ?string $rawContent = null)
    {
        $actionId = $parsed['actionId'];
        $lotNumber = $parsed['lotNumber'];
        $encryptedData = $parsed['encryptedData'];
        $folder = $this->getActionFolder($actionId);

        if (empty($encryptedData)) {
            $lotNo = $lotNumber;
            if ($lotNo === null) {
                $errResponse = response()->json([
                    'status' => 'error',
                    'message' => 'Lot number not found in request data'
                ], 400);
                $this->logActionResponse($actionId, $errResponse, $lotNo);
                return $errResponse;
            }

            $timestamp = date('Ymd_His');
            $filename = "enc_ben_transaction_response_{$lotNo}_{$timestamp}.txt";

            try {
                $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('bandhan.lot_details')
                    ->where('lot_no', $lotNo)
                    ->get();
            } catch (\Exception $e) {
                Log::warning("Could not query pgsql_payment bandhan.lot_details: " . $e->getMessage());
                $lotBeneficiaryDetails = collect();
            }

            if ($lotBeneficiaryDetails->isEmpty()) {
                // Fallback to local BandhanTransactionDetail
                $localDetails = BandhanTransactionDetail::where('lot_number', $lotNo)->get();
                if ($localDetails->isNotEmpty()) {
                    $finalData = [];
                    foreach ($localDetails as $detail) {
                        $row = ($detail->transaction_id ?? $detail->id) . '|' . ($detail->beneficiary_id ?? '') . "|00|\n";
                        $finalData[] = $row;
                    }
                } else {
                    $errResponse = response()->json([
                        'status' => 'error',
                        'message' => 'No beneficiary details found for lot number: ' . $lotNo
                    ], 404);
                    $this->logActionResponse($actionId, $errResponse, $lotNo);
                    return $errResponse;
                }
            } else {
                $finalData = [];
                foreach ($lotBeneficiaryDetails as $detail) {
                    $transaction_id = $detail->ld_id;
                    $uniqueId = $detail->ben_id;
                    $row = $transaction_id . '|' . $uniqueId . "|00|\n";
                    $finalData[] = $row;
                }
            }

            $decryptData = implode('', $finalData);
            
            // Store in action-specific folder & backwards-compatible folder
            Storage::disk('local')->put("{$folder}/{$filename}", $decryptData);
            Storage::disk('local')->put("bandhanbentransactionencdata/{$filename}", $decryptData);

            $compressed = gzcompress($decryptData, 9);
            $base64Data = base64_encode($compressed);
            $encryptedTransactionData = BandhanPayment::encryptCode($base64Data);

            $recordsList = [
                ["Fn" => "responseData", "Fv" => $encryptedTransactionData, "Dt" => ""]
            ];

            $responseData = $this->buildTupleResponse($parsed, $recordsList, 0);
            $response = response()->json(json_encode($responseData), 200);
            $this->logActionResponse($actionId, $response, $lotNo, ['transaction_records' => count($finalData)]);
            return $response;
        }

        $response = response()->json([
            'status'  => 'success',
            'message' => "Action {$actionId} transaction response payload processed and saved to {$folder}/.",
            'data'    => [
                'action_id'  => $actionId,
                'lot_number' => $lotNumber,
                'folder'     => $folder,
            ]
        ], 200);
        $this->logActionResponse($actionId, $response, $lotNumber);
        return $response;
    }

    /**
     * Default response handler for unmapped or generic action IDs.
     */
    public function handleDefaultAction(array $parsed)
    {
        $actionId = $parsed['actionId'];
        $folder = $this->getActionFolder($actionId);

        $response = response()->json([
            'status'  => 'success',
            'message' => "Payload processed and stored in {$folder}/ successfully.",
            'data'    => [
                'action_id'               => $actionId,
                'lot_number'              => $parsed['lotNumber'],
                'folder'                  => $folder,
                'encrypted_file'          => $parsed['storedFiles']['encryptedFileName'] ?? null,
                'decrypted_file'          => $parsed['storedFiles']['decryptedFileName'] ?? null,
                'decrypted_records_count' => $parsed['storedFiles']['totalBeneficiariesCount'] ?? 0,
            ]
        ], 200);

        $this->logActionResponse($actionId, $response, $parsed['lotNumber']);
        return $response;
    }

    /**
     * Parse core fields from request data and MethodArg.
     */
    public function parsePayload(array $data, ?string $rawContent = null): array
    {
        $actionId = (string)($data['ActionId'] ?? request()->input('ActionId', ''));
        $triggeredByUserId = $data['TriggeredByUserId'] ?? request()->input('TriggeredByUserId');
        $applicationId = $data['ApplicationId'] ?? request()->input('ApplicationId');
        $lotNumber = null;
        $recordCount = null;
        $encryptedData = null;

        if (!empty($data['MethodArg']) && is_array($data['MethodArg'])) {
            foreach ($data['MethodArg'] as $arg) {
                $fn = $arg['FieldName'] ?? '';
                if ($fn === '@data') {
                    $encryptedData = $arg['Value'] ?? null;
                } elseif ($fn === '@lotNumber') {
                    $lotNumber = $arg['Value'] ?? null;
                } elseif ($fn === '@recordCount') {
                    $recordCount = $arg['Value'] ?? null;
                }
            }
        }

        if (!$encryptedData && is_array($data)) {
            $encryptedData = $data['data'] ?? $data['Value'] ?? $data['responseData'] ?? null;
        }

        return [
            'actionId'          => $actionId,
            'triggeredByUserId' => $triggeredByUserId,
            'applicationId'     => $applicationId,
            'lotNumber'         => $lotNumber,
            'recordCount'       => $recordCount,
            'encryptedData'     => $encryptedData,
        ];
    }

    /**
     * Store raw encrypted and decrypted payload files in the action's dedicated folder.
     */
    protected function storeActionPayloadFiles(array $parsed, array $data, ?string $rawContent = null): array
    {
        $actionId = $parsed['actionId'];
        $folder = $this->getActionFolder($actionId);
        $lotNumber = $parsed['lotNumber'];
        $encryptedData = $parsed['encryptedData'];

        $timestamp = date('Ymd_His');
        $lotSuffix = !empty($lotNumber) ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber) : '';

        // 1. Raw Payload / Encrypted File
        $encryptedFileName = "encrypted{$lotSuffix}_{$timestamp}.txt";
        $formattedJson = !empty($data)
            ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : $rawContent;

        if (!empty($formattedJson)) {
            // Save in dedicated action folder
            Storage::disk('local')->put("{$folder}/{$encryptedFileName}", $formattedJson);
            // Save in legacy general folder for backward compatibility
            Storage::disk('local')->put("bandhanbentransactionencdata/{$encryptedFileName}", $formattedJson);
        }

        $decryptedPlainText = null;
        $decryptedFileName = null;
        $validLines = [];
        $totalBeneficiariesCount = 0;

        // 2. Decrypted File
        if (!empty($encryptedData)) {
            $decryptedBinary = BandhanPayment::decryptCode($encryptedData);
            if ($decryptedBinary !== false) {
                $plainText = @gzuncompress($decryptedBinary);
                if ($plainText === false) {
                    $plainText = @gzinflate($decryptedBinary);
                }
                if ($plainText === false) {
                    $plainText = $decryptedBinary;
                }

                $decryptedPlainText = $plainText;
                $decryptedFileName = "decrypted{$lotSuffix}_{$timestamp}.txt";

                // Save decrypted text in dedicated action folder
                Storage::disk('local')->put("{$folder}/{$decryptedFileName}", $plainText);
                // Save in legacy general folder for backward compatibility
                Storage::disk('local')->put("bandhanbentransactionencdata/{$decryptedFileName}", $plainText);

                $lines = preg_split('/\r\n|\r|\n/', trim($decryptedPlainText));
                $validLines = array_values(array_filter($lines, fn($line) => trim($line) !== ''));
                $totalBeneficiariesCount = count($validLines);
            }
        }

        return [
            'encryptedFileName'       => $encryptedFileName,
            'encryptedRelativePath'   => "{$folder}/{$encryptedFileName}",
            'decryptedFileName'       => $decryptedFileName,
            'decryptedRelativePath'   => $decryptedFileName ? "{$folder}/{$decryptedFileName}" : null,
            'decryptedPlainText'      => $decryptedPlainText,
            'validLines'              => $validLines,
            'totalBeneficiariesCount' => $totalBeneficiariesCount,
        ];
    }

    /**
     * Standard success JSON structure expected by Bandhan Bank.
     */
    protected function buildStandardSuccessResponse(array $parsed, string $errorMessage = ''): array
    {
        return [
            "RemoteIP"          => null,
            "ApplicationId"     => (int)($parsed['applicationId'] ?? -999),
            "TriggeredByUserId" => (string)($parsed['triggeredByUserId'] ?? ""),
            "ActionId"          => (int)($parsed['actionId'] ?? 0),
            "ActionMethodName"  => "",
            "ActionNameSpace"   => "",
            "GroupMethodName"   => "",
            "GroupNameSpace"    => "",
            "NoOfArguments"     => 0,
            "RequestType"       => "",
            "MethodArg"         => [],
            "MethodArgLite"     => [],
            "DbTuple"           => [],
            "SqlScriptList"     => [],
            "Base64ObjectString"=> "",
            "DeviceTypeId"      => 0,
            "DeviceId"          => "",
            "DbTupleLite"       => [],
            "ApiTrailId"        => -999,
            "TransactionId"     => 0,
            "Rrn"               => "",
            "ExtRefNo"          => "",
            "Base64Objects"     => [],
            "IsActionBlocked"   => false,
            "ActionName"        => "",
            "StoredProcArg"     => [
                "StoredProcName"   => "",
                "ArgumentListLite" => [],
                "ReturnField"      => ["Fn" => "", "Fv" => "", "Dt" => ""],
                "DbServerId"       => "",
                "DefaultDBName"    => ""
            ],
            "ResponseStatus"    => 'SUCCESS',
            "ErrorMessage"      => $errorMessage,
            "ErrorDetail"       => "",
            "ErrorCode"         => "",
            "ErrorLocation"     => "",
            "ExceptionLogId"    => ""
        ];
    }

    /**
     * Standard DbTupleLite JSON structure for query and data response actions.
     */
    protected function buildTupleResponse(array $parsed, array $recordFields, ?int $customActionId = null, string $tableName = ""): array
    {
        return [
            "RemoteIP"          => null,
            "ApplicationId"     => (int)($parsed['applicationId'] ?? -999),
            "TriggeredByUserId" => (string)($parsed['triggeredByUserId'] ?? ""),
            "ActionId"          => $customActionId !== null ? $customActionId : (int)($parsed['actionId'] ?? 0),
            "ActionMethodName"  => "",
            "ActionNameSpace"   => "",
            "GroupMethodName"   => "",
            "GroupNameSpace"    => "",
            "NoOfArguments"     => 0,
            "RequestType"       => "",
            "MethodArg"         => [],
            "MethodArgLite"     => [],
            "DbTuple"           => [],
            "SqlScriptList"     => [],
            "Base64ObjectString"=> "",
            "DeviceTypeId"      => 0,
            "DeviceId"          => "",
            "DbTupleLite"       => [
                [
                    "TableName"          => $tableName,
                    "PrimaryKeyField"    => "",
                    "PrimaryKeyDbField"  => "",
                    "DbName"             => "",
                    "RecordList"         => [
                        [
                            "Record"      => $recordFields,
                            "DbTupleLite" => null
                        ]
                    ]
                ]
            ],
            "ApiTrailId"        => -999,
            "TransactionId"     => 0,
            "Rrn"               => "",
            "ExtRefNo"          => "",
            "Base64Objects"     => [],
            "IsActionBlocked"   => false,
            "ActionName"        => "",
            "StoredProcArg"     => [
                "StoredProcName"   => "",
                "ArgumentListLite" => [],
                "ReturnField"      => ["Fn" => "", "Fv" => "", "Dt" => ""],
                "DbServerId"       => "",
                "DefaultDBName"    => ""
            ],
            "ResponseStatus"    => "SUCCESS",
            "ErrorMessage"      => "",
            "ErrorDetail"       => "",
            "ErrorCode"         => "",
            "ErrorLocation"     => "",
            "ExceptionLogId"    => ""
        ];
    }
}
