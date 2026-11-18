<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Rules;

use Closepact\RotRutXml\Reporting\Category;
use Closepact\RotRutXml\Reporting\Severity;
use InvalidArgumentException;

/**
 * Every code the toolkit can report, with its category, severity, text in
 * Swedish and English, and the primary sources behind it.
 *
 * A code never changes meaning. A rule that is no longer valid is removed in a
 * major release, never repurposed (docs/maintenance.md).
 */
final class RuleCatalog
{
    /** @var array<string,RuleDefinition> */
    private static array $rules = [];

    public static function get(string $code): RuleDefinition
    {
        return self::all()[$code] ?? throw new InvalidArgumentException("Unknown rule code '{$code}'.");
    }

    public static function has(string $code): bool
    {
        return isset(self::all()[$code]);
    }

    /**
     * @param  list<string>  $sources
     * @param  list<string>  $versions
     */
    private static function define(string $code, Category $category, Severity $severity, string $sv, string $en, ?string $fixSv = null, ?string $fixEn = null, array $sources = [], array $versions = [], string $status = 'verified'): void
    {
        self::$rules[$code] = new RuleDefinition($code, $category, $severity, $sv, $en, $fixSv, $fixEn, $sources, $versions, $status);
    }

    /** @return array<string,RuleDefinition> */
    public static function all(): array
    {
        if (self::$rules !== []) {
            return self::$rules;
        }

        $e = Severity::Error;
        $w = Severity::Warning;
        $n = Severity::Notice;

        // --- Generator input -------------------------------------------------------
        $i = Category::Input;
        self::define('INPUT_INVALID_JSON', $i, $e, 'Indata är inte giltig JSON: {detail}', 'The input is not valid JSON: {detail}');
        self::define('INPUT_REQUIRED_MISSING', $i, $e, '{field} saknas i indata.', '{field} is missing from the input.');
        self::define('INPUT_INVALID_VALUE', $i, $e, '{field} har ett ogiltigt värde; väntat: {expected}.', '{field} has an invalid value; expected: {expected}.');
        self::define('INPUT_UNKNOWN_FIELD', $i, $e,
            '{field} är inte ett känt fält (stavfel?).', '{field} is not a known field (typo?).',
            'Se docs/input-format.md.', 'See docs/input-format.md.');
        self::define('INPUT_DUPLICATE_SERVICE', $i, $e,
            '{field}: {value} anges två gånger; ange varje tjänst en gång per köpare.',
            '{field}: {value} is listed twice; give each service once per buyer.');
        self::define('INPUT_NOT_FOR_VERSION', $i, $w,
            '{field} skrivs inte i {version} och ignoreras.', '{field} is not written in {version} and is ignored.');

        // --- XML and safety ------------------------------------------------------
        $x = Category::Xml;
        self::define('XML_EMPTY', $x, $e, 'Filen är tom.', 'The file is empty.');
        self::define('XML_TOO_LARGE', $x, $e,
            'Filen är större än {limit} byte och läses inte.',
            'The file is larger than {limit} bytes and is not read.',
            'En begäran har högst 100 köpare; en så stor fil är troligen fel fil.',
            'A request has at most 100 buyers; a file this large is probably the wrong file.');
        self::define('XML_DOCTYPE_FORBIDDEN', $x, $e,
            'Filen innehåller en DOCTYPE- eller ENTITY-deklaration. Sådana används inte i Skatteverkets format och avvisas av säkerhetsskäl.',
            'The file contains a DOCTYPE or ENTITY declaration. Skatteverket\'s formats do not use them and they are refused for safety.',
            'Ta bort deklarationen.', 'Remove the declaration.');
        self::define('XML_MALFORMED', $x, $e,
            'Filen är inte välformad XML: {detail}',
            'The file is not well-formed XML: {detail}');
        self::define('XML_TOO_DEEP', $x, $e,
            'Elementen är nästlade djupare än {limit} nivåer.',
            'Elements are nested deeper than {limit} levels.');

        // --- Version --------------------------------------------------------------
        $v = Category::Version;
        self::define('VERSION_UNKNOWN', $v, $e,
            'Filen är inte en begäran om utbetalning för rot och rut i något känt format (rotelement {root}, namnrymd {namespace}).',
            'The file is not a ROT/RUT payment request in any known format (root element {root}, namespace {namespace}).',
            'Kontrollera att filen är en XML-fil för Rot och rut – företag.', 'Check that the file is an XML file for Rot och rut – företag.',
            ['skv-schema-page']);
        self::define('VERSION_UNSUPPORTED', $v, $e,
            'Filen är skriven i {legacy}. Skatteverket tar fortfarande emot version 3 och 4 men fasar ut dem; det här verktyget kontrollerar bara version 6, 1.0 och 1.1.',
            'The file is written in {legacy}. Skatteverket still accepts versions 3 and 4 but is phasing them out; this toolkit checks only versions 6, 1.0 and 1.1.',
            'Skapa filen i version 6, 1.0 eller 1.1.', 'Produce the file in version 6, 1.0 or 1.1.',
            ['skv-schema-page']);
        self::define('VERSION_MISMATCH', $v, $e,
            'Filen är i {detected}, men {expected} begärdes.',
            'The file is in {detected}, but {expected} was asked for.');

        // --- Schema ---------------------------------------------------------------
        $s = Category::Schema;
        self::define('SCHEMA_INVALID', $s, $e,
            'Filen följer inte Skatteverkets XML-schema för {version}: {detail}',
            'The file does not follow Skatteverket\'s XML schema for {version}: {detail}',
            null, null, ['skv-xsd-v6', 'skv-xsd-v1-0', 'skv-xsd-v1-1']);
        self::define('SCHEMA_UNAVAILABLE', $s, $n,
            'XSD-kontrollen gjordes inte: Skatteverkets schema för {version} finns inte lokalt. Regler och grundläggande struktur kontrollerades.',
            'The XSD check was not run: Skatteverket\'s schema for {version} is not available locally. Rules and basic structure were checked.',
            'Hämta schemana en gång med `rot-rut schemas:fetch`, eller ange en katalog med --schemas.',
            'Fetch the schemas once with `rot-rut schemas:fetch`, or point --schemas at a directory.',
            ['skv-schema-page']);
        self::define('SCHEMA_CHECKSUM_MISMATCH', $s, $n,
            'XSD-kontrollen gjordes inte: den lokala filen {file} skiljer sig från den version verktyget känner till.',
            'The XSD check was not run: the local file {file} differs from the version this toolkit knows.',
            'Skatteverket kan ha ändrat schemat. Hämta det igen; om felet kvarstår, rapportera det (docs/maintenance.md).',
            'Skatteverket may have changed the schema. Fetch it again; if it persists, report it (docs/maintenance.md).',
            ['skv-schema-page']);

        // --- Structure (used only when the XSD could not run) ----------------------
        $st = Category::Structure;
        self::define('STRUCTURE_REQUIRED_MISSING', $st, $e,
            '{field} saknas.', '{field} is missing.',
            null, null, ['skv-xsd-v6', 'skv-xsd-v1-0', 'skv-xsd-v1-1']);
        self::define('STRUCTURE_INVALID_VALUE', $st, $e,
            '{field} har ett ogiltigt värde; väntat: {expected}.',
            '{field} has an invalid value; expected: {expected}.',
            null, null, ['skv-xsd-v6', 'skv-xsd-v1-0', 'skv-xsd-v1-1']);

        // --- Rules (primary sources) -------------------------------------------------
        $r = Category::Rule;
        self::define('REQUEST_TYPE_NOT_ROT_RUT', $r, $e,
            'TypAvBegaran är {value}. E-tjänsten Rot och rut – företag tar bara emot ROT och RUT (grön teknik har en egen e-tjänst).',
            'TypAvBegaran is {value}. The e-service Rot och rut – företag accepts only ROT and RUT (green technology has its own e-service).',
            'Ange ROT eller RUT, en typ per fil.', 'Use ROT or RUT, one type per file.',
            ['skv-xsd-v1-0', 'skv-xsd-v1-1', 'skv-schema-page'], ['v1.0', 'v1.1']);
        self::define('CLAIMS_REQUIRED', $r, $e,
            'Begäran innehåller ingen köpare.', 'The request contains no buyer.',
            'Lägg till minst ett ärende.', 'Add at least one case.',
            ['skv-import-rules']);
        self::define('CLAIMS_TOO_MANY', $r, $e,
            'Begäran innehåller {count} köpare; högst 100 är tillåtna.',
            'The request contains {count} buyers; at most 100 are allowed.',
            'Dela upp köparna på flera filer.', 'Split the buyers over several files.',
            ['skv-import-rules', 'skv-xsd-v1-1']);
        self::define('SERVICE_CODE_INVALID', $r, $e,
            'Ärende {claim}: {value} är inte en giltig tjänst för {type}.',
            'Case {claim}: {value} is not a valid service for {type}.',
            'Använd en av koderna i schemats dokumentation (rot-rut rules visar dem).',
            'Use one of the codes in the schema documentation (rot-rut rules lists them).',
            ['skv-xsd-v1-0', 'skv-xsd-v1-1'], ['v1.0', 'v1.1']);
        self::define('SERVICE_REQUIRED', $r, $e,
            'Ärende {claim}: inget utfört arbete är angivet.',
            'Case {claim}: no work is declared.',
            'Ange vilken tjänst som utförts.', 'State which service was performed.',
            ['sfs-2009-194', 'skv-import-rules']);
        self::define('HOURS_AND_MATERIAL_REQUIRED', $r, $e,
            'Ärende {claim}: arbetade timmar och materialkostnad måste anges för minst ett arbetsområde.',
            'Case {claim}: hours worked and material cost must be given for at least one work area.',
            'Ange faktiskt arbetade timmar (även vid fast pris) och materialkostnad (0 om ingen).',
            'Give the hours actually worked (also for fixed-price work) and the material cost (0 if none).',
            ['skv-import-rules', 'sfs-2009-194']);
        self::define('HOURS_AND_MATERIAL_FLAT_RATE_ONLY', $r, $n,
            'Ärende {claim}: bara schablontjänster är angivna, så inga timmar eller material finns. Skatteverkets importregel nämner timmar och material för minst ett arbetsområde, men Skatteverkets egen exempelfil för version 6 innehåller ett sådant ärende.',
            'Case {claim}: only flat-rate services are declared, so there are no hours or material. Skatteverket\'s import rule mentions hours and material for at least one work area, but Skatteverket\'s own version 6 example file contains such a case.',
            null, null, ['skv-import-rules', 'sfs-2009-194'], [], 'verify_required');
        self::define('FLAT_RATE_HAS_HOURS_OR_COST', $r, $e,
            'Ärende {claim}: {value} är en schablontjänst och anges utan timmar och kostnad.',
            'Case {claim}: {value} is a flat-rate service and is given without hours or cost.',
            'Ta bort AntalTimmar och Kostnad för tjänsten.', 'Remove AntalTimmar and Kostnad for the service.',
            ['skv-xsd-v1-0', 'skv-xsd-v1-1', 'sfs-2009-194'], ['v1.0', 'v1.1']);
        self::define('OTHER_COST_REQUIRED', $r, $e,
            'Ärende {claim}: övrig kostnad saknas. Den ska fyllas i när timmar eller material angetts för någon tjänst.',
            'Case {claim}: other cost is missing. It must be filled in when hours or material are given for any service.',
            'Ange övrig kostnad (0 om ingen).', 'Give the other cost (0 if none).',
            ['skv-xsd-v6', 'skv-xsd-v1-0', 'skv-xsd-v1-1', 'sfs-2009-194']);
        self::define('LABOUR_COST_REQUIRED', $r, $e,
            'Ärende {claim}: arbetskostnaden (Arbetskostnad) saknas; den anges för rot och rut.',
            'Case {claim}: the labour cost (Arbetskostnad) is missing; it is given for ROT and RUT.',
            'Ange priset för arbetet.', 'Give the price of the work.',
            ['skv-xsd-v1-0', 'skv-xsd-v1-1', 'sfs-2009-194'], ['v1.0', 'v1.1']);
        self::define('REQUESTED_EXCEEDS_PAID', $r, $e,
            'Ärende {claim}: begärt belopp ({requested} kr) är större än betalt belopp ({paid} kr).',
            'Case {claim}: the requested amount ({requested} SEK) is larger than the amount paid ({paid} SEK).',
            'Begärt belopp får inte vara större än det köparen betalat.', 'The requested amount may not exceed what the buyer paid.',
            ['skv-import-rules', 'sfs-2009-194']);
        self::define('REQUESTED_PLUS_PAID_EXCEEDS_LABOUR', $r, $e,
            'Ärende {claim}: begärt ({requested} kr) plus betalt ({paid} kr) är mer än arbetskostnaden ({labour} kr).',
            'Case {claim}: requested ({requested} SEK) plus paid ({paid} SEK) is more than the labour cost ({labour} SEK).',
            'Utbetalningen får bara täcka skillnaden mellan debiterat för arbetet och vad köparen betalat.',
            'The payment may only cover the difference between what was charged for the work and what the buyer paid.',
            ['skv-import-rules', 'sfs-2009-194']);
        self::define('ROT_REQUESTED_EXCEEDS_SHARE', $r, $e,
            'Ärende {claim}: för rotarbete får utbetalningen vara högst tre sjundedelar av betalt belopp, alltså {max} kr; begärt är {requested} kr.',
            'Case {claim}: for ROT work the payment may be at most three sevenths of the amount paid, i.e. {max} SEK; {requested} SEK is requested.',
            null, null, ['sfs-2009-194', 'sfs-1999-1229']);
        self::define('LIMITS_NOT_CHECKED', $r, $n,
            'Andelsgränsen för rot och årstaket kontrollerades inte för betalningar under {years}: verktyget kontrollerar dem bara för år där lagtexten är genomgången ({checked}).',
            'The ROT share limit and the annual cap were not checked for payments in {years}: the toolkit checks them only for years whose law text has been reviewed ({checked}).',
            'Kontrollera beloppen mot Skatteverkets regler för året.', 'Check the amounts against Skatteverket\'s rules for that year.',
            ['sfs-2009-194', 'sfs-1999-1229']);
        self::define('ANNUAL_LIMIT_EXCEEDED', $r, $e,
            'Köparen i ärende {claim}: i den här filen begärs {sum} kr för betalningar {year}, mer än {limit} kr som är den högsta skattereduktionen per år för {type}.',
            'Buyer in case {claim}: this file requests {sum} SEK for payments in {year}, more than {limit} SEK, the highest yearly deduction for {type}.',
            'Utbetalning sker inte över gränsen; tidigare begäran för samma köpare och år räknas också.',
            'No payment is made above the limit; earlier requests for the same buyer and year count too.',
            ['sfs-2009-194', 'sfs-1999-1229']);
        self::define('PAYMENT_YEAR_MIXED', $r, $e,
            'Köparna har betalat under olika år ({years}). Alla betalningsdatum i en fil måste gälla samma betalningsår.',
            'The buyers paid in different years ({years}). Every payment date in one file must fall in the same payment year.',
            'Gör en fil per betalningsår.', 'Make one file per payment year.',
            ['skv-import-rules']);
        self::define('PAYMENT_DATE_AFTER_SUBMISSION', $r, $e,
            'Ärende {claim}: betalningsdatum {date} ligger efter ansökningsdagen {asOf}.',
            'Case {claim}: the payment date {date} is after the application date {asOf}.',
            'Begär utbetalning först när köparen har betalat. Ange --as-of om filen ska skickas ett annat datum.',
            'Request payment only after the buyer has paid. Pass --as-of if the file is to be sent on another date.',
            ['skv-import-rules', 'sfs-2009-194']);
        self::define('DEADLINE_PASSED', $r, $e,
            'Ärende {claim}: betalningen gjordes {year}; begäran skulle ha kommit in till Skatteverket senast {deadline}.',
            'Case {claim}: the payment was made in {year}; the request had to reach Skatteverket by {deadline}.',
            null, null, ['sfs-2009-194']);
        self::define('BUYER_ID_INVALID', $r, $e,
            'Ärende {claim}: köparens person- eller samordningsnummer är inte formellt korrekt ({reason}).',
            'Case {claim}: the buyer\'s personnummer or samordningsnummer is not formally correct ({reason}).',
            'Kontrollera numret mot köparens uppgifter.', 'Check the number against the buyer\'s details.',
            ['skv-import-rules', 'skv-704', 'skv-personnummer']);
        self::define('BUYER_NOT_A_PERSON', $r, $e,
            'Ärende {claim}: köparen anges med ett organisationsnummer. Begäran ska innehålla köparens personnummer eller samordningsnummer.',
            'Case {claim}: the buyer is given as an organisation number. A request must hold the buyer\'s personnummer or samordningsnummer.',
            null, null, ['sfs-2009-194', 'sfs-1999-1229']);
        self::define('BUYER_UNDER_18', $r, $e,
            'Ärende {claim}: köparen fyller inte 18 år under betalningsåret {year}.',
            'Case {claim}: the buyer does not turn 18 during the payment year {year}.',
            null, null, ['skv-import-rules']);
        self::define('BUYER_IS_PERFORMER', $r, $e,
            'Ärende {claim}: köparen har samma nummer som utföraren.',
            'Case {claim}: the buyer has the same number as the performer.',
            'Den som utför arbetet kan inte själv få skattereduktion för det.', 'The person doing the work cannot get the deduction for it.',
            ['skv-import-rules', 'sfs-1999-1229'], ['v1.0', 'v1.1']);
        self::define('PERFORMER_ID_INVALID', $r, $e,
            'Utförarens nummer (Utforare) är inte ett formellt korrekt person- eller organisationsnummer ({reason}).',
            'The performer\'s number (Utforare) is not a formally correct personnummer or organisation number ({reason}).',
            'Ange företagets organisationsnummer (enskild firma: innehavarens personnummer). E-tjänsten jämför det med företaget du företräder.',
            'Give the company\'s organisation number (sole trader: the owner\'s personnummer). The e-service compares it with the company you represent.',
            ['skv-schema-page', 'skv-pisa-faltlista', 'skv-704'], ['v1.0', 'v1.1']);
        self::define('ROT_PROPERTY_REQUIRED', $r, $e,
            'Ärende {claim}: fastighetsuppgifter saknas. Rotarbete anges med fastighetsbeteckning, eller med lägenhetsnummer och bostadsrättsföreningens organisationsnummer.',
            'Case {claim}: property information is missing. ROT work states the property designation, or the apartment number and the housing cooperative\'s organisation number.',
            null, null, ['sfs-2009-194', 'skv-xsd-v6', 'skv-xsd-v1-1']);
        self::define('ROT_APARTMENT_INCOMPLETE', $r, $e,
            'Ärende {claim}: för en bostadsrätt anges både lägenhetsnummer och föreningens organisationsnummer; {missing} saknas.',
            'Case {claim}: for a cooperative apartment both the apartment number and the cooperative\'s organisation number are given; {missing} is missing.',
            null, null, ['sfs-2009-194', 'skv-xsd-v6', 'skv-xsd-v1-1']);
        self::define('PROPERTY_NOT_FOR_RUT', $r, $w,
            'Ärende {claim}: fastighetsuppgifter är angivna i en rutbegäran; schemat anger dem bara för rot (och grön teknik).',
            'Case {claim}: property information is given in a RUT request; the schema provides for it only for ROT (and green technology).',
            'Ta bort Fastighet.', 'Remove Fastighet.',
            ['skv-xsd-v1-0', 'skv-xsd-v1-1'], ['v1.0', 'v1.1'], 'verify_required');
        self::define('BRF_ORGNR_INVALID', $r, $e,
            'Ärende {claim}: bostadsrättsföreningens organisationsnummer är inte formellt korrekt ({reason}).',
            'Case {claim}: the housing cooperative\'s organisation number is not formally correct ({reason}).',
            null, null, ['skv-import-rules', 'skv-pisa-faltlista']);

        // --- Transition -------------------------------------------------------------
        $t = Category::Transition;
        self::define('V1_1_NOT_YET_USABLE', $t, $w,
            'Version 1.1 kan användas från och med 2027-01-01; ansökningsdagen är {asOf}.',
            'Version 1.1 can be used from 2027-01-01; the application date is {asOf}.',
            'Skicka filen från 2027-01-01, eller skapa den i version 6 eller 1.0 fram till dess.',
            'Send the file from 2027-01-01, or produce it in version 6 or 1.0 until then.',
            ['skv-schema-page', 'skv-xsd-v1-1', 'riksdagen-sku3'], ['v1.1'], 'verify_required');
        self::define('DECLARATIONS_IN_E_SERVICE', $t, $n,
            'Från 2027-01-01 ska en begäran ange om underentreprenörer eller bemanningsföretag anlitats, och uppgifterna lämnas på heder och samvete. {version} saknar fält för svaren, så de fylls i manuellt i e-tjänsten efter importen.',
            'From 2027-01-01 a request states whether subcontractors or a staffing company were used, and its details are given on "heder och samvete" (on one\'s honour). {version} has no fields for the answers, so they are filled in by hand in the e-service after import.',
            'Använd version 1.1 för att ha uppgifterna i filen.', 'Use version 1.1 to carry the answers in the file.',
            ['skv-schema-page', 'riksdagen-sku3'], ['v6', 'v1.0']);

        // --- Policy (opt-in) ----------------------------------------------------------
        $p = Category::Policy;
        self::define('POLICY_SERVICE_DUPLICATED', $p, $w,
            'Ärende {claim}: {value} anges flera gånger.', 'Case {claim}: {value} is declared more than once.',
            'Slå ihop raderna till en per tjänst.', 'Merge the rows into one per service.');
        self::define('POLICY_SERVICE_WITHOUT_HOURS', $p, $w,
            'Ärende {claim}: {value} anges utan arbetade timmar.', 'Case {claim}: {value} is declared without hours worked.',
            'Lagen kräver antalet arbetade timmar för andra tjänster än schablontjänster; ange faktiska timmar även vid fast pris.',
            'The law asks for the hours worked for all but flat-rate services; give actual hours also for fixed-price work.',
            ['sfs-2009-194', 'skv-import-rules']);
        self::define('POLICY_REQUESTED_ZERO', $p, $w,
            'Ärende {claim}: begärt belopp är 0 kr.', 'Case {claim}: the requested amount is 0 SEK.');
        self::define('POLICY_PROPERTY_AMBIGUOUS', $p, $w,
            'Ärende {claim}: både fastighetsbeteckning och lägenhetsuppgifter är angivna.',
            'Case {claim}: both a property designation and apartment details are given.',
            'Ange fastighetsbeteckning för småhus, eller lägenhetsnummer och föreningens nummer för bostadsrätt.',
            'Give the designation for a house, or the apartment number and cooperative number for a cooperative apartment.',
            ['sfs-2009-194']);
        self::define('POLICY_INVOICE_NUMBER_MISSING', $p, $w,
            'Ärende {claim}: fakturanummer saknas.', 'Case {claim}: the invoice number is missing.',
            'Skatteverkets beslutsfil anger fakturanummer per köpare; med fakturanumret i begäran går besluten att stämma av mot fakturorna.',
            'Skatteverket\'s decision file states an invoice number per buyer; with it in the request, decisions can be matched to invoices.',
            ['skv-beslut-json']);
        self::define('POLICY_INVOICE_NUMBER_DUPLICATED', $p, $w,
            'Ärende {claim}: fakturanumret används också i ärende {other}.', 'Case {claim}: the invoice number is also used in case {other}.');

        return self::$rules;
    }
}
