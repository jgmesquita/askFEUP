<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notification';
    public $timestamps = false;

    protected $fillable = [
        'user_receive_id',
        'user_trigger_id',
        'type',
        'question_id',
        'answer_id',
        'comment_id',
        'badge_id',
        'date',
        'is_read',
    ];

    protected $attributes = [
        'is_read' => false,
    ];

    protected $casts = [
        'date' => 'datetime', 
    ];

    public function userReceive()
    {
        return $this->belongsTo(User::class, 'user_receive_id');
    }

    public function userTrigger()
    {
        return $this->belongsTo(User::class, 'user_trigger_id');
    }

    public function question()
    {
        return $this->belongsTo(QuestionPost::class, 'question_id');
    }

    public function answer()
    {
        return $this->belongsTo(AnswerPost::class, 'answer_id');
    }

    public function comment()
    {
        return $this->belongsTo(CommentPost::class, 'comment_id');
    }

    public function badge() 
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }

    public static function unreadCount($userId)
    {
        return self::where('user_receive_id', $userId)
            ->where('is_read', false)
            ->count();
    }   
}
