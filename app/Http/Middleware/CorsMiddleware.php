<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CorsMiddleware
{
    private $headers;
    private $allow_origin;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
            'Access-Control-Allow-Credentials' => 'true',//allow client send cookie
            'Access-Control-Max-Age' => 1728000 // optional, to define the validation time of this request, during this time, client dont need to send pre-validation request anymore.
        ];

        $this->allow_origin = [
            '*'
        ];
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        // return 403 if the origin is not in allowed list
        if ( !in_array('*', $this->allow_origin) || (!in_array('*', $this->allow_origin) && !in_array($origin, $this->allow_origin) && !empty($origin)))
            return new Response('Forbidden', 403);

        // if it's options request, response 200 first, and allow this origin
        if ($request->isMethod('options'))
            return $this->setCorsHeaders(new Response('OK', 200), $origin);
        // if it's simple request no-cors request, set the header as usual
        $response = $next($request);
        $methodVariable = array($response, 'header');
        // if the session middleware activated, system has "header methods not exists" error, so add this if condition, set the header only when the header function exists
        if (is_callable($methodVariable, false, $callable_name)) {
            return $this->setCorsHeaders($response, $origin);
        }
        return $response;
    }
    public function setCorsHeaders($response, $origin)
    {
        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }
        if (in_array($origin, $this->allow_origin)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        } else {
            $response->header('Access-Control-Allow-Origin', '*');
        }
        return $response;
    }
}
