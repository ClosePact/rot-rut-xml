# rot-rut-xml

Verktyg för att skapa och kontrollera XML-filer med begäran om utbetalning för rot- och rutarbete, för import i Skatteverkets e-tjänst *Rot och rut – företag*.

[English](README.en.md) · [Regler och koder](docs/rules.md) · [Källor](docs/regulatory-sources.md)

> **Inte från Skatteverket.** Det här är ett fristående verktyg med öppen källkod. Det är inte utvecklat, granskat eller godkänt av Skatteverket. En fil som klarar kontrollen kan ändå avvisas eller få avslag, och verktyget säger ingenting om köparens rätt till skattereduktion. Skatteverket prövar varje begäran. Kontrollen ersätter inte skatte- eller redovisningsrådgivning.

## Vad det gör

- **Kontrollerar** en XML-fil i version 6, 1.0 eller 1.1. Först säker inläsning, sedan version (från namnrymden), sedan Skatteverkets XSD, sedan de regler som XSD:n inte kan uttrycka. Exempel på sådana regler: begärt belopp högst betalt belopp, begärt plus betalt högst arbetskostnaden, samma betalningsår för alla köpare, köparen minst 18 år under betalningsåret, fastighetsuppgifter för rot, tjänstekoder som passar typen av begäran, giltiga person- och organisationsnummer, sista dag för begäran och rotandelen på tre sjundedelar.
- **Skapar** en fil från strukturerade indata (JSON eller PHP-array) i valfri version. Filen kontrolleras innan den lämnas ut, och generatorn hittar aldrig på värden: timmar, kostnader och 2027 års uppgifter om underentreprenör och bemanningsföretag måste anges uttryckligen.
- **Förklarar** varje fynd på svenska och engelska, med XML-sökväg, en åtgärd där en sådan är säker och länk till primärkällan: lag, Skatteverkets importregler eller schemats dokumentation.

Allt sker lokalt. Filen skickas aldrig någonstans, och verktyget använder bara nätverket när du själv kör `rot-rut schemas:fetch`.

## Vem det är för

Den som bygger eller underhåller fakturerings- och affärssystem för städfirmor, flyttfirmor, hantverkare och andra företag som begär utbetalning för rot och rut. Verktyget passar också redovisningskonsulter och integratörer som vill kontrollera en fil innan den laddas upp.

## Versioner

| Format | Namnrymd | Används | I filen |
|---|---|---|---|
| **XML version 6** (`ht/begaran V6`) | `…/ht/begaran/6.0` | sedan 2021-01-01 | — |
| **XML version 1.0** (`skattered/begaran V1`) | `…/skattered/begaran/1.0` | i dag | utförarens nummer |
| **XML version 1.1** (`skattered/begaran V1.1`) | `…/skattered/begaran/1.1` | från **2027-01-01** | utförarens nummer, underentreprenör, bemanningsföretag |

Version 3 och 4 tas fortfarande emot av Skatteverket men fasas ut. De känns igen men kontrolleras inte här. Formatets version är inte paketets version; se [kompatibilitet](docs/compatibility.md).

**Från 2027-01-01:** en begäran ska ange om underentreprenörer eller bemanningsföretag anlitats, och uppgifterna lämnas på heder och samvete (bet. 2026/27:SkU3, beslutad 2026-11-11). Med version 6 eller 1.0 fylls svaren i manuellt i e-tjänsten efter importen. Version 1.1 har dem i filen. Se [versionsbyte](docs/version-migration.md).

## Installation

Kräver PHP 8.3 eller senare med `dom` och `libxml`.

```bash
composer config repositories.rot-rut-xml vcs https://github.com/ClosePact/rot-rut-xml
composer require closepact/rot-rut-xml:^0.3
vendor/bin/rot-rut schemas:fetch
```

Paketet finns ännu inte på Packagist. Tills dess hämtar Composer det direkt från GitHub (raden `composer config`).

`schemas:fetch` hämtar Skatteverkets XSD-filer över HTTPS och sparar dem bara om kontrollsumman (sha256) stämmer med [manifestet](resources/schemas/manifest.json). Schemana följer inte med paketet, eftersom Skatteverket inte anger villkor för vidarespridning. Se [installation](docs/installation.md).

## Snabbstart (CLI)

```bash
# Kontrollera en fil (svensk text som standard)
vendor/bin/rot-rut validate begaran.xml

# Som JSON, med ansökningsdagen angiven
vendor/bin/rot-rut validate begaran.xml --format=json --as-of=2027-01-12

# Skapa en fil i version 1.1 från JSON
vendor/bin/rot-rut generate indata.json --version=v1.1 --output=begaran.xml

vendor/bin/rot-rut versions     # format som stöds
vendor/bin/rot-rut rules        # alla regler och tjänstekoder
vendor/bin/rot-rut sources      # primärkällor
```

Exempel på utdata:

```text
rot-rut-xml 0.3.0 · XML version 1.1 (skattered/begaran V1.1) · XSD: godkänd · ansökningsdag 2027-01-12
FEL      REQUESTED_EXCEEDS_PAID  /Begaran/Arende[2]/BegartBelopp
         Ärende 2: begärt belopp (900 kr) är större än betalt belopp (750 kr).
         Åtgärd: Begärt belopp får inte vara större än det köparen betalat.
         Källa: https://www.skatteverket.se/foretag/etjansterochblanketter/allaetjanster/schemalagerxml/rotochrutforetag/reglerforattimporterafiltillrotochrut.4.76a43be412206334b89800033198.html
         Källa: https://www.riksdagen.se/sv/dokument-och-lagar/dokument/svensk-forfattningssamling/lag-2009194-om-forfarandet-vid-skattereduktion_sfs-2009-194/
Fel hittades: 1 fel, 0 varningar, 0 noteringar.
```

Exit-koder: `0` inga fel · `1` fel i filen · `2` fel användning · `3` XSD-kontrollen kunde inte köras (ange `--no-schema` för att godta det) · `4` fil- eller nätverksfel. Se [CLI](docs/cli.md).

## Användning i PHP

```php
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Generator\Generator;
use Closepact\RotRutXml\Generator\GenerationFailed;
use Closepact\RotRutXml\Version\SchemaVersion;

$validator = Validator::create();                       // schemana från schemas:fetch
$result = $validator->validateFile('begaran.xml', ValidationOptions::forDate('2027-01-12'));

$result->isValid();          // inga fel i de kontroller som kördes
$result->schemaChecked();    // XSD-kontrollen kördes och godkändes
foreach ($result->errors() as $issue) {
    echo $issue->code, ' ', $issue->path, ': ', $issue->message('sv'), PHP_EOL;
}
$json = $result->toArray();  // samma struktur som --format=json

try {
    $file = (new Generator($validator))->generate($input, SchemaVersion::V1_1);
    file_put_contents('begaran.xml', $file->xml);
} catch (GenerationFailed $e) {
    // $e->issues: vad som saknas eller är fel, med kod och fältsökväg
}
```

Indataformatet beskrivs i [docs/input-format.md](docs/input-format.md), och det finns kompletta exempel i [examples/](examples/). Verktyget är ramverksoberoende och fungerar lika bra i Laravel som i ren PHP.

## Felkoder

Varje fynd har en stabil kod, en nivå (`error`, `warning`, `notice`) och en kategori: XSD (`schema`), regel från primärkälla (`rule`), övergång mellan format (`transition`) och valfri husregel (`policy`, bara med `--policy`). Alla koder, texter och källor finns i [docs/rules.md](docs/rules.md), som genereras från koden. Exempel:

| Kod | Betyder |
|---|---|
| `REQUESTED_EXCEEDS_PAID` | Begärt belopp är större än betalt belopp. |
| `REQUESTED_PLUS_PAID_EXCEEDS_LABOUR` | Begärt plus betalt är mer än arbetskostnaden. |
| `ROT_REQUESTED_EXCEEDS_SHARE` | Rot: mer än tre sjundedelar av betalt belopp (betalningar 2026). |
| `ROT_PROPERTY_REQUIRED` | Rot utan fastighetsbeteckning eller lägenhet och BRF. |
| `PAYMENT_YEAR_MIXED` | Köparna har betalat under olika år. |
| `BUYER_UNDER_18` | Köparen fyller inte 18 under betalningsåret. |
| `SERVICE_CODE_INVALID` | Tjänstekoden passar inte typen av begäran. |
| `DECLARATIONS_IN_E_SERVICE` | Från 2027: svaren om underentreprenör och bemanningsföretag fylls i i e-tjänsten. |

Meddelanden innehåller aldrig person- eller organisationsnummer, så en rapport kan ligga i en logg utan att lämna ut någon.

## Begränsningar

- **Skatteverkets e-tjänst har sista ordet.** Verktyget kontrollerar det Skatteverket publicerar: schemat, importreglerna och lagtexten. E-tjänstens interna kontroller kan vara fler.
- **Version 1.1 är inte provimporterad.** Den är kontrollerad mot Skatteverkets XSD och exempelfiler, men en fil från verktyget har ännu inte importerats i e-tjänsten. Se [kompatibilitet](docs/compatibility.md).
- **Beloppsgränser kontrolleras bara för genomgångna år.** Rotandelen och årstaket kontrolleras för betalningar 2026, där lagtexten är genomgången. För andra år rapporteras `LIMITS_NOT_CHECKED` i stället för en gissning.
- **Tidigare begäran räknas inte in.** Årstaket kontrolleras bara inom en fil, eftersom verktyget inte ser andra begäran för samma köpare.
- **Grön teknik stöds inte.** Det är en annan e-tjänst.

## Dataskydd

Filer innehåller köparnas personnummer. Verktyget läser dem bara lokalt, skickar inget över nätverket, sparar inget och skriver aldrig ut nummer i meddelanden. Testerna och exemplen använder bara Skatteverkets publicerade testpersonnummer. Se [säkerhet](docs/security.md) och [SECURITY.md](SECURITY.md).

## Bidra

Ändringar i regler kräver en primärkälla. Se [CONTRIBUTING.md](CONTRIBUTING.md) och [underhåll](docs/maintenance.md).

## Licens

[MIT](LICENSE) för koden i det här repot. Skatteverkets scheman och exempelfiler ingår inte och omfattas inte av licensen.

---

Underhålls av [Closepact](https://closepact.com/?utm_source=github&utm_medium=opensource&utm_campaign=rot_rut_xml), affärssystem för serviceföretag med offert, avtal, fakturering och rot och rut.
