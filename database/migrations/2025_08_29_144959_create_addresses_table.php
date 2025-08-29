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
        Schema::create("addresses", function (Blueprint $table) {
            $table->id();
            $table->string("addressable_type");
            $table->unsignedBigInteger("addressable_id");
            $table
                ->enum("address_type", [
                    "primary",
                    "billing",
                    "shipping",
                    "delivery",
                    "warehouse",
                    "office",
                    "residence",
                ])
                ->default("primary");
            $table->string("label", 100)->nullable();
            $table->string("address_line_1", 500);
            $table->string("address_line_2", 500)->nullable();
            $table->string("city", 100);
            $table->string("state", 100);
            $table->string("postal_code", 20);
            $table->string("country", 100)->default("Uganda");
            $table->decimal("latitude", 10, 8)->nullable();
            $table->decimal("longitude", 11, 8)->nullable();
            $table->boolean("is_default")->default(false);
            $table->boolean("is_verified")->default(false);
            $table->timestamp("verified_at")->nullable();
            $table->timestamps();

            // Indexes
            $table->index(["addressable_type", "addressable_id"]);
            $table->index("address_type");
            $table->index("city");
            $table->index("state");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("addresses");
    }
};
