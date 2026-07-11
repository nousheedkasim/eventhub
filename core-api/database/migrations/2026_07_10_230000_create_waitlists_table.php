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
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('priority_index')->default(0);
            $table->boolean('notified')->default(false);
            $table->timestamps();

            // Unique constraint to prevent duplicate waitlist entries
            $table->unique(['ticket_type_id', 'user_id']);
            
            // Indexes for efficient querying
            $table->index(['ticket_type_id', 'priority_index']);
            $table->index('notified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
