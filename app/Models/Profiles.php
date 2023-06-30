<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profiles extends Model
{
    use HasFactory;

    public const CREATED_AT = 'p_tech_creation';
    public const UPDATED_AT = 'p_tech_modification';

    protected $table = 'profiles';
    protected $primaryKey = 'p_id';

    protected $fillable = [
        'p_id',
        'p_xref_f_id',
        'p_profile',
        'p_tech_creation',
        'p_tech_modification',
        'p_tech_deleted',
    ];
}
