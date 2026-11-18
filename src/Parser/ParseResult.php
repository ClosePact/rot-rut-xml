<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Parser;

use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Reporting\Issue;

final readonly class ParseResult
{
    /** @param list<Issue> $structureIssues */
    public function __construct(public Request $request, public array $structureIssues) {}
}
