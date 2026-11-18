<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Unit;

use Closepact\RotRutXml\Identity\IdentityNumber;
use Closepact\RotRutXml\Tests\TestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Real-looking numbers here come from Skatteverket's published test sets
 * (Testpersonnummer, Testsamordningsnummer; CC0) or from Skatteverket's own
 * documents (SKV 704's worked example, the BRF number in Skatteverket's V6
 * example file). Numbers built inside a test are built to be impossible
 * (a date that does not exist), never to look like someone.
 */
final class IdentityNumberTest extends TestCase
{
    private static function asOf(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-18');
    }

    /** @return array<string,array{0:string,1:string,2:?int,3:string}> */
    public static function valid(): array
    {
        return [
            'test personnummer, 12 digits' => ['198001022386', IdentityNumber::PERSONNUMMER, 1980, '198001022386'],
            'test personnummer, 10 digits' => ['8001022386', IdentityNumber::PERSONNUMMER, 1980, '198001022386'],
            'test personnummer, hyphen' => ['800102-2386', IdentityNumber::PERSONNUMMER, 1980, '198001022386'],
            'test personnummer, 12 with hyphen' => ['19800102-2386', IdentityNumber::PERSONNUMMER, 1980, '198001022386'],
            'test personnummer, born 2009' => ['200901012385', IdentityNumber::PERSONNUMMER, 2009, '200901012385'],
            'SKV 704 worked example' => ['640823-3234', IdentityNumber::PERSONNUMMER, 1964, '196408233234'],
            'plus sign: 100 years or older' => ['640823+3234', IdentityNumber::PERSONNUMMER, 1864, '186408233234'],
            'test samordningsnummer' => ['198001662397', IdentityNumber::SAMORDNINGSNUMMER, 1980, '198001662397'],
            'test samordningsnummer, unknown month and day' => ['198000602394', IdentityNumber::SAMORDNINGSNUMMER, 1980, '198000602394'],
            'organisationsnummer, 16 prefix (Skatteverket V6 example)' => ['167999000040', IdentityNumber::ORGANISATIONSNUMMER, null, '167999000040'],
            'organisationsnummer, 10 digits' => ['7999000040', IdentityNumber::ORGANISATIONSNUMMER, null, '167999000040'],
            'organisationsnummer, hyphen' => ['799900-0040', IdentityNumber::ORGANISATIONSNUMMER, null, '167999000040'],
        ];
    }

    #[DataProvider('valid')]
    public function test_formally_correct_numbers(string $raw, string $kind, ?int $birthYear, string $twelve): void
    {
        $number = IdentityNumber::parse($raw, self::asOf());

        self::assertInstanceOf(IdentityNumber::class, $number);
        self::assertSame($kind, $number->kind);
        self::assertSame($birthYear, $number->birthYear);
        self::assertSame($twelve, $number->twelve);
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function invalid(): array
    {
        return [
            'letters' => ['19800102238X', 'format'],
            'too short' => ['800102238', 'format'],
            'two separators' => ['800102--2386', 'format'],
            'wrong check digit' => ['198001022387', 'check_digit'],
            'organisation, wrong check digit' => ['167999000041', 'check_digit'],
            'organisation, wrong prefix' => ['177999000040', 'org_prefix'],
            'Skatteverket placeholder zeros' => ['000000000000', 'date'],
            'month 13' => [self::withCheckDigit('801301238'), 'date'],
            'February 30' => [self::withCheckDigit('800230238'), 'date'],
            'century 17 (test number)' => ['178001022386', 'date'],
        ];
    }

    #[DataProvider('invalid')]
    public function test_numbers_that_are_not_formally_correct(string $raw, string $reason): void
    {
        self::assertSame($reason, IdentityNumber::parse($raw, self::asOf()));
    }

    public function test_a_twelve_digit_number_born_after_the_reference_date_is_not_correct(): void
    {
        // Test personnummer born 2009-01-01, checked as if it were 2008.
        self::assertSame('date', IdentityNumber::parse('200901012385', new DateTimeImmutable('2008-06-01')));
    }

    public function test_ten_digits_resolve_to_the_latest_century_not_in_the_future(): void
    {
        // Test personnummer 800102-2386 (born 2 January), read on 1 and on 2 January 1980.
        $dayBefore = IdentityNumber::parse('8001022386', new DateTimeImmutable('1980-01-01'));
        $sameDay = IdentityNumber::parse('8001022386', new DateTimeImmutable('1980-01-02'));

        self::assertInstanceOf(IdentityNumber::class, $dayBefore);
        self::assertInstanceOf(IdentityNumber::class, $sameDay);
        self::assertSame(1880, $dayBefore->birthYear, 'a birthday later in the reference year belongs to the previous century');
        self::assertSame(1980, $sameDay->birthYear);
    }

    public function test_luhn_matches_skv_704_example(): void
    {
        self::assertTrue(IdentityNumber::luhnValid('6408233234'));
        self::assertFalse(IdentityNumber::luhnValid('6408233235'));
        self::assertFalse(IdentityNumber::luhnValid('640823323'));
    }

    /** Appends the modulus-10 check digit to nine digits (used only for impossible dates or relative-date tests). */
    private static function withCheckDigit(string $nine): string
    {
        for ($d = 0; $d <= 9; $d++) {
            if (IdentityNumber::luhnValid($nine.$d)) {
                return $nine.$d;
            }
        }

        throw new \LogicException('unreachable');
    }
}
