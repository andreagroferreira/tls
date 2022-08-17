<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $user = new User();
            $bearToken = $request->bearerToken();
            if ($bearToken) {
                $publicKey = getPublicKey();
                try {
                    $decodedToken = JWT::decode($bearToken, new Key($publicKey, 'RS256'));
                    $user->id = substr(crc32($decodedToken->sub), 0, 9);
                    $user->family_name = $decodedToken->family_name;
                    $user->given_name = $decodedToken->given_name;
                    $user->name = $decodedToken->name;
                    $user->email = $decodedToken->email;
                    $user->login = current(explode('@', $decodedToken->email));
                    $user->resource_access = $decodedToken->resource_access;
                    $user->groups = $decodedToken->groups;
                    $user->token_expired = false;
                } catch (ExpiredException $e) {
                    $user->token_expired = true;
                    \Log::info($e->getMessage());
                } catch (\Exception $e) {
                    \Log::info($e->getMessage());
                }
            } else {
                $user = null;
            }
            return $user;
        });
    }
}
