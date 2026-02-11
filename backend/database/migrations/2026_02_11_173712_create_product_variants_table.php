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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->string('color')->nullable();
            $table->string('material')->nullable();
            $table->string('dimensions')->nullable();

            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('sale_price_cents')->nullable();

            $table->unsignedInteger('stock_qty')->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['stock_qty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
