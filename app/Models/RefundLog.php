<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundLog extends Model
{
    use HasFactory;

    public const CREATED_AT = 'rl_tech_creation';
    public const UPDATED_AT = 'rl_tech_modification';

    protected $table = 'refund_logs';
    protected $primaryKey = 'rl_id';

    protected $fillable = [
        'rl_id',
        'rl_xref_r_id',
        'rl_xref_ri_id',
        'rl_type',
        'rl_description',
        'rl_agent',
        'rl_tech_creation',
        'rl_tech_modification',
    ];
}
