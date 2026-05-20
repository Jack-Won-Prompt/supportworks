<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;

/**
 * 워크트리에 실제 코드 변경을 적용하는 어댑터.
 *
 * 구현체:
 *   - StubAiCodeApplier (PoC) — 표식 파일만 만들고 끝 (실제 코드 수정 없음)
 *   - (추후) ClaudeAiCodeApplier — Anthropic API 로 변경 제안 받아 적용
 */
interface AiCodeApplier
{
    /**
     * job 의 changed_files 에 변경 적용. 성공하면 true.
     * 실패해도 throw 하지 않고 false 반환 (호출측이 tests_failed 로 전환).
     */
    public function apply(AiFixJob $job, string $worktreePath): bool;
}