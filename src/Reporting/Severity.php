<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Reporting;

enum Severity: string
{
    /** The file breaks the schema or a rule a primary source states; Skatteverket would reject it or the request is not allowed. */
    case Error = 'error';

    /** Probably wrong, or a rule whose exact effect is not confirmed (VERIFY_REQUIRED). */
    case Warning = 'warning';

    /** Something to know or do, not a fault in the file. */
    case Notice = 'notice';
}
