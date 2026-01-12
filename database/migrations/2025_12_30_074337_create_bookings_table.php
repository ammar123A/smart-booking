<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('service_price_id')->constrained('service_prices')->cascadeOnDelete();

            $table->string('status');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->timestampTz('expires_at')->nullable();

            $table->unsignedInteger('total_amount');
            $table->char('currency', 3)->default('MYR');
            $table->timestamps();

            $table->index(['staff_id', 'starts_at']);
            $table->index(['customer_id', 'starts_at']);
            $table->index(['status']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            // DB-level protection against double booking per staff.
            // (Note: pending bookings remain blocking until marked expired/cancelled.)
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement("ALTER TABLE bookings
                ADD COLUMN booking_range tstzrange
                GENERATED ALWAYS AS (tstzrange(starts_at, ends_at, '[)')) STORED");

            DB::statement("ALTER TABLE bookings
                ADD CONSTRAINT bookings_no_overlap_per_staff
                EXCLUDE USING gist (
                    staff_id WITH =,
                    booking_range WITH &&
                )
                WHERE (status IN ('pending_payment', 'confirmed'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
