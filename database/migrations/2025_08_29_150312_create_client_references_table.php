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
        Schema::create("client_references", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("client_id")
                ->constrained("clients")
                ->onDelete("cascade");
            $table->enum("reference_type", [
                "personal",
                "professional",
                "family",
                "neighbor",
                "business",
            ]);
            $table->string("name");
            $table->string("relationship", 100);
            $table->string("phone", 50);
            $table->string("email")->nullable();
            $table->text("address")->nullable();
            $table->string("occupation", 100)->nullable();
            $table->integer("years_known")->nullable();
            $table->boolean("is_contacted")->default(false);
            $table->text("contact_notes")->nullable();
            $table
                ->enum("verification_status", ["pending", "verified", "failed"])
                ->default("pending");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("client_references");
    }
};
