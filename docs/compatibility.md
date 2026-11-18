# Compatibility and support matrix

## Format versions (Skatteverket's XML) vs package versions

The **format version** (V6, 1.0, 1.1) is Skatteverket's. The **package version** (`0.3.0`, …) follows [semantic versioning](https://semver.org/):

- **Patch:** fixes that do not change which files pass.
- **Minor:** a new format version, a new rule on a new code, or a newly reviewed period in `AmountLimits`. This can make a previously passing file fail when a source supports it.
- **Major:** removing a format version or a code, or changing the meaning of a code or an exit code.

| Package | V6 | 1.0 | 1.1 | V3, V4 | Amount limits reviewed |
|---|---|---|---|---|---|
| 0.3.x | validate + generate | validate + generate | validate + generate | recognised, not supported | payments in 2026 |

## What has been verified, and how

| Check | Status |
|---|---|
| Files generated for V6, 1.0 and 1.1 pass Skatteverket's XSD | ✅ automated (test suite, against the XSD files fetched from Skatteverket with the sha256 in the manifest) |
| Skatteverket's six example files are read correctly (schema-valid; V6 examples rule-valid; 1.x examples flagged only for their placeholder identity numbers `000000000000`) | ✅ automated |
| Service codes match the XSD documentation; V6 element names and flat-rate services match `BegaranCOMPONENT.xsd` | ✅ automated, read from the schema files |
| A V6 file from this toolkit imports into *Rot och rut – företag* | ❌ **not verified** (no import done) |
| A 1.0 file from this toolkit imports into the e-service | ❌ **not verified** |
| A 1.1 file from this toolkit imports into the e-service | ❌ **not verified**; 1.1 is usable from 2027-01-01 |

Imports need a company representative's e-legitimation. Until they are done and recorded here with date and outcome, nothing in this repository claims that the e-service accepts the files. The tool checks what Skatteverket publishes; the e-service decides.

## PHP

| PHP | Status |
|---|---|
| 8.3 | supported, tested in CI |
| 8.4 | supported, tested in CI |
| 8.5 | supported, tested in CI |
| ≤ 8.2 | not supported (8.2 security support ends 2026-12-31) |

Support for a PHP version is dropped in a minor release after PHP's own security support for it ends.
