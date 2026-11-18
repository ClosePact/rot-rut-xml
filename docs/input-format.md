# Generator input format

One JSON object (or the same structure as a PHP array) describes one request file. The same input can be written in any supported version. Fields a version cannot carry are ignored with an `INPUT_NOT_FOR_VERSION` warning, and fields a version needs are required. Unknown fields are errors (`INPUT_UNKNOWN_FIELD`), to catch typos.

```json
{
  "name": "Rut december",
  "type": "RUT",
  "performer": "198001042384",
  "system_contact": { "name": "…", "product": "…", "emails": ["support@example.com"] },
  "cases": [
    {
      "invoice_number": "2026-1201",
      "buyer": "198001022386",
      "property": { "designation": "EXEMPEL 1:23" },
      "work": [
        { "service": "STADNING", "hours": 4, "material_cost": 80 },
        { "service": "TVATT_VID_TVATTINRATTNING" }
      ],
      "other_cost": 240,
      "payment_date": "2026-12-10",
      "labour_cost": 2020,
      "paid_amount": 1010,
      "requested_amount": 1010,
      "subcontractors": false,
      "staffing_agency": false
    }
  ]
}
```

| Field | Required | Rules |
|---|---|---|
| `name` | yes | 1–16 characters (`NamnPaBegaran`) |
| `type` | yes | `ROT` or `RUT` (one type per file) |
| `performer` | 1.0, 1.1 | The company's organisation number, or a sole trader's personnummer. Written as 12 digits, with the prefix `16` for organisation numbers. |
| `system_contact` | no (1.1 only) | `name` and `product` 1–100 characters; `emails` 1–10 addresses. Skatteverket may use them to announce schema changes. |
| `cases` | yes | 1–100 buyers |
| `cases[].invoice_number` | no | ≤ 20 characters. Recommended: Skatteverket's decision file reports the invoice number per buyer. |
| `cases[].buyer` | yes | The buyer's personnummer or samordningsnummer, 10 or 12 digits; written as 12 |
| `cases[].property` | ROT only | Either `{ "designation" }` (≤ 40 characters), or `{ "apartment_number", "brf_org_number" }` for a cooperative apartment |
| `cases[].work[]` | yes | `service` is a code from [rules.md](rules.md#tjänster--services) that matches `type`, each code once. Flat-rate services (`TVATT_VID_TVATTINRATTNING`, `TRANSPORT_TILL_FORSALJNING`) take nothing else; every other service needs `hours` (0–999) and `material_cost` (whole SEK, 0 if none). |
| `cases[].other_cost` | when a service has hours | Whole SEK, 0 if none |
| `cases[].payment_date` | yes | `YYYY-MM-DD`, from 2009-07-01 |
| `cases[].labour_cost` | yes | Whole SEK, ≥ 2 (the price of the work, `Arbetskostnad`/`PrisForArbete`) |
| `cases[].paid_amount` | yes | Whole SEK the buyer paid for the work |
| `cases[].requested_amount` | yes | Whole SEK requested from Skatteverket |
| `cases[].subcontractors` | 1.1 | `true`/`false`, with no default |
| `cases[].staffing_agency` | 1.1 | `true`/`false`, with no default |

Amounts are whole kronor (*hela krontal*, 5 § lagen (2009:194)). A decimal or a string is refused rather than rounded.

The generator is stricter than the validator on purpose. It writes hours and material for every service that has them, and it never defaults the 2027 answers. A file it writes therefore states only what the input states. After writing, the file goes through the full validation (XSD and rules). If anything is an error, no file is returned (`GenerationFailed`, exit code 1).

Complete examples: [examples/](../examples/).
