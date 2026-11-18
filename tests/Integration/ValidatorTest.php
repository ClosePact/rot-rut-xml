<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Integration;

use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;

final class ValidatorTest extends TestCase
{
    /** @return list<string> */
    private function validate(string $xml, string $asOf = '2027-01-12', ?SchemaVersion $expect = null, bool $policy = false): array
    {
        $result = (new Validator(self::emptyStore()))->validateString($xml, ValidationOptions::forDate($asOf, $expect, $policy));

        return $result->codes();
    }

    public function test_version_mismatch_when_another_version_is_expected(): void
    {
        $xml = (string) file_get_contents(self::fixture('v6/rut-cleaning-valid.xml'));

        self::assertContains('VERSION_MISMATCH', $this->validate($xml, '2026-11-18', SchemaVersion::V1_1));
        self::assertNotContains('VERSION_MISMATCH', $this->validate($xml, '2026-11-18', SchemaVersion::V6));
    }

    public function test_a_v6_case_without_work_needs_a_service(): void
    {
        $xml = (string) file_get_contents(self::fixture('v6/rut-cleaning-valid.xml'));
        $xml = (string) preg_replace('#<ns2:UtfortArbete>.*?</ns2:UtfortArbete>#s', '', $xml, 1);

        self::assertContains('SERVICE_REQUIRED', $this->validate($xml, '2026-11-18'));
    }

    public function test_policy_checks_for_zero_requests_duplicate_invoices_and_ambiguous_property(): void
    {
        $rut = (string) file_get_contents(self::fixture('v1_1/rut-cleaning-valid.xml'));
        $rut = str_replace(['2026-1202', '<p:BegartBelopp>750</p:BegartBelopp>'], ['2026-1201', '<p:BegartBelopp>0</p:BegartBelopp>'], $rut);
        $codes = $this->validate($rut, policy: true);
        self::assertContains('POLICY_INVOICE_NUMBER_DUPLICATED', $codes);
        self::assertContains('POLICY_REQUESTED_ZERO', $codes);
        self::assertNotContains('POLICY_REQUESTED_ZERO', $this->validate($rut), 'policy checks are opt-in');

        $rot = (string) file_get_contents(self::fixture('v1_1/rot-brf-and-house-valid.xml'));
        $rot = str_replace('<p:LagenhetsNr>1102</p:LagenhetsNr>', '<p:Fastighetsbeteckning>EXEMPEL 2:1</p:Fastighetsbeteckning>'."\n".'      <p:LagenhetsNr>1102</p:LagenhetsNr>', $rot);
        self::assertContains('POLICY_PROPERTY_AMBIGUOUS', $this->validate($rot, policy: true));
    }

    public function test_xsi_nil_elements_count_as_absent_in_v6(): void
    {
        $xml = (string) file_get_contents(self::fixture('v6/rut-cleaning-valid.xml'));
        $xml = str_replace('<ns1:Begaran ', '<ns1:Begaran xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ', $xml);
        $xml = (string) preg_replace('#<ns2:Ovrigkostnad>\d+</ns2:Ovrigkostnad>#', '<ns2:Ovrigkostnad xsi:nil="true"/>', $xml, 1);

        self::assertContains('OTHER_COST_REQUIRED', $this->validate($xml, '2026-11-18'));
    }

    public function test_result_serialises_with_sources_and_disclaimer(): void
    {
        $xml = (string) file_get_contents(self::fixture('v1_1/requested-exceeds-paid.xml'));
        $result = (new Validator(self::emptyStore()))->validateString($xml, ValidationOptions::forDate('2027-01-12'));
        $array = $result->toArray();

        self::assertFalse($array['valid']);
        self::assertSame('v1.1', $array['version']);
        self::assertSame('skipped', $array['schema']);
        $error = $array['errors'][0];
        self::assertSame('REQUESTED_EXCEEDS_PAID', $error['code']);
        self::assertSame('/Begaran/Arende[2]/BegartBelopp', $error['path']);
        self::assertSame(2, $error['claim']);
        self::assertStringContainsString('900', $error['message_sv']);
        self::assertStringStartsWith('https://www.skatteverket.se/', $error['sources'][0]['url']);
        self::assertArrayHasKey('sv', $array['disclaimer']);
    }
}
