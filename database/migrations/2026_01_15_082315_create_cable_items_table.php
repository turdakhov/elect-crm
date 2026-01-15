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
        Schema::create('cable_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('floor')->nullable();
            $table->string('room');
            $table->string('name');
            $table->text('comment')->nullable();
            $table->foreignId('cable_id')->constrained()->onDelete('cascade');
            $table->foreignId('pipe_id')->constrained()->onDelete('cascade');
            $table->decimal('cable_length', 10, 2)->default(0);
            $table->decimal('pipe_length', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cable_items');
    }
};
