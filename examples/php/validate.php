<?php

// php examples/php/validate.php fixtures/v1_1/rut-cleaning-valid.xml 2027-01-12

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;

$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? array_values(array_map('strval', $_SERVER['argv'])) : [];

[$script, $file, $asOf] = $argv + [1 => null, 2 => null];
if ($file === null) {
    fwrite(STDERR, "usage: php {$script} <file.xml> [YYYY-MM-DD]\n");
    exit(2);
}

$validator = Validator::create();   // schemas from `rot-rut schemas:fetch` or ROT_RUT_XML_SCHEMA_DIR
$result = $validator->validateFile($file, $asOf !== null ? ValidationOptions::forDate($asOf) : null);

printf("%s, XSD %s, %d error(s)\n", $result->version?->label() ?? 'unknown format', $result->schemaStatus, count($result->errors()));
foreach ([...$result->errors(), ...$result->warnings(), ...$result->notices()] as $issue) {
    printf("[%s] %s %s\n    %s\n", $issue->severity()->value, $issue->code, $issue->path ?? '', $issue->message('en'));
}

exit($result->isValid() ? 0 : 1);
