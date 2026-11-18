# Installation

## Krav / requirements

- PHP 8.3, 8.4 or 8.5 (CI tests all three).
- Extensions: `dom`, `libxml`, `json` (bundled with PHP). `openssl` for `schemas:fetch`.
- No other runtime dependencies.

## Composer

```bash
composer config repositories.rot-rut-xml vcs https://github.com/ClosePact/rot-rut-xml
composer require closepact/rot-rut-xml:^0.3
```

The package is not on Packagist yet. Until it is, the `composer config` line tells Composer to install it from the GitHub repository. Once it is on Packagist, `composer require closepact/rot-rut-xml` alone is enough, and the repository entry can be removed from `composer.json`.

## Skatteverket's schemas

The XSD check needs Skatteverket's schema files. They are **not** shipped with the package, because Skatteverket publishes no terms for redistributing them ([regulatory-sources.md](regulatory-sources.md#licensing)). Fetch them once:

```bash
vendor/bin/rot-rut schemas:fetch                    # the XSD files only
vendor/bin/rot-rut schemas:fetch --include-samples  # plus Skatteverket's example files (used by the test suite)
vendor/bin/rot-rut schemas:status
```

- Files come from `https://xmls.skatteverket.se/…`. The exact URLs are in [resources/schemas/manifest.json](../resources/schemas/manifest.json).
- Each file is kept only when its sha256 matches the manifest. A mismatch means Skatteverket has changed the file. The file is not saved, and the command exits with code 3 (see [maintenance.md](maintenance.md)).
- The default location:
  - `%LOCALAPPDATA%\rot-rut-xml\schemas` on Windows;
  - `$XDG_CACHE_HOME/rot-rut-xml/schemas` or `~/.cache/rot-rut-xml/schemas` elsewhere.
- To choose another location, use `--schemas=DIR` on any command, or set `ROT_RUT_XML_SCHEMA_DIR`.
- In a container or CI: run `schemas:fetch --schemas=/path` at build time and set `ROT_RUT_XML_SCHEMA_DIR=/path` at run time. The validator itself never uses the network.

### Offline or air-gapped

Copy the files by hand into the same layout as Skatteverket's URL paths:

```
<dir>/ht/begaran/V6/Begaran.xsd
<dir>/ht/komponent/V6/BegaranCOMPONENT.xsd
<dir>/skattered/begaran/V1/Begaran.xsd
<dir>/skattered/begaran/V1.1/Begaran.xsd
```

`schemas:status` confirms that the checksums match.

### Without the schemas

Validation still runs the built-in structural checks and every rule. It reports `SCHEMA_UNAVAILABLE`, and `--format=json` shows `"schema": "skipped"`. The CLI exits with `3` unless you pass `--no-schema`, so a CI pipeline cannot pass silently without the XSD check.

## Development

```bash
git clone https://github.com/ClosePact/rot-rut-xml
cd rot-rut-xml
composer install
export ROT_RUT_XML_SCHEMA_DIR="$PWD/build/schemas"
php bin/rot-rut schemas:fetch --include-samples
composer check        # pint --test, phpstan (level 8), phpunit
```

Without `ROT_RUT_XML_SCHEMA_DIR`, the tests that need Skatteverket's files are skipped, and PHPUnit reports them as skipped.
