<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory;
    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'tagname',
        'email',
        'password',
        'age',
        'country',
        'degree',
        'icon',
        'is_admin',
        'is_moderator',
        'is_banned',
        'is_deleted',
        'is_dark_mode'
    ];
    protected $attributes = [
        'is_banned' => false,
        'is_admin' => false,
        'is_moderator' => false,
        'is_deleted' => false,
        'is_dark_mode' => false,
    ];
    
    public function questions()
    {
        return $this->hasMany(QuestionPost::class);
    }
    public function answers() 
    {
        return $this->hasMany(AnswerPost::class);
    }

    public function comments()
    {
        return $this->hasMany(CommentPost::class, 'user_id', 'id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'user_follow_tag', 'user_id', 'tag_id');
    }

    public function badges() {
        return $this->belongsToMany(Badge::class, 'user_badge', 'user_id', 'badge_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_receive_id');
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getNotifications() 
    {
        return $this->notifications()
            ->orderBy('is_read', 'asc')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();
    }
    public function getMoreNotifications($page){
        $per_page = 5;
        $offset = ($page - 1) * $per_page;
        return $this->notifications()
        ->orderBy('is_read', 'asc')
        ->orderBy('date', 'desc')
        ->skip($offset)
        ->take($per_page)
        ->get();
    }

    public function unreadNotificationsCount()
    {
        return $this->notifications()->where('is_read', false)->count();
    }   
    
    public function followedQuestions()
    {
        return $this->belongsToMany(QuestionPost::class, 'user_follow_question', 'user_id', 'question_id');
    }

    public function followsTag(Tag $tag)
    {
       return $this->tags()->where('tag_id', $tag->id)->exists();
    } 

    public static function getTopUser()
    {
        return self::withCount('questions')
            ->withCount('answers')
            ->withCount('comments')
            ->selectRaw('
                ((SELECT COUNT(*) FROM question_post WHERE question_post.user_id = "user".id) +
                (SELECT COUNT(*) FROM answer_post WHERE answer_post.user_id = "user".id) +
                (SELECT COUNT(*) FROM comment_post WHERE comment_post.user_id = "user".id)) 
                AS posts_count'
            )
            ->orderByDesc('posts_count')
            ->first();
    }

    public function totalLikes()
    {
        $result = DB::selectOne('
            SELECT 
                (
                    (SELECT COALESCE(SUM(nr_likes), 0) FROM question_post WHERE user_id = ?) +
                    (SELECT COALESCE(SUM(nr_likes), 0) FROM answer_post WHERE user_id = ?) +
                    (SELECT COALESCE(SUM(nr_likes), 0) FROM comment_post WHERE user_id = ?)
                ) AS total_likes
        ', [$this->id, $this->id, $this->id]);

        return $result->total_likes;
    }
}
