<?php

namespace App\Services\Agent\Parsers;

/**
 * 파싱된 파일 내용 DTO
 * T17-B에서 웍스에 전달하는 구조화된 형태
 */
class ParsedFileContent
{
    public function __construct(
        public readonly string  $fileType,
        public readonly ?string $extractedText,    // 텍스트 형태로 추출된 내용
        public readonly array   $structure,        // 시트별/슬라이드별/페이지별 구조
        public readonly array   $imageReferences,  // 웍스 호출용 스토리지 경로 목록
        public readonly array   $metadata,         // 파일별 메타 (시트 수 등)
        public readonly bool    $needsAiVisual = false,  // PDF/이미지: 웍스 직접 분석 필요
    ) {}

    public function toJson(): string
    {
        return json_encode([
            'file_type'        => $this->fileType,
            'extracted_text'   => $this->extractedText,
            'structure'        => $this->structure,
            'image_references' => $this->imageReferences,
            'metadata'         => $this->metadata,
            'needs_ai_visual'  => $this->needsAiVisual,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        return new self(
            fileType:        $data['file_type'] ?? 'other',
            extractedText:   $data['extracted_text'] ?? null,
            structure:       $data['structure'] ?? [],
            imageReferences: $data['image_references'] ?? [],
            metadata:        $data['metadata'] ?? [],
            needsAiVisual:   $data['needs_ai_visual'] ?? false,
        );
    }

    /**
     * 웍스 프롬프트에 첨부할 텍스트 요약
     * T17-B에서 사용
     */
    public function toPromptText(string $fileName): string
    {
        if ($this->needsAiVisual) {
            return "[{$fileName}] 이미지/PDF 파일 — 웍스 직접 시각 분석 필요";
        }

        if (!$this->extractedText) {
            return "[{$fileName}] 텍스트 없음 (메타데이터만 존재)";
        }

        $meta = [];
        if (!empty($this->metadata)) {
            foreach ($this->metadata as $k => $v) {
                $meta[] = "{$k}: {$v}";
            }
        }

        $header = "[{$fileName}]";
        if ($meta) {
            $header .= ' (' . implode(', ', $meta) . ')';
        }

        return $header . "\n" . $this->extractedText;
    }
}
