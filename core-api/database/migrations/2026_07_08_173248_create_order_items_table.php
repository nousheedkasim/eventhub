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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Automatically cleans up items if an order is wiped
            $table->foreignId('ticket_type_id')->constrained()->onDelete('restrict'); // Prevents deleting a ticket type if purchase history exists
            $table->integer('quantity');
            $table->integer('price_per_item_cents'); // Snapshots the price at the exact millisecond of purchase for financial records
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};