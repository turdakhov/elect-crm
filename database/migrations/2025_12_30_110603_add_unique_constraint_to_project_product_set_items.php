<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->unique(['project_product_set_id', 'product_id'], 'unique_product_per_set');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->dropUnique('unique_product_per_set');
        });
    }
};
