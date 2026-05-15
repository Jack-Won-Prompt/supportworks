<?php

namespace App\Http\Controllers;

use App\Mail\DeliverableApprovalMail;
use App\Mail\DeliverableApprovalRespondMail;
use App\Models\Agent\Deliverable;
use App\Models\Agent\DeliverableApproval;
use App\Models\Agent\DeliverableComment;
use App\Models\Agent\DeliverableFileRegistration;
use App\Models\Agent\DeliverableStepData;
use App\Models\Agent\DeliverableStepVersion;
use App\Models\Agent\DeliverableToolResult;
use App\Models\FileComment;
use App\Models\FileVersion;
use App\Models\ProjectFile;
use App\Models\AiSetting;
use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\AiOrchestrator;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class DeliverableController extends Controller
{
    private array $config;

    public function __construct()
    {
        $this->config = config('deliverables');
    }

    // 대시보드 (카드/리스트 뷰)
    public function index(Project $project, Request $request): View
    {
        $this->authorizeProject($project);

        $deliverables = Deliverable::where('project_id', $project->id)
            ->get()
            ->keyBy('type_id');

        $types        = $this->config['deliverables'];
        $categories   = $this->config['categories'];
        $view         = $request->input('view', 'card'); // card | list

        // 필터
        $filterCategory       = $request->input('category');
        $filterResponsibility = $request->input('responsibility');
        $filterStatus         = $request->input('status');

        // 통계
        $total     = count($types);
        $completed = $deliverables->where('status', 'completed')->count();
        $inProg    = $deliverables->where('status', 'in_progress')->count();
        $notStart  = $total - $completed - $inProg;

        return view('ai-agent.deliverables.index', [
            'project'              => $project,
            'types'                => $types,
            'categories'           => $categories,
            'deliverables'         => $deliverables,
            'view'                 => $view,
            'filterCategory'       => $filterCategory,
            'filterResponsibility' => $filterResponsibility,
            'filterStatus'         => $filterStatus,
            'stats'                => compact('total', 'completed', 'inProg', 'notStart'),
        ]);
    }

    // 산출물 작성 화면 (3분할)
    public function show(Project $project, string $typeId, Request $request): View
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404, "알 수 없는 산출물 유형: {$typeId}");

        $deliverable = Deliverable::firstOrCreate(
            ['project_id' => $project->id, 'type_id' => $typeId],
            [
                'current_step'   => 1,
                'status'         => 'not_started',
                'responsibility' => $typeDef['responsibility'],
                'created_by'     => Auth::id(),
            ]
        );

        $stepNo = (int) $request->input('step', $deliverable->current_step);
        $stepNo = max(1, min($stepNo, count($typeDef['steps'])));

        $deliverable->load(['stepData', 'toolResults']);

        // 현재 단계 정의
        $currentStep = collect($typeDef['steps'])->firstWhere('order', $stepNo);

        // 프로젝트 멤버 (승인 요청 대상)
        $projectMembers = \App\Models\User::whereIn(
            'id',
            \App\Models\ProjectMember::where('project_id', $project->id)->pluck('user_id')
        )->get(['id', 'name', 'email']);

        // 현재 단계 승인 레코드
        $stepApproval = $deliverable->id
            ? \App\Models\Agent\DeliverableApproval::where('deliverable_id', $deliverable->id)
                ->where('step_order', $stepNo)
                ->latest()
                ->with(['requester:id,name', 'approver:id,name'])
                ->first()
            : null;

        return view('ai-agent.deliverables.show', [
            'project'        => $project,
            'typeDef'        => $typeDef,
            'typeId'         => $typeId,
            'deliverable'    => $deliverable,
            'currentStep'    => $currentStep,
            'stepNo'         => $stepNo,
            'tools'          => $this->config['tools'],
            'categories'     => $this->config['categories'],
            'allTypes'       => $this->config['deliverables'],
            'projectMembers' => $projectMembers,
            'stepApproval'   => $stepApproval,
        ]);
    }

    // 단계 데이터 저장
    public function saveStep(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $deliverable = Deliverable::firstOrCreate(
            ['project_id' => $project->id, 'type_id' => $typeId],
            ['current_step' => 1, 'status' => 'not_started', 'responsibility' => $typeDef['responsibility'], 'created_by' => Auth::id()]
        );

        $stepNo      = (int) $request->input('step', 1);
        $fields      = $request->input('fields', []);
        $versionMode = (string) $request->input('version_mode', 'auto'); // new | overwrite | auto | none
        $changeNote  = (string) ($request->input('change_note') ?? '');

        // 작업본 (deliverable_step_data) 갱신
        foreach ($fields as $key => $value) {
            DeliverableStepData::updateOrCreate(
                ['deliverable_id' => $deliverable->id, 'step_order' => $stepNo, 'field_key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // 버전 스냅샷 처리
        $versionInfo = $this->applyStepVersion($deliverable, $stepNo, $versionMode, $changeNote);

        // 진행 상태 갱신
        $totalSteps = count($typeDef['steps']);
        if ($deliverable->status === 'not_started') {
            $deliverable->status = 'in_progress';
        }
        if ($stepNo >= $totalSteps && $request->boolean('complete')) {
            $deliverable->status       = 'completed';
            $deliverable->current_step = $totalSteps;
        } elseif ($stepNo > $deliverable->current_step) {
            $deliverable->current_step = $stepNo;
        }
        $deliverable->save();

        return response()->json([
            'ok'      => true,
            'step'    => $stepNo,
            'status'  => $deliverable->status,
            'version' => $versionInfo,
        ]);
    }

    // STEP 작업본을 스냅샷으로 묶어 버전 레코드로 저장
    // mode: new(차상위 version_no 추가) | overwrite(최신 버전 갱신) | auto(첫 저장이면 v1, 이후엔 작업본만) | none
    private function applyStepVersion(Deliverable $deliverable, int $stepNo, string $mode, string $changeNote): ?array
    {
        if ($mode === 'none') return null;

        $deliverable->load(['stepData', 'toolResults']);

        $fieldsSnap = [];
        foreach ($deliverable->stepData->where('step_order', $stepNo) as $row) {
            $fieldsSnap[$row->field_key] = [
                'value'    => $row->value,
                'en_value' => $row->en_value ?? '',
                'en_hash'  => $row->en_hash ?? '',
            ];
        }
        $toolsSnap = [];
        foreach ($deliverable->toolResults->where('step_order', $stepNo) as $row) {
            $toolsSnap[$row->tool_id] = json_decode($row->result ?? 'null', true);
        }

        if ($mode === 'auto') {
            // 해당 STEP에 버전이 하나도 없을 때만 v1 자동 생성
            $exists = DeliverableStepVersion::where('deliverable_id', $deliverable->id)
                ->where('step_order', $stepNo)
                ->exists();
            if ($exists) return null;
            $mode = 'new';
            if ($changeNote === '') $changeNote = '최초 저장';
        }

        if ($mode === 'overwrite') {
            $latest = $deliverable->latestStepVersion($stepNo);
            if (!$latest) {
                $mode = 'new';
            } else {
                $latest->update([
                    'snapshot_fields' => json_encode($fieldsSnap, JSON_UNESCAPED_UNICODE),
                    'snapshot_tools'  => json_encode($toolsSnap, JSON_UNESCAPED_UNICODE),
                    'change_note'     => $changeNote !== '' ? $changeNote : $latest->change_note,
                    'created_by'      => Auth::id(),
                ]);
                return ['mode' => 'overwrite', 'version_no' => $latest->version_no, 'id' => $latest->id];
            }
        }

        if ($mode === 'new') {
            $versionNo = $deliverable->nextStepVersionNo($stepNo);
            $row = DeliverableStepVersion::create([
                'deliverable_id'  => $deliverable->id,
                'step_order'      => $stepNo,
                'version_no'      => $versionNo,
                'snapshot_fields' => json_encode($fieldsSnap, JSON_UNESCAPED_UNICODE),
                'snapshot_tools'  => json_encode($toolsSnap, JSON_UNESCAPED_UNICODE),
                'change_note'     => $changeNote,
                'created_by'      => Auth::id(),
            ]);
            return ['mode' => 'new', 'version_no' => $versionNo, 'id' => $row->id];
        }

        return null;
    }

    // ── STEP 버전 이력 ─────────────────────────────────────────
    public function versionIndex(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->first();
        if (!$deliverable) return response()->json(['ok' => true, 'versions' => []]);

        $stepNo = (int) $request->input('step', 0);
        $query  = DeliverableStepVersion::where('deliverable_id', $deliverable->id);
        if ($stepNo > 0) $query->where('step_order', $stepNo);

        $rows = $query->with('creator:id,name')
            ->orderBy('step_order')
            ->orderByDesc('version_no')
            ->get()
            ->map(fn ($v) => [
                'id'          => $v->id,
                'step_order'  => $v->step_order,
                'version_no'  => $v->version_no,
                'change_note' => $v->change_note,
                'creator'     => $v->creator?->name,
                'created_at'  => $v->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['ok' => true, 'versions' => $rows]);
    }

    public function versionShow(Project $project, string $typeId, int $versionId): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        $version = DeliverableStepVersion::where('deliverable_id', $deliverable->id)
            ->where('id', $versionId)
            ->firstOrFail();

        return response()->json([
            'ok'         => true,
            'id'         => $version->id,
            'step_order' => $version->step_order,
            'version_no' => $version->version_no,
            'change_note'=> $version->change_note,
            'fields'     => $version->fieldsArray(),
            'tools'      => $version->toolsArray(),
            'created_at' => $version->created_at?->format('Y-m-d H:i'),
        ]);
    }

    public function versionRestore(Project $project, string $typeId, int $versionId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        $version = DeliverableStepVersion::where('deliverable_id', $deliverable->id)
            ->where('id', $versionId)
            ->firstOrFail();

        $stepNo = $version->step_order;
        $fields = $version->fieldsArray();
        $tools  = $version->toolsArray();

        // 작업본을 해당 버전 내용으로 덮어쓰기
        DeliverableStepData::where('deliverable_id', $deliverable->id)
            ->where('step_order', $stepNo)
            ->delete();
        foreach ($fields as $key => $payload) {
            DeliverableStepData::create([
                'deliverable_id' => $deliverable->id,
                'step_order'     => $stepNo,
                'field_key'      => $key,
                'value'          => is_array($payload) ? ($payload['value'] ?? null) : $payload,
                'en_value'       => is_array($payload) ? ($payload['en_value'] ?? null) : null,
                'en_hash'        => is_array($payload) ? ($payload['en_hash'] ?? null) : null,
            ]);
        }

        DeliverableToolResult::where('deliverable_id', $deliverable->id)
            ->where('step_order', $stepNo)
            ->delete();
        foreach ($tools as $toolId => $result) {
            DeliverableToolResult::create([
                'deliverable_id' => $deliverable->id,
                'step_order'     => $stepNo,
                'tool_id'        => $toolId,
                'result'         => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
        }

        // 복원 결과를 새 버전으로 기록 (이력 보존)
        $newVersionNo = $deliverable->nextStepVersionNo($stepNo);
        $note         = sprintf('v%d 에서 복원', $version->version_no);
        DeliverableStepVersion::create([
            'deliverable_id'  => $deliverable->id,
            'step_order'      => $stepNo,
            'version_no'      => $newVersionNo,
            'snapshot_fields' => $version->snapshot_fields,
            'snapshot_tools'  => $version->snapshot_tools,
            'change_note'     => $note,
            'created_by'      => Auth::id(),
        ]);

        return response()->json([
            'ok'         => true,
            'step_order' => $stepNo,
            'version_no' => $newVersionNo,
            'message'    => "{$stepNo}단계를 v{$version->version_no} 내용으로 복원하고 v{$newVersionNo} 으로 기록했습니다.",
        ]);
    }

    // 번역 캐시 저장
    public function saveTranslation(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where(['project_id' => $project->id, 'type_id' => $typeId])->first();
        if (!$deliverable) return response()->json(['ok' => false], 404);

        $stepNo   = (int) $request->input('step');
        $fieldKey = $request->input('field_key');
        $enValue  = $request->input('en_value', '');

        $row = DeliverableStepData::where([
            'deliverable_id' => $deliverable->id,
            'step_order'     => $stepNo,
            'field_key'      => $fieldKey,
        ])->first();

        if (!$row) return response()->json(['ok' => false, 'error' => 'field not found'], 404);

        $row->update([
            'en_value' => $enValue,
            'en_hash'  => md5($row->value ?? ''),
        ]);

        return response()->json(['ok' => true]);
    }

    // 전체 단계 필드 목록 (번역 일괄 처리용)
    public function allStepFields(Project $project, string $typeId): JsonResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->with('stepData')
            ->first();

        if (!$deliverable) {
            return response()->json(['ok' => true, 'fields' => []]);
        }

        $fields = [];
        foreach ($typeDef['steps'] as $step) {
            foreach ($step['fields'] ?? [] as $field) {
                if (!in_array($field['type'], ['textarea', 'table', 'input'])) continue;
                $value = $deliverable->getStepValue($step['order'], $field['key']);
                if (!$value || trim((string) $value) === '') continue;

                $enData   = $deliverable->getStepEnData($step['order'], $field['key']);
                $fields[] = [
                    'step'      => $step['order'],
                    'field_key' => $field['key'],
                    'label'     => $field['label'],
                    'value'     => (string) $value,
                    'has_en'    => $enData['valid'],
                    'en_value'  => $enData['valid'] ? $enData['en_value'] : '',
                ];
            }
        }

        return response()->json(['ok' => true, 'fields' => $fields]);
    }

    // 도구 결과 저장
    public function saveTool(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $deliverable = Deliverable::where('project_id', $project->id)->where('type_id', $typeId)->firstOrFail();

        DeliverableToolResult::updateOrCreate(
            [
                'deliverable_id' => $deliverable->id,
                'step_order'     => $request->input('step'),
                'tool_id'        => $request->input('tool_id'),
            ],
            ['result' => json_encode($request->input('result'))]
        );

        return response()->json(['ok' => true]);
    }

    // 웍스 초안 생성
    public function generateDraft(Project $project, string $typeId, Request $request): JsonResponse
    {
        set_time_limit(300);

        try {
            $this->authorizeProject($project);

            $typeDef = $this->config['deliverables'][$typeId] ?? null;
            if (!$typeDef) {
                return response()->json(['error' => '산출물 유형을 찾을 수 없습니다.'], 404);
            }

            $stepNo      = (int) $request->input('step', 1);
            $currentStep = collect($typeDef['steps'])->firstWhere('order', $stepNo);
            if (!$currentStep) {
                return response()->json(['error' => '단계 정의를 찾을 수 없습니다.'], 422);
            }

            // 텍스트 입력 필드만 초안 생성 대상 (diagram/review 제외)
            $draftableFields = collect($currentStep['fields'])
                ->filter(fn($f) => in_array($f['type'], ['textarea', 'table', 'input']))
                ->values();

            if ($draftableFields->isEmpty()) {
                return response()->json(['error' => '이 단계는 웍스 초안 생성을 지원하지 않습니다.'], 422);
            }

            $aiSetting = AiSetting::current();
            if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey()) {
                return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 503);
            }

            // 기획서
            $planningDoc = PlanningDoc::where('project_id', $project->id)
                ->orderByDesc('created_at')->first();

            // 이전 단계 데이터 (컨텍스트)
            $deliverable = Deliverable::where('project_id', $project->id)
                ->where('type_id', $typeId)
                ->with('stepData')
                ->first();

            $prevContext = '';
            if ($deliverable) {
                $prevData = $deliverable->stepData->where('step_order', '<', $stepNo);
                foreach ($prevData->groupBy('step_order') as $sOrder => $items) {
                    $stepDef = collect($typeDef['steps'])->firstWhere('order', (int) $sOrder);
                    $prevContext .= "\n### {$sOrder}단계: " . ($stepDef['title'] ?? '') . "\n";
                    foreach ($items as $item) {
                        $fieldDef   = collect($stepDef['fields'] ?? [])->firstWhere('key', $item->field_key);
                        $fieldLabel = $fieldDef['label'] ?? $item->field_key;
                        $prevContext .= "- {$fieldLabel}: " . mb_substr($item->value, 0, 500) . "\n";
                    }
                }
            }

            // Tool schema: 초안 필드들
            $schemaProps = [];
            $required    = [];
            foreach ($draftableFields as $field) {
                $schemaProps[$field['key']] = [
                    'type'        => 'string',
                    'description' => $field['label'] . ' 항목의 초안 내용',
                ];
                $required[] = $field['key'];
            }

            $fieldSchema = [
                'type'       => 'object',
                'properties' => $schemaProps,
                'required'   => $required,
            ];

            // 시스템 프롬프트
            $docContent   = $planningDoc ? mb_substr(strip_tags($planningDoc->content ?? ''), 0, 3000) : null;
            $systemPrompt = implode("\n\n", array_filter([
                "당신은 IT 프로젝트 문서 전문가입니다. {$typeDef['name']}({$typeDef['shortName']}) 산출물을 작성하는 역할입니다.",
                "## 프로젝트 정보\n- 이름: {$project->name}\n- 설명: " . ($project->description ?? '없음'),
                $docContent ? "## 프로젝트 기획서\n{$docContent}" : null,
                $prevContext ? "## 이 산출물의 이전 단계 입력 내용{$prevContext}" : null,
                "## 작성 지침\n- 위 정보를 최대한 활용해 구체적이고 실무적인 초안을 작성하세요.\n- 누락된 정보는 프로젝트 성격에 맞게 합리적으로 추론하세요.\n- 한국어로 작성하세요.",
            ]));

            // 사용자 프롬프트
            $userPrompt = "{$stepNo}단계 '{$currentStep['title']}'({$currentStep['description']}) 항목의 초안을 작성해주세요. 각 필드를 채워주세요.";

            $orchestrator = new AiOrchestrator(
                $aiSetting->anthropicKey(),
                $aiSetting->openaiKey(),
            );
            $result = $orchestrator->generateDraft($systemPrompt, $userPrompt, $fieldSchema);

            return response()->json([
                'ok'       => true,
                'fields'   => $result['fields'],
                'step'     => $stepNo,
                'provider' => $result['provider'],
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[generateDraft] ' . $e->getMessage(), [
                'typeId' => $typeId,
                'step'   => $request->input('step'),
                'trace'  => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => '웍스 생성 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // 웍스 초안 스트리밍 (SSE — GET, step은 query string으로 전달)
    public function generateDraftStream(Project $project, string $typeId, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        set_time_limit(300);

        // 사전 검증 (스트림 열기 전에 오류 확인)
        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        if (!$typeDef) abort(404);

        $stepNo      = (int) $request->query('step', 1);
        $currentStep = collect($typeDef['steps'])->firstWhere('order', $stepNo);
        if (!$currentStep) abort(422);

        $draftableFields = collect($currentStep['fields'])
            ->filter(fn($f) => in_array($f['type'], ['textarea', 'input', 'table']))
            ->values();

        $aiSetting = AiSetting::current();
        $hasKey    = $aiSetting->anthropicKey() || $aiSetting->openaiKey();
        if (!$hasKey) abort(503);

        // 기획서 + 이전 단계 컨텍스트
        $planningDoc = PlanningDoc::where('project_id', $project->id)->orderByDesc('created_at')->first();
        $deliverable = Deliverable::where('project_id', $project->id)->where('type_id', $typeId)->with('stepData')->first();

        $prevContext = '';
        if ($deliverable) {
            foreach ($deliverable->stepData->where('step_order', '<', $stepNo)->groupBy('step_order') as $sOrder => $items) {
                $stepDef     = collect($typeDef['steps'])->firstWhere('order', (int) $sOrder);
                $prevContext .= "\n### {$sOrder}단계: " . ($stepDef['title'] ?? '') . "\n";
                foreach ($items as $item) {
                    $fd          = collect($stepDef['fields'] ?? [])->firstWhere('key', $item->field_key);
                    $prevContext .= '- ' . ($fd['label'] ?? $item->field_key) . ': ' . mb_substr($item->value, 0, 300) . "\n";
                }
            }
        }

        $docContent = $planningDoc ? mb_substr(strip_tags($planningDoc->content ?? ''), 0, 2000) : null;

        // 필드 키 목록 + JSON 예시 (table 타입은 Markdown 테이블 예시 포함)
        $fieldKeys      = $draftableFields->pluck('key')->toArray();
        $jsonGuideItems = [];
        $hasTableField  = false;
        foreach ($draftableFields as $f) {
            if ($f['type'] === 'table') {
                // 실제 줄바꿈 → json_encode 가 \n 이스케이프로 변환해 웍스에게 올바른 예시 전달
                $jsonGuideItems[$f['key']] = "| 컬럼1 | 컬럼2 |\n|------|------|\n| 내용1 | 내용2 |";
                $hasTableField = true;
            } else {
                $jsonGuideItems[$f['key']] = '...작성 내용...';
            }
        }
        $jsonGuide = json_encode($jsonGuideItems, JSON_UNESCAPED_UNICODE);
        $tableNote = $hasTableField
            ? "\n※ 테이블 필드는 Markdown 표 형식으로 작성하고, 줄바꿈은 반드시 \\n 이스케이프 시퀀스로 표현하세요 (실제 개행 문자 사용 금지)."
            : '';

        $systemPrompt = implode("\n\n", array_filter([
            "당신은 IT 프로젝트 문서 전문가입니다. {$typeDef['name']}({$typeDef['shortName']}) 산출물을 작성합니다.",
            "## 프로젝트 정보\n- 이름: {$project->name}\n- 설명: " . ($project->description ?? '없음'),
            $docContent ? "## 프로젝트 기획서\n{$docContent}" : null,
            $prevContext ? "## 이전 단계 내용{$prevContext}" : null,
            "## 출력 형식 (필수)\n아래 JSON 형식으로만 응답하세요. 마크다운 코드 블록, 설명 텍스트, 주석을 절대 포함하지 마세요. 순수 JSON만 출력하세요.{$tableNote}\n{$jsonGuide}",
        ]));

        $userPrompt = "{$stepNo}단계 '{$currentStep['title']}'({$currentStep['description']}) 내용을 한국어로 구체적이고 실무적으로 작성해주세요.";

        $anthropicKey = $aiSetting->anthropicKey();

        return response()->stream(function () use ($anthropicKey, $systemPrompt, $userPrompt, $fieldKeys, $aiSetting, $typeDef, $stepNo) {
            // 출력 버퍼 비우기 (XAMPP 환경 대응)
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $sse = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
            };

            $anthropicError = null;

            // ── 1순위: Anthropic (Claude) 스트리밍 ──────────────────────────
            if ($anthropicKey) {
                try {
                    $accumulated = '';
                    $provider    = new \App\Services\Agent\AnthropicProvider($anthropicKey);

                    $provider->stream(
                        $systemPrompt,
                        [['role' => 'user', 'content' => $userPrompt]],
                        function (string $chunk) use (&$accumulated, $sse) {
                            $accumulated .= $chunk;
                            $sse('chunk', ['text' => $chunk]);
                        },
                        ['max_tokens' => 2000, 'timeout' => 120]
                    );

                    if (empty(trim($accumulated))) {
                        throw new \RuntimeException('웍스가 빈 응답을 반환했습니다.');
                    }

                    // JSON 추출 (```json/``` 코드블록 또는 { ... } 패턴)
                    $json = $accumulated;
                    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/u', $json, $m)) {
                        $json = trim($m[1]);
                    } elseif (preg_match('/(\{[\s\S]*\})/us', $json, $m)) {
                        $json = trim($m[1]);
                    }

                    $fields = json_decode($json, true);

                    // 복구 1: trailing comma
                    if (!is_array($fields)) {
                        $cleaned = preg_replace('/,\s*([\]}])/u', '$1', $json);
                        $fields  = json_decode($cleaned, true);
                    }

                    // 복구 2: JSON 문자열 값 내 literal 줄바꿈 → \n 이스케이프 교체
                    if (!is_array($fields)) {
                        $fixedJson = preg_replace_callback(
                            '/"(?:[^"\\\\]|\\\\.)*"/s',
                            fn ($m) => str_replace(["\r\n", "\n", "\r"], '\\n', $m[0]),
                            $json
                        );
                        $fields = $fixedJson ? (json_decode($fixedJson, true) ?? []) : [];
                    }

                    $fields   = is_array($fields) ? $fields : [];
                    $filtered = empty($fieldKeys) ? $fields : array_intersect_key($fields, array_flip($fieldKeys));

                    $debug = empty($filtered) ? [
                        'fieldKeys' => $fieldKeys,
                        'aiKeys'    => array_keys($fields),
                        'jsonError' => json_last_error_msg(),
                        'snippet'   => mb_substr($accumulated, 0, 300),
                    ] : null;

                    \Illuminate\Support\Facades\Log::info('[generateDraftStream] claude done', [
                        'fieldKeys'    => $fieldKeys,
                        'filteredKeys' => array_keys($filtered),
                    ]);

                    $sse('done', ['ok' => true, 'fields' => $filtered, 'provider' => 'claude', '_debug' => $debug]);
                    return;

                } catch (\Throwable $e) {
                    $anthropicError = $e->getMessage();
                    \Illuminate\Support\Facades\Log::warning('[generateDraftStream] Anthropic 실패, OpenAI 폴백: ' . $anthropicError);
                    $sse('status', ['text' => 'Claude 사용 불가, OpenAI로 재시도 중…']);
                }
            }

            // ── 2순위: OpenAI 폴백 ────────────────────────────────────────
            if ($aiSetting->openaiKey()) {
                try {
                    $sse('status', ['text' => '웍스 초안 생성 중…']);

                    $schemaProps = [];
                    foreach ($fieldKeys as $key) {
                        $fd = collect(collect($typeDef['steps'])->firstWhere('order', $stepNo)['fields'] ?? [])->firstWhere('key', $key);
                        $schemaProps[$key] = ['type' => 'string', 'description' => ($fd['label'] ?? $key) . ' 초안'];
                    }
                    $orchestrator = new \App\Services\AiOrchestrator(null, $aiSetting->openaiKey());
                    $result = $orchestrator->generateDraft($systemPrompt, $userPrompt, [
                        'type' => 'object', 'properties' => $schemaProps, 'required' => $fieldKeys,
                    ]);
                    $sse('done', ['ok' => true, 'fields' => $result['fields'], 'provider' => $result['provider']]);

                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('[generateDraftStream] OpenAI 도 실패: ' . $e->getMessage());
                    $sse('error', ['error' => '웍스 생성 실패: ' . $e->getMessage()]);
                }
                return;
            }

            // ── 키 없음 또는 모든 공급자 실패 ─────────────────────────────
            $sse('error', ['error' => $anthropicError ?? '웍스 API 키가 설정되지 않았습니다.']);
        }, 200, [
            'Content-Type'      => 'text/event-stream; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    // 웍스 분석 (적합성 검증 / 보완 제안 / 표준 비교 / 도구 자동 생성 / 자유 질문)
    public function analyzeStep(Project $project, string $typeId, Request $request): JsonResponse
    {
        set_time_limit(300);
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $aiSetting = AiSetting::current();
        if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey() && !$aiSetting->manusKey()) {
            return response()->json(['error' => '웍스 API 키가 설정되지 않았습니다.'], 503);
        }

        $action      = $request->input('action', 'question');
        $stepNo      = (int) $request->input('step', 1);
        $fields      = $request->input('fields', []);
        $question    = $request->input('question', '');
        $toolId      = $request->input('tool_id', '');

        $currentStep = collect($typeDef['steps'])->firstWhere('order', $stepNo);
        abort_if(!$currentStep, 422, '단계 정의를 찾을 수 없습니다.');

        // 기획서
        $planningDoc = PlanningDoc::where('project_id', $project->id)
            ->orderByDesc('created_at')->first();
        $docContent  = $planningDoc ? mb_substr(strip_tags($planningDoc->content ?? ''), 0, 2000) : null;

        // DB에 저장된 전체 단계 내용 로드 (다른 단계 컨텍스트)
        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->with('stepData')
            ->first();

        // 전체 산출물 컨텍스트 (저장된 모든 단계 내용, 현재 단계 제외)
        $priorStepsSummary = '';
        if ($deliverable) {
            foreach ($typeDef['steps'] as $stepDef) {
                if ($stepDef['order'] === $stepNo) continue;
                $stepLines = [];
                foreach ($stepDef['fields'] ?? [] as $fieldDef) {
                    $saved = $deliverable->getStepValue($stepDef['order'], $fieldDef['key']);
                    if (!$saved) continue;
                    $stepLines[] = "  - {$fieldDef['label']}: " . mb_substr((string) $saved, 0, 400);
                }
                if ($stepLines) {
                    $priorStepsSummary .= "### {$stepDef['order']}단계: {$stepDef['title']}\n" . implode("\n", $stepLines) . "\n";
                }
            }
        }

        // 현재 단계 입력 필드 요약
        // 클라이언트 전송값 우선, 없으면 DB 저장값 사용
        $fieldsSummary = '';
        $textareaKeys  = [];
        foreach ($currentStep['fields'] ?? [] as $fieldDef) {
            if (($fieldDef['type'] ?? '') === 'textarea') {
                $textareaKeys[] = $fieldDef['key'];
            }
            $val = $fields[$fieldDef['key']]
                ?? ($deliverable?->getStepValue($stepNo, $fieldDef['key']) ?? null);
            if (!$val) continue;
            $fieldsSummary .= "- {$fieldDef['label']}({$fieldDef['key']}): " . mb_substr((string) $val, 0, 500) . "\n";
        }

        // 반영 가능한 필드가 있을 때 JSON 블록 출력 지시
        $fieldsInstruction = '';
        if (!empty($textareaKeys)) {
            $keyList = implode(', ', $textareaKeys);
            $fieldsInstruction = "\n\n## 반영 가능 필드\n분석·제안이 완료된 후, 개선된 필드 내용을 아래 형식으로 응답 **맨 끝**에 추가하세요.\n사용 가능한 필드 키: {$keyList}\n내용이 없거나 변경 불필요한 필드는 포함하지 마세요.\n<FIELDS_JSON>{\"field_key\": \"개선된 내용\"}</FIELDS_JSON>";
        }

        $baseSystem = implode("\n\n", array_filter([
            "당신은 IT 프로젝트 문서 전문가입니다. {$typeDef['name']}({$typeDef['shortName']}) 산출물을 전문적으로 검토합니다.",
            "## 프로젝트 정보\n- 이름: {$project->name}\n- 설명: " . ($project->description ?? '없음'),
            $docContent ? "## 프로젝트 기획서\n{$docContent}" : null,
            $priorStepsSummary ? "## 이전 단계 저장 내용\n{$priorStepsSummary}" : null,
            "## 현재 단계\n{$stepNo}단계: {$currentStep['title']} — {$currentStep['description']}",
            $fieldsSummary ? "## 현재 단계 입력 내용\n{$fieldsSummary}" : null,
            "한국어로 명확하고 실무적으로 답변하세요.",
        ]));

        [$systemPrompt, $userPrompt] = match ($action) {
            'validate' => [
                $baseSystem . "\n\n## 지시\n위 내용의 적합성을 검증하세요. 충족 여부, 미흡한 부분, 개선 필요 사항을 체크리스트 형식으로 분석해 주세요." . $fieldsInstruction,
                "{$stepNo}단계 '{$currentStep['title']}' 내용의 적합성을 검증해 주세요.",
            ],
            'suggest' => [
                $baseSystem . "\n\n## 지시\n위 내용을 검토하고 보완 사항을 제안하세요. 구체적인 추가 내용이나 수정 방향을 우선순위별로 제시해 주세요." . $fieldsInstruction,
                "{$stepNo}단계 '{$currentStep['title']}' 내용의 보완 사항을 제안해 주세요.",
            ],
            'standard' => [
                $baseSystem . "\n\n## 지시\n위 내용을 IT 프로젝트 문서 표준(ISO 29148, PMBOK, SWEBOK 등)과 비교하세요. 표준 준수 여부와 개선 방향을 구체적으로 제시해 주세요." . $fieldsInstruction,
                "{$stepNo}단계 '{$currentStep['title']}' 내용을 업계 표준과 비교 분석해 주세요.",
            ],
            'tool-generate' => [
                $baseSystem . "\n\n## 지시\n현재 단계의 내용을 바탕으로 활용 가능한 분석 도구(UML, 체크리스트, 매트릭스, 다이어그램 등)의 결과 초안을 생성하세요." . $fieldsInstruction,
                "{$stepNo}단계 '{$currentStep['title']}' 단계의 도구 활용 결과를 자동 생성해 주세요.",
            ],
            'table' => [
                $baseSystem . "\n\n## 지시\n현재 단계의 내용을 분석하여 Markdown 표 형식으로 데이터 테이블을 생성하세요.\n반드시 아래 형식으로만 응답하세요:\n<TABLE_MD>\n| 컬럼1 | 컬럼2 | 컬럼3 |\n|------|------|------|\n| 내용1 | 내용2 | 내용3 |\n</TABLE_MD>\n설명 없이 <TABLE_MD> 태그 안의 Markdown 표만 반환하세요.",
                "{$stepNo}단계 '{$currentStep['title']}' 에 맞는 데이터 테이블을 Markdown 형식으로 생성해 주세요.",
            ],
            'diagram' => (function () use ($baseSystem, $stepNo, $currentStep, $toolId): array {
                $diagramTypes = [
                    'DIAGRAM-FLOW' => ['name' => '업무 흐름도(Flowchart)', 'syntax' => 'flowchart TD'],
                    'DIAGRAM-SEQ'  => ['name' => '시퀀스 다이어그램', 'syntax' => 'sequenceDiagram'],
                    'DIAGRAM-ERD'  => ['name' => 'ERD(Entity-Relationship Diagram)', 'syntax' => 'erDiagram'],
                    'DIAGRAM-ARCH' => ['name' => '아키텍처 다이어그램', 'syntax' => 'flowchart TB'],
                    'DIAGRAM-DFD'  => ['name' => '데이터 흐름도(DFD)', 'syntax' => 'flowchart LR'],
                    'DIAGRAM-NET'  => ['name' => '네트워크 다이어그램', 'syntax' => 'flowchart TB'],
                    'DIAGRAM-LIFE' => ['name' => '생명주기 다이어그램', 'syntax' => 'stateDiagram-v2'],
                ];
                $dType = $diagramTypes[$toolId] ?? ['name' => '다이어그램', 'syntax' => 'flowchart TD'];
                $system = $baseSystem . "\n\n## 지시\n현재 단계의 내용을 분석하여 {$dType['name']}을 Mermaid 문법으로 생성하세요.\n반드시 아래 형식으로만 응답하세요:\n<MERMAID>\n{$dType['syntax']}\n    ...\n</MERMAID>\n설명이나 다른 텍스트 없이 <MERMAID> 태그 안의 코드만 반환하세요.";
                return [$system, "{$stepNo}단계 '{$currentStep['title']}' 에 맞는 {$dType['name']}을 Mermaid 코드로 생성해 주세요."];
            })(),
            'qa' => [
                $baseSystem . "\n\n## 지시\n현재 단계의 내용을 분석하여 품질·리스크 점검을 위한 질의응답 체크리스트를 섹션별로 생성하세요.\n위험 수준은 none, low, medium, high, critical 중 하나로 지정하세요.\n반드시 아래 JSON 형식으로만 응답하세요:\n<QA_JSON>\n{\"sections\":[{\"title\":\"섹션명\",\"items\":[{\"question\":\"질문\",\"answer\":\"\",\"risk\":\"none\",\"notes\":\"\"}]}]}\n</QA_JSON>\n설명 없이 <QA_JSON> 태그 안의 JSON만 반환하세요.",
                "{$stepNo}단계 '{$currentStep['title']}' 에 맞는 품질·리스크 점검 질의응답 체크리스트를 생성해 주세요.",
            ],
            default => [
                $baseSystem . "\n\n## 지시\n사용자의 질문 또는 지시에 전문가로서 정확하고 실용적으로 답변하세요. 내용을 직접 작성·수정하는 요청이라면 개선된 필드 내용을 반드시 포함하세요." . $fieldsInstruction,
                $question ?: "{$stepNo}단계 '{$currentStep['title']}' 에 대해 조언해 주세요.",
            ],
        };

        try {
            $orchestrator = new AiOrchestrator(
                $aiSetting->anthropicKey(),
                $aiSetting->openaiKey(),
                $aiSetting->manusKey(),
                $aiSetting->manusEndpoint(),
            );
            $result = $orchestrator->chatRawDirect(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );

            $responseText   = $result['text'];

            // 테이블 액션: <TABLE_MD>...</TABLE_MD> 또는 ```markdown 코드 블록 추출
            if ($action === 'table') {
                $mdCode = '';
                if (preg_match('/<TABLE_MD>(.*?)<\/TABLE_MD>/si', $responseText, $m)) {
                    $mdCode = trim($m[1]);
                } elseif (preg_match('/```(?:markdown|md)?\s*((?:\|[^\n]*\n?)+)/si', $responseText, $m)) {
                    $mdCode = trim($m[1]);
                } else {
                    // 응답 중 표 라인만 추출
                    $lines = array_filter(explode("\n", $responseText), fn($l) => str_starts_with(trim($l), '|'));
                    $mdCode = implode("\n", $lines) ?: trim($responseText);
                }
                return response()->json(['ok' => true, 'markdown' => $mdCode, 'provider' => $result['provider']]);
            }

            // 다이어그램 액션: <MERMAID>...</MERMAID> 또는 ```mermaid 코드 블록 추출
            if ($action === 'diagram') {
                $mermaidCode = '';
                if (preg_match('/<MERMAID>(.*?)<\/MERMAID>/si', $responseText, $m)) {
                    $mermaidCode = trim($m[1]);
                } elseif (preg_match('/```mermaid\s*(.*?)```/si', $responseText, $m)) {
                    $mermaidCode = trim($m[1]);
                } else {
                    $mermaidCode = trim($responseText);
                }
                return response()->json(['ok' => true, 'mermaid' => $mermaidCode, 'provider' => $result['provider']]);
            }

            // Q&A 체크리스트 액션: <QA_JSON>...</QA_JSON> 파싱
            if ($action === 'qa') {
                $qaJson = '';
                if (preg_match('/<QA_JSON>(.*?)<\/QA_JSON>/si', $responseText, $m)) {
                    $qaJson = trim($m[1]);
                } elseif (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/si', $responseText, $m)) {
                    $qaJson = trim($m[1]);
                } else {
                    $qaJson = trim($responseText);
                }
                $qaData = json_decode($qaJson, true);
                if (!is_array($qaData) || empty($qaData['sections'])) {
                    return response()->json(['ok' => false, 'message' => '웍스 응답을 파싱할 수 없습니다. 다시 시도해 주세요.']);
                }
                return response()->json(['ok' => true, 'result' => $qaData, 'provider' => $result['provider']]);
            }

            // <FIELDS_JSON>…</FIELDS_JSON> 파싱 후 text 에서 제거
            $extractedFields = [];
            if (preg_match('/<FIELDS_JSON>(.*?)<\/FIELDS_JSON>/s', $responseText, $m)) {
                $decoded = json_decode(trim($m[1]), true);
                if (is_array($decoded)) {
                    $extractedFields = $decoded;
                }
                $responseText = trim(str_replace($m[0], '', $responseText));
            }

            return response()->json([
                'ok'       => true,
                'text'     => $responseText,
                'provider' => $result['provider'],
                'fields'   => $extractedFields,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => '웍스 분석 중 오류: ' . $e->getMessage()], 500);
        }
    }

    // 승인 요청 전송
    public function approvalRequest(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $validated = $request->validate([
            'step'        => 'required|integer|min:1',
            'approver_id' => 'required|integer|exists:users,id',
        ]);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        $stepNo  = (int) $validated['step'];
        $stepDef = collect($typeDef['steps'])->firstWhere('order', $stepNo);
        abort_if(!$stepDef, 422, '단계 정의를 찾을 수 없습니다.');

        $approver = User::findOrFail($validated['approver_id']);

        // 같은 단계에 pending 승인 요청이 이미 있으면 취소 후 재생성
        DeliverableApproval::where('deliverable_id', $deliverable->id)
            ->where('step_order', $stepNo)
            ->where('status', 'pending')
            ->delete();

        $approval = DeliverableApproval::create([
            'deliverable_id' => $deliverable->id,
            'step_order'     => $stepNo,
            'requester_id'   => Auth::id(),
            'approver_id'    => $approver->id,
            'status'         => 'pending',
        ]);

        $approval->load('requester');

        $mailOk = false;
        try {
            Mail::to($approver->email)->send(new DeliverableApprovalMail(
                $deliverable,
                $approval,
                $approver,
                $stepDef['title'],
                $typeDef['name'],
            ));
            $mailOk = true;
        } catch (\Throwable) {
            // 메일 실패해도 요청은 유지
        }

        // 이메일 발송 성공 시 SMS 추가 발송 (응답 차단 방지 — terminating)
        if ($mailOk && $approver->phone) {
            $smsPhone = $approver->phone;
            $smsName  = $approver->name;
            $smsMsg   = "[SupportWorks] " . (Auth::user()->name ?? '요청자') .
                       "님이 산출물 '{$typeDef['name']} - {$stepDef['title']}' 승인을 요청했습니다.";
            app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
            });
        }

        return response()->json([
            'ok'          => true,
            'approval_id' => $approval->id,
            'message'     => "{$approver->name}님에게 승인 요청을 전송했습니다.",
        ]);
    }

    // 승인 또는 반려 처리
    public function approvalRespond(Project $project, string $typeId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'approval_id' => 'required|integer',
            'action'      => 'required|in:approved,rejected',
            'note'        => 'nullable|string|max:500',
        ]);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        $approval = DeliverableApproval::where('deliverable_id', $deliverable->id)
            ->where('id', $validated['approval_id'])
            ->where('approver_id', Auth::id())
            ->where('status', 'pending')
            ->firstOrFail();

        $approval->update([
            'status'       => $validated['action'],
            'responded_at' => now(),
            'note'         => $validated['note'] ?? null,
        ]);

        // 요청자에게 승인/반려 결과 이메일 발송
        $approval->load(['requester', 'approver']);
        $typeDef  = $this->config['deliverables'][$typeId] ?? [];
        $stepDef  = collect($typeDef['steps'] ?? [])->firstWhere('order', $approval->step_order);
        $requester = $approval->requester;

        $respondMailOk = false;
        if ($requester?->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($requester->email)
                    ->send(new DeliverableApprovalRespondMail(
                        $deliverable,
                        $approval,
                        $requester,
                        $stepDef['title']       ?? '',
                        $typeDef['name']        ?? $typeId,
                    ));
                $respondMailOk = true;
            } catch (\Throwable) {}
        }

        // 이메일 성공 후 SMS 추가 발송 (응답 차단 방지 — terminating)
        if ($respondMailOk && $requester?->phone) {
            $smsPhone = $requester->phone;
            $smsName  = $requester->name;
            $approverName = $approval->approver->name ?? '승인자';
            $resultLabel  = $validated['action'] === 'approved' ? '승인' : '반려';
            $title        = ($typeDef['name'] ?? $typeId) . ' - ' . ($stepDef['title'] ?? '');
            $smsMsg   = "[SupportWorks] {$approverName}님이 산출물 '{$title}'을(를) {$resultLabel}했습니다.";
            app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
            });
        }

        return response()->json([
            'ok'     => true,
            'status' => $validated['action'],
            'message' => $validated['action'] === 'approved' ? '승인 처리되었습니다.' : '반려 처리되었습니다.',
        ]);
    }

    // 링크 공유 토큰 생성/삭제
    public function toggleShare(Project $project, string $typeId): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        if ($deliverable->share_token) {
            $deliverable->update(['share_token' => null]);
            return response()->json(['ok' => true, 'url' => null, 'active' => false]);
        }

        $token = Str::random(48);
        $deliverable->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'url'    => route('deliverables.public-share', $token),
            'active' => true,
        ]);
    }

    // 공개 링크 뷰어 (로그인 불필요)
    public function publicShare(string $token): View
    {
        $deliverable = Deliverable::where('share_token', $token)->firstOrFail();
        $typeDef     = $this->config['deliverables'][$deliverable->type_id] ?? null;
        abort_if(!$typeDef, 404);

        $project = \App\Models\Project::findOrFail($deliverable->project_id);
        $deliverable->load(['stepData', 'toolResults']);

        return view('ai-agent.deliverables.public-share', [
            'deliverable' => $deliverable,
            'typeDef'     => $typeDef,
            'project'     => $project,
            'tools'       => $this->config['tools'],
        ]);
    }

    // Word(.docx) 내보내기
    public function exportWord(Project $project, string $typeId, \Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $lang = $request->input('lang', 'ko'); // 'ko' | 'en'
        $isEn = $lang === 'en';
        $font = $isEn ? 'Calibri' : '맑은 고딕';

        // 영문 번역 맵 로드 (STEP 제목/설명, 필드 레이블, 다이어그램 툴 이름)
        $enMap = $isEn ? (config('deliverables_en') ?? []) : [];

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->with(['stepData', 'toolResults'])
            ->firstOrFail();

        // STEP 별 최신 버전 스냅샷이 있으면 우선 사용 (없으면 작업본 fallback)
        $versionSnapshots = $this->buildVersionSnapshots($deliverable);
        $stepValueGetter  = $this->stepValueResolver($deliverable, $versionSnapshots);
        $stepEnGetter     = $this->stepEnResolver($deliverable, $versionSnapshots);
        $toolResultGetter = $this->toolResultResolver($deliverable, $versionSnapshots);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName($font);
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(1, [
            'name' => $font, 'size' => 16, 'bold' => true, 'color' => '4F46E5',
        ], ['spaceBefore' => 280, 'spaceAfter' => 120,
            'borderBottomColor' => '4F46E5', 'borderBottomSize' => 6]);
        $phpWord->addTitleStyle(2, [
            'name' => $font, 'size' => 13, 'bold' => true, 'color' => '374151',
        ], ['spaceBefore' => 200, 'spaceAfter' => 80]);

        $section = $phpWord->addSection([
            'marginTop' => 1440, 'marginBottom' => 1440,
            'marginLeft' => 1080, 'marginRight' => 1080,
        ]);

        // 표지
        $section->addText($this->wordEsc($typeDef['name'] . ' (' . $typeDef['shortName'] . ')'), [
            'name' => $font, 'size' => 22, 'bold' => true, 'color' => '312E81',
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 120]);

        $section->addText($this->wordEsc($project->name), [
            'name' => $font, 'size' => 13, 'color' => '6B7280',
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);

        $section->addText($isEn ? now()->format('F j, Y') : now()->format('Y년 m월 d일'), [
            'name' => $font, 'size' => 11, 'color' => '9CA3AF',
        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 480]);

        // 단계별 내용 — 내용이 있는 STEP만 제목 포함 출력
        $tmpImages = [];
        foreach ($typeDef['steps'] as $step) {
            // 이 STEP의 영문 번역 레코드 (없으면 null)
            $stepEn = $enMap['deliverables'][$typeId]['steps'][$step['order']] ?? null;

            // 1) 이 STEP의 모든 항목을 먼저 수집
            $stepItems = [];

            foreach ($step['fields'] ?? [] as $field) {
                if ($isEn) {
                    $enData = $stepEnGetter($step['order'], $field['key']);
                    // 영문 번역이 없으면 해당 필드 제외 (한글 원문 폴백 없음)
                    if (!$enData['valid'] || !$enData['en_value']) continue;
                    $val = $enData['en_value'];
                } else {
                    $val = $stepValueGetter($step['order'], $field['key']);
                }
                if (!$val) continue;

                // 필드 레이블 번역
                $label = ($stepEn['fields'][$field['key']] ?? null) ?: $field['label'];

                $stepItems[] = ['type' => 'field', 'label' => $label, 'val' => (string) $val];
            }

            foreach ($step['tools'] ?? [] as $tb) {
                $toolDef = $this->config['tools'][$tb['toolId']] ?? null;
                if (($toolDef['category'] ?? '') !== 'diagram') continue;

                $toolResult = $toolResultGetter($step['order'], $tb['toolId']);
                if (empty($toolResult['png'])) continue;

                $pngData = base64_decode($toolResult['png']);
                if (!$pngData) continue;

                $tmpImg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dlv_dgr_' . uniqid() . '.png';
                file_put_contents($tmpImg, $pngData);
                $tmpImages[] = $tmpImg;

                // 다이어그램 툴 이름 번역
                $toolLabel = ($enMap['tools'][$tb['toolId']] ?? null) ?: ($toolDef['name'] ?? $tb['toolId']);

                $stepItems[] = ['type' => 'diagram', 'label' => $toolLabel, 'tmpImg' => $tmpImg];
            }

            // 2) 내용이 없으면 이 STEP 전체 생략 (제목·설명 포함)
            if (empty($stepItems)) continue;

            // 3) 내용이 있을 때만 STEP 제목·설명 출력 (번역 적용)
            $stepTitle = ($stepEn['title'] ?? null) ?: $step['title'];
            $section->addTitle($this->wordEsc($step['order'] . '. ' . $stepTitle), 1);


            // 4) 수집된 항목 출력
            foreach ($stepItems as $item) {
                $section->addTitle($this->wordEsc($item['label']), 2);

                if ($item['type'] === 'field') {
                    $this->addMarkdownToWord($section, $item['val'], $font);
                } else {
                    $section->addImage($item['tmpImg'], [
                        'width'         => 450,
                        'wrappingStyle' => 'inline',
                        'alignment'     => Jc::CENTER,
                    ]);
                }
            }
        }

        // 푸터
        $footer = $section->addFooter();
        $footer->addPreserveText(
            '{PAGE} / {NUMPAGES}',
            ['name' => $font, 'size' => 9, 'color' => '9CA3AF'],
            ['alignment' => Jc::RIGHT]
        );

        $safeName = preg_replace('/[^A-Za-z0-9가-힣_\-]/', '_', $project->name . '_' . $typeDef['shortName']);
        $fileName = $safeName . ($isEn ? '_EN' : '') . '_' . now()->format('Ymd') . '.docx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dlv_' . uniqid() . '.docx';

        WordIOFactory::createWriter($phpWord, 'Word2007')->save($tmpPath);

        // PNG 임시 파일 정리 (DOCX에 이미 임베딩됨)
        foreach ($tmpImages as $tmp) {
            @unlink($tmp);
        }

        // 한글 파일명 보존 — Symfony 기본 makeDisposition 의 ASCII 음역 fallback 우회
        $encoded  = rawurlencode($fileName);
        $response = response()->download($tmpPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
        $response->headers->set('Content-Disposition', "attachment; filename*=UTF-8''{$encoded}");
        return $response;
    }

    // ── 뷰어 의견 ────────────────────────────────────────────
    public function viewerCommentsIndex(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);
        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)->firstOrFail();

        $step = (int) $request->input('step', 1);
        $comments = $deliverable->viewerComments()
            ->where('step_order', $step)
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'user_name'  => $c->user->name,
                'user_id'    => $c->user_id,
                'mine'       => $c->user_id === Auth::id(),
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json($comments);
    }

    public function viewerCommentsStore(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);
        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)->firstOrFail();

        $validated = $request->validate([
            'step' => 'required|integer|min:1',
            'body' => 'required|string|max:2000',
        ]);

        $comment = $deliverable->viewerComments()->create([
            'step_order' => $validated['step'],
            'user_id'    => Auth::id(),
            'body'       => $validated['body'],
        ]);
        $comment->load('user:id,name');

        return response()->json([
            'ok'         => true,
            'id'         => $comment->id,
            'body'       => $comment->body,
            'user_name'  => $comment->user->name,
            'user_id'    => $comment->user_id,
            'mine'       => true,
            'created_at' => $comment->created_at->diffForHumans(),
        ]);
    }

    public function viewerCommentsDestroy(Project $project, string $typeId, DeliverableComment $comment): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($comment->user_id !== Auth::id(), 403);
        $comment->delete();
        return response()->json(['ok' => true]);
    }

    // 산출물 삭제(초기화)
    public function destroy(Project $project, string $typeId): RedirectResponse
    {
        $this->authorizeProject($project);

        Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->delete();

        return redirect()
            ->route('ai-agent.projects.deliverables.index', $project)
            ->with('success', '산출물이 초기화되었습니다.');
    }

    // ── 마크다운 → PhpWord 네이티브 변환 ─────────────────────────────────

    private function addMarkdownToWord(\PhpOffice\PhpWord\Element\Section $section, string $markdown, string $font = '맑은 고딕'): void
    {
        if (trim($markdown) === '') return;

        $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = rtrim($lines[$i]);

            // 헤딩
            if (preg_match('/^(#{1,6})\s+(.+)/', $line, $m)) {
                $level = strlen($m[1]);
                $text  = preg_replace('/\*{1,2}(.+?)\*{1,2}/', '$1', $m[2]);
                $size  = match(true) { $level === 1 => 13, $level === 2 => 12, default => 11 };
                $section->addText(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), [
                    'name' => $font, 'size' => $size, 'bold' => true, 'color' => $level <= 2 ? '374151' : '6B7280',
                ], ['spaceBefore' => 140, 'spaceAfter' => 60]);
                $i++;
                continue;
            }

            // 가로선
            if (preg_match('/^[-*_]{3,}\s*$/', $line)) {
                $section->addTextBreak(1);
                $i++;
                continue;
            }

            // 코드 블록
            if (str_starts_with($line, '```')) {
                $i++;
                $codeLines = [];
                while ($i < $total && !str_starts_with(rtrim($lines[$i]), '```')) {
                    $codeLines[] = $lines[$i];
                    $i++;
                }
                $i++; // closing ```
                if ($codeLines) {
                    $section->addText(htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8'), [
                        'name' => 'Courier New', 'size' => 9, 'color' => '1F2937',
                    ], ['spaceBefore' => 40, 'spaceAfter' => 40]);
                }
                continue;
            }

            // 테이블 (| 로 시작하는 행들)
            if (preg_match('/^\s*\|/', $line)) {
                $tableLines = [];
                while ($i < $total && preg_match('/^\s*\|/', $lines[$i])) {
                    if (!preg_match('/^\s*\|[\s:\-|]+\|\s*$/', $lines[$i])) {
                        $tableLines[] = $lines[$i];
                    }
                    $i++;
                }
                if ($tableLines) {
                    $this->addWordTable($section, $tableLines, $font);
                }
                continue;
            }

            // 불릿 리스트
            if (preg_match('/^(\s*)([-*+])\s+(.+)/', $line, $m)) {
                $depth = (int)(strlen($m[1]) / 2);
                $text  = $this->stripInlineMd($m[3]);
                $section->addListItem(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $depth, [
                    'name' => $font, 'size' => 11,
                ]);
                $i++;
                continue;
            }

            // 번호 리스트
            if (preg_match('/^(\s*)\d+\.\s+(.+)/', $line, $m)) {
                $depth = (int)(strlen($m[1]) / 2);
                $text  = $this->stripInlineMd($m[2]);
                $section->addListItem(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $depth, [
                    'name' => $font, 'size' => 11,
                ], 'listNumber');
                $i++;
                continue;
            }

            // 빈 줄
            if ($line === '') {
                $i++;
                continue;
            }

            // 일반 단락 (인라인 서식 포함)
            $this->addWordParagraph($section, $line, $font);
            $i++;
        }
    }

    private function addWordParagraph(\PhpOffice\PhpWord\Element\Section $section, string $text, string $font = '맑은 고딕'): void
    {
        $run = $section->addTextRun(['spaceAfter' => 80, 'spaceBefore' => 20]);
        $segments = preg_split('/(\*\*[^*\n]+\*\*|\*[^*\n]+\*|`[^`\n]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($segments as $seg) {
            if ($seg === '') continue;
            if (preg_match('/^\*\*(.+)\*\*$/s', $seg, $m)) {
                $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11, 'bold' => true]);
            } elseif (preg_match('/^\*(.+)\*$/s', $seg, $m)) {
                $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11, 'italic' => true]);
            } elseif (preg_match('/^`(.+)`$/s', $seg, $m)) {
                $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => 'Courier New', 'size' => 10]);
            } else {
                $run->addText(htmlspecialchars($seg, ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11]);
            }
        }
    }

    private function addWordTable(\PhpOffice\PhpWord\Element\Section $section, array $tableLines, string $font = '맑은 고딕'): void
    {
        if (empty($tableLines)) return;

        // 열 수 파악 (최대값 기준)
        $maxCols = 0;
        foreach ($tableLines as $rowLine) {
            $cnt = count(array_filter(
                array_map('trim', explode('|', trim($rowLine, " |\t"))),
                fn($c) => $c !== ''
            ));
            $maxCols = max($maxCols, $cnt);
        }
        if ($maxCols === 0) return;

        // A4 기준 인쇄 가능 너비 (11906 twips - 좌우 여백 1080 각각)
        $printableWidth = 9746; // twips
        $cellWidth      = (int) floor($printableWidth / $maxCols);

        $table = $section->addTable([
            'borderSize'  => 4,
            'borderColor' => 'D1D5DB',
            'cellMargin'  => 80,
        ]);

        foreach ($tableLines as $rowIdx => $rowLine) {
            $cells = array_values(array_filter(
                array_map('trim', explode('|', trim($rowLine, " |\t"))),
                fn($c) => $c !== ''
            ));

            $table->addRow();

            // 실제 열이 maxCols보다 적으면 마지막 셀을 colspan으로 확장
            $colCount = count($cells);
            foreach ($cells as $colIdx => $cell) {
                $isHeader = $rowIdx === 0;
                $isLast   = $colIdx === $colCount - 1;
                // 마지막 셀이 부족한 열을 흡수해 테이블이 항상 전체 너비를 채우게 함
                $width    = $isLast ? $printableWidth - $cellWidth * ($colCount - 1) : $cellWidth;

                $cellEl = $table->addCell($width, $isHeader ? ['bgColor' => 'F1F5F9'] : []);
                $plain  = $this->stripInlineMd($cell);
                $cellEl->addText(htmlspecialchars($plain, ENT_QUOTES, 'UTF-8'), [
                    'name' => $font, 'size' => 10, 'bold' => $isHeader,
                ], ['wordWrap' => true]);
            }
        }

        $section->addTextBreak(1);
    }

    private function stripInlineMd(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        return $text;
    }

    // PhpWord은 기본적으로 writeRaw()를 사용하므로 XML 특수문자를 직접 이스케이프해야 함
    private function wordEsc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // STEP 별 최신 버전 스냅샷 모음 → [stepOrder => ['fields' => [...], 'tools' => [...]]]
    private function buildVersionSnapshots(Deliverable $deliverable): array
    {
        $snapshots = [];
        $versions  = DeliverableStepVersion::where('deliverable_id', $deliverable->id)
            ->orderBy('step_order')
            ->orderByDesc('version_no')
            ->get();

        foreach ($versions as $v) {
            if (isset($snapshots[$v->step_order])) continue; // 가장 최신만
            $snapshots[$v->step_order] = [
                'fields' => $v->fieldsArray(),
                'tools'  => $v->toolsArray(),
            ];
        }
        return $snapshots;
    }

    // 버전 스냅샷이 있으면 거기서, 없으면 작업본에서 값을 꺼내는 클로저
    private function stepValueResolver(Deliverable $deliverable, array $snapshots): \Closure
    {
        return function (int $step, string $key) use ($deliverable, $snapshots) {
            if (isset($snapshots[$step]['fields'][$key])) {
                $payload = $snapshots[$step]['fields'][$key];
                return is_array($payload) ? ($payload['value'] ?? null) : $payload;
            }
            return $deliverable->getStepValue($step, $key);
        };
    }

    private function stepEnResolver(Deliverable $deliverable, array $snapshots): \Closure
    {
        return function (int $step, string $key) use ($deliverable, $snapshots) {
            if (isset($snapshots[$step]['fields'][$key]) && is_array($snapshots[$step]['fields'][$key])) {
                $p     = $snapshots[$step]['fields'][$key];
                $value = $p['value']    ?? '';
                $en    = $p['en_value'] ?? '';
                $hash  = $p['en_hash']  ?? '';
                $valid = $hash !== '' && $hash === md5((string) $value);
                return ['en_value' => $en, 'valid' => $valid];
            }
            return $deliverable->getStepEnData($step, $key);
        };
    }

    private function toolResultResolver(Deliverable $deliverable, array $snapshots): \Closure
    {
        return function (int $step, string $toolId) use ($deliverable, $snapshots) {
            if (isset($snapshots[$step]['tools'][$toolId])) {
                return $snapshots[$step]['tools'][$toolId];
            }
            return $deliverable->getToolResult($step, $toolId);
        };
    }

    // 파일 등록 대상 후보 목록 (다이얼로그용)
    public function registerableFiles(Project $project, string $typeId): JsonResponse
    {
        $this->authorizeProject($project);

        $files = ProjectFile::where('project_id', $project->id)
            ->where(function ($q) {
                $q->whereNull('file_type')->orWhere('file_type', 'file');
            })
            ->with('category:id,name,color')
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'category_id', 'original_name', 'mime_type', 'size', 'updated_at']);

        $rows = $files->map(function ($f) {
            $maxVer = (int) ($f->versions()->max('version') ?? 1);
            return [
                'id'             => $f->id,
                'original_name'  => $f->original_name,
                'mime_type'      => $f->mime_type,
                'size'           => $f->size,
                'next_version'   => $maxVer + 1,
                'updated_at'     => $f->updated_at?->format('Y-m-d H:i'),
                'category_id'    => $f->category_id,
                'category_name'  => $f->category?->name,
                'category_color' => $f->category?->color,
            ];
        });

        return response()->json(['ok' => true, 'files' => $rows]);
    }

    // 산출물 통합 Word 를 project_files 로 등록
    // target_file_id 가 지정되면 해당 파일에 새 버전 추가, 없으면 신규 파일로 최초 등록
    public function registerAsFile(Project $project, string $typeId, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $typeDef = $this->config['deliverables'][$typeId] ?? null;
        abort_if(!$typeDef, 404);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->firstOrFail();

        $changeNote   = (string) ($request->input('change_note') ?? '');
        $lang         = $request->input('lang', 'ko');
        $targetFileId = $request->input('target_file_id');

        // 대상 파일 선결정 (지정된 경우 같은 프로젝트 소속인지 검증)
        $projectFile = null;
        if (!empty($targetFileId)) {
            $projectFile = ProjectFile::where('id', $targetFileId)
                ->where('project_id', $project->id)
                ->first();
            if (!$projectFile) {
                return response()->json(['ok' => false, 'message' => '선택한 파일을 찾을 수 없습니다.'], 404);
            }
        }

        // 1) Word 파일 생성
        $exportRequest = Request::create('', 'GET', ['lang' => $lang]);
        $response      = $this->exportWord($project, $typeId, $exportRequest);
        $tmpPath       = $response->getFile()->getPathname();

        // 2) 'local' 디스크(storage/app/)에 저장 — ProjectFile 모듈과 동일한 경로 컨벤션
        //    (미리보기/다운로드/Office→PDF 변환 모두 이 경로 기준으로 동작)
        $storedName = uniqid('dlv_', true) . '.docx';
        $finalPath  = \Illuminate\Support\Facades\Storage::disk('local')
            ->putFileAs('project_files', new \Illuminate\Http\File($tmpPath), $storedName);
        @unlink($tmpPath);

        $size = \Illuminate\Support\Facades\Storage::disk('local')->size($finalPath) ?: 0;
        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        // 신규 등록일 때 사용할 파일명 (기존 파일에 버전업할 때는 그 파일명 기반)
        $baseName = $projectFile?->original_name
            ?? sprintf('%s_%s.docx',
                preg_replace('/[^A-Za-z0-9가-힣_\-]/', '_', $project->name),
                $typeDef['shortName']);

        if (!$projectFile) {
            // ── 최초 등록 ─────────────────────────────────────────
            $projectFile = ProjectFile::create([
                'project_id'    => $project->id,
                'uploaded_by'   => Auth::id(),
                'original_name' => $baseName,
                'stored_name'   => $storedName,
                'path'          => $finalPath,
                'mime_type'     => $mime,
                'size'          => $size,
                'description'   => "{$typeDef['name']} 산출물",
                'file_type'     => 'file',
            ]);
            $versionNo = 1;
            $isNew     = true;
        } else {
            // ── 기존 파일에 버전 추가 ────────────────────────────
            $versionNo = (int) ($projectFile->versions()->max('version') ?? 1) + 1;
            $projectFile->update([
                'stored_name' => $storedName,
                'path'        => $finalPath,
                'mime_type'   => $mime,
                'size'        => $size,
            ]);
            $isNew = false;
        }

        // file_versions 기록 — 최초인데 비어 있으면 v1 도 함께 기록
        if ($versionNo === 1 && $projectFile->versions()->count() === 0) {
            FileVersion::create([
                'project_file_id' => $projectFile->id,
                'version'         => 1,
                'original_name'   => $baseName,
                'stored_name'     => $storedName,
                'path'            => $finalPath,
                'mime_type'       => $mime,
                'size'            => $size,
                'uploaded_by'     => Auth::id(),
                'change_note'     => $changeNote !== '' ? $changeNote : '최초 등록',
            ]);
        } else {
            FileVersion::create([
                'project_file_id' => $projectFile->id,
                'version'         => $versionNo,
                'original_name'   => $baseName,
                'stored_name'     => $storedName,
                'path'            => $finalPath,
                'mime_type'       => $mime,
                'size'            => $size,
                'uploaded_by'     => Auth::id(),
                'change_note'     => $changeNote !== '' ? $changeNote : "산출물 v{$versionNo} 등록",
            ]);
        }

        // 기존 파일에 새 버전을 추가한 경우, 그 시점에 활성(아직 frozen 안 된)이던 코멘트를
        // 이전 버전(v(versionNo-1))으로 동결 — resolved 상태/내용은 보존, 새 버전에서는 보이지 않음
        if (!$isNew && $versionNo > 1) {
            FileComment::where('project_file_id', $projectFile->id)
                ->whereNull('frozen_at_version')
                ->update(['frozen_at_version' => $versionNo]);
        }

        // 산출물 ↔ 파일 등록 이력 기록
        DeliverableFileRegistration::create([
            'deliverable_id'  => $deliverable->id,
            'project_file_id' => $projectFile->id,
            'file_version'    => $versionNo,
            'lang'            => $lang,
            'change_note'     => $changeNote !== '' ? $changeNote : null,
            'created_by'      => Auth::id(),
        ]);

        $msg = $isNew
            ? "새 파일로 등록했습니다 (v1)."
            : "'{$projectFile->original_name}' 에 v{$versionNo} 버전을 추가했습니다.";

        return response()->json([
            'ok'              => true,
            'project_file_id' => $projectFile->id,
            'version'         => $versionNo,
            'is_new'          => $isNew,
            'message'         => $msg,
        ]);
    }

    // 산출물의 파일 등록 이력 (다이얼로그용)
    public function fileRegistrations(Project $project, string $typeId): JsonResponse
    {
        $this->authorizeProject($project);

        $deliverable = Deliverable::where('project_id', $project->id)
            ->where('type_id', $typeId)
            ->first();

        if (!$deliverable) {
            return response()->json(['ok' => true, 'registrations' => []]);
        }

        $rows = DeliverableFileRegistration::where('deliverable_id', $deliverable->id)
            ->with(['projectFile:id,original_name', 'creator:id,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'project_file_id' => $r->project_file_id,
                'file_name'       => $r->projectFile?->original_name,
                'file_version'    => $r->file_version,
                'lang'            => $r->lang,
                'change_note'     => $r->change_note,
                'creator'         => $r->creator?->name,
                'created_at'      => $r->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['ok' => true, 'registrations' => $rows]);
    }

    private function authorizeProject(Project $project): void
    {
        if (Auth::user()?->isAdmin()) {
            return;
        }

        abort_unless(
            ProjectMember::where('project_id', $project->id)
                ->where('user_id', Auth::id())
                ->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
