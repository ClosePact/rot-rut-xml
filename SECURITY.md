# Säkerhet / Security

## Rapportera en sårbarhet / Reporting a vulnerability

Rapportera säkerhetsproblem **privat** via GitHub: *Security → Report a vulnerability* i det här repot. Skapa inte en öppen issue.

Please report security problems **privately** through GitHub's *Security → Report a vulnerability* on this repository, not as a public issue.

Include the version (`rot-rut about`), a description, and a minimal file that shows the problem. Never include real personal data. We aim to acknowledge reports within five working days.

## Scope

- In scope: XML parsing of untrusted files (XXE, entity expansion, resource exhaustion), the schema loader and downloader, leakage of identity numbers into output, and the generated XML.
- Out of scope: Skatteverket's systems and e-service, and the correctness of tax decisions.

## Supported versions

The latest minor release receives security fixes. See [docs/compatibility.md](docs/compatibility.md) for PHP versions.

How the toolkit handles untrusted input: [docs/security.md](docs/security.md).
