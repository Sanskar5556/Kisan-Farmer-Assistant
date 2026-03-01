<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ============================================================
// MIGRATION: Create Disease Detections Table
// Stores results from AI image analysis
// ============================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('crop_diary_id')->nullable()->constrained('crop_diaries')->onDelete('set null');
            $table->string('image_path', 255);       // Path to uploaded image
            $table->string('disease_name', 150);     // e.g. "Leaf Blight"
            $table->decimal('confidence', 5, 2);     // e.g. 94.75 (percent)
            $table->text('recommendation');          // Treatment advice from AI
            $table->string('crop_name', 100)->nullable(); // Which crop was analyzed
            $table->timestamp('analyzed_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_detections');
    }
};
