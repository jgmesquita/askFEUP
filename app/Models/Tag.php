<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    protected $table = 'tag';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'color',
        'color_text'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_follow_tag', 'tag_id', 'user_id');
    }
    
    public function questions()
    {
        return $this->hasMany(QuestionPost::class, 'tag_id', 'id');
    }
}