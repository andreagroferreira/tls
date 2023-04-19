<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentServiceProviders extends Model
{
    use HasFactory;
    public const CREATED_AT = 'psp_tech_creation';
    public const UPDATED_AT = 'psp_tech_modification';

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
