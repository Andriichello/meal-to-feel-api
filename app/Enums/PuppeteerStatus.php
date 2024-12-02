<?php

namespace App\Enums;

/**
 * Enum PuppeteerStatus.
 */
enum PuppeteerStatus: string
{
    case Success = 'Success';
    case MissingArgs = 'Missing Args';
    case Verification = 'Verification';
    case NoBrowser = 'No Browser';
    case TryAfter = 'Try After';
    case NoUpload = 'No Upload';
    case TimedOut = 'Timed Out';
    case ParsingFail = 'Parsing Fail';
    case NoJSON = 'No JSON';
}
