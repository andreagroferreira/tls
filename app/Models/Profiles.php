<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profiles extends Model
{
    use HasFactory;

    protected $table = 'profiles';
    protected $primaryKey = 'p_id';
    public $timestamps = false;

    protected $fillable = [
        'p_id',
        'p_xref_f_id',
        'p_profile',
        'p_tech_creation',
        'p_tech_modification',
        'p_tech_deleted',
    ];
}
