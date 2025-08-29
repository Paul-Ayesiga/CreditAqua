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
        Schema::create("guarantors", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("lease_application_id")
                ->constrained("lease_applications")
                ->onDelete("cascade");
            $table->string("name");
            $table->enum("id_type", [
                "national_id",
                "passport",
                "driving_license",
            ]);
            $table->string("id_number", 100);
            $table->string("phone", 50);
            $table->string("email")->nullable();
            $table->string("occupation", 100);
            $table->string("employer")->nullable();
            $table->decimal("monthly_income", 15, 2)->nullable();
            $table->string("relationship_to_client", 100);
            $table->decimal("guarantee_amount", 15, 2);
            $table->boolean("consent_given")->default(false);
            $table->timestamp("consent_date")->nullable();
            $table
                ->enum("verification_status", [
                    "pending",
                    "verified",
                    "rejected",
                ])
                ->default("pending");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("guarantors");
    }
};
