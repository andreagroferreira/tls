<?php

namespace App\Repositories;

use App\Models\Profiles as ProfileModel;
use Illuminate\Support\Facades\DB;

class ProfileRepository
{
    protected $profileModel;

    public function __construct(ProfileModel $profileModel)
    {
        $this->profileModel = $profileModel;
    }

    public function setConnection($connection)
    {
        $this->profileModel->setConnection($connection);
    }

    public function createMany($attributes)
    {
        return $this->profileModel->insert($attributes);
    }

    public function fetch($where, $field = ['*'])
    {
        return $this->profileModel->select($field)->where($where)->get();
    }

    public function fetchLast($where, $field = ['*'])
    {
        return $this->profileModel
            ->select($field)
            ->where($where)
            ->orderBy('p_tech_creation', 'DESC')
            ->first();
    }

    public function getProfile($f_id)
    {
        return $this->profileModel
            ->select('p_profile as profile')
            ->where('p_xref_f_id', $f_id)
            ->where('p_tech_deleted', false)
            ->orderBy('p_tech_creation', 'DESC')
            ->first();
    }

    public function fetchApplications($profile)
    {
        DB::setDefaultConnection($this->profileModel->getConnectionName());
        $sql = $this->profileModel
            ->select(['p_xref_f_id', 'p_profile'])
            ->distinct('p_xref_f_id')
            ->orderBy('p_xref_f_id')
            ->orderBy('p_tech_creation', 'DESC');
        return DB::table(DB::raw("({$sql->toSql()}) as sub"))
            ->where('p_profile', $profile)
            ->get();
    }

    public function getMultiProfiles($f_ids)
    {
        return $this->profileModel
            ->select(['p_xref_f_id as f_id', 'p_profile as profile'])
            ->whereIn('p_xref_f_id', $f_ids)
            ->where('p_tech_deleted', false)
            ->distinct('p_xref_f_id')
            ->orderBy('p_xref_f_id')
            ->orderBy('p_tech_creation', 'DESC')
            ->get();
    }
}
