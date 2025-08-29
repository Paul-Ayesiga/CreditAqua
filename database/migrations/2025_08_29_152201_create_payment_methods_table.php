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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('method_type', ['bank_account', 'mobile_money', 'card']);
            $table->string('provider', 100); // Bank name, MTN, Airtel, etc.
            $table->string('account_number', 100);
            $table->string('account_name', 255);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('client_id');
            $table->index('method_type');
            $table->index('is_default');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
