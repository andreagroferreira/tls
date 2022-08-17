<?php

namespace App\Services;

use App\Exceptions\Custom\KeycloakException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Helpers\BasicFunctionsHelp;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Client\Provider\GenericProvider;
use Illuminate\Contracts\Auth\Factory as Auth;

class KeycloakService
{
    protected $apiService;
    protected $project;
    protected $provider;
    protected $syncRole = ["agent", "manager", "supervisor", "dev"];
    protected $useKeycloakProject = ['tlsconnect', 'editor'];
    protected $syncedCode = 'already_synced';
    protected $keycloakAdminApiService;
    protected $rolePermissionService;
    protected $issuerService;
    private $auth;

    public function __construct(
        ApiService $apiService,
        KeycloakAdminApiService $keycloakAdminApiService,
        RolePermissionService $rolePermissionService,
        Auth $auth
    )
    {
        $this->provider = new GenericProvider([
            'clientId' => config('keycloak.client_id'),
            'clientSecret' => config('keycloak.client_secret'),
            'redirectUri' => 'redirect url',
            'urlAuthorize' => config('keycloak.host') . '/auth/realms/' . config('keycloak.realm') . '/protocol/openid-connect/auth',
            'urlAccessToken' => config('keycloak.host') . '/auth/realms/' . config('keycloak.realm') . '/protocol/openid-connect/token',
            'urlResourceOwnerDetails' => config('keycloak.host') . '/auth/realms/' . config('keycloak.realm') . '/protocol/openid-connect/userinfo',
        ]);
        $this->apiService = $apiService;
        $this->keycloakAdminApiService = $keycloakAdminApiService;
        $this->rolePermissionService = $rolePermissionService;
        $this->issuerService = 1;
        $this->auth = $auth;
    }

    public function logout($params) {
        $response = $this->apiService->callKeycloak('POST-FORM', 'auth/realms/atlas-private-azure/protocol/openid-connect/logout', [
            'client_id' => 'tlsagent',
            'refresh_token' => $params['refresh_token']
        ]);
        if ($response['status'] != 204) {
            throw new KeycloakException('Keycloak logout failed');
        }
        return true;
    }

    public function extractFromToken($token)
    {
        $publicKey = BasicFunctionsHelp::getPublicKey();
        return JWT::decode($token, new Key($publicKey, 'RS256'));
    }

    public function check($params, $login)
    {
        $admin_login_url = 'auth/realms/atlas-private-azure/protocol/openid-connect/token';
        $response = $this->apiService->callKeycloakApi('POST-FORM', $admin_login_url, [
            'grant_type' => 'password',
            'client_id' => 'tlsagent',
            'username' => $params['username'],
            'password' => $params['password'],
        ]);
        if ($response['status'] == 200) {
            $token = $response['body']['access_token'];
            $result = $this->extractFromToken($token);
            if ($result->preferred_username == $login) {
                return [
                    'status' => 'error',
                    'code' => 'E0052',
                    'message' => 'user_validate_fail'
                ];
            }
            return [
                'status' => 'success',
                'message' => [
                    'userId' => $result->sub,
                    'username' => $result->preferred_username,
                    'email' => $result->email,
                    'roles' => $result->groups,
                ]
            ];
        }
        return [
            'status' => 'error',
            'code' => 'E0052',
            'message' => 'user_validate_fail'
        ];
    }

    public function getPermissionLevel(): array {
        if (empty($this->auth->user()->groups)) {
            return [];
        }
        $roles = $this->auth->user()->groups;
        $allow_issuers = [];
        $permission_level = [];
        foreach ($roles as $role) {
            $role_arr = explode('/', $role);
            array_pop($role_arr);
            $role_name = array_pop($role_arr);
            //global role, TLSagent/ww/agent
            if ($role_name == 'ww') {
                return [
                    'allow_country_issuers' => ['all'],
                    'permission_level' => ['all_permission'],
                ];
            }
            // city role,  TLSagent/ww/de/TUN/agent etc.
            if (strlen($role_name) === 3) {
                $issuer = array_pop($role_arr). strtoupper($role_name).'2'.substr(getenv('CLIENT'), -2);
                array_push($allow_issuers, $issuer);
                $permission_level[] = 'city_permission';
            }
            // country role ,like TLSagent/ww/tn/agent etc.
            if (strlen($role_name) === 2) {
                $issuers = $this->getByCountry($role_name);
                $allow_issuers = array_merge($allow_issuers, $issuers);
                $permission_level[] = 'country_permission';
            }
        }
        return [
            'allow_country_issuers' => $allow_issuers,
            'permission_level' => $permission_level,
        ];
    }

    public function getByCountry($role_name): array {
        $all_issuers = $this->issuerService->fetchAll();
        $current_country = [];
        foreach ($all_issuers as $issuer) {
            if (in_array($issuer['i_place'], explode(',', $role_name))) {
                array_push($current_country, $issuer['i_place']);
            }
        }
        return $current_country;
    }

    public function sync($params) {
        $this->project = $this->apiService->getProjectId();
        $keycloakUserId = $params['keycloak_user_id'] ?? '';
        $userDetail = $this->keycloakAdminApiService->getUserDetails($keycloakUserId);
        $permission = $this->keycloakAdminApiService->getUserAssignedGroups($keycloakUserId) ?? [];
        if (!$this->isSynced($userDetail)) {
            $res = $this->fromTheAmcSyncGroup($userDetail);
            if (!$res) {
                return ['error' => 'Error', 'status' => 'permissions_sync_fail', 'message' => 'permissions_sync_fail', 'user_permission' => array_column($permission, 'path')];
            } else {
                return ['status' => 'permissions_sync_success', 'message' => 'permissions_sync_success', 'user_permission' => array_column($permission, 'path')];
            }
        }
        return ['status' => 'permissions_sync_success', 'message' => 'permissions_sync_success', 'user_permission' => array_column($permission, 'path')];
    }

    public function isSynced($user_keycloak_info)
    {
        $attributes = $user_keycloak_info['attributes'][$this->syncedCode] ?? [];
        return in_array('true', $attributes);
    }

    private function fromTheAmcSyncGroup($user_keycloak_info) {
        $response = true;
        $userId = array_get($user_keycloak_info, 'sub') ? array_get($user_keycloak_info, 'sub') : array_get($user_keycloak_info, 'id');
        foreach ($this->useKeycloakProject as $type) {
            $res = $this->apiService->callAmcApi('GET', 'api/get_user_permission.php?project=' . $type . '&login=' . array_get($user_keycloak_info, 'email'));
            if (array_get($res, 'status') != 200 || blank(array_get($res, 'body.permissions'))) {
                continue;
            }
            $permissions = (array)array_get($res, 'body.permissions');
            foreach ($permissions as $item) {
                $role = $this->roleNameSynchronization(array_get($item, 'role'));
                if (!in_array(strtolower(array_get($item, 'i_dest')), ['all', $this->project]) || !in_array(strtolower($role), $this->syncRole)) {
                    continue;
                }

                $country = $this->getCountry($item);
                if ($country == 'error') {
                    continue;
                }
                $result = $this->keycloakAdminApiService->joinGroup(
                    array_get($user_keycloak_info, 'id'),
                    $country,
                    (strtolower(array_get($item, 'i_city')) == 'all' ? 'all' : strtoupper(array_get($item, 'i_city'))),
                    $role
                );
                if (!$result) {
                    $response = false;
                }
            }
        }
        $this->keycloakAdminApiService->setUserAttributes($userId, [$this->syncedCode => 'true']);
        return $response;
    }

    private function getCountry($item) {
        // fix city:PAR, country: All config in amc
        $country = '';
        $issuers = $this->issuerService->fetchAll();
        if (strtolower(array_get($item, 'i_city')) != 'all') {
            foreach ($issuers as $value) {
                if ($value['i_city'] == array_get($item, 'i_city')) {
                    return $value['i_place'];
                } else {
                    $country = 'error';
                }
            }
        } else {
            $country = strtolower(array_get($item, 'i_place'));
        }
        return $country;
    }

    public function getIssuersByCountry($country): array {
        $all_issuers = $this->issuerService->fetchAll();
        $current_issuers = [];
        foreach ($all_issuers as $issuer) {
            if (in_array($issuer['i_place'], explode(',', $country))) {
                array_push($current_issuers, $issuer['i_tag']);
            }
        }
        return $current_issuers;
    }

    private function roleNameSynchronization($role_name) {
        /*
         * some role name need replaced agent
         * https://gitlab.com/dev_tls/TLSagent/backlog/-/issues/190
         */
        switch (strtolower($role_name)) {
            case 'agent_new':
                return 'agent';
            case 'agent_cashier_new':
                return 'agent';
            case 'agent_cashier':
                return 'agent';
            case 'cashier':
                return 'agent';
            case 'call center':
                return 'agent';
            default:
                return $role_name;
        }
    }
}
