<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Model;

/**
 * One buyer's part of a request: `Arende` (1.x) or `Arenden` (V6).
 *
 * Every value is nullable because a file can omit or garble any of them; the
 * parser records why, and the rules skip what they cannot read.
 */
final readonly class BuyerClaim
{
    /**
     * @param  list<WorkItem>  $work
     * @param  array<string,string>  $paths  field name => XPath-like location, for reporting
     */
    public function __construct(
        /** 1-based position in the file. */
        public int $index,
        public ?string $invoiceNumber,
        public ?string $buyer,
        public ?Property $property,
        public array $work,
        public ?int $otherCost,
        public ?string $paymentDate,
        public ?int $labourCost,
        public ?int $paidAmount,
        public ?int $requestedAmount,
        public ?bool $subcontractors,
        public ?bool $staffingAgency,
        public array $paths,
    ) {}

    public function path(string $field): string
    {
        return $this->paths[$field] ?? $this->paths['claim'];
    }

    public function paymentYear(): ?int
    {
        return $this->paymentDate !== null && preg_match('/^(\d{4})-\d{2}-\d{2}$/', $this->paymentDate, $m) === 1 ? (int) $m[1] : null;
    }
}
