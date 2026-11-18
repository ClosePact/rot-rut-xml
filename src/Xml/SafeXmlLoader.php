<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Xml;

use Closepact\RotRutXml\Reporting\Issue;
use DOMDocument;
use DOMElement;

/**
 * Parses untrusted XML without letting it reach the network or the file system.
 *
 * - DOCTYPE and ENTITY declarations are refused before parsing. Skatteverket's
 *   formats never use them, and refusing them closes XXE and entity-expansion
 *   attacks outright instead of relying on parser flags.
 * - LIBXML_NONET forbids network access; entities are never substituted
 *   (no LIBXML_NOENT) and no DTD is loaded (no LIBXML_DTDLOAD).
 * - Size and nesting depth are capped.
 */
final class SafeXmlLoader
{
    public const DEFAULT_MAX_BYTES = 5_000_000;

    public const DEFAULT_MAX_DEPTH = 32;

    public function __construct(
        private readonly int $maxBytes = self::DEFAULT_MAX_BYTES,
        private readonly int $maxDepth = self::DEFAULT_MAX_DEPTH,
    ) {}

    public function load(string $xml): LoadResult
    {
        if (trim($xml) === '') {
            return LoadResult::failed(new Issue('XML_EMPTY'));
        }

        if (strlen($xml) > $this->maxBytes) {
            return LoadResult::failed(new Issue('XML_TOO_LARGE', params: ['limit' => $this->maxBytes]));
        }

        if (preg_match('/<!\s*(DOCTYPE|ENTITY)/i', $xml) === 1) {
            return LoadResult::failed(new Issue('XML_DOCTYPE_FORBIDDEN'));
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument;
        $document->preserveWhiteSpace = true;
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $errors !== []) {
            $first = $errors[0] ?? null;
            $detail = $first !== null ? trim($first->message) : 'libxml reported no error message';

            return LoadResult::failed(new Issue('XML_MALFORMED', params: ['detail' => $detail], line: $first?->line));
        }

        $root = $document->documentElement;
        if ($root === null) {
            return LoadResult::failed(new Issue('XML_MALFORMED', params: ['detail' => 'no root element']));
        }

        if ($this->depth($root) > $this->maxDepth) {
            return LoadResult::failed(new Issue('XML_TOO_DEEP', params: ['limit' => $this->maxDepth]));
        }

        return new LoadResult($document, []);
    }

    private function depth(DOMElement $root): int
    {
        $max = 0;
        $stack = [[$root, 1]];
        while ($stack !== []) {
            [$node, $level] = array_pop($stack);
            $max = max($max, $level);
            if ($max > $this->maxDepth) {
                return $max;
            }
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $stack[] = [$child, $level + 1];
                }
            }
        }

        return $max;
    }
}
