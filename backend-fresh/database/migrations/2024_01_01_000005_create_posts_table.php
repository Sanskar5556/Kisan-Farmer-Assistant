<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ============================================================
// MIGRATION: Create Posts Table (Community Module)
// ============================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('body');
            $table->string('image_path', 255)->nullable();
            $table->string('crop_tag', 100)->nullable();  // e.g. "wheat", "rice"
            $table->unsignedInteger('likes_count')->default(0); // Cached count for performance
            $table->timestamps();

            $table->index('crop_tag');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
