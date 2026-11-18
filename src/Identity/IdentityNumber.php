<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Identity;

use DateTimeImmutable;

/**
 * A Swedish personnummer, samordningsnummer or organisationsnummer, checked for
 * form only.
 *
 * "Formally correct" (Skatteverket's "formellt korrekt") means: the right number
 * of digits, a date that exists (personnummer) or a day plus 60 (samordningsnummer,
 * where an unknown month or day is written 00 / 60), a third digit of at least 2
 * (organisationsnummer), and a check digit computed with the modulus-10 method
 * (weights 2 and 1). It says nothing about whether the number is assigned to
 * anyone. Sources: resources/sources.json (skv-704, skv-pisa-faltlista).
 *
 * Accepted input: 10 or 12 digits, optionally with `-` or `+` before the last
 * four. A 12-digit organisationsnummer carries the fictitious century digits 16.
 */
final readonly class IdentityNumber
{
    public const PERSONNUMMER = 'personnummer';

    public const SAMORDNINGSNUMMER = 'samordningsnummer';

    public const ORGANISATIONSNUMMER = 'organisationsnummer';

    private function __construct(
        public string $kind,
        /** The ten digits the check digit is computed over. */
        public string $digits10,
        /** Year of birth, for persons. */
        public ?int $birthYear,
        /** Twelve-digit form: century + ten digits, or 16 + ten digits for organisations. */
        public string $twelve,
    ) {}

    /**
     * @return self|string the number, or the reason it is not formally correct:
     *                     `format`, `check_digit`, `date`, `org_prefix`
     */
    public static function parse(string $raw, DateTimeImmutable $asOf): self|string
    {
        if (preg_match('/^(\d{6}|\d{8})([-+]?)(\d{4})$/', trim($raw), $m) !== 1) {
            return 'format';
        }

        $head = $m[1];
        $separator = $m[2];
        $digits = $head.$m[3];
        $ten = substr($digits, -10);
        $prefix = strlen($digits) === 12 ? substr($digits, 0, 2) : null;

        if (! self::luhnValid($ten)) {
            return 'check_digit';
        }

        $isOrganisation = (int) $ten[2] >= 2;

        if ($isOrganisation) {
            if ($prefix !== null && $prefix !== '16') {
                return 'org_prefix';
            }

            return new self(self::ORGANISATIONSNUMMER, $ten, null, '16'.$ten);
        }

        if ($prefix === '16') {
            return 'org_prefix';
        }

        $yy = (int) substr($ten, 0, 2);
        $month = (int) substr($ten, 2, 2);
        $day = (int) substr($ten, 4, 2);
        $coordination = $day >= 60;

        if ($prefix !== null && ! in_array($prefix, ['18', '19', '20'], true)) {
            return 'date';
        }
        $year = $prefix !== null
            ? ((int) $prefix) * 100 + $yy
            : self::inferYear($yy, $coordination ? null : [$month, $day], $separator === '+', $asOf);

        if ($coordination) {
            // Samordningsnummer: day + 60; Skatteverket's own test set writes an
            // unknown month as 00 and an unknown day as 60.
            $realDay = $day - 60;
            $validDay = $realDay === 0 || ($month === 0 ? $realDay <= 31 : ($month <= 12 && checkdate($month, $realDay, $year)));
            if ($month > 12 || $realDay > 31 || ! $validDay) {
                return 'date';
            }
        } elseif ($month < 1 || $month > 12 || ! checkdate($month, $day, $year)) {
            return 'date';
        }

        if (! $coordination && $prefix !== null) {
            $born = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
            if ($born === false || $born > $asOf) {
                return 'date';
            }
        }

        return new self(
            $coordination ? self::SAMORDNINGSNUMMER : self::PERSONNUMMER,
            $ten,
            $year,
            sprintf('%04d', $year).substr($ten, 2),
        );
    }

    public function isPerson(): bool
    {
        return $this->kind !== self::ORGANISATIONSNUMMER;
    }

    /** Modulus 10 with weights 2,1,2,… over the first nine digits; the tenth is the check digit. */
    public static function luhnValid(string $ten): bool
    {
        if (preg_match('/^\d{10}$/', $ten) !== 1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $product = (int) $ten[$i] * ($i % 2 === 0 ? 2 : 1);
            $sum += $product > 9 ? $product - 9 : $product;
        }

        return (10 - $sum % 10) % 10 === (int) $ten[9];
    }

    /**
     * Century for a ten-digit number: the most recent year that is not in the
     * future, or a hundred years earlier when the separator is `+` (Skatteverket:
     * the hyphen becomes a plus the year a person turns 100).
     */
    /** @param array{0:int,1:int}|null $monthDay */
    private static function inferYear(int $yy, ?array $monthDay, bool $centenarian, DateTimeImmutable $asOf): int
    {
        $current = (int) $asOf->format('Y');
        $year = intdiv($current, 100) * 100 + $yy;
        $inFuture = $year > $current;
        if (! $inFuture && $year === $current && $monthDay !== null) {
            $inFuture = sprintf('%02d-%02d', $monthDay[0], $monthDay[1]) > $asOf->format('m-d');
        }
        if ($inFuture) {
            $year -= 100;
        }

        return $centenarian ? $year - 100 : $year;
    }
}
