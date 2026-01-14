<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->string('slug')->unique();
            $table->integer('min_points')->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0); // e.g., 5.00 for 5%
            $table->decimal('points_multiplier', 3, 2)->default(1.00); // e.g., 1.50 for 1.5x points
            $table->string('color')->nullable(); // Hex color for UI
            $table->string('icon')->nullable();
            $table->text('benefits')->nullable(); // JSON array of benefits
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
