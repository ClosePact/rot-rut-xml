<?php

declare(strict_types=1);

/*
 * Writes docs/rules.md from the rule catalog, the service catalog, the amount
 * limits and the source registry, so the documentation cannot drift from the
 * code. RuleCatalogTest fails when the committed file is out of date.
 *
 *     php tools/rules-doc.php > docs/rules.md
 */

use Closepact\RotRutXml\Model\Request;
use Closepact\RotRutXml\Model\ServiceCatalog;
use Closepact\RotRutXml\Rules\AmountLimits;
use Closepact\RotRutXml\Rules\RuleCatalog;
use Closepact\RotRutXml\Rules\SourceRegistry;

require __DIR__.'/../vendor/autoload.php';

$out = [];
$out[] = '# Regler och koder / Rules and codes';
$out[] = '';
$out[] = '> Genererad från koden (`php tools/rules-doc.php > docs/rules.md`). Ändra inte för hand.';
$out[] = '> Generated from the code. Do not edit by hand.';
$out[] = '';
$out[] = 'Kategori / category: `input` indata till generatorn, `xml` säker inläsning, `version` format, `schema` Skatteverkets XSD, `structure` grundkontroll när XSD saknas, `rule` regel från primärkälla, `transition` övergångar, `policy` valfria kontroller (`--policy`).';
$out[] = '';
$out[] = 'Status `verify_required`: regeln är tolkad och rapporteras därför bara som varning eller notering. / The rule is an interpretation and is therefore only reported as a warning or notice.';
$out[] = '';
$out[] = '| Kod / code | Kategori | Nivå / severity | Versioner | Status | Källor / sources |';
$out[] = '|---|---|---|---|---|---|';
foreach (RuleCatalog::all() as $rule) {
    $out[] = sprintf('| `%s` | %s | %s | %s | %s | %s |',
        $rule->code, $rule->category->value, $rule->severity->value,
        $rule->versions === [] ? 'alla / all' : implode(', ', $rule->versions),
        $rule->status, $rule->sources === [] ? '–' : implode(', ', array_map(static fn (string $s): string => "[{$s}](#{$s})", $rule->sources)));
}
$out[] = '';
$out[] = '## Texter / messages';
$out[] = '';
foreach (RuleCatalog::all() as $rule) {
    $out[] = '### `'.$rule->code.'`';
    $out[] = '';
    $out[] = '- sv: '.$rule->sv;
    $out[] = '- en: '.$rule->en;
    if ($rule->fixSv !== null) {
        $out[] = '- åtgärd: '.$rule->fixSv;
        $out[] = '- fix: '.$rule->fixEn;
    }
    $out[] = '';
}
$out[] = '## Tjänster / services';
$out[] = '';
$out[] = '| Kod (1.0/1.1) | Typ | Element i V6 | Schablon / flat rate |';
$out[] = '|---|---|---|---|';
foreach ([Request::ROT, Request::RUT] as $type) {
    foreach (ServiceCatalog::codesFor($type) as $code) {
        $out[] = sprintf('| `%s` | %s | `%s` | %s |', $code, $type, ServiceCatalog::v6Element($code), ServiceCatalog::isFlatRate($code) ? 'ja / yes' : '');
    }
}
$out[] = '';
$out[] = '## Beloppsgränser / amount limits';
$out[] = '';
$out[] = 'Kontrolleras bara för betalningar inom en genomgången period; utanför rapporteras `LIMITS_NOT_CHECKED`. / Checked only for payments inside a reviewed period; outside it, `LIMITS_NOT_CHECKED` is reported.';
$out[] = '';
foreach (AmountLimits::checkedPeriods() as $period) {
    $out[] = '- '.$period.': rot ≤ 3/7 av betalt belopp, högst 50 000 kr per köpare och år för rot och 75 000 kr totalt (7 § lagen (2009:194), 67 kap. 19 § IL).';
}
$out[] = '';
$out[] = '## Källor / sources';
$out[] = '';
foreach (SourceRegistry::all() as $id => $source) {
    $out[] = '### '.$id;
    $out[] = '';
    $out[] = '- '.$source['publisher'].': ['.$source['title'].']('.$source['url'].')';
    $out[] = '- läst / read: '.$source['retrieved'];
    $out[] = '- '.$source['covers'];
    $out[] = '';
}

echo implode("\n", $out);
