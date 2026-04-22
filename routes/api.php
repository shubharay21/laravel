<?php

use App\Helpers\Encryption;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\Helper;

Route::post('/cmosvc/user/generateotp', function () {
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

Route::post('/cmosvc/user/login', function () {
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
            $dataArray = json_decode($response->getBody()); // array of details
            if (is_array($dataArray)) {
                // wrap inside an object named 'details'
                $data = (object)['details' => $dataArray];
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

/*Route::post('jaibanglaapi/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('backfromjb', [AuthController::class, 'backfromjb']);
});*/

// Route::post('/test', function () {
//     return response()->json([

//             "RemoteIP" => null,
//             "ApplicationId" => -999,
//             "TriggeredByUserId" => "",
//             "ActionId" => 0,
//             "ActionMethodName" => "",
//             "ActionNameSpace" => "",
//             "GroupMethodName" => "",
//             "GroupNameSpace" => "",
//             "NoOfArguments" => 0,
//             "RequestType" => "",
//             "MethodArg" => [],
//             "MethodArgLite" => [],
//             "DbTuple" => [],
//             "SqlScriptList" => [],
//             "Base64ObjectString" => "",
//             "DeviceTypeId" => 0,
//             "DeviceId" => "",
//             "DbTupleLite" => [],
//             "ApiTrailId" => -999,
//             "TransactionId" => 0,
//             "Rrn" => "",
//             "ExtRefNo" => "",
//             "Base64Objects" => [],
//             "IsActionBlocked" => false,
//             "ActionName" => "",
//             "StoredProcArg" => [
//                 "StoredProcName" => "",
//                 "ArgumentListLite" => [],
//                 "ReturnField" => [
//                     "Fn" => "",
//                     "Fv" => "",
//                     "Dt" => ""
//                 ],
//                 "DbServerId" => "",
//                 "DefaultDBName" => ""
//             ],
//             "ResponseStatus" => "SUCCESS",
//             "ErrorMessage" => "This lot number is already exists",
//             "ErrorDetail" => "",
//             "ErrorCode" => "",
//             "ErrorLocation" => "",
//             "ExceptionLogId" => ""
//     ], 200);
// });
// Route::post('/test', function () {

//     $responseData = [
//         "RemoteIP" => null,
//         "ApplicationId" => -999,
//         "TriggeredByUserId" => "",
//         "ActionId" => 0,
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
//             "ReturnField" => [
//                 "Fn" => "",
//                 "Fv" => "",
//                 "Dt" => ""
//             ],
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
// });
// Route::post('/test', function (Request $request) {
//     $actionId = $request->input('ActionId');

//     // ডিফল্ট রেসপন্স স্ট্রাকচার
//     $responseData = [
//         "ResponseStatus" => "SUCCESS",
//         "ErrorMessage" => "",
//         "DbTupleLite" => [],
//         "ApplicationId" => $request->input('ApplicationId'),
//         "TriggeredByUserId" => $request->input('TriggeredByUserId'),
//         "ActionId" => $actionId
//     ];

//     // ActionId অনুযায়ী ডেটা ডাইনামিক করা
//     switch ($actionId) {

//         // ১. Lot Validation & Transaction Info (যেখানে success/failed count চেক করা হয়)
//         case '1068': // LotValidationInfoActionId
//         case '1073': // LotTransactionInfoActionId
//             $responseData["DbTupleLite"] = [
//                 [
//                     "RecordList" => [
//                         [
//                             "Record" => [
//                                 ["Fn" => "successCount", "Fv" => "50"], // আপনার প্রয়োজন অনুযায়ী সংখ্যা পাল্টান
//                                 ["Fn" => "rejectedCount", "Fv" => "0"],
//                                 ["Fn" => "status", "Fv" => "Completed"] // বা "Partial"
//                             ]
//                         ]
//                     ]
//                 ]
//             ];
//             break;

//         // ২. Response Details (যেখানে এনক্রিপ্টেড ফাইল ডেটা ফেরত আসে)
//         case '1069': // LotValidationDetActionId
//         case '1074': // LotTransactionDetActionId
//             // এখানে Fv-তে থাকা ডেটা আপনার কোড decrypt এবং gzuncompress করে।
//             // টেস্টের জন্য আগে থেকে তৈরি করা কোনো বেইজ৬৪ এনক্রিপ্টেড স্ট্রিং এখানে দিতে পারেন।
//             $responseData["DbTupleLite"] = [
//                 [
//                     "RecordList" => [
//                         [
//                             "Record" => [
//                                 ["Fn" => "data", "Fv" => "DUMMY_ENCRYPTED_BASE64_DATA"] 
//                             ]
//                         ]
//                     ]
//                 ]
//             ];
//             break;

//         // ৩. Upload Actions (যেখানে শুধু SUCCESS হলেই চলে)
//         case '1060': // LotValidationUploadActionId
//         case '1072': // LotTransactionUploadActionId
//             // এখানে অতিরিক্ত কোনো ডাটার প্রয়োজন নেই, শুধু SUCCESS থাকলেই ডাটাবেস আপডেট হবে
//             $responseData["ResponseStatus"] = "SUCCESS";
//             break;

//         // ৪. বিশেষ এরর টেস্ট (যেমন লট আগে থেকেই আছে কি না)
//         case 'TEST_ERROR': 
//             $responseData["ResponseStatus"] = "FAILURE";
//             $responseData["ErrorMessage"] = "This lot number is already exists";
//             break;
//     }

//     // আপনার কোডের Double JSON Decode হ্যান্ডেল করার জন্য:
//     // প্রথমে অ্যারেটিকে JSON এ কনভার্ট করা হলো, তারপর সেটাকে আবার JSON রেসপন্স হিসেবে পাঠানো হলো।
//     return response()->json(json_encode($responseData), 200);
// });

// Route::post('/test', function (Request $request) {
//     $actionId = (string)$request->input('ActionId');
//     $responseData = [
//         "RemoteIP" => null,
//         "ApplicationId" => (int)$request->input('ApplicationId', -999),
//         "TriggeredByUserId" => $request->input('TriggeredByUserId', ""),
//         "ActionId" => (int)$actionId,
//         "ResponseStatus" => "SUCCESS",
//         "ErrorMessage" => "",
//         "DbTupleLite" => [],
//         "StoredProcArg" => [
//             "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""]
//         ]
//     ];
//     switch ($actionId) {
//         case '1060':
//         case '1072':
//             $responseData["ResponseStatus"] = "FAILURE";
//             $responseData["ErrorMessage"] = "This lot number is already exists";
//             break;
//         case '1068':
//         case '1073':
//             $responseData["DbTupleLite"] = [[
//                 "TableName" => "",
//                 "RecordList" => [[
//                     "Record" => [
//                         ["Fn" => "successCount", "Fv" => "100", "Dt" => ""],
//                         ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""],
//                         ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
//                         ["Fn" => "lotNumber", "Fv" => "T20260420", "Dt" => ""]
//                     ],
//                     "DbTupleLite" => null
//                 ]]
//             ]];
//             break;
//         case '1069':
//         case '1074':
//             $testData = "transaction_id|name|ifsc|accNo|uniqueId\n1|Test User|IFSC001|123456|BEN001";
//             $compressedData = base64_encode(gzcompress($testData));
//             $responseData["DbTupleLite"] = [[
//                 "RecordList" => [[
//                     "Record" => [
//                         ["Fn" => "responseData", "Fv" => $compressedData, "Dt" => ""]
//                     ]
//                 ]]
//             ]];
//             break;
//         default:
//             $responseData["ResponseStatus"] = "FAILURE";
//             $responseData["ErrorMessage"] = "Unknown Action ID";
//             break;
//     }
//     return response()->json(json_encode($responseData), 200);
// });

Route::post('/Bone/LakshmiBhandar/1.0/1234', function (Request $request) {
    $rawContent = $request->getContent();
    // return response()->json([
    //     'received' => $rawContent
    // ]);
    $data = json_decode($rawContent, true);
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
                if (empty($decryptData)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No valid beneficiary data to process'
                    ], 400);
                }

                // Store in file
                try {
                    Storage::put('bandhanbentransactionencdata/' . $filename, $decryptData);
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
