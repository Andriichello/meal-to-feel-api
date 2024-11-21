<?php

namespace App\Enums;

/**
 * Enum FlowStep.
 */
enum FlowStep: string
{
    case Initiation = 'initiation';
    case Save = 'save';
    case Cancel = 'cancel';
    case Upload = 'upload';
    case SetTime = 'set-time';
    case SetDate = 'set-date';

}
