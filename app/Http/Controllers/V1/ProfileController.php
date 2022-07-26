<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use App\Jobs\PaymentEauditorLogJob;
use App\Services\ProfileService;

class ProfileController extends BaseController
{
    protected $profileService;
    protected $profileActionName = 'ProfilingFileProcessing';

    public function __construct(
        ProfileService $profileService
    ) {
        $this->profileService = $profileService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/profile",
     *     tags={"Profile"},
     *     description="Upload profiles for applications. duplicated upload will be ignored",
     *      @OA\Parameter(
     *          name="profiles",
     *          in="query",
     *          description="csv file containing profiles for multiple applications",
     *          required=true,
     *          @OA\Schema(type="file"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="all the profile uploaded",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function upload(Request $request) {
        $file = $request->file('profiles');

        $params = [
            'profiles' => $file,
        ];
        $validator = validator($params, [
            'profiles'   => 'required|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return $this->sendError('params error', $validator->errors()->first());
        }

        if ($file->isValid()) {
            $extension = $file->getClientOriginalExtension();
            if ($extension !== 'csv') {
                return $this->sendError('file format error', 'Please upload a file in CSV format.');
            }
            $realPath = $file->getRealPath();

            try{
                $profiles_content = csv_to_array($realPath, ',');
            } catch (\Exception $e) {
                return $this->sendError('file error', 'Please upload the correct file');
            }

            $header = ['TLS ID Number', 'age', 'gender', 'travel_purpose', 'visa_type', 'country', 'city', 'Profile'];
            foreach ($header as $v) {
                if (!array_key_exists($v, $profiles_content[0])) {
                    return $this->sendError('File structure error', 'File is missing ' . $v . ' column.');
                }
            }

            foreach ($profiles_content as $k=>$v){
                if(empty($v['TLS ID Number'])) {
                    return $this->sendError('File structure error', 'line '.($k+2).':TLS ID Number column format error, TLS ID Number should not be empty.');
                }
                if(empty($v['Profile'])) {
                    return $this->sendError('File structure error', 'line '.($k+2).':Profile column format error, Profile should not be empty.');
                }
            }

            $log_content = [
                'action_name' => $this->profileActionName,
                'user_name' => 'tlsinsight',
                'queue_type' => 'profile_process_log'
            ];

            try {
                $this->profileService->upload($profiles_content);
                $log_content['type'] = 'Success';
                dispatch(new PaymentEauditorLogJob($log_content))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
                return $this->sendResponse([
                    'status' => 'success',
                    'message' => 'Upload successful!'
                ]);
            } catch (\Exception $e) {
                $log_content['type']         = 'Error';
                $log_content['errorComment'] = $e->getMessage();
                dispatch(new PaymentEauditorLogJob($log_content))->onConnection('payment_api_eauditor_log_queue')->onQueue('payment_api_eauditor_log_queue');
                return $this->sendError('unknown_error', $e->getMessage());
            }
        } else {
            return $this->sendError('Upload failed', 'Upload failed, please try again.');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/application-with-profile/{profile}",
     *     tags={"Payment API"},
     *     description="get all applicant ids with profile name",
     *     @OA\Parameter(
     *          name="profile",
     *          in="path",
     *          description="the profile name",
     *          required=true,
     *          @OA\Schema(type="string", example="UK-PL"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="all application ids for this profile",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="some error happens for ths API"
     *      ),
     * )
     */
    public function fetchApplications(Request $request)
    {
        $profile  = urldecode($request->route('profile'));
        if (empty($profile)) {
            return $this->sendError('miss_fields', 'missing profile');
        }
        try {
            $result = $this->profileService->fetchApplications($profile);
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/application-profile/{f_id}",
     *     tags={"Profile"},
     *     description="Get profile for one application",
     *     @OA\Parameter(
     *          name="f_id",
     *          in="path",
     *          description="application f_id",
     *          required=true,
     *          @OA\Schema(type="integer", example="12345"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="profile found for this application",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="some error happens for ths API"
     *      ),
     * )
     */
    public function fetchProfile(Request $request)
    {
        $f_id  = $request->route('f_id');
        if (empty($f_id)) {
            return $this->sendError('miss_fields', 'missing f_id');
        }
        try {
            $result = $this->profileService->fetchProfile($f_id);
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }

    /**
     * @OA\Post (
     *     path="/api/v1/application-profiles",
     *     tags={"Profile"},
     *     description="Get profile for multiple applications",
     *     @OA\Parameter(
     *          name="f_ids",
     *          in="query",
     *          description="applications f_id",
     *          required=true,
     *          @OA\Schema(
     *              type="array",
     *              example="[10001, 10002]",
     *              @OA\Items(
     *                  type="integer"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="profile found for this application",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="some error happens for ths API"
     *      ),
     * )
     */
    public function fetchMultiProfiles(Request $request)
    {
        $f_ids = $request->input('f_ids');
        if (empty($f_ids)) {
            return $this->sendError('miss_fields', 'missing f_ids');
        }
        try {
            $result = $this->profileService->fetchMulti($f_ids);
            return $this->sendResponse($result);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
        }
    }
}
