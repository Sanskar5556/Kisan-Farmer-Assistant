<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ============================================================
// DISEASE DETECTION MODEL
// Stores AI image analysis results from the FastAPI microservice
// ============================================================
class DiseaseDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'crop_diary_id', 'image_path',
        'disease_name', 'confidence', 'recommendation',
        'crop_name', 'analyzed_at'
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'confidence'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cropDiary()
    {
        return $this->belongsTo(CropDiary::class);
    }
}
