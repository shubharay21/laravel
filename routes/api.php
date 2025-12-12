<?php

use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\AuthController;
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
            if (is_array($dataArray)) {
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

Route::get('/WbDeath', function () {

    // dd('ok');

    try {
        $client = new \GuzzleHttp\Client();
        $url = url('example1.json');
        $response = $client->get($url);

        if ($response->getStatusCode() === 200) {

            $dataArray = json_decode($response->getBody(), true);

            return response()->json([
                'data'       => $dataArray,
                'TotalRec'   => count($dataArray),
                'Exception'  => false,
                'Errors'     => null
            ], 200);
        }

        return response()->json([
            "data"     => [],
            "TotalRec" => 0,
            "Exception" => false
        ], 200);
    } catch (\Exception $e) {

        return response()->json([
            'data'       => [],
            'TotalRec'   => 0,
            'Exception'  => true,
            'Errors'     => [
                'Message' => $e->getMessage()
            ]
        ], 500);
    }
});


Route::post('/WbDeathDetailsCallBack', function () {

    return response()->json([
        'ResponseDesc'   => 'Details callback successfully processed.',
        'HttpStatusCode' => 200,
        'ResponseType'   => 'Success',
        'Exception'      => false
    ], 200);
});
/*Route::post('jaibanglaapi/auth/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('backfromjb', [AuthController::class, 'backfromjb']);
});*/


