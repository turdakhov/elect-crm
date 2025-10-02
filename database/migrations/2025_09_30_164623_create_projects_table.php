<?php

use App\Enums\ProjectStatusEnum;
use App\Models\Complex;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', array_column(ProjectStatusEnum::cases(), 'name'))->default(ProjectStatusEnum::Planned->name);
            $table->foreignId('client_id')->nullable()->constrained('users');
            $table->foreignId('foreman_id')->nullable()->constrained('users');
            $table->foreignId('designer_id')->nullable()->constrained('users');
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->decimal('square', 8, 2)->nullable();
            $table->unsignedBigInteger('price_per_sqm')->nullable();
            $table->unsignedBigInteger('price_per_sqm_rough')->nullable();
            $table->unsignedBigInteger('price_per_sqm_fine')->nullable();
            $table->unsignedBigInteger('total_price')->nullable();
            $table->foreignIdFor(Complex::class);
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
