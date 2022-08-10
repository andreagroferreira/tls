<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentServiceProviders extends Model
{
    use HasFactory;

    protected $table = 'payment_service_providers';
    protected $primaryKey = 'psp_id';
    public $timestamps = false;

    protected $fillable = [
        'psp_id',
        'psp_code',
        'psp_name',
        'psp_tech_deleted',
    ];
}
