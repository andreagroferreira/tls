<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class KeycloakAdminApiService
{
    protected $apiService;
    protected $projectId;
    protected $keycloakAdminAccessTokenHeader;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->projectId = $this->apiService->getProjectId();
        $this->keycloakAdminAccessTokenHeader = $this->getAdminAccessTokenHeader();
    }

    protected function getAdminAccessTokenHeader()
    {
        $refresh_token_cache_key = 'keycloak_admin_refresh_token_' . $this->projectId;
        $access_token_cache_key = 'keycloak_admin_access_token_' . $this->projectId;
        $admin_login_url = 'auth/realms/master/protocol/openid-connect/token';

        if (Cache::has($access_token_cache_key)) {
            return ['headers' => ['Authorization' => 'Bearer ' . Cache::get($access_token_cache_key)]];
        }

        if (Cache::has($refresh_token_cache_key)) {
            $response = $this->apiService->callKeycloakApi('POST-FORM', $admin_login_url, [
                'grant_type' => 'refresh_token',
                'client_id' => 'admin-cli',
                'refresh_token' => Cache::get($refresh_token_cache_key),
            ]);
            if ($response['status'] != 200) {
                return false;
            }
            Cache::put($access_token_cache_key, array_get($response, 'body.access_token'), array_get($response, 'body.expires_in') / 60);
            Cache::put($refresh_token_cache_key, array_get($response, 'body.refresh_token'), array_get($response, 'body.refresh_expires_in') / 60);

            return ['headers' => ['Authorization' => 'Bearer ' . array_get($response, 'body.access_token')]];
        }

        $response = $this->apiService->callKeycloakApi('POST-FORM', $admin_login_url, [
            'grant_type' => 'password',
            'client_id' => 'admin-cli',
            'username' => config('keycloak.admin_username'),
            'password' => config('keycloak.admin_password'),
        ]);
        if ($response['status'] != 200) {
            return false;
        }
        Cache::put($access_token_cache_key, array_get($response, 'body.access_token'), array_get($response, 'body.expires_in') / 60);
        Cache::put($refresh_token_cache_key, array_get($response, 'body.refresh_token'), array_get($response, 'body.refresh_expires_in') / 60);

        return ['headers' => ['Authorization' => 'Bearer ' . array_get($response, 'body.access_token')]];
    }
}
