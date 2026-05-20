<?php

namespace App\Services\AiFix;

use App\Models\SystemErrorLog;

/**
 * SystemErrorLog 한 건을 분석해 AnalysisResult 를 돌려주는 어댑터.
 *
 * 구현체:
 *   - StubAiAnalyzer: PoC 단계, 실제 LLM 호출 없이 휴리스틱만으로 추정
 *   - (추후) ClaudeAiAnalyzer: Anthropic API 호출로 코드 컨텍스트 분석
 */
interface AiAnalyzer
{
    public function analyze(SystemErrorLog $errorLog): AnalysisResult;
}