<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionLogs extends Model
{
    protected $table = 'transaction_logs';
    protected $primaryKey = 'tl_id';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tl_xref_transaction_id',
        'tl_content',
    ];
}
