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
        Schema::create("lease_agreements", function (Blueprint $table) {
            $table->id();
            $table->string("agreement_number", 50)->unique();
            $table
                ->foreignId("lease_application_id")
                ->unique()
                ->constrained("lease_applications")
                ->onDelete("cascade");
            $table
                ->foreignId("client_id")
                ->constrained("clients")
                ->onDelete("cascade");
            $table
                ->foreignId("product_id")
                ->constrained("products")
                ->onDelete("cascade");
            $table
                ->foreignId("manufacturer_id")
                ->constrained("manufacturers")
                ->onDelete("cascade");
            $table->integer("quantity");
            $table->date("lease_start_date");
            $table->date("lease_end_date");
            $table->integer("lease_duration_months");
            $table->decimal("monthly_payment", 15, 2);
            $table->decimal("total_lease_amount", 15, 2);
            $table->decimal("down_payment", 15, 2);
            $table->decimal("security_deposit", 15, 2);
            $table->decimal("late_fee_percentage", 5, 2)->default(5.0);
            $table->integer("grace_period_days")->default(5);
            $table->decimal("early_termination_fee", 15, 2)->nullable();
            $table
                ->enum("maintenance_responsibility", [
                    "client",
                    "manufacturer",
                    "shared",
                ])
                ->default("client");
            $table->boolean("insurance_required")->default(false);
            $table->string("insurance_provider")->nullable();
            $table->text("terms_and_conditions");
            $table->text("special_terms")->nullable();
            $table
                ->enum("status", [
                    "draft",
                    "active",
                    "completed",
                    "terminated",
                    "breached",
                ])
                ->default("draft");
            $table->boolean("signed_by_client")->default(false);
            $table->boolean("signed_by_manufacturer")->default(false);
            $table->timestamp("client_signature_date")->nullable();
            $table->timestamp("manufacturer_signature_date")->nullable();
            $table->string("witness_name")->nullable();
            $table->timestamp("witness_signature_date")->nullable();
            $table->timestamps();

            // Indexes
            $table->index("agreement_number");
            $table->index("client_id");
            $table->index("status");
            $table->index("lease_start_date");
            $table->index("lease_end_date");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("lease_agreements");
    }
};
