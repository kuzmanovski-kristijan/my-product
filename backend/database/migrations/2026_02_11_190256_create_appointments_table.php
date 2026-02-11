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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('status')->default('booked'); // booked/canceled/completed
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'starts_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
