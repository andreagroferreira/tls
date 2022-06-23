<?php

namespace App\Jobs;

class ProfileUploadJob extends Job
{
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $profileService = app()->make('App\Services\ProfileService');
        $profileService->insert($this->data);
    }
}
