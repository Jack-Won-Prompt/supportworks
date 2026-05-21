<?php

namespace App\Http\Controllers;

use App\Models\Maint\MaintMenu;
use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestNote;
use App\Models\Maint\MaintUser;
use App\Services\Maint\SrSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // null = 제한 없음, 양수 = 해당 회사로 제한, 0 = 접근 불가
        $accessScope = null;
        if (!$isSrPrivileged) {
            $accessScope = $u?->company_group_id ? (int) $u->company_group_id : 0;
        }

        $applyAccessScope = function ($qb) use ($accessScope) {
            if ($accessScope === 0) {
                // 비권한자 + 회사 없음 → 결과 없음
                $qb->whereRaw('1=0');
            } elseif ($accessScope !== null) {
                $qb->where('company_group_id', $accessScope);
            }
        };

        $q = MaintRequest::query()
            ->with(['menu', 'coloUser', 'assignee'])
            ->latest('id');

        $applyAccessScope($q);

        if ($bucket !== 'all' && isset($bucketStatuses[$bucket])) {
            $q->whereIn('status', $bucketStatuses[$bucket]);
        }

        if ($s = $request->string('status')->toString())   $q->where('status', $s);
        if ($p = $request->string('priority')->toString()) $q->where('priority', $p);
        if ($m = $request->integer('menu_id'))             $q->where('menu_id', $m);
        if ($a = $request->integer('assignee_id'))         $q->where('assignee_id', $a);
        if ($c = $request->integer('colo_user_id'))        $q->where('colo_user_id', $c);
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

        // ── 통계 카운트 (접근 범위 적용) ──
        $cntQ = MaintRequest::query();
        $applyAccessScope($cntQ);
        $statusCounts = $cntQ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $menus      = MaintMenu::orderBy('name')->get(['id', 'name']);
        $coloUsers  = MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $devUsers   = MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $canFilterByCompany = $isSrPrivileged;
        $companyGroups = \DB::table('company_groups')->orderBy('name')->get(['id', 'name']);

        return view('maint-requests.index', compact(
            'requests', 'menus', 'coloUsers', 'devUsers', 'bucket', 'perPage',
            'canFilterByCompany', 'companyGroups', 'statusCounts'
        ));
    }

    public static function bucketStatuses(): array
    {
        // 모든 상태가 정확히 한 버킷에만 속하도록 (합계 = 전체)
        return [
            'in_progress' => ['draft', 'requested', 'planned', 'in_progress'],
            'reviewing'   => ['pending_check', 'review_requested', 'review_again', 'discussion_needed', 'on_hold', 'awaiting_file', 'replied'],
            'completed'   => ['completed'],
        ];
    }

    public function show(MaintRequest $maintRequest)
    {
        return view('maint-requests.show', $this->detailData($maintRequest));
    }

    public function embed(MaintRequest $maintRequest)
    {
        return view('maint-requests.embed', $this->detailData($maintRequest));
    }

    private function detailData(MaintRequest $maintRequest): array
    {
        $maintRequest->load(['menu', 'coloUser', 'assignee', 'notes' => fn ($q) => $q->oldest('id')]);

        return [
            'r'         => $maintRequest,
            'menus'     => MaintMenu::orderBy('name')->get(['id', 'name']),
            'coloUsers' => MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'devUsers'  => MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create()
    {
        $menus     = MaintMenu::orderBy('name')->get(['id', 'name']);
        $coloUsers = MaintUser::colo()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $devUsers  = MaintUser::withworks()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('maint-requests.create', compact('menus', 'coloUsers', 'devUsers'));
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
            'request_date'     => 'nullable|date',
            'eta'              => 'nullable|date',
            'colo_user_id'     => 'nullable|exists:maint_users,id',
            'colo_user_name'   => 'nullable|string|max:100',
            'assignee_id'      => 'nullable|exists:maint_users,id',
            'assignee_name'    => 'nullable|string|max:100',
            'status'           => 'nullable|in:' . implode(',', MaintRequest::STATUSES),
        ]);
        $this->normalizeAiSummaryFields($data);

        // 회사는 로그인 사용자 본인 소속으로 자동 지정
        $data['company_group_id'] = auth()->user()?->company_group_id;
        abort_if(empty($data['company_group_id']), 422, '회사 소속이 없는 사용자는 SR을 등록할 수 없습니다.');

        DB::transaction(function () use (&$data) {
            $data['menu_id']      = $this->resolveMenu($data);
            $data['colo_user_id'] = $this->resolveUser($data['colo_user_id'] ?? null, $data['colo_user_name'] ?? null, 'colo');
            $data['assignee_id']  = $this->resolveUser($data['assignee_id'] ?? null, $data['assignee_name'] ?? null, 'withworks');
            unset($data['menu_name'], $data['colo_user_name'], $data['assignee_name']);

            $data['status'] = $data['status'] ?? 'draft';

            MaintRequest::create($data);
        });

        return redirect()->route('maint-requests.index')->with('success', '요청이 등록되었습니다.');
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
            'request_date'     => 'nullable|date',
            'eta'              => 'nullable|date',
            'colo_user_id'     => 'nullable|exists:maint_users,id',
            'colo_user_name'   => 'nullable|string|max:100',
            'assignee_id'      => 'nullable|exists:maint_users,id',
            'assignee_name'    => 'nullable|string|max:100',
            'status'           => 'required|in:' . implode(',', MaintRequest::STATUSES),
        ]);
        $this->normalizeAiSummaryFields($data);

        DB::transaction(function () use (&$data, $maintRequest) {
            $data['menu_id']      = $this->resolveMenu($data);
            $data['colo_user_id'] = $this->resolveUser($data['colo_user_id'] ?? null, $data['colo_user_name'] ?? null, 'colo');
            $data['assignee_id']  = $this->resolveUser($data['assignee_id'] ?? null, $data['assignee_name'] ?? null, 'withworks');
            unset($data['menu_name'], $data['colo_user_name'], $data['assignee_name']);

            if ($data['status'] === 'completed' && !$maintRequest->completed_at) {
                $data['completed_at'] = now();
            } elseif ($data['status'] !== 'completed') {
                $data['completed_at'] = null;
            }

            $maintRequest->update($data);
        });

        if (request()->boolean('_modal')) {
            return redirect()->route('maint-requests.embed', $maintRequest)->with('success', '수정되었습니다.');
        }
        return redirect()->route('maint-requests.show', $maintRequest)->with('success', '수정되었습니다.');
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
        abort_unless(auth()->user()?->isAdmin(), 403, '삭제 권한이 없습니다.');

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
        ]);
        $data['request_id'] = $maintRequest->id;
        MaintRequestNote::create($data);

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

            $resolveUser = function (string $name, string $team) use (&$userCache, $norm, &$stats, $companyGroupId): int {
                $name = $norm($name);
                $key = "{$team}:{$name}";
                if (isset($userCache[$key])) return $userCache[$key];
                $existing = DB::table('maint_users')->where('team', $team)->where('name', $name)->first(['id', 'company_group_id']);
                if ($existing) {
                    // 콜로 사용자가 회사 매핑이 없으면 업로드 시 지정된 회사로 자동 세팅
                    if ($team === 'colo' && $companyGroupId && empty($existing->company_group_id)) {
                        DB::table('maint_users')->where('id', $existing->id)->update([
                            'company_group_id' => $companyGroupId,
                            'updated_at'       => now(),
                        ]);
                        $stats['colo_users_company_set']++;
                    }
                    return $userCache[$key] = (int) $existing->id;
                }
                $stats['users_added']++;
                return $userCache[$key] = DB::table('maint_users')->insertGetId([
                    'name'             => $name,
                    'team'             => $team,
                    'user_id'          => null,
                    'company_group_id' => ($team === 'colo') ? $companyGroupId : null,
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
                $coloUserId = $row['colo_user'] ? $resolveUser($row['colo_user'], 'colo') : null;

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
        if (mb_strpos($s, '초긴급') !== false) return 'critical';
        if (mb_strpos($s, '재확인') !== false) return 'recheck';
        if (mb_strpos($s, '긴급')   !== false) return 'urgent';
        return 'normal';
    }

    private static function mapImportStatus(?string $coloCheck, ?string $progress): string
    {
        $c = trim((string) $coloCheck);
        $p = trim((string) $progress);
        if ($c === '완료' || $c === '재확인') return $c === '재확인' ? 'review_again' : 'completed';

        $text = $p !== '' ? $p : $c;
        if ($text === '') return 'draft';

        if (mb_strpos($text, '완료')     !== false) return 'completed';
        if (mb_strpos($text, '답변')     !== false) return 'replied';
        if (mb_strpos($text, '재확인')   !== false) return 'review_again';
        if (mb_strpos($text, '검토')     !== false) return 'review_requested';
        if (mb_strpos($text, '확인요청') !== false) return 'pending_check';
        if (mb_strpos($text, '확인대기') !== false) return 'pending_check';
        if (mb_strpos($text, '확인필요') !== false) return 'pending_check';
        if (mb_strpos($text, '논의')     !== false) return 'discussion_needed';
        if (mb_strpos($text, '보류')     !== false) return 'on_hold';
        if (mb_strpos($text, '파일')     !== false) return 'awaiting_file';
        if (mb_strpos($text, '개발예정') !== false) return 'planned';
        if (mb_strpos($text, '개발대기') !== false) return 'planned';
        if (mb_strpos($text, '진행')     !== false) return 'in_progress';
        if (mb_strpos($text, '추가요청') !== false) return 'requested';
        if (mb_strpos($text, '요청')     !== false) return 'requested';
        return 'draft';
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
        if ($name === '') {
            return null;
        }
        return MaintMenu::firstOrCreate(['name' => $name])->id;
    }

    private function resolveUser(?int $id, ?string $name, string $team): ?int
    {
        if ($id) {
            return $id;
        }
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        return MaintUser::firstOrCreate(
            ['team' => $team, 'name' => $name],
            ['is_active' => true],
        )->id;
    }

    /**
     * 폼에서 hidden input 으로 받은 ai_summary_context_ids(JSON 문자열) 를 array 로 변환
     * 후 ai_summary_at 자동 기록. ai_summary 비어있으면 모든 ai_summary_* 칼럼 비움 (요약 제거).
     */
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

        $companyGroupId = auth()->user()?->company_group_id;
        abort_if(empty($companyGroupId), 422, '회사 소속이 없는 사용자는 사용할 수 없습니다.');

        // 기존 SR 이면 로드, 아니면 transient 인스턴스로 SrSummaryService 에 넘김.
        if (!empty($data['id'])) {
            $req = MaintRequest::with('menu')->findOrFail($data['id']);
            abort_if((int) $req->company_group_id !== (int) $companyGroupId, 403);

            // 폼에서 사용자가 막 수정한 값으로 덮어 컨텍스트 일치
            $req->summary  = $data['summary']  ?? $req->summary;
            $req->content  = $data['content']  ?? $req->content;
            $req->category = $data['category'] ?? $req->category;
            $req->menu_id  = $this->resolveMenu($data) ?? $req->menu_id;
            $req->priority = $data['priority'] ?? $req->priority;
        } else {
            $req = new MaintRequest([
                'summary'          => $data['summary']  ?? '',
                'content'          => $data['content']  ?? '',
                'category'         => $data['category'] ?? null,
                'priority'         => $data['priority'] ?? 'normal',
                'company_group_id' => $companyGroupId,
                'menu_id'          => $this->resolveMenu($data),
            ]);
            if ($req->menu_id) {
                $req->setRelation('menu', MaintMenu::find($req->menu_id));
            }
        }
        $req->company_group_id = $companyGroupId;

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
            'ok'           => true,
            'summary'      => $result['summary'],
            'context_ids'  => $result['context_ids'],
            'provider'     => $result['provider'],
        ]);
    }
}
