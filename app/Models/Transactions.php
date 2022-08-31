<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $primaryKey = 't_id';
    public $timestamps = false;

    protected $fillable = [
        't_id',
        't_xref_fg_id',
        't_transaction_id',
        't_client',
        't_issuer',
        't_gateway_transaction_id',
        't_gateway',
        't_currency',
        't_status',
        't_redirect_url',
        't_onerror_url',
        't_reminder_url',
        't_callback_url',
        't_expiration',
        't_gateway_expiration',
        't_workflow',
        't_payment_method',
        't_basket_type'
    ];
}
