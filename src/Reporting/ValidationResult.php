<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Reporting;

use Closepact\RotRutXml\Version\SchemaVersion;

/**
 * The outcome of one validation.
 *
 * `isValid()` means no errors were found by the checks that ran. Whether the
 * XSD check ran is reported separately (`schemaStatus`): a file can have no
 * errors while its XSD check was skipped, and callers that need the XSD check
 * should look at both (the CLI does, see docs/cli.md).
 */
final readonly class ValidationResult
{
    public const SCHEMA_PASSED = 'passed';

    public const SCHEMA_FAILED = 'failed';

    public const SCHEMA_SKIPPED = 'skipped';

    public const SCHEMA_NOT_RUN = 'not_run';

    /** @param list<Issue> $issues */
    public function __construct(
        public ?SchemaVersion $version,
        public array $issues,
        public string $schemaStatus,
        public string $asOf,
        public ?string $namespace = null,
    ) {}

    public function isValid(): bool
    {
        return $this->errors() === [];
    }

    public function schemaChecked(): bool
    {
        return $this->schemaStatus === self::SCHEMA_PASSED;
    }

    /** @return list<Issue> */
    public function errors(): array
    {
        return $this->bySeverity(Severity::Error);
    }

    /** @return list<Issue> */
    public function warnings(): array
    {
        return $this->bySeverity(Severity::Warning);
    }

    /** @return list<Issue> */
    public function notices(): array
    {
        return $this->bySeverity(Severity::Notice);
    }

    /** @return list<string> */
    public function codes(?Severity $severity = null): array
    {
        $issues = $severity === null ? $this->issues : $this->bySeverity($severity);

        return array_values(array_map(static fn (Issue $i): string => $i->code, $issues));
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $map = static fn (array $issues): array => array_map(static fn (Issue $i): array => $i->toArray(), $issues);

        return [
            'valid' => $this->isValid(),
            'version' => $this->version?->value,
            'version_label' => $this->version?->label(),
            'namespace' => $this->namespace,
            'schema' => $this->schemaStatus,
            'as_of' => $this->asOf,
            'summary' => [
                'errors' => count($this->errors()),
                'warnings' => count($this->warnings()),
                'notices' => count($this->notices()),
            ],
            'errors' => $map($this->errors()),
            'warnings' => $map($this->warnings()),
            'notices' => $map($this->notices()),
            'disclaimer' => [
                'sv' => 'En kontroll av filen är inte ett besked från Skatteverket och ersätter inte skatterådgivning. Skatteverket prövar varje begäran.',
                'en' => 'A file check is not a decision by Skatteverket and does not replace tax advice. Skatteverket assesses every request.',
            ],
        ];
    }

    /** @return list<Issue> */
    private function bySeverity(Severity $severity): array
    {
        return array_values(array_filter($this->issues, static fn (Issue $i): bool => $i->severity() === $severity));
    }
}
