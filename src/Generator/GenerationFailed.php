<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Generator;

use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Reporting\ValidationResult;
use RuntimeException;

final class GenerationFailed extends RuntimeException
{
    /** @param list<Issue> $issues */
    public function __construct(public readonly array $issues, public readonly ?ValidationResult $validation = null)
    {
        $codes = implode(', ', array_unique(array_map(static fn (Issue $i): string => $i->code, $issues)));
        parent::__construct("The request file was not generated: {$codes}");
    }
}
