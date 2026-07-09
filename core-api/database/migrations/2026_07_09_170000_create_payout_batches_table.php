<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();

            $table->string('batch_reference', 100)->unique();

            $table->enum('status', [
                'pending',
                'running',
                'completed',
                'failed',
            ]);

            $table->integer('total_payouts');
            $table->integer('processed_count');
            $table->string('resume_token', 255)->nullable();

            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_batches');
    }
};

