<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // КРИТИЧЕСКАЯ ПРОВЕРКА: убеждаемся что не используем production базу
        $this->ensureTestingDatabase();
    }
    
    /**
     * Убеждаемся что тесты используют тестовую базу данных
     */
    private function ensureTestingDatabase(): void
    {
        $databaseName = DB::connection()->getDatabaseName();
        $environment = app()->environment();
        
        // Проверяем что окружение - testing
        if ($environment !== 'testing') {
            throw new \RuntimeException(
                "ОШИБКА: Тесты должны выполняться в окружении 'testing', текущее: {$environment}"
            );
        }
        
        // Проверяем что не используем production базу
        if (in_array($databaseName, ['laravel', 'production', 'prod'])) {
            throw new \RuntimeException(
                "ОШИБКА: Запрещено использовать production базу '{$databaseName}' для тестов! Текущая БД: {$databaseName}"
            );
        }
        
        // Проверяем что база называется 'testing' или ':memory:'
        if (!in_array($databaseName, ['testing', ':memory:'])) {
            throw new \RuntimeException(
                "ПРЕДУПРЕЖДЕНИЕ: Тесты используют базу '{$databaseName}'. Рекомендуется использовать 'testing' или ':memory:'"
            );
        }
    }
}

