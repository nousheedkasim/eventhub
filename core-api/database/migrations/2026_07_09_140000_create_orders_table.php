<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('attendee_id');

            $table->enum('status', [
                'pending',
                'held',
                'paid',
                'cancelled',
                'expired',
                'refunded',
            ]);

            $table->decimal('total_amount', 10, 2);
            $table->dateTime('hold_expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

