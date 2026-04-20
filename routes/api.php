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

Route::post('/test', function (Request $request) {
    $actionId = (string)$request->input('ActionId');
    $responseData = [
        "RemoteIP" => null,
        "ApplicationId" => (int)$request->input('ApplicationId', -999),
        "TriggeredByUserId" => $request->input('TriggeredByUserId', ""),
        "ActionId" => (int)$actionId,
        "ResponseStatus" => "SUCCESS",
        "ErrorMessage" => "",
        "DbTupleLite" => [],
        "StoredProcArg" => [
            "ReturnField" => ["Fn" => "", "Fv" => "", "Dt" => ""]
        ]
    ];
    switch ($actionId) {
        case '1060':
        case '1072':
            $responseData["ResponseStatus"] = "FAILURE";
            $responseData["ErrorMessage"] = "This lot number is already exists";
            break;
        case '1068':
        case '1073':
            $responseData["DbTupleLite"] = [[
                "TableName" => "",
                "RecordList" => [[
                    "Record" => [
                        ["Fn" => "successCount", "Fv" => "100", "Dt" => ""],
                        ["Fn" => "rejectedCount", "Fv" => "0", "Dt" => ""],
                        ["Fn" => "status", "Fv" => "Completed", "Dt" => ""],
                        ["Fn" => "lotNumber", "Fv" => "T20260420", "Dt" => ""]
                    ],
                    "DbTupleLite" => null
                ]]
            ]];
            break;
        case '1069':
        case '1074':
            $testData = "transaction_id|name|ifsc|accNo|uniqueId\n1|Test User|IFSC001|123456|BEN001";
            $compressedData = base64_encode(gzcompress($testData));
            $responseData["DbTupleLite"] = [[
                "RecordList" => [[
                    "Record" => [
                        ["Fn" => "responseData", "Fv" => $compressedData, "Dt" => ""]
                    ]
                ]]
            ]];
            break;
        default:
            $responseData["ResponseStatus"] = "FAILURE";
            $responseData["ErrorMessage"] = "Unknown Action ID";
            break;
    }
    return response()->json(json_encode($responseData), 200);
});
