<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureVersionConfigurations extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'feature_version_configurations';

    /**
     * @var string
     */
    protected $primaryKey = 'fvc_id';

    /**
     * @var array
     */
    protected $fillable = [
        'fvc_id',
        'fvc_project',
        'fvc_country',
        'fvc_city',
        'fvc_xref_fv_id',
    ];
}
