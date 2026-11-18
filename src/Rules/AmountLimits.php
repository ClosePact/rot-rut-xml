<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

/**
 * Amount limits by payment date, one row per period whose law text has been
 * read. A payment outside every row is not checked against these limits, and
 * the report says so (LIMITS_NOT_CHECKED) — the toolkit never assumes last
 * year's figures still apply.
 *
 * Adding a year: read 7 § lagen (2009:194) and 67 kap. 19 § inkomstskattelagen
 * as in force for that year on riksdagen.se, add a row, add fixtures on both
 * sides of the boundary, and record the source in docs/regulatory-sources.md.
 */
final class AmountLimits
{
    /**
     * @var list<array{from:string,to:string,rotShare:array{0:int,1:int},rutShare:array{0:int,1:int},annualTotal:int,annualRot:int,source:string}>
     */
    private const PERIODS = [
        [
            // 7 § lagen (2009:194) in the wording of SFS 2025:324 (in force 2026-01-01):
            // RUT up to the amount paid, other work (ROT) up to three sevenths of it;
            // 75 000 kr a year in total, of which 50 000 kr for ROT. Read 2026-11-18.
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'rotShare' => [3, 7],
            'rutShare' => [1, 1],
            'annualTotal' => 75_000,
            'annualRot' => 50_000,
            'source' => 'sfs-2009-194',
        ],
    ];

    /** @return array{from:string,to:string,rotShare:array{0:int,1:int},rutShare:array{0:int,1:int},annualTotal:int,annualRot:int,source:string}|null */
    public static function forPaymentDate(string $date): ?array
    {
        foreach (self::PERIODS as $period) {
            if ($date >= $period['from'] && $date <= $period['to']) {
                return $period;
            }
        }

        return null;
    }

    /** @return list<string> e.g. ['2026-01-01–2026-12-31'] */
    public static function checkedPeriods(): array
    {
        return array_map(static fn (array $p): string => $p['from'].'–'.$p['to'], self::PERIODS);
    }
}
