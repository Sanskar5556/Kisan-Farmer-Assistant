<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ============================================================
// COMMENT MODEL
// Supports nested replies via parent_id
// ============================================================
class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'parent_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Children = replies to this comment
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('user');
    }
}
