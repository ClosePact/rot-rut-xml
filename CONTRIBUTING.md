# Bidra / Contributing

Tack för att du vill hjälpa till. Frågor och förslag går bra på svenska eller engelska.

Thank you for helping. Issues and pull requests are welcome in Swedish or English.

## The one rule

**Every rule needs a primary source:** Skatteverket's own pages or schema files, or the statute text on riksdagen.se. Blog posts, forum answers and vendor help pages are not enough. If something is an interpretation, it is reported as a warning or notice with the status `verify_required`, never as an error.

## Data

Never put real personnummer, organisation numbers of real customers, or customer files in issues, pull requests, fixtures or tests. Use the numbers from Skatteverket's **Testpersonnummer** and **Testsamordningsnummer** data sets (links in [docs/regulatory-sources.md](docs/regulatory-sources.md#test-identities)). Do not compute "fake" numbers with a valid check digit, because they can belong to real people.

## Workflow

```bash
composer install
export ROT_RUT_XML_SCHEMA_DIR="$PWD/build/schemas"
php bin/rot-rut schemas:fetch --include-samples
composer check
```

- A changed rule:
  - update `src/Rules` and `resources/sources.json`;
  - add a fixture that triggers it and one at the boundary that passes;
  - add an entry to `fixtures/expectations.json`;
  - run `php tools/rules-doc.php > docs/rules.md`;
  - update `docs/regulatory-sources.md` and `CHANGELOG.md`.
- A changed writer: regenerate the valid fixtures from `examples/` (`GeneratorTest` compares them byte for byte).
- Codes never change meaning. A new meaning gets a new code.

See [docs/maintenance.md](docs/maintenance.md) for the review and release checklists.

## Code of conduct

Be kind and specific. Maintainers may close issues that ask for tax advice; those questions belong with Skatteverket or an adviser.
