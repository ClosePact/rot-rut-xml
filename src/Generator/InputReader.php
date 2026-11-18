<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Generator;

use Closepact\RotRutXml\Identity\IdentityNumber;
use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Version\SchemaVersion;
use DateTimeImmutable;

/**
 * Checks generator input (docs/input-format.md) and normalises it.
 *
 * Deliberately stricter than the validator: a file the toolkit writes states
 * the hours and material for every service that has them, the other cost
 * whenever it is required, and both 2027 answers explicitly for 1.1 — nothing
 * is defaulted, because a default here would be a statement made on the
 * company's behalf.
 *
 * Limits on lengths and ranges are the XSD facets (NamnPaBegaran 16, FakturaNr
 * 20, LagenhetsNr 25, Fastighetsbeteckning 40, AntalTimmar 0–999, costs 0–9 999 999,
 * Arbetskostnad ≥ 2).
 */
final class InputReader
{
    private const TOP = ['name', 'type', 'performer', 'system_contact', 'cases'];

    private const CASE = ['invoice_number', 'buyer', 'property', 'work', 'other_cost', 'payment_date', 'labour_cost',
        'paid_amount', 'requested_amount', 'subcontractors', 'staffing_agency'];

    /** @var list<Issue> */
    private array $issues = [];

    /**
     * @param  array<mixed>  $input
     * @return array{0: array<string,mixed>|null, 1: list<Issue>}
     */
    public function read(array $input, SchemaVersion $version, DateTimeImmutable $asOf): array
    {
        $this->issues = [];
        $this->unknown($input, self::TOP, '');

        $out = [
            'name' => $this->string($input, 'name', 'name', 1, 16),
            'type' => $this->enum($input, 'type', 'type', [Request::ROT, Request::RUT]),
            'performer' => null,
            'contact' => null,
            'cases' => [],
        ];

        if ($version->carriesPerformer()) {
            $raw = $this->string($input, 'performer', 'performer', 10, 13);
            if ($raw !== null) {
                $number = IdentityNumber::parse($raw, $asOf);
                if (is_string($number)) {
                    $this->invalid('performer', 'person- eller organisationsnummer / personnummer or organisation number ('.$number.')');
                } else {
                    $out['performer'] = $number->twelve;
                }
            }
        } elseif (array_key_exists('performer', $input)) {
            $this->issues[] = new Issue('INPUT_NOT_FOR_VERSION', null, ['field' => 'performer', 'version' => $version->label()]);
        }

        if (array_key_exists('system_contact', $input)) {
            if ($version !== SchemaVersion::V1_1) {
                $this->issues[] = new Issue('INPUT_NOT_FOR_VERSION', null, ['field' => 'system_contact', 'version' => $version->label()]);
            } else {
                $out['contact'] = $this->contact($input['system_contact']);
            }
        }

        $cases = $input['cases'] ?? null;
        if (! is_array($cases) || ! array_is_list($cases) || $cases === []) {
            $this->issues[] = new Issue(is_array($cases) ? 'INPUT_INVALID_VALUE' : 'INPUT_REQUIRED_MISSING', null,
                ['field' => 'cases', 'expected' => 'lista med 1–100 köpare / list of 1–100 buyers']);
        } else {
            foreach ($cases as $i => $case) {
                $out['cases'][] = $this->case($case, "cases[{$i}]", $out['type'], $version, $asOf);
            }
        }

        return [$this->hasErrors() ? null : $out, $this->issues];
    }

    /** @return array<string,mixed> */
    private function case(mixed $case, string $at, ?string $type, SchemaVersion $version, DateTimeImmutable $asOf): array
    {
        if (! is_array($case)) {
            $this->invalid($at, 'objekt / object');

            return [];
        }
        $this->unknown($case, self::CASE, $at.'.');

        $buyer = null;
        $raw = $this->string($case, 'buyer', $at.'.buyer', 10, 13);
        if ($raw !== null) {
            $number = IdentityNumber::parse($raw, $asOf);
            if (is_string($number) || ! $number->isPerson()) {
                $this->invalid($at.'.buyer', 'personnummer/samordningsnummer'.(is_string($number) ? ' ('.$number.')' : ''));
            } else {
                $buyer = $number->twelve;
            }
        }

        $work = [];
        $nonFlat = false;
        $seen = [];
        $items = $case['work'] ?? null;
        if (! is_array($items) || ! array_is_list($items) || $items === []) {
            $this->issues[] = new Issue(is_array($items) ? 'INPUT_INVALID_VALUE' : 'INPUT_REQUIRED_MISSING', null,
                ['field' => $at.'.work', 'expected' => 'minst en tjänst / at least one service']);
        } else {
            foreach ($items as $j => $item) {
                $path = "{$at}.work[{$j}]";
                if (! is_array($item)) {
                    $this->invalid($path, 'objekt / object');

                    continue;
                }
                $this->unknown($item, ['service', 'hours', 'material_cost'], $path.'.');
                $code = $this->string($item, 'service', $path.'.service', 1, 50);
                if ($code === null) {
                    continue;
                }
                if ($type !== null && ServiceCatalog::typeOf($code) !== $type) {
                    $this->invalid($path.'.service', implode(', ', ServiceCatalog::codesFor($type)));

                    continue;
                }
                if (isset($seen[$code])) {
                    $this->issues[] = new Issue('INPUT_DUPLICATE_SERVICE', null, ['field' => $path.'.service', 'value' => $code]);

                    continue;
                }
                $seen[$code] = true;
                if (ServiceCatalog::isFlatRate($code)) {
                    foreach (['hours', 'material_cost'] as $field) {
                        if (array_key_exists($field, $item)) {
                            $this->invalid("{$path}.{$field}", 'ingenting – schablontjänst / nothing – flat-rate service');
                        }
                    }
                    $work[] = ['service' => $code, 'hours' => null, 'material' => null];

                    continue;
                }
                $nonFlat = true;
                $work[] = [
                    'service' => $code,
                    'hours' => $this->int($item, 'hours', $path.'.hours', 0, 999),
                    'material' => $this->int($item, 'material_cost', $path.'.material_cost', 0, 9_999_999),
                ];
            }
        }

        $property = null;
        if ($type === Request::ROT) {
            $property = $this->property($case['property'] ?? null, $at.'.property', $asOf);
        } elseif (array_key_exists('property', $case)) {
            $this->invalid($at.'.property', 'ingenting för RUT / nothing for RUT');
        }

        $declarations = ['subcontractors' => null, 'staffing_agency' => null];
        foreach (array_keys($declarations) as $field) {
            if ($version->carriesDeclarations()) {
                $declarations[$field] = $this->bool($case, $field, "{$at}.{$field}");
            } elseif (array_key_exists($field, $case)) {
                $this->issues[] = new Issue('INPUT_NOT_FOR_VERSION', null, ['field' => "{$at}.{$field}", 'version' => $version->label()]);
            }
        }

        $invoice = array_key_exists('invoice_number', $case) ? $this->string($case, 'invoice_number', $at.'.invoice_number', 1, 20) : null;
        $otherCost = $nonFlat || array_key_exists('other_cost', $case) ? $this->int($case, 'other_cost', $at.'.other_cost', 0, 9_999_999) : null;

        return [
            'invoice' => $invoice,
            'buyer' => $buyer,
            'property' => $property,
            'work' => $work,
            'other_cost' => $otherCost,
            'payment_date' => $this->date($case, 'payment_date', $at.'.payment_date'),
            'labour_cost' => $this->int($case, 'labour_cost', $at.'.labour_cost', 2, 99_999_999_999),
            'paid' => $this->int($case, 'paid_amount', $at.'.paid_amount', 0, 99_999_999_999),
            'requested' => $this->int($case, 'requested_amount', $at.'.requested_amount', 0, 99_999_999_999),
            'subcontractors' => $declarations['subcontractors'],
            'staffing_agency' => $declarations['staffing_agency'],
        ];
    }

    /** @return array{designation:?string,apartment:?string,brf:?string}|null */
    private function property(mixed $property, string $at, DateTimeImmutable $asOf): ?array
    {
        if (! is_array($property)) {
            $this->issues[] = new Issue('INPUT_REQUIRED_MISSING', null, ['field' => $at]);

            return null;
        }
        $this->unknown($property, ['designation', 'apartment_number', 'brf_org_number'], $at.'.');

        if (array_key_exists('designation', $property)) {
            if (array_key_exists('apartment_number', $property) || array_key_exists('brf_org_number', $property)) {
                $this->invalid($at, 'antingen designation eller apartment_number + brf_org_number / either designation or apartment_number + brf_org_number');
            }

            return ['designation' => $this->string($property, 'designation', $at.'.designation', 1, 40), 'apartment' => null, 'brf' => null];
        }

        $apartment = $this->string($property, 'apartment_number', $at.'.apartment_number', 1, 25);
        $raw = $this->string($property, 'brf_org_number', $at.'.brf_org_number', 10, 13);
        $brf = null;
        if ($raw !== null) {
            $number = IdentityNumber::parse($raw, $asOf);
            if (is_string($number) || $number->isPerson()) {
                $this->invalid($at.'.brf_org_number', 'organisationsnummer / organisation number');
            } else {
                $brf = $number->twelve;
            }
        }

        return ['designation' => null, 'apartment' => $apartment, 'brf' => $brf];
    }

    /** @return array{name:?string,product:?string,emails:list<string>}|null */
    private function contact(mixed $contact): ?array
    {
        if (! is_array($contact)) {
            $this->invalid('system_contact', 'objekt / object');

            return null;
        }
        $this->unknown($contact, ['name', 'product', 'emails'], 'system_contact.');
        $emails = $contact['emails'] ?? null;
        $valid = [];
        if (! is_array($emails) || $emails === [] || count($emails) > 10) {
            $this->invalid('system_contact.emails', '1–10 e-postadresser / 1–10 email addresses');
        } else {
            foreach (array_values($emails) as $k => $email) {
                if (! is_string($email) || preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email) !== 1 || strlen($email) > 254) {
                    $this->invalid("system_contact.emails[{$k}]", 'e-postadress / email address');
                } else {
                    $valid[] = $email;
                }
            }
        }

        return [
            'name' => $this->string($contact, 'name', 'system_contact.name', 1, 100),
            'product' => $this->string($contact, 'product', 'system_contact.product', 1, 100),
            'emails' => $valid,
        ];
    }

    // --- primitives ---------------------------------------------------------------------

    /** @param array<mixed> $data */
    private function string(array $data, string $key, string $path, int $min, int $max): ?string
    {
        if (! array_key_exists($key, $data)) {
            $this->issues[] = new Issue('INPUT_REQUIRED_MISSING', null, ['field' => $path]);

            return null;
        }
        $value = $data[$key];
        if (! is_string($value) || trim($value) === '' || self::length(trim($value)) < $min || self::length(trim($value)) > $max) {
            $this->invalid($path, "text, {$min}–{$max} tecken / characters");

            return null;
        }

        return trim($value);
    }

    /** @param array<mixed> $data */
    private function int(array $data, string $key, string $path, int $min, int $max): ?int
    {
        if (! array_key_exists($key, $data)) {
            $this->issues[] = new Issue('INPUT_REQUIRED_MISSING', null, ['field' => $path]);

            return null;
        }
        $value = $data[$key];
        if (! is_int($value) || $value < $min || $value > $max) {
            $this->invalid($path, "heltal i hela kronor/timmar {$min}–{$max} / whole number {$min}–{$max}");

            return null;
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private function bool(array $data, string $key, string $path): ?bool
    {
        if (! array_key_exists($key, $data)) {
            $this->issues[] = new Issue('INPUT_REQUIRED_MISSING', null, ['field' => $path]);

            return null;
        }
        if (! is_bool($data[$key])) {
            $this->invalid($path, 'true/false');

            return null;
        }

        return $data[$key];
    }

    /**
     * @param  array<mixed>  $data
     * @param  list<string>  $allowed
     */
    private function enum(array $data, string $key, string $path, array $allowed): ?string
    {
        $value = $this->string($data, $key, $path, 1, 50);
        if ($value !== null && ! in_array($value, $allowed, true)) {
            $this->invalid($path, implode(' / ', $allowed));

            return null;
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private function date(array $data, string $key, string $path): ?string
    {
        $value = $this->string($data, $key, $path, 10, 10);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || $value < '2009-07-01') {
            $this->invalid($path, 'ÅÅÅÅ-MM-DD från 2009-07-01 / YYYY-MM-DD from 2009-07-01');

            return null;
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     * @param  list<string>  $known
     */
    private function unknown(array $data, array $known, string $prefix): void
    {
        foreach (array_keys($data) as $key) {
            if (! in_array($key, $known, true)) {
                $this->issues[] = new Issue('INPUT_UNKNOWN_FIELD', null, ['field' => $prefix.$key]);
            }
        }
    }

    /** Characters, not bytes, without needing ext-mbstring. */
    private static function length(string $value): int
    {
        return (int) preg_match_all('/./us', $value);
    }

    private function invalid(string $path, string $expected): void
    {
        $this->issues[] = new Issue('INPUT_INVALID_VALUE', null, ['field' => $path, 'expected' => $expected]);
    }

    private function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity()->value === 'error') {
                return true;
            }
        }

        return false;
    }
}
