<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Interfaces\LBInterface;

class LBService implements LBInterface
{
    protected $baseurl;
    public function __construct()
    {
        if (app()->environment(['local', 'staging'])) {
            $this->baseurl = 'http://lbv2.test/api/';
        } else {
            $this->baseurl = 'https://socialsecurity.wb.gov.in/';
        }
    }

    public function athentication()
    {
        try {
            $client = new Client();
            $url = $this->baseurl . 'lbapi/auth/login';
            $response = $client->post($url, [
                'json' => [
                    'email' => 'approverdpaschimedinipur@gmail.com',
                    'password' => '1234',
                ]
            ]);
            $body = json_decode($response->getBody());
            if ($body->is_success) {
                return $body->token;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    public function sendtolb()
    {
        if ($this->athentication()) {
            try {
                $client = new Client();
                $url = $this->baseurl . 'sendtolb';
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->athentication(),
                        'Accept'        => 'application/json',
                    ]
                ]);
                $body = json_decode($response->getBody());
                dd($body->message);
            } catch (\Exception $e) {
                return 'Error: ' . $e->getMessage();
            }
        } else {
            dd('Token is invalid');
        }
    }
    public function logoutfromlb()
    {
        if ($this->athentication()) {
            try {
                $client = new Client();
                $url = $this->baseurl . 'logout';
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->athentication(),
                        'Accept'        => 'application/json',
                    ]
                ]);
                $body = json_decode($response->getBody());
                dd($body->message);
            } catch (\Exception $e) {
                return 'Error: ' . $e->getMessage();
            }
        } else {
            dd('Token is invalid');
        }
    }

    public function refreshtokenforlb()
    {
        if ($this->athentication()) {
            try {
                $client = new Client();
                $url = $this->baseurl . 'refresh';
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->athentication(),
                        'Accept'        => 'application/json',
                    ]
                ]);
                $body = json_decode($response->getBody());
                dd($body->token);
            } catch (\Exception $e) {
                return 'Error: ' . $e->getMessage();
            }
        } else {
            dd('Token is invalid');
        }
    }
}
