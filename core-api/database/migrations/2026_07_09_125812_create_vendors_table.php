<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {

            $table->id();

            // Basic vendor information
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            // Business information
            $table->text('address')->nullable();
            $table->string('website')->nullable();

            // KYC workflow
            $table->enum('kyc_status', [
                'pending',
                'verified',
                'rejected'
            ])->default('pending');

            $table->text('kyc_notes')->nullable();

            // Bank / payout information
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();

            // Vendor status
            $table->boolean('is_active')
                  ->default(true);

            $table->timestamps();

            $table->softDeletes();


            // Indexes
            $table->index('kyc_status');
            $table->index('is_active');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};