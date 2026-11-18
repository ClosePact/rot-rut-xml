# Security and privacy

## Threat model

The toolkit reads files a user hands it: possibly crafted, often holding personal identity numbers. It must not let a file reach the file system or the network, exhaust memory, or leak identity numbers into logs.

## Measures

| Risk | Measure | Test |
|---|---|---|
| XXE (external entities reading local files or URLs) | Any `<!DOCTYPE` or `<!ENTITY` is refused before parsing (`XML_DOCTYPE_FORBIDDEN`); Skatteverket's formats never use them. libxml runs with `LIBXML_NONET`, without `LIBXML_NOENT` or `LIBXML_DTDLOAD`. | `SafeXmlLoaderTest`, fixtures `invalid/doctype-xxe.xml` |
| Entity expansion ("billion laughs") | Same refusal | `invalid/billion-laughs.xml` |
| Huge files | 5 MB limit (`XML_TOO_LARGE`); a 100-buyer file is about 52 kB | `SafeXmlLoaderTest` |
| Deep nesting | 32 levels (`XML_TOO_DEEP`); Skatteverket's formats need at most 6 | `SafeXmlLoaderTest` |
| Schema loading reaching the network or other files | During XSD validation, a custom libxml entity loader allows only files inside the schema directory. Skatteverket URLs are mapped to the local copy and anything else is refused. A schema whose sha256 differs from the manifest is never used. | `SchemaTest` |
| Tampered schema downloads | HTTPS only, peer verification on, no redirects, sha256 checked before saving, atomic write | `SchemaTest` |
| Identity numbers in logs | Messages never include personnummer or organisation numbers; findings point to an XML path instead | `FixtureExpectationsTest::test_messages_never_contain_identity_numbers` |
| Data leaving the machine | No network use except `schemas:fetch`, which runs only on demand. No telemetry. | code review |

## Generated XML

Values are written as DOM text nodes, so special characters are escaped by the DOM, never by string concatenation.

## Reporting a vulnerability

See [SECURITY.md](../SECURITY.md).
