<?php

namespace App\Interfaces;


interface LBInterface
{
    public function athentication();
    public function sendtolb();
    public function logoutfromlb();
    public function refreshtokenforlb();
}