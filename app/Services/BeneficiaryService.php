<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Helpers\Encryption;
class BeneficiaryService
{
    public function generateAndEncryptLotData($lotNo)
    {
        $lotBeneficiaryDetails = DB::connection('pgsql_payment')
            ->table('lb_main.av_lot_details')
            ->where('lot_no', $lotNo)
            ->whereNull('av_account_status')
            ->whereNull('name_status')
            ->get();
        if ($lotBeneficiaryDetails->isEmpty()) {
            return ['error' => 'No beneficiary details found', 'code' => 404];
        }
        $finalData = [];
        $successLimit = 5;
        $rejectedLimit = 0;
        $counter = 0;
        foreach ($lotBeneficiaryDetails as $detail) {
            if ($counter < $successLimit) {
                $row = $this->formatRow($detail, "Y", "", "00", "Y", "00", $detail->ben_name);
            } elseif ($counter < ($successLimit + $rejectedLimit)) {
                $row = $this->formatRow($detail, "N", "", "01", "N", "01", "");
            } else {
                break; 
            }
            $finalData[] = $row;
            $counter++;
        }
        $plainTextData = implode('', $finalData);
        if (empty($plainTextData)) {
            return ['error' => 'No valid data to process', 'code' => 400];
        }
        $filename = "enc_beneficiary_response_" . $lotNo . ".txt";
        Storage::put('bandhanbeneficiaryencdata/' . $filename, $plainTextData);
        $key = Config::get('bandhan.EncryptionKey');
        $iv = Config::get('bandhan.IvData');
        if (empty($key) || empty($iv)) {
            return ['error' => 'Encryption configuration missing', 'code' => 500];
        }
        try {
            $compressed = gzcompress($plainTextData, 9);
            $base64Data = base64_encode($compressed);
            $encrypted = Encryption::encryptCode($key, $iv, $base64Data);
            return [
                'success' => true,
                'encryptedData' => $encrypted,
                'totalCount' => count($lotBeneficiaryDetails),
                'successCount' => $successLimit,
                'rejectedCount' => $rejectedLimit
            ];
        } catch (\Exception $e) {
            return ['error' => 'Encryption failed: ' . $e->getMessage(), 'code' => 500];
        }
    }
    private function formatRow($d, $status, $rem, $sCode, $nStatus, $nCode, $nRes)
    {
        return "{$d->ld_id}|{$d->ben_id}|{$status}|{$rem}|{$sCode}|{$nStatus}|{$nCode}|{$nRes}\n";
    }
}