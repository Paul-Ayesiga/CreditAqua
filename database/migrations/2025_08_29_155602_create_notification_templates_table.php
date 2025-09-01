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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['email', 'sms', 'push', 'system']);
            $table->string('event_trigger')->comment('Event that triggers this template');
            $table->string('subject', 500)->nullable();
            $table->text('content');
            $table->json('variables')->nullable()->comment('Available template variables');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type']);
            $table->index(['event_trigger']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
