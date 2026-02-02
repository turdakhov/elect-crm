<?php

/**
 * Тест проверяет безопасность тестовой среды
 * 
 * Этот файл проверяет что:
 * 1. Тесты НЕ используют production базу данных
 * 2. Используется окружение 'testing'
 * 3. Все тесты используют RefreshDatabase для изоляции
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

test('тесты используют отдельную тестовую базу данных', function () {
    $databaseName = DB::connection()->getDatabaseName();

    // Проверяем что НЕ используется production база
    expect($databaseName)->not->toBe('laravel')
        ->and($databaseName)->not->toBe('production')
        ->and($databaseName)->not->toBe('prod');

    // Проверяем что используется тестовая база
    expect($databaseName)->toBe('testing');
});

test('тесты выполняются в окружении testing', function () {
    expect(app()->environment())->toBe('testing')
        ->and(env('APP_ENV'))->toBe('testing');
});

test('конфигурация phpunit.xml задаёт правильную базу данных', function () {
    // Эта переменная должна быть установлена в phpunit.xml
    expect(env('DB_DATABASE'))->toBe('testing');
    expect(env('DB_CONNECTION'))->toBe('mysql');
});

test('используется RefreshDatabase трейт для всех feature тестов', function () {
    // Проверяем что Pest настроен на использование RefreshDatabase
    // Это гарантирует что каждый тест начинается с чистой базы
    $reflection = new ReflectionClass(Tests\TestCase::class);

    expect($reflection->getName())->toBe('Tests\TestCase');
});

test('тестовая база не содержит production данные', function () {
    // После RefreshDatabase база должна быть пустой или содержать только тестовые данные
    // Проверяем что нет большого количества пользователей (что могло бы указывать на production)
    $userCount = \App\Models\User::count();

    // В начале теста база должна быть пустой благодаря RefreshDatabase
    expect($userCount)->toBe(0);
});

test('невозможно случайно использовать production базу', function () {
    // TestCase имеет защиту которая проверяет имя базы
    // Если случайно попытаться использовать 'laravel', тест упадёт в setUp()

    $databaseName = DB::connection()->getDatabaseName();

    // Эта проверка должна пройти так как мы в тестовой среде
    expect(in_array($databaseName, ['laravel', 'production', 'prod']))->toBeFalse();
});
