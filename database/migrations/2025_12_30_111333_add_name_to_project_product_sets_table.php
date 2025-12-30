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
        Schema::table('project_product_sets', function (Blueprint $table) {
            $table->string('name')->after('project_id')->default('Смета');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_product_sets', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
