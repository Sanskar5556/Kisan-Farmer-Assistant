<?php

namespace App\Http\Controllers;

use App\Models\CropDiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

// ============================================================
// CROP DIARY CONTROLLER
//
// Farmers can:
// - Add crop entries (what they planted, when, where)
// - See crop age automatically calculated
// - Get daily advisory based on crop stage + weather
// ============================================================
class CropDiaryController extends Controller
{
    /**
     * List all diary entries for the logged-in farmer
     * GET /api/diary
     */
    public function index()
    {
        $diaries = auth()->user()
            ->cropDiaries()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'diaries' => $diaries,
        ]);
    }

    /**
     * Create a new crop diary entry
     * POST /api/diary
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_name'       => 'required|string|max:100',
            'sowing_date'     => 'required|date|before_or_equal:today',
            'field_area'      => 'required|numeric|min:0.01',
            'field_location'  => 'nullable|string|max:255',
            'irrigation_type' => 'nullable|in:rain-fed,canal,drip,sprinkler',
            'notes'           => 'nullable|string',
        ]);

        $diary = auth()->user()->cropDiaries()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Crop diary created successfully!',
            'diary'   => $diary,
        ], 201);
    }

    /**
     * Get a single diary entry with auto-calculated crop age
     * GET /api/diary/{id}
     */
    public function show($id)
    {
        $diary = auth()->user()->cropDiaries()->findOrFail($id);

        return response()->json([
            'success' => true,
            'diary'   => $diary, // crop_age_days and crop_stage are auto-appended by the model
        ]);
    }

    /**
     * Update a diary entry
     * PUT /api/diary/{id}
     */
    public function update(Request $request, $id)
    {
        $diary = auth()->user()->cropDiaries()->findOrFail($id);

        $validated = $request->validate([
            'crop_name'       => 'sometimes|string|max:100',
            'sowing_date'     => 'sometimes|date',
            'field_area'      => 'sometimes|numeric|min:0.01',
            'field_location'  => 'nullable|string|max:255',
            'irrigation_type' => 'nullable|in:rain-fed,canal,drip,sprinkler',
            'notes'           => 'nullable|string',
            'status'          => 'sometimes|in:growing,harvested,failed',
        ]);

        $diary->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'diary'   => $diary,
        ]);
    }

    /**
     * Delete a diary entry
     * DELETE /api/diary/{id}
     */
    public function destroy($id)
    {
        $diary = auth()->user()->cropDiaries()->findOrFail($id);
        $diary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Diary entry deleted'
        ]);
    }

    /**
     * Get daily advisory for a crop entry
     * GET /api/diary/{id}/advisory
     *
     * Combines: crop stage + live weather data → smart advice
     */
    public function advisory($id)
    {
        $diary = auth()->user()->cropDiaries()->findOrFail($id);
        $stage = $diary->crop_stage;  // From model accessor
        $age   = $diary->crop_age_days;

        // Fetch weather data for the farmer's district
        $weather = $this->fetchWeather(
            auth()->user()->district ?? 'Delhi'
        );

        // Generate advisory based on crop stage
        $advice = $this->generateAdvisory($diary->crop_name, $stage, $age, $weather);

        return response()->json([
            'success'  => true,
            'diary_id' => $id,
            'crop'     => $diary->crop_name,
            'stage'    => $stage,
            'age_days' => $age,
            'weather'  => $weather,
            'advisory' => $advice,
        ]);
    }

    /**
     * Fetch current weather from OpenWeatherMap API
     */
    private function fetchWeather(string $location): array
    {
        try {
            $response = Http::get(config('app.weather_api_url', env('WEATHER_API_URL')).'/weather', [
                'q'     => $location . ',IN',
                'appid' => env('WEATHER_API_KEY'),
                'units' => 'metric',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'temperature' => $data['main']['temp'] ?? 25,
                    'humidity'    => $data['main']['humidity'] ?? 60,
                    'description' => $data['weather'][0]['description'] ?? 'clear sky',
                    'wind_speed'  => $data['wind']['speed'] ?? 5,
                ];
            }
        } catch (\Exception $e) {
            // Fallback to dummy weather if API fails
        }

        return [
            'temperature' => 26,
            'humidity'    => 65,
            'description' => 'partly cloudy (demo)',
            'wind_speed'  => 3,
        ];
    }

    /**
     * Generate human-readable advisory based on crop stage and weather
     */
    private function generateAdvisory(string $crop, string $stage, int $ageDays, array $weather): array
    {
        $temp     = $weather['temperature'];
        $humidity = $weather['humidity'];
        $tips     = [];

        // Stage-based general tips
        $stageTips = [
            'germination'  => "Seeds are germinating. Maintain soil moisture. Avoid over-watering.",
            'seedling'     => "Young plants are growing. Thin out weak seedlings. First fertilizer application (N:P:K 20:20:20) recommended.",
            'vegetative'   => "Active leaf growth stage. Apply nitrogen fertilizer. Watch for pest activity.",
            'flowering'    => "Flowering stage is critical. Avoid pesticide spraying. Ensure pollination is not disturbed.",
            'grain_filling' => "Crop is filling grains. Reduce irrigation. Monitor for disease.",
            'maturity'     => "Crop is near harvest. Check grain moisture content. Prepare harvesting equipment.",
        ];

        $tips[] = $stageTips[$stage] ?? "Monitor your crop regularly.";

        // Weather-based tips
        if ($temp > 38) {
            $tips[] = "⚠️ High temperature alert ({$temp}°C). Irrigate in the evening to reduce heat stress.";
        }
        if ($temp < 10) {
            $tips[] = "⚠️ Cold weather alert ({$temp}°C). Cover seedlings or young plants if possible.";
        }
        if ($humidity > 80) {
            $tips[] = "⚠️ High humidity ({$humidity}%). Risk of fungal disease (blight, rust). Apply fungicide if needed.";
        }
        if ($humidity < 30) {
            $tips[] = "Low humidity. Increase irrigation frequency to prevent drought stress.";
        }

        // Crop-specific tips
        $cropTips = [
            'wheat'  => "Wheat responds well to urea top-dressing at {$ageDays} days.",
            'rice'   => "Check water level in paddy fields. Maintain 2-5 cm depth.",
            'tomato' => "Support the plants with stakes at this stage.",
            'onion'  => "Reduce irrigation 2 weeks before harvest for better storage.",
        ];

        $cropKey = strtolower($crop);
        if (isset($cropTips[$cropKey])) {
            $tips[] = $cropTips[$cropKey];
        }

        return [
            'stage_info'   => $stageTips[$stage] ?? '',
            'weather_tips' => $tips,
            'next_action'  => "Check crop again in 3 days. Day {$ageDays} of growth.",
        ];
    }
}
