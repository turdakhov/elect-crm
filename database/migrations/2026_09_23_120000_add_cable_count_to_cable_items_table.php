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
        Schema::table('cable_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('cable_count')->default(1)->after('cable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cable_items', function (Blueprint $table) {
            $table->dropColumn('cable_count');
        });
    }
};
