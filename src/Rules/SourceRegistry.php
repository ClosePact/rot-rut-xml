<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

use RuntimeException;

/** The primary sources in resources/sources.json. */
final class SourceRegistry
{
    /** @var array<string,array{publisher:string,title:string,url:string,retrieved:string,covers:string}>|null */
    private static ?array $sources = null;

    /** @return array<string,array{publisher:string,title:string,url:string,retrieved:string,covers:string}> */
    public static function all(): array
    {
        if (self::$sources === null) {
            $json = file_get_contents(dirname(__DIR__, 2).'/resources/sources.json');
            $data = $json === false ? null : json_decode($json, true);
            if (! is_array($data) || ! is_array($data['sources'] ?? null)) {
                throw new RuntimeException('resources/sources.json is missing or not valid JSON.');
            }
            /** @var array<string,array{publisher:string,title:string,url:string,retrieved:string,covers:string}> $sources */
            $sources = $data['sources'];
            self::$sources = $sources;
        }

        return self::$sources;
    }

    /** @return array{publisher:string,title:string,url:string,retrieved:string,covers:string} */
    public static function get(string $id): array
    {
        return self::all()[$id] ?? throw new RuntimeException("Unknown source '{$id}'.");
    }
}
