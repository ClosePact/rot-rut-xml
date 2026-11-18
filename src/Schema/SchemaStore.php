<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Schema;

use Closepact\RotRutXml\Version\SchemaVersion;

/**
 * A local directory holding Skatteverket's schemas, laid out like Skatteverket's
 * URLs (ht/begaran/V6/Begaran.xsd, …) so the relative import in the V6 schema works.
 *
 * Where it is: the directory given explicitly, else ROT_RUT_XML_SCHEMA_DIR, else
 * the user's cache directory (%LOCALAPPDATA%\rot-rut-xml\schemas on Windows,
 * $XDG_CACHE_HOME/rot-rut-xml/schemas or ~/.cache/rot-rut-xml/schemas elsewhere).
 */
final readonly class SchemaStore
{
    public function __construct(public string $directory) {}

    public static function default(?string $directory = null): self
    {
        if ($directory !== null && $directory !== '') {
            return new self(rtrim($directory, '/\\'));
        }

        $env = getenv('ROT_RUT_XML_SCHEMA_DIR');
        if (is_string($env) && $env !== '') {
            return new self(rtrim($env, '/\\'));
        }

        $base = getenv('LOCALAPPDATA') ?: getenv('XDG_CACHE_HOME') ?: null;
        if ($base === null) {
            $home = getenv('HOME') ?: getenv('USERPROFILE') ?: sys_get_temp_dir();
            $base = rtrim((string) $home, '/\\').DIRECTORY_SEPARATOR.'.cache';
        }

        return new self(rtrim($base, '/\\').DIRECTORY_SEPARATOR.'rot-rut-xml'.DIRECTORY_SEPARATOR.'schemas');
    }

    public function pathOf(string $relative): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /** Whether every XSD file the version needs is present and matches the manifest. */
    public function status(SchemaVersion $version): SchemaStatus
    {
        foreach (SchemaManifest::schemaFilesFor($version) as $file) {
            $status = $this->fileStatus($file['path']);
            if ($status !== SchemaStatus::OK) {
                return new SchemaStatus($status, $file['path']);
            }
        }

        return new SchemaStatus(SchemaStatus::OK, $version->schemaEntry());
    }

    /** @return SchemaStatus::OK|SchemaStatus::MISSING|SchemaStatus::CHECKSUM */
    public function fileStatus(string $relative): string
    {
        $entry = SchemaManifest::byPath($relative);
        $path = $this->pathOf($relative);
        if ($entry === null || ! is_file($path)) {
            return SchemaStatus::MISSING;
        }

        return hash_file('sha256', $path) === $entry['sha256'] ? SchemaStatus::OK : SchemaStatus::CHECKSUM;
    }
}
