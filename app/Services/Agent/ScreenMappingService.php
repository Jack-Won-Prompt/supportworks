<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentScreen;
use App\Models\User;
use App\Services\Agent\Figma\FigmaClientFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScreenMappingService
{
    public function __construct(
        private readonly FigmaClientFactory  $clientFactory,
        private readonly TraceabilityService $traceability,
    ) {}

    /**
     * Figma 파일의 최상위 프레임 목록 + 미리보기 + 매핑 상태 조회
     */
    public function getFigmaFrames(string $fileKey, User $user): array
    {
        $client = $this->clientFactory->forUser($user);
        $file   = $client->getFile($fileKey);
        $frames = $file->getFrames();

        if (empty($frames)) {
            return [];
        }

        // 소규모 미리보기 이미지 (scale 0.5)
        $frameIds = array_column($frames, 'id');
        $previews = [];
        try {
            $previews = $client->getImages($fileKey, $frameIds, 'png', 0.5);
        } catch (\Exception) {
            // 이미지 실패 무시
        }

        // 현재 이미 매핑된 nodeId 목록
        $mappedNodeIds = AiAgentScreen::where('figma_file_key', $fileKey)
            ->whereNotNull('figma_frame_id')
            ->pluck('id', 'figma_frame_id')
            ->all(); // [nodeId => screenId]

        return collect($frames)->map(function (array $frame) use ($previews, $mappedNodeIds) {
            $nodeId = $frame['id'];
            return [
                'node_id'           => $nodeId,
                'name'              => $frame['name'],
                'preview_url'       => $previews[$nodeId] ?? null,
                'is_mapped'         => isset($mappedNodeIds[$nodeId]),
                'mapped_screen_id'  => $mappedNodeIds[$nodeId] ?? null,
            ];
        })->all();
    }

    /**
     * 이름 기반 자동 매핑 제안 (threshold: 0.7)
     */
    public function suggestMappings(int $projectId, string $fileKey, User $user): array
    {
        $screens = AiAgentScreen::where('project_id', $projectId)
            ->whereNull('figma_frame_id')
            ->whereNull('archived_at')
            ->get();

        if ($screens->isEmpty()) return [];

        $frames        = $this->getFigmaFrames($fileKey, $user);
        $unmappedFrames = collect($frames)->filter(fn($f) => !$f['is_mapped'])->values();

        if ($unmappedFrames->isEmpty()) return [];

        $suggestions = [];

        foreach ($screens as $screen) {
            $best = $this->findBestNameMatch($screen->title, $unmappedFrames->all());
            if ($best && $best['similarity'] >= 0.7) {
                $suggestions[] = [
                    'screen_id'        => $screen->id,
                    'screen_screen_id' => $screen->screen_id,
                    'screen_name'      => $screen->title,
                    'figma_node_id'    => $best['node_id'],
                    'figma_frame_name' => $best['name'],
                    'figma_file_key'   => $fileKey,
                    'similarity'       => round($best['similarity'], 2),
                    'preview_url'      => $best['preview_url'],
                ];
            }
        }

        // 유사도 내림차순
        usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $suggestions;
    }

    /**
     * 단일 매핑 적용
     */
    public function applyMapping(
        AiAgentScreen $screen,
        string        $fileKey,
        string        $nodeId,
        string        $frameName,
        int           $userId,
    ): void {
        $screen->mapToFigma($fileKey, $nodeId, $frameName, $userId);

        // 추적성 링크 (T03)
        $this->traceability->link(
            projectId:  $screen->project_id,
            sourceType: 'screen',
            sourceId:   $screen->id,
            sourceRef:  $screen->screen_id,
            targetType: 'figma_frame',
            targetId:   $screen->id, // 외부 시스템이므로 screen ID를 proxy로 사용
            targetRef:  "{$fileKey}:{$nodeId}",
            linkType:   'designed_in',
        );
    }

    /**
     * 일괄 매핑 적용
     */
    public function applySuggestionsBatch(array $suggestions, User $user): int
    {
        $applied = 0;

        DB::transaction(function () use ($suggestions, $user, &$applied) {
            foreach ($suggestions as $sug) {
                $screen = AiAgentScreen::find($sug['screen_id'] ?? null);
                if (!$screen || $screen->hasFigmaMapping()) continue;

                $this->applyMapping(
                    screen:    $screen,
                    fileKey:   $sug['figma_file_key']   ?? '',
                    nodeId:    $sug['figma_node_id']    ?? '',
                    frameName: $sug['figma_frame_name'] ?? '',
                    userId:    $user->id,
                );
                $applied++;
            }
        });

        return $applied;
    }

    /**
     * 프로젝트 매핑 진척도
     */
    public function getMappingStatus(int $projectId): array
    {
        $total  = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->count();
        $mapped = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')
            ->whereNotNull('figma_frame_id')
            ->count();

        return [
            'total'    => $total,
            'mapped'   => $mapped,
            'unmapped' => $total - $mapped,
            'percent'  => $total > 0 ? round($mapped / $total * 100) : 0,
        ];
    }

    /**
     * 매핑 목록 JSON 내보내기
     */
    public function exportMappings(int $projectId): array
    {
        return AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->orderBy('screen_id')
            ->get(['id', 'screen_id', 'title', 'figma_file_key', 'figma_frame_id', 'figma_frame_name', 'figma_url', 'figma_mapped_at'])
            ->map(fn($s) => [
                'screen_id'        => $s->screen_id,
                'title'            => $s->title,
                'is_mapped'        => $s->hasFigmaMapping(),
                'figma_file_key'   => $s->figma_file_key,
                'figma_node_id'    => $s->figma_frame_id,
                'figma_frame_name' => $s->figma_frame_name,
                'figma_url'        => $s->figma_url,
                'mapped_at'        => $s->figma_mapped_at?->toIso8601String(),
            ])
            ->all();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function findBestNameMatch(string $screenName, array $frames): ?array
    {
        $normalized = $this->normalize($screenName);
        $best       = null;
        $bestScore  = 0.0;

        foreach ($frames as $frame) {
            $score = $this->calculateSimilarity($normalized, $this->normalize($frame['name']));
            if ($score > $bestScore) {
                $best      = array_merge($frame, ['similarity' => $score]);
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function normalize(string $name): string
    {
        // 소문자 변환 + 영숫자/한글 이외 문자 제거
        return Str::lower(preg_replace('/[^a-z0-9\p{Hangul}]/u', '', $name));
    }

    private function calculateSimilarity(string $a, string $b): float
    {
        if ($a === '' && $b === '') return 1.0;
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;

        // 대소문자/공백 제거 후 동일
        if ($a === $b) return 0.95;

        // 포함 관계
        if (str_contains($a, $b) || str_contains($b, $a)) return 0.85;

        // Levenshtein 비율
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) return 0.0;

        $distance = levenshtein($a, $b);
        return max(0.0, 1.0 - ($distance / $maxLen));
    }
}
