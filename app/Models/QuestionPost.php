<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuestionPost extends Model
{
    use HasFactory;
    protected $table = 'question_post';
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $fillable = [
        'text',
        'date',
        'is_edited',
        'user_id',
        'title',
        'tag_id',
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
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    public function answers()
    {
        return $this->hasMany(AnswerPost::class, 'question_id', 'id');
    }
    public function comments()
    {
        return $this->hasManyThrough(CommentPost::class, AnswerPost::class, 'question_id', 'answer_id')->orderBy('date', 'desc');
    }
    public function likes()
    {
        return $this->hasMany(QuestionLike::class, 'post_id', 'id');
    }

    public function getAnswer($page = 1)
    {
        $per_page = 5;
        $offset = ($page - 1) * $per_page;
    
        $answers = $this->answers()
            ->orderBy('is_correct', 'desc')
            ->orderBy('date', 'desc')
            ->skip($offset)
            ->take($per_page)
            ->get();
    
        return $answers;
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
    
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow_question', 'question_id', 'user_id');
    }

    public function followersCount() 
    {
        return $this->followers()->count();
    }

    public function isFollowedBy($userId) 
    {
        return $this->followers()->where('user_id', $userId)->exists();
    }
}
