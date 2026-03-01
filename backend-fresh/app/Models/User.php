<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

// ============================================================
// USER MODEL
// Represents both Farmers and Admins
// Implements JWTSubject for JWT token authentication
// ============================================================
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'role', 'phone', 'state', 'district'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ---- JWT required methods ----
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Returns the user's ID
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role, // Embed role in token so frontend knows permissions
        ];
    }

    // ---- Relationships ----
    public function cropDiaries()
    {
        return $this->hasMany(CropDiary::class);
    }

    public function diseaseDetections()
    {
        return $this->hasMany(DiseaseDetection::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function cropPreferences()
    {
        return $this->hasMany(UserCropPreference::class);
    }

    // ---- Helper: Check role ----
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }
}
