<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    public const CREATED_AT = 'r_tech_creation';
    public const UPDATED_AT = 'r_tech_modification';

    protected $table = 'refunds';
    protected $primaryKey = 'r_id';

    protected $fillable = [
        'r_id',
        'r_issuer',
        'r_reason_type',
        'r_status',
        'r_tech_creation',
        'r_tech_modification',
    ];
}
