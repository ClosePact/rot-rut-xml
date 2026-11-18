<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

use Closepact\RotRutXml\Reporting\Category;
use Closepact\RotRutXml\Reporting\Severity;

final readonly class RuleDefinition
{
    /**
     * @param  list<string>  $sources  ids in resources/sources.json
     * @param  list<string>  $versions  format versions the rule is checked for (`v6`, `v1.0`, `v1.1`); empty = all
     * @param  string  $status  `verified` (a primary source states it) or `verify_required` (inferred; kept as a warning)
     */
    public function __construct(
        public string $code,
        public Category $category,
        public Severity $severity,
        public string $sv,
        public string $en,
        public ?string $fixSv = null,
        public ?string $fixEn = null,
        public array $sources = [],
        public array $versions = [],
        public string $status = 'verified',
    ) {}

    /** @param array<string,string|int> $params */
    public function message(string $language, array $params = []): string
    {
        $text = $language === 'en' ? $this->en : $this->sv;
        foreach ($params as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return $text;
    }

    public function fix(string $language): ?string
    {
        return $language === 'en' ? $this->fixEn : $this->fixSv;
    }

    /** @return list<array{id:string,title:string,url:string}> */
    public function sourceList(): array
    {
        return array_map(static function (string $id): array {
            $source = SourceRegistry::get($id);

            return ['id' => $id, 'title' => $source['title'], 'url' => $source['url']];
        }, $this->sources);
    }
}
