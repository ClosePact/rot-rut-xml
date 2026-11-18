<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Integration;

use Closepact\RotRutXml\Schema\SchemaFetcher;
use Closepact\RotRutXml\Schema\SchemaManifest;
use Closepact\RotRutXml\Schema\SchemaStatus;
use Closepact\RotRutXml\Schema\SchemaStore;
use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;

final class SchemaTest extends TestCase
{
    private function temporaryStore(): SchemaStore
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rot-rut-xml-'.bin2hex(random_bytes(4));
        mkdir($dir);

        return new SchemaStore($dir);
    }

    public function test_manifest_lists_https_sources_with_sha256_for_every_version(): void
    {
        foreach (SchemaManifest::files() as $file) {
            self::assertStringStartsWith('https://xmls.skatteverket.se/', $file['url']);
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $file['sha256']);
        }
        foreach (SchemaVersion::cases() as $version) {
            self::assertNotEmpty(SchemaManifest::schemaFilesFor($version), $version->value);
        }
    }

    public function test_fetcher_keeps_only_files_whose_checksum_matches(): void
    {
        $source = $this->requireSchemas();
        $store = $this->temporaryStore();
        $tamper = 'skattered/begaran/V1.1/Begaran.xsd';
        $fetcher = new SchemaFetcher($store, static function (string $url) use ($source, $tamper): string {
            $file = SchemaManifest::byUrl($url) ?? throw new \RuntimeException("not in the manifest: {$url}");
            $body = (string) file_get_contents($source->pathOf($file['path']));

            return $file['path'] === $tamper ? $body.' ' : $body;
        });

        $report = array_column($fetcher->fetch(), 'state', 'path');

        self::assertSame('downloaded', $report['ht/begaran/V6/Begaran.xsd']);
        self::assertSame(SchemaStatus::CHECKSUM, $report[$tamper]);
        self::assertFileDoesNotExist($store->pathOf($tamper));
        self::assertTrue($store->status(SchemaVersion::V6)->isUsable());
        self::assertSame(SchemaStatus::MISSING, $store->status(SchemaVersion::V1_1)->state);
        self::assertSame('unchanged', array_column($fetcher->fetch(), 'state', 'path')['ht/begaran/V6/Begaran.xsd'], 'a second run downloads nothing');
    }

    public function test_a_changed_schema_is_never_used(): void
    {
        $source = $this->requireSchemas();
        $store = $this->temporaryStore();
        foreach (SchemaManifest::schemaFilesFor(SchemaVersion::V1_1) as $file) {
            @mkdir(dirname($store->pathOf($file['path'])), 0777, true);
            copy($source->pathOf($file['path']), $store->pathOf($file['path']));
            file_put_contents($store->pathOf($file['path']), "\n", FILE_APPEND);
        }

        $result = (new Validator($store))->validateFile(self::fixture('v1_1/rut-cleaning-valid.xml'), ValidationOptions::forDate('2027-01-12'));

        self::assertSame('skipped', $result->schemaStatus);
        self::assertContains('SCHEMA_CHECKSUM_MISMATCH', $result->codes());
    }

    public function test_validation_works_from_a_copy_of_the_schemas_anywhere(): void
    {
        $source = $this->requireSchemas();
        $store = $this->temporaryStore();
        foreach (SchemaManifest::files() as $file) {
            if ($file['kind'] === 'xsd') {
                @mkdir(dirname($store->pathOf($file['path'])), 0777, true);
                copy($source->pathOf($file['path']), $store->pathOf($file['path']));
            }
        }

        $result = (new Validator($store))->validateFile(self::fixture('v6/rot-brf-and-house-valid.xml'), ValidationOptions::forDate('2026-11-18'));

        self::assertSame('passed', $result->schemaStatus, 'the relative import in the V6 schema resolves inside the copy');
    }
}
