# Changelog

All notable changes are listed here. The package follows [semantic versioning](https://semver.org/); the version of Skatteverket's XML format is a separate thing (see docs/compatibility.md).

## [Unreleased]

Nothing yet.

## [0.3.0] – 2026-11-18

First version: validation, generator and CLI.

### Added

- Validation of payment-request files in XML version 6, 1.0 and 1.1:
  - safe parsing;
  - version detection;
  - Skatteverket's XSD, from a local, checksum-verified copy;
  - rules from lagen (2009:194), 67 kap. inkomstskattelagen and Skatteverket's import rules;
  - format-transition notices for 2027.
- Generator from JSON or PHP arrays for all three versions. It validates its own output and refuses to return a file with errors.
- CLI `rot-rut` with the commands `validate`, `generate`, `versions`, `rules`, `sources`, `schemas:fetch` and `schemas:status`; JSON output and stable exit codes.
- Findings in Swedish and English, with XML path and primary-source links, and never with identity numbers.
- Opt-in policy checks (`--policy`).
- 44 fixtures with 56 expected outcomes, built only from Skatteverket's published test personnummer.

### Not verified

- Import of files produced by this toolkit into the e-service *Rot och rut – företag*: not yet done for any version (docs/compatibility.md).
