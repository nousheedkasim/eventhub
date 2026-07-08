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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // Restrict deletion if financial records exist
            $table->string('order_reference')->unique(); // Unique identifier (e.g., EVT-2026-ABCDE)
            
            // Financial Breakdown (all stored in cents to prevent decimal math bugs)
            $table->integer('total_amount_cents');
            $table->integer('platform_fee_cents');
            $table->integer('vendor_payout_cents');
            
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};