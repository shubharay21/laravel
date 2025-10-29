<?php

use Illuminate\Support\Facades\Route;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
        "Token" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxNDIwNiwidXNlcl90eXBlIjoxLCJwb3NpdGlvbl9pZCI6MTQ1NTYsInJvbGVfaWQiOjEyLCJyb2xlX2NvZGUiOiJVUzAxMiIsImV4cCI6MTc2MTc0MDM1NywiaWF0IjoxNzYxNzMzMTU3fQ.feLWrdQlq3K80Xm6fehhe_OBFEQD673SCaZ9B2-fv7M"
    ]);
});

Route::post('/cmosvc/shared/wcdpullgriev/', function () {
    $client = new Client();
    try {
        $url = url('example.json');
        $response = $client->get($url);
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return response()->json([
                'status' => 'success',
                'message' => 'File found and loaded successfully',
                'Data' => $data,
                "Exception" => false,
                "Errors" => null,
            ]);
        } else {
            return response()->json([
                'status' => 'warning',
                'message' => 'File found but returned status: ' . $response->getStatusCode(),
            ]);
        }
    } catch (RequestException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ]);
    }
});
