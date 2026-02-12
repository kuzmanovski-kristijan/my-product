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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->unique()->after('payment_status');
            $table->string('stripe_client_secret')->nullable()->after('stripe_payment_intent_id'); // optional (handy for debug)
            $table->string('currency', 10)->default(config('services.stripe.currency', 'mkd'))->after('stripe_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['stripe_payment_intent_id']);
            $table->dropColumn(['stripe_payment_intent_id', 'stripe_client_secret', 'currency']);
        });
    }
};
