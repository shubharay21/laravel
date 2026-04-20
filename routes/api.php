<?php

use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

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

Route::post('/test', function (Request $request) {
    $rawContent = $request->getContent();
    $data = json_decode($rawContent, true);
    $actionId = $data['ActionId'] ?? 'Not Found';
    // return response()->json(['received' => $actionId]);
    switch ($actionId) {
        case '1060':
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
            break;
        case '1074':
            // ইনকামিং ডাটা থেকে এনক্রিপ্টেড স্ট্রিং (Fv) বের করা
            $incomingFv = $data['DbTupleLite'][0]['RecordList'][0]['Record'][0]['Fv'] ?? "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:TEJOSUNAQmFuZGhhbjgyNg==";

            // আপনার রিকোয়েস্ট অনুযায়ী সম্পূর্ণ ১০৭৪ রেসপন্স অবজেক্ট
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
                                        "Fv" => $incomingFv, // এখানে ইনকামিং ডাটাটি ব্যাক করা হচ্ছে
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
            break;
    }
});
