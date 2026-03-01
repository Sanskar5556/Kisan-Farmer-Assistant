<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCropPreference extends Model
{
    protected $fillable = [
        'user_id', 'crop_name', 'interaction_count', 'last_viewed_at'
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
