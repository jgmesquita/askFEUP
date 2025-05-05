<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerLike extends Model
{
    use HasFactory;
    protected $table = 'answer_like';

    protected $primaryKey = ['user_id', 'post_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'date',
    ];
    protected $casts = [
        'date' => 'datetime',
    ];

    public function answer()
    {
        return $this->belongsTo(AnswerPost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}