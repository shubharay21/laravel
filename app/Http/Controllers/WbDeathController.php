<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WbDeathController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('FromDate');
        $to = $request->query('ToDate');
        $pageIndex = $request->query('PageIndex', 1);
        $pageSize = $request->query('PageSize', 10);

        // শুধু ডেমো হিসেবে "ok" রিটার্ন
        return response()->json([
            "status" => "ok",
            "received" => [
                "FromDate" => $from,
                "ToDate" => $to,
                "PageIndex" => $pageIndex,
                "PageSize" => $pageSize
            ]
        ]);
    }
}
