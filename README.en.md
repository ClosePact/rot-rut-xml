# rot-rut-xml

Create and validate Swedish ROT/RUT payment-request XML files (*begäran om utbetalning*) for import into Skatteverket's e-service *Rot och rut – företag*.

[Svenska](README.md) · [Rules and codes](docs/rules.md) · [Sources](docs/regulatory-sources.md)

> **Not from Skatteverket.** This is an independent open-source tool. It is not developed, reviewed or endorsed by the Swedish Tax Agency (Skatteverket). A file that passes these checks can still be rejected or refused, and the tool says nothing about whether a buyer is entitled to the deduction. Skatteverket assesses every request. The checks are no substitute for tax or accounting advice.

## What it does

- **Validates** a file in format version 6, 1.0 or 1.1. The order is:
  1. safe parsing;
  2. version detection from the namespace;
  3. Skatteverket's XSD;
  4. the rules the XSD cannot express.

  Examples of those rules: requested ≤ paid, requested + paid ≤ labour cost, one payment year per file, buyer at least 18 in the payment year, property details for ROT, service codes valid for the request type, formally correct personnummer and organisation numbers, the 31 January deadline, and the ROT share of three sevenths.
- **Generates** a file from structured input (JSON or a PHP array) in any supported version.
  - The file is validated before it is returned.
  - Nothing is defaulted: hours, costs and the 2027 answers (subcontractors, staffing company) must be stated explicitly.
- **Explains** every finding in Swedish and English. Each one comes with the XML path, a correction where one is safe, and the primary source: the statute, Skatteverket's import rules, or the schema documentation.

Everything runs locally. Files are never sent anywhere. The only network access is `rot-rut schemas:fetch`, and it runs only when you call it.

## Who it is for

Developers and maintainers of invoicing and business software for Swedish cleaning companies, movers, tradespeople and other service businesses that file ROT/RUT payment requests. It is also for accountants and integrators who want to check a file before uploading it.

## Formats

| Format | Namespace | In use | In the file |
|---|---|---|---|
| **XML version 6** (`ht/begaran V6`) | `…/ht/begaran/6.0` | since 2021-01-01 | — |
| **XML version 1.0** (`skattered/begaran V1`) | `…/skattered/begaran/1.0` | today | performer's number |
| **XML version 1.1** (`skattered/begaran V1.1`) | `…/skattered/begaran/1.1` | from **2027-01-01** | performer's number, subcontractors, staffing company |

Versions 3 and 4 are still accepted by Skatteverket but are being phased out. They are recognised but not validated here. The format version is not the package version; see [compatibility](docs/compatibility.md).

**From 2027-01-01** a request states whether subcontractors or a staffing company were used, and its details are given *på heder och samvete* (on one's honour). This follows bet. 2026/27:SkU3, decided 2026-11-11. With version 6 or 1.0 the answers are entered by hand in the e-service after import; version 1.1 carries them in the file. See [version migration](docs/version-migration.md).

## Install

Requires PHP 8.3+ with `dom` and `libxml`.

```bash
composer require closepact/rot-rut-xml
vendor/bin/rot-rut schemas:fetch
```

`schemas:fetch` downloads Skatteverket's XSD files over HTTPS. A file is kept only when its sha256 matches the [manifest](resources/schemas/manifest.json). The schemas are not bundled because Skatteverket states no redistribution terms. See [installation](docs/installation.md).

## CLI

```bash
vendor/bin/rot-rut validate request.xml --lang=en
vendor/bin/rot-rut validate request.xml --format=json --as-of=2027-01-12
vendor/bin/rot-rut generate input.json --version=v1.1 --output=request.xml
vendor/bin/rot-rut versions | rules | sources | schemas:status
```

Exit codes:

| Code | Meaning |
|---|---|
| `0` | no errors |
| `1` | errors in the file |
| `2` | usage error |
| `3` | the XSD check could not run (pass `--no-schema` to accept that) |
| `4` | I/O or download failure |

Details: [docs/cli.md](docs/cli.md).

## PHP

```php
$validator = Closepact\RotRutXml\Validator::create();
$result = $validator->validateFile('request.xml', Closepact\RotRutXml\ValidationOptions::forDate('2027-01-12'));

$result->isValid();        // no errors from the checks that ran
$result->schemaChecked();  // the XSD check ran and passed
$result->toArray();        // same structure as --format=json
```

- Generator input format: [docs/input-format.md](docs/input-format.md).
- Examples: [examples/](examples/).
- The library is framework-independent; it works in Laravel and in plain PHP.

## Findings

Every finding has the following:

- a stable code;
- a severity: `error`, `warning` or `notice`;
- a category: `schema` (XSD), `rule` (from a primary source), `transition` (format changes over time) or `policy` (optional house checks, only with `--policy`).

All codes, texts and sources are in [docs/rules.md](docs/rules.md), which is generated from the code. Messages never contain identity numbers, so a report can go to a log or a ticket.

## Limitations

- **The e-service has the last word.** The toolkit checks what Skatteverket publishes: the schema, the import rules and the statute text. The e-service may check more.
- **Version 1.1 has not been import-tested.** It is checked against Skatteverket's XSD and example files. A file produced by this toolkit has not yet been imported into the e-service. See [compatibility](docs/compatibility.md).
- **Amount limits are checked only for reviewed years.** The ROT share and the annual cap are checked for payments made in 2026, whose statute text has been reviewed. For other years the report says `LIMITS_NOT_CHECKED` instead of guessing.
- **Earlier requests are not counted.** The annual cap is checked within one file, because the toolkit cannot see other requests for the same buyer.
- **Green technology (grön teknik) is not supported.** It has its own e-service.

## Privacy

Request files contain buyers' personnummer.

- The toolkit reads them locally, sends nothing, stores nothing, and never prints identity numbers in messages.
- Tests and examples use only Skatteverket's published test personnummer.

See [security](docs/security.md) and [SECURITY.md](SECURITY.md).

## Contributing and licence

A rule change needs a primary source; see [CONTRIBUTING.md](CONTRIBUTING.md).

[MIT](LICENSE) for the code in this repository. Skatteverket's schemas and example files are not included and not covered by this licence.

---

Maintained by [Closepact](https://closepact.com/en?utm_source=github&utm_medium=opensource&utm_campaign=rot_rut_xml), business software for Swedish service companies: quotes, agreements, invoicing, ROT and RUT.
