<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentConfigurations extends Model
{
    use HasFactory;

    protected $table = 'payment_configurations';
    protected $primaryKey = 'pc_id';
    public $timestamps = false;

    protected $fillable = [
        'pc_id',
        'pc_xref_pa_id',
        'pc_project',
        'pc_country',
        'pc_city',
        'pc_service',
        'pc_tech_deleted',
        'pc_is_actived',
    ];
}
