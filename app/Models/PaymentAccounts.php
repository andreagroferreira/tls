<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAccounts extends Model
{
    use HasFactory;
    public const CREATED_AT = 'pa_tech_creation';
    public const UPDATED_AT = 'pa_tech_modification';

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
