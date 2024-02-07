<?php

namespace App\Enum;

enum FrequencyEnum: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
}
