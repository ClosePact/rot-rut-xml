<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Xml;

use DOMDocument;
use DOMElement;

/** Readable element paths without namespace prefixes: /Begaran/Arende[2]/BegartBelopp. */
final class XmlPath
{
    public static function of(DOMElement $element): string
    {
        $parts = [];
        $node = $element;
        while ($node instanceof DOMElement) {
            $name = (string) $node->localName;
            $parent = $node->parentNode;
            $same = 0;
            $position = 0;
            if ($parent instanceof DOMElement) {
                foreach ($parent->childNodes as $sibling) {
                    if ($sibling instanceof DOMElement && $sibling->localName === $name) {
                        $same++;
                        if ($sibling->isSameNode($node)) {
                            $position = $same;
                        }
                    }
                }
            }
            $parts[] = $same > 1 ? $name.'['.$position.']' : $name;
            $node = $parent;
        }

        return '/'.implode('/', array_reverse($parts));
    }

    /** The first element with this local name on this line, if any. */
    public static function find(DOMDocument $document, string $localName, int $line): ?DOMElement
    {
        foreach ($document->getElementsByTagNameNS('*', $localName) as $element) {
            if ($element instanceof DOMElement && $element->getLineNo() === $line) {
                return $element;
            }
        }

        return null;
    }
}
