<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Encryption
{
    public static function encryptCode($key, $iv, $base64OfCompressedData)
    {
        $OPENSSL_CIPHER_NAME = "aes-256-cbc"; //Name of OpenSSL Cipher 
        $CIPHER_KEY_LEN = 32; //256 bits
        if (strlen($key) < $CIPHER_KEY_LEN) {
            $key = str_pad("$key", $CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > $CIPHER_KEY_LEN) {
            $key = substr($key, 0, $CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $encodedEncryptedData = base64_encode(openssl_encrypt($base64OfCompressedData, $OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;

        return $encryptedPayload;
    }

    public  static  function decryptCode($key, $data)
    {
        $OPENSSL_CIPHER_NAME = "aes-256-cbc"; //Name of OpenSSL Cipher 
        $CIPHER_KEY_LEN = 32; //256 bits
        if (strlen($key) < $CIPHER_KEY_LEN) {
            $key = str_pad("$key", $CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > $CIPHER_KEY_LEN) {
            $key = substr($key, 0, $CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $parts = explode(':', $data); //Separate Encrypted data from iv.
        $decryptedData = openssl_decrypt(base64_decode($parts[0]), $OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));

        return $decryptedData;
    }
    public static function getenc($lotNumber, $responseSuccess, $responseFailed, $responsePending)
    {
        // dd($lotNumber, $responseSuccess, $responseFailed, $responsePending);                                                             
        $lotNo = $lotNumber; // Assuming $lotNumber is the lot_no
        $filename = "enc_beneficiary_response_" . $lotNo . ".txt"; // Generate filename based on lot_no
        $lotBeneficiaryDetails = "select * from lb_main.av_lot_details where lot_no = '$lotNo'";
        $lotBeneficiaryDetails = DB::connection('pgsql_payment')->select($lotBeneficiaryDetails);
        $finalData = [];
        $successLimit = (int) $responseSuccess;
        $rejectedLimit = (int) $responseFailed;
        $pendingLimit = (int) $responsePending;
        $counter = 0;

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

        // Store in file
        Storage::put('bandhanbeneficiaryencdata/' . $filename, $decryptData);

        // Encrypt
        $key = Config::get('bandhan.EncryptionKey');
        $iv = Config::get('bandhan.IvData');
        $data = Storage::get('bandhanbeneficiaryencdata/' . $filename);
        $compressed = gzcompress($data, 9);
        $base64OfCompressedData = base64_encode($compressed);
        $encryptedData = self::encryptCode($key, $iv, $base64OfCompressedData); // Assuming BandhanPayment is in app/Services

        // Debugging output with dd - COMMENT OUT WHEN DONE DEBUGGING
        // dd([
        //     'input' => compact('lotNumber', 'responseSuccess', 'responseFailed', 'responsePending'),
        //     'Beneficiary SQL' => $lotBeneficiaryDetails,
        //     'Beneficiary Details' => $lotBeneficiaryDetails,
        //     'Final Data' => $finalData,
        //     'Filename' => $filename,
        //     'Key' => $key,
        //     'IV' => $iv,
        //     'Compressed' => $compressed,
        //     'Base64' => $base64OfCompressedData,
        //     'Encrypted' => isset($encryptedData) ? $encryptedData : null
        // ]);

        return $encryptedData;
    }
}
