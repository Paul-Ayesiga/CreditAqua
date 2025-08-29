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
        Schema::create("attachments", function (Blueprint $table) {
            $table->id();
            $table->string("attachable_type");
            $table->unsignedBigInteger("attachable_id");
            $table->enum("file_type", [
                "image",
                "document",
                "video",
                "audio",
                "other",
            ]);
            $table->string("title");
            $table->text("description")->nullable();
            $table->string("file_path", 500);
            $table->string("file_name");
            $table->string("original_name");
            $table->unsignedBigInteger("file_size");
            $table->string("mime_type", 100);
            $table
                ->foreignId("uploaded_by")
                ->constrained("users")
                ->onDelete("cascade");
            $table->boolean("is_public")->default(false);
            $table->timestamps();

            // Indexes
            $table->index(["attachable_type", "attachable_id"]);
            $table->index("file_type");
            $table->index("uploaded_by");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("attachments");
    }
};
