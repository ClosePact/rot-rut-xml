<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Generator;

use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Reporting\ValidationResult;

final readonly class GenerationResult
{
    /** @param list<Issue> $inputWarnings */
    public function __construct(
        public string $xml,
        /** Validation of the written file: no errors, possibly warnings and notices. */
        public ValidationResult $validation,
        public array $inputWarnings = [],
    ) {}
}
