<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use function Illuminate\Events\queueable;

class RecommendationRuleEngineService
{
    protected $apiService;
    protected $client;
    protected $recommendationConfigService;

    public function __construct(
        ApiService $apiService,
        RecommendationConfigService $recommendationConfigService
    )
    {
        $this->apiService = $apiService;
        $this->recommendationConfigService = $recommendationConfigService;
        $this->client = $this->apiService->getProjectId();
    }

    public function fetchRules($params, $stages_status = [], $basket_skus = [])
    {
        $issuer = $params['issuer'];
        $issuer_rules = $this->getIssuerRules($issuer);
        unset($params['issuer']);

        $conflict_skus = [];
        //get all the matched condition rules
        $matched_rules = collect($issuer_rules)
            ->filter(function ($issuer_rule) use ($params, $stages_status, $basket_skus, &$conflict_skus) {
                $conditions = array_filter($issuer_rule, function($value) {
                    return !is_null($value) && $value !== '';
                });
                $matched = true;
                if(!empty($conditions['AVS Conflict'])) {
                    $resolved_avs_conflict = $this->resolveAvsConflict($basket_skus, $conditions['AVS Conflict']);
                    $conflict_skus = array_merge($conflict_skus, $resolved_avs_conflict['conflict_skus']);
                    if(!$resolved_avs_conflict['matched']) {
                        return false;
                    }
                }
                foreach ($conditions as $key => $rule) {
                    $verified_value = $params[$key] ?? '';
                    switch ($key){
                        case "Visa Type":
                        case "Travel Purpose":
                        case "Visa SubType":
                            $matched = $this->checkList($verified_value, $rule);
                            break;
                        case "In a group":
                        case "City of residence":
                        case "Nationality":
                        case "Account Type":
                        case "On Site":
                        case "Profile":
                            $matched = $this->checkString($verified_value, $rule);
                            break;
                        case "Workflow":
                            $matched = $this->checkWorkflowStatus($stages_status, $rule);
                            break;
                        case "Age Range":
                            $matched = $this->checkAge($verified_value, $rule);
                            break;
                        case "Step":
                            $matched = $this->checkStep($verified_value, $rule);
                            break;
                        default: break;
                    }
                    if (!$matched) {
                        break;
                    }
                }
                return $matched;
            })->groupBy('Rule')->toArray();
        $result['remove'] = array_column($matched_rules['Remove'] ?? [], null, 'Service ID' );
        $result['add'] = $this->getAddSkusAndPriority($issuer, $matched_rules['Add'] ?? []);
        $result['conflict'] = array_values(array_unique($conflict_skus));
        return $result;
    }

    private function getAddSkusAndPriority($issuer, $add_rules) {
        $country = substr($issuer, 0, 2);
        $dest = substr($issuer, -3);
        // get the most priority sku rules
        // city rule > country rule > dest rule
        return collect($add_rules)->map(function ($rule) use ($country, $dest) {
            if ($rule['Scope'] === $dest) {
                $score = 1000;
            } elseif ($rule['Scope'] === $country) {
                $score = 100;
            } else {
                $score = 10;
            }
            $rule['score'] = $score + $rule['Priority'];
            return $rule;
        })->groupBy('Service ID')->map(function($item) {
            $item = $item->sortBy('score')->first();
            unset($item['score']);
            return $item;
        })->toArray();
    }

    private function resolveAvsConflict($basket_avs, $condition) {
        $conflict_skus = [];
        $matched = false;
        if (str_starts_with($condition, 'in_list')) {
            $conflict_skus = array_map('trim', explode(',', preg_replace('/^in_list\((.*)?\)/' , '$1', $condition)));
            $matched = !empty(array_intersect($basket_avs, $conflict_skus));
        } elseif (str_starts_with($condition, 'not_in_list')) {
            $conflict_skus = array_map('trim', explode(',', preg_replace('/^not_in_list\((.*)?\)/' , '$1', $condition)));
            $matched = empty(array_intersect($basket_avs, $conflict_skus));
        }
        return [
            'conflict_skus' => $conflict_skus,
            'matched' => $matched
        ];
    }

    private function checkList($value, $rule)
    {
        if (str_starts_with($rule, 'in_list')) {
            return in_list($value, $rule);
        } else if (str_starts_with($rule, 'not_in_list')) {
            return not_in_list($value, $rule);
        } else {
            return false;
        }
    }

    private function checkString($value, $rule) {
        return $value === $rule;
    }

    private function checkAge($age, $rule)
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
            } else {
                $match = $age === $rule;
            }
        } catch (\Exception $e) {
            Log::info('expression is not valid: ' . $rule);
        }

        return $match;
    }

    private function checkWorkflowStatus($stages_status, $rule)
    {
        if (str_starts_with($rule, 'workflow_status')) {
            return workflow_status($stages_status, $rule);
        } else {
            return false;
        }
    }

    private function checkStep($step, $rule)
    {
        return in_array($step, array_map('trim', explode(',', $rule)));
    }

    private function getIssuerRules($issuer)
    {
        $issuer_rule_cache_key = $this->getIssuerRulesCacheKey($issuer);
        // refresh cache
        if (Cache::has($issuer_rule_cache_key)) {
            return Cache::get($issuer_rule_cache_key);
        }

        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);
        $dest = substr($issuer, -3);
        $rule_config  = $this->recommendationConfigService->fetch(1)->toArray();
        $client_rules = empty($rule_config)
            ? csv_to_array(storage_path('rules/' . $this->client . '/recommendation_rules.csv'), ',')
            : csv2array($rule_config[0]['rc_content'], 'INDEXED_ARRAY', ',');
        $issuer_rules = collect($client_rules)->filter(function($rule) use ($country, $city, $dest){
            return in_array($rule['Scope'], [$country, $city, $dest]);
        })->values()->toArray();
        Cache::put($issuer_rule_cache_key, $issuer_rules, 15 * 60);
        return $issuer_rules;
    }

    private function getIssuerRulesCacheKey($issuer): string
    {
        return 'recommendation_rule_engine_cache_' . $issuer;
    }
}
