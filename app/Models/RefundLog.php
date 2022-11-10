<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundLog extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'refund_logs';
    protected $primaryKey = 'rl_id';

    protected $fillable = [
        'rl_id',
        'rl_xref_r_id',
        'rl_xref_ri_id',
        'rl_type',
        'rl_log',
        'rl_agent'
    ];
}
