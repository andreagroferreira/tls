<?php

function get_application_action_history_cache_key($f_id): string
{
    return 'application_action_history_cache_' . app()->make(App\Services\ApiService::class)->getProjectId() . '_' . $f_id;
}

function get_applicant_cache_key($f_id): string
{
    return 'applicant_info_cache_' . app()->make(App\Services\ApiService::class)->getProjectId() . '_' . $f_id;
}

function get_form_stage_status_cache_key($f_id): string
{
    return 'form_stage_status_cache_' . app()->make(App\Services\ApiService::class)->getProjectId() . '_' . $f_id;
}

function get_cache_ttl($type)
{
    switch ($type) {
        case 'applicant':
            $ttl = 5 * 60;
            break;
        case 'visa_type':
        case 'configuration':
        case 'issuer':
            $ttl = 30 * 60;
            break;
        default:
            $ttl = 15 * 60;
    }
    return $ttl;
}
