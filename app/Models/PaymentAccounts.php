<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAccounts extends Model
{
    use HasFactory;

    protected $table = 'payment_accounts';
    protected $primaryKey = 'pa_id';
    public $timestamps = false;

    protected $fillable = [
        'pa_id',
        'pa_xref_psp_id',
        'pa_type',
        'pa_name',
        'pa_info',
        'pa_tech_deleted',
    ];
}
