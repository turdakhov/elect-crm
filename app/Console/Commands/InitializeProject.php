<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InitializeProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:init {--force : Пропустить подтверждение}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Инициализировать проект с минимальным набором ключевых данных (пользователи, типы файлов, типы расходов)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('🚀 Инициализация проекта');
        $this->newLine();

        // Проверка подтверждения
        if (! $this->option('force')) {
            if (! $this->confirm('Эта команда добавит ключевые данные в базу данных. Продолжить?', true)) {
                $this->components->warn('Инициализация отменена');

                return self::FAILURE;
            }
        }

        // Запускаем seeder
        $this->components->task('Добавление ключевых данных', function () {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\InitialDataSeeder',
            ]);

            return true;
        });

        $this->newLine();
        $this->components->success('Проект успешно инициализирован!');
        $this->newLine();

        $this->components->bulletList([
            'Создан администратор: admin@example.com / password',
            'Добавлены типы файлов (pdf, xlsx, картинка, word, чертеж)',
            'Добавлены типы расходов (зарплаты, реклама, материалы и т.д.)',
        ]);

        $this->newLine();
        $this->components->info('💡 Теперь вы можете войти в систему и начать работу');
        $this->newLine();

        return self::SUCCESS;
    }
}
