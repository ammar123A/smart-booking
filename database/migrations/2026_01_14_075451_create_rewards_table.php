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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('points_cost');
            $table->enum('type', ['discount_percentage', 'discount_fixed', 'free_service', 'voucher']);
            $table->integer('value'); // Percentage or fixed amount in cents
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('cascade'); // For free_service type
            $table->integer('max_redemptions')->nullable(); // Null = unlimited
            $table->integer('times_redeemed')->default(0);
            $table->foreignId('min_tier_id')->nullable()->constrained('loyalty_tiers')->onDelete('set null');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
