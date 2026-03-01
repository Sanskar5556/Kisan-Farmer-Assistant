<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\APMCController;
use App\Http\Controllers\CropDiaryController;
use App\Http\Controllers\ImageAnalysisController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\DiscoveryController;

/*
|--------------------------------------------------------------------------
| API Routes for Kisan Smart Assistant
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api
| Example: Route::get('/apmc/...) => accessible at http://localhost:8000/api/apmc/...
|
| Middleware:
| - 'jwt'       => Requires a valid JWT token (see JwtMiddleware.php)
| - 'role:admin' => Only admins can access this route
|
*/

// =============================================
// AUTH ROUTES — No token required
// =============================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);  // POST /api/auth/register
    Route::post('/login',    [AuthController::class, 'login']);     // POST /api/auth/login

    // Protected auth routes (need token)
    Route::middleware('jwt')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);  // POST /api/auth/logout
        Route::post('/refresh', [AuthController::class, 'refresh']); // POST /api/auth/refresh
        Route::get('/me',       [AuthController::class, 'me']);      // GET  /api/auth/me
    });
});

// =============================================
// ALL PROTECTED ROUTES — Require JWT token
// =============================================
Route::middleware('jwt')->group(function () {

    // ---- APMC Market Intelligence ----
    Route::prefix('apmc')->group(function () {
        Route::get('/district', [APMCController::class, 'byDistrict']);      // GET /api/apmc/district?crop=Wheat&district=Pune
        Route::get('/state',    [APMCController::class, 'stateAverage']);    // GET /api/apmc/state?crop=Wheat&state=Maharashtra
        Route::get('/national', [APMCController::class, 'nationalAverage']); // GET /api/apmc/national?crop=Wheat
        Route::get('/trend',    [APMCController::class, 'yearTrend']);       // GET /api/apmc/trend?crop=Wheat&year=2024

        // Admin only: seed demo data
        Route::post('/seed-demo-data', [APMCController::class, 'seedDemoData'])->middleware('role:admin');
    });

    // ---- Farmer Crop Diary ----
    Route::prefix('diary')->group(function () {
        Route::get('/',              [CropDiaryController::class, 'index']);    // GET    /api/diary
        Route::post('/',             [CropDiaryController::class, 'store']);    // POST   /api/diary
        Route::get('/{id}',          [CropDiaryController::class, 'show']);    // GET    /api/diary/1
        Route::put('/{id}',          [CropDiaryController::class, 'update']);  // PUT    /api/diary/1
        Route::delete('/{id}',       [CropDiaryController::class, 'destroy']); // DELETE /api/diary/1
        Route::get('/{id}/advisory', [CropDiaryController::class, 'advisory']); // GET   /api/diary/1/advisory
    });

    // ---- AI Image Analysis ----
    Route::prefix('image')->group(function () {
        Route::post('/analyze', [ImageAnalysisController::class, 'analyze']); // POST /api/image/analyze
        Route::get('/history',  [ImageAnalysisController::class, 'history']); // GET  /api/image/history
    });

    // ---- Community ----
    Route::prefix('community')->group(function () {
        Route::get('/posts',                        [CommunityController::class, 'index']);        // GET    /api/community/posts
        Route::post('/posts',                       [CommunityController::class, 'store']);        // POST   /api/community/posts
        Route::post('/posts/{id}/like',             [CommunityController::class, 'toggleLike']);   // POST   /api/community/posts/1/like
        Route::post('/posts/{id}/comments',         [CommunityController::class, 'addComment']);   // POST   /api/community/posts/1/comments
        Route::delete('/posts/{id}',                [CommunityController::class, 'destroy']);      // DELETE /api/community/posts/1
    });

    // ---- Discovery Engine ----
    Route::prefix('discovery')->group(function () {
        Route::get('/suggestions', [DiscoveryController::class, 'suggestions']); // GET  /api/discovery/suggestions
        Route::get('/trending',    [DiscoveryController::class, 'trending']);    // GET  /api/discovery/trending
        Route::post('/track',      [DiscoveryController::class, 'track']);       // POST /api/discovery/track
        Route::get('/popup',       [DiscoveryController::class, 'popup']);       // GET  /api/discovery/popup
    });

});
