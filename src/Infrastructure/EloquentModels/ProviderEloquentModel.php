<?php

namespace Src\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'payment_service_providers';
    protected $primaryKey = 'psp_id';

    protected $fillable = [
        'psp_id',
        'psp_code',
        'psp_name',
        'psp_tech_creation',
        'psp_tech_modification',
        'psp_tech_deleted',
    ];
}
