<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Schema;

use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Version\SchemaVersion;
use Closepact\RotRutXml\Xml\XmlPath;
use DOMDocument;

/**
 * Validates a document against Skatteverket's XSD from a local SchemaStore.
 *
 * While validating, libxml may load files only from inside the schema
 * directory: a schema URL that points at Skatteverket is mapped to the local
 * copy, and anything else is refused. Validation never touches the network.
 */
final class XsdValidator
{
    public function __construct(private readonly SchemaStore $store) {}

    /** @return list<Issue> */
    public function validate(DOMDocument $document, SchemaVersion $version): array
    {
        $root = realpath($this->store->directory);
        $entry = realpath($this->store->pathOf($version->schemaEntry()));
        if ($root === false || $entry === false) {
            return [new Issue('SCHEMA_UNAVAILABLE', params: ['version' => $version->label()])];
        }

        $previousLoader = libxml_get_external_entity_loader();
        libxml_set_external_entity_loader(fn (?string $public, string $system, array $context): ?string => $this->resolve($system, $root));
        $previousErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            @$document->schemaValidate($entry);
            // Warnings come from reading Skatteverket's schema itself (its metadata
            // annotation declares a relative namespace URI), not from the file.
            $errors = array_values(array_filter(libxml_get_errors(), static fn (\LibXMLError $e): bool => $e->level !== LIBXML_ERR_WARNING));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
            libxml_set_external_entity_loader($previousLoader);
        }

        $issues = [];
        foreach ($errors as $error) {
            $message = trim((string) preg_replace('/\{[^}]*\}/', '', $error->message));
            $path = null;
            if (preg_match("/^Element '([^']+)'/", $message, $m) === 1) {
                $element = XmlPath::find($document, $m[1], $error->line);
                $path = $element !== null ? XmlPath::of($element) : null;
            }
            $issues[] = new Issue('SCHEMA_INVALID', $path, ['version' => $version->label(), 'detail' => $message], $error->line);
        }

        return $issues;
    }

    /** Only files inside the schema directory, or Skatteverket URLs mapped to their local copy. */
    private function resolve(string $system, string $root): ?string
    {
        if (preg_match('#^https?://#i', $system) === 1) {
            $file = SchemaManifest::byUrl($system);

            return $file === null ? null : $this->inside($this->store->pathOf($file['path']), $root);
        }

        $path = rawurldecode($system);
        if (preg_match('#^file:/*([A-Za-z]:[/\\\\].*)$#i', $path, $m) === 1) {
            $path = $m[1];                                   // file:/C:/… or file:///C:/…
        } elseif (preg_match('#^file:/*(.*)$#i', $path, $m) === 1) {
            $path = '/'.$m[1];                               // file:/home/… or file:///home/…
        }

        return $this->inside($path, $root);
    }

    private function inside(string $path, string $root): ?string
    {
        $real = realpath($path);
        if ($real === false) {
            return null;
        }

        $normalise = static fn (string $p): string => strtolower(str_replace('\\', '/', $p));

        return str_starts_with($normalise($real), rtrim($normalise($root), '/').'/') ? $real : null;
    }
}
