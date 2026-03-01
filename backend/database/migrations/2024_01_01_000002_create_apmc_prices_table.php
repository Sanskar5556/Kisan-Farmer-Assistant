<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ============================================================
// MIGRATION: Create APMC Prices Table
// Stores district-wise crop prices fetched from APMC markets
// ============================================================
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apmc_prices', function (Blueprint $table) {
            $table->id();
            $table->string('crop_name', 100);       // e.g. Wheat, Rice, Tomato
            $table->string('state', 100);            // e.g. Maharashtra
            $table->string('district', 100);         // e.g. Pune
            $table->string('market_name', 150);      // e.g. Pune APMC
            $table->decimal('min_price', 10, 2);     // Minimum price per quintal
            $table->decimal('max_price', 10, 2);     // Maximum price per quintal
            $table->decimal('modal_price', 10, 2);   // Modal (most common) price
            $table->date('price_date');              // Date of this price record
            $table->timestamps();

            // Indexes for fast filtering
            $table->index(['crop_name', 'district', 'price_date'], 'idx_crop_district_date');
            $table->index(['crop_name', 'state', 'price_date'], 'idx_crop_state_date');
            $table->index('price_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apmc_prices');
    }
};
