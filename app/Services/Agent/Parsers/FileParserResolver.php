<?php

namespace App\Services\Agent\Parsers;

class FileParserResolver
{
    /** @param FileParser[] $parsers */
    public function __construct(private readonly array $parsers) {}

    public function resolve(string $mimeType): FileParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($mimeType)) {
                return $parser;
            }
        }

        return new FallbackFileParser();
    }
}
