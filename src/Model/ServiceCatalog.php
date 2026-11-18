<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Model;

/**
 * The services a ROT or RUT request can declare.
 *
 * Codes: the documentation of `TypAvUtfortArbete` in the 1.0 and 1.1 schemas
 * ("Giltiga värden när ROT valts: …", "… när RUT valts: …"). The schema itself
 * accepts any string; the e-service checks the value after import.
 *
 * V6 element names: the children of `ArendeUtfortArbeteRotTYPE` and
 * `ArendeUtfortArbeteRutTYPE` in BegaranCOMPONENT.xsd. Skatteverket does not
 * publish a table pairing them with the 1.x codes; the pairing below follows the
 * names one to one and is covered by tests that read both schemas.
 *
 * Flat-rate ("schablon") services: the two V6 elements of `SchablonarbeteTYPE`,
 * which are the services in 67 kap. 13 § 12 and 13 inkomstskattelagen; for these
 * a request states no hours (9 § 4 lagen (2009:194)) and, in 1.x, no cost
 * ("Anges inte för schablontyper").
 */
final class ServiceCatalog
{
    /** @var array<string,array{type:string,v6:string,flatRate:bool}> */
    private const SERVICES = [
        'BYGG' => ['type' => Request::ROT, 'v6' => 'Bygg', 'flatRate' => false],
        'EL' => ['type' => Request::ROT, 'v6' => 'El', 'flatRate' => false],
        'GLASARBETE' => ['type' => Request::ROT, 'v6' => 'GlasPlatarbete', 'flatRate' => false],
        'MARKARBETE' => ['type' => Request::ROT, 'v6' => 'MarkDraneringarbete', 'flatRate' => false],
        'MURNING' => ['type' => Request::ROT, 'v6' => 'Murning', 'flatRate' => false],
        'MALNING_TAPETSERING' => ['type' => Request::ROT, 'v6' => 'MalningTapetsering', 'flatRate' => false],
        'VVS' => ['type' => Request::ROT, 'v6' => 'Vvs', 'flatRate' => false],
        'STADNING' => ['type' => Request::RUT, 'v6' => 'Stadning', 'flatRate' => false],
        'KLAD_TEXTILVARD' => ['type' => Request::RUT, 'v6' => 'KladOchTextilvard', 'flatRate' => false],
        'SNOSKOTTNING' => ['type' => Request::RUT, 'v6' => 'Snoskottning', 'flatRate' => false],
        'TRADGARDSARBETE' => ['type' => Request::RUT, 'v6' => 'Tradgardsarbete', 'flatRate' => false],
        'BARNPASSNING' => ['type' => Request::RUT, 'v6' => 'Barnpassning', 'flatRate' => false],
        'PERSONLIG_OMSORG' => ['type' => Request::RUT, 'v6' => 'Personligomsorg', 'flatRate' => false],
        'FLYTTJANSTER' => ['type' => Request::RUT, 'v6' => 'Flyttjanster', 'flatRate' => false],
        'IT_TJANSTER' => ['type' => Request::RUT, 'v6' => 'ItTjanster', 'flatRate' => false],
        'REPARATION_VITVAROR' => ['type' => Request::RUT, 'v6' => 'ReparationAvVitvaror', 'flatRate' => false],
        'MOBLERING' => ['type' => Request::RUT, 'v6' => 'Moblering', 'flatRate' => false],
        'TILLSYN_AV_BOSTAD' => ['type' => Request::RUT, 'v6' => 'TillsynAvBostad', 'flatRate' => false],
        'TRANSPORT_TILL_FORSALJNING' => ['type' => Request::RUT, 'v6' => 'TransportTillForsaljning', 'flatRate' => true],
        'TVATT_VID_TVATTINRATTNING' => ['type' => Request::RUT, 'v6' => 'TvattVidTvattinrattning', 'flatRate' => true],
    ];

    /** Codes valid for GRON_TEKNIK in the 1.x schemas; a different e-service, listed only to recognise them. */
    public const GREEN_TECHNOLOGY = ['INSTALLATION_LADDPUNKT', 'INSTALLATION_LAGRING', 'INSTALLATION_SOLCELLER'];

    public static function exists(string $code): bool
    {
        return isset(self::SERVICES[$code]);
    }

    public static function typeOf(string $code): ?string
    {
        return self::SERVICES[$code]['type'] ?? null;
    }

    public static function isFlatRate(string $code): bool
    {
        return self::SERVICES[$code]['flatRate'] ?? false;
    }

    public static function v6Element(string $code): ?string
    {
        return self::SERVICES[$code]['v6'] ?? null;
    }

    public static function fromV6Element(string $element): ?string
    {
        foreach (self::SERVICES as $code => $service) {
            if ($service['v6'] === $element) {
                return $code;
            }
        }

        return null;
    }

    /** @return list<string> codes for ROT or RUT, in the order the V6 schema lists them */
    public static function codesFor(string $type): array
    {
        return array_keys(array_filter(self::SERVICES, static fn (array $s): bool => $s['type'] === $type));
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::SERVICES);
    }
}
