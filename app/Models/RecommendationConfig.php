<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationConfig extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'recommendation_config';
    protected $primaryKey = 'rc_id';

    protected $fillable = [
        'rc_id',
        'rc_file_name',
        'rc_uploaded_by',
        'rc_content',
        'rc_file_size',
        'rc_comment',
    ];
}
