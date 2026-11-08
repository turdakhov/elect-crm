<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Смета из 3D-плана стала обычной, редактируемой.
 *
 * Пока смета была закрыта, «что выдано» читалось из её строк. Строки теперь
 * правят руками, и правка не должна возвращаться доп-сметой — поэтому
 * выданное запоминает ОТПРАВКА: в какую смету её выдали. Выданная потребность
 * части — это полная потребность её последней выданной отправки.
 *
 * Одна смета может собрать несколько отправок (все щиты вместе), отсюда ссылка
 * со стороны отправки, а не сметы.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_syncs', function (Blueprint $table) {
            $table->foreignId('product_set_id')->nullable()->after('warnings')
                ->constrained('project_product_sets')->nullOnDelete();
            $table->timestamp('issued_at')->nullable()->after('product_set_id');
        });

        // То, что уже выдано старым способом: смета знала свою отправку.
        foreach (DB::table('project_product_sets')->whereNotNull('plan_sync_id')->whereNull('deleted_at')->get() as $set) {
            DB::table('plan_syncs')->where('id', $set->plan_sync_id)
                ->update(['product_set_id' => $set->id, 'issued_at' => $set->created_at]);
        }

        // «Не закупаем»: строки ключа в смету не попадают (заглушки, которых нет в продаже).
        Schema::table('plan_keys', function (Blueprint $table) {
            $table->boolean('skip')->default(false)->after('product_id');
        });

        // Строка без товара — законна: смету выдают, не дожидаясь привязки всех
        // ключей. И удалённый товар больше не уносит строку из сметы.
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
        Schema::table('plan_keys', function (Blueprint $table) {
            $table->dropColumn('skip');
        });
        Schema::table('plan_syncs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_set_id');
            $table->dropColumn('issued_at');
        });
    }
};
