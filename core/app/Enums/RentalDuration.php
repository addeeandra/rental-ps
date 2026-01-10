<?php

namespace App\Enums;

enum RentalDuration: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
