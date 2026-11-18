# Maintenance

Owner: Closepact (maintainers listed on the GitHub repository). Expected effort: **S**, about 2–4 hours a quarter, plus **M** around a format change.

## Triggers for a review

| Trigger | Where to look | What to do |
|---|---|---|
| Skatteverket changes a schema file | `schemas:fetch` reports `checksum_mismatch`; CI fails | Diff the new file; update `manifest.json` (url, sha256); adapt parser, writer and rules if the structure changed; add fixtures; release a minor version |
| A new format version is published | The schema page (link in `sources.json`) | New `SchemaVersion` case, parser and writer branches, fixtures, docs; minor release |
| Service codes change | The XSD documentation; `ServiceCatalogTest` fails | Update `ServiceCatalog`; minor release |
| Law change (rates, caps, deadline, content of a request) | riksdagen.se: 2009:194 and 67 kap. IL; Skatteverket's rot/rut pages | Add a period row in `AmountLimits` or a rule; update `sources.json` and `regulatory-sources.md`; fixtures on both sides of the effective date |
| A new calendar year | Every December | Review the amount limits for the coming year (see `AmountLimits`); until then, `LIMITS_NOT_CHECKED` is reported, never old figures |
| An import test is done | — | Record date, version and outcome in `compatibility.md` |
| A confirmed bug | Issues | Regression fixture first, then the fix; patch release |
| A PHP version reaches end of security support | php.net | Drop it in the next minor release; update CI and `compatibility.md` |

## Rule review checklist

- [ ] The rule's primary source is linked in `resources/sources.json` with today's `retrieved` date.
- [ ] The wording in `RuleCatalog` (sv and en) matches the source, and the severity is honest. An interpretation is a warning or notice with `verify_required`.
- [ ] A fixture or unit test triggers the code, and one shows the boundary that passes.
- [ ] `php tools/rules-doc.php > docs/rules.md` is regenerated.
- [ ] `docs/regulatory-sources.md` is updated, including the VERIFY list.
- [ ] CHANGELOG entry.

## Release checklist

- [ ] `composer check` is green locally, with the schemas installed (no skipped tests).
- [ ] CI is green on PHP 8.3, 8.4 and 8.5.
- [ ] `Toolkit::VERSION`, `CHANGELOG.md` and the compatibility table are updated.
- [ ] Tag `vX.Y.Z` (signed if possible). The release workflow builds and verifies the archive; it publishes nothing.
- [ ] Publishing to Packagist and making releases public are manual steps by the owner.

## Dependencies

- Runtime: none beyond PHP extensions.
- Dev: PHPUnit, PHPStan and Pint, constrained to major versions.
- Dependabot opens weekly PRs for Composer and GitHub Actions; actions are pinned to commit SHAs.

## Unsupported versions

- V3 and V4 are recognised and reported as `VERSION_UNSUPPORTED`. They will not be supported, because Skatteverket is phasing them out.
- When Skatteverket announces an end date for a version this package supports, the toolkit will report a transition warning from that date and drop the version in the next major release after it.
