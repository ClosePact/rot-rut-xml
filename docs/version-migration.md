# Version migration: V6 → 1.1

## What changes on 2027-01-01

- From 2027-01-01 every ROT/RUT payment request states whether **subcontractors** (*underentreprenörer*) or a **staffing company** (*bemanningsföretag*) were used, and its details are given **på heder och samvete** (bet. 2026/27:SkU3, decided 2026-11-11).
- Skatteverket published XML version **1.1** for this.
- Older versions (6, 1.0) still import. The two answers are then filled in by hand in the e-service after import.

## Field mapping

| Meaning | V6 (`ht/begaran`) | 1.0 / 1.1 (`skattered/begaran`) |
|---|---|---|
| Name of the request | `NamnPaBegaran` | `NamnPaBegaran` |
| ROT or RUT | the element: `RotBegaran` / `HushallBegaran` | `TypAvBegaran`: `ROT` / `RUT` |
| Company | — | `Utforare` |
| One buyer | `Arenden` (in `RotBegaran`/`HushallBegaran`) | `Arende` |
| Buyer | `Kopare` (exactly 12 digits) | `Kopare` (10–13 characters) |
| Payment date | `BetalningsDatum` | `Betalningsdatum` |
| Labour cost | `PrisForArbete` (required) | `Arbetskostnad` (required for ROT/RUT by rule) |
| Other cost | `Ovrigkostnad` | `OvrigKostnad` |
| Property (ROT) | `Fastighetsbeteckning`, `LagenhetsNr`, `BrfOrgNr` directly in the case | the same three inside `Fastighet` |
| Work | one element per service (`Stadning`, `Bygg`, …) with `AntalTimmar`, `Materialkostnad` | repeated `UtfortArbete` with `TypAvUtfortArbete` (`STADNING`, `BYGG`, …), `AntalTimmar`, `Kostnad` |
| Flat-rate services | `TransportTillForsaljning`/`TvattVidTvattinrattning` with `Utfort` true/false | the code only, without hours or cost |
| Subcontractors / staffing company | — | 1.1: `Underentreprenor`, `Bemanningsforetag` (true/false, mandatory) |
| System contact | — | 1.1: optional `Systemkontakt` |

The generator's input format is the same for every version ([input-format.md](input-format.md)), so switching is a change of `--version`.

## Suggested switch

1. **Now:** keep sending V6 (or 1.0). Start collecting the two answers per invoice in your system. They are a statement made on the company's behalf, so they need a person's answer, not a default.
2. **From 2027-01-01:**
   - with V6/1.0, the e-service asks for the answers after import (this toolkit reports `DECLARATIONS_IN_E_SERVICE`);
   - with 1.1, the answers travel in the file.
3. **Before sending 1.1 by default:** import one 1.1 file in the e-service *without signing* and confirm that it is accepted. That import has not been done for files from this toolkit; see [compatibility.md](compatibility.md).

Checking 1.1 before 2027-01-01 gives the warning `V1_1_NOT_YET_USABLE`, because the format is usable from that date.

## January 2027

Requests for payments made in December 2026 are due by **31 January 2027** (8 § lagen (2009:194)), and they will be filed under the new rules. Use `--as-of` with the day you plan to send.
