<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\BandhanTransactionController;
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
            $dataArray = json_decode($response->getBody());
            $data = is_array($dataArray) ? (object)['details' => $dataArray] : $dataArray;

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

/*
|--------------------------------------------------------------------------
| Bandhan Bank Callback & API Route
|--------------------------------------------------------------------------
|
| Handles all ActionIds (1060, 1068, 1069, 1072, 1073, 1074) & callback payloads.
|
*/

Route::any('/Bone/AnnapurnaLogin/1.0/1234', function (Request $request) {
    $apiKey    = $request->header('api_key') ?? $request->header('api-key');
    $secretKey = $request->header('secret_key') ?? $request->header('secret-key');
    $userId    = $request->header('user_id') ?? $request->header('user-id');
    if ($apiKey != "neugnxzjhgfc3mr4qj2muafg7" || $secretKey != "a1b2c33d4e5f6g7h8i9jakblc" || $userId != "user3") {
        return response()->json([
            'responseCode' => '02',
            'responseMsg' => 'Please provide user Id',
            'token' => "",
        ], 400);
    } else {
        return response()->json([
            'responseCode' => '00',
            'responseMsg' => 'Success',
            'token' => Str::random(30)
        ], 200);
    }
});
Route::any('/Bone/Annapurna/1.0/1234', [BandhanTransactionController::class, 'handleBandhanCallback']);
