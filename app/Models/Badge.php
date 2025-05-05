<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;
    protected $table = 'badge';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'title',
        'icon',
        'description',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'user_badge', 'badge_id', 'user_id');
    }
}
