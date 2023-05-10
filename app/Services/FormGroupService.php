<?php

namespace App\Services;

class FormGroupService
{
    private $apiService;
    private $form_group = [];
    private $forms_in_group = [];

    public function __construct(ApiService $apiService) {
        $this->apiService = $apiService;
    }

    public function fetch($fg_id, $client) {
        $form_group = $this->callFormGroupApi($fg_id, $client);
        if (($form_group['status'] ?? '') != 200) return [];
        $this->form_group = $form_group['body'];

        return $this->form_group;
    }

    public function fetchFomrs($fg_id, $client) {
        $forms_in_group = $this->callFormInGroupApi($fg_id, $client);
        if (($forms_in_group['status'] ?? '') != 200) return [];
        $this->forms_in_group = $forms_in_group['body'];

        return $this->forms_in_group;
    }

    private function callFormGroupApi($fg_id, $client)
    {
        return $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/form_group/' . $fg_id);
    }

    public function callFormInGroupApi($fg_id, $client)
    {
        return $this->apiService->callTlsApi('GET', '/tls/v2/' . $client . '/forms_in_group/' . $fg_id);
    }
}
