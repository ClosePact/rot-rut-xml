<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Model;

/** Where ROT work was done: a property designation, or an apartment and its housing cooperative. */
final readonly class Property
{
    public function __construct(
        public ?string $designation,
        public ?string $apartmentNumber,
        public ?string $brfOrgNumber,
        public string $path,
    ) {}

    public function isEmpty(): bool
    {
        return self::blank($this->designation) && self::blank($this->apartmentNumber) && self::blank($this->brfOrgNumber);
    }

    public static function blank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
