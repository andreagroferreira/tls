<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItems extends Model
{
    use HasFactory;

    protected $table = 'transaction_items';
    protected $primaryKey = 'ti_id';
    public $timestamps = false;

    protected $fillable = [
        'ti_xref_f_id',
        'ti_xref_transaction_id',
        'ti_fee_type',
        'ti_vat',
        'ti_amount',
    ];
}
