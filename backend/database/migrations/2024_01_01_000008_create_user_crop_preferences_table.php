<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ============================================================
// MIGRATION: Create User Crop Preferences Table
// Discovery Engine uses this to track what crops users view
// ============================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_crop_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('crop_name', 100);
            $table->unsignedInteger('interaction_count')->default(1); // Incremented on each view/action
            $table->timestamp('last_viewed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'crop_name']); // One record per crop per user
            $table->index(['crop_name', 'interaction_count']); // For trending queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_crop_preferences');
    }
};
