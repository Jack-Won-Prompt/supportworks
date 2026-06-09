<?php

namespace App\Http\Controllers;

use App\Models\Maint\MaintMenu;
use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestAttachment;
use App\Models\Maint\MaintRequestNote;
use App\Models\Maint\MaintUser;
use App\Models\User;
use App\Services\Maint\SrAiReviewService;
use App\Services\Maint\SrNotificationService;
use App\Services\Maint\SrSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsxDate;

class MaintRequestController extends Controller
{
    public function index(Request $request)
    {
        $bucket = $request->string('bucket')->toString();
        if ($bucket === '') {
            $bucket = 'in_progress';
        }

        $bucketStatuses = self::bucketStatuses();

        // ── 권한 기반 접근 범위 결정 ──
        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));

        // 링크더랩 회사 소속 일반 사용자는 자기 회사 SR + 추가개발(유상) SR 모두 접근
        $linkthelabId = \App\Models\CompanyGroup::where('name', '링크더랩')->value('id');
        $isLinkthelabMember = !$isSrPrivileged && $u && (int) $u->company_group_id === (int) $linkthelabId;

        // null = 제한 없음, 양수 = 해당 회사로 제한, 0 = 접근 불가
        $accessScope = null;
        if (!$isSrPrivileged) {
            $accessScope = $u?->company_group_id ? (int) $u->company_group_id : 0;
        }

        $applyAccessScope = function ($qb) use ($accessScope, $isLinkthelabMember) {
            if ($accessScope === 0) {
                $qb->whereRaw('1=0');
            } elseif ($accessScope !== null) {
                if ($isLinkthelabMember) {
                    // 링크더랩 일반 사용자: 본인 회사 OR 추가개발 활성 SR
                    $qb->where(function ($x) use ($accessScope) {
                        $x->where('company_group_id', $accessScope)
                          ->orWhere('paid_dev_enabled', true);
                    });
                } else {
                    $qb->where('company_group_id', $accessScope);
                }
            }
        };

        $q = MaintRequest::query()
            ->with(['menu', 'coloUser', 'assignee']);

        // 정렬 — 헤더 클릭으로 토글
        $sortableMap = [
            'id'           => 'id',
            'priority'     => 'priority',
            'status'       => 'status',
            'request_date' => 'request_date',
            'eta'          => 'eta',
        ];
        $sort = $request->string('sort')->toString();
        $dir  = strtolower($request->string('dir')->toString()) === 'asc' ? 'asc' : 'desc';
        if ($sort === 'colo_user') {
            // 요청자 이름으로 정렬 (subquery — JOIN 없이 안전)
            $q->orderBy(MaintUser::select('name')->whereColumn('id', 'maint_requests.colo_user_id'), $dir)
              ->orderBy('id', 'desc');
        } elseif (isset($sortableMap[$sort])) {
            $q->orderBy($sortableMap[$sort], $dir)->orderBy('id', 'desc');
        } else {
            $q->latest('id'); // 기본: 최신 순
        }

        $applyAccessScope($q);

        if ($bucket !== 'all' && isset($bucketStatuses[$bucket])) {
            $q->whereIn('status', $bucketStatuses[$bucket]);
        }

        // 상태·우선순위·담당자·요청자는 멀티 체크 필터 — 배열 또는 단일 값 모두 지원
        $statusArr   = array_values(array_filter((array) $request->input('status'),   fn ($v) => $v !== null && $v !== ''));
        $priorityArr = array_values(array_filter((array) $request->input('priority'), fn ($v) => $v !== null && $v !== ''));
        $assigneeArr = array_values(array_filter(array_map('intval', (array) $request->input('assignee_id')),  fn ($v) => $v > 0));
        $coloUserArr = array_values(array_filter(array_map('intval', (array) $request->input('colo_user_id')), fn ($v) => $v > 0));
        if (!empty($statusArr))   $q->whereIn('status', $statusArr);
        if (!empty($priorityArr)) $q->whereIn('priority', $priorityArr);
        if (!empty($assigneeArr)) $q->whereIn('assignee_id', $assigneeArr);
        if (!empty($coloUserArr)) $q->whereIn('colo_user_id', $coloUserArr);
        if ($m = $request->integer('menu_id'))             $q->where('menu_id', $m);
        // 요청일 범위 (from ~ to) — 단방향만 입력해도 동작
        if ($df = $request->date('date_from')) $q->whereDate('request_date', '>=', $df->toDateString());
        if ($dt = $request->date('date_to'))   $q->whereDate('request_date', '<=', $dt->toDateString());
        // company_group_id 파라미터는 권한자만 허용 (비권한자는 자기 회사로 고정됨)
        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $q->where('company_group_id', $cg);
        }
        if ($kw = trim($request->string('q')->toString())) {
            $q->where(function ($x) use ($kw) {
                $x->where('summary', 'like', "%{$kw}%")
                  ->orWhere('content', 'like', "%{$kw}%");
            });
        }

        $perPage = (int) $request->integer('per_page', 30);
        if (!in_array($perPage, [30, 50, 100], true)) {
            $perPage = 30;
        }
        $requests = $q->paginate($perPage)->withQueryString();

        // ── 통계 카운트 (접근 범위 + 회사 선택 필터 반영) ──
        // KPI 는 상태별 분포를 보여주므로 status/priority/assignee 등 자체 분포 필터는 반영하지 않음.
        // 회사 선택(company_group_id)은 "어느 회사 SR 을 보는가" 라는 컨텍스트라 KPI 도 같이 좁혀야 한다.
        $cntQ = MaintRequest::query();
        $applyAccessScope($cntQ);
        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $cntQ->where('company_group_id', $cg);
        }
        $statusCounts = $cntQ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $menus      = MaintMenu::orderBy('name')->get(['id', 'name']);
        $coloUsers  = MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $devUsers   = MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = MaintRequest::whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category')->values();

        $canFilterByCompany = $isSrPrivileged;
        $companyGroups = \DB::table('company_groups')->orderBy('name')->get(['id', 'name']);

        // 담당자 필터 노출 권한 — 관리자/SR 담당자/(어떤 프로젝트든) 매니저만
        $isProjectManager = $u && \App\Models\ProjectMember::where('user_id', $u->id)
            ->where('role', 'manager')->exists();
        $canFilterByAssignee = $isSrPrivileged || $isProjectManager;

        return view('maint-requests.index', compact(
            'requests', 'menus', 'coloUsers', 'devUsers', 'categories', 'bucket', 'perPage',
            'canFilterByCompany', 'canFilterByAssignee', 'companyGroups', 'statusCounts'
        ));
    }

    /**
     * SR 간트 보기 — 회사별 그룹, request_date~eta 로 바 표시.
     *  - index() 와 동일한 access scope·필터 적용
     *  - eta 가 null 이면 request_date + 7일을 임시 종료일로 사용
     *  - 드래그 편집은 admin / is_sr_agent 만 (뷰에서 가드)
     */
    public function gantt(Request $request)
    {
        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        $linkthelabId = \App\Models\CompanyGroup::where('name', '링크더랩')->value('id');
        $isLinkthelabMember = !$isSrPrivileged && $u && (int) $u->company_group_id === (int) $linkthelabId;

        $accessScope = null;
        if (!$isSrPrivileged) {
            $accessScope = $u?->company_group_id ? (int) $u->company_group_id : 0;
        }

        $q = MaintRequest::query()->with(['companyGroup', 'assignee']);

        if ($accessScope === 0) {
            $q->whereRaw('1=0');
        } elseif ($accessScope !== null) {
            if ($isLinkthelabMember) {
                $q->where(function ($x) use ($accessScope) {
                    $x->where('company_group_id', $accessScope)->orWhere('paid_dev_enabled', true);
                });
            } else {
                $q->where('company_group_id', $accessScope);
            }
        }

        // index() 와 동일한 필터 (간트는 보통 전체 또는 진행중을 보고 싶을 것)
        $bucket = $request->string('bucket')->toString() ?: 'in_progress';
        $bucketStatuses = self::bucketStatuses();
        if ($bucket !== 'all' && isset($bucketStatuses[$bucket])) {
            $q->whereIn('status', $bucketStatuses[$bucket]);
        }

        $statusArr   = array_values(array_filter((array) $request->input('status'),   fn ($v) => $v !== null && $v !== ''));
        $priorityArr = array_values(array_filter((array) $request->input('priority'), fn ($v) => $v !== null && $v !== ''));
        $assigneeArr = array_values(array_filter(array_map('intval', (array) $request->input('assignee_id')),  fn ($v) => $v > 0));
        $coloUserArr = array_values(array_filter(array_map('intval', (array) $request->input('colo_user_id')), fn ($v) => $v > 0));
        if (!empty($statusArr))   $q->whereIn('status', $statusArr);
        if (!empty($priorityArr)) $q->whereIn('priority', $priorityArr);
        if (!empty($assigneeArr)) $q->whereIn('assignee_id', $assigneeArr);
        if (!empty($coloUserArr)) $q->whereIn('colo_user_id', $coloUserArr);
        if ($m = $request->integer('menu_id'))             $q->where('menu_id', $m);

        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $q->where('company_group_id', $cg);
        }
        if ($df = $request->date('date_from')) $q->whereDate('request_date', '>=', $df->toDateString());
        if ($dt = $request->date('date_to'))   $q->whereDate('request_date', '<=', $dt->toDateString());
        if ($kw = trim($request->string('q')->toString())) {
            $q->where(function ($x) use ($kw) {
                $x->where('summary', 'like', "%{$kw}%")->orWhere('content', 'like', "%{$kw}%");
            });
        }

        $rows = $q
            ->orderBy('company_group_id')
            ->orderByRaw('COALESCE(gantt_sort_order, 2147483647)')
            ->orderBy('request_date')
            ->orderBy('id')
            ->get();

        // 상단 통계 칩용 — index() 와 동일하게 status 별 건수 (필터 자체 분포 영향 없도록 bucket 만 빼고 access scope + company 만 반영)
        $cntQ = MaintRequest::query();
        if ($accessScope === 0) {
            $cntQ->whereRaw('1=0');
        } elseif ($accessScope !== null) {
            if ($isLinkthelabMember) {
                $cntQ->where(function ($x) use ($accessScope) {
                    $x->where('company_group_id', $accessScope)->orWhere('paid_dev_enabled', true);
                });
            } else {
                $cntQ->where('company_group_id', $accessScope);
            }
        }
        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $cntQ->where('company_group_id', $cg);
        }
        $statusCounts = $cntQ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status')->toArray();

        $ganttTasks = $rows->map(function (MaintRequest $r) {
            $start = optional($r->request_date)->format('Y-m-d') ?? now()->toDateString();
            $end   = optional($r->eta)->format('Y-m-d') ?? \Carbon\Carbon::parse($start)->addDays(7)->toDateString();
            $progress = match ($r->status) {
                'completed' => 100,
                'reviewing' => 80,
                'in_progress', 'additional_dev' => 50,
                'ai_review' => 10,
                default => 0,
            };
            return [
                'id'            => (string) $r->id,
                'name'          => $r->summary,
                'group_name'    => $r->companyGroup?->name ?? '(미지정)',
                'start'         => $start,
                'end'           => $end,
                'progress'      => $progress,
                '_status'       => $r->status,
                '_status_label' => $this->srStatusLabel($r->status),
                '_priority'     => $r->priority ?? '',
                '_assignee'     => $r->assignee?->name ?? '-',
                '_assignee_id'  => $r->assignee_id,
                '_excel_no'     => $r->excel_no,
                '_paid_dev'     => (bool) $r->paid_dev_enabled,
            ];
        })->values();

        $companyGroups = \App\Models\CompanyGroup::orderBy('name')->get(['id', 'name']);
        $coloUsers     = MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $devUsers      = MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $canFilterByCompany = $isSrPrivileged;
        $isProjectManager   = $u && \App\Models\ProjectMember::where('user_id', $u->id)->where('role', 'manager')->exists();
        $canFilterByAssignee = $isSrPrivileged || $isProjectManager;

        return view('maint-requests.gantt', [
            'ganttTasks'         => $ganttTasks,
            'companyGroups'      => $companyGroups,
            'coloUsers'          => $coloUsers,
            'devUsers'           => $devUsers,
            'canFilterByCompany' => $canFilterByCompany,
            'canFilterByAssignee'=> $canFilterByAssignee,
            'bucket'             => $bucket,
            'isSrPrivileged'     => $isSrPrivileged,
            'statusCounts'       => $statusCounts,
        ]);
    }

    /** SR 상태 라벨 (간트 팝업/툴팁용) */
    private function srStatusLabel(?string $s): string
    {
        return match ($s) {
            'ai_review'      => 'AI 검토',
            'requested'      => '요청',
            'in_progress'    => '진행중',
            'additional_dev' => '추가 개발',
            'reviewing'      => '검토',
            'completed'      => '완료',
            default          => (string) $s,
        };
    }

    /**
     * 간트 행 순서 일괄 저장 — admin / is_sr_agent 만.
     * Body: { order: [{id, sort_order, group_name?}, ...] }
     * group_name 이 있으면 company_group 도 갱신 (이름 → id 매핑).
     */
    public function ganttReorder(Request $request)
    {
        $u = auth()->user();
        $isPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        abort_unless($isPrivileged, 403, '간트 순서 변경 권한이 없습니다.');

        $data = $request->validate([
            'order'              => 'required|array',
            'order.*.id'         => 'required|integer|exists:maint_requests,id',
            'order.*.sort_order' => 'required|integer',
            'order.*.group_name' => 'nullable|string|max:100',
        ]);

        // group_name → company_group_id 매핑 (캐시)
        $cgMap = \App\Models\CompanyGroup::pluck('id', 'name')->all();

        DB::transaction(function () use ($data, $cgMap) {
            foreach ($data['order'] as $o) {
                $update = ['gantt_sort_order' => (int) $o['sort_order']];
                if (!empty($o['group_name']) && isset($cgMap[$o['group_name']])) {
                    $update['company_group_id'] = $cgMap[$o['group_name']];
                }
                MaintRequest::where('id', $o['id'])->update($update);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * 간트 드래그 편집 — eta(+ 선택적으로 request_date) 만 갱신.
     * 권한: admin 또는 is_sr_agent.
     */
    public function ganttUpdateDates(Request $request, MaintRequest $maintRequest)
    {
        $u = auth()->user();
        $isPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        abort_unless($isPrivileged, 403, '간트 편집 권한이 없습니다.');

        $data = $request->validate([
            'start' => 'nullable|date',
            'end'   => 'nullable|date',
        ]);

        $update = [];
        if (!empty($data['start'])) $update['request_date'] = $data['start'];
        if (!empty($data['end']))   $update['eta']          = $data['end'];
        if (empty($update)) return response()->json(['ok' => true]);

        $maintRequest->update($update);
        return response()->json(['ok' => true]);
    }

    public static function bucketStatuses(): array
    {
        // 5유형(STATUSES) 기준 — 합계 = 전체
        return [
            'in_progress' => ['requested', 'in_progress', 'additional_dev'],
            'reviewing'   => ['reviewing'],
            'completed'   => ['completed'],
        ];
    }

    public function embed(MaintRequest $maintRequest)
    {
        return view('maint-requests.embed', $this->detailData($maintRequest));
    }

    /**
     * 웍스 요약을 (재)생성한 직후 AI 필드만 가벼운 PATCH 로 즉시 저장.
     * (사용자가 본 메인 저장 버튼을 누르지 않아도 ai_summary / ai_classification 이 영구화되도록)
     */
    public function updateAiSummary(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'ai_summary'              => 'nullable|string',
            'ai_summary_context_ids'  => 'nullable|array',
            'ai_classification'       => 'nullable|in:free,paid,discuss',
        ]);

        $update = [];
        if (array_key_exists('ai_summary', $data)) {
            $sum = trim((string) $data['ai_summary']);
            $update['ai_summary']             = $sum !== '' ? $sum : null;
            $update['ai_summary_at']          = $sum !== '' ? now() : null;
            $update['ai_summary_context_ids'] = $sum !== '' ? ($data['ai_summary_context_ids'] ?? null) : null;
        }
        if (array_key_exists('ai_classification', $data)) {
            $update['ai_classification'] = $data['ai_classification'];
            // paid (유상 추가 개발) 분류 시 구분 자동 매핑
            if (($data['ai_classification'] ?? null) === 'paid') {
                $update['category'] = '추가개발';
            }
        }

        if ($update) {
            $maintRequest->update($update);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * 웍스 요약 판단(ai_classification) 수동 변경 — 관리자/SR 담당자 전용.
     * 라우트 미들웨어(sr.or.admin)로 권한 보호.
     * paid 변경 시 category='추가개발' 자동 매핑(updateAiSummary 와 동일 정책).
     */
    public function updateClassification(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            // 빈 값(null) 허용 — 미분류 상태로 되돌릴 수 있어야 함
            'ai_classification' => 'nullable|in:free,paid,discuss',
        ]);

        $cls = $data['ai_classification'] ?? null;
        $update = ['ai_classification' => $cls];
        // paid 로 바뀔 때만 category 자동 매핑. 그 외(미분류/free/discuss)에서는 기존 category 보존.
        if ($cls === 'paid') {
            $update['category'] = '추가개발';
        }
        $maintRequest->update($update);

        return response()->json([
            'ok'                => true,
            'ai_classification' => $maintRequest->ai_classification,
            'category'          => $maintRequest->category,
        ]);
    }

    private function detailData(MaintRequest $maintRequest): array
    {
        $maintRequest->load([
            'menu', 'coloUser', 'assignee', 'companyGroup',
            'notes' => fn ($q) => $q->oldest('id'),
            'attachments' => fn ($q) => $q->oldest('id'),
        ]);

        // 기존 SR 에서 사용된 구분(category) distinct 목록 — 입력 보조용
        $categories = MaintRequest::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return [
            'r'          => $maintRequest,
            'menus'      => MaintMenu::orderBy('name')->get(['id', 'name']),
            'coloUsers'  => MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'devUsers'   => MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => $categories,
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'menu_id'          => 'nullable|exists:maint_menus,id',
            'menu_name'        => 'nullable|string|max:255',
            'priority'         => 'required|in:' . implode(',', MaintRequest::PRIORITIES),
            'category'         => 'nullable|string|max:100',
            'summary'          => 'required|string|max:500',
            'content'          => 'nullable|string',
            'ai_summary'       => 'nullable|string',
            'ai_summary_context_ids' => 'nullable|string', // JSON 문자열로 전송
            'ai_classification' => 'nullable|in:free,paid,discuss',
            'request_date'     => 'nullable|date',
            'eta'              => 'nullable|date',
            'colo_user_id'     => 'nullable|exists:maint_users,id',
            'colo_user_name'   => 'nullable|string|max:100',
            'assignee_id'      => 'nullable|exists:maint_users,id',
            'assignee_name'    => 'nullable|string|max:100',
            'status'           => 'nullable|in:' . implode(',', MaintRequest::STATUSES),
            'difficulty_score' => 'nullable|integer|min:1|max:5',
            'attachments'      => 'nullable|array|max:10',
            'attachments.*'    => 'file|max:10240', // 10MB / 파일
        ]);
        $this->normalizeAiSummaryFields($data);
        $this->applyClassificationToCategory($data);
        $uploaded = $request->file('attachments', []);
        unset($data['attachments']);

        // 회사는 로그인 사용자 본인 소속으로 자동 지정
        $data['company_group_id'] = auth()->user()?->company_group_id;
        abort_if(empty($data['company_group_id']), 422, '회사 소속이 없는 사용자는 SR을 등록할 수 없습니다.');

        // AI 분석은 모달 안에서 등록자가 미리 트리거 → ai_summary 가 채워진 상태로 제출됨.
        // 별도 ai_review 상태 없이 바로 'requested' 로 진입.
        $newSr = null;
        DB::transaction(function () use (&$data, &$newSr) {
            $companyGroupId       = (int) ($data['company_group_id'] ?? 0) ?: null;
            $data['menu_id']      = $this->resolveMenu($data);
            $data['colo_user_id'] = $this->resolveUser($data['colo_user_id'] ?? null, $data['colo_user_name'] ?? null, 'colo', $companyGroupId);
            $data['assignee_id']  = $this->resolveUser($data['assignee_id'] ?? null, $data['assignee_name'] ?? null, 'withworks');
            unset($data['menu_name'], $data['colo_user_name'], $data['assignee_name']);

            $data['status'] = $data['status'] ?? 'requested';

            $newSr = MaintRequest::create($data);
        });

        // 첨부파일 저장 — SR 생성 후, private 디스크 maint-attachments/{sr_id}/
        if ($newSr && !empty($uploaded)) {
            $this->storeAttachments($newSr, $uploaded);
        }

        SrNotificationService::notifySrChanged($newSr, auth()->user(), '등록');

        // 등록 직후 인덱스 리스트로 복귀 (팝업 닫힘 효과). 상세는 사용자가 리스트에서 직접 클릭.
        return redirect()->route('maint-requests.index')->with('success', '요청이 등록되었습니다.');
    }

    /**
     * ai_review 상태의 SR 을 요청자가 확인 → 'requested' 로 전환.
     *
     *   mode=as_is : AI 정리본을 채택해 ai_summary 로 승격
     *   mode=edit  : 사용자가 수정한 summary 를 ai_summary 로 저장
     *   mode=skip  : AI 정리본 미적용, 원본 그대로 진행 (ai_summary 비움)
     * AI 가 던진 질문(ai_review_questions) 에 답한 내용은 그대로 ai_review_questions 에 a 로 저장.
     */
    public function confirmAiReview(Request $request, MaintRequest $maintRequest)
    {
        abort_unless($maintRequest->status === 'ai_review', 409, 'AI 검토 대기 상태가 아닙니다.');

        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        $sameCompany = $u && (int) $u->company_group_id === (int) $maintRequest->company_group_id;
        abort_unless($isSrPrivileged || $sameCompany, 403, 'SR 을 확인할 권한이 없습니다.');

        $data = $request->validate([
            'mode'          => 'required|in:as_is,edit,skip',
            'ai_summary'    => 'nullable|string|max:8000',
            'answers'       => 'nullable|array',
            'answers.*'     => 'nullable|string|max:2000',
        ]);

        $update = ['status' => 'requested'];

        if ($data['mode'] === 'as_is') {
            $sum = trim((string) $maintRequest->ai_review_summary);
            if ($sum !== '') {
                $update['ai_summary']    = $sum;
                $update['ai_summary_at'] = now();
                $update['ai_summary_context_ids'] = null;
            }
        } elseif ($data['mode'] === 'edit') {
            $sum = trim((string) ($data['ai_summary'] ?? ''));
            if ($sum !== '') {
                $update['ai_summary']    = $sum;
                $update['ai_summary_at'] = now();
                $update['ai_summary_context_ids'] = null;
            }
        }
        // skip: ai_summary 그대로 (보통 null)

        // AI 질문 답변 병합 — 기존 questions 배열 유지하면서 a 만 채움
        if (is_array($maintRequest->ai_review_questions)) {
            $answers = (array) ($data['answers'] ?? []);
            $merged = [];
            foreach ($maintRequest->ai_review_questions as $idx => $q) {
                $merged[] = [
                    'q' => (string) ($q['q'] ?? ''),
                    'a' => isset($answers[$idx]) ? trim((string) $answers[$idx]) : (string) ($q['a'] ?? ''),
                ];
            }
            $update['ai_review_questions'] = $merged;
        }

        $update['ai_review_status'] = 'confirmed';

        $maintRequest->update($update);

        // (담당자 알림 메일은 일단 보내지 않음 — 추후 필요 시 활성화)

        if ($request->boolean('_modal')) {
            return redirect()->route('maint-requests.embed', $maintRequest)->with('success', '확인 완료 — 담당자에게 전달되었습니다.');
        }
        // 상세는 팝업으로만 노출 — 리스트로 복귀하면서 자동으로 해당 SR 팝업을 열도록 ?open 쿼리 부여
        return redirect()->route('maint-requests.index', ['open' => $maintRequest->id])->with('success', '확인 완료 — 담당자에게 전달되었습니다.');
    }

    public function update(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'menu_id'          => 'nullable|exists:maint_menus,id',
            'menu_name'        => 'nullable|string|max:255',
            'priority'         => 'required|in:' . implode(',', MaintRequest::PRIORITIES),
            'category'         => 'nullable|string|max:100',
            'summary'          => 'required|string|max:500',
            'content'          => 'nullable|string',
            'ai_summary'       => 'nullable|string',
            'ai_summary_context_ids' => 'nullable|string',
            'ai_classification' => 'nullable|in:free,paid,discuss',
            'request_date'     => 'nullable|date',
            'eta'              => 'nullable|date',
            'colo_user_id'     => 'nullable|exists:maint_users,id',
            'colo_user_name'   => 'nullable|string|max:100',
            'assignee_id'      => 'nullable|exists:maint_users,id',
            'assignee_name'    => 'nullable|string|max:100',
            'status'           => 'required|in:' . implode(',', MaintRequest::STATUSES),
            'difficulty_score' => 'nullable|integer|min:1|max:5',
            'paid_dev_enabled'     => 'nullable|boolean',
            'paid_dev_days'        => 'nullable|integer|min:0|max:9999',
            'paid_dev_cost'        => 'nullable|integer|min:0',
            'paid_dev_description' => 'nullable|string|max:5000',
            'attachments'          => 'nullable|array|max:10',
            'attachments.*'        => 'file|max:10240', // 10MB / 파일
        ]);
        $data['paid_dev_enabled'] = $request->boolean('paid_dev_enabled');
        $this->normalizeAiSummaryFields($data);
        $this->applyClassificationToCategory($data);
        $uploaded = $request->file('attachments', []);
        unset($data['attachments']);

        // 기존 첨부 개수 + 신규 추가 합산이 10개 초과 시 거절 (등록 후 삭제 불가 정책)
        if (!empty($uploaded)) {
            $existing = $maintRequest->attachments()->count();
            abort_if($existing + count($uploaded) > 10, 422, '첨부파일은 SR 당 최대 10개까지만 가능합니다.');
        }

        DB::transaction(function () use (&$data, $maintRequest) {
            $companyGroupId       = (int) ($maintRequest->company_group_id ?? 0) ?: null;
            $data['menu_id']      = $this->resolveMenu($data);
            $data['colo_user_id'] = $this->resolveUser($data['colo_user_id'] ?? null, $data['colo_user_name'] ?? null, 'colo', $companyGroupId);
            $data['assignee_id']  = $this->resolveUser($data['assignee_id'] ?? null, $data['assignee_name'] ?? null, 'withworks');
            unset($data['menu_name'], $data['colo_user_name'], $data['assignee_name']);

            if ($data['status'] === 'completed' && !$maintRequest->completed_at) {
                $data['completed_at'] = now();
            } elseif ($data['status'] !== 'completed') {
                $data['completed_at'] = null;
            }

            $maintRequest->update($data);
        });

        if (!empty($uploaded)) {
            $this->storeAttachments($maintRequest, $uploaded);
        }

        SrNotificationService::notifySrChanged($maintRequest->fresh(), auth()->user(), '수정');

        if (request()->boolean('_modal')) {
            return redirect()->route('maint-requests.embed', $maintRequest)->with('success', '수정되었습니다.');
        }
        return redirect()->route('maint-requests.index', ['open' => $maintRequest->id])->with('success', '수정되었습니다.');
    }

    public function quickUpdate(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'priority' => 'sometimes|in:' . implode(',', MaintRequest::PRIORITIES),
            'status'   => 'sometimes|in:' . implode(',', MaintRequest::STATUSES),
        ]);

        if (empty($data)) {
            return response()->json(['ok' => false, 'message' => '변경할 값이 없습니다.'], 422);
        }

        if (array_key_exists('status', $data)) {
            // 상태 변경은 링크더랩 사용자(관리자 or 링크더랩 회사 소속)만 허용
            $u = auth()->user();
            $linkthelabId = \App\Models\CompanyGroup::where('name', '링크더랩')->value('id');
            $canChangeStatus = $u && ($u->isAdmin() || (int) $u->company_group_id === (int) $linkthelabId);
            if (!$canChangeStatus) {
                return response()->json(['ok' => false, 'message' => '상태 변경 권한이 없습니다.'], 403);
            }

            if ($data['status'] === 'completed' && !$maintRequest->completed_at) {
                $data['completed_at'] = now();
            } elseif ($data['status'] !== 'completed') {
                $data['completed_at'] = null;
            }
        }

        $maintRequest->update($data);

        return response()->json([
            'ok'       => true,
            'priority' => $maintRequest->priority,
            'status'   => $maintRequest->status,
        ]);
    }

    public function destroy(MaintRequest $maintRequest)
    {
        $u = auth()->user();
        abort_unless($u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false)), 403, '삭제 권한이 없습니다.');
        // 상태가 '요청(requested)' 일 때만 삭제 가능
        abort_unless($maintRequest->status === 'requested', 422, "'요청' 상태가 아닌 SR은 삭제할 수 없습니다.");

        $maintRequest->delete();
        if (request()->boolean('_modal')) {
            return redirect()->route('maint-requests.embed.closed')->with('maint_modal_close', true);
        }
        return redirect()->route('maint-requests.index')->with('success', '삭제되었습니다.');
    }

    public function embedClosed()
    {
        return view('maint-requests.embed-closed');
    }

    public function storeNote(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'note_type' => 'required|in:colo,link',
            'body'      => 'required|string',
            'parent_id' => 'nullable|integer|exists:maint_request_notes,id',
        ]);
        // 답글의 답글 방지 — parent_id 가 또 다른 답글이면 그 답글의 부모를 사용 (한 단계로 평탄화)
        if (!empty($data['parent_id'])) {
            $parent = MaintRequestNote::find($data['parent_id']);
            abort_if(!$parent || $parent->request_id !== $maintRequest->id, 422, '잘못된 답글 대상입니다.');
            // 다른 type 의 비고에 답글 다는 것 금지
            abort_if($parent->note_type !== $data['note_type'], 422, '비고 유형이 일치하지 않습니다.');
            if ($parent->parent_id) {
                $data['parent_id'] = $parent->parent_id; // 1단계 트리 유지
            }
        }
        $data['request_id'] = $maintRequest->id;
        $note = MaintRequestNote::create($data);

        // 알림(이메일 + FCM) — 대상자 규칙은 SrNotificationService 참조
        SrNotificationService::notifyNoteAdded($maintRequest, $note, auth()->user());

        if ($request->boolean('_modal')) {
            return redirect()->route('maint-requests.embed', $maintRequest)->with('success', '비고가 추가되었습니다.');
        }
        return back()->with('success', '비고가 추가되었습니다.');
    }

    public function destroyNote(Request $request, MaintRequest $maintRequest, MaintRequestNote $note)
    {
        abort_unless($note->request_id === $maintRequest->id, 404);
        $note->delete();

        if ($request->boolean('_modal')) {
            return redirect()->route('maint-requests.embed', $maintRequest)->with('success', '비고가 삭제되었습니다.');
        }
        return back()->with('success', '비고가 삭제되었습니다.');
    }

    public function import(Request $request)
    {
        // 관리자 / SR 담당자만 허용 (UI 가드와 동일 — 직접 POST 우회 방지)
        $u = auth()->user();
        abort_unless($u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false)), 403);

        $request->validate([
            'file'             => 'required|file|mimes:xlsx,xls|max:51200',
            'company_group_id' => 'required|exists:company_groups,id',
        ]);

        $companyGroupId = (int) $request->input('company_group_id');

        try {
            $stats = $this->processImport($request->file('file')->getRealPath(), $companyGroupId);
        } catch (\Throwable $e) {
            $err = '엑셀 처리 중 오류: ' . $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $err], 500);
            }
            return back()->with('error', $err);
        }

        $msg = sprintf(
            '엑셀 업로드 완료 · 신규 %d건 추가, 중복 %d건 건너뜀 (메뉴 +%d, 사용자 +%d, 비고 +%d)',
            $stats['requests_added'], $stats['requests_skipped'],
            $stats['menus_added'], $stats['users_added'], $stats['notes_added']
        );
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $msg, 'stats' => $stats]);
        }
        return back()->with('success', $msg);
    }

    private function processImport(string $path, ?int $companyGroupId = null): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $rows = $this->collectXlsxRows($book);

        $stats = [
            'requests_added' => 0,
            'requests_skipped' => 0,
            'notes_added' => 0,
            'menus_added' => 0,
            'users_added' => 0,
            'colo_users_company_set' => 0,
        ];

        $norm = fn(string $s) => trim(preg_replace('/\s+/u', ' ', $s));

        DB::transaction(function () use ($rows, $norm, &$stats, $companyGroupId) {
            $menuCache = [];
            $userCache = [];

            $resolveMenu = function (string $name) use (&$menuCache, $norm, &$stats): int {
                $key = $norm($name);
                if (isset($menuCache[$key])) return $menuCache[$key];
                $existing = DB::table('maint_menus')->where('name', $key)->value('id');
                if ($existing) {
                    return $menuCache[$key] = (int) $existing;
                }
                $stats['menus_added']++;
                return $menuCache[$key] = DB::table('maint_menus')->insertGetId([
                    'name'       => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            };

            $resolveUser = function (?string $name, string $team) use (&$userCache, $norm, &$stats, $companyGroupId): ?int {
                $clean = $name ? $norm($name) : '';

                // 요청자(colo): 빈값/회사 User 미매칭 → NULL(선택 안함), 매칭되면 MaintUser 확보
                if ($team === 'colo') {
                    if ($clean === '') return null;

                    $matchUserId = $companyGroupId
                        ? DB::table('users')
                            ->where('company_group_id', $companyGroupId)
                            ->where('name', $clean)
                            ->value('id')
                        : null;

                    if (!$matchUserId) return null;

                    $key = "colo:{$clean}:{$companyGroupId}";
                    if (isset($userCache[$key])) return $userCache[$key];

                    $existing = DB::table('maint_users')
                        ->where('team', 'colo')
                        ->where('name', $clean)
                        ->where('company_group_id', $companyGroupId)
                        ->first(['id', 'user_id']);

                    if ($existing) {
                        if (empty($existing->user_id)) {
                            DB::table('maint_users')->where('id', $existing->id)->update([
                                'user_id'    => $matchUserId,
                                'updated_at' => now(),
                            ]);
                        }
                        return $userCache[$key] = (int) $existing->id;
                    }

                    $stats['users_added']++;
                    return $userCache[$key] = DB::table('maint_users')->insertGetId([
                        'name'             => $clean,
                        'team'             => 'colo',
                        'user_id'          => $matchUserId,
                        'company_group_id' => $companyGroupId,
                        'is_active'        => 1,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }

                // withworks (담당자): 회사 무관, 빈값이면 null
                if ($clean === '') return null;
                $key = "{$team}:{$clean}";
                if (isset($userCache[$key])) return $userCache[$key];
                $existing = DB::table('maint_users')->where('team', $team)->where('name', $clean)->first(['id']);
                if ($existing) return $userCache[$key] = (int) $existing->id;

                $stats['users_added']++;
                return $userCache[$key] = DB::table('maint_users')->insertGetId([
                    'name'             => $clean,
                    'team'             => $team,
                    'user_id'          => null,
                    'company_group_id' => null,
                    'is_active'        => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            };

            foreach ($rows as $row) {
                $menuName = $row['menu'] ?: '(미지정)';
                $summary  = mb_substr(explode("\n", $row['content'] ?: '(내용없음)')[0], 0, 500);

                // ── 중복 판정 (같은 회사 내에서만) ──
                if ($row['no'] !== null) {
                    // Sheet1: 회사 + excel_no 조합이 유일 키
                    $exists = DB::table('maint_requests')
                        ->where('company_group_id', $companyGroupId)
                        ->where('excel_no', $row['no'])
                        ->exists();
                } else {
                    // Sheet1 (2): excel_no 없음 → 회사 + 메뉴 + summary 조합으로 판정
                    $menuId = $resolveMenu($menuName);
                    $exists = DB::table('maint_requests')
                        ->where('company_group_id', $companyGroupId)
                        ->where('source_sheet', $row['sheet'])
                        ->where('menu_id', $menuId)
                        ->where('summary', $summary)
                        ->exists();
                }

                if ($exists) {
                    $stats['requests_skipped']++;
                    continue;
                }

                $menuId = $resolveMenu($menuName);
                // colo_user 는 빈값이거나 회사 User 와 매칭 안되면 NULL(선택 안함)
                $coloUserId = $resolveUser($row['colo_user'], 'colo');

                $assigneeId  = null;
                $assigneeRaw = null;
                if ($row['assignee']) {
                    $names = preg_split('/[,\n→\/]/u', $row['assignee'], -1, PREG_SPLIT_NO_EMPTY);
                    $names = array_filter(array_map('trim', $names));
                    if (count($names) > 1) {
                        $assigneeRaw = $row['assignee'];
                    }
                    $first = reset($names);
                    if ($first) {
                        $assigneeId = $resolveUser($first, 'withworks');
                    }
                }

                $status = self::mapImportStatus($row['colo_check'], $row['progress']);
                $completedAt = ($status === 'completed' && $row['eta']) ? ($row['eta'] . ' 00:00:00') : null;

                $reqId = DB::table('maint_requests')->insertGetId([
                    'excel_no'         => $row['no'],
                    'source_sheet'     => $row['sheet'],
                    'menu_id'          => $menuId,
                    'company_group_id' => $companyGroupId,
                    'request_date'     => $row['req_date'],
                    'priority'       => $row['priority'],
                    'category'       => $row['category'],
                    'summary'        => $summary,
                    'content'        => $row['content'],
                    'status'         => $status,
                    'progress_raw'   => $row['progress'],
                    'colo_check_raw' => $row['colo_check'],
                    'colo_user_id'   => $coloUserId,
                    'assignee_id'    => $assigneeId,
                    'assignee_raw'   => $assigneeRaw,
                    'eta'            => $row['eta'],
                    'grid_refresh'   => $row['grid'],
                    'completed_at'   => $completedAt,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $stats['requests_added']++;

                if ($row['note_colo']) {
                    DB::table('maint_request_notes')->insert([
                        'request_id' => $reqId, 'note_type' => 'colo', 'body' => $row['note_colo'],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $stats['notes_added']++;
                }
                if ($row['note_link']) {
                    DB::table('maint_request_notes')->insert([
                        'request_id' => $reqId, 'note_type' => 'link', 'body' => $row['note_link'],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $stats['notes_added']++;
                }
            }

            if ($stats['requests_added'] > 0) {
                DB::statement('UPDATE maint_menus m SET request_cnt = (SELECT COUNT(*) FROM maint_requests r WHERE r.menu_id = m.id)');
            }
        });

        return $stats;
    }

    private function collectXlsxRows($book): array
    {
        $rows = [];

        $sheet1 = $book->getSheetByName('Sheet1');
        if ($sheet1) {
            $last = $sheet1->getHighestDataRow();
            for ($r = 2; $r <= $last; $r++) {
                $arr = $sheet1->rangeToArray("A{$r}:N{$r}", null, false, false)[0];
                [$no, $reqDate, $coloUser, $coloCheck, $priority, $menu, $content, $category, $progress, $assignee, $eta, $noteColo, $noteLink, $grid] = $arr;
                if (!is_numeric($no)) continue;

                // excel_no는 있어도 실제 내용(메뉴·요청내용)이 모두 비어있으면 스킵
                if (trim((string) $menu) === '' && trim((string) $content) === '') continue;

                $rows[] = [
                    'sheet'      => 'sheet1',
                    'no'         => (int) $no,
                    'req_date'   => self::parseXlsxDate($reqDate),
                    'colo_user'  => self::normStr($coloUser, 100),
                    'colo_check' => self::normStr($coloCheck, 50),
                    'priority'   => self::mapImportPriority($priority),
                    'menu'       => self::normStr($menu, 255),
                    'content'    => self::normStr($content),
                    'category'   => self::normStr($category, 100),
                    'progress'   => self::normStr($progress, 100),
                    'assignee'   => self::normStr($assignee, 100),
                    'eta'        => self::parseXlsxDate($eta),
                    'note_colo'  => self::normStr($noteColo),
                    'note_link'  => self::normStr($noteLink),
                    'grid'       => self::normStr($grid, 100),
                ];
            }
        }

        $sheet2 = $book->getSheetByName('Sheet1 (2)');
        if ($sheet2) {
            $last = $sheet2->getHighestDataRow();
            for ($r = 2; $r <= $last; $r++) {
                $arr = $sheet2->rangeToArray("A{$r}:J{$r}", null, false, false)[0];
                [$dummy, $menu, $content, $progress, $assignee, $eta, $noteColo, $noteLink, $coloCheck, $grid] = $arr;
                if (trim((string) $menu) === '' && trim((string) $content) === '') continue;

                $rows[] = [
                    'sheet'      => 'sheet1_2',
                    'no'         => null,
                    'req_date'   => null,
                    'colo_user'  => null,
                    'colo_check' => self::normStr($coloCheck, 50),
                    'priority'   => 'normal',
                    'menu'       => self::normStr($menu, 255),
                    'content'    => self::normStr($content),
                    'category'   => null,
                    'progress'   => self::normStr($progress, 100),
                    'assignee'   => self::normStr($assignee, 100),
                    'eta'        => self::parseXlsxDate($eta),
                    'note_colo'  => self::normStr($noteColo),
                    'note_link'  => self::normStr($noteLink),
                    'grid'       => self::normStr($grid, 100),
                ];
            }
        }

        return $rows;
    }

    private static function mapImportPriority(?string $raw): string
    {
        $s = trim((string) $raw);
        if ($s === '') return 'normal';
        // '초긴급' 은 'urgent' 로 통합됨
        if (mb_strpos($s, '재확인') !== false) return 'recheck';
        if (mb_strpos($s, '긴급')   !== false) return 'urgent';
        return 'normal';
    }

    private static function mapImportStatus(?string $coloCheck, ?string $progress): string
    {
        $c = trim((string) $coloCheck);
        $p = trim((string) $progress);
        if ($c === '완료') return 'completed';

        $text = $p !== '' ? $p : $c;
        if ($text === '') return 'requested';

        // 5유형으로 매핑 (구 키워드들 → reviewing 으로 합쳐짐)
        if (mb_strpos($text, '완료')     !== false) return 'completed';
        if (mb_strpos($text, '답변')     !== false) return 'reviewing';
        if (mb_strpos($text, '재확인')   !== false) return 'reviewing';
        if (mb_strpos($text, '검토')     !== false) return 'reviewing';
        if (mb_strpos($text, '확인')     !== false) return 'reviewing'; // 확인요청/대기/필요
        if (mb_strpos($text, '논의')     !== false) return 'reviewing';
        if (mb_strpos($text, '보류')     !== false) return 'reviewing';
        if (mb_strpos($text, '파일')     !== false) return 'reviewing';
        if (mb_strpos($text, '추가 개발')!== false || mb_strpos($text, '추가개발') !== false) return 'additional_dev';
        if (mb_strpos($text, '개발예정') !== false) return 'in_progress';
        if (mb_strpos($text, '개발대기') !== false) return 'in_progress';
        if (mb_strpos($text, '진행')     !== false) return 'in_progress';
        if (mb_strpos($text, '요청')     !== false) return 'requested';   // 추가요청 포함
        return 'requested';
    }

    private static function parseXlsxDate($cell): ?string
    {
        if ($cell === null || $cell === '') return null;
        if (is_numeric($cell)) {
            try { return XlsxDate::excelToDateTimeObject((float) $cell)->format('Y-m-d'); }
            catch (\Throwable $e) { return null; }
        }
        $s = trim((string) $cell);
        if ($s === '') return null;
        try { return \Carbon\Carbon::parse($s)->format('Y-m-d'); }
        catch (\Throwable $e) { return null; }
    }

    private static function normStr($v, int $max = 0): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        if ($s === '' || $s === '#VALUE!') return null;
        if ($max > 0 && mb_strlen($s) > $max) {
            $s = mb_substr($s, 0, $max);
        }
        return $s;
    }

    private function resolveMenu(array $data): ?int
    {
        if (!empty($data['menu_id'])) {
            return (int) $data['menu_id'];
        }
        $name = trim((string) ($data['menu_name'] ?? ''));
        // 모달에서 메뉴를 비워두는 경우가 있으므로 엑셀 임포트와 동일하게 '(미지정)' 으로 폴백.
        // maint_requests.menu_id 는 NOT NULL 이라 null 반환하면 INSERT 가 실패한다.
        if ($name === '') {
            $name = '(미지정)';
        }
        return MaintMenu::firstOrCreate(['name' => $name])->id;
    }

    /**
     * MaintUser 해석/생성.
     *   team='colo' 의 경우 colo_user 는 콜로플라스트 전용이 아니라 SR 의 요청자(비 SR 담당자) —
     *   $companyGroupId 가 주어지면 같은 이름·같은 회사의 User 가 있을 때 user_id 도 자동 연결.
     *
     * NOTE: maint_users 의 유니크 키는 (team, name) 이므로 매칭 시에도 이 두 컬럼만 사용해야 한다.
     *       company_group_id 를 조건에 넣으면 기존 행이 NULL 회사로 만들어진 경우 매칭 실패 →
     *       INSERT 시도 → UK 위반 (1062). 대신 누락된 company_group_id / user_id 는 사후 patch.
     */
    private function resolveUser(?int $id, ?string $name, string $team, ?int $companyGroupId = null): ?int
    {
        if ($id) {
            return $id;
        }
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = MaintUser::where('team', $team)->where('name', $name)->first();

        if ($existing) {
            // 기존 행에 누락된 정보만 보강 (덮어쓰지 않음)
            if ($team === 'colo' && $companyGroupId) {
                $patch = [];
                if (empty($existing->company_group_id)) {
                    $patch['company_group_id'] = $companyGroupId;
                }
                if (empty($existing->user_id)) {
                    $userId = \App\Models\User::where('company_group_id', $companyGroupId)
                        ->where('name', $name)
                        ->value('id');
                    if ($userId) $patch['user_id'] = $userId;
                }
                if ($patch) $existing->update($patch);
            }
            return $existing->id;
        }

        $data = ['team' => $team, 'name' => $name, 'is_active' => true];
        if ($team === 'colo' && $companyGroupId) {
            $data['company_group_id'] = $companyGroupId;
            $userId = \App\Models\User::where('company_group_id', $companyGroupId)
                ->where('name', $name)
                ->value('id');
            if ($userId) $data['user_id'] = $userId;
        }

        return MaintUser::create($data)->id;
    }

    /**
     * 폼에서 hidden input 으로 받은 ai_summary_context_ids(JSON 문자열) 를 array 로 변환
     * 후 ai_summary_at 자동 기록. ai_summary 비어있으면 모든 ai_summary_* 칼럼 비움 (요약 제거).
     */
    /**
     * 업로드된 파일들을 private 디스크에 저장하고 maint_request_attachments 행 생성.
     * 경로: maint-attachments/{sr_id}/{timestamp}_{random}.{ext}
     * 원본 파일명은 DB 의 original_name 컬럼에 보존.
     */
    private function storeAttachments(MaintRequest $sr, array $files): void
    {
        $userId = auth()->id();
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;
            $path = $file->store('maint-attachments/' . $sr->id, 'local');
            MaintRequestAttachment::create([
                'request_id'    => $sr->id,
                'uploaded_by'   => $userId,
                'original_name' => $file->getClientOriginalName(),
                'disk'          => 'local',
                'path'          => $path,
                'size'          => $file->getSize() ?: 0,
                'mime'          => $file->getMimeType(),
            ]);
        }
    }

    /**
     * 첨부파일 다운로드 — 라우트는 signed 미들웨어로 보호되므로 URL::signedRoute 만 통과시킴.
     * 추가로 SR 열람 권한도 검사 (해당 회사 또는 SR 담당자).
     */
    public function downloadAttachment(MaintRequestAttachment $attachment)
    {
        $attachment->loadMissing('request');
        $sr = $attachment->request;
        abort_unless($sr, 404);

        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        $sameCompany = $u && (int) $u->company_group_id === (int) $sr->company_group_id;
        abort_unless($isSrPrivileged || $sameCompany, 403, '이 SR 의 첨부파일을 다운로드할 권한이 없습니다.');

        $disk = Storage::disk($attachment->disk ?: 'local');
        abort_unless($disk->exists($attachment->path), 404, '파일이 존재하지 않습니다.');

        return $disk->download($attachment->path, $attachment->original_name);
    }

    /**
     * 웍스 요약 분류 결과(ai_classification)가 'paid' (유상 추가 개발) 이면
     * 구분(category) 을 '추가개발' 로 자동 매핑. 그 외 분류는 기존 category 유지.
     */
    private function applyClassificationToCategory(array &$data): void
    {
        if (!array_key_exists('ai_classification', $data)) return;
        if (($data['ai_classification'] ?? null) === 'paid') {
            $data['category'] = '추가개발';
        }
    }

    private function normalizeAiSummaryFields(array &$data): void
    {
        if (!array_key_exists('ai_summary', $data)) return;

        $sum = trim((string) ($data['ai_summary'] ?? ''));
        if ($sum === '') {
            $data['ai_summary']             = null;
            $data['ai_summary_at']          = null;
            $data['ai_summary_context_ids'] = null;
            return;
        }

        $data['ai_summary']    = $sum;
        $data['ai_summary_at'] = now();

        $rawIds = $data['ai_summary_context_ids'] ?? null;
        if (is_string($rawIds) && $rawIds !== '') {
            $decoded = json_decode($rawIds, true);
            $data['ai_summary_context_ids'] = is_array($decoded) ? $decoded : null;
        } elseif (!is_array($rawIds)) {
            $data['ai_summary_context_ids'] = null;
        }
    }

    /**
     * [웍스 요약 생성] AJAX 엔드포인트.
     *
     * 신규(create) / 수정(edit) 양쪽에서 호출 가능. 폼이 아직 저장 안 된 상태라도
     * body 의 summary / content / menu_id / category 로 transient MaintRequest 를
     * 만들어 SrSummaryService 에 전달. company_group_id 는 로그인 사용자 소속으로 강제.
     */
    public function worksSummary(Request $request, SrSummaryService $service)
    {
        $data = $request->validate([
            'id'        => 'nullable|integer|exists:maint_requests,id',
            'summary'   => 'nullable|string|max:500',
            'content'   => 'nullable|string',
            'menu_id'   => 'nullable|integer|exists:maint_menus,id',
            'menu_name' => 'nullable|string|max:255',
            'category'  => 'nullable|string|max:100',
            'priority'  => 'nullable|in:' . implode(',', MaintRequest::PRIORITIES),
        ]);

        // 권한 — index 의 access scope 로직과 동일하게 처리.
        // 권한자(admin / sr_agent): 모든 회사 SR 접근, 본인 company_group_id 없어도 OK.
        // 비권한자: 자기 회사 SR 만, company_group_id 필수.
        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
        $userCompanyId  = $u?->company_group_id;

        // 기존 SR 이면 로드, 아니면 transient 인스턴스로 SrSummaryService 에 넘김.
        if (!empty($data['id'])) {
            $req = MaintRequest::with('menu')->findOrFail($data['id']);

            // 비권한자는 자기 회사 SR 만 처리 가능
            if (!$isSrPrivileged) {
                abort_if(empty($userCompanyId) || (int) $req->company_group_id !== (int) $userCompanyId, 403);
            }

            // 폼에서 사용자가 막 수정한 값으로 덮어 컨텍스트 일치
            $req->summary  = $data['summary']  ?? $req->summary;
            $req->content  = $data['content']  ?? $req->content;
            $req->category = $data['category'] ?? $req->category;
            $req->menu_id  = $this->resolveMenu($data) ?? $req->menu_id;
            $req->priority = $data['priority'] ?? $req->priority;
            // company_group_id 는 SR 원본 그대로 유지 (유사 검색 컨텍스트가 SR 소속 회사 기준이 되도록)
        } else {
            // 신규(create) 시: 비권한자는 자기 회사로 강제, 권한자는 본인 회사 또는 null 허용
            abort_if(!$isSrPrivileged && empty($userCompanyId), 422, '회사 소속이 없는 사용자는 사용할 수 없습니다.');

            $req = new MaintRequest([
                'summary'          => $data['summary']  ?? '',
                'content'          => $data['content']  ?? '',
                'category'         => $data['category'] ?? null,
                'priority'         => $data['priority'] ?? 'normal',
                'company_group_id' => $userCompanyId,
                'menu_id'          => $this->resolveMenu($data),
            ]);
            if ($req->menu_id) {
                $req->setRelation('menu', MaintMenu::find($req->menu_id));
            }
        }

        if (trim((string) $req->content) === '' && trim((string) $req->summary) === '') {
            return response()->json([
                'ok'      => false,
                'message' => '요약 또는 상세 내용을 먼저 입력하세요.',
            ], 422);
        }

        try {
            $result = $service->summarize($req);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => '웍스 요약 생성 실패: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok'             => true,
            'summary'        => $result['summary'],
            'context_ids'    => $result['context_ids'],
            'provider'       => $result['provider'],
            'classification' => $result['classification'] ?? null,
        ]);
    }

    /**
     * SR 상세 내용 리치 에디터의 이미지 업로드 (paste / 툴바).
     * storage/app/public/maint-sr/images 에 저장 후 공개 URL 반환.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',  // 5MB
        ]);
        $path = $request->file('image')->store('maint-sr/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    /**
     * SR 요청 엑셀 다운로드.
     * - 현재 사용자가 접근 가능한 모든 SR (권한자: 전체 또는 필터, 비권한자: 본인 회사)
     * - 시트 1: 대시보드 (KPI · 상태/우선순위 분포)
     * - 시트 2: 상세 리스트 (헤더 스타일링, freeze, autofilter, 자동 폭)
     * - 파일명: SR_요청_YYYYMMDD-HHmmss.xlsx
     */
    public function exportExcel(Request $request)
    {
        $u = auth()->user();
        $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));

        // 비권한자는 본인 회사 SR만
        $q = MaintRequest::query()
            ->with(['menu', 'coloUser', 'assignee', 'companyGroup'])
            ->latest('id');
        if (!$isSrPrivileged) {
            if (!$u?->company_group_id) abort(403, '회사 소속 없는 사용자는 다운로드할 수 없습니다.');
            $q->where('company_group_id', $u->company_group_id);
        } elseif ($cg = $request->integer('company_group_id')) {
            $q->where('company_group_id', $cg);
        }

        // 필터(현재 화면 상태 그대로 반영) — index() 와 동일하게 status/priority/담당자는 배열 멀티값 지원
        $statusArr   = array_values(array_filter((array) $request->input('status'),   fn ($v) => $v !== null && $v !== ''));
        $priorityArr = array_values(array_filter((array) $request->input('priority'), fn ($v) => $v !== null && $v !== ''));
        $assigneeArr = array_values(array_filter(array_map('intval', (array) $request->input('assignee_id')),  fn ($v) => $v > 0));
        $coloUserArr = array_values(array_filter(array_map('intval', (array) $request->input('colo_user_id')), fn ($v) => $v > 0));
        if (!empty($statusArr))   $q->whereIn('status', $statusArr);
        if (!empty($priorityArr)) $q->whereIn('priority', $priorityArr);
        if (!empty($assigneeArr)) $q->whereIn('assignee_id', $assigneeArr);
        if (!empty($coloUserArr)) $q->whereIn('colo_user_id', $coloUserArr);
        if ($m = $request->integer('menu_id'))             $q->where('menu_id', $m);
        if ($df = $request->date('date_from')) $q->whereDate('request_date', '>=', $df->toDateString());
        if ($dt = $request->date('date_to'))   $q->whereDate('request_date', '<=', $dt->toDateString());
        if ($kw = trim((string) $request->input('q', ''))) {
            $q->where(function ($x) use ($kw) {
                $x->where('summary', 'like', "%{$kw}%")
                  ->orWhere('content', 'like', "%{$kw}%");
            });
        }
        $bucket = (string) $request->input('bucket', '');
        $bucketStatuses = self::bucketStatuses();
        if ($bucket && $bucket !== 'all' && isset($bucketStatuses[$bucket])) {
            $q->whereIn('status', $bucketStatuses[$bucket]);
        }

        $rows = $q->get();

        // 상태 카운트
        $statusCounts = $rows->groupBy('status')->map->count();
        $priorityCounts = $rows->groupBy('priority')->map->count();

        // PhpSpreadsheet 작성
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ── 시트 1: 대시보드 ──
        $dash = $book->getActiveSheet();
        $dash->setTitle('대시보드');

        $statusLabels = [
            'requested'      => '요청',
            'in_progress'    => '진행중',
            'additional_dev' => '추가 개발',
            'reviewing'      => '검토',
            'completed'      => '완료',
        ];
        $priorityLabels = ['normal' => '일반', 'urgent' => '긴급', 'recheck' => '재확인'];
        $bucketDefs = self::bucketStatuses();

        // 컬럼 폭 (카드형 레이아웃 8컬럼)
        foreach (['A','B','C','D','E','F','G','H'] as $col) {
            $dash->getColumnDimension($col)->setWidth(16);
        }

        // ── 헤더 ──
        $dash->setCellValue('A1', 'SR 요청 대시보드');
        $dash->mergeCells('A1:H1');
        $dash->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F86EF']],
            'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
        ]);
        $dash->getRowDimension(1)->setRowHeight(40);

        $companyName = $u?->companyGroup?->name ?: ($isSrPrivileged ? '전체 회사' : '');
        $dash->setCellValue('A2', '회사: ' . ($companyName ?: '-') . '   |   추출: ' . now()->format('Y-m-d H:i'));
        $dash->mergeCells('A2:H2');
        $dash->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F8FAFC']],
        ]);
        $dash->getRowDimension(2)->setRowHeight(22);

        // ── 빈 행 ──
        $dash->getRowDimension(3)->setRowHeight(12);

        // ── KPI 카드 4종 (화면 상단과 동일) ──
        $bucketCount = fn(array $statuses) => $rows->whereIn('status', $statuses)->count();
        $kpis = [
            ['col'=>'A', 'span'=>2, 'label'=>'전체',      'value'=>$rows->count(),                 'bg'=>'EEF2FF', 'fg'=>'3730A3'],
            ['col'=>'C', 'span'=>2, 'label'=>'진행/예정', 'value'=>$bucketCount($bucketDefs['in_progress']), 'bg'=>'FEF3C7', 'fg'=>'92400E'],
            ['col'=>'E', 'span'=>2, 'label'=>'검토중',    'value'=>$bucketCount($bucketDefs['reviewing']),   'bg'=>'FFEDD5', 'fg'=>'9A3412'],
            ['col'=>'G', 'span'=>2, 'label'=>'완료',      'value'=>$bucketCount($bucketDefs['completed']),   'bg'=>'D1FAE5', 'fg'=>'065F46'],
        ];
        foreach ($kpis as $k) {
            $colStart = $k['col'];
            $colEnd = chr(ord($k['col']) + $k['span'] - 1);
            // 라벨 카드 상단
            $dash->setCellValue($colStart . '4', $k['label']);
            $dash->mergeCells("{$colStart}4:{$colEnd}4");
            $dash->getStyle("{$colStart}4")->applyFromArray([
                'font' => ['size' => 11, 'color' => ['rgb' => $k['fg']], 'bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $k['bg']]],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['top' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']], 'left' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']], 'right' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            // 큰 숫자
            $dash->setCellValue($colStart . '5', number_format($k['value']));
            $dash->mergeCells("{$colStart}5:{$colEnd}5");
            $dash->getStyle("{$colStart}5")->applyFromArray([
                'font' => ['size' => 28, 'color' => ['rgb' => $k['fg']], 'bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $k['bg']]],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['bottom' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']], 'left' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']], 'right' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }
        $dash->getRowDimension(4)->setRowHeight(28);
        $dash->getRowDimension(5)->setRowHeight(56);
        $dash->getRowDimension(6)->setRowHeight(14);

        // ── 상태별·우선순위별 상세 ──
        $dash->setCellValue('A7', '상태별 상세');
        $dash->mergeCells('A7:D7');
        $dash->setCellValue('E7', '우선순위별');
        $dash->mergeCells('E7:H7');
        $dash->getStyle('A7:H7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '484F5E']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $dash->getRowDimension(7)->setRowHeight(26);

        // 상태별 (2개씩 두 페어 = A/B, C/D)
        $statusKeys = array_keys($statusLabels);
        $rowR = 8;
        for ($i = 0; $i < count($statusKeys); $i += 2) {
            $dash->setCellValue("A{$rowR}", $statusLabels[$statusKeys[$i]]);
            $dash->setCellValue("B{$rowR}", $statusCounts[$statusKeys[$i]] ?? 0);
            if (isset($statusKeys[$i + 1])) {
                $dash->setCellValue("C{$rowR}", $statusLabels[$statusKeys[$i + 1]]);
                $dash->setCellValue("D{$rowR}", $statusCounts[$statusKeys[$i + 1]] ?? 0);
            }
            $rowR++;
        }
        $statusEndRow = $rowR - 1;

        // 우선순위 (E,F=라벨, G,H=값)
        $priRow = 8;
        foreach ($priorityLabels as $k => $label) {
            $dash->setCellValue("E{$priRow}", $label);
            $dash->mergeCells("E{$priRow}:F{$priRow}");
            $dash->setCellValue("G{$priRow}", $priorityCounts[$k] ?? 0);
            $dash->mergeCells("G{$priRow}:H{$priRow}");
            $priRow++;
        }
        $priEndRow = $priRow - 1;

        $endRow = max($statusEndRow, $priEndRow);
        $dash->getStyle("A8:H{$endRow}")->applyFromArray([
            'font' => ['size' => 11, 'color' => ['rgb' => '374151']],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => 'center'],
        ]);
        $dash->getStyle("A8:A{$statusEndRow}")->getFont()->setBold(true);
        $dash->getStyle("C8:C{$statusEndRow}")->getFont()->setBold(true);
        $dash->getStyle("E8:F{$priEndRow}")->getFont()->setBold(true);
        $dash->getStyle("B8:B{$statusEndRow}")->getAlignment()->setHorizontal('right');
        $dash->getStyle("D8:D{$statusEndRow}")->getAlignment()->setHorizontal('right');
        $dash->getStyle("G8:H{$priEndRow}")->getAlignment()->setHorizontal('right');
        for ($r = 8; $r <= $endRow; $r++) {
            $dash->getRowDimension($r)->setRowHeight(22);
        }

        // ── 시트 2: 상세 리스트 ──
        $list = $book->createSheet();
        $list->setTitle('SR 리스트');

        $headers = ['#', '회사', '메뉴', '우선순위', '상태', '구분', '요약', '콜로 담당', '링크더랩 담당', '요청일', '완료예정', '완료일', '등록일'];
        $list->fromArray($headers, null, 'A1');

        $headRange = 'A1:' . chr(64 + count($headers)) . '1';
        $list->getStyle($headRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F86EF']],
            'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => '006CCA']]],
        ]);
        $list->getRowDimension(1)->setRowHeight(28);

        $r = 2;
        foreach ($rows as $sr) {
            $list->fromArray([
                $sr->id,
                $sr->companyGroup?->name ?? '',
                $sr->menu?->name ?? '',
                $priorityLabels[$sr->priority] ?? $sr->priority,
                $statusLabels[$sr->status] ?? $sr->status,
                $sr->category ?? '',
                $sr->summary ?? '',
                $sr->coloUser?->name ?? '',
                $sr->assignee?->name ?? '',
                optional($sr->request_date)->format('Y-m-d') ?? '',
                optional($sr->eta)->format('Y-m-d') ?? '',
                optional($sr->completed_at)->format('Y-m-d') ?? '',
                optional($sr->created_at)->format('Y-m-d H:i') ?? '',
            ], null, "A{$r}");

            // 우선순위 배경색
            $priColor = ['urgent' => 'FFEDED', 'recheck' => 'FFF4ED'][$sr->priority] ?? null;
            if ($priColor) {
                $list->getStyle("D{$r}")->getFill()->setFillType('solid')->getStartColor()->setRGB($priColor);
            }
            // 상태 배경색
            $statColor = [
                'requested'      => 'DBEAFE',
                'in_progress'    => 'FEF3C7',
                'additional_dev' => 'EDE9FE',
                'reviewing'      => 'FFEDD5',
                'completed'      => 'D1FAE5',
            ][$sr->status] ?? null;
            if ($statColor) {
                $list->getStyle("E{$r}")->getFill()->setFillType('solid')->getStartColor()->setRGB($statColor);
            }
            $r++;
        }
        $endRow = $r - 1;
        if ($endRow >= 2) {
            $list->getStyle("A2:M{$endRow}")
                ->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setRGB('E5E7EB');
        }

        // 자동 폭 + freeze + autofilter
        foreach (range('A', 'M') as $col) {
            $list->getColumnDimension($col)->setAutoSize(true);
        }
        $list->freezePane('A2');
        $list->setAutoFilter("A1:M{$endRow}");

        // 첫 시트(대시보드) 를 활성 시트로
        $book->setActiveSheetIndex(0);

        // 다운로드 응답
        $filename = 'SR_요청_' . now()->format('Ymd-His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * 추가 개발(유상) 매니저 전송 — 매니저 + 요청자 + 링크더랩 담당자에게 이메일,
     * SR 상태를 'additional_dev' 로 변경, paid_dev_sent_at 기록.
     */
    public function sendToManager(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'days'        => 'required|integer|min:0|max:9999',
            'cost'        => 'required|integer|min:0',
            'description' => 'required|string|max:5000',
        ]);

        $maintRequest->loadMissing(['coloUser', 'assignee', 'companyGroup']);

        // SR 요청자 식별 — 콜로 담당자(coloUser) 이름과 회사 매칭
        $requesterUser = null;
        if ($maintRequest->coloUser && $maintRequest->company_group_id) {
            $requesterUser = \App\Models\User::where('company_group_id', $maintRequest->company_group_id)
                ->where('name', $maintRequest->coloUser->name)
                ->whereNotNull('email')
                ->first();
        }
        if (!$requesterUser) {
            return response()->json(['ok' => false, 'message' => 'SR 요청자(회사 측 담당자)를 가입된 사용자로 매핑할 수 없어 매니저를 찾을 수 없습니다.'], 422);
        }

        // 매니저 = 요청자 회사의 manager 권한 사용자
        $managers = \App\Models\User::where('company_group_id', $requesterUser->company_group_id)
            ->where(function ($q) {
                $q->where('role', 'manager')->orWhere('role', 'admin');
            })
            ->whereNotNull('email')
            ->get();
        if ($managers->isEmpty()) {
            return response()->json(['ok' => false, 'message' => '요청자 회사에 매니저 권한 사용자가 없습니다.'], 422);
        }

        // 링크더랩 담당자
        $devUser = null;
        if ($maintRequest->assignee) {
            $devUser = \App\Models\User::where('is_sr_agent', true)
                ->where('name', $maintRequest->assignee->name)
                ->whereNotNull('email')
                ->first();
        }

        $recipients = $managers->pluck('email')->all();
        $recipients[] = $requesterUser->email;
        if ($devUser?->email) $recipients[] = $devUser->email;
        $recipients = array_values(array_unique(array_filter($recipients, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));

        if (empty($recipients)) {
            return response()->json(['ok' => false, 'message' => '유효한 수신자 이메일이 없습니다.'], 422);
        }

        // 저장 + 상태 변경
        $maintRequest->update([
            'paid_dev_enabled'    => true,
            'paid_dev_days'       => (int) $data['days'],
            'paid_dev_cost'       => (int) $data['cost'],
            'paid_dev_description'=> $data['description'],
            'paid_dev_sent_at'    => now(),
            'status'              => 'additional_dev',
        ]);

        try {
            Mail::send(new \App\Mail\PaidDevRequestMail($maintRequest->fresh(), $recipients, $requesterUser->name));
            return response()->json(['ok' => true, 'message' => '매니저(' . $managers->count() . '명)에게 전송했습니다. SR 상태가 \'추가 개발\'로 변경되었습니다.']);
        } catch (\Throwable $e) {
            Log::warning('PaidDev 메일 발송 실패: ' . $e->getMessage(), ['sr_id' => $maintRequest->id]);
            return response()->json(['ok' => true, 'message' => '저장은 완료됐으나 이메일 발송 실패: ' . $e->getMessage()]);
        }
    }

}
