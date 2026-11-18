# CLI: `rot-rut`

```
rot-rut validate <file.xml> [--format=text|json] [--version=auto|v6|v1.0|v1.1] [--as-of=YYYY-MM-DD]
                            [--schemas=DIR] [--no-schema] [--policy] [--lang=sv|en]
rot-rut generate <input.json> --version=v6|v1.0|v1.1 [--output=file.xml] [--as-of=YYYY-MM-DD]
                            [--schemas=DIR] [--no-schema] [--policy] [--lang=sv|en]
rot-rut versions [--format=json]
rot-rut rules [--format=json] [--lang=sv|en]
rot-rut sources [--format=json]
rot-rut schemas:fetch [--schemas=DIR] [--include-samples]
rot-rut schemas:status [--schemas=DIR] [--format=json]
rot-rut about | help
```

## Options

| Option | Meaning |
|---|---|
| `--as-of` | The day the file will be sent to Skatteverket (*ansökningsdatum*). It is used for the payment-date check, the 31 January deadline and the 2027 format transition. Default: today in Europe/Stockholm. |
| `--version` | `validate`: fail with `VERSION_MISMATCH` if the file is in another version (`auto` accepts any). `generate`: the version to write (required). |
| `--policy` | Also run the optional house checks (category `policy`): missing or duplicated invoice numbers, a requested amount of 0, a service listed twice, a service without hours, or both a property designation and apartment details. |
| `--no-schema` | Accept that the XSD check cannot run. Without it, a missing or changed schema gives exit code 3. |
| `--schemas` | Directory holding Skatteverket's schemas (default: see [installation.md](installation.md)). |
| `--lang` | Language of the text output. JSON always carries both. |

## Exit codes (stable)

| Code | `validate` | `generate` |
|---|---|---|
| 0 | No errors, and the XSD check ran (or `--no-schema`) | File written |
| 1 | Errors in the file | Errors in the input or in the resulting file; nothing written |
| 2 | Wrong usage | Wrong usage |
| 3 | No errors, but the XSD check could not run | XSD check could not run; nothing written |
| 4 | File cannot be read | File cannot be read or written |

Warnings and notices never change the exit code.

## JSON output (`validate --format=json`)

```json
{
  "tool": { "name": "rot-rut-xml", "version": "0.3.0" },
  "valid": false,
  "version": "v1.1",
  "version_label": "XML version 1.1 (skattered/begaran V1.1)",
  "namespace": "http://xmls.skatteverket.se/se/skatteverket/skattered/begaran/1.1",
  "schema": "passed",
  "as_of": "2027-01-12",
  "summary": { "errors": 1, "warnings": 0, "notices": 0 },
  "errors": [
    {
      "code": "ROT_PROPERTY_REQUIRED",
      "severity": "error",
      "category": "rule",
      "path": "/Begaran/Arende[2]/Fastighet",
      "claim": 2,
      "message_sv": "Ärende 2: fastighetsuppgifter saknas. …",
      "message_en": "Case 2: property information is missing. …",
      "status": "verified",
      "sources": [
        { "id": "sfs-2009-194", "title": "Lag (2009:194) …", "url": "https://www.riksdagen.se/…" }
      ]
    }
  ],
  "warnings": [],
  "notices": [],
  "disclaimer": { "sv": "…", "en": "…" }
}
```

- `schema` is one of these:
  - `passed`;
  - `failed`;
  - `skipped`: the schema is not installed or its checksum differs;
  - `not_run`: the file could not be parsed, or its version is not supported.
- `claim` is the 1-based position of the buyer (`Arende`/`Arenden`) the finding concerns.
- `line` appears where the XML parser or the XSD reports one.
- Codes, categories and texts are listed in [rules.md](rules.md).
