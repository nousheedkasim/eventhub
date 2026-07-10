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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('gateway', 50);
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 255)->unique();
            $table->string('payment_reference', 255)->unique();
            $table->dateTime('event_date');
            $table->decimal('refunded_amount', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
