<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('event_id');

            $table->string('type', 100);
            $table->decimal('price', 10, 2);

            $table->integer('inventory');
            $table->integer('sold_count')->default(0);

            $table->dateTime('available_from');
            $table->dateTime('available_until');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();

            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};

