<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BandhanPayment
{
    public static function encryptCode($param1, $param2 = null, $param3 = null)
    {
        if (func_num_args() === 1) {
            $data = $param1;
            $key = config('bandhan.EncryptionKey', 'b8832e5779ba07289d9e384ea82f774d');
            $iv = config('bandhan.IvData', 'LBNIC@Bandhan826');
        } elseif (func_num_args() === 3) {
            if (is_string($param1) && strlen($param1) === 32) {
                $key = $param1;
                $iv = $param2;
                $data = $param3;
            } else {
                $data = $param1;
                $key = $param2 ?: config('bandhan.EncryptionKey', 'b8832e5779ba07289d9e384ea82f774d');
                $iv = $param3 ?: config('bandhan.IvData', 'LBNIC@Bandhan826');
            }
        } else {
            $data = $param1;
            $key = $param2 ?: config('bandhan.EncryptionKey', 'b8832e5779ba07289d9e384ea82f774d');
            $iv = config('bandhan.IvData', 'LBNIC@Bandhan826');
        }

        $OPENSSL_CIPHER_NAME = "aes-256-cbc";
        $CIPHER_KEY_LEN = 32;
        if (strlen($key) < $CIPHER_KEY_LEN) {
            $key = str_pad("$key", $CIPHER_KEY_LEN, "0");
        } else if (strlen($key) > $CIPHER_KEY_LEN) {
            $key = substr($key, 0, $CIPHER_KEY_LEN);
        }

        $encodedEncryptedData = base64_encode(openssl_encrypt($data, $OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;

        return $encryptedPayload;
    }

    public static function decryptCode($param1, $param2 = null)
    {
        if (func_num_args() === 1) {
            $data = $param1;
            $key = config('bandhan.EncryptionKey', 'b8832e5779ba07289d9e384ea82f774d');
        } else {
            if (str_contains((string) $param2, ':')) {
                $key = $param1;
                $data = $param2;
            } else {
                $data = $param1;
                $key = $param2 ?: config('bandhan.EncryptionKey', 'b8832e5779ba07289d9e384ea82f774d');
            }
        }

        $OPENSSL_CIPHER_NAME = "aes-256-cbc";
        $CIPHER_KEY_LEN = 32;
        if (strlen($key) < $CIPHER_KEY_LEN) {
            $key = str_pad("$key", $CIPHER_KEY_LEN, "0");
        } else if (strlen($key) > $CIPHER_KEY_LEN) {
            $key = substr($key, 0, $CIPHER_KEY_LEN);
        }

        $parts = explode(':', $data);
        if (count($parts) < 2) {
            return false;
        }

        $decryptedData = openssl_decrypt(base64_decode($parts[0]), $OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));

        return $decryptedData;
    }

    public static function curlPayment($TriggeredByUserId, $ApplicationId, $actionId, $apiFieldArray, $apiFieldValueArray)
    {
        $apiMethodArgArray = [];
        foreach ($apiFieldArray as $key => $apifieldval) {
            $nestedData['FieldName'] = $apifieldval;
            $nestedData['Value'] = $apiFieldValueArray[$key] ?? '';
            $apiMethodArgArray[] = $nestedData;
        }

        $parameters = [
            "TriggeredByUserId" => trim($TriggeredByUserId),
            "ApplicationId" => trim($ApplicationId),
            "ActionId" => trim($actionId),
            "MethodArg" => $apiMethodArgArray
        ];

        $defaultUrl = config('app.env') === 'production'
            ? 'https://lb.bandhanbank.co.in/Bone/LakshmiBhandar/1.0/1234'
            : 'http://laravel.test/api/Bone/LakshmiBhandar/1.0/1234';

        $url = config('paymentapi.BANDHAN_API_ENDPOINT')
            ?: config('bandhan.ApiEndpoint')
            ?: env('BANDHAN_API_ENDPOINT', $defaultUrl);

        $jsonPayload = json_encode($parameters);

        $post = curl_init();
        curl_setopt($post, CURLOPT_HEADER, false);
        curl_setopt($post, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($jsonPayload)
        ]);

        if (str_starts_with($url, 'https://')) {
            curl_setopt($post, CURLOPT_SSLVERSION, 6);
            curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($post, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($post, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($post, CURLOPT_TIMEOUT, 120);
        curl_setopt($post, CURLOPT_URL, $url);
        curl_setopt($post, CURLOPT_POST, 1);
        curl_setopt($post, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($post);
        $errorCurl = curl_error($post);
        $httpCode = (int) curl_getinfo($post, CURLINFO_HTTP_CODE);
        curl_close($post);

        // Detect HTTP error status codes (4xx, 5xx)
        if ($httpCode >= 400) {
            $statusText = match ($httpCode) {
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                500 => 'Internal Server Error',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                default => 'HTTP Error'
            };
            $errorMsg = "Gateway Error [HTTP {$httpCode} - {$statusText}] from {$url}";
            if (!empty($errorCurl)) {
                $errorMsg .= " ({$errorCurl})";
            }
            $errorCurl = $errorMsg;
        } elseif ($result === false && empty($errorCurl)) {
            $errorCurl = "cURL execution failed connecting to {$url}";
        }

        return [
            'result'    => $result,
            'errorCurl' => $errorCurl,
            'httpCode'  => $httpCode
        ];
    }
}
