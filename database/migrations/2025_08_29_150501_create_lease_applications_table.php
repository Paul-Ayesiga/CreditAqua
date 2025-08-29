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
        Schema::create("lease_applications", function (Blueprint $table) {
            $table->id();
            $table->string("application_number", 50)->unique();
            $table
                ->foreignId("client_id")
                ->constrained("clients")
                ->onDelete("cascade");
            $table
                ->foreignId("product_id")
                ->constrained("products")
                ->onDelete("cascade");
            $table->integer("quantity")->default(1);
            $table->integer("lease_duration_months");
            $table->decimal("monthly_payment", 15, 2);
            $table->decimal("total_lease_amount", 15, 2);
            $table->decimal("down_payment", 15, 2)->default(0.0);
            $table->decimal("security_deposit", 15, 2)->default(0.0);
            $table->decimal("application_fee", 15, 2)->default(0.0);
            $table->text("delivery_address");
            $table->date("preferred_delivery_date")->nullable();
            $table->text("purpose_of_lease");
            $table->text("collateral_description")->nullable();
            $table->boolean("guarantor_required")->default(false);
            $table->boolean("employment_verification")->default(false);
            $table->boolean("income_verification")->default(false);
            $table
                ->enum("status", [
                    "draft",
                    "submitted",
                    "under_review",
                    "approved",
                    "rejected",
                    "cancelled",
                ])
                ->default("draft");
            $table->decimal("risk_assessment_score", 5, 2)->nullable();
            $table->text("assessment_notes")->nullable();
            $table->timestamp("submitted_at")->nullable();
            $table->timestamp("reviewed_at")->nullable();
            $table
                ->foreignId("reviewed_by")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->text("approval_notes")->nullable();
            $table->text("rejection_reason")->nullable();
            $table->timestamps();

            // Indexes
            $table->index("application_number");
            $table->index("client_id");
            $table->index("status");
            $table->index("submitted_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("lease_applications");
    }
};
