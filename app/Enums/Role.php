<?php

namespace App\Enums;

/**
 * Enum FlowName.
 */
enum Role: string
{
    case User = 'User';
    case Trainer = 'Trainer';
    case Admin = 'Admin';
}
