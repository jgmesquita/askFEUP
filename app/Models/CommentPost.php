<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentPost extends Model
{
    use HasFactory;
    protected $table = 'comment_post';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'text',
        'date',
        'is_edited',
        'user_id',
        'answer_id',
        'nr_likes'
    ];
    protected $attributes = [
        'is_edited' => false,
        'nr_likes' => 0
    ];
    protected $casts = [
        'date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answer()
    {
        return $this->belongsTo(AnswerPost::class);
    }

    public function likes() {
        return $this->hasMany(CommentLike::class, 'post_id', 'id');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function toggleLike(User $user)
    {
        $like = $this->likes()->where('user_id', $user->id)->first(); 

        if ($like) {
            $this->likes()->where('user_id', $user->id)->delete();
        } else {
            $this->likes()->create(['user_id' => $user->id]);
        }
    }
}