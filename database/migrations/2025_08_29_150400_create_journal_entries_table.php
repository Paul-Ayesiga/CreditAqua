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
        Schema::create("journal_entries", function (Blueprint $table) {
            $table->id();
            $table->string("entry_number", 50)->unique();
            $table->date("transaction_date");
            $table->string("reference_type")->nullable(); // Model class name
            $table->unsignedBigInteger("reference_id")->nullable(); // Model ID
            $table->text("description");
            $table->decimal("total_debit", 15, 2);
            $table->decimal("total_credit", 15, 2);
            $table->enum("status", ["draft", "posted", "reversed"])->default("draft");
            $table->foreignId("created_by")->constrained("users")->onDelete("restrict");
            $table->foreignId("posted_by")->nullable()->constrained("users")->onDelete("set null");
            $table->timestamp("posted_at")->nullable();
            $table->timestamps();

            // Indexes
            $table->index("entry_number");
            $table->index("transaction_date");
            $table->index(["reference_type", "reference_id"]);
            $table->index("status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("journal_entries");
    }
};
