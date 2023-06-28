<?php

namespace Src\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurationEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'payment_configurations';
    protected $primaryKey = 'pc_id';

    protected $fillable = [
        'pc_id',
        'pc_xref_pa_id',
        'pc_project',
        'pc_country',
        'pc_city',
        'pc_service',
        'pc_tech_creation',
        'pc_tech_modification',
        'pc_tech_deleted',
        'pc_is_active',
    ];
}
