# Regulatory sources

Every rule in this toolkit rests on a primary source: Swedish statute text on riksdagen.se, or Skatteverket's own pages and schema files. All sources were read on **2026-11-18**. The machine-readable registry is [resources/sources.json](../resources/sources.json), and the per-code mapping is in [rules.md](rules.md).

## Formats and dates

| Fact | Source | Quote |
|---|---|---|
| Versions 1.1, 1.0 and 6 can be imported; 3 and 4 still work but will be phased out | Skatteverket, [Schemalager (XML) – Rot och rut](https://www.skatteverket.se/foretag/etjansterochblanketter/allaetjanster/schemalagerxml/rotochrutforetag.4.71004e4c133e23bf6db800063583.html) | "XML version 3 och 4 fungerar fortfarande men kommer på sikt att fasas ut." |
| 1.1 usable from 2027-01-01 | same page; XSD 1.1 metadata `effectiveFrom 2027-01-01`, `modified 2026-11-09` | "Om förslagen beslutas kan version 1.1 användas från och med den 1 januari 2027." |
| Older versions need the new answers entered in the e-service | same page | "Det går att använda äldre versioner men då behöver de nya uppgifterna fyllas i manuellt i e-tjänsten." |
| 1.0 must contain the company's organisation number | same page | "XML-filen ska innehålla företagets organisationsnummer." |
| ROT and RUT cannot share a file | same page and the import rules | "Du kan till exempel inte blanda begäran om utbetalning för rotarbete och utbetalning för rutarbete i samma fil." |
| 2027 law change: requests on *heder och samvete*, subcontractors/staffing company stated | Riksdagen, [bet. 2026/27:SkU3](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/betankande/effektivare-kontrollmojligheter-i-systemen-for-rot_he01sku3/), decided 2026-11-11; [prop. 2025/26:282](https://www.regeringen.se/rattsliga-dokument/proposition/2026/06/prop.-202526282) | "… uppgifterna i en begäran om utbetalning lämnas på heder och samvete. Utförare ska även … ange om underentreprenörer eller bemanningsföretag har anlitats …" "Lagändringarna börjar gälla den 1 januari 2027." |
| 1.1 elements `Underentreprenor`, `Bemanningsforetag` mandatory; no `xsi:nil`; optional `Systemkontakt` | [Begaran.xsd V1.1](https://xmls.skatteverket.se/se/skatteverket/skattered/begaran/V1.1/Begaran.xsd) | "Elementen är obligatoriska och ska anges med värdet true eller false." |
| The ROT/RUT e-service does not accept green technology | [Begaran.xsd V1.0](https://xmls.skatteverket.se/se/skatteverket/skattered/begaran/V1/Begaran.xsd), note of 2025-08-26 | "… tillåter där rot- eller rutbegäran, men inte grön teknik." |

## Rules checked in the e-service after import

Source: Skatteverket, [Regler för att importera fil till Rot och rut – företag](https://www.skatteverket.se/foretag/etjansterochblanketter/allaetjanster/schemalagerxml/rotochrutforetag/reglerforattimporterafiltillrotochrut.4.76a43be412206334b89800033198.html).

| Skatteverket's rule | Code |
|---|---|
| "Köparens person-/organisationsnummer ska vara formellt korrekt." | `BUYER_ID_INVALID` |
| "Köparens personnummer får inte vara identiskt med utförarens personnummer." | `BUYER_IS_PERFORMER` (1.x; V6 does not carry the performer) |
| "Köparen ska fylla minst 18 år under betalningsåret" | `BUYER_UNDER_18` |
| "Betalningsdatum får inte vara senare än ansökningsdatum" | `PAYMENT_DATE_AFTER_SUBMISSION` (with `--as-of` as the application date) |
| "Begärt belopp får inte överstiga gränsen för skattereduktion." | `ROT_REQUESTED_EXCEEDS_SHARE`, `ANNUAL_LIMIT_EXCEEDED` (see the law below) |
| "Begärt belopp får inte vara större än Betalt belopp." | `REQUESTED_EXCEEDS_PAID` |
| "Begärt belopp + Betalt belopp får inte vara större än arbetskostnaden." | `REQUESTED_PLUS_PAID_EXCEEDS_LABOUR` |
| "Betalningsdatum för samtliga köpare måste gälla för samma betalningsår." | `PAYMENT_YEAR_MIXED` |
| "Bostadsrättsföreningens organisationsnummer ska vara formellt korrekt" | `BRF_ORGNR_INVALID` |
| "Arbetade timmar och materialkostnad måste anges för minst ett arbetsområde." | `HOURS_AND_MATERIAL_REQUIRED` (see VERIFY below) |
| Schema rules: "Det måste alltid finnas uppgifter om minst en köpare." "Max antal köpare är 100 st." | `CLAIMS_REQUIRED`, `CLAIMS_TOO_MANY` (the V6 XSD has no maximum, so the rule is checked separately) |
| "För arbeten med fastpris ska alltid faktiskt antal arbetade timmar anges." | `POLICY_SERVICE_WITHOUT_HOURS` (opt-in) |

## Statute

[Lag (2009:194) om förfarandet vid skattereduktion för hushållsarbete](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/svensk-forfattningssamling/lag-2009194-om-forfarandet-vid-skattereduktion_sfs-2009-194/), consolidated up to SFS 2025:324:

- **5 §**: amounts in whole kronor. The generator refuses decimals.
- **7 §**: the payment is at most the difference between the amount charged incl. VAT and what the buyer paid (`REQUESTED_PLUS_PAID_EXCEEDS_LABOUR`). It is at most the amount paid for household services under 67 kap. 13 § IL, i.e. RUT (`REQUESTED_EXCEEDS_PAID`), and *tre sjundedelar* of it for other work, i.e. ROT (`ROT_REQUESTED_EXCEEDS_SHARE`). The buyer's preliminary reduction may not exceed 75 000 kr a year, of which 50 000 kr for ROT (`ANNUAL_LIMIT_EXCEEDED`, counted within one file).
- **8 §**: filed electronically, and received by Skatteverket "senast den 31 januari året efter det beskattningsår då arbetet betalades" (`DEADLINE_PASSED`).
- **9 §**: the request contains the buyer's personnummer or samordningsnummer (`BUYER_NOT_A_PERSON`), the amounts charged for work, material and other (`OTHER_COST_REQUIRED`, `LABOUR_COST_REQUIRED`), the amount paid and payment day, and what the work was and the hours worked, "utom i fråga om sådant arbete som avses i 67 kap. 13 § 12 och 13". Those are the flat-rate services (`FLAT_RATE_HAS_HOURS_OR_COST`, `SERVICE_REQUIRED`).
- **9 a §**: for ROT, the designation of the house or owner-occupied flat, or the apartment's designation and the cooperative's organisation number (`ROT_PROPERTY_REQUIRED`, `ROT_APARTMENT_INCOMPLETE`).

[Inkomstskattelag (1999:1229) 67 kap.](https://www.riksdagen.se/sv/dokument-och-lagar/dokument/svensk-forfattningssamling/inkomstskattelag-19991229_sfs-1999-1229/):

- **13 §** lists the household services; 12 is laundry at a laundry facility and 13 is transport to resale.
- **13 a–13 b §§** cover ROT.
- **15 §**: the person doing the work cannot be the one claiming the deduction (`BUYER_IS_PERFORMER`).
- **19 §**: 50 / 30 percent, 75 000 kr and 50 000 kr (Lag 2025:322).

**Amount limits are versioned by payment date** ([AmountLimits.php](../src/Rules/AmountLimits.php)). Only 2026-01-01 – 2026-12-31 is reviewed. Payments outside a reviewed period get `LIMITS_NOT_CHECKED`, never last year's figures. The 2025 temporary increase of ROT (12 May – 31 December 2025) is deliberately not encoded, because its exact wording was not reviewed.

## Identity numbers

| Fact | Source |
|---|---|
| Check digit by "modulus-10-metoden med vikterna 1 och 2" (personnummer and samordningsnummer) | SKV 704 utgåva 8 (Sept 2007), [mirror at Sweden Connect](https://docs.swedenconnect.se/technical-framework/mirror/skv/skv704-8.pdf) |
| Hyphen becomes a plus sign the year a person turns 100 | Skatteverket, [Personnummer och samordningsnummer](https://www.skatteverket.se/privat/folkbokforing/personnummerochsamordningsnummer.4.3810a01c150939e893f18c29.html) |
| Organisationsnummer: ten digits, third digit ≥ 2, check digit last; 12-digit form with "två inledande fiktiva sekelsiffror (16)" | Skatteverket, [Fält i XML – för flyttkontrolluppgift och åtaganden, v2](https://www.skatteverket.se/download/18.7eada0316ed67d728240c9/1708607929567/Pisa_faltlista_v2_0.pdf) |
| Samordningsnummer: day + 60; unknown month or day written 00 / 60 | Skatteverket's published test set (numbers such as `198000602394`) |

## Test identities

Every personnummer and samordningsnummer in this repository comes from Skatteverket's open data sets **Testpersonnummer** and **Testsamordningsnummer** (CC0 1.0, "får bara användas i testmiljöer"):

- <https://skatteverket.entryscape.net/rowstore/dataset/b4de7df7-63c0-4e7e-bb59-1f156a591763>
- <https://skatteverket.entryscape.net/rowstore/dataset/9f29fe09-4dbc-4d2f-848f-7cffdd075383>

Skatteverket's own V6 example files use the same set. No official test organisationsnummer exists, so:

- the cooperative number `167999000040` is the one in Skatteverket's own example file `exempel_rot_3st.xml`;
- every performer is a sole trader with a test personnummer.

No identity number in this repository was invented. The unit tests compute check digits only for impossible dates (month 13, 30 February). See [fixtures/README.md](../fixtures/README.md).

## Licensing

Skatteverket's schema and example files carry no licence or redistribution terms. Neither the files, the schema page, nor xmls.skatteverket.se states any (checked 2026-11-18). This repository therefore does not include them:

- `schemas:fetch` downloads them from Skatteverket and verifies their sha256;
- the test suite fetches them in CI;
- only the URLs and checksums are distributed ([manifest.json](../resources/schemas/manifest.json)).

The service codes and element names that appear in the code are facts needed for interoperability, not copied documentation.

## VERIFY_REQUIRED

| Item | Why it is open | What would settle it |
|---|---|---|
| A 1.1 file from this toolkit imports into the e-service | No import has been made; it needs a company's e-legitimation | One real import (without signing) |
| `HOURS_AND_MATERIAL_REQUIRED` for a buyer with only flat-rate services | The import rule asks for hours and material for at least one work area, yet Skatteverket's own V6 example has such a buyer | Skatteverket's answer, or an import test. Until then it is a notice (`HOURS_AND_MATERIAL_FLAT_RATE_ONLY`). |
| `PROPERTY_NOT_FOR_RUT` | The schema says *Fastighet* is given for ROT and green technology; whether the e-service refuses it for RUT is not stated | Import test; kept as a warning |
| `V1_1_NOT_YET_USABLE` | Whether the e-service refuses 1.1 before 2027-01-01 is not stated | Kept as a warning |
| `PERFORMER_ID_INVALID` for a 12-digit organisation number with the prefix `16` | The e-service compares the number with the company the user represents; the expected written form (10 digits or 16 + 10) is not documented for this format | Import test with both forms |
| Amount limits for payments from 2027-01-01 | The statute text in force for 2027 has not been reviewed | Read 7 § lagen (2009:194) and 67 kap. 19 § IL as in force on 2027-01-01 and add a row to `AmountLimits` |
| The SFS number of the 2027 amendment | Not yet in the consolidated text on 2026-11-18 | Riksdagen/SFS after publication |
| SKV 704: current edition | Only the 2007 edition (mirror) was found | Skatteverket's publication list |
