<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\ExpenseType;
use App\Models\FileType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder для инициализации пустого проекта с минимальным набором ключевых данных.
 * Содержит только необходимые для работы системы данные:
 * - Администраторов
 * - Типы файлов
 * - Типы расходов
 */
class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Инициализация ключевых данных...');

        $this->seedUsers();
        $this->seedFileTypes();
        $this->seedExpenseTypes();

        $this->command->info('✅ Ключевые данные успешно добавлены!');
    }

    /**
     * Создаёт минимальный набор пользователей (только администраторы)
     */
    private function seedUsers(): void
    {
        $this->command->info('👤 Создание пользователей...');

        // Главный администратор
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRoleEnum::Admin->name,
            ]
        );

        $this->command->info('   ✓ Создан администратор (admin@example.com / password)');
    }

    /**
     * Создаёт стандартные типы файлов
     */
    private function seedFileTypes(): void
    {
        $this->command->info('📄 Создание типов файлов...');

        $fileTypes = [
            ['name' => 'pdf', 'description' => 'PDF документ'],
            ['name' => 'xlsx', 'description' => 'Excel таблица'],
            ['name' => 'картинка', 'description' => 'Изображение (jpg, png, gif и т.д.)'],
            ['name' => 'word', 'description' => 'Word документ'],
            ['name' => 'чертеж', 'description' => 'Чертёж или схема'],
        ];

        foreach ($fileTypes as $fileType) {
            FileType::firstOrCreate(
                ['name' => $fileType['name']],
                ['description' => $fileType['description']]
            );
        }

        $this->command->info('   ✓ Создано '.count($fileTypes).' типов файлов');
    }

    /**
     * Создаёт стандартные типы расходов
     */
    private function seedExpenseTypes(): void
    {
        $this->command->info('💰 Создание типов расходов...');

        $expenseTypes = [
            'Зарплаты',
            'Личные доли',
            'Реклама',
            'Инструменты',
            'Рабочие расходы',
            'Услуги',
            'Материалы',
            'Транспорт',
        ];

        foreach ($expenseTypes as $name) {
            ExpenseType::firstOrCreate(['name' => $name]);
        }

        $this->command->info('   ✓ Создано '.count($expenseTypes).' типов расходов');
    }
}
