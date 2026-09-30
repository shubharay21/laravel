<?php

namespace App\Services;

use App\Helpers\BandhanLogger;

class BandhanLoggerService
{
    /**
     * Save raw response and decoded response log for any Bandhan Action.
     *
     * @param string|int $actionId   Action ID
     * @param mixed      $response   Response data or JsonResponse
     * @param string|null $lotNumber  Lot Number
     * @param array      $context    Additional context
     * @return array                 File paths of saved raw and decoded log files
     */
    public function log(string|int $actionId, $response, ?string $lotNumber = null, array $context = []): array
    {
        return BandhanLogger::logResponse($actionId, $response, $lotNumber, $context);
    }
}
