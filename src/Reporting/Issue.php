<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Reporting;

use Closepact\RotRutXml\Rules\RuleCatalog;

/**
 * One finding. Text, severity, category and sources come from the rule catalog,
 * so the same code always means the same thing.
 *
 * Messages never contain identity numbers: a report may end up in a log or a
 * ticket, and the path tells the reader where to look.
 */
final readonly class Issue
{
    /**
     * @param  array<string,string|int>  $params  values for the message placeholders
     */
    public function __construct(
        public string $code,
        public ?string $path = null,
        public array $params = [],
        public ?int $line = null,
        /** 1-based buyer position, when the finding is about one buyer. */
        public ?int $claim = null,
    ) {}

    public function severity(): Severity
    {
        return RuleCatalog::get($this->code)->severity;
    }

    public function category(): Category
    {
        return RuleCatalog::get($this->code)->category;
    }

    public function message(string $language = 'sv'): string
    {
        return RuleCatalog::get($this->code)->message($language, $this->params);
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $rule = RuleCatalog::get($this->code);

        return array_filter([
            'code' => $this->code,
            'severity' => $rule->severity->value,
            'category' => $rule->category->value,
            'path' => $this->path,
            'line' => $this->line,
            'claim' => $this->claim,
            'message_sv' => $rule->message('sv', $this->params),
            'message_en' => $rule->message('en', $this->params),
            'fix_sv' => $rule->fix('sv'),
            'fix_en' => $rule->fix('en'),
            'status' => $rule->status,
            'sources' => $rule->sourceList(),
        ], static fn (mixed $v): bool => $v !== null && $v !== []);
    }
}
