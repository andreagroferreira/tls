<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    public const CREATED_AT = 't_tech_creation';
    public const UPDATED_AT = 't_tech_modification';

    protected $table = 'transactions';
    protected $primaryKey = 't_id';
    protected $connection = 'payment_pgsql';

    protected $fillable = [
        't_id',
        't_xref_fg_id',
        't_transaction_id',
        't_client',
        't_issuer',
        't_gateway_transaction_id',
        't_gateway_transaction_reference',
        't_gateway',
        't_currency',
        't_status',
        't_redirect_url',
        't_onerror_url',
        't_reminder_url',
        't_callback_url',
        't_expiration',
        't_tech_creation',
        't_tech_modification',
        't_gateway_expiration',
        't_workflow',
        't_payment_method',
        't_service',
        't_invoice_storage',
        't_xref_pa_id',
        't_agent_name',
        't_appointment_date',
        't_appointment_time',
    ];
}
