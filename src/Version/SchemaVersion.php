<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Version;

/**
 * The versions of Skatteverket's import format that this toolkit reads and writes.
 *
 * The value is the XML format's own version, not this package's version: the
 * package follows semantic versioning on its own, and a package release can add,
 * change or drop support for a format version (see docs/compatibility.md).
 *
 * Source for the list, the namespaces and the dates: Skatteverket's schema page
 * "Schemalager (XML) – Rot och rut företag" and the schemas' own metadata
 * (resources/sources.json: skv-schema-page, skv-xsd-v6, skv-xsd-v1-0, skv-xsd-v1-1).
 */
enum SchemaVersion: string
{
    case V6 = 'v6';
    case V1_0 = 'v1.0';
    case V1_1 = 'v1.1';

    public const NS_V6_ROOT = 'http://xmls.skatteverket.se/se/skatteverket/ht/begaran/6.0';

    public const NS_V6_COMPONENT = 'http://xmls.skatteverket.se/se/skatteverket/ht/komponent/begaran/6.0';

    public const NS_V1_0 = 'http://xmls.skatteverket.se/se/skatteverket/skattered/begaran/1.0';

    public const NS_V1_1 = 'http://xmls.skatteverket.se/se/skatteverket/skattered/begaran/1.1';

    /** Human-readable name, as Skatteverket's schema page refers to it. */
    public function label(): string
    {
        return match ($this) {
            self::V6 => 'XML version 6 (ht/begaran V6)',
            self::V1_0 => 'XML version 1.0 (skattered/begaran V1)',
            self::V1_1 => 'XML version 1.1 (skattered/begaran V1.1)',
        };
    }

    /** Namespace of the root element `Begaran`. */
    public function rootNamespace(): string
    {
        return match ($this) {
            self::V6 => self::NS_V6_ROOT,
            self::V1_0 => self::NS_V1_0,
            self::V1_1 => self::NS_V1_1,
        };
    }

    /** Namespace of the elements inside `Begaran`. */
    public function contentNamespace(): string
    {
        return match ($this) {
            self::V6 => self::NS_V6_COMPONENT,
            self::V1_0 => self::NS_V1_0,
            self::V1_1 => self::NS_V1_1,
        };
    }

    /** Path of the entry XSD inside a schema directory (mirrors Skatteverket's URL paths). */
    public function schemaEntry(): string
    {
        return match ($this) {
            self::V6 => 'ht/begaran/V6/Begaran.xsd',
            self::V1_0 => 'skattered/begaran/V1/Begaran.xsd',
            self::V1_1 => 'skattered/begaran/V1.1/Begaran.xsd',
        };
    }

    /**
     * First day Skatteverket says the version can be used, when it states one.
     *
     * V6: "infördes den 1 januari 2021" (schema page). V1.1: effectiveFrom
     * 2027-01-01 in the XSD metadata. V1.0: no date stated for ROT/RUT use.
     */
    public function usableFrom(): ?string
    {
        return match ($this) {
            self::V6 => '2021-01-01',
            self::V1_0 => null,
            self::V1_1 => '2027-01-01',
        };
    }

    /** The file names the company (`Utforare`). */
    public function carriesPerformer(): bool
    {
        return $this !== self::V6;
    }

    /** The file carries the 2027 answers (`Underentreprenor`, `Bemanningsforetag`). */
    public function carriesDeclarations(): bool
    {
        return $this === self::V1_1;
    }

    public static function fromRootNamespace(string $namespace): ?self
    {
        foreach (self::cases() as $version) {
            if ($version->rootNamespace() === $namespace) {
                return $version;
            }
        }

        return null;
    }

    /** Accepts `v6`, `6`, `v1.0`, `1.0`, `v1.1`, `1.1` (case-insensitive). */
    public static function fromOption(string $value): ?self
    {
        return match (strtolower(trim($value))) {
            'v6', '6' => self::V6,
            'v1.0', '1.0', 'v1' => self::V1_0,
            'v1.1', '1.1' => self::V1_1,
            default => null,
        };
    }
}
