<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Часть отправки 3D-плана: «трассы» или «щит <id>».
 *
 * Щит собирают и закупают под него отдельно от трасс, и провод щита не должен
 * смешиваться с кабелем трасс. Поэтому у каждой части свои сметы, своя
 * разница с выданным и своя «последняя отправка».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_syncs', function (Blueprint $table) {
            $table->string('scope', 100)->default('трассы')->after('project_id');
            $table->string('scope_name')->nullable()->after('scope');
            $table->index(['project_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('plan_syncs', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'scope']);
            $table->dropColumn(['scope', 'scope_name']);
        });
    }
};
