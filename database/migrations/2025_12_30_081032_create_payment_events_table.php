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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider');
            $table->string('provider_ref');
            $table->string('event_type');
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestampTz('received_at');
            $table->timestamps();

            $table->index(['provider', 'provider_ref']);
            $table->unique(['provider', 'provider_ref', 'event_type', 'payload_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
