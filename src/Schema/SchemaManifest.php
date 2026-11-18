<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Schema;

use Closepact\RotRutXml\Version\SchemaVersion;
use RuntimeException;

/** The Skatteverket files this toolkit knows, with where they come from and their checksums. */
final class SchemaManifest
{
    /** @var list<array{path:string,url:string,sha256:string,version:string,kind:string}>|null */
    private static ?array $files = null;

    /** @return list<array{path:string,url:string,sha256:string,version:string,kind:string}> */
    public static function files(): array
    {
        if (self::$files === null) {
            $json = file_get_contents(dirname(__DIR__, 2).'/resources/schemas/manifest.json');
            $data = $json === false ? null : json_decode($json, true);
            if (! is_array($data) || ! is_array($data['files'] ?? null)) {
                throw new RuntimeException('resources/schemas/manifest.json is missing or not valid JSON.');
            }
            /** @var list<array{path:string,url:string,sha256:string,version:string,kind:string}> $files */
            $files = $data['files'];
            self::$files = $files;
        }

        return self::$files;
    }

    /** @return list<array{path:string,url:string,sha256:string,version:string,kind:string}> the XSD files one version needs */
    public static function schemaFilesFor(SchemaVersion $version): array
    {
        return array_values(array_filter(
            self::files(),
            static fn (array $f): bool => $f['kind'] === 'xsd' && $f['version'] === $version->value,
        ));
    }

    /** @return array{path:string,url:string,sha256:string,version:string,kind:string}|null */
    public static function byPath(string $path): ?array
    {
        foreach (self::files() as $file) {
            if ($file['path'] === $path) {
                return $file;
            }
        }

        return null;
    }

    /** @return array{path:string,url:string,sha256:string,version:string,kind:string}|null */
    public static function byUrl(string $url): ?array
    {
        $normalised = preg_replace('#^http://#', 'https://', $url);
        foreach (self::files() as $file) {
            if ($file['url'] === $normalised) {
                return $file;
            }
        }

        return null;
    }
}
