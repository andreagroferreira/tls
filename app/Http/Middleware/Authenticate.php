<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    private $ignoreIssuerAuth = [];

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$role)
    {
        if (env("APP_ENV") == 'testing') {
            return $next($request);
        }

        /*
        if ($this->auth->guard($guard)->guest()) {
            return response('Unauthorized.', 401);
        }
        */

        if (!is_object($this->auth->user())) {
            return response('Unauthorized.', 401);
        }
        if ($this->auth->user()->token_expired) {
            return response('Unauthorized.', 401);
        }

        /* for client project, not need issuer params */
        if ($request->method() == 'GET' && in_array($request->path(), $this->ignoreIssuerAuth)) {
            return $next($request);
        }
        $check = baseCheck($role, $this->auth->user()->groups);
        if (!$check) {
            return response('Unauthorized.', 403);
        }

        return $next($request);
    }
}
