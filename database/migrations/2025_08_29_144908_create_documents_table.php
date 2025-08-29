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
        Schema::create("documents", function (Blueprint $table) {
            $table->id();
            $table->string("documentable_type");
            $table->unsignedBigInteger("documentable_id");
            $table->enum("document_type", [
                "kyc_id",
                "business_license",
                "tax_certificate",
                "bank_statement",
                "agreement",
                "invoice",
                "receipt",
                "guarantee",
                "other",
            ]);
            $table->string("title");
            $table->text("description")->nullable();
            $table->string("file_path", 500);
            $table->string("file_name");
            $table->unsignedBigInteger("file_size");
            $table->string("mime_type", 100);
            $table->string("document_number", 100)->nullable();
            $table->date("issue_date")->nullable();
            $table->date("expiry_date")->nullable();
            $table
                ->enum("status", ["pending", "approved", "rejected", "expired"])
                ->default("pending");
            $table
                ->foreignId("uploaded_by")
                ->constrained("users")
                ->onDelete("cascade");
            $table
                ->foreignId("verified_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->timestamp("verified_at")->nullable();
            $table->text("remarks")->nullable();
            $table->timestamps();

            // Indexes
            $table->index(["documentable_type", "documentable_id"]);
            $table->index("document_type");
            $table->index("status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("documents");
    }
};
