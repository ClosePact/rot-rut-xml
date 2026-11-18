<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Generator;

use Closepact\RotRutXml\Reporting\Issue;
use Closepact\RotRutXml\ValidationOptions;
use Closepact\RotRutXml\Validator;
use Closepact\RotRutXml\Version\SchemaVersion;

/**
 * Builds a payment-request file from structured input and refuses to hand back
 * a file that does not pass its own validation.
 *
 *     $result = (new Generator(Validator::create()))->generate($input, SchemaVersion::V1_1);
 *     file_put_contents('begaran.xml', $result->xml);
 *
 * The generator states nothing on the company's behalf that the input does not
 * state: no default hours, costs or 2027 answers, and no judgement about whether
 * the buyer is entitled to the deduction.
 */
final class Generator
{
    public function __construct(
        private readonly Validator $validator,
        private readonly InputReader $reader = new InputReader,
        private readonly XmlWriter $writer = new XmlWriter,
    ) {}

    /**
     * @param  array<mixed>  $input  see docs/input-format.md
     *
     * @throws GenerationFailed when the input or the resulting file has errors
     */
    public function generate(array $input, SchemaVersion $version, ?ValidationOptions $options = null): GenerationResult
    {
        $options ??= new ValidationOptions;
        [$normalised, $inputIssues] = $this->reader->read($input, $version, $options->asOf);
        if ($normalised === null) {
            throw new GenerationFailed($inputIssues);
        }

        $xml = $this->writer->write($normalised, $version);
        $validation = $this->validator->validateString($xml, new ValidationOptions($options->asOf, $version, $options->policy, $options->useSchema));
        if (! $validation->isValid()) {
            throw new GenerationFailed([...$inputIssues, ...$validation->errors()], $validation);
        }

        return new GenerationResult($xml, $validation, $inputIssues);
    }

    /**
     * @return array<mixed>
     *
     * @throws GenerationFailed on invalid JSON
     */
    public static function decodeJson(string $json): array
    {
        try {
            $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GenerationFailed([new Issue('INPUT_INVALID_JSON', params: ['detail' => $e->getMessage()])]);
        }
        if (! is_array($data)) {
            throw new GenerationFailed([new Issue('INPUT_INVALID_JSON', params: ['detail' => 'top level must be an object'])]);
        }

        return $data;
    }
}
