<?php

namespace App\Http\Controllers\V1;

use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    protected $profileService;

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
     *          description="profiles for multiple applications",
     *          required=true,
     *          @OA\Schema(type="string", example="[{'f_id': 10001, 'profile': 'PREMIUM'}]"),
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
        $profiles = $request->input('profiles');
        if(empty($profiles)) {
            return $this->sendError('params_error', 'The param profiles is invalid');
        }

        try {
            $this->profileService->upload($profiles);
            return $this->sendResponse([
                'status' => 'success',
                'message' => 'profiles add to the queue!'
            ]);
        } catch (\Exception $e) {
            return $this->sendError('unknown_error', $e->getMessage());
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
        $profile  = $request->route('profile');
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
