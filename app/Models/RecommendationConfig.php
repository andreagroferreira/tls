<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationConfig extends Model
{
    use HasFactory;

    protected $table = 'recommendataion_config';
    protected $primaryKey = 'rc_id';
    public $timestamps = false;

    protected $fillable = [
        'rc_id',
        'rc_file_name',
        'rc_uploaded_by',
        'rc_content',
        'rc_file_size',
        'rc_comment'
    ];
}
