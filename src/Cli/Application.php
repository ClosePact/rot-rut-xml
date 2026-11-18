<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Cli;

use Closepact\RotRutXml\Generator\GenerationFailed;
use Closepact\RotRutXml\Generator\Generator;
use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Reporting\ValidationResult;
use Closepact\RotRutXml\Rules\RuleCatalog;
use Closepact\RotRutXml\Rules\SourceRegistry;
use Closepact\RotRutXml\Schema\SchemaFetcher;
use Closepact\RotRutXml\Schema\SchemaManifest;
use Closepact\RotRutXml\Schema\SchemaStore;
use Closepact\RotRutXml\Toolkit;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * The `rot-rut` command. Exit codes (stable, see docs/cli.md):
 *
 *   0  no errors (and the XSD check ran, unless --no-schema)
 *   1  the file or input has errors
 *   2  wrong usage
 *   3  the XSD check could not run (schema missing or changed); use --no-schema to accept that
 *   4  could not read or write a file, or download failed
 */
final class Application
{
    public const OK = 0;

    public const INVALID = 1;

    public const USAGE = 2;

    public const NO_SCHEMA = 3;

    public const IO = 4;

    /** @var resource */
    private $out;

    /** @var resource */
    private $err;

    /**
     * @param  resource|null  $out
     * @param  resource|null  $err
     */
    public function __construct($out = null, $err = null)
    {
        $this->out = $out ?? STDOUT;
        $this->err = $err ?? STDERR;
    }

    /** @param list<string> $argv arguments without the program name */
    public function run(array $argv): int
    {
        [$command, $args, $options] = $this->parse($argv);
        $lang = ($options['lang'] ?? 'sv') === 'en' ? 'en' : 'sv';

        try {
            return match ($command) {
                'validate' => $this->validate($args, $options, $lang),
                'generate' => $this->generate($args, $options, $lang),
                'versions' => $this->versions($options),
                'rules' => $this->rules($options, $lang),
                'sources' => $this->sources($options),
                'schemas:fetch' => $this->fetch($options),
                'schemas:status' => $this->status($options),
                'about' => $this->about(),
                null, 'help', '--help', '-h' => $this->help(),
                default => $this->usage("Okänt kommando / unknown command: {$command}"),
            };
        } catch (UsageError $e) {
            return $this->usage($e->getMessage());
        } catch (Throwable $e) {
            fwrite($this->err, 'Fel / error: '.$e->getMessage().PHP_EOL);

            return self::IO;
        }
    }

    // --- commands -----------------------------------------------------------------------

    /**
     * @param  list<string>  $args
     * @param  array<string,string|true>  $options
     */
    private function validate(array $args, array $options, string $lang): int
    {
        $file = $args[0] ?? throw new UsageError('rot-rut validate <fil.xml> [--format=json] [--version=auto|v6|v1.0|v1.1] [--as-of=ÅÅÅÅ-MM-DD] [--schemas=DIR] [--no-schema] [--policy] [--lang=sv|en]');
        if (! is_file($file) || ! is_readable($file)) {
            fwrite($this->err, "Kan inte läsa / cannot read: {$file}".PHP_EOL);

            return self::IO;
        }

        $validator = Validator::create($this->stringOption($options, 'schemas'));
        $result = $validator->validateFile($file, $this->validationOptions($options));

        $this->report($result, $options, $lang);

        return $this->exitCode($result, isset($options['no-schema']));
    }

    /**
     * @param  list<string>  $args
     * @param  array<string,string|true>  $options
     */
    private function generate(array $args, array $options, string $lang): int
    {
        $file = $args[0] ?? throw new UsageError('rot-rut generate <indata.json> --version=v6|v1.0|v1.1 [--output=fil.xml] [--as-of=ÅÅÅÅ-MM-DD] [--schemas=DIR] [--no-schema]');
        $version = SchemaVersion::fromOption($this->stringOption($options, 'version') ?? '')
            ?? throw new UsageError('--version=v6, --version=v1.0 eller --version=v1.1 krävs / is required');
        $json = @file_get_contents($file);
        if ($json === false) {
            fwrite($this->err, "Kan inte läsa / cannot read: {$file}".PHP_EOL);

            return self::IO;
        }

        $validator = Validator::create($this->stringOption($options, 'schemas'));
        $generator = new Generator($validator);
        $validationOptions = $this->validationOptions($options, $version);

        try {
            $result = $generator->generate(Generator::decodeJson($json), $version, $validationOptions);
        } catch (GenerationFailed $e) {
            $this->issues($e->issues, $lang, $this->err);
            fwrite($this->err, ($lang === 'en' ? 'No file was written.' : 'Ingen fil skrevs.').PHP_EOL);

            return self::INVALID;
        }

        if (! $result->validation->schemaChecked() && ! isset($options['no-schema'])) {
            $this->issues($result->validation->notices(), $lang, $this->err);
            fwrite($this->err, ($lang === 'en'
                ? 'No file was written: the XSD check could not run. Run `rot-rut schemas:fetch`, or pass --no-schema.'
                : 'Ingen fil skrevs: XSD-kontrollen kunde inte köras. Kör `rot-rut schemas:fetch`, eller ange --no-schema.').PHP_EOL);

            return self::NO_SCHEMA;
        }

        $this->issues([...$result->inputWarnings, ...$result->validation->warnings(), ...$result->validation->notices()], $lang, $this->err);
        $output = $this->stringOption($options, 'output');
        if ($output === null) {
            fwrite($this->out, $result->xml);
        } elseif (file_put_contents($output, $result->xml) === false) {
            fwrite($this->err, "Kan inte skriva / cannot write: {$output}".PHP_EOL);

            return self::IO;
        } else {
            fwrite($this->err, ($lang === 'en' ? 'Written: ' : 'Skrev: ').$output.PHP_EOL);
        }

        return self::OK;
    }

    /** @param array<string,string|true> $options */
    private function versions(array $options): int
    {
        $rows = array_map(static fn (SchemaVersion $v): array => [
            'version' => $v->value,
            'label' => $v->label(),
            'namespace' => $v->rootNamespace(),
            'usable_from' => $v->usableFrom(),
            'performer_in_file' => $v->carriesPerformer(),
            'declarations_2027_in_file' => $v->carriesDeclarations(),
            'schema' => array_map(static fn (array $f): string => $f['url'], SchemaManifest::schemaFilesFor($v)),
        ], SchemaVersion::cases());

        if (($options['format'] ?? 'text') === 'json') {
            return $this->json(['versions' => $rows, 'not_supported' => 'ht/begaran V3 and V4 (still accepted by Skatteverket, being phased out)']);
        }
        foreach ($rows as $row) {
            $this->line(sprintf('%-5s %s', $row['version'], $row['label']));
            $this->line('      namnrymd / namespace: '.$row['namespace']);
            $this->line('      från / from: '.($row['usable_from'] ?? '–').'   Utforare: '.($row['performer_in_file'] ? 'ja/yes' : 'nej/no')
                .'   2027-svar / answers: '.($row['declarations_2027_in_file'] ? 'ja/yes' : 'nej/no'));
        }
        $this->line('Version 3 och 4 tas fortfarande emot av Skatteverket men stöds inte här / are still accepted by Skatteverket but not supported here.');

        return self::OK;
    }

    /** @param array<string,string|true> $options */
    private function rules(array $options, string $lang): int
    {
        $rows = [];
        foreach (RuleCatalog::all() as $rule) {
            $rows[] = [
                'code' => $rule->code,
                'category' => $rule->category->value,
                'severity' => $rule->severity->value,
                'status' => $rule->status,
                'versions' => $rule->versions === [] ? ['v6', 'v1.0', 'v1.1'] : $rule->versions,
                'message_sv' => $rule->sv,
                'message_en' => $rule->en,
                'sources' => $rule->sources,
            ];
        }
        $services = [];
        foreach ([Request::ROT, Request::RUT] as $type) {
            foreach (ServiceCatalog::codesFor($type) as $code) {
                $services[] = ['code' => $code, 'type' => $type, 'v6_element' => ServiceCatalog::v6Element($code), 'flat_rate' => ServiceCatalog::isFlatRate($code)];
            }
        }

        if (($options['format'] ?? 'text') === 'json') {
            return $this->json(['rules' => $rows, 'services' => $services]);
        }
        foreach ($rows as $row) {
            $this->line(sprintf('%-36s %-10s %-8s %s', $row['code'], $row['category'], $row['severity'], $row['status'] === 'verified' ? '' : 'VERIFY_REQUIRED'));
            $this->line('    '.($lang === 'en' ? $row['message_en'] : $row['message_sv']));
        }
        $this->line('');
        $this->line($lang === 'en' ? 'Services:' : 'Tjänster:');
        foreach ($services as $s) {
            $this->line(sprintf('  %-4s %-28s V6: %-26s %s', $s['type'], $s['code'], $s['v6_element'], $s['flat_rate'] ? 'schablon / flat rate' : ''));
        }

        return self::OK;
    }

    /** @param array<string,string|true> $options */
    private function sources(array $options): int
    {
        $sources = SourceRegistry::all();
        if (($options['format'] ?? 'text') === 'json') {
            return $this->json(['sources' => $sources]);
        }
        foreach ($sources as $id => $source) {
            $this->line("{$id}  ({$source['publisher']}, läst/read {$source['retrieved']})");
            $this->line('    '.$source['title']);
            $this->line('    '.$source['url']);
        }

        return self::OK;
    }

    /** @param array<string,string|true> $options */
    private function fetch(array $options): int
    {
        $store = SchemaStore::default($this->stringOption($options, 'schemas'));
        $this->line('Katalog / directory: '.$store->directory);
        $failed = false;
        foreach ((new SchemaFetcher($store))->fetch(isset($options['include-samples'])) as $row) {
            $this->line(sprintf('  %-18s %s', $row['state'], $row['path']));
            $failed = $failed || $row['state'] === 'checksum_mismatch';
        }
        if ($failed) {
            fwrite($this->err, 'En fil skiljer sig från den kända versionen och sparades inte. Skatteverket kan ha ändrat den; se docs/maintenance.md. / A file differs from the known version and was not saved.'.PHP_EOL);

            return self::NO_SCHEMA;
        }

        return self::OK;
    }

    /** @param array<string,string|true> $options */
    private function status(array $options): int
    {
        $store = SchemaStore::default($this->stringOption($options, 'schemas'));
        $rows = [];
        foreach (SchemaVersion::cases() as $version) {
            $rows[$version->value] = $store->status($version)->state;
        }
        $code = in_array('missing', $rows, true) || in_array('checksum_mismatch', $rows, true) ? self::NO_SCHEMA : self::OK;
        if (($options['format'] ?? 'text') === 'json') {
            $this->json(['directory' => $store->directory, 'versions' => $rows]);

            return $code;
        }
        $this->line('Katalog / directory: '.$store->directory);
        foreach ($rows as $version => $state) {
            $this->line(sprintf('  %-5s %s', $version, $state));
        }

        return $code;
    }

    // --- output ------------------------------------------------------------------------

    /** @param array<string,string|true> $options */
    private function report(ValidationResult $result, array $options, string $lang): void
    {
        if (($options['format'] ?? 'text') === 'json') {
            $this->json(['tool' => ['name' => Toolkit::NAME, 'version' => Toolkit::VERSION], ...$result->toArray()]);

            return;
        }

        $schema = [
            ValidationResult::SCHEMA_PASSED => $lang === 'en' ? 'passed' : 'godkänd',
            ValidationResult::SCHEMA_FAILED => $lang === 'en' ? 'failed' : 'underkänd',
            ValidationResult::SCHEMA_SKIPPED => $lang === 'en' ? 'not run' : 'ej körd',
            ValidationResult::SCHEMA_NOT_RUN => $lang === 'en' ? 'not run' : 'ej körd',
        ][$result->schemaStatus];
        $this->line(sprintf('%s %s · %s · XSD: %s · %s %s',
            Toolkit::NAME, Toolkit::VERSION, $result->version?->label() ?? '?', $schema,
            $lang === 'en' ? 'application date' : 'ansökningsdag', $result->asOf));

        $this->issues($result->issues, $lang, $this->out);

        $summary = $lang === 'en'
            ? sprintf('%s: %d error(s), %d warning(s), %d notice(s).', $result->isValid() ? 'No errors' : 'Errors found', count($result->errors()), count($result->warnings()), count($result->notices()))
            : sprintf('%s: %d fel, %d varningar, %d noteringar.', $result->isValid() ? 'Inga fel' : 'Fel hittades', count($result->errors()), count($result->warnings()), count($result->notices()));
        $this->line($summary);
        $this->line($lang === 'en'
            ? 'A file check is not a decision by Skatteverket and does not replace tax advice.'
            : 'En kontroll av filen är inte ett besked från Skatteverket och ersätter inte skatterådgivning.');
    }

    /**
     * @param  list<Issue>  $issues
     * @param  resource  $stream
     */
    private function issues(array $issues, string $lang, $stream): void
    {
        $labels = $lang === 'en'
            ? ['error' => 'ERROR', 'warning' => 'WARNING', 'notice' => 'NOTICE']
            : ['error' => 'FEL', 'warning' => 'VARNING', 'notice' => 'NOTERING'];
        foreach ($issues as $issue) {
            $data = $issue->toArray();
            $where = trim(($issue->path ?? '').($issue->line !== null ? ' ('.($lang === 'en' ? 'line' : 'rad').' '.$issue->line.')' : ''));
            fwrite($stream, sprintf('%-8s %s  %s', $labels[$data['severity']], $issue->code, $where).PHP_EOL);
            fwrite($stream, '         '.$issue->message($lang).PHP_EOL);
            $fix = $data['fix_'.$lang] ?? null;
            if (is_string($fix)) {
                fwrite($stream, '         '.($lang === 'en' ? 'Fix: ' : 'Åtgärd: ').$fix.PHP_EOL);
            }
            foreach ($data['sources'] ?? [] as $source) {
                fwrite($stream, '         '.($lang === 'en' ? 'Source: ' : 'Källa: ').$source['url'].PHP_EOL);
            }
        }
    }

    private function exitCode(ValidationResult $result, bool $schemaOptional): int
    {
        if (! $result->isValid()) {
            return self::INVALID;
        }

        return $result->schemaChecked() || $schemaOptional ? self::OK : self::NO_SCHEMA;
    }

    /** @param array<string,mixed> $data */
    private function json(array $data): int
    {
        fwrite($this->out, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        return self::OK;
    }

    private function line(string $text): void
    {
        fwrite($this->out, $text.PHP_EOL);
    }

    private function about(): int
    {
        $this->line(Toolkit::NAME.' '.Toolkit::VERSION);

        return self::OK;
    }

    private function help(): int
    {
        $this->line(Toolkit::NAME.' '.Toolkit::VERSION.' – skapa och kontrollera XML-filer för begäran om utbetalning (rot och rut).');
        $this->line('');
        $this->line('  rot-rut validate <fil.xml> [--format=text|json] [--version=auto|v6|v1.0|v1.1] [--as-of=ÅÅÅÅ-MM-DD]');
        $this->line('                   [--schemas=DIR] [--no-schema] [--policy] [--lang=sv|en]');
        $this->line('  rot-rut generate <indata.json> --version=v6|v1.0|v1.1 [--output=fil.xml] [--as-of=ÅÅÅÅ-MM-DD] [--no-schema]');
        $this->line('  rot-rut versions [--format=json]      format som stöds / supported formats');
        $this->line('  rot-rut rules [--format=json]         alla regler och tjänstekoder / all rules and service codes');
        $this->line('  rot-rut sources [--format=json]       källor / primary sources');
        $this->line('  rot-rut schemas:fetch [--schemas=DIR] [--include-samples]   hämtar Skatteverkets XSD (HTTPS, sha256)');
        $this->line('  rot-rut schemas:status [--schemas=DIR]');
        $this->line('');
        $this->line('Exit: 0 ok · 1 fel/errors · 2 användning/usage · 3 XSD ej körd/not run · 4 I/O');
        $this->line('Filen lämnar aldrig datorn. / The file never leaves your computer.');

        return self::OK;
    }

    private function usage(string $message): int
    {
        fwrite($this->err, $message.PHP_EOL.'rot-rut help'.PHP_EOL);

        return self::USAGE;
    }

    // --- arguments ---------------------------------------------------------------------

    /**
     * @param  list<string>  $argv
     * @return array{0:?string,1:list<string>,2:array<string,string|true>}
     */
    private function parse(array $argv): array
    {
        $command = null;
        $args = [];
        $options = [];
        foreach ($argv as $arg) {
            if (in_array($arg, ['--help', '-h'], true)) {
                $command ??= 'help';
            } elseif (str_starts_with($arg, '--') && strlen($arg) > 2) {
                $pair = explode('=', substr($arg, 2), 2);
                $options[$pair[0]] = $pair[1] ?? true;
            } elseif ($command === null) {
                $command = $arg;
            } else {
                $args[] = $arg;
            }
        }

        return [$command, $args, $options];
    }

    /** @param array<string,string|true> $options */
    private function stringOption(array $options, string $name): ?string
    {
        $value = $options[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string,string|true> $options */
    private function validationOptions(array $options, ?SchemaVersion $expect = null): ValidationOptions
    {
        $asOf = null;
        $raw = $this->stringOption($options, 'as-of');
        if ($raw !== null) {
            $asOf = DateTimeImmutable::createFromFormat('!Y-m-d', $raw, new DateTimeZone('Europe/Stockholm'));
            if ($asOf === false || $asOf->format('Y-m-d') !== $raw) {
                throw new UsageError('--as-of ska vara ÅÅÅÅ-MM-DD / must be YYYY-MM-DD');
            }
        }

        $versionOption = $this->stringOption($options, 'version');
        if ($expect === null && $versionOption !== null && strtolower($versionOption) !== 'auto') {
            $expect = SchemaVersion::fromOption($versionOption) ?? throw new UsageError('--version: auto, v6, v1.0 eller v1.1');
        }

        return new ValidationOptions($asOf, $expect, isset($options['policy']), ! isset($options['no-schema']));
    }
}
