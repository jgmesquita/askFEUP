<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AnswerPost extends Model
{
    use HasFactory;
    protected $table = 'answer_post';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'text',
        'date',
        'is_edited',
        'is_correct',
        'user_id',
        'question_id',
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
    
    public function question()
    {
        return $this->belongsTo(QuestionPost::class);
    }

    public function comments()
    {
        return $this->hasMany(CommentPost::class, 'answer_id')->orderBy('date', 'desc');
    }

    public function getCom($page = 1){
        $per_page = 3;
        $offset = ($page - 1) * $per_page;
        $comments = $this->comments()
            ->selectRaw(
                'comment_post.*, EXISTS(
                    SELECT 1 
                    FROM comment_like 
                    WHERE comment_like.post_id = comment_post.id 
                    AND comment_like.user_id = ?
                ) AS is_liked',
                [Auth::id()] 
            )
            ->skip($offset)
            ->take($per_page)
            ->orderBy('date', 'desc')
            ->get();

        return $comments;
    }

    public function likes() {
        return $this->hasMany(AnswerLike::class, 'post_id', 'id');
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
    public function commentsCount()
    {
        return $this->comments()->count();
    }
}