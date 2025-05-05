<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReportReason extends Model
{
    use HasFactory;

    protected $table = 'post_report_reason';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'reason',
    ];

    public function reports()
    {
        return $this->hasMany(PostReport::class, 'reason_id');
    }
}