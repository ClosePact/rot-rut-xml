<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Integration;

use Closepact\RotRutXml\Reporting\Severity;
use Closepact\RotRutXml\Reporting\ValidationResult;
use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every fixture against fixtures/expectations.json, twice: with Skatteverket's
 * XSD (skipped when not installed) and without it (always runs).
 */
final class FixtureExpectationsTest extends TestCase
{
    /** @return array<string,array{0:array<string,mixed>}> */
    public static function cases(): array
    {
        $data = json_decode((string) file_get_contents(self::root().'/fixtures/expectations.json'), true, 512, JSON_THROW_ON_ERROR);
        $cases = [];
        foreach ($data['cases'] as $case) {
            $cases[$case['id']] = [$case];
        }

        return $cases;
    }

    /** @param array<string,mixed> $case */
    #[DataProvider('cases')]
    public function test_with_skatteverkets_schema(array $case): void
    {
        $result = (new Validator($this->requireSchemas()))->validateFile(self::fixture($case['file']), $this->options($case));

        self::assertSame($case['schema'], $result->schemaStatus, 'XSD status');
        $this->assertCodes($case['errors'], $case['warnings'], $case['notices'], $result);
    }

    /** @param array<string,mixed> $case */
    #[DataProvider('cases')]
    public function test_without_schema(array $case): void
    {
        $result = (new Validator(self::emptyStore()))->validateFile(self::fixture($case['file']), $this->options($case));

        $notRun = $case['schema'] === ValidationResult::SCHEMA_NOT_RUN;
        self::assertSame($notRun ? ValidationResult::SCHEMA_NOT_RUN : ValidationResult::SCHEMA_SKIPPED, $result->schemaStatus);
        $notices = $notRun ? $case['notices'] : [...$case['notices'], 'SCHEMA_UNAVAILABLE'];
        $this->assertCodes($case['errors_without_schema'] ?? $case['errors'], $case['warnings'], $notices, $result);
    }

    /** @param array<string,mixed> $case */
    #[DataProvider('cases')]
    public function test_messages_never_contain_identity_numbers(array $case): void
    {
        $result = (new Validator(self::emptyStore()))->validateFile(self::fixture($case['file']), $this->options($case));
        $json = (string) json_encode($result->toArray(), JSON_UNESCAPED_UNICODE);

        self::assertDoesNotMatchRegularExpression('/(?<!\d)(\d{6}|\d{8})[-+]?\d{4}(?!\d)/', $json, 'a report must not echo personnummer or organisation numbers');
    }

    /** @param array<string,mixed> $case */
    private function options(array $case): ValidationOptions
    {
        return ValidationOptions::forDate($case['as_of'], policy: (bool) ($case['policy'] ?? false));
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  list<string>  $notices
     */
    private function assertCodes(array $errors, array $warnings, array $notices, ValidationResult $result): void
    {
        $actual = static function (Severity $severity) use ($result): array {
            $codes = array_values(array_unique($result->codes($severity)));
            sort($codes);

            return $codes;
        };
        $expected = static function (array $codes): array {
            $codes = array_values(array_unique($codes));
            sort($codes);

            return $codes;
        };
        $detail = json_encode(array_map(static fn ($i) => [$i->code, $i->path, $i->message('en')], $result->issues), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        self::assertSame($expected($errors), $actual(Severity::Error), "errors\n".$detail);
        self::assertSame($expected($warnings), $actual(Severity::Warning), "warnings\n".$detail);
        self::assertSame($expected($notices), $actual(Severity::Notice), "notices\n".$detail);
        self::assertSame($errors === [], $result->isValid());
    }
}
