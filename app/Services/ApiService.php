<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class ApiService
{
    private $guzzleClient;
    private $accept = 'application/json';

    public function __construct() {
        $this->guzzleClient = new GuzzleClient();
    }

    private function getTlsApiDomain()
    {
        return env('TLSCONTACT_API');
    }

    public function getApiVersion()
    {
        return 'v2';
    }

    public function callTlsApi($method, $url, $data = array())
    {
        $url = $this->getTlsApiDomain() . '/' . $url;
        switch (strtolower($method)) {
            case 'get':
                $response = $this->getApi($url);
                break;
            case 'get_stream':
                $response = $this->getStreamApi($url, $data);
                break;
            case 'post':
                $response = $this->postApi($url, $data);
                break;
            default:
                $response = null;
                break;
        }
        return $response;
    }

    public function callInvoiceApi($url, $data)
    {
        return $this->postApi($url, $data);
    }

    private function getApi($url)
    {
        $response = $this->guzzleClient->request('get', $url, [
            'verify' => env('VERIFYPEER'),
            'http_errors' => false,
            'idn_conversion' => false,
            'headers' => [
                'log-uuid' => request()->get('log-uuid'),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            ],
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf("Request api fail: %s [GET] | Api Return: %s", $url, json_encode($response, 256)));
        }
        return $response;
    }

    private function getStreamApi($url, $data)
    {
        $response = $this->guzzleClient->request('post', $url, [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => $this->accept,
            'headers' => ['log-uuid' => request()->get('log-uuid')],
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => $response->getBody()
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf("Request api fail: %s [get_stream] | Parameters: %s | Api Return: %s", $url, json_encode($data, 256), $response));
        }
        return $response;
    }

    private function postApi($url, $data)
    {
        $response = $this->guzzleClient->request('post', $url, [
            'verify' => env('VERIFYPEER'),
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => $this->accept,
            'headers' => ['log-uuid' => request()->get('log-uuid')],
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf("Request api fail: %s [POST] | Parameters: %s | Api Return: %s", $url, json_encode($data, 256), json_encode($response, 256)));
        }
        return $response;
    }
}