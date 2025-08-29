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
        Schema::create("products", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("manufacturer_id")
                ->constrained("manufacturers")
                ->onDelete("cascade");
            $table
                ->foreignId("category_id")
                ->constrained("product_categories")
                ->onDelete("cascade");
            $table->string("name");
            $table->string("slug");
            $table->string("sku", 100)->unique();
            $table->text("description")->nullable();
            $table->json("specifications")->nullable();
            $table->decimal("unit_price", 15, 2);
            $table->decimal("lease_price", 15, 2);
            $table->string("currency", 3)->default("UGX");
            $table->integer("minimum_lease_duration")->default(12);
            $table->integer("maximum_lease_duration")->default(60);
            $table->integer("warranty_period")->default(12);
            $table->boolean("maintenance_required")->default(false);
            $table->boolean("installation_required")->default(true);
            $table->decimal("weight_kg", 8, 2)->nullable();
            $table->string("dimensions_cm", 50)->nullable();
            $table->string("color", 50)->nullable();
            $table->string("material", 100)->nullable();
            $table->integer("capacity_liters")->nullable();
            $table->json("images")->nullable();
            $table
                ->enum("status", [
                    "draft",
                    "active",
                    "inactive",
                    "discontinued",
                ])
                ->default("draft");
            $table->boolean("is_featured")->default(false);
            $table->timestamps();

            // Indexes
            $table->index("manufacturer_id");
            $table->index("category_id");
            $table->index("status");
            $table->index("sku");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
