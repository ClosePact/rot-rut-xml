# Regler och koder / Rules and codes

> Genererad från koden (`php tools/rules-doc.php > docs/rules.md`). Ändra inte för hand.
> Generated from the code. Do not edit by hand.

Kategori / category: `input` indata till generatorn, `xml` säker inläsning, `version` format, `schema` Skatteverkets XSD, `structure` grundkontroll när XSD saknas, `rule` regel från primärkälla, `transition` övergångar, `policy` valfria kontroller (`--policy`).

Status `verify_required`: regeln är tolkad och rapporteras därför bara som varning eller notering. / The rule is an interpretation and is therefore only reported as a warning or notice.

| Kod / code | Kategori | Nivå / severity | Versioner | Status | Källor / sources |
|---|---|---|---|---|---|
| `INPUT_INVALID_JSON` | input | error | alla / all | verified | – |
| `INPUT_REQUIRED_MISSING` | input | error | alla / all | verified | – |
| `INPUT_INVALID_VALUE` | input | error | alla / all | verified | – |
| `INPUT_UNKNOWN_FIELD` | input | error | alla / all | verified | – |
| `INPUT_DUPLICATE_SERVICE` | input | error | alla / all | verified | – |
| `INPUT_NOT_FOR_VERSION` | input | warning | alla / all | verified | – |
| `XML_EMPTY` | xml | error | alla / all | verified | – |
| `XML_TOO_LARGE` | xml | error | alla / all | verified | – |
| `XML_DOCTYPE_FORBIDDEN` | xml | error | alla / all | verified | – |
| `XML_MALFORMED` | xml | error | alla / all | verified | – |
| `XML_TOO_DEEP` | xml | error | alla / all | verified | – |
| `VERSION_UNKNOWN` | version | error | alla / all | verified | [skv-schema-page](#skv-schema-page) |
| `VERSION_UNSUPPORTED` | version | error | alla / all | verified | [skv-schema-page](#skv-schema-page) |
| `VERSION_MISMATCH` | version | error | alla / all | verified | – |
| `SCHEMA_INVALID` | schema | error | alla / all | verified | [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `SCHEMA_UNAVAILABLE` | schema | notice | alla / all | verified | [skv-schema-page](#skv-schema-page) |
| `SCHEMA_CHECKSUM_MISMATCH` | schema | notice | alla / all | verified | [skv-schema-page](#skv-schema-page) |
| `STRUCTURE_REQUIRED_MISSING` | structure | error | alla / all | verified | [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `STRUCTURE_INVALID_VALUE` | structure | error | alla / all | verified | [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `REQUEST_TYPE_NOT_ROT_RUT` | rule | error | v1.0, v1.1 | verified | [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1), [skv-schema-page](#skv-schema-page) |
| `CLAIMS_REQUIRED` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules) |
| `CLAIMS_TOO_MANY` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `SERVICE_CODE_INVALID` | rule | error | v1.0, v1.1 | verified | [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `SERVICE_REQUIRED` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [skv-import-rules](#skv-import-rules) |
| `HOURS_AND_MATERIAL_REQUIRED` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [sfs-2009-194](#sfs-2009-194) |
| `HOURS_AND_MATERIAL_FLAT_RATE_ONLY` | rule | notice | alla / all | verify_required | [skv-import-rules](#skv-import-rules), [sfs-2009-194](#sfs-2009-194) |
| `FLAT_RATE_HAS_HOURS_OR_COST` | rule | error | v1.0, v1.1 | verified | [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1), [sfs-2009-194](#sfs-2009-194) |
| `OTHER_COST_REQUIRED` | rule | error | alla / all | verified | [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1), [sfs-2009-194](#sfs-2009-194) |
| `LABOUR_COST_REQUIRED` | rule | error | v1.0, v1.1 | verified | [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1), [sfs-2009-194](#sfs-2009-194) |
| `REQUESTED_EXCEEDS_PAID` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [sfs-2009-194](#sfs-2009-194) |
| `REQUESTED_PLUS_PAID_EXCEEDS_LABOUR` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [sfs-2009-194](#sfs-2009-194) |
| `ROT_REQUESTED_EXCEEDS_SHARE` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [sfs-1999-1229](#sfs-1999-1229) |
| `LIMITS_NOT_CHECKED` | rule | notice | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [sfs-1999-1229](#sfs-1999-1229) |
| `ANNUAL_LIMIT_EXCEEDED` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [sfs-1999-1229](#sfs-1999-1229) |
| `PAYMENT_YEAR_MIXED` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules) |
| `PAYMENT_DATE_AFTER_SUBMISSION` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [sfs-2009-194](#sfs-2009-194) |
| `DEADLINE_PASSED` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194) |
| `BUYER_ID_INVALID` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [skv-704](#skv-704), [skv-personnummer](#skv-personnummer) |
| `BUYER_NOT_A_PERSON` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [sfs-1999-1229](#sfs-1999-1229) |
| `BUYER_UNDER_18` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules) |
| `BUYER_IS_PERFORMER` | rule | error | v1.0, v1.1 | verified | [skv-import-rules](#skv-import-rules), [sfs-1999-1229](#sfs-1999-1229) |
| `PERFORMER_ID_INVALID` | rule | error | v1.0, v1.1 | verified | [skv-schema-page](#skv-schema-page), [skv-pisa-faltlista](#skv-pisa-faltlista), [skv-704](#skv-704) |
| `ROT_PROPERTY_REQUIRED` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `ROT_APARTMENT_INCOMPLETE` | rule | error | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [skv-xsd-v6](#skv-xsd-v6), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `PROPERTY_NOT_FOR_RUT` | rule | warning | v1.0, v1.1 | verify_required | [skv-xsd-v1-0](#skv-xsd-v1-0), [skv-xsd-v1-1](#skv-xsd-v1-1) |
| `BRF_ORGNR_INVALID` | rule | error | alla / all | verified | [skv-import-rules](#skv-import-rules), [skv-pisa-faltlista](#skv-pisa-faltlista) |
| `V1_1_NOT_YET_USABLE` | transition | warning | v1.1 | verify_required | [skv-schema-page](#skv-schema-page), [skv-xsd-v1-1](#skv-xsd-v1-1), [riksdagen-sku3](#riksdagen-sku3) |
| `DECLARATIONS_IN_E_SERVICE` | transition | notice | v6, v1.0 | verified | [skv-schema-page](#skv-schema-page), [riksdagen-sku3](#riksdagen-sku3) |
| `POLICY_SERVICE_DUPLICATED` | policy | warning | alla / all | verified | – |
| `POLICY_SERVICE_WITHOUT_HOURS` | policy | warning | alla / all | verified | [sfs-2009-194](#sfs-2009-194), [skv-import-rules](#skv-import-rules) |
| `POLICY_REQUESTED_ZERO` | policy | warning | alla / all | verified | – |
| `POLICY_PROPERTY_AMBIGUOUS` | policy | warning | alla / all | verified | [sfs-2009-194](#sfs-2009-194) |
| `POLICY_INVOICE_NUMBER_MISSING` | policy | warning | alla / all | verified | [skv-beslut-json](#skv-beslut-json) |
| `POLICY_INVOICE_NUMBER_DUPLICATED` | policy | warning | alla / all | verified | – |

## Texter / messages

### `INPUT_INVALID_JSON`

- sv: Indata är inte giltig JSON: {detail}
- en: The input is not valid JSON: {detail}

### `INPUT_REQUIRED_MISSING`

- sv: {field} saknas i indata.
- en: {field} is missing from the input.

### `INPUT_INVALID_VALUE`

- sv: {field} har ett ogiltigt värde; väntat: {expected}.
- en: {field} has an invalid value; expected: {expected}.

### `INPUT_UNKNOWN_FIELD`

- sv: {field} är inte ett känt fält (stavfel?).
- en: {field} is not a known field (typo?).
- åtgärd: Se docs/input-format.md.
- fix: See docs/input-format.md.

### `INPUT_DUPLICATE_SERVICE`

- sv: {field}: {value} anges två gånger; ange varje tjänst en gång per köpare.
- en: {field}: {value} is listed twice; give each service once per buyer.

### `INPUT_NOT_FOR_VERSION`

- sv: {field} skrivs inte i {version} och ignoreras.
- en: {field} is not written in {version} and is ignored.

### `XML_EMPTY`

- sv: Filen är tom.
- en: The file is empty.

### `XML_TOO_LARGE`

- sv: Filen är större än {limit} byte och läses inte.
- en: The file is larger than {limit} bytes and is not read.
- åtgärd: En begäran har högst 100 köpare; en så stor fil är troligen fel fil.
- fix: A request has at most 100 buyers; a file this large is probably the wrong file.

### `XML_DOCTYPE_FORBIDDEN`

- sv: Filen innehåller en DOCTYPE- eller ENTITY-deklaration. Sådana används inte i Skatteverkets format och avvisas av säkerhetsskäl.
- en: The file contains a DOCTYPE or ENTITY declaration. Skatteverket's formats do not use them and they are refused for safety.
- åtgärd: Ta bort deklarationen.
- fix: Remove the declaration.

### `XML_MALFORMED`

- sv: Filen är inte välformad XML: {detail}
- en: The file is not well-formed XML: {detail}

### `XML_TOO_DEEP`

- sv: Elementen är nästlade djupare än {limit} nivåer.
- en: Elements are nested deeper than {limit} levels.

### `VERSION_UNKNOWN`

- sv: Filen är inte en begäran om utbetalning för rot och rut i något känt format (rotelement {root}, namnrymd {namespace}).
- en: The file is not a ROT/RUT payment request in any known format (root element {root}, namespace {namespace}).
- åtgärd: Kontrollera att filen är en XML-fil för Rot och rut – företag.
- fix: Check that the file is an XML file for Rot och rut – företag.

### `VERSION_UNSUPPORTED`

- sv: Filen är skriven i {legacy}. Skatteverket tar fortfarande emot version 3 och 4 men fasar ut dem; det här verktyget kontrollerar bara version 6, 1.0 och 1.1.
- en: The file is written in {legacy}. Skatteverket still accepts versions 3 and 4 but is phasing them out; this toolkit checks only versions 6, 1.0 and 1.1.
- åtgärd: Skapa filen i version 6, 1.0 eller 1.1.
- fix: Produce the file in version 6, 1.0 or 1.1.

### `VERSION_MISMATCH`

- sv: Filen är i {detected}, men {expected} begärdes.
- en: The file is in {detected}, but {expected} was asked for.

### `SCHEMA_INVALID`

- sv: Filen följer inte Skatteverkets XML-schema för {version}: {detail}
- en: The file does not follow Skatteverket's XML schema for {version}: {detail}

### `SCHEMA_UNAVAILABLE`

- sv: XSD-kontrollen gjordes inte: Skatteverkets schema för {version} finns inte lokalt. Regler och grundläggande struktur kontrollerades.
- en: The XSD check was not run: Skatteverket's schema for {version} is not available locally. Rules and basic structure were checked.
- åtgärd: Hämta schemana en gång med `rot-rut schemas:fetch`, eller ange en katalog med --schemas.
- fix: Fetch the schemas once with `rot-rut schemas:fetch`, or point --schemas at a directory.

### `SCHEMA_CHECKSUM_MISMATCH`

- sv: XSD-kontrollen gjordes inte: den lokala filen {file} skiljer sig från den version verktyget känner till.
- en: The XSD check was not run: the local file {file} differs from the version this toolkit knows.
- åtgärd: Skatteverket kan ha ändrat schemat. Hämta det igen; om felet kvarstår, rapportera det (docs/maintenance.md).
- fix: Skatteverket may have changed the schema. Fetch it again; if it persists, report it (docs/maintenance.md).

### `STRUCTURE_REQUIRED_MISSING`

- sv: {field} saknas.
- en: {field} is missing.

### `STRUCTURE_INVALID_VALUE`

- sv: {field} har ett ogiltigt värde; väntat: {expected}.
- en: {field} has an invalid value; expected: {expected}.

### `REQUEST_TYPE_NOT_ROT_RUT`

- sv: TypAvBegaran är {value}. E-tjänsten Rot och rut – företag tar bara emot ROT och RUT (grön teknik har en egen e-tjänst).
- en: TypAvBegaran is {value}. The e-service Rot och rut – företag accepts only ROT and RUT (green technology has its own e-service).
- åtgärd: Ange ROT eller RUT, en typ per fil.
- fix: Use ROT or RUT, one type per file.

### `CLAIMS_REQUIRED`

- sv: Begäran innehåller ingen köpare.
- en: The request contains no buyer.
- åtgärd: Lägg till minst ett ärende.
- fix: Add at least one case.

### `CLAIMS_TOO_MANY`

- sv: Begäran innehåller {count} köpare; högst 100 är tillåtna.
- en: The request contains {count} buyers; at most 100 are allowed.
- åtgärd: Dela upp köparna på flera filer.
- fix: Split the buyers over several files.

### `SERVICE_CODE_INVALID`

- sv: Ärende {claim}: {value} är inte en giltig tjänst för {type}.
- en: Case {claim}: {value} is not a valid service for {type}.
- åtgärd: Använd en av koderna i schemats dokumentation (rot-rut rules visar dem).
- fix: Use one of the codes in the schema documentation (rot-rut rules lists them).

### `SERVICE_REQUIRED`

- sv: Ärende {claim}: inget utfört arbete är angivet.
- en: Case {claim}: no work is declared.
- åtgärd: Ange vilken tjänst som utförts.
- fix: State which service was performed.

### `HOURS_AND_MATERIAL_REQUIRED`

- sv: Ärende {claim}: arbetade timmar och materialkostnad måste anges för minst ett arbetsområde.
- en: Case {claim}: hours worked and material cost must be given for at least one work area.
- åtgärd: Ange faktiskt arbetade timmar (även vid fast pris) och materialkostnad (0 om ingen).
- fix: Give the hours actually worked (also for fixed-price work) and the material cost (0 if none).

### `HOURS_AND_MATERIAL_FLAT_RATE_ONLY`

- sv: Ärende {claim}: bara schablontjänster är angivna, så inga timmar eller material finns. Skatteverkets importregel nämner timmar och material för minst ett arbetsområde, men Skatteverkets egen exempelfil för version 6 innehåller ett sådant ärende.
- en: Case {claim}: only flat-rate services are declared, so there are no hours or material. Skatteverket's import rule mentions hours and material for at least one work area, but Skatteverket's own version 6 example file contains such a case.

### `FLAT_RATE_HAS_HOURS_OR_COST`

- sv: Ärende {claim}: {value} är en schablontjänst och anges utan timmar och kostnad.
- en: Case {claim}: {value} is a flat-rate service and is given without hours or cost.
- åtgärd: Ta bort AntalTimmar och Kostnad för tjänsten.
- fix: Remove AntalTimmar and Kostnad for the service.

### `OTHER_COST_REQUIRED`

- sv: Ärende {claim}: övrig kostnad saknas. Den ska fyllas i när timmar eller material angetts för någon tjänst.
- en: Case {claim}: other cost is missing. It must be filled in when hours or material are given for any service.
- åtgärd: Ange övrig kostnad (0 om ingen).
- fix: Give the other cost (0 if none).

### `LABOUR_COST_REQUIRED`

- sv: Ärende {claim}: arbetskostnaden (Arbetskostnad) saknas; den anges för rot och rut.
- en: Case {claim}: the labour cost (Arbetskostnad) is missing; it is given for ROT and RUT.
- åtgärd: Ange priset för arbetet.
- fix: Give the price of the work.

### `REQUESTED_EXCEEDS_PAID`

- sv: Ärende {claim}: begärt belopp ({requested} kr) är större än betalt belopp ({paid} kr).
- en: Case {claim}: the requested amount ({requested} SEK) is larger than the amount paid ({paid} SEK).
- åtgärd: Begärt belopp får inte vara större än det köparen betalat.
- fix: The requested amount may not exceed what the buyer paid.

### `REQUESTED_PLUS_PAID_EXCEEDS_LABOUR`

- sv: Ärende {claim}: begärt ({requested} kr) plus betalt ({paid} kr) är mer än arbetskostnaden ({labour} kr).
- en: Case {claim}: requested ({requested} SEK) plus paid ({paid} SEK) is more than the labour cost ({labour} SEK).
- åtgärd: Utbetalningen får bara täcka skillnaden mellan debiterat för arbetet och vad köparen betalat.
- fix: The payment may only cover the difference between what was charged for the work and what the buyer paid.

### `ROT_REQUESTED_EXCEEDS_SHARE`

- sv: Ärende {claim}: för rotarbete får utbetalningen vara högst tre sjundedelar av betalt belopp, alltså {max} kr; begärt är {requested} kr.
- en: Case {claim}: for ROT work the payment may be at most three sevenths of the amount paid, i.e. {max} SEK; {requested} SEK is requested.

### `LIMITS_NOT_CHECKED`

- sv: Andelsgränsen för rot och årstaket kontrollerades inte för betalningar under {years}: verktyget kontrollerar dem bara för år där lagtexten är genomgången ({checked}).
- en: The ROT share limit and the annual cap were not checked for payments in {years}: the toolkit checks them only for years whose law text has been reviewed ({checked}).
- åtgärd: Kontrollera beloppen mot Skatteverkets regler för året.
- fix: Check the amounts against Skatteverket's rules for that year.

### `ANNUAL_LIMIT_EXCEEDED`

- sv: Köparen i ärende {claim}: i den här filen begärs {sum} kr för betalningar {year}, mer än {limit} kr som är den högsta skattereduktionen per år för {type}.
- en: Buyer in case {claim}: this file requests {sum} SEK for payments in {year}, more than {limit} SEK, the highest yearly deduction for {type}.
- åtgärd: Utbetalning sker inte över gränsen; tidigare begäran för samma köpare och år räknas också.
- fix: No payment is made above the limit; earlier requests for the same buyer and year count too.

### `PAYMENT_YEAR_MIXED`

- sv: Köparna har betalat under olika år ({years}). Alla betalningsdatum i en fil måste gälla samma betalningsår.
- en: The buyers paid in different years ({years}). Every payment date in one file must fall in the same payment year.
- åtgärd: Gör en fil per betalningsår.
- fix: Make one file per payment year.

### `PAYMENT_DATE_AFTER_SUBMISSION`

- sv: Ärende {claim}: betalningsdatum {date} ligger efter ansökningsdagen {asOf}.
- en: Case {claim}: the payment date {date} is after the application date {asOf}.
- åtgärd: Begär utbetalning först när köparen har betalat. Ange --as-of om filen ska skickas ett annat datum.
- fix: Request payment only after the buyer has paid. Pass --as-of if the file is to be sent on another date.

### `DEADLINE_PASSED`

- sv: Ärende {claim}: betalningen gjordes {year}; begäran skulle ha kommit in till Skatteverket senast {deadline}.
- en: Case {claim}: the payment was made in {year}; the request had to reach Skatteverket by {deadline}.

### `BUYER_ID_INVALID`

- sv: Ärende {claim}: köparens person- eller samordningsnummer är inte formellt korrekt ({reason}).
- en: Case {claim}: the buyer's personnummer or samordningsnummer is not formally correct ({reason}).
- åtgärd: Kontrollera numret mot köparens uppgifter.
- fix: Check the number against the buyer's details.

### `BUYER_NOT_A_PERSON`

- sv: Ärende {claim}: köparen anges med ett organisationsnummer. Begäran ska innehålla köparens personnummer eller samordningsnummer.
- en: Case {claim}: the buyer is given as an organisation number. A request must hold the buyer's personnummer or samordningsnummer.

### `BUYER_UNDER_18`

- sv: Ärende {claim}: köparen fyller inte 18 år under betalningsåret {year}.
- en: Case {claim}: the buyer does not turn 18 during the payment year {year}.

### `BUYER_IS_PERFORMER`

- sv: Ärende {claim}: köparen har samma nummer som utföraren.
- en: Case {claim}: the buyer has the same number as the performer.
- åtgärd: Den som utför arbetet kan inte själv få skattereduktion för det.
- fix: The person doing the work cannot get the deduction for it.

### `PERFORMER_ID_INVALID`

- sv: Utförarens nummer (Utforare) är inte ett formellt korrekt person- eller organisationsnummer ({reason}).
- en: The performer's number (Utforare) is not a formally correct personnummer or organisation number ({reason}).
- åtgärd: Ange företagets organisationsnummer (enskild firma: innehavarens personnummer). E-tjänsten jämför det med företaget du företräder.
- fix: Give the company's organisation number (sole trader: the owner's personnummer). The e-service compares it with the company you represent.

### `ROT_PROPERTY_REQUIRED`

- sv: Ärende {claim}: fastighetsuppgifter saknas. Rotarbete anges med fastighetsbeteckning, eller med lägenhetsnummer och bostadsrättsföreningens organisationsnummer.
- en: Case {claim}: property information is missing. ROT work states the property designation, or the apartment number and the housing cooperative's organisation number.

### `ROT_APARTMENT_INCOMPLETE`

- sv: Ärende {claim}: för en bostadsrätt anges både lägenhetsnummer och föreningens organisationsnummer; {missing} saknas.
- en: Case {claim}: for a cooperative apartment both the apartment number and the cooperative's organisation number are given; {missing} is missing.

### `PROPERTY_NOT_FOR_RUT`

- sv: Ärende {claim}: fastighetsuppgifter är angivna i en rutbegäran; schemat anger dem bara för rot (och grön teknik).
- en: Case {claim}: property information is given in a RUT request; the schema provides for it only for ROT (and green technology).
- åtgärd: Ta bort Fastighet.
- fix: Remove Fastighet.

### `BRF_ORGNR_INVALID`

- sv: Ärende {claim}: bostadsrättsföreningens organisationsnummer är inte formellt korrekt ({reason}).
- en: Case {claim}: the housing cooperative's organisation number is not formally correct ({reason}).

### `V1_1_NOT_YET_USABLE`

- sv: Version 1.1 kan användas från och med 2027-01-01; ansökningsdagen är {asOf}.
- en: Version 1.1 can be used from 2027-01-01; the application date is {asOf}.
- åtgärd: Skicka filen från 2027-01-01, eller skapa den i version 6 eller 1.0 fram till dess.
- fix: Send the file from 2027-01-01, or produce it in version 6 or 1.0 until then.

### `DECLARATIONS_IN_E_SERVICE`

- sv: Från 2027-01-01 ska en begäran ange om underentreprenörer eller bemanningsföretag anlitats, och uppgifterna lämnas på heder och samvete. {version} saknar fält för svaren, så de fylls i manuellt i e-tjänsten efter importen.
- en: From 2027-01-01 a request states whether subcontractors or a staffing company were used, and its details are given on "heder och samvete" (on one's honour). {version} has no fields for the answers, so they are filled in by hand in the e-service after import.
- åtgärd: Använd version 1.1 för att ha uppgifterna i filen.
- fix: Use version 1.1 to carry the answers in the file.

### `POLICY_SERVICE_DUPLICATED`

- sv: Ärende {claim}: {value} anges flera gånger.
- en: Case {claim}: {value} is declared more than once.
- åtgärd: Slå ihop raderna till en per tjänst.
- fix: Merge the rows into one per service.

### `POLICY_SERVICE_WITHOUT_HOURS`

- sv: Ärende {claim}: {value} anges utan arbetade timmar.
- en: Case {claim}: {value} is declared without hours worked.
- åtgärd: Lagen kräver antalet arbetade timmar för andra tjänster än schablontjänster; ange faktiska timmar även vid fast pris.
- fix: The law asks for the hours worked for all but flat-rate services; give actual hours also for fixed-price work.

### `POLICY_REQUESTED_ZERO`

- sv: Ärende {claim}: begärt belopp är 0 kr.
- en: Case {claim}: the requested amount is 0 SEK.

### `POLICY_PROPERTY_AMBIGUOUS`

- sv: Ärende {claim}: både fastighetsbeteckning och lägenhetsuppgifter är angivna.
- en: Case {claim}: both a property designation and apartment details are given.
- åtgärd: Ange fastighetsbeteckning för småhus, eller lägenhetsnummer och föreningens nummer för bostadsrätt.
- fix: Give the designation for a house, or the apartment number and cooperative number for a cooperative apartment.

### `POLICY_INVOICE_NUMBER_MISSING`

- sv: Ärende {claim}: fakturanummer saknas.
- en: Case {claim}: the invoice number is missing.
- åtgärd: Skatteverkets beslutsfil anger fakturanummer per köpare; med fakturanumret i begäran går besluten att stämma av mot fakturorna.
- fix: Skatteverket's decision file states an invoice number per buyer; with it in the request, decisions can be matched to invoices.

### `POLICY_INVOICE_NUMBER_DUPLICATED`

- sv: Ärende {claim}: fakturanumret används också i ärende {other}.
- en: Case {claim}: the invoice number is also used in case {other}.

## Tjänster / services

| Kod (1.0/1.1) | Typ | Element i V6 | Schablon / flat rate |
|---|---|---|---|
| `BYGG` | ROT | `Bygg` |  |
| `EL` | ROT | `El` |  |
| `GLASARBETE` | ROT | `GlasPlatarbete` |  |
| `MARKARBETE` | ROT | `MarkDraneringarbete` |  |
| `MURNING` | ROT | `Murning` |  |
| `MALNING_TAPETSERING` | ROT | `MalningTapetsering` |  |
| `VVS` | ROT | `Vvs` |  |
| `STADNING` | RUT | `Stadning` |  |
| `KLAD_TEXTILVARD` | RUT | `KladOchTextilvard` |  |
| `SNOSKOTTNING` | RUT | `Snoskottning` |  |
| `TRADGARDSARBETE` | RUT | `Tradgardsarbete` |  |
| `BARNPASSNING` | RUT | `Barnpassning` |  |
| `PERSONLIG_OMSORG` | RUT | `Personligomsorg` |  |
| `FLYTTJANSTER` | RUT | `Flyttjanster` |  |
| `IT_TJANSTER` | RUT | `ItTjanster` |  |
| `REPARATION_VITVAROR` | RUT | `ReparationAvVitvaror` |  |
| `MOBLERING` | RUT | `Moblering` |  |
| `TILLSYN_AV_BOSTAD` | RUT | `TillsynAvBostad` |  |
| `TRANSPORT_TILL_FORSALJNING` | RUT | `TransportTillForsaljning` | ja / yes |
| `TVATT_VID_TVATTINRATTNING` | RUT | `TvattVidTvattinrattning` | ja / yes |

## Beloppsgränser / amount limits

Kontrolleras bara för betalningar inom en genomgången period; utanför rapporteras `LIMITS_NOT_CHECKED`. / Checked only for payments inside a reviewed period; outside it, `LIMITS_NOT_CHECKED` is reported.

- 2026-01-01–2026-12-31: rot ≤ 3/7 av betalt belopp, högst 50 000 kr per köpare och år för rot och 75 000 kr totalt (7 § lagen (2009:194), 67 kap. 19 § IL).

## Källor / sources

### skv-schema-page

- Skatteverket: [Schemalager (XML) – Rot och rut (företag): scheman och exempelfiler](https://www.skatteverket.se/foretag/etjansterochblanketter/allaetjanster/schemalagerxml/rotochrutforetag.4.71004e4c133e23bf6db800063583.html)
- läst / read: 2026-11-18
- Supported XML versions (1.1, 1.0, 6; 3 and 4 still work but will be phased out), 1.1 usable from 2027-01-01, older versions need the new answers entered manually in the e-service, V1.0 must contain the company's organisation number, ROT and RUT cannot be mixed in one file.

### skv-import-rules

- Skatteverket: [Regler för att importera fil till Rot och rut – företag](https://www.skatteverket.se/foretag/etjansterochblanketter/allaetjanster/schemalagerxml/rotochrutforetag/reglerforattimporterafiltillrotochrut.4.76a43be412206334b89800033198.html)
- läst / read: 2026-11-18
- Checks made in the e-service after import (buyer number formally correct, buyer not the performer, buyer at least 18 in the payment year, payment date not after the application date, requested within the deduction limit, requested ≤ paid, requested + paid ≤ labour cost, one payment year per file, housing cooperative number formally correct, hours and material for at least one work area) and checks made by the schema (numeric personnummer, payment date after 2009-06-30, at least one buyer, at most 100 buyers); fixed-price work states the actual hours.

### skv-xsd-v6

- Skatteverket: [Begaran.xsd and BegaranCOMPONENT.xsd, XML version 6](https://xmls.skatteverket.se/se/skatteverket/ht/komponent/V6/BegaranCOMPONENT.xsd)
- läst / read: 2026-11-18
- V6 structure, field formats, element documentation (e.g. Ovrigkostnad 'Ska fyllas i om timmar eller material angetts för någon tjänst i utfört arbete').

### skv-xsd-v1-0

- Skatteverket: [Begaran.xsd, XML version 1.0 (skattered/begaran V1)](https://xmls.skatteverket.se/se/skatteverket/skattered/begaran/V1/Begaran.xsd)
- läst / read: 2026-11-18
- 1.0 structure; 2025-08-26 note that ROT and RUT are allowed and validated outside the schema, and that the ROT/RUT e-service does not accept GRON_TEKNIK; valid service codes in the documentation of TypAvUtfortArbete.

### skv-xsd-v1-1

- Skatteverket: [Begaran.xsd, XML version 1.1 (skattered/begaran V1.1)](https://xmls.skatteverket.se/se/skatteverket/skattered/begaran/V1.1/Begaran.xsd)
- läst / read: 2026-11-18
- 1.1 structure (Underentreprenor, Bemanningsforetag mandatory true/false; Systemkontakt optional; no xsi:nil), effectiveFrom 2027-01-01, modified 2026-11-09, service codes, flat-rate services without hours or cost, Fastighet only for ROT (and green technology), Arbetskostnad only for ROT and RUT.

### sfs-2009-194

- Riksdagen (Svensk författningssamling): [Lag (2009:194) om förfarandet vid skattereduktion för hushållsarbete, consolidated up to SFS 2025:324](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/svensk-forfattningssamling/lag-2009194-om-forfarandet-vid-skattereduktion_sfs-2009-194/)
- läst / read: 2026-11-18
- 5 § whole kronor; 7 § payment at most the difference between charged incl. VAT and paid, at most the paid amount (67 kap. 13 § IL) or three sevenths of it (other work), 75 000 kr / 50 000 kr per year; 8 § filed electronically, received by 31 January the year after payment; 9 § content (buyer's personnummer or samordningsnummer, amounts, payment day, hours except 67 kap. 13 § 12–13 IL); 9 a § property designation, apartment and housing cooperative number for ROT.

### sfs-1999-1229

- Riksdagen (Svensk författningssamling): [Inkomstskattelag (1999:1229), 67 kap. 11–19 §§ (skattereduktion för hushållsarbete)](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/svensk-forfattningssamling/inkomstskattelag-19991229_sfs-1999-1229/)
- läst / read: 2026-11-18
- 13 § household services (12: laundry at a laundry facility, 13: transport to resale), 13 a–13 b §§ ROT, 15 § the person doing the work cannot be the buyer, 19 § 50 / 30 percent, 75 000 kr and 50 000 kr per year (Lag 2025:322).

### riksdagen-sku3

- Riksdagen: [Betänkande 2026/27:SkU3 Effektivare kontrollmöjligheter i systemen för rot, rut, grön teknik och personalliggare](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/betankande/effektivare-kontrollmojligheter-i-systemen-for-rot_he01sku3/)
- läst / read: 2026-11-18
- Decided 2026-11-11: a payment request is given 'på heder och samvete' and states whether subcontractors or staffing companies were used; the changes apply from 2027-01-01. The amending SFS number was not yet in the consolidated text on 2026-11-18.

### prop-2025-26-282

- Regeringen: [Prop. 2025/26:282 Effektivare kontrollmöjligheter i systemen för rot, rut, grön teknik och personalliggare](https://www.regeringen.se/rattsliga-dokument/proposition/2026/06/prop.-202526282)
- läst / read: 2026-11-18
- The bill XML version 1.1 was made for (as linked from Skatteverket's schema page).

### skv-704

- Skatteverket: [SKV 704 utgåva 8 (september 2007), Personnummer – check digit by the modulus-10 method with weights 1 and 2](https://docs.swedenconnect.se/technical-framework/mirror/skv/skv704-8.pdf)
- läst / read: 2026-11-18
- Check digit method for personnummer and samordningsnummer. This is a mirror kept by Sweden Connect; Skatteverket's own copy of this edition was not found on skatteverket.se (VERIFY_REQUIRED: current edition).

### skv-personnummer

- Skatteverket: [Personnummer och samordningsnummer](https://www.skatteverket.se/privat/folkbokforing/personnummerochsamordningsnummer.4.3810a01c150939e893f18c29.html)
- läst / read: 2026-11-18
- Structure of the personnummer; the hyphen becomes a plus sign the year a person turns 100; the last digit is a check digit.

### skv-pisa-faltlista

- Skatteverket: [Fält i XML – för flyttkontrolluppgift och åtaganden – Version 2](https://www.skatteverket.se/download/18.7eada0316ed67d728240c9/1708607929567/Pisa_faltlista_v2_0.pdf)
- läst / read: 2026-11-18
- Organisationsnummer: ten digits, third digit at least 2, last digit a check digit; written with the fictitious century digits 16 it has twelve digits.

### skv-beslut-json

- Skatteverket: [Beslut.json, JSON schema for the decision export (skattered/begaran V1)](https://xmls.skatteverket.se/se/skatteverket/skattered/begaran/V1/Beslut.json)
- läst / read: 2026-11-18
- Decision export: fakturanummer is a required property, so an invoice number in the request lets decisions be matched back to invoices.

### skv-testpersonnummer

- Skatteverket (öppna data, CC0 1.0): [Testpersonnummer and Testsamordningsnummer](https://skatteverket.entryscape.net/rowstore/dataset/b4de7df7-63c0-4e7e-bb59-1f156a591763)
- läst / read: 2026-11-18
- Fictitious numbers for test environments ('får bara användas i testmiljöer'); the only identity numbers used in this repository's fixtures and tests. Samordningsnummer: https://skatteverket.entryscape.net/rowstore/dataset/9f29fe09-4dbc-4d2f-848f-7cffdd075383
