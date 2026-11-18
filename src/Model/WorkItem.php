<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Model;

/** One service declared in a buyer's claim (`UtfortArbete`). */
final readonly class WorkItem
{
    public function __construct(
        /** Canonical service code, e.g. STADNING (see ServiceCatalog). Null when the file names an unknown service. */
        public ?string $service,
        /** The service exactly as written in the file (code or V6 element name). */
        public string $rawService,
        public ?int $hours,
        /** Material cost, or for V1.x the `Kostnad` element. */
        public ?int $materialCost,
        /** V6 flat-rate services only: the `Utfort` flag. */
        public ?bool $flatRateDone,
        public string $path,
    ) {}
}
