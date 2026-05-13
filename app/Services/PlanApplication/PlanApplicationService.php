<?php

namespace App\Services\PlanApplication;

use App\Models\PlanApplication;
use App\Models\PlanningDoc;
use App\Models\PlanningDocHistory;
use App\Models\ProjectFeatureSuggestion;
use App\Models\Requirement;
use App\Models\SubTask;
use App\Models\User;
use App\Services\PlanApplication\Templates\TemplateRegistry;
use Illuminate\Support\Facades\DB;

class PlanApplicationService
{
    public function __construct(
        private MarkdownInserter $inserter,
        private TemplateRegistry $registry,
    ) {}

    public function preview(array $requirementIds, string $templateName = 'default', array $options = []): string
    {
        $requirements = Requirement::whereIn('id', $requirementIds)->get();
        $template     = $this->registry->get($templateName);
        return $template->render($requirements, $options);
    }

    public function apply(
        array    $requirementIds,
        int      $planId,
        int      $appliedById,
        string   $position      = 'end',
        ?string  $sectionAnchor = null,
        string   $templateName  = 'default'
    ): PlanApplicationResult {
        $result = new PlanApplicationResult();

        $plan = PlanningDoc::findOrFail($planId);

        // Separate already-applied from fresh
        $existingMap = PlanApplication::whereIn('requirement_id', $requirementIds)
            ->where('plan_id', $planId)
            ->whereNull('deleted_at')
            ->pluck('applied_at', 'requirement_id');

        $toApply = [];
        foreach ($requirementIds as $reqId) {
            if (isset($existingMap[$reqId])) {
                $result->skipped[] = [
                    'requirement_id' => $reqId,
                    'reason'         => 'already_applied',
                    'applied_at'     => (string) $existingMap[$reqId],
                ];
            } else {
                $toApply[] = $reqId;
            }
        }

        if (empty($toApply)) {
            return $result;
        }

        $requirements = Requirement::whereIn('id', $toApply)->get();
        $siMode       = $plan->project?->si_mode_enabled ?? false;
        $template     = $this->registry->get($templateName);
        $user         = User::find($appliedById);
        $userName     = $user?->name ?? '알 수 없음';

        // Render each requirement individually so reverts can remove precise blocks
        $individualMarkdowns = [];
        foreach ($requirements as $req) {
            $individualMarkdowns[$req->id] = $template->render(
                collect([$req]),
                ['si_mode' => $siMode, 'applied_by' => $userName]
            );
        }
        $markdown = implode("\n\n---\n\n", $individualMarkdowns);

        DB::transaction(function () use (
            $plan, $requirements, $markdown, $individualMarkdowns, $appliedById,
            $position, $sectionAnchor, $templateName, &$result
        ) {
            // Optimistic lock: re-read version before writing
            $freshPlan     = PlanningDoc::lockForUpdate()->find($plan->id);
            $beforeContent = $freshPlan->content ?? '';
            $newContent    = $this->inserter->insert(
                $beforeContent,
                $markdown,
                $position,
                $sectionAnchor
            );
            $newVersion = $freshPlan->version + 1;

            $freshPlan->update([
                'content' => $newContent,
                'version' => $newVersion,
            ]);

            // 변경 이력 기록
            $titles  = $requirements->pluck('title')->take(3)->implode(', ');
            $total   = $requirements->count();
            $summary = "요구사항 {$total}개 반영" . ($titles ? ": {$titles}" . ($total > 3 ? ' 외 ' . ($total - 3) . '개' : '') : '');

            PlanningDocHistory::create([
                'planning_doc_id' => $freshPlan->id,
                'version'         => $newVersion,
                'change_type'     => 'user_edit',
                'before_content'  => $beforeContent,
                'after_content'   => $newContent,
                'summary'         => $summary,
                'changed_by'      => $appliedById,
                'approval_status' => 'approved',
            ]);

            $now = now();
            foreach ($requirements as $req) {
                try {
                    $app = PlanApplication::create([
                        'requirement_id'    => $req->id,
                        'plan_id'           => $freshPlan->id,
                        'applied_by_id'     => $appliedById,
                        'applied_at'        => $now,
                        'insertion_position'=> $position,
                        'section_anchor'    => $sectionAnchor,
                        'template_used'     => $templateName,
                        'inserted_markdown' => $individualMarkdowns[$req->id] ?? $markdown,
                        'is_completed'      => true,
                        'completed_at'      => $now,
                    ]);

                    $req->update([
                        'applied_to_plan'    => true,
                        'applied_to_plan_at' => $now,
                        'applied_to_plan_id' => $freshPlan->id,
                    ]);

                    $result->applied[] = [
                        'requirement_id' => $req->id,
                        'application_id' => $app->id,
                    ];
                } catch (\Throwable $e) {
                    $result->failed[] = [
                        'requirement_id' => $req->id,
                        'reason'         => $e->getMessage(),
                    ];
                }
            }
        });

        return $result;
    }

    public function revert(int $applicationId): ?string
    {
        $app = PlanApplication::with(['plan', 'requirement'])->findOrFail($applicationId);

        // 진행중이거나 완료된 일정 Task가 있으면 차단
        if ($app->requirement_id) {
            $blocked = SubTask::where('requirement_id', $app->requirement_id)
                ->whereIn('status', ['in_progress', 'done'])
                ->exists();
            if ($blocked) {
                throw new \RuntimeException('진행중이거나 완료된 일정 Task가 있어 취소할 수 없습니다.');
            }
        }

        $updatedContent = null;
        if ($app->inserted_markdown && $app->plan) {
            $this->removeBlockFromDoc($app->plan, $app->inserted_markdown);
            $updatedContent = $app->plan->fresh()->content;
        }

        // 미시작 SubTask 삭제
        if ($app->requirement_id) {
            SubTask::where('requirement_id', $app->requirement_id)
                ->where('status', 'not_started')
                ->delete();
        }

        $app->requirement?->update([
            'applied_to_plan'    => false,
            'applied_to_plan_at' => null,
            'applied_to_plan_id' => null,
        ]);

        $app->delete();
        return $updatedContent;
    }

    /**
     * 요구사항 삭제 cascade 전용: 블로킹 태스크 재검사 없이 강제 취소.
     * destroy()에서 이미 검증 완료된 후 호출되므로 중복 체크 생략.
     */
    public function forceRevert(int $applicationId): void
    {
        $app = PlanApplication::with(['plan', 'requirement'])->find($applicationId);
        if (!$app) return;

        if ($app->inserted_markdown && $app->plan) {
            $this->removeBlockFromDoc($app->plan, $app->inserted_markdown);
        }

        if ($app->requirement_id) {
            SubTask::where('requirement_id', $app->requirement_id)
                ->where('status', 'not_started')
                ->delete();
        }

        $app->requirement?->update([
            'applied_to_plan'    => false,
            'applied_to_plan_at' => null,
            'applied_to_plan_id' => null,
        ]);

        $app->delete();
    }

    /**
     * 웍스 기능 추천을 통해 직접 삽입된 내용 취소 (PlanApplication 없이 저장된 경우).
     */
    public function revertFeatureSuggestion(ProjectFeatureSuggestion $suggestion): void
    {
        if ($suggestion->is_applied && $suggestion->inserted_markdown && $suggestion->planning_doc_id) {
            $doc = PlanningDoc::find($suggestion->planning_doc_id);
            if ($doc) {
                $this->removeBlockFromDoc($doc, $suggestion->inserted_markdown);
            }
        }

        $suggestion->update([
            'is_applied'        => false,
            'applied_at'        => null,
            'requirement_id'    => null,
            'planning_doc_id'   => null,
            'inserted_markdown' => null,
        ]);
    }

    private function removeBlockFromDoc(PlanningDoc $doc, string $block): void
    {
        $content = $doc->content ?? '';
        if (!str_contains($content, $block)) return;

        // Try removing with trailing separator (block is first of several)
        if (str_contains($content, $block . "\n\n---\n\n")) {
            $newContent = str_replace($block . "\n\n---\n\n", '', $content);
        // Try removing with leading separator (block is after another block)
        } elseif (str_contains($content, "\n\n---\n\n" . $block)) {
            $newContent = str_replace("\n\n---\n\n" . $block, '', $content);
        // Try removing with leading double-newline (block follows original content)
        } elseif (str_contains($content, "\n\n" . $block)) {
            $newContent = str_replace("\n\n" . $block, '', $content);
        } else {
            $newContent = str_replace($block, '', $content);
        }

        $doc->update(['content' => rtrim($newContent)]);
    }

    /** Extract ## and ### headings from a plan's content for the modal dropdown */
    public function getHeadings(PlanningDoc $plan): array
    {
        $content  = $plan->content ?? '';
        $headings = [];

        foreach (explode("\n", $content) as $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $m)) {
                $headings[] = trim($m[2]);
            }
        }

        return array_values(array_unique($headings));
    }
}
