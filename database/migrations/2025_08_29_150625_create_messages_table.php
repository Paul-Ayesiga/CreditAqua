<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("messages", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("sender_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table
                ->foreignId("recipient_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table->string("subject", 500)->nullable();
            $table->text("body");
            $table->enum("message_type", ["system", "user", "automated"]);
            $table
                ->enum("priority", ["low", "normal", "high", "urgent"])
                ->default("normal");
            $table
                ->enum("status", [
                    "draft",
                    "sent",
                    "delivered",
                    "read",
                    "failed",
                ])
                ->default("draft");
            $table->timestamp("read_at")->nullable();
            $table
                ->foreignId("parent_id")
                ->nullable()
                ->constrained("messages")
                ->onDelete("cascade");
            $table->timestamps();

            // Indexes
            $table->index("sender_id");
            $table->index("recipient_id");
            $table->index("status");
            $table->index("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("messages");
    }
};
