<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');

            $table->enum('status', [
                'open',
                'investigating',
                'resolved',
                'rejected',
            ]);

            $table->text('reason')->nullable();
            $table->text('resolution')->nullable();

            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};

