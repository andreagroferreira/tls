<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\URL;

class PaymentInitiateService
{
    private $guzzleClient;

    public function __construct()
    {
        $this->guzzleClient = new GuzzleClient();
    }

    public function paymentInitiate($method, $url, $params = '', $resources = false, $header = [])
    {
        $url_parts = explode('/', URL::current());
        $server_name = $url_parts[2];
        $ch = curl_init();
        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_REFERER, $server_name);
        // execute post
        $result = curl_exec($ch);
        $Headers = curl_getinfo($ch);
        // close connection
        if ($resources) {
            return $Headers['url'];
        }

        return $result;
    }
}
