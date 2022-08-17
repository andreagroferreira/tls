<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class KeycloakAdminApiService
{
    protected $apiService;
    protected $projectId;
    protected $keycloakRealm;
    protected $keycloakApiPrefix;
    protected $keycloakAdminAccessTokenHeader;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->projectId = $this->apiService->getProjectId();
        $this->keycloakRealm = config('keycloak.realm');
        $this->keycloakApiPrefix = 'auth/admin/realms/' . $this->keycloakRealm;
        $this->keycloakAdminAccessTokenHeader = $this->getAdminAccessTokenHeader();
    }

    public function getAllGroups()
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/groups', [], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') != 200) {
            return [];
        }
        return array_first(array_get($response, 'body', []), function ($value) {
            return array_get($value, 'path') == '/TLSagent';
        });
    }

    public function userEnabledOrDisabled($user_id, $status = false)
    {
        $response = $this->apiService->callKeycloakApi('PUT', $this->keycloakApiPrefix . '/users/' . $user_id, ['enabled' => $status], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') == 204) {
            return true;
        }
        return false;

    }

    public function getGroupDetails($group_id)
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/groups/' . $group_id, [], $this->keycloakAdminAccessTokenHeader);

        if (array_get($response, 'status') != 200) {
            return [];
        }

        return array_get($response, 'body', []);
    }

    public function getAllUsers()
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/users', [], $this->keycloakAdminAccessTokenHeader);

        if (array_get($response, 'status') != 200) {
            return [];
        }

        return array_get($response, 'body', []);
    }

    public function createUser($user_data)
    {
        $response = $this->apiService->callKeycloakApi('POST', $this->keycloakApiPrefix . '/users', $user_data, $this->keycloakAdminAccessTokenHeader);

        return array_get($response, 'status') == 201;
    }

    public function getUserDetails($user_id)
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/users/' . $user_id, [], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') != 200) {
            return [];
        }

        return array_get($response, 'body', []);
    }

    public function setUserAttributes($user_id, $attributes)
    {
        $res = array_get($this->getUserDetails($user_id), 'attributes');
        if (blank($res)) {
            $res = $attributes;
        } else {
            foreach ($attributes as $key => $value) {
                $res[$key] = $value;
            }
        }
        $response = $this->apiService->callKeycloakApi('PUT', $this->keycloakApiPrefix . '/users/' . $user_id, ['attributes' => $res], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') == 204) {
            return true;
        }
        return false;
    }

    // Include groups under the current group
    public function getUsersByGroupId($group_id, $users = [])
    {
        $users = array_merge($users, $this->getGroupMembers($group_id));

        $sub_group = array_get($this->getGroupDetails($group_id), 'subGroups', []);

        if (blank($sub_group)) {
            return $users;
        }

        foreach ($sub_group as $item) {
            $users = $this->getUsersByGroupId(array_get($item, 'id'), $users);
        }

        return collect($users)
            ->groupBy('id')
            ->values()
            ->transform(function ($item, $key) {
                return array_first($item);
            })
            ->toArray();
    }

    public function getUserAssignedGroups($user_id)
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/users/' . $user_id . '/groups', [], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') != 200) {
            return [];
        }

        return collect(array_get($response, 'body', []))
            ->transform(function ($item, $key) {
                $item['path'] = str_replace_first('/TLSagent/ww/', '', array_get($item, 'path', ''));
                return array_only($item, ['id', 'path']);
            })
            ->toArray();
    }

    // Include groups under the current group
    public function getGroupMembersCount($group)
    {
        $users = $this->getGroupMembers(array_get($group, 'id'));
        $group['user_ids'] = array_pluck($users, 'id');
        $group['user_count'] = count($group['user_ids']);

        if (blank(array_get($group, 'subGroups'))) {
            return $group;
        }

        foreach ($group['subGroups'] as &$item) {
            $item = $this->getGroupMembersCount($item);
            $group['user_ids'] = array_unique(array_merge($group['user_ids'], $item['user_ids']));
            $group['user_count'] = count($group['user_ids']);
        }

        return $group;
    }

    public function leaveGroup($user_id, $group_id)
    {
        $response = $this->apiService->callKeycloakApi('DELETE', $this->keycloakApiPrefix . '/users/' . $user_id . '/groups/' . $group_id, [], $this->keycloakAdminAccessTokenHeader);

        if (array_get($response, 'status') == 204) {
            return true;
        }
        return false;
    }

    public function joinGroup($user_id, $country_code, $city_code, $role)
    {
        $ww_group = $this->getWWGroup();
        $group_id = $this->createGroup($ww_group, $country_code . '/' . $city_code . '/' . $role);

        $response = $this->apiService->callKeycloakApi('PUT', $this->keycloakApiPrefix . '/users/' . $user_id . '/groups/' . $group_id, [], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') == 204) {
            return true;
        }
        return false;
    }

    public function getEvents($event)
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/events?client=tlsagent&type=' . $event . '&max=-1', [], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') != 200) {
            return [];
        }

        return array_get($response, 'body', []);
    }

    public function createGroup($parent_group, $path)
    {
        $path_collect = collect(explode('/', $path));
        $first = $path_collect->first();
        if ($first != 'all') {
            $sub_group = array_first(array_get($parent_group, 'subGroups', []), function ($item) use ($first) {
                return array_get($item, 'name') == $first;
            });
            if (blank($sub_group)) {
                $sub_group = $this->setGroupChildren(array_get($parent_group, 'id'), $first);
            }
        } else {
            $sub_group = $parent_group;
        }

        if ($path_collect->count() == 1) {
            return array_get($sub_group, 'id');
        } else {
            $path_collect->shift();
            $group_id = $this->createGroup($sub_group, $path_collect->implode('/'));
        }

        return $group_id;
    }

    protected function setGroupChildren($parent_id, $group_name)
    {
        $response = $this->apiService->callKeycloakApi('POST', $this->keycloakApiPrefix . '/groups/' . $parent_id . '/children', ['name' => $group_name], $this->keycloakAdminAccessTokenHeader);
        if (array_get($response, 'status') != 201) {
            return [];
        }

        return array_get($response, 'body', []);
    }

    protected function getWWGroup()
    {
        $tlsagent_group = $this->getAllGroups();

        if (blank($tlsagent_group)) {
            $this->apiService->callKeycloakApi('POST', $this->keycloakApiPrefix . '/groups', ['name' => 'TLSagent'], $this->keycloakAdminAccessTokenHeader);
            $tlsagent_group = $this->getAllGroups();
        }
        $ww_group = array_first(array_get($tlsagent_group, 'subGroups', []), function ($item) {
            return array_get($item, 'path') == '/TLSagent/ww';
        });
        if (filled($ww_group)) {
            return $ww_group;
        }
        return $this->setGroupChildren(array_get($tlsagent_group, 'id'), 'ww');
    }

    protected function getGroupMembers($group_id)
    {
        $response = $this->apiService->callKeycloakApi('GET', $this->keycloakApiPrefix . '/groups/' . $group_id . '/members?max=-1', [], $this->keycloakAdminAccessTokenHeader);

        if (array_get($response, 'status') != 200) {
            return [];
        }

        return array_get($response, 'body', []);
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
