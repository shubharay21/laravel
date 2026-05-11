<?php

use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\AuthController;
use App\Helpers\Encryption;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\Helper;



Route::post('/Bone/LakshmiBhandar/1.0/1234', function (Request $request) {
    $rawContent = $request->getContent();
    // return response()->json([
    //     'received' => $rawContent
    // ]);
    $data = json_decode($rawContent, true);
    // return response()->json([
    //     'received' => $data
    // ]);
    $actionId = $data['ActionId'] ?? 'Not Found';
    $lotFileNumber = null;
    if (!empty($data['MethodArg'])) {
        foreach ($data['MethodArg'] as $arg) {
            if (($arg['FieldName'] ?? '') === '@lotNumber') {
                $lotFileNumber = $arg['Value'] ?? null;
                break;
            }
        }
    }
    switch ($actionId) {
        case '1060':
            $responseData = [
                "RemoteIP" => null,
                "ApplicationId" => -999,
                "TriggeredByUserId" => "",
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
                    "ReturnField" => [
                        "Fn" => "",
                        "Fv" => "",
                        "Dt" => ""
                    ],
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
            break;
        case '1072':
            $responseData = [
                "RemoteIP" => null,
                "ApplicationId" => -999,
                "TriggeredByUserId" => "",
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
                    "ReturnField" => [
                        "Fn" => "",
                        "Fv" => "",
                        "Dt" => ""
                    ],
                    "DbServerId" => "",
                    "DefaultDBName" => ""
                ],
                "ResponseStatus" => 'SUCCESS',
                "ErrorMessage" => 'This lot number is already exists',
                "ErrorDetail" => "",
                "ErrorCode" => "",
                "ErrorLocation" => "",
                "ExceptionLogId" => ""
            ];
            //         return response()->json([
            //     'received' => $responseData
            // ]);
            return response()->json(json_encode($responseData), 200);
            break;
        case '1068':
            $responseData = [
                "RemoteIP" => null,
                "ApplicationId" => -999,
                "TriggeredByUserId" => "",
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
                        "TableName" => "Dummy_Response_Table",
                        "PrimaryKeyField" => "",
                        "PrimaryKeyDbField" => "",
                        "DbName" => "",
                        "RecordList" => [
                            [
                                "Record" => [
                                    [
                                        "Fn" => "lotNumber",
                                        "Fv" => "F304202604922690",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "totalRecord",
                                        "Fv" => "10",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "successCount",
                                        "Fv" => "10",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "rejectedCount",
                                        "Fv" => "",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "status",
                                        "Fv" => "Completed",
                                        "Dt" => ""
                                    ]
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
            break;
        case '1073':
            $responseData = [
                "RemoteIP" => null,
                "ApplicationId" => (int)$request->input('ApplicationId', -999),
                "TriggeredByUserId" => $request->input('TriggeredByUserId', ""),
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
                                    [
                                        "Fn" => "lotNumber",
                                        "Fv" => "T303202604132443",
                                        "Dt" => ""
                                    ],

                                    [
                                        "Fn" => "totalRecord",
                                        "Fv" => "16",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "date",
                                        "Fv" => "20-04-2026 13:30:48",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "status",
                                        "Fv" => "Completed",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "successCount",
                                        "Fv" => "16",
                                        "Dt" => ""
                                    ],
                                    [
                                        "Fn" => "rejectedCount",
                                        "Fv" => "0",
                                        "Dt" => ""
                                    ]
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
            break;
        case '1069':
            // Check if $lotNumber is null
            if ($lotFileNumber !== null) {
                //  return response()->json(['received' => $lotFileNumber]);
                $lotNumber = DB::connection('pgsql_payment')->table('lb_main.av_lot_master')->where('file_name', $lotFileNumber)->value('lot_no');
                // return response()->json(['received' => $lotNumber]); 
            }
            if ($lotNumber === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lot number not found in request data'
                ], 400);
            } else {

                $lotNo = $lotNumber; // Assuming $lotNumber is the lot_no
                $filename = "enc_beneficiary_response_" . $lotNo . ".txt"; // Generate filename based on lot_no
                $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('lb_main.av_lot_details')->where('lot_no', $lotNo)->where('av_account_status', null)->where('name_status', null)->get();
                $finalData = [];
                $successLimit = 5; // (int) $responseSuccess;
                $rejectedLimit = 0; // (int) $responseFailed;
                $pendingLimit = 5; // (int) $responsePending;
                $counter = 0;

                // Check if data exists
                if ($lotBeneficiaryDetails->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No beneficiary details found for lot number: ' . $lotNo
                    ], 404);
                }

                // return response()->json(['beneficiaryDetails' => $lotBeneficiaryDetails]); // Debugging line to check the fetched details
                foreach ($lotBeneficiaryDetails as $lotBeneficiaryDetail) {
                    $ld_id = $lotBeneficiaryDetail->ld_id;
                    $ben_id = $lotBeneficiaryDetail->ben_id;
                    $success_status = "Y";
                    $success_remarks = "";
                    $success_status_code = "00";
                    $success_name_status = "Y";
                    $success_name_status_code = "00";
                    $success_name_response = $lotBeneficiaryDetail->ben_name;

                    $rejected_status = "N";
                    $rejected_remarks = "";
                    $rejected_status_code = "01";
                    $rejected_name_status = "N";
                    $rejected_name_status_code = "01"; // Fixed: was "01" || "51" which is invalid
                    $rejected_name_response = "";

                    $pending_status = "N";
                    $pending_remarks = "";
                    $pending_status_code = "51";
                    $pending_name_status = "N";
                    $pending_name_status_code = "";
                    $pending_name_response = "";

                    if ($counter < $successLimit) {
                        $row = $ld_id . '|' . $ben_id . '|' . $success_status . '|' . $success_remarks . '|' . $success_status_code . '|' . $success_name_status . '|' . $success_name_status_code . '|' . $success_name_response . "\n";
                    } elseif ($counter < ($successLimit + $rejectedLimit)) {
                        $row = $ld_id . '|' . $ben_id . '|' . $rejected_status . '|' . $rejected_remarks . '|' . $rejected_status_code . '|' . $rejected_name_status . '|' . $rejected_name_status_code . '|' . $rejected_name_response . "\n";
                    } else {
                        continue; // Skip pending as per your code
                    }
                    $finalData[] = $row;
                    $counter++;
                }

                $decryptData = implode('', $finalData);

                // Validate before storage and encryption
                if (empty($decryptData)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No valid beneficiary data to process'
                    ], 400);
                }

                // Store in file
                try {
                    Storage::put('bandhanbeneficiaryencdata/' . $filename, $decryptData);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to store file: ' . $e->getMessage()
                    ], 500);
                }

                // Encrypt
                $key = Config::get('bandhan.EncryptionKey');
                $iv = Config::get('bandhan.IvData');

                // Check if key and iv are configured
                if (empty($key) || empty($iv)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Encryption key or IV not configured'
                    ], 500);
                }

                try {
                    $data = Storage::get('bandhanbeneficiaryencdata/' . $filename);
                    $compressed = gzcompress($data, 9);
                    $base64OfCompressedData = base64_encode($compressed);
                    $encryptedData = Encryption::encryptCode($key, $iv, $base64OfCompressedData);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Encryption failed: ' . $e->getMessage()
                    ], 500);
                }

                // return response()->json(['encryptedData' => $encryptedData]); // Debugging line to check the encrypted data
                $responseData = [
                    "RemoteIP" => null,
                    "ApplicationId" => -999,
                    "TriggeredByUserId" => "",
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
                                        ["Fn" => "Record", "Fv" => $encryptedData, "Dt" => ""],
                                        ["Fn" => "lotNumber", "Fv" => $lotNo, "Dt" => ""],
                                        ["Fn" => "totalRecord", "Fv" => (string)count($lotBeneficiaryDetails), "Dt" => ""],
                                        ["Fn" => "date", "Fv" => date('d-m-Y H:i:s'), "Dt" => ""],
                                        ["Fn" => "status", "Fv" => "Partial", "Dt" => ""],
                                        ["Fn" => "successCount", "Fv" => (string)$successLimit, "Dt" => ""],
                                        ["Fn" => "rejectedCount", "Fv" => (string)$rejectedLimit, "Dt" => ""],
                                        ["Fn" => "pendingCount", "Fv" => (string)(count($lotBeneficiaryDetails) - $successLimit - $rejectedLimit), "Dt" => ""]
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
                        "ReturnField" => [
                            "Fn" => "",
                            "Fv" => "",
                            "Dt" => ""
                        ],
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
            if ($lotFileNumber !== null) {
                //  return response()->json(['received' => $lotFileNumber]);
                $lotNumber = DB::connection('pgsql_payment')->table('bandhan.lot_master')->where('file_name', $lotFileNumber)->value('lot_no');
                // return response()->json(['received' => $lotNumber]); 
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lot file name not found in request data'
                ], 400);
            }
            if ($lotNumber === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lot number not found in request data'
                ], 400);
            } else {
                $lotNo = $lotNumber; // Assuming $lotNumber is the lot_no
                $limit = 100; // Set your desired limit here
                $filename = "enc_ben_transaction_response_" . $lotNo . ".txt";
                $lotBeneficiaryDetails = DB::connection('pgsql_payment')->table('bandhan.lot_details')->where('lot_no', $lotNo)->limit($limit)->get();
                if ($lotBeneficiaryDetails->isEmpty()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No beneficiary details found for lot number: ' . $lotNo
                    ], 404);
                }
                // return response()->json(['beneficiaryDetails' => $lotBeneficiaryDetails]); 
                foreach ($lotBeneficiaryDetails as $lotBeneficiaryDetail) {
                    $transaction_id = $lotBeneficiaryDetail->ld_id;
                    $uniqueId = $lotBeneficiaryDetail->ben_id;
                    $success_code = "00";
                    $failed_code = "01";
                    $row = $transaction_id . '|'  . $uniqueId . '|' .  $success_code . '|' . "\n" . '';
                    $finalData[] = $row;
                }
                $decryptData = implode('', $finalData);
                // return response()->json(['received' => $decryptData]); 
                if (empty($decryptData)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No valid beneficiary data to process'
                    ], 400);
                }

                // Store in file
                try {
                    $is_stored = Storage::put('bandhanbentransactionencdata/' . $filename, $decryptData);
                    // return response()->json(['received' => $is_stored]); 
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to store file: ' . $e->getMessage()
                    ], 500);
                }
                // Encrypt
                $key = Config::get('bandhan.EncryptionKey');
                $iv = Config::get('bandhan.IvData');
                // Check if key and iv are configured
                if (empty($key) || empty($iv)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Encryption key or IV not configured'
                    ], 500);
                }
                try {
                    $data = Storage::get('bandhanbentransactionencdata/' . $filename);
                    $compressed = gzcompress($data, 9);
                    $base64OfCompressedData = base64_encode($compressed);
                    $encryptedTransactionData = Encryption::encryptCode($key, $iv, $base64OfCompressedData);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Encryption failed: ' . $e->getMessage()
                    ], 500);
                }
                // $encryptedTransactionData = "fhfege";
                $responseData = [
                    "RemoteIP" => null,
                    "ApplicationId" => -999,
                    "TriggeredByUserId" => "",
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
                                        [
                                            "Fn" => "responseData",
                                            "Fv" => $encryptedTransactionData,
                                            "Dt" => ""
                                        ]
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
                        "ReturnField" => [
                            "Fn" => "",
                            "Fv" => "",
                            "Dt" => ""
                        ],
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
    }
});


Route::post('/cmosvc/user/generateotp/', function () {
    return response()->json([
        "Data" => [
            "login_as_role" => [
                [
                    "position_id" => 14556,
                    "role_id" => 12,
                    "role_name" => "DATA Integrator USER",
                    "role_code" => "US012",
                    "office_name" => "Women & Child Development and Social Welfare Department"
                ]
            ],
            "Code" => "001",
            "Message" => "Success"
        ],
        "Exception" => false,
        "Errors" => null,
        "Token" => null
    ]);
});

Route::post('/cmosvc/user/login/', function () {
    return response()->json([
        "Data" => [
            "admin_user_id" => 14206,
            "u_phone" => "9559000099",
            "u_email" => "dataintegrator@gmail.com",
            "official_name" => "Data Integrator",
            "curr_login_position_id" => 14556,
            "curr_login_role_code" => "US012",
            "curr_login_role_name" => "DATA Integrator USER"
        ],
        "Exception" => false,
        "Errors" => null,
        "Token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxNDIwNiwidXNlcl90eXBlIjoxLCJwb3NpdGlvbl9pZCI6MTQ1NTYsInJvbGVfaWQiOjEyLCJyb2xlX2NvZGUiOiJVUzAxMiIsImV4cCI6MTc2MTgxMjA1MywiaWF0IjoxNzYxODA0ODUzfQ.loD8xzR6pXRuDKFHguUFvnmi8SJ5Yhtotcc5sLZzbp0"
    ]);
});

Route::post('/cmosvc/shared/wcdpullgriev/', function () {
    $client = new \GuzzleHttp\Client();

    try {
        $url = url('example.json');
        $response = $client->get($url);

        if ($response->getStatusCode() === 200) {
            $dataArray = json_decode($response->getBody());
            if (is_array($dataArray)) {
                $data = (object) ['details' => $dataArray];
            } else {
                $data = $dataArray;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'File found and loaded successfully',
                'Data' => $data,
                'Exception' => false,
                'Errors' => null,
            ]);
        }
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ]);
    }
});

Route::post('/cmosvc/shared/wcdpushgrievatr/', function () {
    return response()->json([
        'Data' => [
            'Message' => 'Grievance status updated successfully',
        ],
        'Exception' => false,
    ], 200);
});

Route::get('/WbDeath', function () {
    // dd('ok');

    try {
        $client = new \GuzzleHttp\Client();
        $url = url('example1.json');
        $response = $client->get($url);

        if ($response->getStatusCode() === 200) {

            $dataArray = json_decode($response->getBody(), true);

            return response()->json([
                'data' => $dataArray,
                'TotalRec' => count($dataArray),
                'CurrentPageIndex' => count($dataArray),
                'TotalRecCurrectPage' => count($dataArray),
                'Exception' => false,
                'Errors' => null
            ], 200);
        }

        return response()->json([
            "data" => [],
            "TotalRec" => 0,
            "Exception" => false
        ], 200);
    } catch (\Exception $e) {

        return response()->json([
            'data' => [],
            'TotalRec' => 0,
            'Exception' => true,
            'Errors' => [
                'Message' => $e->getMessage()
            ]
        ], 500);
    }
});


Route::post('/WbDeathDetailsCallBack', function () {

    return response()->json([
        'ResponseDesc' => 'Details callback successfully processed.',
        'HttpStatusCode' => 200,
        'ResponseType' => 'Success',
        'Exception' => false
    ], 200);
});
/*Route::post('jaibanglaapi/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('backfromjb', [AuthController::class, 'backfromjb']);
});*/

Route::get('/jwt-check', function () {
    return config('jwt.secret');
});

Route::post('/backend/api/Auth/v1/ApiLogin', function () {

    return response()->json([
        "apiResponseStatus" => 1,
        "result" => "TEST_TOKEN_123456789",
        "message" => "Login Successful",
        "Exception" => false
    ], 200);
});

Route::post('/backend/api/DBTData/v1/SaveDbtDataApi', function () {

    $url = public_path('dbt.json');

    if (file_exists($url)) {

        $jsonData = json_decode(file_get_contents($url), true);

        return response()->json([
            "apiResponseStatus" => 1,
            "result" => $jsonData
        ], 200);
    }

    return response()->json([
        "apiResponseStatus" => 0,
        "result" => "File not found"
    ], 404);
});

Route::post('/wb/coeailabkol/ai_anurup_v1_d2', function () {

    $url = public_path('anurup.json');

    if (file_exists($url)) {

        $jsonData = json_decode(
            file_get_contents($url),
            true
        );

        return response()->json(
            $jsonData,
            200
        );
    }

    return response()->json([
        "status" => "error",
        "message" => "File not found"
    ], 404);
});

Route::post('/wb/bcwd/certtificate_api/certdet', function () {

    $filePath = public_path('caste.json');

    if (file_exists($filePath)) {

        // read json file
        $jsonData = json_decode(
            file_get_contents($filePath),
            true
        );

        return response()->json(
            $jsonData,
            200
        );
    }

    return response()->json([
        "status" => "error",
        "message" => "File not found"
    ], 404);
});

Route::post('/wb/food-supplies/khadyasathi-info-via-aadhar', function () {

    $request = request()->all();

    $aadhar = $request['param'][0]['PData'];

    if ($aadhar == "956670329613") {

        return response()->json([
            "status" => [
                "msg" => "Success"
            ],
            "aadharResult" => [
                "aadharList" => [
                    [
                        "NAME_AS_IN_AADHAR" => "TEST USER",
                        "NAME_AS_IN_RC" => "TEST USER",
                        "RationcardNo" => "RC123456",
                        "Card_Status" => "Active",
                        "FamilyID" => "FAM001",
                        "DOB" => "1990-01-01",
                        "LGD_BlockCode" => "123",
                        "LGD_DistrictCode" => "456"
                    ]
                ]
            ]
        ]);
    } else {

        return response()->json([
            "status" => [
                "msg" => "Success"
            ],
            "aadharResult" => [
                "aadharList" => [
                    [
                        "NAME_AS_IN_AADHAR" => "Not found"
                    ]
                ]
            ]
        ]);
    }
});

Route::post('/wb/food-supplies/wbulc-info-via-aadhar', function () {

    $request = request()->all();

    $uid = $request['uidData'];

    if ($uid == "666666666666") {

        return response()->json([
            "remarks" => "Success",
            "name" => "TEST USER",
            "txnTime" => "2024-01-10 12:30:00"
        ]);
    } else {

        return response()->json([
            "remarks" => "Success",
            "name" => "Not found",
            "txnTime" => "Not found"
        ]);
    }
});

Route::post('/duplicatecheck', function () {
    $request = request()->all();
    return response()->json([
        "isdup" => true,
        "scheme" => 11,
        "checkWith" => $request['checkWith']
    ]);
});
