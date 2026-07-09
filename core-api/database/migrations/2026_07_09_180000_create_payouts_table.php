<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payout_batch_id')
                ->nullable()
                ->constrained('payout_batches')
                ->nullOnDelete();

            $table->decimal('gross_amount', 10, 2);
            $table->decimal('commission', 10, 2);
            $table->decimal('amount', 10, 2);

            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
            ]);

            $table->string('transfer_reference', 255)->nullable();

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

