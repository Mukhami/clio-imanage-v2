<?php

namespace App\Enums;

enum ReminderType: string
{
    case ThirtyDays = '30_day';
    case FourteenDays = '14_day';
    case SevenDays = '7_day';
    case OneDay = '1_day';
    case Expired = 'expired';
}
