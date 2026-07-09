<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('policy_applied', 100);

            $table->enum('status', [
                'pending',
                'approved',
                'completed',
                'failed',
            ]);

            $table->text('reason')->nullable();
            $table->dateTime('refunded_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

