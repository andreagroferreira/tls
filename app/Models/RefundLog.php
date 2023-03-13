<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundLog extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'refund_logs';

    /**
     * @var string
     */
    protected $primaryKey = 'rl_id';

    /**
     * @var array
     */
    protected $fillable = [
        'rl_id',
        'rl_xref_r_id',
        'rl_xref_ri_id',
        'rl_type',
        'rl_description',
        'rl_agent',
    ];
}
