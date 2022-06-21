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
     *     tags={"Payment API"},
     *     description="upload a profiles file to queue",
     *      @OA\Parameter(
     *          name="file",
     *          in="query",
     *          description="a file with csv format",
     *          required=true,
     *          @OA\Schema(type="string", example="10001,uk-PL\r10002,UK-SMS\r\n"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="profiles add to queue",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
     *      ),
     * )
     */
    public function upload(Request $request) {
        $file = $request->file('file');
        if(empty($file) or !$file->isValid()) {
            return $this->sendError('params_error', 'The param file is invalid');
        }

        try {
            $this->profileService->upload($file);
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
     *     description="get all applicant id with profile name",
     *     @OA\Parameter(
     *          name="profile",
     *          in="path",
     *          description="the profile name, UK-PL",
     *          required=false,
     *          @OA\Schema(type="string", example="UK-PL"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get application id success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
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
     *     tags={"Payment API"},
     *     description="get latest profile of applicant",
     *     @OA\Parameter(
     *          name="f_id",
     *          in="path",
     *          description="the applicant form id",
     *          required=false,
     *          @OA\Schema(type="integer", example="1"),
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="get application id success",
     *          @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Error: bad request"
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
}
