<?php

namespace App\Http\Controllers\V1;

use App\Services\RecommendationConfigService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class RecommendationConfigController extends BaseController
{
    private $recommendationConfigService;
    private $recommendationConfigLimit = 10;

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
     *          name="recommendation_rules",
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
            'rc_file_name' => $request->allfiles(),
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
        $file = $params['rc_file_name']['files'];
        if ($file->isValid()) {
            $fileName = $file -> getClientOriginalName();
            $entension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            if ($entension != 'csv') {
                return $this->sendError('file format error', 'Please upload a file in CSV format.');
            }
            $realPath = $file->getRealPath();

            try{
                $recommendation_rules_content = csv_to_array($realPath, ',');
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
                'rc_content' => get_csv_content($realPath),
                'rc_file_size' => $fileSize,
                'rc_comment' => $params['rc_comment']
            ];
            try {
                $this->recommendationConfigService->create($params_create);
                return $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Upload successful!'
                ]);
            } catch (\Exception $e) {
                return $this->sendError('unknown_error', $e->getMessage());
            }
        } else {
            return $this->sendError('Upload failed', 'Upload failed, please try again.');
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
     *          description="recommendation result list limit",
     *          required=false,
     *          @OA\Schema(type="string", example="10"),
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
        $limit = $request->get('limit') ?? $this->recommendationConfigLimit;
        try {
            $res = $this->recommendationConfigService->fetch($limit);
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
            export_csv($res);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

}
