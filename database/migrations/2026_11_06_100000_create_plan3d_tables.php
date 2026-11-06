<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Связь с 3D-планом (модельер разводки).
 *
 * 3D-план — источник правды о том, СКОЛЬКО нужно и КАК нарезать; CRM — о том,
 * что выдано закупщику и что куплено. Сюда 3D-план только присылает отправки,
 * а принимает и выдаёт их человек кнопкой.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Отправка: одно нажатие кнопки «Отправить в CRM». Хранится целиком —
        // по ней видно, с какого состояния модели снята смета или версия журнала.
        Schema::create('plan_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('source_project');
            $table->string('source_name')->nullable();
            $table->string('fingerprint', 64);
            $table->json('journal');
            $table->json('needs');
            $table->json('warnings')->nullable();
            $table->timestamp('journal_accepted_at')->nullable();
            $table->foreignId('journal_accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Принятая версия кабельного журнала: что было в журнале после неё и
        // чем она отличалась от прежней — по разнице перепечатывают наклейки.
        Schema::create('cable_journal_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->foreignId('plan_sync_id')->nullable()->constrained()->nullOnDelete();
            $table->json('rows');
            $table->json('changes');
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'number']);
        });

        // Ключ 3D-плана → товар. 3D-план брендов не знает: «кабель ВВГнг 3×2.5»,
        // «прибор socket». Какой именно товар брать, решают здесь, один раз на
        // все проекты.
        Schema::create('plan_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('unit', 32);
            $table->string('section')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Смета из 3D-плана выдаётся закупщику и больше не меняется:
        // поправки приходят доп-сметами.
        Schema::table('project_product_sets', function (Blueprint $table) {
            $table->foreignId('plan_sync_id')->nullable()->after('comment')->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->after('plan_sync_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('project_product_set_items', function (Blueprint $table) {
            // Метры кабеля дробные, а доп-смета бывает с минусом: потребность
            // уменьшилась, и сумма смет должна это показать.
            $table->decimal('quantity', 12, 2)->change();
            // Ключ 3D-плана. У ручных строк пусто — и уникальность «товар один
            // раз в смете» для них остаётся прежней.
            $table->string('plan_key')->default('')->after('product_id');
            $table->unique(['project_product_set_id', 'product_id', 'plan_key'], 'unique_product_key_per_set');
        });

        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->dropUnique('unique_product_per_set');
        });
    }

    public function down(): void
    {
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->unique(['project_product_set_id', 'product_id'], 'unique_product_per_set');
        });
        Schema::table('project_product_set_items', function (Blueprint $table) {
            $table->dropUnique('unique_product_key_per_set');
            $table->dropColumn('plan_key');
            $table->integer('quantity')->change();
        });
        Schema::table('project_product_sets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropConstrainedForeignId('plan_sync_id');
        });
        Schema::dropIfExists('plan_keys');
        Schema::dropIfExists('cable_journal_versions');
        Schema::dropIfExists('plan_syncs');
    }
};
