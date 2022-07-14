<?php

namespace App\Services;

use App\Jobs\ProfileUploadJob;
use App\Repositories\ProfileRepository;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    protected $profileRepository;

    public function __construct(ProfileRepository   $profileRepository,
                                DbConnectionService $dbConnectionService)
    {
        $this->profileRepository = $profileRepository;
        $this->profileRepository->setConnection($dbConnectionService->getConnection());
    }

    public function upload($profiles)
    {
        $this->insert($profiles);
    }

    private function exists($p_xref_f_id, $profile)
    {
        return ($this->profileRepository->fetchLast(['p_xref_f_id' => $p_xref_f_id])->p_profile === $profile);
    }

    public function insert($data)
    {
        $insert_data = [];
        foreach ($data as $datum) {
            $attributes = [
                'p_xref_f_id' => $datum['TLS ID Number'],
                'p_profile' => $datum['Profile'],
            ];
            if ($this->exists($datum['TLS ID Number'], $datum['Profile'])) continue;
            $insert_data[] = $attributes;
        }
        return $this->profileRepository->createMany($insert_data);
    }

    public function fetchApplications($profile)
    {
        $result = $this->profileRepository->fetchApplications($profile);
        return $result->pluck('p_xref_f_id')->values();
    }

    public function fetchProfile($f_id)
    {
        return $this->profileRepository->getProfile($f_id);
    }

    public function fetchMulti($f_ids)
    {
        return $this->profileRepository->getMultiProfiles($f_ids);
    }
}
