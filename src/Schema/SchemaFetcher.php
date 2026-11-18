<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Schema;

use RuntimeException;

/**
 * Downloads Skatteverket's files into a SchemaStore, over HTTPS only, and keeps
 * a file only when its sha256 matches the manifest.
 *
 * This is the only part of the toolkit that uses the network, and it runs only
 * when asked to (`rot-rut schemas:fetch`).
 */
final class SchemaFetcher
{
    /** @var callable(string):string */
    private $download;

    /** @param (callable(string):string)|null $download fetches a URL and returns the body; injectable for tests */
    public function __construct(private readonly SchemaStore $store, ?callable $download = null)
    {
        $this->download = $download ?? self::httpsGet(...);
    }

    /**
     * @return list<array{path:string,state:string}> one line per file: `downloaded`, `unchanged` or `checksum_mismatch`
     */
    public function fetch(bool $includeSamples = false): array
    {
        $report = [];
        foreach (SchemaManifest::files() as $file) {
            if ($file['kind'] !== 'xsd' && ! $includeSamples) {
                continue;
            }
            if ($this->store->fileStatus($file['path']) === SchemaStatus::OK) {
                $report[] = ['path' => $file['path'], 'state' => 'unchanged'];

                continue;
            }

            $body = ($this->download)($file['url']);
            if (hash('sha256', $body) !== $file['sha256']) {
                $report[] = ['path' => $file['path'], 'state' => SchemaStatus::CHECKSUM];

                continue;
            }

            $target = $this->store->pathOf($file['path']);
            $directory = dirname($target);
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException("Cannot create {$directory}.");
            }
            $temporary = $target.'.part';
            if (file_put_contents($temporary, $body) === false || ! rename($temporary, $target)) {
                throw new RuntimeException("Cannot write {$target}.");
            }
            $report[] = ['path' => $file['path'], 'state' => 'downloaded'];
        }

        return $report;
    }

    private static function httpsGet(string $url): string
    {
        if (! str_starts_with($url, 'https://')) {
            throw new RuntimeException("Refusing to download over plain HTTP: {$url}");
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'follow_location' => 0,
                'header' => "User-Agent: rot-rut-xml (+https://github.com/ClosePact/rot-rut-xml)\r\n",
                'ignore_errors' => false,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException("Download failed: {$url}");
        }

        return $body;
    }
}
