<?php

namespace Tests\Controllers\API\V1;

use App\Services\RuleEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RuleEngineControllerTest extends TestCase
{
    private $form = [
        'f_xcopy_ug_xref_i_tag' => 'zaJNB2be',
        'f_pers_age' => 19,
        'f_pers_sex' => 'M',
        'f_visa_type' => 'short_stay',
    ];

    public function testFetch() {
        $base_url = 'api/v1/rcd_engine';
        $this->get($base_url);
        $this->response->assertStatus(404);

        $base_url = 'api/v1/rcd_engine/fr/10001';
        $this->get($base_url);
        $this->response->assertStatus(400);

        $base_url = 'api/v1/rcd_engine/be/10001';
        $this->get($base_url);
        $this->response->assertStatus(400);

        $ruleEngineService = app()->make('\App\Services\RuleEngineService');
        $rules = $ruleEngineService->fetchRules('be', $this->form);
        $this->assertEquals(1, count($rules));
        $this->assertEquals('YOUNG', array_get($rules, '0'));

        $this->form['f_pers_age'] = 28;
        $ruleEngineService = app()->make('\App\Services\RuleEngineService');
        $rules = $ruleEngineService->fetchRules('be', $this->form);
        $this->assertEquals(2, count($rules));

        $this->form['f_visa_type'] = 'long_stay';
        $ruleEngineService = app()->make('\App\Services\RuleEngineService');
        $rules = $ruleEngineService->fetchRules('be', $this->form);
        $this->assertEquals(0, count($rules));


    }
}
