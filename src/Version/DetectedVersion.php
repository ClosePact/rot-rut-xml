<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Version;

/** What the root element says about the file's format. */
final readonly class DetectedVersion
{
    public function __construct(
        public ?SchemaVersion $version,
        public string $namespace,
        public string $rootName,
        /** A Skatteverket namespace this toolkit recognises but does not validate (e.g. ht/begaran 3.0 or 4.0). */
        public ?string $legacy = null,
    ) {}

    public function isSupported(): bool
    {
        return $this->version !== null;
    }
}
