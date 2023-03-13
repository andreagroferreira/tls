<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferTableTransactionItem extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'transfer_table_transaction_items';
    protected $primaryKey = 'tti_id';

    protected $fillable = [
        'ti_xref_f_id',
        'ti_xref_transaction_id',
        'ti_transaction_item',
        'ti_tech_creation',
        'ti_tech_modification',
    ];
}
