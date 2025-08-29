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
        Schema::create("manufacturers", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_id")
                ->unique()
                ->constrained("users")
                ->onDelete("cascade");
            $table->string("company_name");
            $table->string("company_registration_number", 100)->unique();
            $table->enum("business_type", [
                "sole_proprietorship",
                "partnership",
                "limited_company",
                "corporation",
            ]);
            $table->string("industry", 100);
            $table->string("tax_identification_number", 100)->nullable();
            $table->string("website")->nullable();
            $table->integer("established_year")->nullable();
            $table
                ->enum("employee_count_range", [
                    "1-10",
                    "11-50",
                    "51-200",
                    "201-500",
                    "500+",
                ])
                ->nullable();
            $table->text("description")->nullable();
            $table->string("logo_path", 500)->nullable();
            $table
                ->enum("status", ["pending", "active", "suspended", "inactive"])
                ->default("pending");
            $table
                ->enum("verification_status", [
                    "unverified",
                    "under_review",
                    "verified",
                    "rejected",
                ])
                ->default("unverified");
            $table->timestamp("verified_at")->nullable();
            $table
                ->foreignId("verified_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->decimal("commission_rate", 5, 2)->default(0.0);
            $table->decimal("credit_limit", 15, 2)->default(0.0);
            $table->integer("payment_terms")->default(30);
            $table->timestamps();

            // Indexes
            $table->index("status");
            $table->index("verification_status");
            $table->index("company_name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("manufacturers");
    }
};
