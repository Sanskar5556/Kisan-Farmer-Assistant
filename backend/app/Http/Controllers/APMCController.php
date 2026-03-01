<?php

namespace App\Http\Controllers;

use App\Models\APMCPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

// ============================================================
// APMC MARKET INTELLIGENCE CONTROLLER
//
// Provides 4 types of price queries:
// 1. By district (specific local prices)
// 2. State average (average price across all districts in a state)
// 3. National average (average price across all India)
// 4. Year-wise trend (data grouped by month)
// ============================================================
class APMCController extends Controller
{
    /**
     * Get crop prices for a specific district
     * GET /api/apmc/district?crop=Wheat&district=Pune
     */
    public function byDistrict(Request $request)
    {
        $request->validate([
            'crop' => 'required|string',
            'district' => 'required|string',
        ]);

        $prices = APMCPrice::byCrop($request->crop)
            ->byDistrict($request->district)
            ->orderBy('price_date', 'desc')
            ->limit(30) // Last 30 records
            ->get(['crop_name', 'district', 'market_name', 'modal_price', 'min_price', 'max_price', 'price_date']);

        if ($prices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No price data found for {$request->crop} in {$request->district}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'crop' => $request->crop,
            'district' => $request->district,
            'prices' => $prices,
            'latest' => $prices->first(), // Most recent record
        ]);
    }

    /**
     * Get state-wide average crop price
     * GET /api/apmc/state?crop=Wheat&state=Maharashtra
     */
    public function stateAverage(Request $request)
    {
        $request->validate([
            'crop' => 'required|string',
            'state' => 'required|string',
        ]);

        // Group by district and calculate averages
        $data = APMCPrice::byCrop($request->crop)
            ->byState($request->state)
            ->select(
            'district',
            DB::raw('AVG(modal_price) as avg_price'),
            DB::raw('MIN(min_price) as lowest_price'),
            DB::raw('MAX(max_price) as highest_price'),
            DB::raw('MAX(price_date) as last_updated')
        )
            ->groupBy('district')
            ->orderBy('avg_price', 'desc')
            ->get();

        $stateAvg = $data->avg('avg_price');

        return response()->json([
            'success' => true,
            'crop' => $request->crop,
            'state' => $request->state,
            'state_average' => round($stateAvg, 2),
            'by_district' => $data,
        ]);
    }

    /**
     * Get national average crop price
     * GET /api/apmc/national?crop=Wheat
     */
    public function nationalAverage(Request $request)
    {
        $request->validate(['crop' => 'required|string']);

        $data = APMCPrice::byCrop($request->crop)
            ->select(
            'state',
            DB::raw('AVG(modal_price) as avg_price'),
            DB::raw('COUNT(*) as market_count'),
            DB::raw('MAX(price_date) as last_updated')
        )
            ->groupBy('state')
            ->orderBy('avg_price', 'desc')
            ->get();

        $nationalAvg = APMCPrice::byCrop($request->crop)->avg('modal_price');

        return response()->json([
            'success' => true,
            'crop' => $request->crop,
            'national_average' => round($nationalAvg, 2),
            'by_state' => $data,
        ]);
    }

    /**
     * Get year-wise monthly trend for a crop
     * GET /api/apmc/trend?crop=Wheat&state=Maharashtra&year=2024
     */
    public function yearTrend(Request $request)
    {
        $request->validate([
            'crop' => 'required|string',
            'state' => 'nullable|string',
            'year' => 'nullable|integer|min:2000',
        ]);

        $year = $request->year ?? Carbon::now()->year;

        $query = APMCPrice::byCrop($request->crop)->byYear($year);

        if ($request->state) {
            $query->byState($request->state);
        }

        $trend = $query
            ->select(
            DB::raw('cast(strftime("%Y", price_date) as integer) as year'),
            DB::raw('cast(strftime("%m", price_date) as integer) as month'),
            // SQLite doesn't have a native MONTHNAME function, so we map it out later
            DB::raw('AVG(modal_price) as avg_price'),
            DB::raw('MIN(min_price) as min_price'),
            DB::raw('MAX(max_price) as max_price')
        )
            ->groupBy(DB::raw('strftime("%Y", price_date)'), DB::raw('strftime("%m", price_date)'))
            ->orderBy(DB::raw('strftime("%m", price_date)'))
            ->get()
            ->map(function ($item) {
            $item->avg_price = round($item->avg_price, 2);
            $item->min_price = round($item->min_price, 2);
            $item->max_price = round($item->max_price, 2);
            // Calculate Month Name
            $item->month_name = Carbon::create()->month($item->month)->format('F');
            return $item;
        });

        return response()->json([
            'success' => true,
            'crop' => $request->crop,
            'state' => $request->state ?? 'All India',
            'year' => $year,
            'trend' => $trend,
        ]);
    }

    /**
     * Seed dummy APMC data (Admin only - for testing)
     * POST /api/apmc/seed-demo-data
     */
    public function seedDemoData()
    {
        $crops = [
            'Wheat', 'Rice', 'Tomato', 'Onion', 'Potato', 'Cotton', 'Soybean', 'Maize',
            'Sugarcane', 'Jute', 'Tea', 'Coffee', 'Rubber', 'Mango', 'Banana', 'Apple',
            'Grapes', 'Orange', 'Mustard', 'Groundnut', 'Sunflower', 'Chili', 'Turmeric',
            'Garlic', 'Ginger'
        ];
        $states = [
            'Maharashtra' => ['Pune', 'Nashik', 'Aurangabad', 'Nagpur'],
            'Punjab' => ['Amritsar', 'Ludhiana', 'Jalandhar', 'Patiala'],
            'Rajasthan' => ['Jaipur', 'Jodhpur', 'Bikaner', 'Ajmer'],
            'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Agra', 'Varanasi'],
        ];

        $inserted = 0;
        // Generate 90 days of data for each crop+district combo
        foreach ($states as $state => $districts) {
            foreach ($districts as $district) {
                foreach ($crops as $crop) {
                    for ($day = 90; $day >= 0; $day--) {
                        $basePrice = rand(800, 5000);
                        APMCPrice::create([
                            'crop_name' => $crop,
                            'state' => $state,
                            'district' => $district,
                            'market_name' => "{$district} APMC",
                            'min_price' => $basePrice - rand(50, 200),
                            'max_price' => $basePrice + rand(50, 300),
                            'modal_price' => $basePrice,
                            'price_date' => Carbon::now()->subDays($day)->toDateString(),
                        ]);
                        $inserted++;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Inserted {$inserted} demo price records"
        ]);
    }
}
