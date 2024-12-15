<?php

namespace App\Enums;

/**
 * Enum MealStatus.
 */
enum MealStatus: string
{
    case Draft = 'Draft';
    case Pending = 'Pending';
    case Canceled = 'Canceled';
    case Processed = 'Processed';
}
