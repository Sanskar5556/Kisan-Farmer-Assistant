<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ============================================================
// MIGRATION: Create Crop Diaries Table
// Farmers log their crop planting records here
// ============================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Links to farmers
            $table->string('crop_name', 100);        // e.g. Wheat
            $table->date('sowing_date');             // When farmer sowed/planted
            $table->decimal('field_area', 8, 2);    // Area in acres
            $table->string('field_location', 255)->nullable(); // GPS or text location
            $table->enum('irrigation_type', ['rain-fed', 'canal', 'drip', 'sprinkler'])->default('rain-fed');
            $table->text('notes')->nullable();       // Farmer's own notes
            $table->enum('status', ['growing', 'harvested', 'failed'])->default('growing');
            $table->timestamps();

            $table->index('user_id');
            $table->index('crop_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_diaries');
    }
};
