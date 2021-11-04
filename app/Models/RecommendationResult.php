<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationResult extends Model
{
    use HasFactory;

    protected $table = 'recommendation_result';
    protected $primaryKey = 'rr_id';
    public $timestamps = false;

    protected $fillable = [
        'rr_id',
        'rr_xref_f_id',
        'rr_agent',
        'rr_sku',
        'rr_result',
        'rr_comment',
    ];
}
