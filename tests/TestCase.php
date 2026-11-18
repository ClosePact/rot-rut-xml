<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests;

use Closepact\RotRutXml\Schema\SchemaStore;
use Closepact\RotRutXml\Version\SchemaVersion;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static function root(): string
    {
        return dirname(__DIR__);
    }

    protected static function fixture(string $path): string
    {
        return self::root().'/fixtures/'.$path;
    }

    /**
     * Skatteverket's schemas, fetched with `bin/rot-rut schemas:fetch --include-samples`.
     * Tests that need them are skipped (and reported as skipped) when they are not there;
     * CI fetches them before the test run.
     */
    protected static function schemaStore(): ?SchemaStore
    {
        $dir = getenv('ROT_RUT_XML_SCHEMA_DIR');
        if (! is_string($dir) || $dir === '') {
            return null;
        }
        $store = new SchemaStore($dir);
        foreach (SchemaVersion::cases() as $version) {
            if (! $store->status($version)->isUsable()) {
                return null;
            }
        }

        return $store;
    }

    protected function requireSchemas(): SchemaStore
    {
        $store = self::schemaStore();
        if ($store === null) {
            self::markTestSkipped('Skatteverket\'s schemas are not installed: set ROT_RUT_XML_SCHEMA_DIR and run `bin/rot-rut schemas:fetch --include-samples`.');
        }

        return $store;
    }

    /** A directory that holds no schemas, so the XSD check cannot run. */
    protected static function emptyStore(): SchemaStore
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rot-rut-xml-empty-'.getmypid();
        if (! is_dir($dir)) {
            mkdir($dir);
        }

        return new SchemaStore($dir);
    }
}
