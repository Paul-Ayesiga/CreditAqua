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
            $table->string('payment_reference', 100)->unique();
            $table->foreignId('lease_agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('payment_type', ['down_payment', 'installment', 'late_fee', 'security_deposit', 'early_termination', 'other']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('UGX');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'card']);
            $table->string('payment_channel', 100)->nullable(); // MTN, Airtel, Bank name, etc.
            $table->string('transaction_reference', 255)->nullable();
            $table->string('external_reference', 255)->nullable(); // Third-party payment ref
            $table->date('payment_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('processing_fee', 10, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2);
            $table->string('confirmation_code', 100)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('payment_reference');
            $table->index('lease_agreement_id');
            $table->index('client_id');
            $table->index('payment_date');
            $table->index('status');
            $table->index('payment_type');
            $table->index('payment_method');
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
