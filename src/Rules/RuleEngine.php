<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

use Closepact\RotRutXml\Identity\IdentityNumber;
use Closepact\RotRutXml\Model\BuyerClaim;
use Closepact\RotRutXml\Model\Property;
use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Version\SchemaVersion;

/**
 * The rules a primary source states, beyond what the XSD can express.
 *
 * Each check names its code; the code's text, severity and sources are in
 * RuleCatalog and docs/rules.md. A check that cannot read a value (because the
 * file is broken) skips quietly: the structure or schema findings already say
 * what is wrong, and a second guess on top would only add noise.
 */
final class RuleEngine
{
    public const MAX_CLAIMS = 100;

    /** @return list<Issue> */
    public function check(Request $request, ValidationContext $context): array
    {
        $issues = [];
        $type = $request->type;
        $rotOrRut = $type === Request::ROT || $type === Request::RUT;

        if ($request->version !== SchemaVersion::V6 && $type !== null && ! $rotOrRut) {
            $issues[] = new Issue('REQUEST_TYPE_NOT_ROT_RUT', '/Begaran/TypAvBegaran', ['value' => $type]);
        }

        $count = count($request->claims);
        if ($count === 0) {
            $issues[] = new Issue('CLAIMS_REQUIRED', $request->claimsPath);
        } elseif ($count > self::MAX_CLAIMS) {
            $issues[] = new Issue('CLAIMS_TOO_MANY', $request->claimsPath, ['count' => $count]);
        }

        $performer = null;
        if ($request->version->carriesPerformer() && $request->performer !== null) {
            $performer = IdentityNumber::parse($request->performer, $context->asOf);
            if (is_string($performer)) {
                $issues[] = new Issue('PERFORMER_ID_INVALID', '/Begaran/Utforare', ['reason' => $performer]);
                $performer = null;
            }
        }

        foreach ($request->claims as $claim) {
            array_push($issues, ...$this->claim($request, $claim, $performer, $rotOrRut, $context));
        }

        array_push($issues, ...$this->paymentYears($request, $context));
        if ($rotOrRut) {
            array_push($issues, ...$this->annualLimits($request, $context));
        }
        array_push($issues, ...$this->transition($request, $context));

        if ($context->policy) {
            array_push($issues, ...$this->policy($request));
        }

        return $issues;
    }

    /** @return list<Issue> */
    private function claim(Request $request, BuyerClaim $claim, ?IdentityNumber $performer, bool $rotOrRut, ValidationContext $context): array
    {
        $issues = [];
        $n = $claim->index;

        // Buyer
        if ($claim->buyer !== null) {
            $buyer = IdentityNumber::parse($claim->buyer, $context->asOf);
            if (is_string($buyer)) {
                $issues[] = new Issue('BUYER_ID_INVALID', $claim->path('buyer'), ['claim' => $n, 'reason' => $buyer], claim: $n);
            } elseif (! $buyer->isPerson()) {
                $issues[] = new Issue('BUYER_NOT_A_PERSON', $claim->path('buyer'), ['claim' => $n], claim: $n);
            } else {
                $year = $claim->paymentYear();
                if ($year !== null && $buyer->birthYear !== null && $year - $buyer->birthYear < 18) {
                    $issues[] = new Issue('BUYER_UNDER_18', $claim->path('buyer'), ['claim' => $n, 'year' => $year], claim: $n);
                }
                if ($performer !== null && $performer->digits10 === $buyer->digits10) {
                    $issues[] = new Issue('BUYER_IS_PERFORMER', $claim->path('buyer'), ['claim' => $n], claim: $n);
                }
            }
        }

        // Work
        if ($claim->work === []) {
            $issues[] = new Issue('SERVICE_REQUIRED', $claim->path('work'), ['claim' => $n], claim: $n);
        }

        $hasHoursAndMaterial = false;
        $anyHoursOrMaterial = false;
        $onlyFlatRate = $claim->work !== [];
        $unknownService = false;
        foreach ($claim->work as $item) {
            if ($request->version !== SchemaVersion::V6 && $rotOrRut && ServiceCatalog::typeOf($item->rawService) !== $request->type) {
                $issues[] = new Issue('SERVICE_CODE_INVALID', $item->path, ['claim' => $n, 'value' => $item->rawService, 'type' => (string) $request->type], claim: $n);
                $unknownService = true;

                continue;
            }
            $flat = $item->service !== null && ServiceCatalog::isFlatRate($item->service);
            if ($flat && ($item->hours !== null || $item->materialCost !== null)) {
                $issues[] = new Issue('FLAT_RATE_HAS_HOURS_OR_COST', $item->path, ['claim' => $n, 'value' => $item->rawService], claim: $n);
            }
            if (! $flat) {
                $onlyFlatRate = false;
                $hasHoursAndMaterial = $hasHoursAndMaterial || ($item->hours !== null && $item->materialCost !== null);
            }
            $anyHoursOrMaterial = $anyHoursOrMaterial || $item->hours !== null || $item->materialCost !== null;
        }

        if ($claim->work !== [] && ! $hasHoursAndMaterial && ! $unknownService) {
            $issues[] = new Issue($onlyFlatRate ? 'HOURS_AND_MATERIAL_FLAT_RATE_ONLY' : 'HOURS_AND_MATERIAL_REQUIRED', $claim->path('work'), ['claim' => $n], claim: $n);
        }
        if ($anyHoursOrMaterial && $claim->otherCost === null) {
            $issues[] = new Issue('OTHER_COST_REQUIRED', $claim->path('otherCost'), ['claim' => $n], claim: $n);
        }

        // Amounts
        if ($request->version !== SchemaVersion::V6 && $rotOrRut && $claim->labourCost === null) {
            $issues[] = new Issue('LABOUR_COST_REQUIRED', $claim->path('labourCost'), ['claim' => $n], claim: $n);
        }
        $requested = $claim->requestedAmount;
        $paid = $claim->paidAmount;
        if ($requested !== null && $paid !== null) {
            if ($requested > $paid) {
                $issues[] = new Issue('REQUESTED_EXCEEDS_PAID', $claim->path('requestedAmount'), ['claim' => $n, 'requested' => $requested, 'paid' => $paid], claim: $n);
            }
            if ($claim->labourCost !== null && $requested + $paid > $claim->labourCost) {
                $issues[] = new Issue('REQUESTED_PLUS_PAID_EXCEEDS_LABOUR', $claim->path('requestedAmount'),
                    ['claim' => $n, 'requested' => $requested, 'paid' => $paid, 'labour' => $claim->labourCost], claim: $n);
            }
            $limits = $claim->paymentDate !== null ? AmountLimits::forPaymentDate($claim->paymentDate) : null;
            if ($limits !== null && $request->isRot()) {
                [$numerator, $denominator] = $limits['rotShare'];
                if ($requested * $denominator > $paid * $numerator) {
                    $issues[] = new Issue('ROT_REQUESTED_EXCEEDS_SHARE', $claim->path('requestedAmount'),
                        ['claim' => $n, 'requested' => $requested, 'max' => intdiv($paid * $numerator, $denominator)], claim: $n);
                }
            }
        }

        // Dates
        if ($claim->paymentDate !== null && $claim->paymentDate > $context->asOfDate()) {
            $issues[] = new Issue('PAYMENT_DATE_AFTER_SUBMISSION', $claim->path('paymentDate'),
                ['claim' => $n, 'date' => $claim->paymentDate, 'asOf' => $context->asOfDate()], claim: $n);
        }

        // Property
        if ($request->isRot()) {
            array_push($issues, ...$this->rotProperty($claim, $context));
        } elseif ($request->isRut() && $claim->property !== null && ! $claim->property->isEmpty()) {
            $issues[] = new Issue('PROPERTY_NOT_FOR_RUT', $claim->path('property'), ['claim' => $n], claim: $n);
        }

        return $issues;
    }

    /** @return list<Issue> */
    private function rotProperty(BuyerClaim $claim, ValidationContext $context): array
    {
        $n = $claim->index;
        $property = $claim->property;
        if ($property === null || $property->isEmpty()) {
            return [new Issue('ROT_PROPERTY_REQUIRED', $claim->path('property'), ['claim' => $n], claim: $n)];
        }

        $issues = [];
        $designation = ! Property::blank($property->designation);
        $apartment = ! Property::blank($property->apartmentNumber);
        $brf = ! Property::blank($property->brfOrgNumber);

        if (! $designation && $apartment !== $brf) {
            $issues[] = new Issue('ROT_APARTMENT_INCOMPLETE', $claim->path($apartment ? 'brfOrgNumber' : 'apartmentNumber'),
                ['claim' => $n, 'missing' => $apartment ? 'BrfOrgNr' : 'LagenhetsNr'], claim: $n);
        }

        if ($brf) {
            $number = IdentityNumber::parse((string) $property->brfOrgNumber, $context->asOf);
            $reason = is_string($number) ? $number : ($number->isPerson() ? 'not_organisation' : null);
            if ($reason !== null) {
                $issues[] = new Issue('BRF_ORGNR_INVALID', $claim->path('brfOrgNumber'), ['claim' => $n, 'reason' => $reason], claim: $n);
            }
        }

        return $issues;
    }

    /** @return list<Issue> */
    private function paymentYears(Request $request, ValidationContext $context): array
    {
        $issues = [];
        $first = [];
        foreach ($request->claims as $claim) {
            $year = $claim->paymentYear();
            if ($year !== null && ! isset($first[$year])) {
                $first[$year] = $claim;
            }
        }
        ksort($first);

        if (count($first) > 1) {
            $issues[] = new Issue('PAYMENT_YEAR_MIXED', $request->claimsPath, ['years' => implode(', ', array_keys($first))]);
        }

        foreach ($first as $year => $claim) {
            $deadline = sprintf('%04d-01-31', $year + 1);
            if ($context->asOfDate() > $deadline) {
                $issues[] = new Issue('DEADLINE_PASSED', $claim->path('paymentDate'),
                    ['claim' => $claim->index, 'year' => $year, 'deadline' => $deadline], claim: $claim->index);
            }
        }

        return $issues;
    }

    /** @return list<Issue> */
    private function annualLimits(Request $request, ValidationContext $context): array
    {
        $issues = [];
        $sums = [];
        $firstClaim = [];
        $unchecked = [];
        foreach ($request->claims as $claim) {
            if ($claim->buyer === null || $claim->requestedAmount === null || $claim->paymentDate === null) {
                continue;
            }
            $limits = AmountLimits::forPaymentDate($claim->paymentDate);
            if ($limits === null) {
                $unchecked[(int) substr($claim->paymentDate, 0, 4)] = true;

                continue;
            }
            $buyer = IdentityNumber::parse($claim->buyer, $context->asOf);
            if (is_string($buyer)) {
                continue;
            }
            $key = $buyer->digits10.'|'.substr($claim->paymentDate, 0, 4);
            $sums[$key] = ($sums[$key] ?? 0) + $claim->requestedAmount;
            $firstClaim[$key] ??= [$claim, $limits];
        }

        foreach ($sums as $key => $sum) {
            [$claim, $limits] = $firstClaim[$key];
            $limit = $request->isRot() ? $limits['annualRot'] : $limits['annualTotal'];
            if ($sum > $limit) {
                $issues[] = new Issue('ANNUAL_LIMIT_EXCEEDED', $claim->path('requestedAmount'), [
                    'claim' => $claim->index, 'sum' => $sum, 'limit' => $limit,
                    'year' => substr($key, 11), 'type' => (string) $request->type,
                ], claim: $claim->index);
            }
        }

        if ($unchecked !== []) {
            ksort($unchecked);
            $issues[] = new Issue('LIMITS_NOT_CHECKED', $request->claimsPath, [
                'years' => implode(', ', array_keys($unchecked)),
                'checked' => implode(', ', AmountLimits::checkedPeriods()),
            ]);
        }

        return $issues;
    }

    /** @return list<Issue> */
    private function transition(Request $request, ValidationContext $context): array
    {
        $from = SchemaVersion::V1_1->usableFrom();
        if ($request->version === SchemaVersion::V1_1 && $from !== null && $context->asOfDate() < $from) {
            return [new Issue('V1_1_NOT_YET_USABLE', '/Begaran', ['asOf' => $context->asOfDate()])];
        }
        if (! $request->version->carriesDeclarations() && $from !== null && $context->asOfDate() >= $from) {
            return [new Issue('DECLARATIONS_IN_E_SERVICE', '/Begaran', ['version' => $request->version->label()])];
        }

        return [];
    }

    /** @return list<Issue> */
    private function policy(Request $request): array
    {
        $issues = [];
        $invoices = [];
        foreach ($request->claims as $claim) {
            $n = $claim->index;
            if (Property::blank($claim->invoiceNumber)) {
                $issues[] = new Issue('POLICY_INVOICE_NUMBER_MISSING', $claim->path('invoiceNumber'), ['claim' => $n], claim: $n);
            } else {
                $invoice = trim((string) $claim->invoiceNumber);
                if (isset($invoices[$invoice])) {
                    $issues[] = new Issue('POLICY_INVOICE_NUMBER_DUPLICATED', $claim->path('invoiceNumber'), ['claim' => $n, 'other' => $invoices[$invoice]], claim: $n);
                } else {
                    $invoices[$invoice] = $n;
                }
            }

            if ($claim->requestedAmount === 0) {
                $issues[] = new Issue('POLICY_REQUESTED_ZERO', $claim->path('requestedAmount'), ['claim' => $n], claim: $n);
            }

            $property = $claim->property;
            if ($request->isRot() && $property !== null && ! Property::blank($property->designation)
                && (! Property::blank($property->apartmentNumber) || ! Property::blank($property->brfOrgNumber))) {
                $issues[] = new Issue('POLICY_PROPERTY_AMBIGUOUS', $claim->path('property'), ['claim' => $n], claim: $n);
            }

            $seen = [];
            foreach ($claim->work as $item) {
                if (isset($seen[$item->rawService])) {
                    $issues[] = new Issue('POLICY_SERVICE_DUPLICATED', $item->path, ['claim' => $n, 'value' => $item->rawService], claim: $n);
                }
                $seen[$item->rawService] = true;
                if ($item->service !== null && ! ServiceCatalog::isFlatRate($item->service) && $item->hours === null) {
                    $issues[] = new Issue('POLICY_SERVICE_WITHOUT_HOURS', $item->path, ['claim' => $n, 'value' => $item->rawService], claim: $n);
                }
            }
        }

        return $issues;
    }
}
