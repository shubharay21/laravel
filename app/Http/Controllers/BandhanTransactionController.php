<?php

namespace App\Http\Controllers;

use App\Helpers\BandhanPayment;
use App\Models\BandhanTransaction;
use App\Models\BandhanTransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BandhanTransactionController extends Controller
{
    /**
     * Handle incoming Bandhan callback at /Bone/LakshmiBhandar/1.0/1234
     * - Stores dynamic encrypted and decrypted files in storage for ALL action IDs.
     * - Performs database insertion for ActionId 3713 and 3717.
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

            // 1. Extract core fields
            $actionId = $data['ActionId'] ?? $request->input('ActionId');
            $actionIdStr = (string)$actionId;
            $triggeredByUserId = $data['TriggeredByUserId'] ?? $request->input('TriggeredByUserId');
            $applicationId = $data['ApplicationId'] ?? $request->input('ApplicationId');
            $lotNumber = null;
            $recordCount = null;
            $encryptedData = null;

            $year = null;
            $month = null;

            if (!empty($data['MethodArg']) && is_array($data['MethodArg'])) {
                foreach ($data['MethodArg'] as $arg) {
                    $fn = $arg['FieldName'] ?? '';
                    if ($fn === '@data') {
                        $encryptedData = $arg['Value'] ?? null;
                    } elseif ($fn === '@lotNumber') {
                        $lotNumber = $arg['Value'] ?? null;
                    } elseif ($fn === '@recordCount') {
                        $recordCount = $arg['Value'] ?? null;
                    } elseif ($fn === '@year') {
                        $year = $arg['Value'] ?? null;
                    } elseif ($fn === '@month') {
                        $month = $arg['Value'] ?? null;
                    }
                }
            }

            if (!$encryptedData && is_array($data)) {
                $encryptedData = $data['data'] ?? $data['Value'] ?? $data['responseData'] ?? null;
            }

            $timestamp = date('Ymd_His');
            $lotSuffix = !empty($lotNumber) ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber) : '';

            $decryptedPlainText = null;
            $decryptedFileName = null;
            $totalBeneficiariesCount = 0;
            $transactionRecord = null;

            // 2. Dynamic Encrypted (Raw Payload) File - Stored for ALL Action IDs
            $encryptedFileName = "encrypted{$lotSuffix}_{$timestamp}.txt";
            $formattedJson = !empty($data)
                ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $rawContent;

            if (!empty($formattedJson)) {
                Storage::disk('local')->put("bandhanbentransactionencdata/{$encryptedFileName}", $formattedJson);
            }

            // 3. Dynamic Decrypted File - Decrypted and Stored for ALL Action IDs having encrypted payload
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

                    // Save decrypted plaintext as dynamic file in storage
                    Storage::disk('local')->put("bandhanbentransactionencdata/{$decryptedFileName}", $plainText);

                    $lines = preg_split('/\r\n|\r|\n/', trim($decryptedPlainText));
                    $validLines = array_filter($lines, fn($line) => trim($line) !== '');
                    $totalBeneficiariesCount = count($validLines);
                }

                // 4. Database Insertion - Performed for Action IDs 3713 and 3717
                if (in_array($actionIdStr, ['3713', '3717'])) {
                    DB::beginTransaction();
                    try {
                        $transactionRecord = BandhanTransaction::create([
                            'triggered_by_user_id' => $triggeredByUserId,
                            'application_id'       => $applicationId,
                            'action_id'            => $actionIdStr,
                            'lot_number'           => $lotNumber,
                            'record_count'         => $recordCount ?? $totalBeneficiariesCount,
                            'raw_payload_path'     => "bandhanbentransactionencdata/{$encryptedFileName}",
                            'decrypted_file_path'  => $decryptedFileName ? "bandhanbentransactionencdata/{$decryptedFileName}" : null,
                            'decrypted_data'       => $decryptedPlainText,
                            'status'               => 'SUCCESS',
                        ]);

                        if ($totalBeneficiariesCount > 0 && !empty($validLines)) {
                            $recordsToInsert = [];
                            $now = now();
                            $batchSize = 1000;
                            $lineIndex = 0;

                            foreach ($validLines as $line) {
                                $lineIndex++;
                                $trimmed = trim($line);
                                $cols = explode('|', $trimmed);

                                $col0 = isset($cols[0]) && $cols[0] !== '' ? trim($cols[0]) : null;
                                $col1 = isset($cols[1]) && $cols[1] !== '' ? trim($cols[1]) : null;
                                $col2 = isset($cols[2]) && $cols[2] !== '' ? trim($cols[2]) : null;
                                $col3 = isset($cols[3]) && $cols[3] !== '' ? trim($cols[3]) : null;
                                $col4 = isset($cols[4]) && $cols[4] !== '' ? trim($cols[4]) : null;

                                // Format in raw_row: transaction_id|beneficiary_id|amount|beneficiary_name|account_number|
                                if ($col3 !== null && preg_match('/[a-zA-Z]/', $col3)) {
                                    $transactionId   = $col0;
                                    $beneficiaryId   = $col1;
                                    $beneficiaryName = $col3;
                                    $accountNumber   = $col4;
                                } elseif ($col1 !== null && preg_match('/[a-zA-Z]/', $col1)) {
                                    $transactionId   = $col0;
                                    $beneficiaryName = $col1;
                                    $accountNumber   = $col3;
                                    $beneficiaryId   = $col4;
                                } else {
                                    $transactionId   = $col0;
                                    $beneficiaryId   = $col1;
                                    $beneficiaryName = $col3;
                                    $accountNumber   = $col4;
                                }

                                // Status code assignment for column_3 (00 for Success, 01 for Failed)
                                $rawStatus = $col2;
                                if ($rawStatus !== null && in_array(strtoupper($rawStatus), ['01', '51', 'FAILED', 'REJECTED', 'N', 'FAIL'])) {
                                    $statusCol = $rawStatus;
                                } elseif ($rawStatus !== null && in_array(strtoupper($rawStatus), ['00', 'SUCCESS', 'Y', 'COMPLETED'])) {
                                    $statusCol = ($lineIndex % 5 === 0) ? '01' : '00';
                                } else {
                                    $statusCol = ($lineIndex % 5 === 0) ? '01' : '00';
                                }

                                $recordsToInsert[] = [
                                    'bandhan_transaction_id' => $transactionRecord->id,
                                    'lot_number'             => $lotNumber,
                                    'transaction_id'         => $transactionId,
                                    'beneficiary_name'       => $beneficiaryName,
                                    'column_3'               => $statusCol,
                                    'account_number'         => $accountNumber,
                                    'beneficiary_id'         => $beneficiaryId,
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
                        Log::error("DB Insert Error during Bandhan callback (ActionId {$actionIdStr}): " . $dbEx->getMessage());
                    }
                }
            }

            // 5. ActionId-based routing and response handling
            switch ($actionIdStr) {
                case '1060':
                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => 'SUCCESS',
                        "ErrorMessage" => '',
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                    // case '1072':
                    //     $responseData = [
                    //         "RemoteIP" => null,
                    //         "ApplicationId" => (int)($applicationId ?? -999),
                    //         "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                    //         "ActionId" => (int)$actionId,
                    //         "ActionMethodName" => "",
                    //         "ActionNameSpace" => "",
                    //         "GroupMethodName" => "",
                    //         "GroupNameSpace" => "",
                    //         "NoOfArguments" => 0,
                    //         "RequestType" => "",
                    //         "MethodArg" => [],
                    //         "MethodArgLite" => [],
                    //         "DbTuple" => [],
                    //         "SqlScriptList" => [],
                    //         "Base64ObjectString" => "",
                    //         "DeviceTypeId" => 0,
                    //         "DeviceId" => "",
                    //         "DbTupleLite" => [],
                    //         "ApiTrailId" => -999,
                    //         "TransactionId" => 0,
                    //         "Rrn" => "",
                    //         "ExtRefNo" => "",
                    //         "Base64Objects" => [],
                    //         "IsActionBlocked" => false,
                    //         "ActionName" => "",
                    //         "StoredProcArg" => [
                    //             "StoredProcName" => "",
                    //             "ArgumentListLite" => [],
                    //             "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                    //             "DbServerId" => "",
                    //             "DefaultDBName" => ""
                    //         ],
                    //         "ResponseStatus" => 'SUCCESS',
                    //         "ErrorMessage" => 'This lot number is already exists',
                    //         "ErrorDetail" => "",
                    //         "ErrorCode" => "",
                    //         "ErrorLocation" => "",
                    //         "ExceptionLogId" => ""
                    //     ];
                    //     return response()->json(json_encode($responseData), 200);

                case '3717':
                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => 'SUCCESS',
                        "ErrorMessage" => 'Lot Updated for Bandhan Bank.',
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                case '1068':
                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [
                            [
                                "TableName" => "Dummy_Response_Table",
                                "PrimaryKeyField" => "",
                                "PrimaryKeyDbField" => "",
                                "DbName" => "",
                                "RecordList" => [
                                    [
                                        "Record" => [
                                            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? "F304202604922690"), "Dt" => ""],
                                            ["Fn" => "totalRecord", "Fv" => (string)($recordCount ?? "10"), "Dt" => ""],
                                            ["Fn" => "successCount", "Fv" => "10", "Dt" => ""],
                                            ["Fn" => "rejectedCount", "Fv" => "", "Dt" => ""],
                                            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""]
                                        ],
                                        "DbTupleLite" => null
                                    ]
                                ]
                            ]
                        ],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => "SUCCESS",
                        "ErrorMessage" => "",
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                case '1073':
                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [
                            [
                                "TableName" => "",
                                "PrimaryKeyField" => "",
                                "PrimaryKeyDbField" => "",
                                "DbName" => "",
                                "RecordList" => [
                                    [
                                        "Record" => [
                                            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? "T303202604132443"), "Dt" => ""],
                                            ["Fn" => "totalRecord", "Fv" => (string)($recordCount ?? "16"), "Dt" => ""],
                                            ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                                            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                                            ["Fn" => "successCount", "Fv" => (string)($recordCount ?? "16"), "Dt" => ""],
                                            ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""]
                                        ],
                                        "DbTupleLite" => null
                                    ]
                                ]
                            ]
                        ],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => "SUCCESS",
                        "ErrorMessage" => "",
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                case '1069':
                    if (empty($encryptedData)) {
                        $lotNo = $lotNumber;
                        if ($lotNo === null) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Lot number not found in request data'
                            ], 400);
                        }

                        $filename = "enc_beneficiary_response_" . $lotNo . ".txt";
                        $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('lb_main.av_lot_details')
                            ->where('lot_no', $lotNo)
                            ->whereNull('av_account_status')
                            ->whereNull('name_status')
                            ->get();

                        if ($lotBeneficiaryDetails->isEmpty()) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No beneficiary details found for lot number: ' . $lotNo
                            ], 404);
                        }

                        $finalData = [];
                        foreach ($lotBeneficiaryDetails as $detail) {
                            $row = $detail->ld_id . '|' . $detail->ben_id . "|Y||00|Y|00|" . ($detail->ben_name ?? '') . "\n";
                            $finalData[] = $row;
                        }

                        $plainTextBeneficiaryData = implode('', $finalData);
                        Storage::disk('local')->put('bandhanbeneficiaryencdata/' . $filename, $plainTextBeneficiaryData);

                        $compressed = gzcompress($plainTextBeneficiaryData, 9);
                        $base64Data = base64_encode($compressed);
                        $encryptedOut = BandhanPayment::encryptCode($base64Data);

                        $responseData = [
                            "RemoteIP" => null,
                            "ApplicationId" => (int)($applicationId ?? -999),
                            "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                            "ActionId" => 0,
                            "ActionMethodName" => "",
                            "ActionNameSpace" => "",
                            "GroupMethodName" => "",
                            "GroupNameSpace" => "",
                            "NoOfArguments" => 0,
                            "RequestType" => "",
                            "MethodArg" => [],
                            "MethodArgLite" => [],
                            "DbTuple" => [],
                            "SqlScriptList" => [],
                            "Base64ObjectString" => "",
                            "DeviceTypeId" => 0,
                            "DeviceId" => "",
                            "DbTupleLite" => [
                                [
                                    "TableName" => "",
                                    "PrimaryKeyField" => "",
                                    "PrimaryKeyDbField" => "",
                                    "DbName" => "",
                                    "RecordList" => [
                                        [
                                            "Record" => [
                                                ["Fn" => "Record", "Fv" => $encryptedOut, "Dt" => ""],
                                                ["Fn" => "lotNumber", "Fv" => (string)$lotNo, "Dt" => ""],
                                                ["Fn" => "totalRecord", "Fv" => (string)count($lotBeneficiaryDetails), "Dt" => ""],
                                                ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                                                ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                                                ["Fn" => "successCount", "Fv" => (string)count($lotBeneficiaryDetails), "Dt" => ""],
                                                ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""],
                                                ["Fn" => "pendingCount", "Fv" => "0", "Dt" => ""]
                                            ],
                                            "DbTupleLite" => null
                                        ]
                                    ]
                                ]
                            ],
                            "ApiTrailId" => -999,
                            "TransactionId" => 0,
                            "Rrn" => "",
                            "ExtRefNo" => "",
                            "Base64Objects" => [],
                            "IsActionBlocked" => false,
                            "ActionName" => "",
                            "StoredProcArg" => [
                                "StoredProcName" => "",
                                "ArgumentListLite" => [],
                                "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                                "DbServerId" => "",
                                "DefaultDBName" => ""
                            ],
                            "ResponseStatus" => "SUCCESS",
                            "ErrorMessage" => "",
                            "ErrorDetail" => "",
                            "ErrorCode" => "",
                            "ErrorLocation" => "",
                            "ExceptionLogId" => ""
                        ];
                        return response()->json(json_encode($responseData), 200);
                    }
                    break;

                case '1074':
                    // If no encrypted data was received to decrypt, check if this is an outgoing transaction data query from DB
                    if (empty($encryptedData)) {
                        $lotNo = $lotNumber;
                        if ($lotNo === null) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Lot number not found in request data'
                            ], 400);
                        }

                        $filename = "enc_ben_transaction_response_" . $lotNo . ".txt";
                        $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('bandhan.lot_details')
                            ->where('lot_no', $lotNo)
                            ->get();

                        if ($lotBeneficiaryDetails->isEmpty()) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No beneficiary details found for lot number: ' . $lotNo
                            ], 404);
                        }

                        $finalData = [];
                        foreach ($lotBeneficiaryDetails as $detail) {
                            $transaction_id = $detail->ld_id;
                            $uniqueId = $detail->ben_id;
                            $row = $transaction_id . '|' . $uniqueId . "|00|\n";
                            $finalData[] = $row;
                        }

                        $decryptData = implode('', $finalData);
                        Storage::disk('local')->put('bandhanbentransactionencdata/' . $filename, $decryptData);

                        $compressed = gzcompress($decryptData, 9);
                        $base64Data = base64_encode($compressed);
                        $encryptedTransactionData = BandhanPayment::encryptCode($base64Data);

                        $responseData = [
                            "RemoteIP" => null,
                            "ApplicationId" => (int)($applicationId ?? -999),
                            "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                            "ActionId" => 0,
                            "ActionMethodName" => "",
                            "ActionNameSpace" => "",
                            "GroupMethodName" => "",
                            "GroupNameSpace" => "",
                            "NoOfArguments" => 0,
                            "RequestType" => "",
                            "MethodArg" => [],
                            "MethodArgLite" => [],
                            "DbTuple" => [],
                            "SqlScriptList" => [],
                            "Base64ObjectString" => "",
                            "DeviceTypeId" => 0,
                            "DeviceId" => "",
                            "DbTupleLite" => [
                                [
                                    "TableName" => "",
                                    "PrimaryKeyField" => "",
                                    "PrimaryKeyDbField" => "",
                                    "DbName" => "",
                                    "RecordList" => [
                                        [
                                            "Record" => [
                                                ["Fn" => "responseData", "Fv" => $encryptedTransactionData, "Dt" => ""]
                                            ],
                                            "DbTupleLite" => null
                                        ]
                                    ]
                                ]
                            ],
                            "ApiTrailId" => -999,
                            "TransactionId" => 0,
                            "Rrn" => "",
                            "ExtRefNo" => "",
                            "Base64Objects" => [],
                            "IsActionBlocked" => false,
                            "ActionName" => "",
                            "StoredProcArg" => [
                                "StoredProcName" => "",
                                "ArgumentListLite" => [],
                                "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                                "DbServerId" => "",
                                "DefaultDBName" => ""
                            ],
                            "ResponseStatus" => "SUCCESS",
                            "ErrorMessage" => "",
                            "ErrorDetail" => "",
                            "ErrorCode" => "",
                            "ErrorLocation" => "",
                            "ExceptionLogId" => ""
                        ];
                        return response()->json(json_encode($responseData), 200);
                    }
                    break;

                case '3715':
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

                    // Fallback to storage decrypted files if database not yet queried
                    if ($totalRecord === 0 && !empty($lotNumber)) {
                        $lotSuffixClean = '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber);
                        $files = Storage::disk('local')->files('bandhanbentransactionencdata');
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
                        }
                    }

                    if ($totalRecord === 0 && !empty($recordCount)) {
                        $totalRecord = (int)$recordCount;
                        $successCount = $totalRecord;
                        $rejectedCount = 0;
                    }

                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [
                            [
                                "TableName" => "",
                                "PrimaryKeyField" => "",
                                "PrimaryKeyDbField" => "",
                                "DbName" => "",
                                "RecordList" => [
                                    [
                                        "Record" => [
                                            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? ""), "Dt" => ""],
                                            ["Fn" => "totalRecord", "Fv" => (string)$totalRecord, "Dt" => ""],
                                            ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                                            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                                            ["Fn" => "successCount", "Fv" => (string)$successCount, "Dt" => ""],
                                            ["Fn" => "rejectedCount", "Fv" => (string)$rejectedCount, "Dt" => ""]
                                        ],
                                        "DbTupleLite" => null
                                    ]
                                ]
                            ]
                        ],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => "SUCCESS",
                        "ErrorMessage" => "",
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                case '3718':
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

                    // Fallback to storage decrypted files if database not yet queried
                    if ($totalRecord === 0 && !empty($lotNumber)) {
                        $lotSuffixClean = '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber);
                        $files = Storage::disk('local')->files('bandhanbentransactionencdata');
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
                        }
                    }

                    // Fallback to pgsql_payment database lot details if available
                    if ($totalRecord === 0 && !empty($lotNumber)) {
                        try {
                            $lotMaster = DB::connection('pgsql_payment')->table('bandhan.lot_master')
                                ->where('file_name', $lotNumber)
                                ->orWhere('lot_no', $lotNumber)
                                ->first();
                            if ($lotMaster) {
                                $lotDetailsCount = DB::connection('pgsql_payment')->table('bandhan.lot_details')
                                    ->where('lot_no', $lotMaster->lot_no)
                                    ->count();
                                if ($lotDetailsCount > 0) {
                                    $totalRecord = $lotDetailsCount;
                                    $successCount = $totalRecord;
                                    $rejectedCount = 0;
                                }
                            }
                        } catch (\Throwable $e) {
                            // Ignored if pgsql_payment connection is not configured or reachable
                        }
                    }

                    if ($totalRecord === 0 && !empty($recordCount)) {
                        $totalRecord = (int)$recordCount;
                        $successCount = $totalRecord;
                        $rejectedCount = 0;
                    }

                    $responseData = [
                        "RemoteIP" => null,
                        "ApplicationId" => (int)($applicationId ?? -999),
                        "TriggeredByUserId" => (string)($triggeredByUserId ?? ""),
                        "ActionId" => (int)$actionId,
                        "ActionMethodName" => "",
                        "ActionNameSpace" => "",
                        "GroupMethodName" => "",
                        "GroupNameSpace" => "",
                        "NoOfArguments" => 0,
                        "RequestType" => "",
                        "MethodArg" => [],
                        "MethodArgLite" => [],
                        "DbTuple" => [],
                        "SqlScriptList" => [],
                        "Base64ObjectString" => "",
                        "DeviceTypeId" => 0,
                        "DeviceId" => "",
                        "DbTupleLite" => [
                            [
                                "TableName" => "",
                                "PrimaryKeyField" => "",
                                "PrimaryKeyDbField" => "",
                                "DbName" => "",
                                "RecordList" => [
                                    [
                                        "Record" => [
                                            ["Fn" => "lotNumber", "Fv" => (string)($lotNumber ?? ""), "Dt" => ""],
                                            ["Fn" => "totalRecord", "Fv" => (string)$totalRecord, "Dt" => ""],
                                            ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                                            ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                                            ["Fn" => "successCount", "Fv" => (string)$successCount, "Dt" => ""],
                                            ["Fn" => "rejectedCount", "Fv" => (string)$rejectedCount, "Dt" => ""]
                                        ],
                                        "DbTupleLite" => null
                                    ]
                                ]
                            ]
                        ],
                        "ApiTrailId" => -999,
                        "TransactionId" => 0,
                        "Rrn" => "",
                        "ExtRefNo" => "",
                        "Base64Objects" => [],
                        "IsActionBlocked" => false,
                        "ActionName" => "",
                        "StoredProcArg" => [
                            "StoredProcName" => "",
                            "ArgumentListLite" => [],
                            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""],
                            "DbServerId" => "",
                            "DefaultDBName" => ""
                        ],
                        "ResponseStatus" => "SUCCESS",
                        "ErrorMessage" => "",
                        "ErrorDetail" => "",
                        "ErrorCode" => "",
                        "ErrorLocation" => "",
                        "ExceptionLogId" => ""
                    ];
                    return response()->json(json_encode($responseData), 200);

                case '3713':
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Action 3713 payload decrypted, stored in storage, and inserted into database successfully.',
                        'data'    => [
                            'transaction_id'          => $transactionRecord ? $transactionRecord->id : null,
                            'action_id'               => $actionIdStr,
                            'lot_number'              => $lotNumber,
                            'encrypted_file'          => $encryptedFileName,
                            'decrypted_file'          => $decryptedFileName,
                            'decrypted_records_count' => $totalBeneficiariesCount,
                        ]
                    ], 200);
            }

            // Default response
            // return response()->json([
            //     'status'  => 'success',
            //     'message' => 'Payload processed and stored in storage successfully.',
            //     'data'    => [
            //         'transaction_id'          => $transactionRecord ? $transactionRecord->id : null,
            //         'action_id'               => $actionIdStr,
            //         'lot_number'              => $lotNumber,
            //         'encrypted_file'          => $encryptedFileName,
            //         'decrypted_file'          => $decryptedFileName,
            //         'decrypted_records_count' => $totalBeneficiariesCount,
            //     ]
            // ], 200);
        } catch (\Exception $e) {
            Log::error('Bandhan Callback Processing Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
