<?php

namespace App\Http\Controllers;

use App\Mail\PlanningContentMail;
use App\Models\AiSetting;
use App\Models\PlanApplication;
use App\Models\PlanningDoc;
use App\Models\PlanningDocHistory;
use App\Models\PlanningDocInput;
use App\Models\Project;
use App\Models\ProjectFeatureSuggestion;
use App\Models\Requirement;
use App\Models\SubTask;
use App\Models\SystemErrorLog;
use App\Services\AiOrchestrator;
use App\Services\ClaudeService;
use App\Services\OpenAiService;
use App\Services\PlanApplication\MarkdownInserter;
use App\Services\PlanApplication\PlanApplicationService;
use App\Services\PlanApplication\Templates\TemplateRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

class PlanningDocController extends Controller
{
    // ── 목록 ───────────────────────────────────────────────────────
    public function index(Project $project)
    {
        $this->authorizeProject($project);
        $doc = $project->planningDocs()->first();
        if ($doc) {
            $url = route('projects.planning.show', [$project, $doc]);
            if (request()->has('popup')) $url .= '?popup=1';
            return redirect($url);
        }
        return view('planning.index', compact('project'));
    }

    // ── 생성 ───────────────────────────────────────────────────────
    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $existing = $project->planningDocs()->first();
        if ($existing) {
            $url = route('projects.planning.show', [$project, $existing]);
            if ($request->boolean('popup')) $url .= '?popup=1';
            return redirect($url)->with('info', '기획서가 이미 존재합니다.');
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'content'     => 'nullable|string',
        ]);

        $doc = $project->planningDocs()->create([
            ...$validated,
            'created_by' => auth()->id(),
            'status'     => 'draft',
            'version'    => 1,
        ]);

        if (!empty($validated['content'])) {
            PlanningDocHistory::create([
                'planning_doc_id' => $doc->id,
                'version'         => 1,
                'change_type'     => 'user_add',
                'before_content'  => null,
                'after_content'   => $validated['content'],
                'summary'         => '기획서 최초 작성',
                'changed_by'      => auth()->id(),
                'approval_status' => 'approved',
            ]);
        }

        $showUrl = route('projects.planning.show', [$project, $doc]);
        if ($request->boolean('popup')) {
            $showUrl .= '?popup=1';
        }
        return redirect($showUrl)->with('success', '기획서가 생성되었습니다.');
    }

    // ── 상세 ───────────────────────────────────────────────────────
    public function show(Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);
        $doc->load(['creator', 'approver']);
        $histories         = $doc->histories()->with('changedBy')->get();
        $pendingInputs     = $doc->pendingInputs()->with('creator')->orderByDesc('created_at')->get();
        $members           = $project->members()->get();
        $featureSuggestions = ProjectFeatureSuggestion::withTrashed()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')->get();
        return view('planning.show', compact('project', 'doc', 'histories', 'pendingInputs', 'members', 'featureSuggestions'));
    }

    // ── 본문 수정 저장 ────────────────────────────────────────────
    public function update(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content'     => 'nullable|string',
        ]);

        $beforeContent = $doc->content;
        $doc->update($validated);

        if (isset($validated['content']) && $validated['content'] !== $beforeContent) {
            PlanningDocHistory::create([
                'planning_doc_id' => $doc->id,
                'version'         => $doc->version,
                'change_type'     => 'user_edit',
                'before_content'  => $beforeContent,
                'after_content'   => $validated['content'],
                'summary'         => '사용자 직접 수정',
                'changed_by'      => auth()->id(),
                'approval_status' => 'approved',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '저장되었습니다.');
    }

    // ── 삭제 ───────────────────────────────────────────────────────
    public function destroy(Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);
        $doc->delete();
        return redirect()->route('projects.planning.index', $project)
            ->with('success', '기획서가 삭제되었습니다.');
    }

    // ── 리셋 ───────────────────────────────────────────────────────
    public function reset(Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        DB::transaction(function () use ($doc, $project) {

            // ── 수집 단계: 삭제/수정 전에 필요한 데이터를 먼저 모두 수집 ──

            $planApps = PlanApplication::where('plan_id', $doc->id)
                ->whereNull('deleted_at')
                ->with('requirement')
                ->get();

            $suggestions = ProjectFeatureSuggestion::where('project_id', $project->id)
                ->whereNull('deleted_at')
                ->get();

            // 간트(SubTask) 삭제에 필요한 requirement_id 목록 (삭제 전 수집)
            $reqIds = $planApps->pluck('requirement_id')
                ->merge($suggestions->pluck('requirement_id'))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // ── 1. 기획서 내용에서 삽입 블록 제거 (메모리에서 한 번에 처리) ──
            //    forceRevert()의 반복 DB 재조회 대신, 내용을 메모리에 올려 순차 제거 후 1회 저장
            $content = $doc->fresh()->content ?? '';

            foreach ($planApps as $app) {
                if ($app->inserted_markdown) {
                    $content = $this->stripBlock($content, $app->inserted_markdown);
                }
            }
            foreach ($suggestions as $suggestion) {
                if ($suggestion->is_applied && $suggestion->inserted_markdown) {
                    $content = $this->stripBlock($content, $suggestion->inserted_markdown);
                }
            }

            $doc->update(['content' => rtrim($content)]);

            // ── 2. PlanApplication soft-delete + 요구사항 applied 플래그 리셋 ──
            foreach ($planApps as $app) {
                $app->requirement?->update([
                    'applied_to_plan'    => false,
                    'applied_to_plan_at' => null,
                    'applied_to_plan_id' => null,
                ]);
                $app->delete();
            }

            // ── 3. 웍스 기능 추천 상태 초기화 + soft-delete ──
            foreach ($suggestions as $suggestion) {
                $suggestion->update([
                    'is_applied'        => false,
                    'applied_at'        => null,
                    'requirement_id'    => null,
                    'planning_doc_id'   => null,
                    'inserted_markdown' => null,
                ]);
                $suggestion->delete();
            }

            // ── 4. 잔여 요구사항 applied 플래그 일괄 리셋 ──
            Requirement::where('applied_to_plan_id', $doc->id)
                ->update([
                    'applied_to_plan'    => false,
                    'applied_to_plan_at' => null,
                    'applied_to_plan_id' => null,
                ]);

            // ── 5. 일정/간트 초기화 ──
            //    SubTask는 source_plan_id(웍스 Agent 전용)가 아닌 requirement_id로 연결되므로
            //    수집한 requirement_id 기준으로 삭제
            if (!empty($reqIds)) {
                SubTask::whereIn('requirement_id', $reqIds)->delete();
            }
            // source_plan_id 기반 태스크도 함께 제거 (웍스 Agent 간트 경로)
            SubTask::where('source_plan_id', $doc->id)->delete();

            // ── 6. 기획서 메타 상태 리셋 ──
            $doc->update([
                'pending_content' => null,
                'ai_summary'      => null,
                'ai_conflicts'    => null,
                'ai_suggestions'  => null,
                'status'          => 'draft',
                'approved_by'     => null,
                'approved_at'     => null,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    private function stripBlock(string $content, string $block): string
    {
        if (!str_contains($content, $block)) {
            return $content;
        }
        if (str_contains($content, $block . "\n\n---\n\n")) {
            return str_replace($block . "\n\n---\n\n", '', $content);
        }
        if (str_contains($content, "\n\n---\n\n" . $block)) {
            return str_replace("\n\n---\n\n" . $block, '', $content);
        }
        if (str_contains($content, "\n\n" . $block)) {
            return str_replace("\n\n" . $block, '', $content);
        }
        return str_replace($block, '', $content);
    }

    // ── 사용자 내용 추가 ─────────────────────────────────────────
    public function addInput(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'input_type' => 'required|in:text,memo,requirement,file',
            'content'    => 'nullable|string',
            'file'       => 'nullable|file|max:10240',
        ]);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filePath = $file->store('planning_inputs', 'public');
            $fileName = $file->getClientOriginalName();
        }

        PlanningDocInput::create([
            'planning_doc_id' => $doc->id,
            'input_type'      => $validated['input_type'],
            'content'         => $validated['content'] ?? null,
            'file_path'       => $filePath,
            'file_name'       => $fileName,
            'status'          => 'pending',
            'created_by'      => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '내용이 추가되었습니다.');
    }

    // ── 입력 항목 삭제 ────────────────────────────────────────────
    public function deleteInput(Request $request, Project $project, PlanningDoc $doc, PlanningDocInput $input)
    {
        $this->authorizeProject($project);
        if ($input->planning_doc_id !== $doc->id) abort(403);
        $input->delete();
        return response()->json(['ok' => true]);
    }

    // ── 웍스 통합 ──────────────────────────────────────────────────
    public function aiIntegrate(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $pendingInputs = $doc->pendingInputs()->with('creator')->get();
        if ($pendingInputs->isEmpty()) {
            return response()->json(['error' => '통합할 추가 내용이 없습니다.'], 422);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey() && !$settings->manusKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 500);
        }

        $inputsText = $pendingInputs->map(function ($i) {
            $typeLabel = $i->input_type_label;
            return "### [{$typeLabel}] ({$i->creator->name})\n" . ($i->content ?? "파일: {$i->file_name}");
        })->join("\n\n---\n\n");

        $currentContent = $doc->content ?: '(기획서 내용 없음 - 새로 작성 필요)';

        $systemPrompt = <<<'PROMPT'
당신은 전문 기획서 편집 웍스입니다.
기존 기획서에 사용자가 추가한 내용을 통합하는 역할을 수행합니다.

규칙:
- 기존 내용을 임의로 삭제하지 않고 반드시 변경 사유를 남깁니다
- 중복 내용은 병합하고 유사 내용은 통합합니다
- 기획서의 목차 구조를 유지하며 내용을 재배치합니다
- Markdown 형식으로 기획서를 작성합니다 (# ## ### 헤딩 사용)

반드시 아래 JSON 형식으로만 응답하십시오 (다른 텍스트 없이):
{"integrated_content":"...","summary":"...","conflicts":"...","suggestions":"..."}
PROMPT;

        $userMessage = "## 기존 기획서\n\n{$currentContent}\n\n---\n\n## 사용자 추가 내용\n\n{$inputsText}\n\n위 내용을 기존 기획서에 통합하여 JSON으로 반환하십시오.";

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint(),
            );
            ['text' => $raw] = $orchestrator->chatRaw([['role' => 'user', 'content' => $userMessage]], $systemPrompt);
            $json = $this->extractJsonRobust($raw);

            if (!$json || !isset($json['integrated_content'])) {
                return response()->json(['error' => '웍스 응답 파싱 실패'], 500);
            }

            $beforeContent = $doc->content;

            $doc->update([
                'pending_content' => $json['integrated_content'],
                'ai_summary'      => $json['summary'] ?? null,
                'ai_conflicts'    => $json['conflicts'] ?? null,
                'ai_suggestions'  => $json['suggestions'] ?? null,
                'status'          => 'pending_review',
            ]);

            PlanningDocHistory::create([
                'planning_doc_id' => $doc->id,
                'version'         => $doc->version,
                'change_type'     => 'ai_integrate',
                'before_content'  => $beforeContent,
                'after_content'   => $json['integrated_content'],
                'summary'         => $json['summary'] ?? '웍스 통합 처리',
                'changed_by'      => auth()->id(),
                'approval_status' => 'pending',
            ]);

            // 처리된 입력 항목 상태 업데이트
            $pendingInputs->each(fn($i) => $i->update([
                'status'       => 'processed',
                'processed_at' => now(),
            ]));

            return response()->json([
                'ok'             => true,
                'pending_content' => $doc->pending_content,
                'summary'        => $doc->ai_summary,
                'conflicts'      => $doc->ai_conflicts,
                'suggestions'    => $doc->ai_suggestions,
            ]);

        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['error' => '웍스 처리 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // ── 승인 ────────────────────────────────────────────────────
    public function approve(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        if (!$doc->pending_content) {
            return response()->json(['error' => '검토할 내용이 없습니다.'], 422);
        }

        $doc->update([
            'content'         => $doc->pending_content,
            'pending_content' => null,
            'ai_summary'      => null,
            'ai_conflicts'    => null,
            'ai_suggestions'  => null,
            'version'         => $doc->version + 1,
            'status'          => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        // 최신 웍스 통합 이력을 승인으로 업데이트
        $doc->histories()
            ->where('change_type', 'ai_integrate')
            ->where('approval_status', 'pending')
            ->latest()
            ->first()
            ?->update(['approval_status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        PlanningDocHistory::create([
            'planning_doc_id' => $doc->id,
            'version'         => $doc->version,
            'change_type'     => 'approved',
            'summary'         => 'v' . $doc->version . ' 승인 완료',
            'changed_by'      => auth()->id(),
            'approval_status' => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        return response()->json(['ok' => true, 'version' => $doc->version]);
    }

    // ── 반려 ────────────────────────────────────────────────────
    public function reject(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $doc->update([
            'pending_content' => null,
            'ai_summary'      => null,
            'ai_conflicts'    => null,
            'ai_suggestions'  => null,
            'status'          => 'draft',
        ]);

        $doc->histories()
            ->where('change_type', 'ai_integrate')
            ->where('approval_status', 'pending')
            ->latest()
            ->first()
            ?->update(['approval_status' => 'rejected']);

        PlanningDocHistory::create([
            'planning_doc_id' => $doc->id,
            'version'         => $doc->version,
            'change_type'     => 'rejected',
            'summary'         => '웍스 통합 결과 반려',
            'changed_by'      => auth()->id(),
            'approval_status' => 'rejected',
        ]);

        return response()->json(['ok' => true]);
    }

    // ── 웍스 기획서 초안 작성 ──────────────────────────────────────
    public function aiWrite(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $request->validate([
            'prompt' => 'required|string|max:3000',
            'mode'   => 'nullable|in:new,enhance',
        ]);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey() && !$settings->manusKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 500);
        }

        $mode    = $request->input('mode', 'new');
        $prompt  = $request->input('prompt');
        $current = $doc->content ?: '';

        $systemPrompt = <<<'PROMPT'
당신은 IT/소프트웨어 프로젝트 기획서 작성 전문가입니다.
사용자의 입력을 바탕으로 아래 표준 기획서 구조에 맞춰 Markdown 형식으로 작성하거나 수정합니다.

[표준 기획서 구조]
# 프로젝트명

## 1. 프로젝트 개요
### 1.1 배경 및 목적
### 1.2 프로젝트 범위

## 2. 목표 및 성공 기준

## 3. 주요 기능
### 3.1 기능 목록
### 3.2 기능 상세

## 4. 비기능 요구사항
(성능, 보안, 확장성, 사용성 등)

## 5. 시스템 구성 / 기술 스택

## 6. 일정 계획
(단계별 마일스톤 및 기간)

## 7. 리스크 및 대응 방안

## 8. 기대 효과 / 성과 지표

작성 규칙:
- 위 구조를 기본으로 하되 프로젝트 특성에 맞게 섹션 추가·조정 가능
- 각 섹션은 구체적이고 실용적인 내용으로 충분히 서술
- Markdown 형식 사용 (# ## ### 헤딩, - 목록, **굵게**, 표, 코드블록 등)
- 모든 내용은 한국어로 작성

반드시 아래 JSON 형식으로만 응답 (다른 텍스트 없이):
{"content":"...마크다운 기획서 전문...","summary":"...한 줄 요약..."}
PROMPT;

        if ($mode === 'enhance' && $current) {
            $userMessage = "## 기존 기획서\n\n{$current}\n\n---\n\n## 수정/보완 요청\n\n{$prompt}\n\n기존 기획서를 표준 기획서 구조에 맞게 유지하면서 요청 내용을 반영하여 보완·수정된 기획서를 JSON으로 반환하세요.";
        } else {
            $userMessage = "다음 내용을 바탕으로 표준 프로젝트 기획서 구조에 맞춰 상세한 기획서를 Markdown으로 작성해주세요:\n\n{$prompt}\n\nJSON 형식으로 반환하세요.";
        }

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint(),
            );
            ['text' => $raw] = $orchestrator->chatRawLarge([['role' => 'user', 'content' => $userMessage]], $systemPrompt);
            $json = $this->extractJsonRobust($raw);

            if (!$json || !isset($json['content'])) {
                return response()->json(['error' => '웍스 응답 파싱 실패'], 500);
            }

            return response()->json([
                'ok'      => true,
                'content' => $json['content'],
                'summary' => $json['summary'] ?? '',
            ]);

        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['error' => '웍스 처리 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // ── 웍스 기획서 스트리밍 작성 (Haiku, Claude→OpenAI 폴백) ──────
    public function aiWriteStream(Request $request, Project $project, PlanningDoc $doc): StreamedResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'prompt' => 'required|string|max:3000',
            'mode'   => 'nullable|in:new,enhance',
        ]);

        $settings = AiSetting::current();
        $mode     = $request->input('mode', 'new');
        $prompt   = $request->input('prompt');
        $current  = $doc->content ?: '';

        $systemPrompt = <<<'PROMPT'
당신은 IT/소프트웨어 프로젝트 기획서 작성 전문가입니다.
사용자의 입력을 바탕으로 아래 표준 기획서 구조에 맞춰 Markdown 형식으로 작성하거나 수정합니다.

[표준 기획서 구조]
# 프로젝트명
## 1. 프로젝트 개요 (1.1 배경 및 목적 / 1.2 프로젝트 범위)
## 2. 목표 및 성공 기준
## 3. 주요 기능 (3.1 기능 목록 / 3.2 기능 상세)
## 4. 비기능 요구사항 (성능, 보안, 확장성, 사용성)
## 5. 시스템 구성 / 기술 스택
## 6. 일정 계획 (단계별 마일스톤)
## 7. 리스크 및 대응 방안
## 8. 기대 효과 / 성과 지표

규칙:
- 각 섹션을 구체적으로 서술하고 프로젝트 특성에 맞게 조정
- Markdown 형식 사용 (# ## ### 헤딩, - 목록, **굵게**, 표 등)
- 모든 내용은 한국어로 작성
- JSON 없이 순수 Markdown만 출력
PROMPT;

        if ($mode === 'enhance' && $current) {
            $userMessage = "## 기존 기획서\n\n{$current}\n\n---\n\n## 수정/보완 요청\n\n{$prompt}\n\n기존 기획서를 표준 구조에 맞게 유지하면서 요청 내용을 반영하여 수정·보완된 기획서를 Markdown으로 반환하세요.";
        } else {
            $userMessage = "다음 내용을 바탕으로 표준 프로젝트 기획서 구조에 맞춰 상세한 기획서를 Markdown으로 작성해주세요:\n\n{$prompt}";
        }

        $messages = [['role' => 'user', 'content' => $userMessage]];

        return response()->stream(function () use ($settings, $systemPrompt, $messages) {
            if (ob_get_level()) ob_end_clean();

            $send = function (array $data): void {
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) ob_flush();
                flush();
            };

            // 1차: Claude Haiku 스트리밍
            if ($settings->anthropicKey()) {
                try {
                    (new ClaudeService($settings->anthropicKey()))
                        ->streamRaw($systemPrompt, $messages, fn($chunk) => $send(['chunk' => $chunk]));
                    $send(['done' => true]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[aiWriteStream] Claude 실패, OpenAI 폴백: ' . $e->getMessage());
                }
            }

            // 2차: OpenAI 폴백 (non-streaming, 청크 분할 전송)
            if ($settings->openaiKey()) {
                try {
                    $text   = (new OpenAiService($settings->openaiKey()))->chatRaw($messages, $systemPrompt);
                    $pieces = mb_str_split($text, 30);
                    foreach ($pieces as $piece) {
                        $send(['chunk' => $piece]);
                    }
                    $send(['done' => true]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[aiWriteStream] OpenAI 폴백 실패, Manus 시도: ' . $e->getMessage());
                }
            }

            // 3차: Manus 폴백
            if ($settings->manusKey() && $settings->manusEndpoint()) {
                try {
                    $text   = (new \App\Services\ManusService($settings->manusKey(), $settings->manusEndpoint()))->chatRaw($messages, $systemPrompt);
                    $pieces = mb_str_split($text, 30);
                    foreach ($pieces as $piece) {
                        $send(['chunk' => $piece]);
                    }
                    $send(['done' => true]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[aiWriteStream] Manus 폴백 실패: ' . $e->getMessage());
                }
            }

            $send(['error' => '웍스 API 키가 설정되지 않았거나 오류가 발생했습니다.']);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // ── 웍스 마크다운 정리 ─────────────────────────────────────────
    public function aiCleanup(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $request->validate(['content' => 'required|string|max:50000']);
        $content = $request->input('content');

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey() && !$settings->manusKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 500);
        }

        $systemPrompt = <<<'PROMPT'
당신은 Markdown 기획서 정리 전문가입니다.
사용자가 제공하는 기획서 마크다운을 아래 규칙에 따라 정리하고 구조화합니다.

정리 규칙:
1. 헤딩 계층(#, ##, ###, ####)을 올바르게 정렬하고 일관성 유지 — 최상위 제목은 # 하나만 사용
2. 목록 기호(-, *, 숫자.)를 일관되게 통일 — 항목은 - 사용, 번호 목록은 1. 2. 3. 형식
3. **굵게**, *기울임*, `인라인코드` 등 인라인 서식의 짝이 맞지 않는 부분 수정
4. 헤딩 앞에는 빈 줄 2개, 일반 문단 사이 빈 줄 1개로 간격 통일
5. 표(table)가 있다면 헤더 구분선(| --- |)이 포함된 올바른 Markdown 표 형식으로 수정
6. 불필요하게 반복되는 내용이나 빈 줄 과다 제거
7. 섹션 내 내용을 논리적 순서로 재배치 (필요한 경우만)
8. 핵심 정보와 내용의 의미는 절대 삭제하거나 변경하지 않음
9. 한국어 유지

반드시 아래 JSON 형식으로만 응답하십시오 (다른 텍스트 없이):
{"content":"...정리된 마크다운 전문...","summary":"...변경 사항 한 줄 요약(한국어)..."}
PROMPT;

        $userMessage = "아래 기획서 마크다운을 규칙에 따라 정리해주세요:\n\n{$content}";

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint(),
            );
            ['text' => $raw] = $orchestrator->chatRaw([['role' => 'user', 'content' => $userMessage]], $systemPrompt);
            $json = $this->extractJsonRobust($raw);

            if (!$json || !isset($json['content'])) {
                return response()->json(['error' => '웍스 응답 파싱 실패'], 500);
            }

            return response()->json([
                'ok'      => true,
                'content' => $json['content'],
                'summary' => $json['summary'] ?? '마크다운 정리 완료',
            ]);

        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['error' => '웍스 처리 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // ── 이메일 발송 (기획서 첨부) ─────────────────────────────────
    public function sendEmail(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $request->validate([
            'recipients'   => 'required|array|min:1',
            'recipients.*' => 'required|email|max:255',
            'message'      => 'nullable|string|max:2000',
        ]);

        if (empty(trim($doc->content ?? ''))) {
            return response()->json(['ok' => false, 'message' => '기획서 내용이 없습니다.'], 422);
        }

        // Markdown → HTML 변환
        $htmlContent = \Illuminate\Support\Str::markdown($doc->content, [
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);

        // DomPDF로 PDF 생성
        $pdfHtml = view('emails.planning_pdf', [
            'doc'         => $doc,
            'project'     => $project,
            'htmlContent' => $htmlContent,
            'sentBy'      => auth()->user(),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($pdfHtml)
            ->setPaper('a4', 'portrait');

        $pdfBytes     = $pdf->output();
        $filename     = preg_replace('/[^\w\s가-힣-]/u', '', $doc->title) ?: 'planning';
        $filename     = $filename . '_v' . $doc->version . '.pdf';
        $extraMessage = trim($request->input('message', ''));

        foreach ($request->input('recipients') as $email) {
            Mail::to($email)->send(new PlanningContentMail(
                $doc->title,
                $extraMessage,
                'document',
                $project->name,
                $doc->title,
                $pdfBytes,
                $filename,
            ));
        }

        return response()->json(['ok' => true]);
    }

    // ── Word 다운로드 ───────────────────────────────────────────
    public function download(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $phpWord  = new PhpWord();
        $phpWord->setDefaultFontName('맑은 고딕');
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(0, ['name' => '맑은 고딕', 'size' => 20, 'bold' => true, 'color' => '312E81'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
        $phpWord->addTitleStyle(1, ['name' => '맑은 고딕', 'size' => 15, 'bold' => true, 'color' => '4F46E5'],
            ['spaceBefore' => 280, 'spaceAfter' => 120, 'borderBottomColor' => '4F46E5', 'borderBottomSize' => 6]);
        $phpWord->addTitleStyle(2, ['name' => '맑은 고딕', 'size' => 12, 'bold' => true, 'color' => '0891B2'],
            ['spaceBefore' => 200, 'spaceAfter' => 80]);
        $phpWord->addTitleStyle(3, ['name' => '맑은 고딕', 'size' => 11, 'bold' => true, 'color' => '374151'],
            ['spaceBefore' => 140, 'spaceAfter' => 60]);

        $phpWord->addNumberingStyle('numberedList', [
            'type'   => 'multilevel',
            'levels' => [
                ['pStyle' => 'List', 'format' => 'decimal', 'text' => '%1.', 'left' => 360, 'hanging' => 360, 'tabPos' => 360],
            ],
        ]);

        $section = $phpWord->addSection(['marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440]);

        // ── 푸터 (페이지 번호 + 문서명) ───────────────────────────
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'SupportWorks 기획서  |  ' . now()->format('Y.m.d') . '  |  {PAGE} / {NUMPAGES}',
            ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        // ── 헤더 블록 (이메일 PDF 인디고 헤더와 동일 구조) ──────────
        $phpWord->addTableStyle('_HeaderTable', [
            'borderSize'       => 0,
            'borderColor'      => '4F46E5',
            'cellMarginTop'    => 400,
            'cellMarginBottom' => 400,
            'cellMarginLeft'   => 540,
            'cellMarginRight'  => 540,
        ]);
        $headerTable = $section->addTable('_HeaderTable');
        $headerTable->addRow(1800);
        $headerCell = $headerTable->addCell(9360, ['bgColor' => '4F46E5', 'valign' => 'center']);
        $headerCell->addText('SupportWorks', [
            'name' => '맑은 고딕', 'size' => 9, 'color' => 'C7D2FE',
        ], ['spaceAfter' => 60]);
        $headerCell->addText($this->sanitizeXml($doc->title), [
            'name' => '맑은 고딕', 'size' => 20, 'bold' => true, 'color' => 'FFFFFF',
        ], ['spaceAfter' => 80]);
        $metaParts = array_filter([
            $project->name,
            'v' . $doc->version,
            $doc->status_label ?? null,
            now()->format('Y.m.d'),
        ]);
        $headerCell->addText($this->sanitizeXml(implode('  ·  ', $metaParts)), [
            'name' => '맑은 고딕', 'size' => 9, 'color' => 'C7D2FE',
        ]);

        $section->addTextBreak(1);

        // ── 콘텐츠 표 스타일 등록 ────────────────────────────────────
        $phpWord->addTableStyle('_PlanTable', [
            'borderSize'       => 8,
            'borderColor'      => 'BFDBFE',
            'cellMarginTop'    => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft'   => 100,
            'cellMarginRight'  => 100,
        ]);

        // ── 본문 내용 ─────────────────────────────────────────────
        $content = $doc->content ?? '';
        $lines   = array_map(fn($l) => $this->sanitizeXml(rtrim($l)), explode("\n", $content));
        $n = count($lines);
        $i = 0;

        while ($i < $n) {
            $line = $lines[$i];

            // 표 블록: 연속된 | 행을 모아 Word 테이블로 렌더링
            if (str_starts_with($line, '|')) {
                $tableLines = [];
                while ($i < $n && str_starts_with($lines[$i], '|')) {
                    $tableLines[] = $lines[$i];
                    $i++;
                }
                $this->renderWordTable($section, $tableLines);
                continue;
            }

            if (str_starts_with($line, '#### ')) {
                $section->addTitle(substr($line, 5), 4);
            } elseif (str_starts_with($line, '### ')) {
                $section->addTitle(substr($line, 4), 3);
            } elseif (str_starts_with($line, '## ')) {
                $section->addTitle(substr($line, 3), 2);
            } elseif (str_starts_with($line, '# ')) {
                $section->addTitle(substr($line, 2), 1);
            } elseif (str_starts_with($line, '- ') || str_starts_with($line, '* ')) {
                $textRun = $section->addListItemRun(0);
                $this->addInlineMarkdown($textRun, substr($line, 2));
            } elseif (preg_match('/^\d+\. (.+)$/', $line, $m)) {
                $textRun = $section->addListItemRun(0, 'numberedList');
                $this->addInlineMarkdown($textRun, $m[1]);
            } elseif (preg_match('/^-{3,}$/', $line)) {
                // 수평선: 빈 줄로 처리
            } elseif ($line === '') {
                $section->addTextBreak(1);
            } else {
                $textRun = $section->addTextRun(['spaceAfter' => 60, 'lineHeight' => 1.5]);
                $this->addInlineMarkdown($textRun, $line);
            }

            $i++;
        }

        $safeName = preg_replace('/[^\w\s가-힣-]/u', '', $doc->title) ?: 'planning';
        $fileName  = $safeName . '_v' . $doc->version . '.docx';
        $tmpPath   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'plan_' . uniqid() . '.docx';

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    private function renderWordTable(object $section, array $lines): void
    {
        $contentWidth = 9360; // A4 - 좌우 마진 (twips)

        $rows = [];
        foreach ($lines as $line) {
            // 구분행(| --- | --- |) 건너뜀
            if (preg_match('/^\|[-|: ]+\|$/', $line)) continue;
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (count(array_filter($cells, fn($c) => $c !== '')) > 0) {
                $rows[] = $cells;
            }
        }

        if (empty($rows)) return;

        $colCount = max(array_map('count', $rows));
        if ($colCount === 0) return;
        $colWidth = (int)($contentWidth / $colCount);

        $table = $section->addTable('_PlanTable');

        foreach ($rows as $ri => $cells) {
            $isHeader = $ri === 0;
            $table->addRow(400);
            for ($ci = 0; $ci < $colCount; $ci++) {
                $cellText  = $this->sanitizeXml($cells[$ci] ?? '');
                $cellStyle = $isHeader
                    ? ['bgColor' => 'EFF6FF', 'borderSize' => 8, 'borderColor' => 'BFDBFE']
                    : ['bgColor' => ($ri % 2 === 0) ? 'F8FAFC' : 'FFFFFF', 'borderSize' => 8, 'borderColor' => 'F1F5F9'];
                $wcell     = $table->addCell($colWidth, $cellStyle);
                $fontStyle = $isHeader
                    ? ['name' => '맑은 고딕', 'size' => 10, 'bold' => true, 'color' => '1E40AF']
                    : ['name' => '맑은 고딕', 'size' => 10, 'color' => '374151'];
                $wcell->addText($cellText, $fontStyle, ['spaceAfter' => 0]);
            }
        }

        $section->addTextBreak(1);
    }

    private function sanitizeXml(string $text): string
    {
        // XML 1.0에서 허용되지 않는 제어문자 제거 (탭·줄바꿈 제외)
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
    }

    private function addInlineMarkdown(object $run, string $text): void
    {
        $text  = $this->sanitizeXml($text);
        $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $base  = ['name' => '맑은 고딕', 'size' => 11, 'color' => '1F2937'];

        foreach ($parts as $part) {
            if (preg_match('/^\*\*(.+)\*\*$/s', $part, $m)) {
                $run->addText($m[1], array_merge($base, ['bold' => true]));
            } elseif (preg_match('/^\*(.+)\*$/s', $part, $m)) {
                $run->addText($m[1], array_merge($base, ['italic' => true]));
            } elseif (preg_match('/^`(.+)`$/s', $part, $m)) {
                $run->addText($m[1], array_merge($base, ['name' => 'Courier New', 'color' => '0369A1']));
            } else {
                $run->addText($part, $base);
            }
        }
    }

    // ── 웍스 기능 추천 생성 ─────────────────────────────────────────
    public function suggestFeatures(Request $request, Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        $maxSuggestions = 5;

        $existing = ProjectFeatureSuggestion::where('project_id', $project->id)->get();
        $remaining = $maxSuggestions - $existing->count();

        if ($remaining <= 0) {
            return response()->json(['error' => '추천 기능이 최대 5개에 도달했습니다. 일부를 삭제 후 다시 시도해 주세요.'], 422);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey() && !$settings->manusKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 500);
        }

        $requestCount = min(5, $remaining);
        $docContent   = $doc->content ?: '(기획서 내용 없음)';
        $existingTitles = $existing->pluck('title')->implode(', ');

        $systemPrompt = <<<'PROMPT'
당신은 소프트웨어 프로젝트 기획 전문가입니다.
주어진 기획서 내용을 분석하여 해당 프로젝트에 꼭 필요하지만 놓치기 쉬운 시스템 기능을 추천합니다.

추천 원칙:
- 이 프로젝트의 특성에 맞게 독특하고 구체적인 기능을 추천합니다
- 일반적인 기능보다 이 프로젝트에서 특히 가치 있을 기능을 우선합니다
- 기술적으로 실현 가능하고 실용적인 기능이어야 합니다
- 이미 기획서에 명시된 기능은 추천하지 않습니다
- 이미 추천된 기능과 중복되지 않아야 합니다

반드시 아래 JSON 형식으로만 응답하십시오 (다른 텍스트 없이):
{"suggestions":[{"title":"...","description":"...","reason":"..."}]}
PROMPT;

        $alreadyMsg = $existingTitles ? "\n\n이미 추천된 기능(중복 금지): {$existingTitles}" : '';
        $userMessage = "## 프로젝트명\n{$project->name}\n\n## 기획서 내용\n\n{$docContent}{$alreadyMsg}\n\n위 기획서를 분석하여 이 프로젝트에 꼭 필요하지만 놓치기 쉬운 시스템 기능을 정확히 {$requestCount}개 추천해 주세요. JSON으로 반환하세요.";

        try {
            $orchestrator = new AiOrchestrator(
                $settings->anthropicKey(),
                $settings->openaiKey(),
                $settings->manusKey(),
                $settings->manusEndpoint(),
            );
            ['text' => $raw] = $orchestrator->chatRaw([['role' => 'user', 'content' => $userMessage]], $systemPrompt);
            $json = $this->extractJsonRobust($raw);

            if (!$json || !isset($json['suggestions']) || !is_array($json['suggestions'])) {
                return response()->json(['error' => '웍스 응답 파싱 실패'], 500);
            }

            $newItems = [];
            foreach ($json['suggestions'] as $s) {
                if (empty($s['title'])) continue;
                $row = ProjectFeatureSuggestion::create([
                    'project_id'  => $project->id,
                    'title'       => $s['title'],
                    'description' => $s['description'] ?? '',
                    'reason'      => $s['reason'] ?? null,
                    'created_by'  => auth()->id(),
                ]);
                $newItems[] = [
                    'id'          => $row->id,
                    'title'       => $row->title,
                    'description' => $row->description,
                    'reason'      => $row->reason,
                    'created_at'  => $row->created_at->format('m.d H:i'),
                ];
            }

            $total = ProjectFeatureSuggestion::where('project_id', $project->id)->count();

            return response()->json([
                'ok'       => true,
                'new'      => $newItems,
                'total'    => $total,
                'max'      => $maxSuggestions,
            ]);

        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['error' => '웍스 처리 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // ── 웍스 기능 추천 스트리밍 (Haiku, Claude→OpenAI 폴백) ─────────
    public function suggestFeaturesStream(Request $request, Project $project, PlanningDoc $doc): StreamedResponse
    {
        $this->authorizeProject($project);

        // Active count (excludes soft-deleted)
        $activeCount = ProjectFeatureSuggestion::where('project_id', $project->id)->count();
        $remaining   = 5 - $activeCount;

        if ($remaining <= 0) {
            return response()->stream(function () {
                if (ob_get_level()) ob_end_clean();
                echo 'data: ' . json_encode(['error' => '추천 기능이 최대 5개에 도달했습니다. 일부를 취소 후 다시 시도해 주세요.']) . "\n\n";
                flush();
            }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
        }

        $settings     = AiSetting::current();
        $requestCount = min(5, $remaining);
        $docContent   = $doc->content ?: '(기획서 내용 없음)';

        // Include ALL past titles (even cancelled) so 웍스 won't re-suggest them
        $existingTitles = ProjectFeatureSuggestion::withTrashed()
            ->where('project_id', $project->id)
            ->pluck('title')
            ->implode(', ');

        $systemPrompt = <<<'PROMPT'
당신은 소프트웨어 프로젝트 기획 전문가입니다.
기획서 내용을 분석하여 꼭 필요하지만 놓치기 쉬운 시스템 기능을 추천합니다.

추천 원칙:
- 이 프로젝트 특성에 맞게 구체적이고 가치 있는 기능 추천
- 기술적으로 실현 가능하고 실용적인 기능
- 기획서에 이미 있는 기능 또는 이미 추천된 기능과 중복 금지

각 추천 기능을 아래 형식의 JSON 객체로 출력하세요:
{"title":"기능명","description":"기능 설명 (2-3문장)","reason":"추천 이유"}

각 JSON 객체를 순서대로 출력하고 다른 텍스트는 넣지 마세요.
PROMPT;

        $alreadyMsg  = $existingTitles ? "\n\n이미 추천된 기능(중복 금지): {$existingTitles}" : '';
        $userMessage = "## 프로젝트명\n{$project->name}\n\n## 기획서 내용\n\n{$docContent}{$alreadyMsg}\n\n위 기획서를 분석하여 꼭 필요하지만 놓치기 쉬운 기능을 정확히 {$requestCount}개 추천해주세요.";
        $messages    = [['role' => 'user', 'content' => $userMessage]];

        return response()->stream(function () use ($settings, $systemPrompt, $messages, $project) {
            if (ob_get_level()) ob_end_clean();

            $send = function (array $data): void {
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) ob_flush();
                flush();
            };

            $savedTitles = [];
            $tryParseObj = function (string $json) use ($project, $send, &$savedTitles): void {
                $data = json_decode($json, true);
                if (!$data || empty($data['title'])) return;
                if (in_array($data['title'], $savedTitles, true)) return;
                $savedTitles[] = $data['title'];
                try {
                    $row = ProjectFeatureSuggestion::create([
                        'project_id'  => $project->id,
                        'title'       => $data['title'],
                        'description' => $data['description'] ?? '',
                        'reason'      => $data['reason'] ?? null,
                        'created_by'  => auth()->id(),
                    ]);
                    $send(['item' => [
                        'id'          => $row->id,
                        'title'       => $row->title,
                        'description' => $row->description,
                        'reason'      => $row->reason,
                        'created_at'  => $row->created_at->format('m.d H:i'),
                    ]]);
                } catch (\Throwable) {}
            };

            // Brace-depth tracker — works on both streamed chunks and full text
            $streamBuf = '';
            $depth     = 0;
            $inStr     = false;
            $esc       = false;

            $parseChunk = function (string $chunk) use (&$streamBuf, &$depth, &$inStr, &$esc, $tryParseObj): void {
                for ($i = 0, $len = strlen($chunk); $i < $len; $i++) {
                    $c = $chunk[$i];
                    if ($depth === 0 && $c !== '{') continue;
                    if ($esc)                    { $esc = false; $streamBuf .= $c; continue; }
                    if ($c === '\\' && $inStr)   { $esc = true;  $streamBuf .= $c; continue; }
                    if ($c === '"')              { $inStr = !$inStr; $streamBuf .= $c; continue; }
                    if ($inStr)                  { $streamBuf .= $c; continue; }
                    if ($c === '{')              { $depth++; $streamBuf .= $c; }
                    elseif ($c === '}')          {
                        $streamBuf .= $c;
                        $depth--;
                        if ($depth === 0) { $tryParseObj($streamBuf); $streamBuf = ''; }
                    } else                       { $streamBuf .= $c; }
                }
            };

            // 1차: Claude Haiku 스트리밍
            if ($settings->anthropicKey()) {
                try {
                    (new ClaudeService($settings->anthropicKey()))
                        ->streamRaw($systemPrompt, $messages, $parseChunk, 2000);
                    $send(['done' => true, 'total' => ProjectFeatureSuggestion::where('project_id', $project->id)->count()]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[suggestFeaturesStream] Claude 실패, OpenAI 폴백: ' . $e->getMessage());
                    // Reset parser state for fallback
                    $streamBuf = ''; $depth = 0; $inStr = false; $esc = false;
                }
            }

            // 2차: OpenAI 폴백
            if ($settings->openaiKey()) {
                try {
                    $raw = (new OpenAiService($settings->openaiKey()))->chatRaw($messages, $systemPrompt);
                    $parseChunk($raw);
                    $send(['done' => true, 'total' => ProjectFeatureSuggestion::where('project_id', $project->id)->count()]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[suggestFeaturesStream] OpenAI 폴백 실패, Manus 시도: ' . $e->getMessage());
                    $streamBuf = ''; $depth = 0; $inStr = false; $esc = false;
                }
            }

            // 3차: Manus 폴백
            if ($settings->manusKey() && $settings->manusEndpoint()) {
                try {
                    $raw = (new \App\Services\ManusService($settings->manusKey(), $settings->manusEndpoint()))->chatRaw($messages, $systemPrompt);
                    $parseChunk($raw);
                    $send(['done' => true, 'total' => ProjectFeatureSuggestion::where('project_id', $project->id)->count()]);
                    return;
                } catch (\Throwable $e) {
                    Log::warning('[suggestFeaturesStream] Manus 폴백 실패: ' . $e->getMessage());
                }
            }

            $send(['error' => '웍스 API 키가 설정되지 않았거나 오류가 발생했습니다.']);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // ── 기능 추천 기획서 반영 ─────────────────────────────────────
    public function applyFeatureSuggestion(Request $request, Project $project, PlanningDoc $doc, ProjectFeatureSuggestion $suggestion)
    {
        $this->authorizeProject($project);

        if ($suggestion->project_id !== $project->id) abort(403);

        $user  = auth()->user();
        $date  = now()->format('Y-m-d');
        $now   = now();

        // Build the block content (without leading separator — stored for later removal)
        $blockContent = "### 기능: {$suggestion->title}\n\n{$suggestion->description}";
        if ($suggestion->reason) {
            $blockContent .= "\n\n> **추천 이유:** {$suggestion->reason}";
        }
        $blockContent .= "\n\n*웍스 기능 추천 반영 — {$user->name} · {$date}*";

        $newContent = ($doc->content ?? '') . "\n\n---\n\n" . $blockContent;
        $doc->update(['content' => $newContent]);

        PlanningDocHistory::create([
            'planning_doc_id' => $doc->id,
            'version'         => $doc->version,
            'change_type'     => 'user_edit',
            'before_content'  => $doc->getOriginal('content'),
            'after_content'   => $newContent,
            'summary'         => "웍스 추천 기능 반영: {$suggestion->title}",
            'changed_by'      => $user->id,
            'approval_status' => 'approved',
        ]);

        // 요구사항 자동 생성 (아직 연결 안 된 경우만)
        $requirement = null;
        if (!$suggestion->requirement_id) {
            $requirement = $project->requirements()->create([
                'title'           => $suggestion->title,
                'description'     => $suggestion->description,
                'status'          => 'draft',
                'priority'        => 'medium',
                'category'        => 'functional',
                'source_type'     => 'ai_analyzed',
                'source_ref'      => '웍스 기능 추천',
                'reporter_id'     => $user->id,
                'applied_to_plan'    => true,
                'applied_to_plan_at' => $now,
                'applied_to_plan_id' => $doc->id,
            ]);
        }

        $suggestion->update([
            'is_applied'       => true,
            'applied_at'       => $now,
            'requirement_id'   => $requirement?->id ?? $suggestion->requirement_id,
            'planning_doc_id'  => $doc->id,
            'inserted_markdown'=> $blockContent,
        ]);

        return response()->json(['ok' => true, 'content' => $newContent]);
    }

    // ── 기능 추천 삭제 / 기획서 반영 취소 ──────────────────────────
    public function deleteFeatureSuggestion(Request $request, Project $project, ProjectFeatureSuggestion $suggestion)
    {
        $this->authorizeProject($project);

        if ($suggestion->project_id !== $project->id) abort(403);

        // 진행중이거나 완료된 일정 Task가 있으면 차단
        if ($suggestion->requirement_id) {
            $blocked = SubTask::where('requirement_id', $suggestion->requirement_id)
                ->whereIn('status', ['in_progress', 'done'])
                ->exists();
            if ($blocked) {
                return response()->json([
                    'ok'    => false,
                    'error' => '진행중이거나 완료된 일정 Task가 있어 반영을 취소할 수 없습니다.',
                ], 422);
            }
        }

        // 기획서에 반영된 경우 → 내용 제거
        $updatedContent = null;
        if ($suggestion->is_applied && $suggestion->inserted_markdown && $suggestion->planning_doc_id) {
            $doc = PlanningDoc::find($suggestion->planning_doc_id);
            if ($doc) {
                $this->removeBlockFromDoc($doc, $suggestion->inserted_markdown);
                $updatedContent = $doc->fresh()->content;
            }
        }

        // 연결된 요구사항 삭제 (미시작 SubTask 포함)
        if ($suggestion->requirement_id) {
            $req = Requirement::find($suggestion->requirement_id);
            if ($req) {
                SubTask::where('requirement_id', $req->id)
                    ->where('status', 'not_started')
                    ->delete();
                $req->delete();
            }
        }

        // Soft-delete: keep in history, only status changes
        $suggestion->update([
            'is_applied'        => false,
            'applied_at'        => null,
            'requirement_id'    => null,
            'planning_doc_id'   => null,
            'inserted_markdown' => null,
        ]);
        $suggestion->delete();

        return response()->json(['ok' => true, 'content' => $updatedContent]);
    }

    private function removeBlockFromDoc(PlanningDoc $doc, string $block): void
    {
        $content = $doc->content ?? '';
        if (!str_contains($content, $block)) return;

        if (str_contains($content, $block . "\n\n---\n\n")) {
            $newContent = str_replace($block . "\n\n---\n\n", '', $content);
        } elseif (str_contains($content, "\n\n---\n\n" . $block)) {
            $newContent = str_replace("\n\n---\n\n" . $block, '', $content);
        } elseif (str_contains($content, "\n\n" . $block)) {
            $newContent = str_replace("\n\n" . $block, '', $content);
        } else {
            $newContent = str_replace($block, '', $content);
        }

        $doc->update(['content' => rtrim($newContent)]);
    }

    // ── 외부 공유 링크 토글 ─────────────────────────────────────────
    public function toggleShare(Project $project, PlanningDoc $doc)
    {
        $this->authorizeProject($project);

        if ($doc->share_token) {
            $doc->update(['share_token' => null]);
            return response()->json(['ok' => true, 'active' => false]);
        }

        $token = \Illuminate\Support\Str::random(48);
        $doc->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'active' => true,
            'token'  => $token,
            'url'    => route('planning.public-share', $token),
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }

    // 코드블록 제거, 중괄호 경계 탐색으로 JSON 안정 추출
    private function extractJsonRobust(string $raw): ?array
    {
        // 1. 직접 파싱
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;

        // 2. 마크다운 코드블록(```json ... ```) 제거 후 재시도
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $cleaned = preg_replace('/\s*```\s*$/i', '', $cleaned);
        $json = json_decode(trim($cleaned), true);
        if (is_array($json)) return $json;

        // 3. 첫 번째 { ~ 마지막 } 구간 추출 후 재시도
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = json_decode(substr($raw, $start, $end - $start + 1), true);
            if (is_array($json)) return $json;
        }

        return null;
    }
}
