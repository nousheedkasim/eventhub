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
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('refund_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_events');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('order_events');
    }
};
