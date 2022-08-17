<?php


namespace App\Services;

class RolePermissionService
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function getAllRights()
    {
        $content = $this->getRolePermissionContent();

        return csv2array($content, 'MULTIPLE_ENTRIES', '--');
    }

    public function getAllRoles()
    {
        $content = $this->getRolePermissionContent();
        $content = csv2array($content, 'MULTIPLE_ENTRIES', '--');

        unset($content['*']);
        return array_keys($content);
    }

    protected function getRolePermissionContent()
    {
        $response = $this->apiService->callEditorApi('GET', "editor/v1/role_permission");

        if (array_get($response, 'status') != 200 || blank(array_get($response, 'body.rp_id'))) {
            return [];
        }

        return array_get($response, 'body.rp_content');
    }
}
