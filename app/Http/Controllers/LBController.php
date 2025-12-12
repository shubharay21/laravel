<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\LBInterface;
class LBController extends Controller
{
    protected $lbService;
    public function __construct(LBInterface $lbService)
    {
        $this->lbService = $lbService;
    }
    public function sendtolb()
    {
        $data = $this->lbService->sendtolb();
    }
    public function logoutfromlb()
    {
        $data = $this->lbService->logoutfromlb();
    }
    public function refreshtokenforlb()
    {
        $data = $this->lbService->refreshtokenforlb();
    }
}
