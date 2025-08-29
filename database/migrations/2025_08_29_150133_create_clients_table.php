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
        Schema::create("clients", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("user_id")
                ->unique()
                ->constrained("users")
                ->onDelete("cascade");
            $table->string("client_code", 50)->unique();
            $table->enum("client_type", [
                "individual",
                "business",
                "cooperative",
                "ngo",
            ]);
            $table
                ->enum("title", ["Mr", "Mrs", "Ms", "Dr", "Prof"])
                ->nullable();
            $table->string("first_name");
            $table->string("middle_name")->nullable();
            $table->string("last_name");
            $table->string("business_name")->nullable();
            $table->date("date_of_birth")->nullable();
            $table->enum("gender", ["male", "female", "other"])->nullable();
            $table
                ->enum("marital_status", [
                    "single",
                    "married",
                    "divorced",
                    "widowed",
                ])
                ->nullable();
            $table->enum("id_type", [
                "national_id",
                "passport",
                "driving_license",
            ]);
            $table->string("id_number", 100)->unique();
            $table->string("phone_primary", 50);
            $table->string("phone_secondary", 50)->nullable();
            $table->string("email_secondary")->nullable();
            $table->string("occupation", 100)->nullable();
            $table->string("employer")->nullable();
            $table->decimal("monthly_income", 15, 2)->nullable();
            $table->string("bank_name", 100)->nullable();
            $table->string("bank_account_number", 50)->nullable();
            $table->string("mobile_money_provider", 50)->nullable();
            $table->string("mobile_money_number", 50)->nullable();
            $table
                ->enum("preferred_payment_method", [
                    "bank_transfer",
                    "mobile_money",
                    "cash",
                    "cheque",
                ])
                ->default("mobile_money");
            $table
                ->enum("kyc_status", [
                    "incomplete",
                    "pending",
                    "approved",
                    "rejected",
                ])
                ->default("incomplete");
            $table->timestamp("kyc_completed_at")->nullable();
            $table->integer("credit_score")->nullable();
            $table->enum("risk_rating", ["low", "medium", "high"])->nullable();
            $table
                ->enum("status", [
                    "active",
                    "inactive",
                    "blacklisted",
                    "suspended",
                ])
                ->default("active");
            $table->enum("registration_source", [
                "online",
                "agent",
                "walk_in",
                "referral",
            ]);
            $table
                ->foreignId("referred_by")
                ->nullable()
                ->constrained("clients")
                ->onDelete("set null");
            $table->text("notes")->nullable();
            $table->timestamps();

            // Indexes
            $table->index("client_code");
            $table->index("id_number");
            $table->index("kyc_status");
            $table->index("status");
            $table->index("phone_primary");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("clients");
    }
};
