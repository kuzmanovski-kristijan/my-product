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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->string('name')->after('category_id');
            $table->string('slug')->unique()->after('name');
            $table->string('brand')->nullable()->after('slug');
            $table->string('warranty')->nullable()->after('brand');
            $table->text('short_description')->nullable()->after('warranty');
            $table->longText('description')->nullable()->after('short_description');
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_featured')->default(false)->after('is_active');

            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropUnique(['slug']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'name',
                'brand',
                'warranty',
                'short_description',
                'description',
                'is_active',
                'is_featured',
                'slug',
            ]);
        });
    }
};
