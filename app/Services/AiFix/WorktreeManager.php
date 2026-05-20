<?php

namespace App\Services\AiFix;

/**
 * AI Fix 작업용 git worktree 생성/제거.
 *
 * 구현체:
 *   - StubWorktreeManager (테스트/PoC) — 실제 파일시스템 작업 없음
 *   - (추후) ProcessWorktreeManager — Symfony Process 로 ai-maintenance 의
 *     prepare-worktree.ps1 또는 git worktree 명령을 직접 호출
 */
interface WorktreeManager
{
    /**
     * 워크트리 생성. 절대 경로 반환.
     * 동일 jobId 가 이미 존재하면 그 경로를 그대로 반환 (멱등).
     */
    public function create(int $jobId, string $branch): string;

    /** 워크트리 제거 + 로컬 브랜치 정리. 존재하지 않으면 no-op. */
    public function remove(int $jobId): void;
}