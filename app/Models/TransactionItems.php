<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItems extends Model
{
    use HasFactory;

    public const CREATED_AT = 'ti_tech_creation';
    public const UPDATED_AT = 'ti_tech_modification';

    protected $table = 'transaction_items';
    protected $primaryKey = 'ti_id';
    protected $connection = 'payment_pgsql';

    protected $fillable = [
        'ti_xref_f_id',
        'ti_xref_transaction_id',
        'ti_fee_type',
        'ti_vat',
        'ti_amount',
        'ti_quantity',
        'ti_price_rule',
        'ti_fee_name',
        'ti_label',
        'ti_tag',
        'ti_tech_creation',
        'ti_tech_modification',
    ];
}
