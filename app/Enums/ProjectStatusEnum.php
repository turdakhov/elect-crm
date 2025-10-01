<?php

namespace App\Enums;

enum ProjectStatusEnum: string
{
    case Planned = 'В процессе переговоров';
    case ProjectDesign = 'Проектирование';
    case RoughInProgress = 'Черновая в работе';
    case RoughFinished = 'Черновая завершена';
    case FineInProgress = 'Чистовая в работе';
    case Completed = 'Завершен';
    case OnHold = 'На паузе';
    case Cancelled = 'Отменен';
}
