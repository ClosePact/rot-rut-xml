<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Generator;

use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Version\SchemaVersion;
use DOMDocument;
use DOMElement;

/**
 * Writes normalised input (InputReader) as XML in one of the supported versions,
 * in the element order each schema prescribes. Prefixes follow Skatteverket's
 * own example files (ns1/ns2 for V6, p for 1.x); the prefixes carry no meaning.
 */
final class XmlWriter
{
    private DOMDocument $document;

    private string $namespace;

    private string $prefix;

    /** @param array<string,mixed> $input */
    public function write(array $input, SchemaVersion $version): string
    {
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;

        $version === SchemaVersion::V6 ? $this->v6($input) : $this->v1($input, $version);

        return (string) $this->document->saveXML();
    }

    /** @param array<string,mixed> $input */
    private function v6(array $input): void
    {
        $root = $this->document->createElementNS(SchemaVersion::NS_V6_ROOT, 'ns1:Begaran');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ns2', SchemaVersion::NS_V6_COMPONENT);
        $this->document->appendChild($root);
        $this->namespace = SchemaVersion::NS_V6_COMPONENT;
        $this->prefix = 'ns2';

        $this->add($root, 'NamnPaBegaran', $input['name']);
        $rot = $input['type'] === Request::ROT;
        $container = $this->add($root, $rot ? 'RotBegaran' : 'HushallBegaran');

        foreach ($input['cases'] as $case) {
            $e = $this->add($container, 'Arenden');
            $this->add($e, 'Kopare', $case['buyer']);
            $this->add($e, 'BetalningsDatum', $case['payment_date']);
            $this->add($e, 'PrisForArbete', $case['labour_cost']);
            $this->add($e, 'BetaltBelopp', $case['paid']);
            $this->add($e, 'BegartBelopp', $case['requested']);
            $this->optional($e, 'FakturaNr', $case['invoice']);
            $this->optional($e, 'Ovrigkostnad', $case['other_cost']);
            if ($rot && $case['property'] !== null) {
                $this->optional($e, 'Fastighetsbeteckning', $case['property']['designation']);
                $this->optional($e, 'LagenhetsNr', $case['property']['apartment']);
                $this->optional($e, 'BrfOrgNr', $case['property']['brf']);
            }

            $byCode = [];
            foreach ($case['work'] as $item) {
                $byCode[$item['service']] = $item;
            }
            $work = $this->add($e, 'UtfortArbete');
            foreach (ServiceCatalog::codesFor($input['type']) as $code) {   // schema order
                if (! isset($byCode[$code])) {
                    continue;
                }
                $service = $this->add($work, (string) ServiceCatalog::v6Element($code));
                if (ServiceCatalog::isFlatRate($code)) {
                    $this->add($service, 'Utfort', 'true');

                    continue;
                }
                $this->optional($service, 'AntalTimmar', $byCode[$code]['hours']);
                $this->add($service, 'Materialkostnad', $byCode[$code]['material']);
            }
        }
    }

    /** @param array<string,mixed> $input */
    private function v1(array $input, SchemaVersion $version): void
    {
        $this->namespace = $version->rootNamespace();
        $this->prefix = 'p';
        $root = $this->document->createElementNS($this->namespace, 'p:Begaran');
        $this->document->appendChild($root);

        if ($version === SchemaVersion::V1_1 && $input['contact'] !== null) {
            $contact = $this->add($root, 'Systemkontakt');
            $this->add($contact, 'Namn', $input['contact']['name']);
            $this->add($contact, 'Produktnamn', $input['contact']['product']);
            foreach ($input['contact']['emails'] as $email) {
                $this->add($contact, 'KontaktEpost', $email);
            }
        }

        $this->add($root, 'NamnPaBegaran', $input['name']);
        $this->add($root, 'TypAvBegaran', $input['type']);
        $this->add($root, 'Utforare', $input['performer']);

        foreach ($input['cases'] as $case) {
            $e = $this->add($root, 'Arende');
            $this->optional($e, 'FakturaNr', $case['invoice']);
            $this->add($e, 'Kopare', $case['buyer']);
            if ($case['property'] !== null) {
                $property = $this->add($e, 'Fastighet');
                $this->optional($property, 'Fastighetsbeteckning', $case['property']['designation']);
                $this->optional($property, 'LagenhetsNr', $case['property']['apartment']);
                $this->optional($property, 'BrfOrgNr', $case['property']['brf']);
            }
            foreach ($case['work'] as $item) {
                $work = $this->add($e, 'UtfortArbete');
                $this->add($work, 'TypAvUtfortArbete', $item['service']);
                $this->optional($work, 'AntalTimmar', $item['hours']);
                $this->optional($work, 'Kostnad', $item['material']);
            }
            $this->optional($e, 'OvrigKostnad', $case['other_cost']);
            $this->add($e, 'Betalningsdatum', $case['payment_date']);
            $this->add($e, 'Arbetskostnad', $case['labour_cost']);
            $this->add($e, 'BetaltBelopp', $case['paid']);
            $this->add($e, 'BegartBelopp', $case['requested']);
            if ($version->carriesDeclarations()) {
                $this->add($e, 'Underentreprenor', $case['subcontractors'] ? 'true' : 'false');
                $this->add($e, 'Bemanningsforetag', $case['staffing_agency'] ? 'true' : 'false');
            }
        }
    }

    private function add(DOMElement $parent, string $name, string|int|null $value = null): DOMElement
    {
        $element = $this->document->createElementNS($this->namespace, $this->prefix.':'.$name);
        if ($value !== null) {
            $element->appendChild($this->document->createTextNode((string) $value));
        }
        $parent->appendChild($element);

        return $element;
    }

    private function optional(DOMElement $parent, string $name, string|int|null $value): void
    {
        if ($value !== null) {
            $this->add($parent, $name, $value);
        }
    }
}
