<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case Admin = 'Администратор';
    case Manager = 'Менеджер';
    case Worker = 'Рабочий';
    case Client = 'Клиент';
    case Designer = 'Дизайнер';
    case Accountant = 'Бухгалтер';
    case Foreman = 'Прораб';
    case Supervisor = 'Технадзор';
}
