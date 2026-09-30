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
            $detailsCount = BandhanTransactionDetail::where('lot_number', $lotNumber)->count();
            if ($detailsCount > 0) {
                $totalRecord = $detailsCount;
                $rejectedCount = BandhanTransactionDetail::where('lot_number', $lotNumber)
                    ->where(function ($q) {
                        $q->whereIn('column_3', ['01', '51', 'FAILED', 'REJECTED', 'N'])
                          ->orWhere('raw_row', 'like', '%|01|%')
                          ->orWhere('raw_row', 'like', '%|51|%')
                          ->orWhere('raw_row', 'like', '%|N|%');
                    })->count();
                $successCount = max(0, $totalRecord - $rejectedCount);
            } else {
                $tx = BandhanTransaction::where('lot_number', $lotNumber)->latest()->first();
                if ($tx && $tx->record_count) {
                    $totalRecord = (int)$tx->record_count;
                    $successCount = $totalRecord;
                    $rejectedCount = 0;
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
                            $cols = explode('|', $line);
                            $statusVal = $cols[2] ?? '';
                            if (in_array($statusVal, ['01', '51', 'N', 'FAILED', 'REJECTED'])) {
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
     * ACTION 3716 (Right column) / 1069: Lot Validation Response / Det
     * Pulls validation status rows from database, encrypts payload, and saves to bandhan/3716/.
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
                $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('lb_main.av_lot_details')
                    ->where('lot_no', $lotNo)
                    ->whereNull('av_account_status')
                    ->whereNull('name_status')
                    ->get();
            } catch (\Exception $e) {
                Log::warning("Could not query pgsql_payment lb_main.av_lot_details: " . $e->getMessage());
                $lotBeneficiaryDetails = collect();
            }

            if ($lotBeneficiaryDetails->isEmpty()) {
                // Fallback to local BandhanTransactionDetail
                $localDetails = BandhanTransactionDetail::where('lot_number', $lotNo)->get();
                if ($localDetails->isNotEmpty()) {
                    $finalData = [];
                    foreach ($localDetails as $detail) {
                        $row = ($detail->transaction_id ?? $detail->id) . '|' . ($detail->beneficiary_id ?? '') . "|Y||00|Y|00|" . ($detail->beneficiary_name ?? '') . "\n";
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
                    $row = $detail->ld_id . '|' . $detail->ben_id . "|Y||00|Y|00|" . ($detail->ben_name ?? '') . "\n";
                    $finalData[] = $row;
                }
            }

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
                ["Fn" => "totalRecord", "Fv" => (string)count($finalData), "Dt" => ""],
                ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                ["Fn" => "successCount", "Fv" => (string)count($finalData), "Dt" => ""],
                ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""],
                ["Fn" => "pendingCount", "Fv" => "0", "Dt" => ""]
            ];

            $responseData = $this->buildTupleResponse($parsed, $recordsList, 0);
            $response = response()->json(json_encode($responseData), 200);
            $this->logActionResponse($actionId, $response, $lotNo, ['beneficiary_records' => count($finalData)]);
            return $response;
        }

        // When encrypted data was provided by bank
        $response = response()->json([
            'status'  => 'success',
            'message' => "Action {$actionId} response payload processed and saved to {$folder}/.",
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
