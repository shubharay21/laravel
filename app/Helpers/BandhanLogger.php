<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BandhanLogger
{
    /**
     * Save raw response and decoded response logs for any Bandhan Action.
     *
     * @param string|int $actionId   Action ID (e.g., '3713', '3715', '3716', etc.)
     * @param mixed      $response   The raw or array response data or JsonResponse
     * @param string|null $lotNumber  Lot Number if available
     * @param array      $context    Additional context (e.g., request data, execution time)
     * @return array                 File paths of saved raw and decoded log files
     */
    public static function logResponse($actionId, $response, ?string $lotNumber = null, array $context = []): array
    {
        $actionIdStr = (string)$actionId;
        $timestamp = date('Ymd_His');
        $lotSuffix = !empty($lotNumber) ? '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$lotNumber) : '';
        
        $folder = "bandhan/{$actionIdStr}";
        if (!Storage::disk('local')->exists($folder)) {
            Storage::disk('local')->makeDirectory($folder);
        }

        // 1. Extract raw response string and decoded representation
        $rawResponseString = '';
        $decodedResponse = null;

        if ($response instanceof JsonResponse) {
            $rawResponseString = (string)$response->getContent();
            $decodedResponse = json_decode($rawResponseString, true) ?? $response->getData(true);
        } elseif (is_string($response)) {
            $rawResponseString = $response;
            $jsonAttempt = json_decode($response, true);
            $decodedResponse = $jsonAttempt !== null ? $jsonAttempt : $response;
        } elseif (is_array($response) || is_object($response)) {
            $decodedResponse = (array)$response;
            $rawResponseString = json_encode($response, JSON_UNESCAPED_SLASHES);
        }

        // If the decoded response has an inner JSON string (like json_encode(responseData) passed inside response()->json()), unwrap it
        if (is_string($decodedResponse)) {
            $inner = json_decode($decodedResponse, true);
            if (is_array($inner)) {
                $decodedResponse = $inner;
            }
        }

        // 2. Prepare filenames
        $rawFilename = "response_raw{$lotSuffix}_{$timestamp}.txt";
        $decodedFilename = "response_decoded{$lotSuffix}_{$timestamp}.json";

        $rawPath = "{$folder}/{$rawFilename}";
        $decodedPath = "{$folder}/{$decodedFilename}";

        // 3. Save Raw Response Log File
        Storage::disk('local')->put($rawPath, $rawResponseString);

        // 4. Save Decoded Response Log File (formatted with pretty print and context)
        $logPayload = [
            'action_id'        => $actionIdStr,
            'lot_number'       => $lotNumber,
            'timestamp'        => date('Y-m-d H:i:s'),
            'context'          => $context,
            'decoded_response' => $decodedResponse,
        ];
        $prettyJson = json_encode($logPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        Storage::disk('local')->put($decodedPath, $prettyJson);

        // 5. Also log to Laravel application logger
        Log::info("Bandhan Action {$actionIdStr} Response Logged", [
            'action_id'    => $actionIdStr,
            'lot_number'   => $lotNumber,
            'raw_file'     => $rawPath,
            'decoded_file' => $decodedPath,
        ]);

        return [
            'raw_file_path'     => $rawPath,
            'decoded_file_path' => $decodedPath,
            'raw_filename'      => $rawFilename,
            'decoded_filename'  => $decodedFilename,
        ];
    }
}
