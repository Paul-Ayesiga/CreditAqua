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
        Schema::create("manufacturer_contacts", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("manufacturer_id")
                ->constrained("manufacturers")
                ->onDelete("cascade");
            $table->enum("contact_type", [
                "primary",
                "secondary",
                "finance",
                "technical",
                "sales",
            ]);
            $table->string("name");
            $table->string("title", 100)->nullable();
            $table->string("email")->nullable();
            $table->string("phone", 50)->nullable();
            $table->boolean("is_primary")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("manufacturer_contacts");
    }
};
