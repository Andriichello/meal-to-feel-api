<?php

namespace App\Enums;

/**
 * Enum FlowStatus.
 */
enum FlowStatus: string
{
    case New = 'new';
    case Initiated = 'initiated';
    case InProgress = 'in-progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';
}
