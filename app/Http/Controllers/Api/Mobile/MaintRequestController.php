<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\Maint\MaintMenu;
use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestNote;
use App\Services\Maint\SrNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintRequestController extends Controller
{
    /**
     * 5유형 상태 → 버킷 3개 매핑 (웹 MaintRequestController::bucketStatuses 와 동일)
     */
    private const BUCKET_STATUSES = [
        'in_progress' => ['requested', 'in_progress', 'additional_dev'],
        'reviewing'   => ['reviewing'],
        'completed'   => ['completed'],
    ];

    public function index(Request $request): JsonResponse
    {
        [$applyScope, $accessible] = $this->resolveAccessScope($request->user());
        if (!$accessible) {
            return response()->json([
                'data'          => [],
                'meta'          => ['current_page' => 1, 'last_page' => 1, 'per_page' => 0, 'total' => 0],
                'status_counts' => new \stdClass(),
                'bucket'        => $request->string('bucket')->toString() ?: 'in_progress',
            ]);
        }

        $bucket = $request->string('bucket')->toString() ?: 'in_progress';

        $q = MaintRequest::query()->with(['menu:id,name', 'coloUser:id,name', 'assignee:id,name'])
            ->withCount('notes');

        $applyScope($q);

        // 권한자(admin/is_sr_agent)만 회사그룹 지정 필터 허용. 일반 사용자는 본인 회사로 이미 고정.
        $isSrPrivileged = $request->user() && ($request->user()->isAdmin() || (bool) ($request->user()->is_sr_agent ?? false));
        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $q->where('company_group_id', $cg);
        }

        if ($bucket !== 'all' && isset(self::BUCKET_STATUSES[$bucket])) {
            $q->whereIn('status', self::BUCKET_STATUSES[$bucket]);
        }

        // 멀티 선택 필터
        $statusArr   = array_values(array_filter((array) $request->input('status'),   fn ($v) => $v !== null && $v !== ''));
        $priorityArr = array_values(array_filter((array) $request->input('priority'), fn ($v) => $v !== null && $v !== ''));
        if (!empty($statusArr))   $q->whereIn('status', $statusArr);
        if (!empty($priorityArr)) $q->whereIn('priority', $priorityArr);
        if ($menuId = $request->integer('menu_id')) {
            $q->where('menu_id', $menuId);
        }
        if ($df = $request->date('date_from')) $q->whereDate('request_date', '>=', $df->toDateString());
        if ($dt = $request->date('date_to'))   $q->whereDate('request_date', '<=', $dt->toDateString());
        if ($kw = trim($request->string('q')->toString())) {
            $q->where(function ($x) use ($kw) {
                $x->where('summary', 'like', "%{$kw}%")
                  ->orWhere('content', 'like', "%{$kw}%");
            });
        }

        // 정렬: 최신순 고정 (모바일은 단순화)
        $q->latest('id');

        $perPage = (int) $request->integer('per_page', 20);
        if ($perPage < 1)  $perPage = 20;
        if ($perPage > 50) $perPage = 50;
        $page = $q->paginate($perPage);

        // KPI 카운트 — 접근 범위 + 회사 선택 필터 반영 (웹 의미 동일)
        $cntQ = MaintRequest::query();
        $applyScope($cntQ);
        if ($isSrPrivileged && ($cg = $request->integer('company_group_id'))) {
            $cntQ->where('company_group_id', $cg);
        }
        $statusCounts = $cntQ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return response()->json([
            'data' => array_map(fn ($r) => $this->listResource($r), $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
            'status_counts' => (object) $statusCounts,
            'bucket'        => $bucket,
        ]);
    }

    public function show(Request $request, MaintRequest $maintRequest): JsonResponse
    {
        [$applyScope, $accessible] = $this->resolveAccessScope($request->user());
        abort_unless($accessible, 403);
        $this->assertVisible($request->user(), $maintRequest);

        $maintRequest->load([
            'menu:id,name',
            'coloUser:id,name',
            'assignee:id,name',
            'companyGroup:id,name',
            'notes' => fn ($q) => $q->oldest('id'),
        ]);

        return response()->json($this->detailResource($maintRequest));
    }

    public function menus(Request $request): JsonResponse
    {
        // 메뉴 목록 (필터 옵션용) — 인증 사용자 누구나 조회 가능
        $menus = MaintMenu::orderBy('name')->get(['id', 'name']);
        return response()->json($menus);
    }

    /**
     * 회사그룹 목록 — 권한자(admin/is_sr_agent)에게만 노출.
     * 일반 사용자는 자기 회사로 자동 고정되므로 호출 불필요.
     */
    public function companyGroups(Request $request): JsonResponse
    {
        $u = $request->user();
        abort_unless($u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false)), 403);
        $groups = CompanyGroup::orderBy('name')->get(['id', 'name']);
        return response()->json($groups);
    }

    public function storeNote(Request $request, MaintRequest $maintRequest): JsonResponse
    {
        [$applyScope, $accessible] = $this->resolveAccessScope($request->user());
        abort_unless($accessible, 403);
        $this->assertVisible($request->user(), $maintRequest);

        $data = $request->validate([
            'note_type' => 'required|in:colo,link',
            'body'      => 'required|string',
            'parent_id' => 'nullable|integer|exists:maint_request_notes,id',
        ]);

        if (!empty($data['parent_id'])) {
            $parent = MaintRequestNote::find($data['parent_id']);
            abort_if(!$parent || $parent->request_id !== $maintRequest->id, 422, '잘못된 답글 대상입니다.');
            abort_if($parent->note_type !== $data['note_type'], 422, '비고 유형이 일치하지 않습니다.');
            if ($parent->parent_id) {
                $data['parent_id'] = $parent->parent_id; // 1단계 트리 유지
            }
        }

        $data['request_id'] = $maintRequest->id;
        $note = MaintRequestNote::create($data);

        // 알림(이메일 + FCM) — 웹과 동일 규칙
        SrNotificationService::notifyNoteAdded($maintRequest, $note, $request->user());

        return response()->json($this->noteResource($note), 201);
    }

    public function destroyNote(Request $request, MaintRequest $maintRequest, MaintRequestNote $note): JsonResponse
    {
        [$applyScope, $accessible] = $this->resolveAccessScope($request->user());
        abort_unless($accessible, 403);
        $this->assertVisible($request->user(), $maintRequest);
        abort_unless($note->request_id === $maintRequest->id, 404);

        $note->delete();
        return response()->json(['message' => '비고가 삭제되었습니다.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Authorization helpers (웹 MaintRequestController::index 의 접근 범위 규칙과 동일)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * @return array{0: \Closure, 1: bool} [scope applier, accessible?]
     */
    private function resolveAccessScope($user): array
    {
        $isSrPrivileged = $user && ($user->isAdmin() || (bool) ($user->is_sr_agent ?? false));

        if ($isSrPrivileged) {
            return [function ($qb) {/* 제한 없음 */}, true];
        }

        $linkthelabId = CompanyGroup::where('name', '링크더랩')->value('id');
        $isLinkthelabMember = $user && $linkthelabId && (int) $user->company_group_id === (int) $linkthelabId;

        if (!$user->company_group_id) {
            return [function ($qb) { $qb->whereRaw('1=0'); }, false];
        }

        $cgId = (int) $user->company_group_id;
        return [function ($qb) use ($cgId, $isLinkthelabMember) {
            if ($isLinkthelabMember) {
                $qb->where(function ($x) use ($cgId) {
                    $x->where('company_group_id', $cgId)
                      ->orWhere('paid_dev_enabled', true);
                });
            } else {
                $qb->where('company_group_id', $cgId);
            }
        }, true];
    }

    private function assertVisible($user, MaintRequest $sr): void
    {
        $isSrPrivileged = $user && ($user->isAdmin() || (bool) ($user->is_sr_agent ?? false));
        if ($isSrPrivileged) return;

        $linkthelabId = CompanyGroup::where('name', '링크더랩')->value('id');
        $isLinkthelabMember = $user && $linkthelabId && (int) $user->company_group_id === (int) $linkthelabId;

        if (!$user->company_group_id) abort(403);

        if ($isLinkthelabMember) {
            if ((int) $sr->company_group_id !== (int) $user->company_group_id && !$sr->paid_dev_enabled) {
                abort(403);
            }
            return;
        }

        if ((int) $sr->company_group_id !== (int) $user->company_group_id) {
            abort(403);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Resources
    // ──────────────────────────────────────────────────────────────────────

    private function listResource(MaintRequest $r): array
    {
        return [
            'id'                 => $r->id,
            'excel_no'           => $r->excel_no,
            'summary'            => $r->summary,
            'status'             => $r->status,
            'priority'           => $r->priority,
            'category'           => $r->category,
            'request_date'       => optional($r->request_date)->toDateString(),
            'eta'                => optional($r->eta)->toDateString(),
            'completed_at'       => optional($r->completed_at)?->toIso8601String(),
            'difficulty_score'   => $r->difficulty_score,
            'reopen_count'       => $r->reopen_count,
            'paid_dev_enabled'   => (bool) $r->paid_dev_enabled,
            'menu'               => $r->menu ? ['id' => $r->menu->id, 'name' => $r->menu->name] : null,
            'colo_user'          => $r->coloUser ? ['id' => $r->coloUser->id, 'name' => $r->coloUser->name] : null,
            'assignee'           => $r->assignee ? ['id' => $r->assignee->id, 'name' => $r->assignee->name] : null,
            'notes_count'        => (int) ($r->notes_count ?? 0),
        ];
    }

    private function detailResource(MaintRequest $r): array
    {
        $notesGrouped = $this->groupNotesByThread($r->notes);

        return array_merge($this->listResource($r), [
            'content'           => $r->content,
            'ai_summary'        => $r->ai_summary,
            'ai_summary_at'     => optional($r->ai_summary_at)?->toIso8601String(),
            'ai_classification' => $r->ai_classification,
            'company_group'     => $r->companyGroup ? ['id' => $r->companyGroup->id, 'name' => $r->companyGroup->name] : null,
            'paid_dev_days'     => $r->paid_dev_days,
            'paid_dev_cost'     => $r->paid_dev_cost,
            'paid_dev_description' => $r->paid_dev_description,
            'colo_notes'        => $notesGrouped['colo'],
            'link_notes'        => $notesGrouped['link'],
        ]);
    }

    /**
     * 1단계 트리(부모/답글) 로 그룹화 후 note_type 별로 분리.
     * @return array{colo: array, link: array}
     */
    private function groupNotesByThread($notes): array
    {
        $byParent = [];
        foreach ($notes as $n) {
            if ($n->parent_id) {
                $byParent[$n->parent_id][] = $n;
            }
        }

        $out = ['colo' => [], 'link' => []];
        foreach ($notes as $n) {
            if ($n->parent_id) continue;
            $row = $this->noteResource($n);
            $row['replies'] = array_map(fn ($r) => $this->noteResource($r), $byParent[$n->id] ?? []);
            $out[$n->note_type][] = $row;
        }
        return $out;
    }

    private function noteResource(MaintRequestNote $n): array
    {
        return [
            'id'         => $n->id,
            'request_id' => $n->request_id,
            'note_type'  => $n->note_type,
            'body'       => $n->body,
            'parent_id'  => $n->parent_id,
            'created_at' => optional($n->created_at)?->toIso8601String(),
        ];
    }
}
