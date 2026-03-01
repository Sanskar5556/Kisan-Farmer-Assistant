<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ============================================================
// APMC PRICE MODEL
// Represents a single day's crop price in a specific market
// ============================================================
class APMCPrice extends Model
{
    use HasFactory;

    protected $table = 'apmc_prices';

    protected $fillable = [
        'crop_name', 'state', 'district',
        'market_name', 'min_price', 'max_price',
        'modal_price', 'price_date'
    ];

    protected $casts = [
        'price_date'  => 'date',
        'min_price'   => 'float',
        'max_price'   => 'float',
        'modal_price' => 'float',
    ];

    // ---- Scopes for easy filtering ----

    // Usage: APMCPrice::byCrop('Wheat')->get()
    public function scopeByCrop($query, string $cropName)
    {
        return $query->where('crop_name', 'like', "%{$cropName}%");
    }

    // Usage: APMCPrice::byDistrict('Pune')->get()
    public function scopeByDistrict($query, string $district)
    {
        return $query->where('district', $district);
    }

    // Usage: APMCPrice::byState('Maharashtra')->get()
    public function scopeByState($query, string $state)
    {
        return $query->where('state', $state);
    }

    // Usage: APMCPrice::byYear(2024)->get()
    public function scopeByYear($query, int $year)
    {
        return $query->whereYear('price_date', $year);
    }
}
