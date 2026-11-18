<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Xml;

use Closepact\RotRutXml\Reporting\Issue;
use DOMDocument;

final readonly class LoadResult
{
    /** @param list<Issue> $issues */
    public function __construct(public ?DOMDocument $document, public array $issues) {}

    public static function failed(Issue $issue): self
    {
        return new self(null, [$issue]);
    }
}
