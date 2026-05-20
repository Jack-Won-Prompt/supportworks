<?php

namespace App\Services\WorksBuilder\Packaging;

use App\Models\WorksBuilder\ChecklistItem;
use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\OutputPackage;
use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Preview\LayoutPreviewBuilder;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * 명세 v11 §1.8 — 검수 OK 시 자동 빌드되는 zip 패키지.
 *
 * 구성:
 *   - output.html
 *   - integrity.json
 *   - meta.json (parent/reopen 메타 포함)
 *   - options.json
 *   - layout_preview.svg
 *   - ai_call_history.json
 *   - review_history/{round}.json
 *   - checklist_applied.json
 *   - README.md
 */
class OutputPackageBuilder
{
    public function __construct(
        private LayoutPreviewBuilder $previewBuilder,
        private HtmlAssetSplitter $splitter,
    ) {}

    public function buildFor(Task $task): OutputPackage
    {
        $task->loadMissing([
            'project', 'assignee', 'currentOption',
            'reviewSessions.html', 'reviewSessions.highlights',
            'aiCallLogs', 'generatedHtml',
        ]);

        $finalHtml = $this->resolveFinalHtml($task);
        if (!$finalHtml) {
            throw new \RuntimeException("Task #{$task->id}에 사용할 HTML이 없습니다.");
        }

        $tmpDir  = storage_path('app/wb-tmp/'.uniqid('pkg_', true));
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        // 원본 HTML → assets 분리 (output.html은 풀어서 단독 뷰어 가능)
        $split = $this->splitter->split($finalHtml->html_content);

        $files = [
            'output.html'             => $split['html'],
            'integrity.json'          => $this->jsonPretty($this->integrityPayload($task, $finalHtml, $split)),
            'meta.json'               => $this->jsonPretty($this->metaPayload($task, $finalHtml)),
            'options.json'            => $this->jsonPretty($task->currentOption?->options_data ?? []),
            'layout_preview.svg'      => $task->currentOption
                ? $this->previewBuilder->build($task->currentOption)
                : '<svg/>',
            'ai_call_history.json'    => $this->jsonPretty($this->aiCallHistoryPayload($task)),
            'checklist_applied.json'  => $this->jsonPretty($this->checklistPayload($task)),
            'README.md'               => $this->readme($task, $split),
        ];

        foreach ($files as $name => $content) {
            file_put_contents($tmpDir.DIRECTORY_SEPARATOR.$name, $content);
        }

        // assets/{css,js,icon} 분리 파일 작성
        $this->writeAssets($tmpDir, $split);

        $reviewDir = $tmpDir.DIRECTORY_SEPARATOR.'review_history';
        if (!is_dir($reviewDir)) mkdir($reviewDir, 0775, true);
        foreach ($task->reviewSessions->sortBy('review_round') as $rs) {
            $payload = [
                'review_round'     => $rs->review_round,
                'decision'         => $rs->decision,
                'integrity_passed' => $rs->integrity_passed,
                'start_hash'       => $rs->start_hash,
                'end_hash'         => $rs->end_hash,
                'started_at'       => $rs->started_at?->toIso8601String(),
                'ended_at'         => $rs->ended_at?->toIso8601String(),
                'highlights'       => $rs->highlights->map(fn ($h) => [
                    'selector' => $h->selector_path,
                    'tag'      => $h->tag_name,
                    'classes'  => $h->classes,
                    'text'     => $h->text_snippet,
                    'bbox'     => [$h->bbox_x, $h->bbox_y, $h->bbox_w, $h->bbox_h],
                ])->toArray(),
            ];
            file_put_contents($reviewDir.DIRECTORY_SEPARATOR."round_{$rs->review_round}.json", $this->jsonPretty($payload));
        }

        $zipName = sprintf('%d_works_builder_output.zip', $task->id);
        $zipDir  = storage_path('app/wb-packages/'.$task->id);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0775, true);
        }
        $zipPath = $zipDir.DIRECTORY_SEPARATOR.$zipName;
        if (file_exists($zipPath)) unlink($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("ZIP 생성 실패: {$zipPath}");
        }
        $this->addDirToZip($zip, $tmpDir, '');
        $zip->close();

        $size = filesize($zipPath) ?: 0;
        $hash = hash_file('sha256', $zipPath);

        $this->cleanupDir($tmpDir);

        $relativePath = 'wb-packages/'.$task->id.'/'.$zipName;

        return OutputPackage::create([
            'task_id'          => $task->id,
            'file_path'        => $relativePath,
            'file_size_bytes'  => $size,
            'package_hash'     => $hash,
            'included_html_id' => $finalHtml->id,
            'build_metadata'   => [
                'parent_task_id' => $task->parent_task_id,
                'reopen_reason'  => $task->reopen_reason,
                'review_rounds'  => $task->reviewSessions->count(),
                'ai_calls'       => $task->aiCallLogs->count(),
            ],
            'built_at'         => now(),
        ]);
    }

    private function resolveFinalHtml(Task $task): ?GeneratedHtml
    {
        // 마지막 OK 결정의 HTML 우선, 없으면 최신 HTML
        $okSession = $task->reviewSessions()
            ->where('decision', 'ok')
            ->orderByDesc('review_round')
            ->first();
        if ($okSession) {
            return $okSession->html;
        }
        return $task->generatedHtml()->orderByDesc('version')->first();
    }

    private function integrityPayload(Task $task, GeneratedHtml $html, array $split): array
    {
        return [
            'original_html_hash'   => $html->html_hash,
            'recomputed_hash'      => GeneratedHtml::hash($html->html_content),
            'matches'              => hash_equals($html->html_hash, GeneratedHtml::hash($html->html_content)),
            'split_output_html_hash' => hash('sha256', $split['html']),
            'split_css_hash'       => $split['css'] ? hash('sha256', $split['css']) : null,
            'split_js_hash'        => $split['js']  ? hash('sha256', $split['js'])  : null,
            'split_icon_count'     => count($split['icons']),
            'note'                 => 'original_html_hash 는 AI가 생성한 단일 HTML의 SHA-256입니다. zip 내부 output.html 은 assets로 분리된 버전이며 별도 hash로 검증합니다.',
        ];
    }

    private function writeAssets(string $tmpDir, array $split): void
    {
        $assetsDir = $tmpDir.DIRECTORY_SEPARATOR.'assets';
        if (!is_dir($assetsDir)) mkdir($assetsDir, 0775, true);

        if ($split['css'] !== null) {
            $cssDir = $assetsDir.DIRECTORY_SEPARATOR.'css';
            if (!is_dir($cssDir)) mkdir($cssDir, 0775, true);
            file_put_contents($cssDir.DIRECTORY_SEPARATOR.'main.css', $split['css']);
        }
        if ($split['js'] !== null) {
            $jsDir = $assetsDir.DIRECTORY_SEPARATOR.'js';
            if (!is_dir($jsDir)) mkdir($jsDir, 0775, true);
            file_put_contents($jsDir.DIRECTORY_SEPARATOR.'main.js', $split['js']);
        }
        if (!empty($split['icons'])) {
            $iconDir = $assetsDir.DIRECTORY_SEPARATOR.'icon';
            if (!is_dir($iconDir)) mkdir($iconDir, 0775, true);
            foreach ($split['icons'] as $name => $svg) {
                file_put_contents($iconDir.DIRECTORY_SEPARATOR.$name, $svg);
            }
        }
    }

    private function metaPayload(Task $task, GeneratedHtml $html): array
    {
        return [
            'task_id'        => $task->id,
            'task_uuid'      => $task->task_uuid,
            'project'        => $task->project?->name,
            'mode'           => $task->mode,
            'parent_task_id' => $task->parent_task_id,
            'reopen_reason'  => $task->reopen_reason,
            'assignee'       => $task->assignee?->name,
            'started_at'     => $task->started_at?->toIso8601String(),
            'completed_at'   => $task->completed_at?->toIso8601String(),
            'final_html_id'  => $html->id,
            'final_version'  => $html->version,
            'review_rounds'  => $task->current_review_round,
            'total_ai_calls' => $task->total_ai_calls,
            'total_tokens'   => $task->total_tokens_used,
            'total_cost_usd' => (float) $task->total_cost_usd,
        ];
    }

    private function aiCallHistoryPayload(Task $task): array
    {
        return $task->aiCallLogs->sortBy('created_at')->values()->map(fn ($log) => [
            'id'                       => $log->id,
            'stage'                    => $log->stage,
            'review_round'             => $log->review_round,
            'primary_provider'         => $log->primary_provider,
            'fallback_used'            => (bool) $log->fallback_used,
            'final_provider'           => $log->final_provider,
            'status'                   => $log->status,
            'primary_attempt_status'   => $log->primary_attempt_status,
            'primary_error_message'    => $log->primary_error_message,
            'fallback_attempt_status'  => $log->fallback_attempt_status,
            'fallback_error_message'   => $log->fallback_error_message,
            'tokens'                   => [
                'prompt'     => $log->prompt_tokens,
                'completion' => $log->completion_tokens,
                'total'      => $log->total_tokens,
            ],
            'cost_usd'                 => $log->estimated_cost_usd,
            'response_time_ms'         => $log->response_time_ms,
            'created_at'               => $log->created_at?->toIso8601String(),
        ])->toArray();
    }

    private function checklistPayload(Task $task): array
    {
        return ChecklistItem::active()
            ->forProject($task->project_id)
            ->orderBy('category')
            ->get()
            ->map(fn ($i) => [
                'category' => $i->category,
                'title'    => $i->title,
                'check'    => $i->check_prompt_text,
            ])->toArray();
    }

    private function readme(Task $task, array $split): string
    {
        $hasCss   = $split['css'] !== null;
        $hasJs    = $split['js']  !== null;
        $iconCnt  = count($split['icons']);

        $lines = [
            "# Works Builder Task #{$task->id}",
            '',
            "프로젝트: " . ($task->project?->name ?? '-'),
            "모드: " . ($task->mode === 'enhance' ? '고도화' : '신규'),
            "최종 검수 차수: {$task->current_review_round}",
            '',
            '## 단독 뷰어',
            '- 이 zip을 풀어서 `output.html`을 브라우저에 더블클릭하면 그대로 동작합니다.',
            '- `output.html`은 `assets/css/main.css`, `assets/js/main.js`, `assets/icon/*.svg`를 상대 경로로 참조합니다.',
            '',
            '## 포함 파일',
            '- output.html — 분리된 표준 HTML (단독 뷰어 가능)',
            '- assets/css/main.css' . ($hasCss ? '' : ' — (없음)'),
            '- assets/js/main.js'   . ($hasJs  ? '' : ' — (없음)'),
            "- assets/icon/icon-N.svg — 인라인 SVG 분리 {$iconCnt}개" . ($iconCnt === 0 ? ' (없음)' : ''),
            '- integrity.json — 원본 HTML SHA-256 + 분리본 hash',
            '- meta.json — Task 메타 (parent/reopen 정보 포함)',
            '- options.json — 사용된 레이아웃 옵션',
            '- layout_preview.svg — 와이어프레임 (담당자 확인용)',
            '- ai_call_history.json — AI 호출 이력 (토큰/비용/폴백)',
            '- review_history/round_*.json — 차수별 검수 기록',
            '- checklist_applied.json — 적용된 표준 체크리스트',
            '',
            '## 원본 단일 HTML이 필요할 때',
            '- Task 상세 화면의 [📥 HTML 다운] 버튼을 사용하세요. 그 다운로드는 AI가 만든 단일 HTML (인라인 CSS/JS/SVG)을 hash 그대로 반환합니다.',
        ];
        return implode("\n", $lines);
    }

    private function jsonPretty(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $dir.DIRECTORY_SEPARATOR.$item;
            $rel  = ($prefix === '' ? '' : $prefix.'/') . $item;
            if (is_dir($full)) {
                $this->addDirToZip($zip, $full, $rel);
            } else {
                $zip->addFile($full, $rel);
            }
        }
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($full) ? $this->cleanupDir($full) : @unlink($full);
        }
        @rmdir($dir);
    }
}
