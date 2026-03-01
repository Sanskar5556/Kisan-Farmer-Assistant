<?php

namespace App\Http\Controllers;

use App\Models\APMCPrice;
use App\Models\UserCropPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ============================================================
// DISCOVERY ENGINE CONTROLLER
//
// Tracks what crops users are interested in,
// then suggests trending crops and personalized recommendations
// ============================================================
class DiscoveryController extends Controller
{
    /**
     * Get personalized crop suggestions for this user
     * GET /api/discovery/suggestions
     *
     * Logic: Show crops the user hasn't engaged with much
     * but are trending nationally
     */
    public function suggestions()
    {
        $userId = auth()->id();

        // Get crops the user has already interacted with
        $userCrops = UserCropPreference::where('user_id', $userId)
            ->pluck('crop_name')
            ->toArray();

        // Get nationally trending crops (most searched / listed in APMC)
        $trending = APMCPrice::select('crop_name', DB::raw('COUNT(*) as frequency'))
            ->groupBy('crop_name')
            ->orderBy('frequency', 'desc')
            ->limit(20)
            ->pluck('crop_name')
            ->toArray();

        // Filter out crops the user already knows about (show new suggestions)
        $suggestions = array_values(array_diff($trending, $userCrops));
        $suggestions = array_slice($suggestions, 0, 5); // Max 5 suggestions

        // Get recent price info for each suggested crop
        $enriched = [];
        foreach ($suggestions as $crop) {
            $latest = APMCPrice::byCrop($crop)->orderBy('price_date', 'desc')->first();
            $enriched[] = [
                'crop_name'    => $crop,
                'modal_price'  => $latest ? $latest->modal_price : null,
                'price_date'   => $latest ? $latest->price_date : null,
                'why_suggested'=> 'Trending in national markets',
            ];
        }

        return response()->json([
            'success'     => true,
            'suggestions' => $enriched,
        ]);
    }

    /**
     * Get nationally trending crops
     * GET /api/discovery/trending
     */
    public function trending()
    {
        // Crops with highest price activity in last 30 days
        $trending = APMCPrice::select(
                'crop_name',
                DB::raw('MAX(modal_price) as max_price'),
                DB::raw('MIN(modal_price) as min_price'),
                DB::raw('COUNT(*) as market_listings')
            )
            ->where('price_date', '>=', now()->subDays(30))
            ->groupBy('crop_name')
            ->orderBy('market_listings', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success'  => true,
            'trending' => $trending,
        ]);
    }

    /**
     * Track when a user views a crop (for personalization)
     * POST /api/discovery/track
     * Body: crop_name (string)
     *
     * Called by frontend whenever user searches/views a crop
     */
    public function track(Request $request)
    {
        $request->validate(['crop_name' => 'required|string|max:100']);

        $cropName = strtolower($request->crop_name);

        // Increment interaction_count (or create if first time)
        UserCropPreference::updateOrCreate(
            ['user_id' => auth()->id(), 'crop_name' => $cropName],
            [
                'interaction_count' => DB::raw('interaction_count + 1'),
                'last_viewed_at'    => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Tracked']);
    }

    /**
     * Get popup discovery data (to show on dashboard)
     * GET /api/discovery/popup
     *
     * Returns 1 crop suggestion the user hasn't seen yet
     */
    public function popup()
    {
        $userId = auth()->id();

        $userCrops = UserCropPreference::where('user_id', $userId)->pluck('crop_name')->toArray();

        $suggestion = APMCPrice::select('crop_name', DB::raw('AVG(modal_price) as avg_price'))
            ->whereNotIn('crop_name', array_map('ucfirst', $userCrops))
            ->where('price_date', '>=', now()->subDays(7))
            ->groupBy('crop_name')
            ->orderBy(DB::raw('COUNT(*)'), 'desc')
            ->first();

        return response()->json([
            'success'    => true,
            'show_popup' => (bool) $suggestion,
            'popup'      => $suggestion ? [
                'title'   => "🌟 Try Growing {$suggestion->crop_name}!",
                'message' => "Currently trading at ₹".round($suggestion->avg_price, 0)." per quintal. Trending in your region!",
                'crop'    => $suggestion->crop_name,
            ] : null,
        ]);
    }
}
