<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Unit;

use Closepact\RotRutXml\Tests\TestCase;
use Closepact\RotRutXml\Xml\SafeXmlLoader;

final class SafeXmlLoaderTest extends TestCase
{
    public function test_refuses_files_over_the_size_limit(): void
    {
        $result = (new SafeXmlLoader(maxBytes: 100))->load('<a>'.str_repeat('x', 200).'</a>');

        self::assertNull($result->document);
        self::assertSame('XML_TOO_LARGE', $result->issues[0]->code);
    }

    public function test_refuses_deep_nesting(): void
    {
        $xml = str_repeat('<a>', 40).str_repeat('</a>', 40);
        $result = (new SafeXmlLoader(maxDepth: 32))->load($xml);

        self::assertSame('XML_TOO_DEEP', $result->issues[0]->code);
        self::assertNotNull((new SafeXmlLoader(maxDepth: 40))->load($xml)->document);
    }

    public function test_refuses_any_doctype_or_entity_declaration_whatever_the_case(): void
    {
        foreach (['<!DOCTYPE a><a/>', '<!doctype a><a/>', "<!DocType a [\n]><a/>", '<!ENTITY x "y"><a/>', '<!  DOCTYPE a><a/>'] as $xml) {
            self::assertSame('XML_DOCTYPE_FORBIDDEN', (new SafeXmlLoader)->load($xml)->issues[0]->code, $xml);
        }
    }

    public function test_external_entities_never_reach_the_file_system(): void
    {
        $secret = tempnam(sys_get_temp_dir(), 'xxe');
        file_put_contents((string) $secret, 'TOP-SECRET');
        $xml = '<?xml version="1.0"?><!DOCTYPE a [<!ENTITY x SYSTEM "file://'.$secret.'">]><a>&x;</a>';

        $result = (new SafeXmlLoader)->load($xml);
        unlink((string) $secret);

        self::assertNull($result->document);
        self::assertStringNotContainsString('TOP-SECRET', json_encode(array_map(static fn ($i) => $i->toArray(), $result->issues)) ?: '');
    }

    public function test_reports_the_line_of_the_first_syntax_error(): void
    {
        $result = (new SafeXmlLoader)->load("<a>\n<b>\n</a>");

        self::assertSame('XML_MALFORMED', $result->issues[0]->code);
        self::assertSame(3, $result->issues[0]->line);
    }

    public function test_loads_a_normal_document(): void
    {
        $result = (new SafeXmlLoader)->load('<?xml version="1.0" encoding="UTF-8"?><a><b>å</b></a>');

        self::assertNotNull($result->document);
        self::assertSame([], $result->issues);
    }
}
