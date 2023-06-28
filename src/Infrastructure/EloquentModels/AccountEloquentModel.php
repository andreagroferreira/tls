<?php

namespace Src\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'payment_accounts';
    protected $primaryKey = 'pa_id';

    protected $fillable = [
        'pa_id',
        'pa_xref_psp_id',
        'pa_type',
        'pa_name',
        'pa_info',
        'pa_tech_creation',
        'pa_tech_modification',
        'pa_tech_deleted',
    ];
}
