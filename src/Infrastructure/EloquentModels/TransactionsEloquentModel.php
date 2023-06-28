<?php

namespace Src\Infrastructure\EloquentModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionsEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'form_group_id',
        'transaction_id',
        'client',
        'issuer',
        'status',
        'currency',
        'expiration_date',
        'workflow',
        'url_actions',
        'is_test',
        'created_at',
        'updated_at',
    ];

}
