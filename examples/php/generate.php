<?php

// php examples/php/generate.php examples/rot-v1.1.json v1.1 2027-01-12 > request.xml

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Closepact\RotRutXml\Generator\GenerationFailed;
use Closepact\RotRutXml\Generator\Generator;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? array_values(array_map('strval', $_SERVER['argv'])) : [];

[$script, $input, $version, $asOf] = $argv + [1 => null, 2 => 'v1.1', 3 => null];
if ($input === null || SchemaVersion::fromOption((string) $version) === null) {
    fwrite(STDERR, "usage: php {$script} <input.json> v6|v1.0|v1.1 [YYYY-MM-DD]\n");
    exit(2);
}

$generator = new Generator(Validator::create());

try {
    $result = $generator->generate(
        Generator::decodeJson((string) file_get_contents($input)),
        SchemaVersion::fromOption((string) $version),
        $asOf !== null ? ValidationOptions::forDate($asOf) : null,
    );
} catch (GenerationFailed $e) {
    foreach ($e->issues as $issue) {
        fwrite(STDERR, $issue->code.': '.$issue->message('en').PHP_EOL);
    }
    exit(1);
}

if (! $result->validation->schemaChecked()) {
    fwrite(STDERR, "Note: Skatteverket's XSD was not available; run `vendor/bin/rot-rut schemas:fetch`.\n");
}

echo $result->xml;
