<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RuleEngineService
{
    protected $apiService;

    public function __construct(
        ApiService $apiService
    )
    {
        $this->apiService = $apiService;
    }

    public function fetchRules($client, $form)
    {
        $rcd_rules = [];
        $all_rules = $this->getAllRcdRules($client);
        foreach ($all_rules as $rule) {
            if (
                $rule['city'] == substr($form['f_xcopy_ug_xref_i_tag'], 2, 3) &&
                $rule['visa_type'] == $form['f_visa_type'] &&
                $this->calcAge($rule['age'], $form['f_pers_age']) &&
                $rule['gender'] == $form['f_pers_sex']
            ) {
                $rcd_rules[] = $rule['sku'];
            }
        }
        return $rcd_rules;
    }

    private function calcAge($exp, $age)
    {
        $match = false;
        try {
            if (str_starts_with($exp, '<=')) {
                $match = $age <= str_replace('<=', '', $exp);
            } else if (str_starts_with($exp, '<')) {
                $match = $age < str_replace('<', '', $exp);
            } else if (str_starts_with($exp, '>=')){
                $match = $age >= str_replace('>=', '', $exp);
            } else if (str_starts_with($exp, '>')) {
                $match = $age > str_replace('>', '', $exp);
            } else if (strpos($exp, '-') !== false) {
                $condition = explode('-', $exp);
                if (count($condition) != 2 || $condition[1] <= $condition [0]) {
                    Log::info('expression is not valid: ' . $exp);
                    $match = false;
                } else {
                    $match = $age >= $condition[0] && $age <= $condition[1];
                }
            }
        } catch (\Exception $e) {
            Log::info('expression is not valid: ' . $exp);
        }

        return $match;
    }

    public function getFormInfo($client, $f_id)
    {
        $form_response = $this->apiService->callTlsApi('GET', "tls/v2/$client/form/$f_id");
        if ($form_response['status'] != 200 || empty($form_response['body'])) {
            return [];
        }
        return $form_response['body'];
    }

    private function getAllRcdRules($client)
    {
        return csvToArray(storage_path('rules/' . $client . '/recommendation_rules.csv'));
    }
}
