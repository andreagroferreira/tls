<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;

class ActionRepository
{
    public function clearActionCache($f_id) {
        if ($f_id) {
            $cacheKey = get_application_action_history_cache_key($f_id);
            Cache::forget($cacheKey);
            $cacheKey = get_form_stage_status_cache_key($f_id);
            Cache::forget($cacheKey);
        }
    }

}
