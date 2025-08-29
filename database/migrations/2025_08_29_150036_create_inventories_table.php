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
        Schema::create("inventories", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("product_id")
                ->constrained("products")
                ->onDelete("cascade");
            $table->string("location")->nullable();
            $table->integer("quantity_available")->default(0);
            $table->integer("quantity_reserved")->default(0);
            $table->integer("quantity_leased")->default(0);
            $table->integer("reorder_level")->default(0);
            $table->timestamp("last_restocked_at")->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();

            // Indexes
            $table->index("product_id");
            $table->index("quantity_available");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("inventories");
    }
};
