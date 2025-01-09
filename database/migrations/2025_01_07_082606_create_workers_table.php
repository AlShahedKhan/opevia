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
        Schema::create('workers', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Reference to users

            $table->string('company_name'); // Company Name
            $table->string('email'); // Email Address
            $table->string('contact_number'); // Contact Number
            $table->text('service_location'); // Service Location
            $table->string('zip_code'); // ZIP Code
            $table->json('photos')->nullable(); // Photos (can store file paths)
            $table->string('service_type'); // Service Type
            $table->text('description')->nullable(); // Description
            $table->boolean('privacy_policy_agreement')->default(false); // Privacy Policy Agreement
            $table->timestamps(); // Created At & Updated At
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
