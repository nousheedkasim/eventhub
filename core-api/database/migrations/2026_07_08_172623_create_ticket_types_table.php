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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            // Automatically creates the foreign key pointing to the events table
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'VIP Access', 'General Admission'
            $table->integer('price_cents'); // $49.99 is stored strictly as 4999 to prevent float rounding bugs
            $table->integer('total_capacity');
            $table->integer('remaining_inventory');
            $table->timestamps();

            // High-concurrency performance index
            $table->index(['event_id', 'remaining_inventory']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};