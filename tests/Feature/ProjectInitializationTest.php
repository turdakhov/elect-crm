<?php

declare(strict_types=1);

use App\Models\ExpenseType;
use App\Models\FileType;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('команда project:init создаёт минимальный набор данных', function () {
    // Очищаем базу
    User::query()->delete();
    FileType::query()->delete();
    ExpenseType::query()->delete();

    // Запускаем команду
    Artisan::call('project:init', ['--force' => true]);

    // Проверяем что создан администратор
    expect(User::where('email', 'admin@example.com')->exists())->toBeTrue();

    // Проверяем что созданы типы файлов
    expect(FileType::where('name', 'pdf')->exists())->toBeTrue();
    expect(FileType::where('name', 'xlsx')->exists())->toBeTrue();
    expect(FileType::where('name', 'картинка')->exists())->toBeTrue();

    // Проверяем что созданы типы расходов
    expect(ExpenseType::where('name', 'Зарплаты')->exists())->toBeTrue();
    expect(ExpenseType::where('name', 'Реклама')->exists())->toBeTrue();
});

test('InitialDataSeeder безопасен для повторного запуска', function () {
    // Первый запуск
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\InitialDataSeeder']);
    $firstCount = User::count();

    // Второй запуск
    Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\InitialDataSeeder']);
    $secondCount = User::count();

    // Количество не должно измениться
    expect($secondCount)->toBe($firstCount);
});

test('администратор создаётся с правильными данными', function () {
    Artisan::call('project:init', ['--force' => true]);

    $admin = User::where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Администратор')
        ->and($admin->role)->toBe('Admin')
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('создаются все необходимые типы файлов', function () {
    Artisan::call('project:init', ['--force' => true]);

    $expectedTypes = ['pdf', 'xlsx', 'картинка', 'word', 'чертеж'];

    foreach ($expectedTypes as $type) {
        expect(FileType::where('name', $type)->exists())->toBeTrue();
    }
});

test('создаются все необходимые типы расходов', function () {
    Artisan::call('project:init', ['--force' => true]);

    $expectedTypes = ['Зарплаты', 'Личные доли', 'Реклама', 'Инструменты', 'Рабочие расходы', 'Услуги', 'Материалы', 'Транспорт'];

    foreach ($expectedTypes as $type) {
        expect(ExpenseType::where('name', $type)->exists())->toBeTrue();
    }
});
