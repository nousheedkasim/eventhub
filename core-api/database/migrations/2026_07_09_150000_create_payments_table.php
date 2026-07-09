<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('gateway', 50);

            $table->enum('status', [
                'pending',
                'authorized',
                'paid',
                'failed',
                'refunded',
            ]);

            $table->string('idempotency_key', 255)->unique();

            $table->string('gateway_reference', 255)->nullable();

            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

