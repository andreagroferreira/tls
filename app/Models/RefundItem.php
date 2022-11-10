<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'refund_items';

    /**
     * @var string
     */
    protected $primaryKey = 'ri_id';

    /**
     * @var array
     */
    protected $fillable = [
        'ri_id',
        'ri_xref_r_id',
        'ri_xref_ti_id',
        'ri_quantity',
        'ri_amount',
        'ri_reason_type',
        'ri_status',
        'ri_invoice_path',
    ];
}
