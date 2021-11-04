<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

class ApiService
{
    private $guzzleClient;
    private $accept = 'application/json';

    public function __construct(GuzzleClient $guzzleClient) {
        $this->guzzleClient = $guzzleClient;
    }

    private function getTlsApiDomain()
    {
        return env('TLSCONTACT_API');
    }

    public function getApiVersion()
    {
        return 'v2';
    }

    private function getDirectusApiDomain()
    {
        return env('DIRECTUS_DOMAIN');
    }

    public function callDirectusApi($method, $url) {
        $url = $this->getDirectusApiDomain() . '/' . $url;
        $method = strtolower($method);
        if ($method == 'get') {
            return $this->getDirectusApi($url);
        } else {
            return '';
        }
    }

    private function getDirectusApi($url) {
        $response = $this->guzzleClient->request('get', $url, [
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

    public function callGeneralApi($method, $url, $data = [], $headers = [], $auth = []) {
        $params = [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false
        ];
        if (!empty($data))    { $params['form_params'] = $data; }
        if (!empty($headers)) { $params['headers']     = $headers; }
        if (!empty($auth))    { $params['auth']        = $auth; }
        $response = $this->guzzleClient->request($method, $url, $params);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf("Request api fail: %s [get_stream] | Parameters: %s | Api Return: %s", $url, json_encode($data, 256), $response));
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
            'verify' => false,
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
            'verify' => false,
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

    public function getTinggAuthorization($tingg_config) {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' =>  $tingg_config['clientID'],
            "client_secret" => $tingg_config['clientSecret'],
        ];
        $response = $this->guzzleClient->request('post', $tingg_config['oauthHost'], [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        return $response['body']['access_token'] ?? '';
    }

    public function getTinggQueryStatus($params, $bearer_token, $tingg_config) {
        $data = [
            'merchantTransactionID' => $params['merchantTransactionID'],
            'serviceCode' => $params['serviceCode']
        ];
        $response = $this->guzzleClient->request('post', $tingg_config['queryStatusHost'], [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json'
            ],
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
        return $response;
    }
}
