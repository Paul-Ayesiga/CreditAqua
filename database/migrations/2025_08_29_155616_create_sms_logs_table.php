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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 50);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed']);
            $table->string('provider', 100)->nullable();
            $table->string('external_id')->nullable();
            $table->decimal('cost', 8, 4)->nullable()->comment('Cost in currency units');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['recipient']);
            $table->index(['status']);
            $table->index(['sent_at']);
            $table->index(['provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
