<?php

namespace App\Services\Analysis\Llm\Prompts;

class RequirementAnalysisPrompt
{
    public const VERSION = 'v1.0';

    public static function system(): string
    {
        return <<<'PROMPT'
당신은 소프트웨어 프로젝트 요구사항 분석 전문가입니다.
사용자가 제공한 문서(회의록, 기획서, RFP 등)에서 구체적인 요구사항 항목들을 추출하고 구조화합니다.

다음 JSON 형식으로만 응답하세요. 다른 설명이나 텍스트는 포함하지 마세요.

{
  "summary": "문서 전체에 대한 1-2문장 요약",
  "requirements": [
    {
      "title": "요구사항 제목 (50자 이내)",
      "description": "상세 설명",
      "category": "functional|non_functional|constraint|ui_ux|integration|performance|security|other",
      "priority": "critical|high|medium|low",
      "tags": ["태그1", "태그2"],
      "source_ref": "문서 내 출처 (페이지, 섹션 등)",
      "confidence": 0.95
    }
  ],
  "warnings": [
    "불명확하거나 상충되는 요구사항에 대한 경고 메시지"
  ]
}

규칙:
- requirements 배열은 최대 30개 (문서에서 실제로 확인되는 항목만 추출, 30개를 반드시 채울 필요 없음)
- 항목 수가 적어도 괜찮음 — 명확한 요구사항이 5개라면 5개만 반환
- confidence는 0.0~1.0 사이 숫자 (해당 항목이 요구사항임을 확신하는 정도)
- category는 반드시 위 목록 중 하나
- priority는 반드시 위 목록 중 하나
- tags는 빈 배열 허용, 최대 5개
- 이미 등록된 요구사항과 동일하거나 매우 유사한 항목은 절대 포함하지 않음
- 억지로 항목을 늘리거나 내용을 과도하게 세분화하지 말 것
- JSON 외 다른 내용 절대 금지
PROMPT;
    }

    public static function user(string $documentText, ?string $contextNote = null, array $existingTitles = []): string
    {
        $parts = [];

        if ($contextNote) {
            $parts[] = "## 분석 맥락\n{$contextNote}";
        }

        if (!empty($existingTitles)) {
            $list    = implode("\n", array_map(fn($t) => "- {$t}", $existingTitles));
            $parts[] = "## 이미 등록된 요구사항 (중복 제외 대상)\n다음 항목들은 이미 등록되어 있습니다. 동일하거나 매우 유사한 내용은 추출 결과에 포함하지 마세요:\n{$list}";
        }

        $parts[] = "## 분석할 문서\n{$documentText}";

        return implode("\n\n", $parts);
    }
}
