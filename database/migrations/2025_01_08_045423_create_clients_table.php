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
        Schema::create('clients', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('user_id')->nullable(); // Add user_id column
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('worker_id')->nullable(); // Add worker_id column
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
            $table->string('full_name'); // Full Name
            $table->string('email'); // Email Address
            $table->string('contact_number'); // Contact Number
            $table->text('service_location'); // Service Location
            $table->string('zip_code'); // ZIP Code
            $table->json('photos')->nullable(); // Photo (can store file path)
            $table->dateTime('start_time'); // Start Time
            $table->dateTime('end_time'); // End Time
            $table->decimal('amount', 10, 2)->default(0.00); // Amount
            $table->text('description')->nullable(); // Description
            $table->boolean('privacy_policy_agreement')->default(false); // Privacy Policy Agreement
            $table->string('payment_intent_id')->nullable(); // Payment Intent ID
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
