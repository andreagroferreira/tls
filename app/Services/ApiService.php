<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiService
{
    private $guzzleClient;
    private $accept = 'application/json';

    public function __construct(GuzzleClient $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    public function getApiVersion()
    {
        return 'v2';
    }

    public function getProjectId($project = '')
    {
        $project = $project ?: getenv('CLIENT');

        switch ($project) {
            case 'gss-us':
                return 'us';

            case 'srf-fr':
                return 'srf_fr';

            case 'hmpo-uk':
                return 'hmpo_uk';

            case 'leg-be':
                return 'leg_be';

            case 'biolab-ma':
                return 'biolab_ma';

            default:
                return substr($project, -2);
        }
    }

    public function callDirectusApi($method, $url)
    {
        $url = $this->getDirectusApiDomain() . '/' . $url;
        $method = strtolower($method);
        if ($method == 'get') {
            return $this->getDirectusApi($url);
        }

        return '';
    }

    public function callKeycloakApi($method, $url, $data = [])
    {
        $url = $this->getKeycloakDomain() . '/' . $url;
        if (strtolower($method) == 'get') {
            return $this->getApi($url);
        }
        if (strtolower($method) == 'post-form') {
            return $this->postFormApi($url, $data);
        }
        if (strtolower($method) == 'post') {
            return $this->postApi($url, $data);
        }
    }

    public function callTlsApi($method, $url, $data = [])
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

    public function callGeneralApi($method, $url, $data = [], $headers = [], $auth = [])
    {
        $params = [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
        ];
        if (!empty($data)) {
            $params['form_params'] = $data;
        }
        if (!empty($headers)) {
            $params['headers'] = $headers;
        }
        if (!empty($auth)) {
            $params['auth'] = $auth;
        }
        $response = $this->guzzleClient->request($method, $url, $params);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [get_stream] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response)));
        }

        return $response;
    }

    public function callGeneralApiJson($method, $url, $data = [], $headers = [], $auth = [])
    {
        $params = [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
        ];
        if (!empty($data)) {
            $params['json'] = $data;
        }
        if (!empty($headers)) {
            $params['headers'] = $headers;
        }
        if (!empty($auth)) {
            $params['auth'] = $auth;
        }
        $response = $this->guzzleClient->request($method, $url, $params);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200 && $response['status'] != 201) {
            Log::error(sprintf('Request api fail: %s [get_stream] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response)));
        }

        return $response;
    }

    public function callInvoiceApi($url, $data)
    {
        return $this->postApi($url, $data);
    }

    public function callEmailApi($method, $url, $data = [], $options = '')
    {
        $url = $this->getEmailApiDomain() . '/' . $url;
        $response = ['status' => 400, 'body' => 'Method not Allowed!'];
        if (strtolower($method) == 'post') {
            for ($i = 1; $i < 3 && $response['status'] != 200; ++$i) {
                $response = $this->postApi($url, $data, $options);
            }
        }

        return $response;
    }

    public function getTinggAuthorization($tingg_config)
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $tingg_config['clientID'],
            'client_secret' => $tingg_config['clientSecret'],
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
            'body' => json_decode($response->getBody(), true),
        ];

        return $response['body']['access_token'] ?? '';
    }

    public function getTinggQueryStatus($params, $bearer_token, $tingg_config)
    {
        $data = [
            'merchantTransactionID' => $params['merchantTransactionID'],
            'serviceCode' => $params['serviceCode'],
        ];
        $response = $this->guzzleClient->request('post', $tingg_config['queryStatusHost'], [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
    }

    public function callEAuditorApi($method, $port, $data = [])
    {
        $url = $this->getEAuditorDomain() . ':' . $port;
        if (strtolower($method) == 'post') {
            return $this->postApi($url, $data);
        }
    }

    public function yookassaCreatePayment($params, $yookassa_config, $Idempotence_Key): array
    {
        $response = $this->guzzleClient->request('post', $yookassa_config['host'], [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Idempotence-Key' => $Idempotence_Key,
                'Authorization' => 'Basic ' . base64_encode("{$yookassa_config['shop_id']}:{$yookassa_config['secret_key']}"),
                'Content-Type' => 'application/json',
            ],
            'json' => $params,
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
    }

    public function yookassaCapturePayment($payment_id, $yookassa_config, $Idempotence_Key): array
    {
        $response = $this->guzzleClient->request('post', $yookassa_config['host'] . "/{$payment_id}/capture", [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Idempotence-Key' => $Idempotence_Key,
                'Authorization' => 'Basic ' . base64_encode("{$yookassa_config['shop_id']}:{$yookassa_config['secret_key']}"),
                'Content-Type' => 'application/json',
            ],
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
    }

    public function getYookassaPayment($payment_id, $yookassa_config, $Idempotence_Key): array
    {
        $response = $this->guzzleClient->request('get', $yookassa_config['host'] . "/{$payment_id}", [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => 'application/json',
            'headers' => [
                'Idempotence-Key' => $Idempotence_Key,
                'Authorization' => 'Basic ' . base64_encode("{$yookassa_config['shop_id']}:{$yookassa_config['secret_key']}"),
                'Content-Type' => 'application/json',
            ],
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
    }

    /**
     * @param string   $queryParams
     * @param Response $data
     * @param string   $folderName
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function callFileLibraryUploadApi(string $queryParams, Response $data, string $folderName): array
    {
        $url = $this->getFileLibraryApiDomain() . '/api/' . $this->getFileLibraryApiVersion() . '/file-library/upload/' . $folderName . '?' . $queryParams;
        $response = $this->guzzleClient->request('post', $url, [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => $this->accept,
            'headers' => ['log-uuid' => request()->get('log-uuid'), 'Content-Type' => 'application/pdf'],
            'body' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [POST] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response, 256)));
        }

        return $response;
    }

    /**
     * @param $queryParams
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function callFileLibraryDownloadApi($queryParams): array
    {
        $url = $this->getFileLibraryApiDomain() . '/api/' . $this->getFileLibraryApiVersion() . '/file-library/download?' . $queryParams;

        return $this->getStreamApi($url, [], 'get');
    }

    /**
     * @param string $queryParams
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function callFileLibraryFilesApi(string $queryParams): array
    {
        return $this->getApi($this->getFileLibraryApiDomain() . '/api/' . $this->getFileLibraryApiVersion() . '/file-library/files/' . $queryParams);
    }

    /**
     * @param $queryParams
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function callCustomerServiceInvoiceDownloadApi(string $path)
    {
        $url = env('CUSTOMER_SERVICE_DOMAIN') . '/api/tls/invoice_pdf/' . $path;

        return $this->getStreamApi($url, [], 'get');
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     *
     * @return array
     */
    public function callWorkflowApi(string $method, string $url, array $data = []): array
    {
        $url = $this->getWorkflowApiDomain() . '/' . $url;

        switch (strtolower($method)) {
            case 'post':
                $response = $this->postApi($url, $data);

                break;

            default:
                $response = null;

                break;
        }

        return $response;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function callEcommerceApi(string $method, string $url, array $data = []): array
    {
        $url = $this->getEcommerceApiDomain() . '/' . $url;

        switch (strtolower($method)) {
            case 'put':
                $response = $this->putApi($url, $data);

                break;

            default:
                $response = null;

                break;
        }

        return $response;
    }

    private function getTlsApiDomain()
    {
        return env('TLSCONTACT_API');
    }

    private function getEAuditorDomain()
    {
        return env('TLSCONTACT_EAUDITOR_DOMAIN');
    }

    private function getKeycloakDomain()
    {
        return config('services.keycloak_web.base_url');
    }

    private function getDirectusApiDomain()
    {
        return env('DIRECTUS_DOMAIN');
    }

    private function getEmailApiDomain()
    {
        return env('EMAIL_SERVICE_DOMAIN');
    }

    private function getDirectusApi($url)
    {
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
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [GET] | Api Return: %s', $url, json_encode($response, 256)));
        }

        return $response;
    }

    private function postFormApi($url, $data)
    {
        $response = $this->guzzleClient->request('post', $url, [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'form_params' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [POST] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response, 256)));
        }

        return $response;
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
        $responseHeaders = $response->getHeaders();
        $response = [
            'status' => $response->getStatusCode(),
            'body' => (array_first($responseHeaders['Content-Type']) === 'image/png') ? $response->getBody() : json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [GET] | Api Return: %s', $url, json_encode($response, 256)));
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array  $data
     * @param string $method
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getStreamApi(
        string $url,
        array $data,
        string $method = 'post'
    ): array {
        $response = $this->guzzleClient->request($method, $url, [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => $this->accept,
            'headers' => ['log-uuid' => request()->get('log-uuid')],
            'json' => $data,
        ]);

        $response = [
            'status' => $response->getStatusCode(),
            'body' => $response->getBody(),
        ];

        if ($response['status'] != 200) {
            Log::error(sprintf(
                'Request api fail: %s [get_stream] | Parameters: %s | Api Return: %s',
                $url,
                json_encode($data, 256),
                json_encode($response, 256)
            ));
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
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [POST] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response, 256)));
        }

        return $response;
    }

    private function getFileLibraryApiDomain(): string
    {
        return env('FILE_LIBRARY_API_DOMAIN');
    }

    private function getFileLibraryApiVersion(): string
    {
        return 'v1';
    }

    /**
     * @return string
     */
    private function getWorkflowApiDomain(): string
    {
        return env('WORKFLOW_SERVICE_DOMAIN');
    }

    /**
     * @return string
     */
    private function getEcommerceApiDomain(): string
    {
        return env('ECOMMERCE_SERVICE_DOMAIN');
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function putApi(string $url, array $data): array
    {
        $response = $this->guzzleClient->request('put', $url, [
            'verify' => false,
            'http_errors' => false,
            'idn_conversion' => false,
            'Accept' => $this->accept,
            'headers' => ['log-uuid' => request()->get('log-uuid')],
            'json' => $data,
        ]);
        $response = [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ];
        if ($response['status'] != 200) {
            Log::error(sprintf('Request api fail: %s [PUT] | Parameters: %s | Api Return: %s', $url, json_encode($data, 256), json_encode($response, 256)));
        }

        return $response;
    }
}
