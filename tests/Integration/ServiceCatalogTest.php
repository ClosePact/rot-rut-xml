<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Integration;

use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Tests\TestCase;
use DOMDocument;
use DOMXPath;

/** The service list is read from Skatteverket's own schema files, not trusted from memory. */
final class ServiceCatalogTest extends TestCase
{
    public function test_codes_match_the_documentation_in_the_1_0_and_1_1_schemas(): void
    {
        $store = $this->requireSchemas();
        foreach (['skattered/begaran/V1/Begaran.xsd', 'skattered/begaran/V1.1/Begaran.xsd'] as $schema) {
            $text = (string) file_get_contents($store->pathOf($schema));
            foreach ([Request::ROT, Request::RUT] as $type) {
                if (preg_match('/Giltiga värden när '.$type.' valts:\s*\[([^\]]+)\]/u', $text, $m) !== 1) {
                    self::fail("{$schema}: no {$type} list");
                }
                $documented = array_map('trim', preg_split('/,\s*/', trim($m[1])) ?: []);
                $ours = ServiceCatalog::codesFor($type);
                sort($documented);
                sort($ours);
                self::assertSame($documented, $ours, "{$schema}: {$type} codes");
            }
        }
    }

    public function test_v6_elements_and_flat_rate_services_match_the_v6_component_schema(): void
    {
        $store = $this->requireSchemas();
        $document = new DOMDocument;
        $document->load($store->pathOf('ht/komponent/V6/BegaranCOMPONENT.xsd'), LIBXML_NOWARNING); // Skatteverket's metadata declares a relative namespace URI
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        foreach ([Request::ROT => 'ArendeUtfortArbeteRotTYPE', Request::RUT => 'ArendeUtfortArbeteRutTYPE'] as $type => $complexType) {
            $elements = [];
            $flat = [];
            foreach ($xpath->query("//xs:complexType[@name='{$complexType}']//xs:element") ?: [] as $element) {
                assert($element instanceof \DOMElement);
                $elements[] = $element->getAttribute('name');
                if ($element->getAttribute('type') === 'SchablonarbeteTYPE') {
                    $flat[] = $element->getAttribute('name');
                }
            }
            $ours = array_map(static fn (string $code): string => (string) ServiceCatalog::v6Element($code), ServiceCatalog::codesFor($type));
            $oursFlat = array_values(array_map(
                static fn (string $code): string => (string) ServiceCatalog::v6Element($code),
                array_filter(ServiceCatalog::codesFor($type), ServiceCatalog::isFlatRate(...)),
            ));

            self::assertSame($elements, $ours, "{$type}: V6 elements in schema order");
            self::assertSame($flat, $oursFlat, "{$type}: flat-rate (SchablonarbeteTYPE) elements");
        }
    }
}
