<?php

namespace App\Services;

use App\Jobs\ProfileUploadJob;
use App\Repositories\ProfileRepository;

class ProfileService
{
    protected $profileRepository;

    public function __construct(ProfileRepository   $profileRepository,
                                DbConnectionService $dbConnectionService)
    {
        $this->profileRepository = $profileRepository;
        $this->profileRepository->setConnection($dbConnectionService->getConnection());
    }

    public function upload($file)
    {
        $content = file_get_contents($file->getRealPath());
        $header  = null;
        foreach (explode("\r", $content) as $item) {
            if (strpos($item, ',') === false) continue;
            $row = str_getcsv($item, ',', null);
            if (!$header) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }

        $chunk_data = array_chunk($data, 1000);
        foreach ($chunk_data as $chunk_datum) {
            dispatch(new ProfileUploadJob($chunk_datum))->onConnection('tlscontact_profile_upload_queue')->onQueue('tlscontact_profile_upload_queue');
        }

        return true;
    }

    private function exists($attributes)
    {
        return !blank($this->profileRepository->fetch($attributes));
    }

    public function insert($data)
    {
        $insert_data = [];
        foreach ($data as $datum) {
            $attributes = [
                'p_xref_f_id' => $datum['f_id'],
                'p_profile' => $datum['profile'],
            ];
            if ($this->exists($attributes)) continue;
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
}
