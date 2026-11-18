<?php

declare(strict_types=1);

namespace Closepact\RotRutXml;

use Closepact\RotRutXml\Version\SchemaVersion;
use DateTimeImmutable;
use DateTimeZone;

final readonly class ValidationOptions
{
    public DateTimeImmutable $asOf;

    public function __construct(
        /** The day the file is to be sent; defaults to today in Sweden. */
        ?DateTimeImmutable $asOf = null,
        /** Fail with VERSION_MISMATCH if the file is in another version. */
        public ?SchemaVersion $expectVersion = null,
        /** Run the optional policy checks. */
        public bool $policy = false,
        /** Run the XSD check when the schema is available locally. */
        public bool $useSchema = true,
    ) {
        $this->asOf = ($asOf ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Stockholm')))->setTime(0, 0);
    }

    public static function forDate(string $date, ?SchemaVersion $expectVersion = null, bool $policy = false): self
    {
        return new self(new DateTimeImmutable($date, new DateTimeZone('Europe/Stockholm')), $expectVersion, $policy);
    }
}
