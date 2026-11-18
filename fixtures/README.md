# Fixtures

Synthetic request files and the findings each must produce ([expectations.json](expectations.json)). The test suite checks every one of them twice: with Skatteverket's XSD and without it.

## Where the identity numbers come from

No identity number here was invented.

| Number | Role | Provenance |
|---|---|---|
| 198001042384 | performer (sole trader) | Skatteverket Testpersonnummer (CC0) |
| 198001022386, 198001032385, 199001012385, 195601132334 | buyers | Skatteverket Testpersonnummer |
| 200812312395, 200901012385 | buyers at the 18-year boundary | Skatteverket Testpersonnummer |
| 198001662397 | buyer with a samordningsnummer | Skatteverket Testsamordningsnummer (CC0) |
| 167999000040 | housing cooperative (BRF) | the `BrfOrgNr` in Skatteverket's own example file `exempel_rot_3st.xml`; no official test organisation numbers exist |
| 198001042385, 198001022387, 167999000041 | deliberately wrong check digit | one digit changed from the numbers above, so it cannot be formally correct |

Data sets: <https://skatteverket.entryscape.net/rowstore/dataset/b4de7df7-63c0-4e7e-bb59-1f156a591763> and <https://skatteverket.entryscape.net/rowstore/dataset/9f29fe09-4dbc-4d2f-848f-7cffdd075383> ("får bara användas i testmiljöer").

Property designations (`EXEMPEL 1:23`), names and amounts are made up. Email addresses use `example.com` (reserved, RFC 2606).

## How they were made

- The valid files in `v6/`, `v1_0/` and `v1_1/` are the generator's output for the inputs in [examples/](../examples/). `GeneratorTest` checks that they still match byte for byte.
- Every other file is one of those with one deliberate change, so each fixture isolates one finding.
- `v6/hundred-buyers.xml` has 100 buyers, the maximum; `invalid/v6-101-buyers.xml` has one more.
- "Valid" means the file passed Skatteverket's XSD (fetched and checksum-verified) and every rule in this toolkit, with the `as_of` date in `expectations.json`. It does **not** mean that the file has been imported into Skatteverket's e-service (see docs/compatibility.md).

Skatteverket's own example files are not copied here; the test suite fetches them (`schemas:fetch --include-samples`).
