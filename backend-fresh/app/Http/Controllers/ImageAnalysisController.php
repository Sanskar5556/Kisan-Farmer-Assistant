<?php

namespace App\Http\Controllers;

use App\Models\DiseaseDetection;
use App\Models\CropDiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

// ============================================================
// IMAGE ANALYSIS CONTROLLER
//
// Flow:
// 1. Farmer uploads image
// 2. Laravel saves it
// 3. Laravel sends it to Python FastAPI microservice
// 4. FastAPI returns: disease name + confidence + recommendation
// 5. Laravel saves result and returns to farmer
// ============================================================
class ImageAnalysisController extends Controller
{
    /**
     * Upload crop image and get AI disease prediction
     * POST /api/image/analyze
     * Body: image (file), crop_name (string), crop_diary_id (optional)
     */
    public function analyze(Request $request)
    {
        // Step 1: Validate the uploaded image
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,jpg,png,webp|max:10240', // Max 10MB
            'crop_name'     => 'nullable|string|max:100',
            'crop_diary_id' => 'nullable|exists:crop_diaries,id',
        ]);

        // Step 2: Save the image in Laravel's storage
        // The file gets saved in: storage/app/public/uploads/disease-images/
        $imagePath = $request->file('image')->store('uploads/disease-images', 'public');
        $fullPath  = storage_path("app/public/{$imagePath}");

        // Step 3: Send image to Python FastAPI microservice
        $aiServiceUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001');

        try {
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($fullPath), basename($fullPath))
                ->post("{$aiServiceUrl}/predict");

            if (!$response->successful()) {
                // If microservice is unreachable, return an error message
                return response()->json([
                    'success' => false,
                    'message' => 'AI service is unavailable. Please try again.',
                    'hint'    => 'Make sure ai-service/main.py is running: uvicorn main:app --reload --port 8001'
                ], 503);
            }

            $aiResult = $response->json();
            /*
             * Expected FastAPI response format:
             * {
             *   "disease": "Wheat Leaf Rust",
             *   "confidence": 94.5,
             *   "recommendation": "Apply Mancozeb at 2g/L water. Spray in evening."
             * }
             */

        } catch (\Exception $e) {
            // If FastAPI is down, return friendly error
            Storage::disk('public')->delete($imagePath); // Clean up uploaded file
            return response()->json([
                'success' => false,
                'message' => 'Could not connect to AI service: ' . $e->getMessage(),
            ], 500);
        }

        // Step 4: Save the detection result in database
        $detection = DiseaseDetection::create([
            'user_id'       => auth()->id(),
            'crop_diary_id' => $request->crop_diary_id,
            'image_path'    => $imagePath,
            'crop_name'     => $request->crop_name,
            'disease_name'  => $aiResult['disease'],
            'confidence'    => $aiResult['confidence'],
            'recommendation'=> $aiResult['recommendation'],
            'analyzed_at'   => Carbon::now(),
        ]);

        // Step 5: Return result to farmer
        return response()->json([
            'success'        => true,
            'message'        => 'Image analyzed successfully!',
            'image_url'      => asset("storage/{$imagePath}"),
            'disease'        => $aiResult['disease'],
            'confidence'     => $aiResult['confidence'],
            'recommendation' => $aiResult['recommendation'],
            'detection_id'   => $detection->id,
        ]);
    }

    /**
     * Get past disease detections for the logged-in farmer
     * GET /api/image/history
     */
    public function history()
    {
        $detections = DiseaseDetection::where('user_id', auth()->id())
            ->with('cropDiary:id,crop_name') // Include linked diary info
            ->orderBy('analyzed_at', 'desc')
            ->paginate(10); // 10 per page

        // Add full image URL to each record
        $detections->getCollection()->transform(function ($item) {
            $item->image_url = asset("storage/{$item->image_path}");
            return $item;
        });

        return response()->json([
            'success'    => true,
            'detections' => $detections,
        ]);
    }
}
