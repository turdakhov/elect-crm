# CRM система

Система управления проектами, закупками и расходами.

## 🚀 Быстрый старт

### Первоначальная настройка

```bash
# Клонировать репозиторий
git clone <url>
cd crm2

# Скопировать .env файл
cp .env.example .env

# Запустить Docker контейнеры
sail up -d

# Установить зависимости
sail composer install
sail npm install

# Сгенерировать ключ приложения
sail artisan key:generate

# Выполнить миграции
sail artisan migrate

# Инициализировать проект с минимальными данными
sail artisan project:init

# Собрать frontend
sail npm run build
```

### Вход в систему

После инициализации вы можете войти с учётными данными:
- **Email:** admin@example.com
- **Пароль:** password

⚠️ **Важно:** Измените пароль администратора после первого входа!

## 📚 Документация

- [Инициализация проекта](INITIALIZE.md) - Настройка пустого проекта
- [Безопасность тестов](tests/Feature/TestDatabaseSecurityTest.php) - Информация о тестовой среде

## 🧪 Тестирование

```bash
# Запустить все тесты
sail artisan test

# Запустить конкретный тест
sail artisan test --filter=ProjectInitializationTest

# Запустить тесты с покрытием
sail artisan test --coverage
```

## 🛠️ Полезные команды

```bash
# Инициализация с минимальными данными
sail artisan project:init

# Заполнить базу тестовыми данными
sail artisan db:seed

# Сброс и пересоздание базы с данными
sail artisan migrate:fresh --seed

# Очистка кэша
sail artisan cache:clear
sail artisan view:clear

# Проверка стиля кода
sail composer pint
```

## 📦 Основные модули

- **Пользователи** - Управление пользователями с разными ролями
- **Проекты** - Управление проектами и клиентами
- **Товары** - Каталог товаров и материалов
- **Закупки** - Управление закупками для проектов
- **Сметы** - Формирование смет для проектов
- **Доходы/Расходы** - Учёт финансов
- **Файлы** - Хранение документов и изображений

## 🔧 Технологии

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Livewire 3, Volt, Tailwind CSS 4, Flux UI
- **База данных:** MySQL 8.0
- **Тестирование:** Pest 4
- **Контейнеризация:** Laravel Sail (Docker)

## 📝 Лицензия

Proprietary
