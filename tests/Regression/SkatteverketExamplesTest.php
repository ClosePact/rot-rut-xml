<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Regression;

use Closepact\RotRutXml\Reporting\Severity;
use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Skatteverket's own example files, fetched with `schemas:fetch --include-samples`
 * (they are not redistributed here). They pin down how the toolkit reads the
 * real thing, including Skatteverket's choices this toolkit has to live with.
 */
final class SkatteverketExamplesTest extends TestCase
{
    /** @return array<string,array{0:string,1:string,2:list<string>,3:list<string>}> */
    public static function examples(): array
    {
        $placeholders = ['BUYER_ID_INVALID', 'PERFORMER_ID_INVALID'];

        return [
            // V6: real test personnummer; schema-valid and rule-valid.
            'V6 ROT, three buyers' => ['skattered/begaran/exempel_rot_3st.xml', '2026-11-18', [], []],
            // Case 2 buys only flat-rate services: see HOURS_AND_MATERIAL_FLAT_RATE_ONLY.
            'V6 RUT, three buyers' => ['skattered/begaran/exempel_rut_3st.xml', '2026-11-18', [], ['HOURS_AND_MATERIAL_FLAT_RATE_ONLY']],
            // 1.x: Skatteverket writes 000000000000 for every identity number.
            '1.0 ROT' => ['skattered/begaran/V1/Rot_V1.0.xml', '2026-11-18', $placeholders, []],
            '1.0 RUT' => ['skattered/begaran/V1/Rut_V1.0.xml', '2026-11-18', $placeholders, []],
            '1.1 ROT' => ['skattered/begaran/V1.1/Rot_V1.1.xml', '2027-01-15', $placeholders, ['LIMITS_NOT_CHECKED']],
            '1.1 RUT' => ['skattered/begaran/V1.1/Rut_V1.1.xml', '2027-01-15', $placeholders, ['LIMITS_NOT_CHECKED']],
        ];
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $notices
     */
    #[DataProvider('examples')]
    public function test_skatteverkets_examples(string $file, string $asOf, array $errors, array $notices): void
    {
        $store = $this->requireSchemas();
        if (! is_file($store->pathOf($file))) {
            self::markTestSkipped("{$file} not fetched (run schemas:fetch --include-samples).");
        }

        $result = (new Validator($store))->validateFile($store->pathOf($file), ValidationOptions::forDate($asOf));

        self::assertSame('passed', $result->schemaStatus, 'every Skatteverket example follows its own schema');
        $sorted = static function (array $codes): array {
            $codes = array_values(array_unique($codes));
            sort($codes);

            return $codes;
        };
        self::assertSame($sorted($errors), $sorted($result->codes(Severity::Error)));
        self::assertSame($sorted($notices), $sorted($result->codes(Severity::Notice)));
        self::assertSame([], $result->warnings());
    }
}
