<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureVersions extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'feature_versions';

    /**
     * @var string
     */
    protected $primaryKey = 'fv_id';

    /**
     * @var array
     */
    protected $fillable = [
        'fv_id',
        'fv_type',
        'fv_version',
    ];
}
