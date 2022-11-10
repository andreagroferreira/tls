<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $table = 'refunds';
    protected $primaryKey = 'r_id';
    public $timestamps = false;

    protected $fillable = [
        'r_id',
        'r_issuer',
        'r_reason_type',
        'r_status',
        'r_appointment_date',
    ];
}
