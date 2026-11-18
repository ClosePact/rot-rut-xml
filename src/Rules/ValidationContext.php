<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

use DateTimeImmutable;

final readonly class ValidationContext
{
    public function __construct(
        /** The day the file is to be sent to Skatteverket (Skatteverket: "ansökningsdatum"). */
        public DateTimeImmutable $asOf,
        /** Run the optional policy checks too. */
        public bool $policy = false,
    ) {}

    public function asOfDate(): string
    {
        return $this->asOf->format('Y-m-d');
    }
}
