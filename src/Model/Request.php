<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Model;

use Closepact\RotRutXml\Version\SchemaVersion;

/** A payment request (`Begaran`), read from a file or built from input, in a version-neutral shape. */
final readonly class Request
{
    public const ROT = 'ROT';

    public const RUT = 'RUT';

    /**
     * @param  list<BuyerClaim>  $claims
     * @param  list<string>  $contactEmails
     */
    public function __construct(
        public SchemaVersion $version,
        public ?string $name,
        /** ROT, RUT, or what a 1.x file says in `TypAvBegaran` (e.g. GRON_TEKNIK). */
        public ?string $type,
        public ?string $performer,
        public array $claims,
        public ?string $contactName = null,
        public ?string $contactProduct = null,
        public array $contactEmails = [],
        /** Path of the element holding the claims, for file-level findings. */
        public string $claimsPath = '/Begaran',
    ) {}

    public function isRot(): bool
    {
        return $this->type === self::ROT;
    }

    public function isRut(): bool
    {
        return $this->type === self::RUT;
    }
}
