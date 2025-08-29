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
        Schema::create("payment_schedules", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("lease_agreement_id")
                ->constrained("lease_agreements")
                ->onDelete("cascade");
            $table->integer("installment_number");
            $table->date("due_date");
            $table->decimal("principal_amount", 15, 2);
            $table->decimal("interest_amount", 15, 2)->default(0.0);
            $table->decimal("total_amount", 15, 2);
            $table
                ->enum("status", [
                    "pending",
                    "paid",
                    "partial",
                    "overdue",
                    "waived",
                ])
                ->default("pending");
            $table->decimal("paid_amount", 15, 2)->default(0.0);
            $table->date("paid_date")->nullable();
            $table->decimal("late_fee", 15, 2)->default(0.0);
            $table->text("notes")->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(["lease_agreement_id", "installment_number"]);
            $table->index("due_date");
            $table->index("status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("payment_schedules");
    }
};
