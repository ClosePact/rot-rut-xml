<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Parser;

use Closepact\RotRutXml\Model\BuyerClaim;
use Closepact\RotRutXml\Model\Property;
use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Model\WorkItem;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Version\SchemaVersion;
use DOMDocument;
use DOMElement;

/**
 * Reads a payment request into the version-neutral model.
 *
 * It reads as much as it can from a damaged file and records a structure issue
 * for each required element that is missing or unreadable. Those issues are
 * reported only when Skatteverket's XSD could not be run; when it could, the
 * XSD's own messages are used instead (see Validator).
 */
final class RequestParser
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    /** @var list<Issue> */
    private array $issues = [];

    private SchemaVersion $version;

    public function parse(DOMDocument $document, SchemaVersion $version): ParseResult
    {
        $this->issues = [];
        $this->version = $version;
        $root = $document->documentElement;
        assert($root instanceof DOMElement);

        return new ParseResult(
            $version === SchemaVersion::V6 ? $this->parseV6($root) : $this->parseV1($root),
            $this->issues,
        );
    }

    private function parseV6(DOMElement $root): Request
    {
        $name = $this->text($root, 'NamnPaBegaran', '/Begaran/NamnPaBegaran', required: true);

        $container = $this->child($root, 'RotBegaran');
        $type = Request::ROT;
        $base = '/Begaran/RotBegaran';
        if ($container === null) {
            $container = $this->child($root, 'HushallBegaran');
            $type = $container === null ? null : Request::RUT;
            $base = '/Begaran/HushallBegaran';
        }

        $claims = [];
        foreach ($container === null ? [] : $this->children($container, 'Arenden') as $i => $element) {
            $claims[] = $this->claimV6($element, $i + 1, $base.'/Arenden['.($i + 1).']', $type);
        }

        return new Request(SchemaVersion::V6, $name, $type, null, $claims, claimsPath: $container === null ? '/Begaran' : $base);
    }

    private function claimV6(DOMElement $e, int $index, string $at, ?string $type): BuyerClaim
    {
        $paths = ['claim' => $at];
        foreach (['Kopare' => 'buyer', 'BetalningsDatum' => 'paymentDate', 'PrisForArbete' => 'labourCost', 'BetaltBelopp' => 'paidAmount',
            'BegartBelopp' => 'requestedAmount', 'FakturaNr' => 'invoiceNumber', 'Ovrigkostnad' => 'otherCost', 'UtfortArbete' => 'work',
            'Fastighetsbeteckning' => 'property', ] as $element => $field) {
            $paths[$field] = $at.'/'.$element;
        }

        $property = null;
        if ($type === Request::ROT) {
            $property = new Property(
                $this->text($e, 'Fastighetsbeteckning', $at.'/Fastighetsbeteckning'),
                $this->text($e, 'LagenhetsNr', $at.'/LagenhetsNr'),
                $this->text($e, 'BrfOrgNr', $at.'/BrfOrgNr'),
                $at,
            );
            $paths['apartmentNumber'] = $at.'/LagenhetsNr';
            $paths['brfOrgNumber'] = $at.'/BrfOrgNr';
        }

        $work = [];
        $workElement = $this->child($e, 'UtfortArbete');
        if ($workElement !== null && ! $this->isNil($workElement)) {
            foreach ($workElement->childNodes as $service) {
                if (! $service instanceof DOMElement || $this->isNil($service)) {
                    continue;
                }
                $name = (string) $service->localName;
                $path = $at.'/UtfortArbete/'.$name;
                $code = ServiceCatalog::fromV6Element($name);
                if ($code !== null && ServiceCatalog::isFlatRate($code)) {
                    $work[] = new WorkItem($code, $name, null, null, $this->bool($service, 'Utfort', $path.'/Utfort', required: true), $path);

                    continue;
                }
                $work[] = new WorkItem(
                    $code,
                    $name,
                    $this->int($service, 'AntalTimmar', $path.'/AntalTimmar'),
                    $this->int($service, 'Materialkostnad', $path.'/Materialkostnad', required: true),
                    null,
                    $path,
                );
            }
        }

        return new BuyerClaim(
            $index,
            $this->text($e, 'FakturaNr', $at.'/FakturaNr'),
            $this->text($e, 'Kopare', $at.'/Kopare', required: true),
            $property,
            $work,
            $this->int($e, 'Ovrigkostnad', $at.'/Ovrigkostnad'),
            $this->date($e, 'BetalningsDatum', $at.'/BetalningsDatum'),
            $this->int($e, 'PrisForArbete', $at.'/PrisForArbete', required: true),
            $this->int($e, 'BetaltBelopp', $at.'/BetaltBelopp', required: true),
            $this->int($e, 'BegartBelopp', $at.'/BegartBelopp', required: true),
            null,
            null,
            $paths,
        );
    }

    private function parseV1(DOMElement $root): Request
    {
        $contact = $this->child($root, 'Systemkontakt');
        $emails = [];
        if ($contact !== null) {
            foreach ($this->children($contact, 'KontaktEpost') as $email) {
                $emails[] = trim($email->textContent);
            }
        }

        $claims = [];
        foreach ($this->children($root, 'Arende') as $i => $element) {
            $claims[] = $this->claimV1($element, $i + 1, '/Begaran/Arende['.($i + 1).']');
        }

        return new Request(
            $this->version,
            $this->text($root, 'NamnPaBegaran', '/Begaran/NamnPaBegaran', required: true),
            $this->text($root, 'TypAvBegaran', '/Begaran/TypAvBegaran', required: true),
            $this->text($root, 'Utforare', '/Begaran/Utforare', required: true),
            $claims,
            $contact === null ? null : $this->text($contact, 'Namn', '/Begaran/Systemkontakt/Namn'),
            $contact === null ? null : $this->text($contact, 'Produktnamn', '/Begaran/Systemkontakt/Produktnamn'),
            $emails,
            '/Begaran',
        );
    }

    private function claimV1(DOMElement $e, int $index, string $at): BuyerClaim
    {
        $paths = ['claim' => $at];
        foreach (['FakturaNr' => 'invoiceNumber', 'Kopare' => 'buyer', 'Fastighet' => 'property', 'UtfortArbete' => 'work', 'OvrigKostnad' => 'otherCost',
            'Betalningsdatum' => 'paymentDate', 'Arbetskostnad' => 'labourCost', 'BetaltBelopp' => 'paidAmount', 'BegartBelopp' => 'requestedAmount',
            'Underentreprenor' => 'subcontractors', 'Bemanningsforetag' => 'staffingAgency', ] as $element => $field) {
            $paths[$field] = $at.'/'.$element;
        }
        $paths['apartmentNumber'] = $at.'/Fastighet/LagenhetsNr';
        $paths['brfOrgNumber'] = $at.'/Fastighet/BrfOrgNr';

        $property = null;
        $fastighet = $this->child($e, 'Fastighet');
        if ($fastighet !== null && ! $this->isNil($fastighet)) {
            $property = new Property(
                $this->text($fastighet, 'Fastighetsbeteckning', $at.'/Fastighet/Fastighetsbeteckning'),
                $this->text($fastighet, 'LagenhetsNr', $at.'/Fastighet/LagenhetsNr'),
                $this->text($fastighet, 'BrfOrgNr', $at.'/Fastighet/BrfOrgNr'),
                $at.'/Fastighet',
            );
        }

        $work = [];
        foreach ($this->children($e, 'UtfortArbete') as $j => $item) {
            $path = $at.'/UtfortArbete['.($j + 1).']';
            $raw = (string) $this->text($item, 'TypAvUtfortArbete', $path.'/TypAvUtfortArbete', required: true);
            $work[] = new WorkItem(
                ServiceCatalog::exists($raw) ? $raw : null,
                $raw,
                $this->int($item, 'AntalTimmar', $path.'/AntalTimmar'),
                $this->int($item, 'Kostnad', $path.'/Kostnad'),
                null,
                $path,
            );
        }

        $declarations = $this->version->carriesDeclarations();

        return new BuyerClaim(
            $index,
            $this->text($e, 'FakturaNr', $at.'/FakturaNr'),
            $this->text($e, 'Kopare', $at.'/Kopare', required: true),
            $property,
            $work,
            $this->int($e, 'OvrigKostnad', $at.'/OvrigKostnad'),
            $this->date($e, 'Betalningsdatum', $at.'/Betalningsdatum'),
            $this->int($e, 'Arbetskostnad', $at.'/Arbetskostnad'),
            $this->int($e, 'BetaltBelopp', $at.'/BetaltBelopp', required: true),
            $this->int($e, 'BegartBelopp', $at.'/BegartBelopp', required: true),
            $declarations ? $this->bool($e, 'Underentreprenor', $at.'/Underentreprenor', required: true) : null,
            $declarations ? $this->bool($e, 'Bemanningsforetag', $at.'/Bemanningsforetag', required: true) : null,
            $paths,
        );
    }

    // --- element access -------------------------------------------------------------

    private function child(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $node) {
            if ($node instanceof DOMElement && $node->localName === $name && $node->namespaceURI === $this->version->contentNamespace()) {
                return $node;
            }
        }

        return null;
    }

    /** @return list<DOMElement> */
    private function children(DOMElement $parent, string $name): array
    {
        $found = [];
        foreach ($parent->childNodes as $node) {
            if ($node instanceof DOMElement && $node->localName === $name && $node->namespaceURI === $this->version->contentNamespace()) {
                $found[] = $node;
            }
        }

        return $found;
    }

    private function isNil(DOMElement $element): bool
    {
        return in_array($element->getAttributeNS(self::XSI, 'nil'), ['true', '1'], true);
    }

    private function text(DOMElement $parent, string $name, string $path, bool $required = false): ?string
    {
        $element = $this->child($parent, $name);
        if ($element === null || $this->isNil($element)) {
            if ($required) {
                $this->issues[] = new Issue('STRUCTURE_REQUIRED_MISSING', $path, ['field' => $name]);
            }

            return null;
        }

        return trim($element->textContent);
    }

    private function int(DOMElement $parent, string $name, string $path, bool $required = false): ?int
    {
        $value = $this->text($parent, $name, $path, $required);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^[+]?\d{1,18}$/', $value) !== 1) {
            $this->issues[] = new Issue('STRUCTURE_INVALID_VALUE', $path, ['field' => $name, 'expected' => 'heltal ≥ 0 / whole number ≥ 0']);

            return null;
        }

        return (int) $value;
    }

    private function date(DOMElement $parent, string $name, string $path): ?string
    {
        $value = $this->text($parent, $name, $path, true);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $this->issues[] = new Issue('STRUCTURE_INVALID_VALUE', $path, ['field' => $name, 'expected' => 'ÅÅÅÅ-MM-DD / YYYY-MM-DD']);

            return null;
        }

        return $value;
    }

    private function bool(DOMElement $parent, string $name, string $path, bool $required = false): ?bool
    {
        $value = $this->text($parent, $name, $path, $required);
        if ($value === null) {
            return null;
        }

        return match ($value) {
            'true', '1' => true,
            'false', '0' => false,
            default => (function () use ($name, $path): ?bool {
                $this->issues[] = new Issue('STRUCTURE_INVALID_VALUE', $path, ['field' => $name, 'expected' => 'true/false']);

                return null;
            })(),
        };
    }
}
