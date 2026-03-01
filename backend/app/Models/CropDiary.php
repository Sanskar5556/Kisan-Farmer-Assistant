<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

// ============================================================
// CROP DIARY MODEL
// Represents a farmer's crop entry with automatic age calculation
// ============================================================
class CropDiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'crop_name', 'sowing_date',
        'field_area', 'field_location', 'irrigation_type',
        'notes', 'status'
    ];

    protected $casts = [
        'sowing_date' => 'date',
        'field_area'  => 'float',
    ];

    // ---- Automatically add crop_age_days when model is retrieved ----
    protected $appends = ['crop_age_days', 'crop_stage'];

    // Accessor: calculates how many days since sowing
    public function getCropAgeDaysAttribute(): int
    {
        return Carbon::parse($this->sowing_date)->diffInDays(Carbon::today());
    }

    // Accessor: determines growth stage based on age
    public function getCropStageAttribute(): string
    {
        $age = $this->crop_age_days;

        if ($age <= 10)       return 'germination';
        if ($age <= 30)       return 'seedling';
        if ($age <= 60)       return 'vegetative';
        if ($age <= 90)       return 'flowering';
        if ($age <= 120)      return 'grain_filling';
        return 'maturity';
    }

    // ---- Relationships ----
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function diseaseDetections()
    {
        return $this->hasMany(DiseaseDetection::class);
    }
}
