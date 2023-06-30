<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function createSimpleMockResponse($responses)
    {
        $responses_array = [];
        foreach ($responses as $res) {
            $headers = ['Content-Type' => 'application/json'];
            $body = json_encode($res['body']);
            $code = $res['code'];
            $responses_array[] = new Response($code, $headers, $body);
        }
        //        $headers = ['Content-Type' => 'application/json'];
        //        $body = json_encode($responseData);
        //        $response = new Response($statusCode, $headers, $body);

        $mock = new MockHandler($responses_array);
        $handler = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handler]);

        // client instance is bound to the mock here.
        $this->app->instance(GuzzleClient::class, $client);

        return $responses;
    }
}
