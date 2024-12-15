<?php

namespace App\Enums;

/**
 * Enum ResultStatus.
 */
enum ResultStatus: string
{
    case Processed = 'Processed';
    case FileIsTooBig = 'File Is Too Big';
    case NoChoices = 'No Choices';
    case Unrecognized = 'Unrecognized';
    case Exception = 'Exception';
    case Nothing = 'Nothing';
    case Ignored = 'Ignored';
}
