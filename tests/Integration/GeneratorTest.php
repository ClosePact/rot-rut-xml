<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Integration;

use Closepact\RotRutXml\Generator\GenerationFailed;
use Closepact\RotRutXml\Generator\Generator;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Schema\SchemaStore;
use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;
use PHPUnit\Framework\Attributes\DataProvider;

final class GeneratorTest extends TestCase
{
    /** @return array<string,array{0:string,1:SchemaVersion,2:string}> */
    public static function examples(): array
    {
        return [
            'RUT 1.1' => ['rut-v1.1.json', SchemaVersion::V1_1, '2027-01-12'],
            'ROT 1.1' => ['rot-v1.1.json', SchemaVersion::V1_1, '2027-01-12'],
            'RUT 1.0' => ['rut-v1.0.json', SchemaVersion::V1_0, '2026-11-18'],
            'RUT V6' => ['rut-v6.json', SchemaVersion::V6, '2026-11-18'],
            'ROT V6' => ['rot-v6.json', SchemaVersion::V6, '2026-11-18'],
        ];
    }

    /** @return array<mixed> */
    private static function example(string $file): array
    {
        return Generator::decodeJson((string) file_get_contents(self::root().'/examples/'.$file));
    }

    private function generator(?SchemaStore $store = null): Generator
    {
        return new Generator(new Validator($store ?? self::emptyStore()));
    }

    #[DataProvider('examples')]
    public function test_examples_generate_files_that_pass_skatteverkets_schema_and_every_rule(string $file, SchemaVersion $version, string $asOf): void
    {
        $result = $this->generator($this->requireSchemas())->generate(self::example($file), $version, ValidationOptions::forDate($asOf));

        self::assertSame('passed', $result->validation->schemaStatus);
        self::assertSame([], $result->validation->codes(), 'no findings at all');
        self::assertSame($version, $result->validation->version);
    }

    #[DataProvider('examples')]
    public function test_output_is_deterministic_and_matches_the_committed_fixture(string $file, SchemaVersion $version, string $asOf): void
    {
        $generator = $this->generator();
        $first = $generator->generate(self::example($file), $version, ValidationOptions::forDate($asOf))->xml;
        $second = $generator->generate(self::example($file), $version, ValidationOptions::forDate($asOf))->xml;
        self::assertSame($first, $second);

        $fixture = [
            'rut-v1.1.json' => 'v1_1/rut-cleaning-valid.xml', 'rot-v1.1.json' => 'v1_1/rot-brf-and-house-valid.xml',
            'rut-v1.0.json' => 'v1_0/rut-cleaning-valid.xml', 'rut-v6.json' => 'v6/rut-cleaning-valid.xml', 'rot-v6.json' => 'v6/rot-brf-and-house-valid.xml',
        ][$file];
        self::assertSame((string) file_get_contents(self::fixture($fixture)), $first, 'regenerate fixtures if the writer changes on purpose');
    }

    /** @return array<string,array{0:callable,1:string,2:SchemaVersion}> */
    public static function badInput(): array
    {
        return [
            'missing name' => [static function (array $d): array {
                unset($d['name']);

                return $d;
            }, 'INPUT_REQUIRED_MISSING', SchemaVersion::V1_1],
            'unknown field' => [static function (array $d): array {
                $d['cases'][0]['paid'] = 1;

                return $d;
            }, 'INPUT_UNKNOWN_FIELD', SchemaVersion::V1_1],
            'amount with öre' => [static function (array $d): array {
                $d['cases'][0]['paid_amount'] = 1010.5;

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'amount as text' => [static function (array $d): array {
                $d['cases'][0]['paid_amount'] = '1010';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'name too long' => [static function (array $d): array {
                $d['name'] = 'Rut december 2026';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'flat-rate with hours' => [static function (array $d): array {
                $d['cases'][0]['work'][1]['hours'] = 1;

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'service of the other type' => [static function (array $d): array {
                $d['cases'][0]['work'][0]['service'] = 'BYGG';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'service twice' => [static function (array $d): array {
                $d['cases'][0]['work'][] = ['service' => 'STADNING', 'hours' => 1, 'material_cost' => 0];

                return $d;
            }, 'INPUT_DUPLICATE_SERVICE', SchemaVersion::V1_1],
            'hours missing' => [static function (array $d): array {
                unset($d['cases'][0]['work'][0]['hours']);

                return $d;
            }, 'INPUT_REQUIRED_MISSING', SchemaVersion::V1_1],
            '2027 answer missing in 1.1' => [static function (array $d): array {
                unset($d['cases'][0]['subcontractors']);

                return $d;
            }, 'INPUT_REQUIRED_MISSING', SchemaVersion::V1_1],
            '2027 answer not a boolean' => [static function (array $d): array {
                $d['cases'][0]['staffing_agency'] = 'no';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'performer missing in 1.1' => [static function (array $d): array {
                unset($d['performer']);

                return $d;
            }, 'INPUT_REQUIRED_MISSING', SchemaVersion::V1_1],
            'buyer check digit' => [static function (array $d): array {
                $d['cases'][0]['buyer'] = '198001022387';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'buyer is an organisation' => [static function (array $d): array {
                $d['cases'][0]['buyer'] = '167999000040';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'property for RUT' => [static function (array $d): array {
                $d['cases'][0]['property'] = ['designation' => 'X 1:1'];

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'date before 2009-07-01' => [static function (array $d): array {
                $d['cases'][0]['payment_date'] = '2009-06-30';

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'no cases' => [static function (array $d): array {
                $d['cases'] = [];

                return $d;
            }, 'INPUT_INVALID_VALUE', SchemaVersion::V1_1],
            'requested more than paid (rule)' => [static function (array $d): array {
                $d['cases'][1]['requested_amount'] = 800;

                return $d;
            }, 'REQUESTED_EXCEEDS_PAID', SchemaVersion::V1_1],
        ];
    }

    #[DataProvider('badInput')]
    public function test_bad_input_fails_safely_with_a_specific_code(callable $change, string $code, SchemaVersion $version): void
    {
        try {
            $this->generator()->generate($change(self::example('rut-v1.1.json')), $version, ValidationOptions::forDate('2027-01-12'));
            self::fail('expected GenerationFailed');
        } catch (GenerationFailed $e) {
            self::assertContains($code, array_map(static fn (Issue $i): string => $i->code, $e->issues));
        }
    }

    public function test_rot_input_needs_a_property(): void
    {
        $data = self::example('rot-v1.1.json');
        unset($data['cases'][0]['property']);

        $this->expectException(GenerationFailed::class);
        $this->generator()->generate($data, SchemaVersion::V1_1, ValidationOptions::forDate('2027-01-12'));
    }

    public function test_fields_a_version_cannot_carry_are_ignored_with_a_warning(): void
    {
        $result = $this->generator()->generate(self::example('rut-v1.1.json'), SchemaVersion::V6, ValidationOptions::forDate('2026-12-20'));

        $codes = array_map(static fn (Issue $i): string => $i->code, $result->inputWarnings);
        self::assertContains('INPUT_NOT_FOR_VERSION', $codes);
        self::assertStringNotContainsString('Underentreprenor', $result->xml);
        self::assertStringNotContainsString('Utforare', $result->xml);
    }

    public function test_invalid_json_is_reported(): void
    {
        try {
            Generator::decodeJson('{"name": ');
            self::fail('expected GenerationFailed');
        } catch (GenerationFailed $e) {
            self::assertSame('INPUT_INVALID_JSON', $e->issues[0]->code);
        }
    }

    public function test_round_trip_keeps_every_value(): void
    {
        $xml = $this->generator()->generate(self::example('rot-v1.1.json'), SchemaVersion::V1_1, ValidationOptions::forDate('2027-01-12'))->xml;

        foreach (['198001032385', '195601132334', '167999000040', '1102', 'EXEMPEL 1:23', 'MALNING_TAPETSERING', '<p:AntalTimmar>16</p:AntalTimmar>',
            '<p:Kostnad>2400</p:Kostnad>', '<p:OvrigKostnad>500</p:OvrigKostnad>', '<p:Arbetskostnad>10000</p:Arbetskostnad>',
            '<p:BetaltBelopp>7000</p:BetaltBelopp>', '<p:BegartBelopp>3000</p:BegartBelopp>', '<p:Underentreprenor>true</p:Underentreprenor>',
            '<p:KontaktEpost>support@example.com</p:KontaktEpost>'] as $expected) {
            self::assertStringContainsString($expected, $xml);
        }
    }
}
