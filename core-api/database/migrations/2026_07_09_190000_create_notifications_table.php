<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipient_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('type', 100);
            $table->string('channel', 30);

            $table->enum('status', ['pending', 'sent', 'failed']);

            $table->integer('retry_count')->default(0);
            $table->text('payload');

            $table->dateTime('sent_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

