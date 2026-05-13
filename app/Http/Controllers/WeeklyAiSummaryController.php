<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Project;
use App\Models\SystemErrorLog;
use App\Models\WeeklyAiSummary;
use App\Models\WeeklyReport;
use App\Services\AiOrchestrator;
use App\Services\DocxWriter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WeeklyAiSummaryController extends Controller
{
    private function authorizeManager(Project $project): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $project->getMemberRole($user) !== 'manager') {
            abort(403);
        }
    }

    // ─── 저장된 서머리 조회 ──────────────────────────────────────────

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeManager($project);

        $type      = $request->input('type', 'full');        // full | weekly
        $weekDate  = $request->input('week');                 // Y-m-d or null

        if ($type === 'weekly' && !$weekDate) {
            return response()->json(['summary' => null]);
        }

        $summary = WeeklyAiSummary::findStored(
            $project->id,
            $type,
            $type === 'full' ? null : $weekDate
        );

        if (!$summary) {
            return response()->json(['summary' => null]);
        }

        return response()->json([
            'summary' => [
                'content'      => $summary->content,
                'generated_at' => $summary->updated_at->format('Y.m.d H:i'),
                'generated_by' => $summary->generatedBy?->name ?? '',
            ],
        ]);
    }

    // ─── 웍스 서머리 생성 + 저장 ──────────────────────────────────────

    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeManager($project);

        $request->validate([
            'type' => 'required|in:full,weekly',
            'week' => 'nullable|date',
        ]);

        $type     = $request->input('type');
        $weekDate = $type === 'weekly' ? $request->input('week') : null;

        if ($type === 'weekly' && !$weekDate) {
            return response()->json(['error' => '주차를 선택해주세요.'], 422);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다. 관리자에게 문의해주세요.'], 422);
        }

        // 보고서 조회
        $query = WeeklyReport::where('project_id', $project->id)->with('tasks');
        if ($type === 'weekly' && $weekDate) {
            $query->where('week_start_date', $weekDate);
        }
        $reports = $query->orderBy('week_start_date')->get();

        if ($reports->isEmpty()) {
            return response()->json(['error' => '분석할 보고서가 없습니다.'], 422);
        }

        // 보고서 텍스트 빌드
        $reportLines = [];
        foreach ($reports as $r) {
            $completed  = $r->tasks->where('section', 'current_week')->where('status', 'completed')->pluck('task_name')->implode(', ');
            $inProgress = $r->tasks->where('section', 'current_week')->where('status', 'in_progress')->pluck('task_name')->implode(', ');
            $nextWeek   = $r->tasks->where('section', 'next_week')->pluck('task_name')->implode(', ');

            $reportLines[] = "=== [{$r->week_label}] {$r->author_name}"
                . ($r->team_name ? " ({$r->team_name})" : '') . " ===\n"
                . "요약: " . strip_tags($r->summary ?? '없음') . "\n"
                . "완료: " . ($completed ?: '없음') . "\n"
                . "진행: " . ($inProgress ?: '없음') . "\n"
                . "차주: " . ($nextWeek ?: '없음') . "\n"
                . ($r->special_notes ? "특이사항: {$r->special_notes}\n" : '');
        }

        $scope = $type === 'full'
            ? "프로젝트 '{$project->name}'의 전체 " . $reports->count() . "개 주간 보고서"
            : "프로젝트 '{$project->name}'의 " . ($reports->first()->week_label ?? $weekDate) . " " . $reports->count() . "개 주간 보고서";

        $systemPrompt = $type === 'full' ? <<<PROMPT
당신은 프로젝트 관리 전문가입니다. 전체 기간의 주간 보고서를 종합 분석하여 관리자에게 필요한 핵심 정보를 제공해주세요.
반드시 아래 구성으로만 작성하세요:

## 전체 진행 현황
프로젝트 전체 기간 동안의 주요 성과, 팀별·담당자별 업무 흐름을 요약합니다.

## 주요 이슈
지연되거나 막혀 있는 업무, 반복 언급 문제, 특이사항을 구체적으로 나열합니다.

## 해결 방안
각 이슈에 대한 구체적이고 실행 가능한 해결 방안을 제안합니다.

## 종합 의견
프로젝트 전반적인 진행 상태에 대한 종합 의견과 향후 주의사항을 제시합니다.

반드시 한국어로 작성하고, 각 항목은 불릿 포인트로 명확하게 정리하세요.
PROMPT : <<<PROMPT
당신은 프로젝트 관리 전문가입니다. 해당 주차 보고서를 분석하여 관리자에게 필요한 핵심 정보를 제공해주세요.
반드시 아래 구성으로만 작성하세요:

## 이번 주 진행 현황
팀 전체의 완료 업무와 진행 중인 업무를 담당자별로 요약합니다.

## 이슈 및 위험 요소
지연, 미완료, 특이사항을 구체적으로 나열합니다.

## 해결 방안
각 이슈에 대한 구체적이고 실행 가능한 해결 방안을 제안합니다.

## 다음 주 주요 일정
차주 계획 중 관리자가 주목해야 할 항목을 요약합니다.

반드시 한국어로 작성하고, 각 항목은 불릿 포인트로 명확하게 정리하세요.
PROMPT;

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint()
            );
            ['text' => $text] = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => "다음은 {$scope}입니다:\n\n" . implode("\n", $reportLines)]],
                $systemPrompt
            );

            $content = trim($text);

            // DB 저장 (upsert)
            WeeklyAiSummary::updateOrCreate(
                [
                    'project_id'      => $project->id,
                    'summary_type'    => $type,
                    'week_start_date' => $weekDate,
                ],
                [
                    'generated_by' => auth()->id(),
                    'content'      => $content,
                ]
            );

            return response()->json([
                'content'      => $content,
                'generated_at' => now()->format('Y.m.d H:i'),
                'generated_by' => auth()->user()->name,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            Log::warning('[WeeklyAiSummary] 생성 실패: ' . $e->getMessage());

            $raw = $e->getMessage();
            if (str_contains($raw, 'credit balance') || str_contains($raw, 'insufficient') || str_contains($raw, 'quota')) {
                $msg = '웍스 크레딧 또는 한도가 초과되었습니다.';
            } elseif (str_contains($raw, 'NO_KEY') || str_contains($raw, '사용 가능한 웍스')) {
                $msg = '웍스 API 키가 설정되어 있지 않습니다.';
            } else {
                $msg = '웍스 서머리 생성 중 오류가 발생했습니다.';
            }
            return response()->json(['error' => $msg], 500);
        }
    }

    // ─── Word 다운로드 ───────────────────────────────────────────────

    public function download(Request $request, Project $project): BinaryFileResponse
    {
        $this->authorizeManager($project);

        $type     = $request->input('type', 'full');
        $weekDate = $type === 'weekly' ? $request->input('week') : null;

        $summary = WeeklyAiSummary::findStored($project->id, $type, $weekDate);

        abort_if(!$summary, 404, '저장된 웍스 서머리가 없습니다. 먼저 생성해주세요.');

        $weekLabel = null;
        if ($type === 'weekly' && $weekDate) {
            $weekLabel = Carbon::parse($weekDate)->locale('ko')->isoFormat('YYYY년 M월 W주차');
        }

        $generatedAt = $summary->updated_at->format('Y.m.d H:i');
        $generatedBy = $summary->generatedBy?->name ?? '';

        $writer   = new DocxWriter();
        $writer->buildAiSummary($project, $type, $weekLabel, $summary->content, $generatedAt, $generatedBy);

        $typeSlug = $type === 'full' ? 'full' : ($weekDate ?? 'weekly');
        $filename = $project->name . '_AI서머리_' . $typeSlug . '_' . now()->format('Ymd') . '.docx';
        $path     = storage_path('app/temp/' . $filename);

        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
