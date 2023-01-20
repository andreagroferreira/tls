<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'refunds';

    /**
     * @var string
     */
    protected $primaryKey = 'r_id';

    /**
     * @var array
     */
    protected $fillable = [
        'r_id',
        'r_issuer',
        'r_reason_type',
        'r_status'
    ];
}
