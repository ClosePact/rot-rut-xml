<?php

declare(strict_types=1);

namespace Closepact\RotRutXml;

use Closepact\RotRutXml\Parser\RequestParser;
use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\Reporting\ValidationResult;
use Closepact\RotRutXml\Rules\RuleEngine;
use Closepact\RotRutXml\Rules\ValidationContext;
use Closepact\RotRutXml\Schema\SchemaStatus;
use Closepact\RotRutXml\Schema\SchemaStore;
use Closepact\RotRutXml\Schema\XsdValidator;
use Closepact\RotRutXml\Version\VersionDetector;
use Closepact\RotRutXml\Xml\SafeXmlLoader;
use RuntimeException;

/**
 * Validates a ROT/RUT payment-request file, locally.
 *
 *     $result = Validator::create()->validateFile('begaran.xml');
 *     $result->isValid();          // no errors from the checks that ran
 *     $result->schemaChecked();    // the XSD check ran and passed
 *
 * Order: safe parse → version → XSD (when the schema is installed) → rules.
 * When the XSD could not run, built-in structural checks stand in for it and
 * a SCHEMA_UNAVAILABLE notice says so.
 */
final class Validator
{
    public function __construct(
        private readonly SchemaStore $schemas,
        private readonly SafeXmlLoader $loader = new SafeXmlLoader,
        private readonly VersionDetector $detector = new VersionDetector,
        private readonly RequestParser $parser = new RequestParser,
        private readonly RuleEngine $rules = new RuleEngine,
    ) {}

    /** @param string|null $schemaDirectory where Skatteverket's schemas are kept (see SchemaStore::default) */
    public static function create(?string $schemaDirectory = null): self
    {
        return new self(SchemaStore::default($schemaDirectory));
    }

    public function schemaStore(): SchemaStore
    {
        return $this->schemas;
    }

    public function validateFile(string $path, ?ValidationOptions $options = null): ValidationResult
    {
        $xml = @file_get_contents($path);
        if ($xml === false) {
            throw new RuntimeException("Cannot read {$path}.");
        }

        return $this->validateString($xml, $options);
    }

    public function validateString(string $xml, ?ValidationOptions $options = null): ValidationResult
    {
        $options ??= new ValidationOptions;
        $asOf = $options->asOf->format('Y-m-d');

        $loaded = $this->loader->load($xml);
        if ($loaded->document === null) {
            return new ValidationResult(null, $loaded->issues, ValidationResult::SCHEMA_NOT_RUN, $asOf);
        }

        $detected = $this->detector->detect($loaded->document);
        if ($detected->version === null) {
            $issue = $detected->legacy !== null
                ? new Issue('VERSION_UNSUPPORTED', '/'.$detected->rootName, ['legacy' => $detected->legacy])
                : new Issue('VERSION_UNKNOWN', '/'.$detected->rootName, ['root' => $detected->rootName, 'namespace' => $detected->namespace ?: '(ingen / none)']);

            return new ValidationResult(null, [$issue], ValidationResult::SCHEMA_NOT_RUN, $asOf, $detected->namespace);
        }

        $version = $detected->version;
        $issues = [];
        if ($options->expectVersion !== null && $options->expectVersion !== $version) {
            $issues[] = new Issue('VERSION_MISMATCH', '/Begaran', ['detected' => $version->label(), 'expected' => $options->expectVersion->label()]);
        }

        $parsed = $this->parser->parse($loaded->document, $version);

        $schemaStatus = ValidationResult::SCHEMA_SKIPPED;
        $status = $options->useSchema ? $this->schemas->status($version) : null;
        if ($status !== null && $status->isUsable()) {
            $schemaIssues = (new XsdValidator($this->schemas))->validate($loaded->document, $version);
            $schemaStatus = $schemaIssues === [] ? ValidationResult::SCHEMA_PASSED : ValidationResult::SCHEMA_FAILED;
            array_push($issues, ...$schemaIssues);
        } else {
            if ($status !== null) {
                $issues[] = $status->state === SchemaStatus::CHECKSUM
                    ? new Issue('SCHEMA_CHECKSUM_MISMATCH', params: ['file' => $status->file])
                    : new Issue('SCHEMA_UNAVAILABLE', params: ['version' => $version->label()]);
            }
            array_push($issues, ...$parsed->structureIssues);
        }

        $context = new ValidationContext($options->asOf, $options->policy);
        array_push($issues, ...$this->rules->check($parsed->request, $context));

        return new ValidationResult($version, $issues, $schemaStatus, $asOf, $detected->namespace);
    }
}
