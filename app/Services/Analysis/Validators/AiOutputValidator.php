<?php

namespace App\Services\Analysis\Validators;

class AiOutputValidator
{
    private const VALID_CATEGORIES = [
        'functional', 'non_functional', 'constraint', 'ui_ux',
        'integration', 'performance', 'security', 'other',
    ];

    private const VALID_PRIORITIES = ['critical', 'high', 'medium', 'low'];

    public function validate(string $rawJson): array
    {
        $json = $this->extractJson($rawJson);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('웍스 응답이 유효한 JSON이 아닙니다: ' . json_last_error_msg());
        }

        if (!isset($data['requirements']) || !is_array($data['requirements'])) {
            throw new \RuntimeException('웍스 응답에 requirements 배열이 없습니다.');
        }

        $data['summary']  = isset($data['summary'])  ? (string) $data['summary']  : '';
        $data['warnings'] = isset($data['warnings']) ? (array)  $data['warnings'] : [];

        $validated = [];
        foreach (array_slice($data['requirements'], 0, 30) as $i => $req) {
            $validated[] = $this->validateRequirement($req, $i);
        }
        $data['requirements'] = $validated;

        return $data;
    }

    private function extractJson(string $raw): string
    {
        // Strip markdown code fences: ```json ... ``` or ``` ... ```
        $stripped = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $stripped = preg_replace('/\s*```$/', '', trim($stripped));

        // If it still doesn't start with '{', try to find the first '{' ... last '}'
        if (!str_starts_with(ltrim($stripped), '{')) {
            $start = strpos($raw, '{');
            $end   = strrpos($raw, '}');
            if ($start !== false && $end !== false && $end > $start) {
                return substr($raw, $start, $end - $start + 1);
            }
        }

        return $stripped;
    }

    private function validateRequirement(array $req, int $index): array
    {
        $prefix = "requirements[{$index}]";

        if (empty($req['title'])) {
            throw new \RuntimeException("{$prefix}.title 이 비어있습니다.");
        }

        if (!in_array($req['category'] ?? '', self::VALID_CATEGORIES)) {
            $req['category'] = 'other';
        }

        if (!in_array($req['priority'] ?? '', self::VALID_PRIORITIES)) {
            $req['priority'] = 'medium';
        }

        $confidence = (float) ($req['confidence'] ?? 0.8);
        $req['confidence'] = max(0.0, min(1.0, $confidence));

        $req['description'] = (string) ($req['description'] ?? '');
        $req['source_ref']  = (string) ($req['source_ref']  ?? '');
        $req['tags']        = array_slice((array) ($req['tags'] ?? []), 0, 5);

        return $req;
    }
}
