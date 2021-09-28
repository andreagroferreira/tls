<?php

namespace App\Http\Controllers\V1;

use App\Services\RuleEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RuleEngineController extends BaseController
{
    private $ruleEngineService;

    public function __construct(
        RuleEngineService $ruleEngineService
    )
    {
        $this->ruleEngineService = $ruleEngineService;
    }

    public function fetch(Request $request) {
        $params = [
            'client' => $request->route('client'),
            'f_id' => $request->route('f_id'),
            'length' => $request->input('length', 6)
        ];
        $validator = validator($params, [
            'client' => [
                'required',
                'string',
                Rule::in(Storage::disk('rules')->allDirectories()),
            ],
            'f_id' => 'required|integer',
            'length' => 'required|integer'
        ]);

        if($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $form = $this->ruleEngineService->getFormInfo($params['client'], $params['f_id']);
            if(empty($form)) {
                return $this->sendError('no_form_exists', 'form not found');
            }
            $res = $this->ruleEngineService->fetchRules($params['client'], $form);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
