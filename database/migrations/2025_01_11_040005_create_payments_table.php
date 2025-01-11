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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_intent_id')->unique();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('worker_id')->nullable(); // if associated with a worker
            $table->string('currency')->default('usd');
            $table->integer('amount'); // in cents
            $table->string('payment_method')->nullable();
            $table->string('description')->nullable();
            $table->string('customer')->nullable(); // Stripe customer ID or name
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('refund_date')->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamps();

            // Add foreign key constraints
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
