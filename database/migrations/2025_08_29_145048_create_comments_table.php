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
        Schema::create("comments", function (Blueprint $table) {
            $table->id();
            $table->string("commentable_type");
            $table->unsignedBigInteger("commentable_id");
            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table
                ->foreignId("parent_id")
                ->nullable()
                ->constrained("comments")
                ->onDelete("cascade");
            $table->enum("comment_type", [
                "note",
                "status_change",
                "reminder",
                "follow_up",
                "issue",
                "resolution",
            ]);
            $table->string("title")->nullable();
            $table->text("content");
            $table
                ->enum("priority", ["low", "medium", "high", "urgent"])
                ->default("medium");
            $table->boolean("is_internal")->default(true);
            $table->boolean("is_pinned")->default(false);
            $table->timestamps();

            // Indexes
            $table->index(["commentable_type", "commentable_id"]);
            $table->index("user_id");
            $table->index("comment_type");
            $table->index("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("comments");
    }
};
