<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Regression;

use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;

/** A full file (100 buyers, the maximum) validates quickly; the budget is generous so slow CI runners pass. */
final class PerformanceTest extends TestCase
{
    public function test_a_hundred_buyer_file_validates_well_within_a_second(): void
    {
        $validator = new Validator(self::schemaStore() ?? self::emptyStore());
        $validator->validateFile(self::fixture('v6/hundred-buyers.xml'), ValidationOptions::forDate('2026-11-18')); // warm-up

        $start = hrtime(true);
        $result = $validator->validateFile(self::fixture('v6/hundred-buyers.xml'), ValidationOptions::forDate('2026-11-18'));
        $ms = (hrtime(true) - $start) / 1e6;

        self::assertTrue($result->isValid());
        self::assertLessThan(1000, $ms, sprintf('took %.1f ms', $ms));
    }
}
