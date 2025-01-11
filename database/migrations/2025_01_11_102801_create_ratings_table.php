<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('worker_id');
        $table->unsignedBigInteger('client_id');
        $table->tinyInteger('rating')->unsigned();
        $table->timestamps();

        // Foreign keys pointing to the users table
        $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
        $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');

        // Optional: Unique constraint to prevent duplicate ratings from the same client to the same worker
        $table->unique(['worker_id', 'client_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
