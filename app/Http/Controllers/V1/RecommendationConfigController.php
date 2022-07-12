<?php

namespace App\Http\Controllers\V1;

use App\Services\RecommendationConfigService;
use Illuminate\Http\Request;
use App\Jobs\PaymentProfileUploadLogJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

class RecommendationConfigController extends BaseController
{
    private $recommendationConfigService;

    public function __construct(
        RecommendationConfigService $recommendationConfigService
    )
    {
        $this->recommendationConfigService = $recommendationConfigService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/recommendation-config-upload",
     *     tags={"Payment API"},
     *     description="upload csv file to table recommendataion_config",
     *      @OA\Parameter(
     *          name="files",
     *          in="query",
     *          description="upload the file name",
     *          required=true,
     *          @OA\Schema(type="string", example="recommendataion_config.csv"),
     *      ),
     *     @OA\Parameter(
     *          name="uploaded_by",
     *          in="query",
     *          description="log on user",
     *          required=true,
     *          @OA\Schema(type="string", example="hua"),
     *      ),
     *     @OA\Parameter(
     *          name="comment",
     *          in="query",
     *          description="user comments",
     *          required=false,
     *          @OA\Schema(type="string", example="hua"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="return upload success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function upload(Request $request)
    {
        $params = [
            'rc_file_name' => $request->input('files_name'),
            'rc_uploaded_by' => $request->input('uploaded_by'),
            'rc_comment' => $request->input('comment'),
        ];
        $validator = validator($params, [
            'rc_file_name'   => 'required',
            'rc_uploaded_by'  => 'required|string',
            'rr_comment' => 'string|nullable'
        ]);
        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }
        // check file structure
        $files = $params['rc_file_name'];
        if (is_array($files)) {
            foreach ($files as $name => $content) {
                $fileInfo = [
                    'fileName' => $name,
                    'fileContent' => $content['fileContent']
                ];
            }
        }
        $fileName = $fileInfo['fileName'];
        $entension = substr($fileName,strrpos($fileName,'.')+1);
        if ($entension != 'csv') {
            return $this->sendError('file format error', 'Please upload a file in CSV format.');
        }
        $fileContent = base64_decode($fileInfo['fileContent']);
        $fileSize = strlen($fileContent);
        try{
            $recommendation_rules_content = csv_content_array($fileContent, ',');
        } catch (\Exception $e) {
            return $this->sendError('file error', 'Please upload the correct file');
        }
        $header = ['Rule', 'Scope', 'Service ID', 'Priority', 'Profile', 'Visa Type', 'Travel Purpose', 'Workflow', 'AVS Conflict', 'On Site', 'Step', 'Creator'];
        foreach ($header as $v) {
            if (!array_key_exists($v, $recommendation_rules_content[0])) {
                return $this->sendError('File structure error', 'File is missing ' . $v . ' column.');
            }
        }
        foreach ($recommendation_rules_content as $k=>$v){
            if($v['Priority'] && !is_numeric($v['Priority'])){
                return $this->sendError('File structure error', 'line '.($k+2).':Priority column format error,Priority should be empty or an integer.');
            }
            if($v['Profile'] && (!is_string($v['Profile']) || is_numeric($v['Profile']))){
                return $this->sendError('File structure error', 'line '.($k+2).':Profile column format error,Profile should be empty or string.');
            }
            if(empty($v['Scope'])){
                return $this->sendError('File structure error', 'line '.($k+2).':Scope column format error,Scope should not be empty.');
            }

        }
        $params_create = [
            'rc_file_name' => $fileName,
            'rc_uploaded_by' => $params['rc_uploaded_by'],
            'rc_content' => $fileContent,
            'rc_file_size' => $fileSize,
            'rc_comment' => $params['rc_comment']
        ];
        try {
            $this->recommendationConfigService->create($params_create);
            $params_create['type'] = 'Sucess';
            Queue::setConnectionName('payment_api_eauditor_log_queue')->laterOn('payment_api_eauditor_log_queue', Carbon::now()->addMinute(3), new PaymentProfileUploadLogJob($params_create));
            return $this->sendResponse([
                'status' => 'success',
                'message' => 'Upload successful!'
            ]);
        } catch (\Exception $e) {
            $params_create['type'] = 'Error';
            $params_create['errorComment'] = $e->getMessage();
            Queue::setConnectionName('payment_api_eauditor_log_queue')->laterOn('payment_api_eauditor_log_queue', Carbon::now()->addMinute(3), new PaymentProfileUploadLogJob($params_create));
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/recommendation-configs",
     *     tags={"Payment API"},
     *     description="Get the top10 recommendation rule files",
     *     @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="number of recommendataion_config, default for 10",
     *          required=false,
     *          @OA\Schema(type="integer", example="10"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get the recommendation result list",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function fetch(Request $request)
    {
        try {
            $params = [
                'limit' => $request->get('limit', 10),
            ];
            $validator = validator($params, [
                'limit' => 'integer'
            ]);
            if($validator->fails()) {
                return $this->sendError('params error', $validator->errors()->first());
            }
            $res = $this->recommendationConfigService->fetch($params['limit']);
            foreach ($res as $k=>$v){
                $res[$k]['rc_file_size'] = get_file_size($v['rc_file_size']);
            }
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/recommendation-config/{rc_id}",
     *     tags={"Payment API"},
     *     description="Download the recommendation config files by id",
     *      @OA\Parameter(
     *          name="rc_id",
     *          in="path",
     *          description="the id for this file",
     *          required=true,
     *          @OA\Schema(type="integer", example="10000"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Download the recommendation config",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      )
     * )
     */
    public function download(Request $request){
        $params    = [
            'rc_id' => $request->route('rc_id')
        ];
        $validator = validator($params, [
            'rc_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        try {
            $params = $validator->validated();
            $res    = $this->recommendationConfigService->fetchByRcId($params['rc_id']);
            return $this->sendResponse($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

}
