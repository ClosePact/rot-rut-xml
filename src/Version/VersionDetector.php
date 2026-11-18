<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Version;

use DOMDocument;

/**
 * Reads the format version from the root element's namespace.
 *
 * The namespace is the only reliable marker: none of the formats has a version
 * attribute. Older `ht/begaran` namespaces (Skatteverket: "XML version 3 och 4
 * fungerar fortfarande men kommer på sikt att fasas ut") are recognised by their
 * pattern and reported as unsupported rather than as unknown.
 */
final class VersionDetector
{
    public function detect(DOMDocument $document): DetectedVersion
    {
        $root = $document->documentElement;
        if ($root === null) {
            return new DetectedVersion(null, '', '');
        }

        $namespace = (string) $root->namespaceURI;
        $name = $root->localName ?? $root->nodeName;
        $version = $name === 'Begaran' ? SchemaVersion::fromRootNamespace($namespace) : null;

        $legacy = null;
        if ($version === null && preg_match('#^https?://xmls\.skatteverket\.se/se/skatteverket/(ht|skattered)/begaran/(\d+(?:\.\d+)?)$#', $namespace, $m) === 1) {
            $legacy = $m[1].'/begaran '.$m[2];
        }

        return new DetectedVersion($version, $namespace, $name, $legacy);
    }
}
