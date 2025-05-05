<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    use HasFactory;
    protected $table = 'comment_like';

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

    public function comment()
    {
        return $this->belongsTo(CommentPost::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}