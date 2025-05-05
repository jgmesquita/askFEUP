<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReport extends Model
{
    use HasFactory;
    protected $table = 'post_report';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_type',
        'post_id',
        'reason_id',
        'date',
        'status',
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
        return $this->belongsTo(QuestionPost::class, 'post_id');
    }

    public function answer()
    {
        return $this->belongsTo(AnswerPost::class, 'post_id');
    }

    public function comment()
    {
        return $this->belongsTo(CommentPost::class, 'post_id');
    }

    public static function getReportedPosts()
    {
        return self::selectRaw('post_id, post_type, COUNT(*) as report_count')
            ->selectRaw("JSON_AGG(post_report.id) as report_ids")
            ->selectRaw("JSON_AGG(post_report_reasons.reason) as report_reasons")
            ->join('post_report_reason as post_report_reasons', 'post_report.reason_id', '=', 'post_report_reasons.id')
            ->where('post_report.status', '=', 'open')
            ->groupBy('post_id', 'post_type')
            ->orderBy('report_count', 'desc')
            ->get();
    }
    
    public function getPostDetailsAttribute()
    {
        switch ($this->post_type) {
            case 'question':
                return $this->question;
            case 'answer':
                return $this->answer;
            case 'comment':
                return $this->comment;
            default:
                return null;
        }
    }

    public function reason()
    {
        return $this->belongsTo(PostReportReason::class, 'reason_id');
    }
}
