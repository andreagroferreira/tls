<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'refund_items';
    protected $primaryKey = 'ri_id';

    protected $fillable = [
        'ri_id',
        'ri_xref_rr_id',
        'ri_xref_ti_id',
        'ri_quantity',
        'ri_amount',
        'ri_reason_type',
        'ri_status',
        'ri_invoice_path',
        'ri_tech_deleted',
    ];
}
