<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RecommendationRuleEngineService
{
    protected $apiService;

    public function __construct(
        ApiService $apiService
    )
    {
        $this->apiService = $apiService;
    }

    public function fetchRules($params)
    {
        $client       = $params['client'];
        $issuer       = $params['issuer'];
        $top          = $params['top'];
        $issuer_rules = $this->getIssuerRules($client, $issuer);
        unset($params['client']);
        unset($params['top']);
        unset($params['issuer']);

        //get all the matched condition rules (ADD or REMOVE)
        $matched_rules = collect($issuer_rules)
            ->filter(function ($client_rule) use ($params) {
                $matched = true;
                foreach ($params as $key => $value) {
                    if ($key == 'Age' && !empty($client_rule['Age Range'])) {
                        $matched = $this->isAgeMatched($value, $client_rule['Age Range']);
                    } else if ($key == 'Step' && !empty($client_rule['Step'])) {
                        $matched = $this->isStepMatched($value, $client_rule[$key]);
                    } else if (!empty($client_rule[$key])) {
                        $matched = $this->isStringMatched($value, $client_rule[$key]);
                    }
                    if (!$matched) {
                        break; //condition checked failed, move to the next rule
                    }
                }
                return $matched;
            })->groupBy('Rule')->toArray();

        return collect($matched_rules['ADD'] ?? [])
            ->filter(function ($add_rule) use ($matched_rules) {
                //remove rules
                $remove_rules = collect($matched_rules['REMOVE'] ?? [])
                    ->pluck('Service ID')
                    ->values()->toArray();
                return !in_array($add_rule['Service ID'], $remove_rules);
            })
            ->pluck('Service ID')
            ->unique()
            ->sortBy('Priority')
            ->take($top)
            ->values()->toArray();
    }

    private function isAgeMatched($age, $rule)
    {
        $match = false;
        try {
            if (str_starts_with($rule, '<=')) {
                $match = $age <= str_replace('<=', '', $rule);
            } else if (str_starts_with($rule, '<')) {
                $match = $age < str_replace('<', '', $rule);
            } else if (str_starts_with($rule, '>=')) {
                $match = $age >= str_replace('>=', '', $rule);
            } else if (str_starts_with($rule, '>')) {
                $match = $age > str_replace('>', '', $rule);
            } else if (strpos($rule, '-') !== false) {
                $condition = explode('-', $rule);
                if (count($condition) != 2 || $condition[1] <= $condition [0]) {
                    Log::info('expression is not valid: ' . $rule);
                    $match = false;
                } else {
                    $match = $age >= $condition[0] && $age <= $condition[1];
                }
            }
        } catch (\Exception $e) {
            Log::info('expression is not valid: ' . $rule);
        }

        return $match;
    }

    private function isStringMatched($string, $rule)
    {
        return $string == $rule;
    }

    private function isStepMatched($step, $rule)
    {
        return in_array($step, array_map('trim', explode(',', $rule)));
    }

    private function getIssuerRules($client, $issuer)
    {
        $client_rules = csvToArray(storage_path('rules/' . $client . '/recommendation_rules.csv'));
        return collect($client_rules)->where('Issuer', $issuer)->values()->toArray();
    }
}
