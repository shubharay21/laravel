<?php

namespace App\Http\Controllers;

use App\Helpers\BandhanPayment;
use App\Models\BandhanTransaction;
use App\Models\BandhanTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BandhanTransactionController extends Controller
{
    /**
     * Handle incoming Bandhan callback at /Bone/LakshmiBhandar/1.0/1234:
     * 1. Stores the raw encrypted payload into a dynamic file (e.g., encrypted_{lotNumber}_{timestamp}.txt).
     * 2. Decrypts @data using BandhanPayment::decryptCode() and uncompresses.
     * 3. Stores decrypted plaintext into a dynamic file (e.g., decrypted_{lotNumber}_{timestamp}.txt).
     * 4. Dynamically stores all decrypted beneficiary records into the database in batches.
     */
    public function handleBandhanCallback(Request $request)
    {
        // Allow sufficient execution time and memory for arbitrary numbers of beneficiaries
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $rawContent = $request->getContent();
            $data = $request->all();

            if (empty($data) && !empty($rawContent)) {
                $decoded = json_decode($rawContent, true);
                $data = is_array($decoded) ? $decoded : [];
            }

            // 1. Extract fields
            $triggeredByUserId = $data['TriggeredByUserId'] ?? null;
            $applicationId = $data['ApplicationId'] ?? null;
            $actionId = $data['ActionId'] ?? null;
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

            $timestamp = date('Ymd_His');
            $lotSuffix = !empty($lotNumber) ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber) : '';

            // 2. Dynamic Encrypted (Raw Payload) File
            $encryptedFileName = "encrypted{$lotSuffix}_{$timestamp}.txt";
            $formattedJson = !empty($data)
                ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $rawContent;

            Storage::disk('local')->put("bandhanbentransactionencdata/{$encryptedFileName}", $formattedJson);

            // 3. Decrypt and decompress if encrypted payload is present
            $decryptedPlainText = null;
            $decryptedFileName = null;
            $totalBeneficiariesCount = 0;

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

                    // Store decrypted values as dynamic txt file
                    Storage::disk('local')->put("bandhanbentransactionencdata/{$decryptedFileName}", $plainText);
                }
            }

            // 4. Store into Database (supports any number of beneficiary records)
            DB::beginTransaction();
            try {
                // Split lines cleanly regardless of line ending (\r\n, \n, \r)
                $lines = !empty($decryptedPlainText)
                    ? preg_split('/\r\n|\r|\n/', trim($decryptedPlainText))
                    : [];

                $validLines = array_filter($lines, fn($line) => trim($line) !== '');
                $totalBeneficiariesCount = count($validLines);

                $transaction = BandhanTransaction::create([
                    'triggered_by_user_id' => $triggeredByUserId,
                    'application_id'       => $applicationId,
                    'action_id'            => $actionId,
                    'lot_number'           => $lotNumber,
                    'record_count'         => $recordCount ?? $totalBeneficiariesCount,
                    'raw_payload_path'     => "bandhanbentransactionencdata/{$encryptedFileName}",
                    'decrypted_file_path'  => $decryptedFileName ? "bandhanbentransactionencdata/{$decryptedFileName}" : null,
                    'decrypted_data'       => $decryptedPlainText,
                    'status'               => 'SUCCESS',
                ]);

                if ($totalBeneficiariesCount > 0) {
                    $recordsToInsert = [];
                    $now = now();
                    $batchSize = 1000;

                    foreach ($validLines as $line) {
                        $trimmed = trim($line);
                        $cols = explode('|', $trimmed);

                        $recordsToInsert[] = [
                            'bandhan_transaction_id' => $transaction->id,
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

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Encrypted payload saved, decrypted, and stored in database successfully.',
                    'data'    => [
                        'transaction_id'          => $transaction->id,
                        'lot_number'              => $lotNumber,
                        'encrypted_file'          => $encryptedFileName,
                        'decrypted_file'          => $decryptedFileName,
                        'decrypted_records_count' => $totalBeneficiariesCount,
                    ]
                ], 200);

            } catch (\Exception $dbEx) {
                DB::rollBack();
                Log::error('DB Insert Error during Bandhan callback: ' . $dbEx->getMessage());
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Database error: ' . $dbEx->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Bandhan Callback Processing Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
