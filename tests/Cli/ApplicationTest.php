<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Tests\Cli;

use Closepact\RotRutXml\Cli\Application;
use Closepact\RotRutXml\Tests\TestCase;

final class ApplicationTest extends TestCase
{
    /** @return array{0:int,1:string,2:string} exit code, stdout, stderr */
    private function cli(string ...$argv): array
    {
        $out = fopen('php://memory', 'w+');
        $err = fopen('php://memory', 'w+');
        assert(is_resource($out) && is_resource($err));
        $code = (new Application($out, $err))->run(array_values($argv));
        rewind($out);
        rewind($err);

        return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err)];
    }

    private function emptyDir(): string
    {
        return self::emptyStore()->directory;
    }

    public function test_validate_valid_file_with_schema_exits_0(): void
    {
        $store = $this->requireSchemas();
        [$code, $out] = $this->cli('validate', self::fixture('v1_1/rut-cleaning-valid.xml'), '--as-of=2027-01-12', '--schemas='.$store->directory);

        self::assertSame(Application::OK, $code);
        self::assertStringContainsString('XSD: godkänd', $out);
        self::assertStringContainsString('Inga fel', $out);
    }

    public function test_without_schema_exits_3_unless_no_schema_is_given(): void
    {
        $file = self::fixture('v1_1/rut-cleaning-valid.xml');

        self::assertSame(Application::NO_SCHEMA, $this->cli('validate', $file, '--as-of=2027-01-12', '--schemas='.$this->emptyDir())[0]);
        self::assertSame(Application::OK, $this->cli('validate', $file, '--as-of=2027-01-12', '--schemas='.$this->emptyDir(), '--no-schema')[0]);
    }

    public function test_invalid_file_exits_1_and_json_lists_the_error(): void
    {
        [$code, $out] = $this->cli('validate', self::fixture('v1_1/requested-exceeds-paid.xml'), '--as-of=2027-01-12', '--schemas='.$this->emptyDir(), '--no-schema', '--format=json');
        $json = json_decode($out, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Application::INVALID, $code);
        self::assertFalse($json['valid']);
        self::assertSame('REQUESTED_EXCEEDS_PAID', $json['errors'][0]['code']);
        self::assertSame('rot-rut-xml', $json['tool']['name']);
    }

    public function test_english_text_output(): void
    {
        [, $out] = $this->cli('validate', self::fixture('v1_1/buyer-under-18.xml'), '--as-of=2027-01-12', '--schemas='.$this->emptyDir(), '--no-schema', '--lang=en');

        self::assertStringContainsString('ERROR', $out);
        self::assertStringContainsString('does not turn 18', $out);
        self::assertStringContainsString('Source: https://www.skatteverket.se/', $out);
    }

    public function test_usage_and_io_errors(): void
    {
        self::assertSame(Application::USAGE, $this->cli('validate')[0]);
        self::assertSame(Application::USAGE, $this->cli('nonsense')[0]);
        self::assertSame(Application::USAGE, $this->cli('validate', self::fixture('v6/rut-cleaning-valid.xml'), '--as-of=18/11/2026')[0]);
        self::assertSame(Application::USAGE, $this->cli('generate', self::root().'/examples/rut-v6.json')[0], '--version is required');
        self::assertSame(Application::IO, $this->cli('validate', self::fixture('does-not-exist.xml'))[0]);
    }

    public function test_generate_writes_a_file_only_when_it_passes(): void
    {
        $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rot-rut-'.bin2hex(random_bytes(4)).'.xml';
        [$code] = $this->cli('generate', self::root().'/examples/rut-v6.json', '--version=v6', '--as-of=2026-11-18', '--schemas='.$this->emptyDir(), '--no-schema', '--output='.$output);

        self::assertSame(Application::OK, $code);
        self::assertFileEquals(self::fixture('v6/rut-cleaning-valid.xml'), $output);
        unlink($output);

        [$code] = $this->cli('generate', self::root().'/examples/rut-v6.json', '--version=v6', '--as-of=2026-11-18', '--schemas='.$this->emptyDir(), '--output='.$output);
        self::assertSame(Application::NO_SCHEMA, $code, 'without the XSD the file is not written unless --no-schema');
        self::assertFileDoesNotExist($output);

        [$code, , $err] = $this->cli('generate', self::root().'/examples/rut-v1.0.json', '--version=v1.1', '--as-of=2027-01-12', '--no-schema', '--output='.$output);
        self::assertSame(Application::INVALID, $code, '1.1 needs the 2027 answers');
        self::assertStringContainsString('INPUT_REQUIRED_MISSING', $err);
        self::assertFileDoesNotExist($output);
    }

    public function test_listing_commands_return_json(): void
    {
        foreach (['versions', 'rules', 'sources'] as $command) {
            [$code, $out] = $this->cli($command, '--format=json');
            self::assertSame(Application::OK, $code, $command);
            self::assertIsArray(json_decode($out, true, 512, JSON_THROW_ON_ERROR), $command);
        }
        [, $out] = $this->cli('versions', '--format=json');
        self::assertSame(['v6', 'v1.0', 'v1.1'], array_column(json_decode($out, true)['versions'], 'version'));
    }

    public function test_schemas_status_reports_missing_files(): void
    {
        [$code, $out] = $this->cli('schemas:status', '--schemas='.$this->emptyDir(), '--format=json');

        self::assertSame(Application::NO_SCHEMA, $code);
        self::assertSame('missing', json_decode($out, true)['versions']['v1.1']);
    }
}
