<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferTableTransaction extends Model
{
    use HasFactory;

    public const CREATED_AT = 't_tech_creation';
    public const UPDATED_AT = 't_tech_modification';

    protected $table = 'transfer_table_transactions';
    protected $primaryKey = 'tt_id';

    protected $fillable = [
        't_xref_fg_id',
        't_transaction_id',
        't_client',
        't_issuer',
        't_gateway_transaction_id',
        't_gateway',
        't_currency',
        't_tech_creation',
        't_tech_modification',
        't_agent_name',
        't_gateway_transaction_reference',
        'f_visa_type',
        'f_visa_sub_type',
        'result_migration',
    ];
}
