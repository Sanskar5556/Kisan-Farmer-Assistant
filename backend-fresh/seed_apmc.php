<?php
// Direct APMC data seeder — runs without artisan
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;
use App\Models\APMCPrice;

$crops  = ['Wheat', 'Rice', 'Tomato', 'Onion', 'Potato', 'Cotton', 'Soybean', 'Maize'];
$states = [
    'Maharashtra'    => ['Pune', 'Nashik', 'Aurangabad', 'Nagpur'],
    'Punjab'         => ['Amritsar', 'Ludhiana', 'Jalandhar', 'Patiala'],
    'Rajasthan'      => ['Jaipur', 'Jodhpur', 'Bikaner', 'Ajmer'],
    'Uttar Pradesh'  => ['Lucknow', 'Kanpur', 'Agra', 'Varanasi'],
    'Madhya Pradesh' => ['Bhopal', 'Indore', 'Jabalpur', 'Gwalior'],
    'Gujarat'        => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot'],
];

// Base prices per crop (₹/quintal) — realistic values
$basePrices = [
    'Wheat'   => 2200, 'Rice'    => 2800, 'Tomato' => 1800,
    'Onion'   => 1500, 'Potato'  => 1200, 'Cotton' => 6000,
    'Soybean' => 4200, 'Maize'   => 1700,
];

// Clear existing data
APMCPrice::truncate();
echo "Cleared existing APMC data.\n";

$inserted = 0;
// Generate 90 days of realistic price data
foreach ($states as $state => $districts) {
    foreach ($districts as $district) {
        foreach ($crops as $crop) {
            $base = $basePrices[$crop];
            for ($day = 90; $day >= 0; $day--) {
                // Simulate realistic price fluctuation
                $variation  = rand(-15, 15) / 100;  // ±15%
                $modal      = round($base * (1 + $variation));
                $min        = round($modal * (1 - rand(3,8)/100));
                $max        = round($modal * (1 + rand(3,10)/100));

                APMCPrice::create([
                    'crop_name'   => $crop,
                    'state'       => $state,
                    'district'    => $district,
                    'market_name' => "{$district} APMC",
                    'min_price'   => $min,
                    'max_price'   => $max,
                    'modal_price' => $modal,
                    'price_date'  => Carbon::now()->subDays($day)->toDateString(),
                ]);
                $inserted++;
            }
        }
    }
}

echo "✅ Inserted {$inserted} APMC price records across " . count($states) . " states, " . 
     array_sum(array_map('count', $states)) . " districts, " . count($crops) . " crops, 91 days.\n";
