<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Unit;

use Closepact\RotRutXml\Rules\RuleCatalog;
use Closepact\RotRutXml\Rules\SourceRegistry;
use Closepact\RotRutXml\Tests\TestCase;

final class RuleCatalogTest extends TestCase
{
    /** Codes whose tests live outside fixtures/expectations.json, and where. */
    private const TESTED_ELSEWHERE = [
        'XML_TOO_LARGE' => 'SafeXmlLoaderTest',
        'XML_TOO_DEEP' => 'SafeXmlLoaderTest',
        'VERSION_MISMATCH' => 'ValidatorTest',
        'SCHEMA_CHECKSUM_MISMATCH' => 'SchemaTest',
        'INPUT_INVALID_JSON' => 'GeneratorTest',
        'INPUT_REQUIRED_MISSING' => 'GeneratorTest',
        'INPUT_INVALID_VALUE' => 'GeneratorTest',
        'INPUT_UNKNOWN_FIELD' => 'GeneratorTest',
        'INPUT_DUPLICATE_SERVICE' => 'GeneratorTest',
        'INPUT_NOT_FOR_VERSION' => 'GeneratorTest',
        'POLICY_REQUESTED_ZERO' => 'ValidatorTest',
        'POLICY_PROPERTY_AMBIGUOUS' => 'ValidatorTest',
        'POLICY_INVOICE_NUMBER_DUPLICATED' => 'ValidatorTest',
        'HOURS_AND_MATERIAL_FLAT_RATE_ONLY' => 'SkatteverketExamplesTest',
        'SCHEMA_UNAVAILABLE' => 'FixtureExpectationsTest::test_without_schema',
        'STRUCTURE_REQUIRED_MISSING' => 'FixtureExpectationsTest::test_without_schema',
        'STRUCTURE_INVALID_VALUE' => 'FixtureExpectationsTest::test_without_schema',
        'SERVICE_REQUIRED' => 'ValidatorTest',
    ];

    public function test_every_rule_has_text_in_both_languages_and_known_sources(): void
    {
        foreach (RuleCatalog::all() as $code => $rule) {
            self::assertMatchesRegularExpression('/^[A-Z0-9_]+$/', $code);
            self::assertNotSame('', trim($rule->sv), $code);
            self::assertNotSame('', trim($rule->en), $code);
            self::assertSame(($rule->fixSv === null), ($rule->fixEn === null), "{$code}: a fix is given in both languages or neither");
            foreach ($rule->sources as $source) {
                self::assertArrayHasKey($source, SourceRegistry::all(), "{$code} cites an unknown source");
            }
            self::assertContains($rule->status, ['verified', 'verify_required'], $code);
        }
    }

    public function test_every_regulatory_rule_cites_a_primary_source(): void
    {
        foreach (RuleCatalog::all() as $code => $rule) {
            if (in_array($rule->category->value, ['rule', 'transition', 'schema', 'structure', 'version'], true) && $code !== 'VERSION_MISMATCH') {
                self::assertNotEmpty($rule->sources, "{$code} has no source");
            }
        }
    }

    public function test_every_source_has_an_exact_url(): void
    {
        foreach (SourceRegistry::all() as $id => $source) {
            self::assertStringStartsWith('https://', $source['url'], $id);
            self::assertNotSame(parse_url($source['url'], PHP_URL_PATH) ?? '/', '/', "{$id} points at a home page");
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $source['retrieved'], $id);
        }
    }

    public function test_every_code_used_in_the_source_is_in_the_catalog(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root().'/src'));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all("/new Issue\\(\\s*\\\$?'([A-Z0-9_]+)'/", (string) file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $code) {
                self::assertTrue(RuleCatalog::has($code), "{$code} in {$file->getFilename()} is not in RuleCatalog");
            }
        }
    }

    public function test_docs_rules_md_is_generated_from_the_code(): void
    {
        $php = PHP_BINARY;
        $generated = shell_exec(escapeshellarg($php).' '.escapeshellarg(self::root().'/tools/rules-doc.php'));

        self::assertSame((string) file_get_contents(self::root().'/docs/rules.md'), (string) $generated, 'run: php tools/rules-doc.php > docs/rules.md');
    }

    public function test_every_code_is_exercised_by_a_test(): void
    {
        $data = json_decode((string) file_get_contents(self::root().'/fixtures/expectations.json'), true, 512, JSON_THROW_ON_ERROR);
        $covered = [];
        foreach ($data['cases'] as $case) {
            foreach (['errors', 'warnings', 'notices', 'errors_without_schema'] as $key) {
                foreach ($case[$key] ?? [] as $code) {
                    $covered[$code] = true;
                }
            }
        }

        $missing = array_diff(array_keys(RuleCatalog::all()), array_keys($covered), array_keys(self::TESTED_ELSEWHERE));
        self::assertSame([], array_values($missing), 'codes without a test');
    }
}
