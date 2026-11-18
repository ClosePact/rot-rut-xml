<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Schema;

final readonly class SchemaStatus
{
    public const OK = 'ok';

    public const MISSING = 'missing';

    public const CHECKSUM = 'checksum_mismatch';

    public function __construct(public string $state, public string $file) {}

    public function isUsable(): bool
    {
        return $this->state === self::OK;
    }
}
