<?php

namespace App\Http\Controllers;

use App\Services\BandhanTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BandhanTransactionController extends Controller
{
    protected BandhanTransactionService $bandhanService;

    public function __construct(BandhanTransactionService $bandhanService)
    {
        $this->bandhanService = $bandhanService;
    }

    /**
     * Handle incoming Bandhan callback at /Bone/LakshmiBhandar/1.0/1234
     * Delegates action processing and dedicated storage folder management to BandhanTransactionService.
     *
     * Action Mapping (Annapurna Scheme - Active):
     * - 3713: Lot Validation Upload
     * - 3715: Lot Validation Info
     * - 3716: Lot Validation Response / Det
     * - 3717: Lot Transaction Upload
     * - 3718: Lot Transaction Info
     * - 3719: Lot Transaction Response / Det
     */
    public function handleBandhanCallback(Request $request)
    {
        // Allow sufficient execution time and memory for large beneficiary lots
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $rawContent = $request->getContent();
            $data = $request->all();

            if (empty($data) && !empty($rawContent)) {
                $decoded = json_decode($rawContent, true);
                $data = is_array($decoded) ? $decoded : [];
            }

            return $this->bandhanService->processCallback($data, $rawContent);
        } catch (\Exception $e) {
            Log::error('Bandhan Callback Processing Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
