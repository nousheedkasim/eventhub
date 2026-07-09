<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('ticket_type_id');

            $table->integer('qty');
            $table->decimal('price_at_purchase', 10, 2);

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('ticket_type_id')
                ->references('id')
                ->on('ticket_types')
                ->cascadeOnDelete();

            $table->index('order_id');
            $table->index('ticket_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

