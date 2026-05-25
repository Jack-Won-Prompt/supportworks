<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\CompanyGroup;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 구성원 목록 — 같은 회사 OR 같은 프로젝트 참여자 합집합 (본인 포함)
        // 관리자는 전체 사용자를 봄
        if ($user->isAdmin()) {
            $members = User::orderBy('name')->get();
        } else {
            $myProjectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id');
            $myCompanyId  = $user->company_group_id;

            $members = User::where(function ($q) use ($myProjectIds, $myCompanyId, $user) {
                    // 본인은 항상 포함
                    $q->where('id', $user->id);
                    // 같은 회사
                    if ($myCompanyId) {
                        $q->orWhere('company_group_id', $myCompanyId);
                    }
                    // 같은 프로젝트 (회사 무관)
                    if ($myProjectIds->isNotEmpty()) {
                        $q->orWhereIn('id', function ($sub) use ($myProjectIds) {
                            $sub->select('user_id')->from('project_members')->whereIn('project_id', $myProjectIds);
                        });
                    }
                })
                ->orderBy('name')
                ->get();
        }

        $invitations = Invitation::with('inviter')
            ->when(
                $user->company_group_id,
                fn($q) => $q->where('company_group_id', $user->company_group_id),
                fn($q) => $q->where('invited_by', $user->id)
            )
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();

        $projects = $user->isAdmin()
            ? Project::orderBy('name')->get(['id', 'name', 'status'])
            : Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->orderBy('name')->get(['id', 'name', 'status']);

        // 팀원별 소속 프로젝트 맵 (user_id → [project_name, ...])
        $memberIds = $members->pluck('id');

        $pmRows = ProjectMember::whereIn('user_id', $memberIds)
            ->join('projects', 'project_members.project_id', '=', 'projects.id')
            ->select('project_members.user_id', 'projects.id as project_id', 'projects.name')
            ->get();

        $createdRows = Project::whereIn('created_by', $memberIds)
            ->get(['id', 'name', 'created_by'])
            ->map(fn($p) => (object)['user_id' => $p->created_by, 'project_id' => $p->id, 'name' => $p->name]);

        $allRows = $pmRows->concat($createdRows)->groupBy('user_id');

        $memberProjectMap = $allRows->map(
            fn($rows) => $rows->pluck('name')->unique()->sort()->values()
        );
        $memberProjectIdMap = $allRows->map(
            fn($rows) => $rows->pluck('project_id')->map(fn($id) => (int) $id)->unique()->values()
        );

        // 프로젝트 배정 권한 판별
        $managedProjectIds = [];
        if ($user->isAdmin()) {
            $canManage = true;
        } else {
            $managedProjectIds = ProjectMember::where('user_id', $user->id)
                ->where('role', 'manager')
                ->pluck('project_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $canManage = !empty($managedProjectIds);
        }

        // 배정 모달에 표시할 프로젝트 목록 (관리자: 전체, 매니저: 담당 프로젝트만)
        $assignableProjects = $user->isAdmin()
            ? Project::orderBy('name')->get(['id', 'name', 'status'])
            : Project::whereIn('id', $managedProjectIds)->orderBy('name')->get(['id', 'name', 'status']);

        $companies = CompanyGroup::orderBy('name')->get(['id', 'name']);

        return view('team.index', compact(
            'members', 'invitations', 'projects',
            'memberProjectMap', 'memberProjectIdMap',
            'canManage', 'managedProjectIds', 'assignableProjects',
            'companies'
        ));
    }

    /**
     * 회사 검색 (자동완성) — company_groups 부분일치 (매니저+ 만)
     */
    public function searchCompanies(Request $request)
    {
        abort_unless($this->isManagerOrAdmin(auth()->user()), 403);

        $q = trim((string) $request->input('q', ''));

        $rows = CompanyGroup::query()
            ->when($q !== '', fn($qb) => $qb->where('name', 'like', '%' . $q . '%'))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn($g) => ['id' => $g->id, 'name' => $g->name])
            ->values();

        return response()->json(['companies' => $rows]);
    }

    /**
     * 회사 목록 조회 (전체)
     */
    public function listCompanies()
    {
        abort_unless($this->isManagerOrAdmin(auth()->user()), 403);
        return response()->json(['companies' => CompanyGroup::orderBy('name')->get(['id', 'name'])]);
    }

    /**
     * 회사 등록 (매니저+ 만) — company_groups 에 신규 행 생성
     */
    public function storeCompany(Request $request)
    {
        abort_unless($this->isManagerOrAdmin(auth()->user()), 403);

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = trim($request->name);
        if ($name === '' || $name === '-') {
            return response()->json(['ok' => false, 'message' => '유효한 회사명을 입력하세요.'], 422);
        }

        $existing = CompanyGroup::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json(['ok' => true, 'company' => $existing->only(['id', 'name']), 'created' => false]);
        }

        $base = preg_replace('/[^a-z0-9]+/i', '-', \Illuminate\Support\Str::ascii($name));
        $base = trim(strtolower($base), '-');
        if ($base === '') $base = 'grp';
        $code = mb_substr($base, 0, 40);
        $i = 0;
        while (CompanyGroup::where('code', $code)->exists()) {
            $i++;
            $code = mb_substr($base, 0, 36) . '-' . $i;
        }

        $group = CompanyGroup::create([
            'name'      => $name,
            'code'      => $code,
            'is_active' => true,
        ]);

        return response()->json(['ok' => true, 'company' => $group->only(['id', 'name']), 'created' => true]);
    }

    /**
     * 구성원 회사 배정 (매니저+ 만)
     * — users.company_group_id 를 변경하고, 호환용 company 문자열도 동기화.
     */
    public function updateMemberCompany(Request $request, User $member)
    {
        $me = auth()->user();
        abort_unless($this->isManagerOrAdmin($me), 403);

        $request->validate([
            'company_id' => 'nullable|integer|exists:company_groups,id',
        ]);

        $companyId = $request->input('company_id') ?: null;
        $group     = $companyId ? CompanyGroup::find($companyId) : null;

        $member->company_group_id = $companyId;
        $member->company          = $group?->name;
        $member->save();

        return response()->json([
            'ok'           => true,
            'company_id'   => $member->company_group_id,
            'company_name' => $member->company,
        ]);
    }

    /**
     * 매니저 이상 권한 검사 — admin 또는 어떤 프로젝트의 manager.
     */
    private function isManagerOrAdmin(?User $user): bool
    {
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        return ProjectMember::where('user_id', $user->id)->where('role', 'manager')->exists();
    }

    public function invite(Request $request)
    {
        $request->validate([
            'email'            => 'required|email|max:255',
            'phone'            => 'nullable|string|max:30',
            'message'          => 'nullable|string|max:1000',
            'project_ids'      => 'nullable|array',
            'project_ids.*'    => 'exists:projects,id',
            'invite_lang'      => 'nullable|string|in:ko,en,ja,zh',
            'company_group_id' => 'nullable|integer|exists:company_groups,id',
        ]);

        $me         = auth()->user();
        $email      = $request->email;
        $phone      = $request->filled('phone') ? preg_replace('/[^\d+\-\s]/', '', trim($request->phone)) : null;
        $message    = $request->filled('message') ? trim($request->message) : null;
        $projectIds = $request->input('project_ids') ? array_map('intval', $request->input('project_ids')) : null;
        $inviteLang = $request->input('invite_lang') ?: app()->getLocale();

        // 회사 선택: 매니저+만 임의 회사 지정 가능, 그 외는 본인 회사로 고정
        $companyId = $this->isManagerOrAdmin($me)
            ? ($request->filled('company_group_id') ? (int) $request->company_group_id : $me->company_group_id)
            : $me->company_group_id;

        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['email' => '이미 가입된 이메일입니다.'])->withInput();
        }

        $invitation = Invitation::where('email', $email)->whereNull('accepted_at')->first();

        if (!$invitation) {
            $invitation = Invitation::create([
                'email'            => $email,
                'phone'            => $phone,
                'message'          => $message,
                'project_ids'      => $projectIds,
                'token'            => Str::random(40),
                'invited_by'       => auth()->id(),
                'company_group_id' => $companyId,
            ]);
        } else {
            $invitation->update([
                'phone'            => $phone ?: $invitation->phone,
                'message'          => $message,
                'project_ids'      => $projectIds,
                'company_group_id' => $companyId,
            ]);
        }

        $invitation->load('inviter');

        // 이메일에 프로젝트 이름 포함을 위해 로드
        $invitedProjects = $projectIds
            ? Project::whereIn('id', $projectIds)->pluck('name')->all()
            : [];

        try {
            Mail::to($email)->send((new InvitationMail($invitation, $invitedProjects))->locale($inviteLang));
            $msg = "{$email} 으로 초대 이메일이 발송되었습니다.";
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            \Log::error('[InvitationMail] ' . $e->getMessage());
            return back()
                ->with('invite_url', rtrim(config('app.url'), '/') . route('team.accept', $invitation->token, false))
                ->with('invite_email', $email)
                ->with('mail_error', '이메일 발송에 실패했습니다. 아래 링크를 직접 공유하세요. (' . $e->getMessage() . ')');
        }

        return back()->with('success', $msg);
    }

    public function updateMemberProjects(Request $request, User $member)
    {
        $request->validate([
            'project_ids'   => 'array',
            'project_ids.*' => 'integer|exists:projects,id',
        ]);

        $me = auth()->user();

        // 권한 범위는 아래의 $allowedIds 로 결정 — 같은 회사 그룹 검사는 제거
        // (구성원 목록이 프로젝트 기반으로 확장됨에 따라 cross-company 멤버도 관리 가능)
        // 관리 가능한 프로젝트 범위 결정
        if ($me->isAdmin()) {
            $allowedIds = null; // null = 제한 없음
        } else {
            $allowedIds = ProjectMember::where('user_id', $me->id)
                ->where('role', 'manager')
                ->pluck('project_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            if (empty($allowedIds)) {
                abort(403);
            }
        }

        $newIds = array_map('intval', $request->input('project_ids', []));
        if ($allowedIds !== null) {
            $newIds = array_values(array_intersect($newIds, $allowedIds));
        }

        // 현재 배정 (관리 범위 내)
        $query = ProjectMember::where('user_id', $member->id);
        if ($allowedIds !== null) {
            $query->whereIn('project_id', $allowedIds);
        }
        $currentIds = $query->pluck('project_id')->map(fn($id) => (int) $id)->toArray();

        // 추가
        foreach (array_diff($newIds, $currentIds) as $projectId) {
            ProjectMember::firstOrCreate(
                ['project_id' => $projectId, 'user_id' => $member->id],
                ['role' => 'member']
            );
        }

        // 제거 (마지막 매니저 보호)
        foreach (array_diff($currentIds, $newIds) as $projectId) {
            $rec = ProjectMember::where('project_id', $projectId)->where('user_id', $member->id)->first();
            if ($rec && $rec->role === 'manager') {
                $managerCount = ProjectMember::where('project_id', $projectId)->where('role', 'manager')->count();
                if ($managerCount <= 1) {
                    continue; // 마지막 매니저는 제거 불가
                }
            }
            ProjectMember::where('project_id', $projectId)->where('user_id', $member->id)->delete();
        }

        $updatedProjects = ProjectMember::where('user_id', $member->id)
            ->join('projects', 'project_members.project_id', '=', 'projects.id')
            ->select('projects.id', 'projects.name')
            ->get();

        return response()->json(['ok' => true, 'projects' => $updatedProjects]);
    }

    public function cancelInvite(Invitation $invitation)
    {
        if ($invitation->invited_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', '초대가 취소되었습니다.');
    }

    public function accept(string $token)
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->first();

        if (!$invitation) {
            return redirect()->route('login')->with('error', '유효하지 않거나 이미 사용된 초대 링크입니다.');
        }

        // 초대된 프로젝트 로드 (이름 + 설명)
        $invitedProjects = $invitation->project_ids
            ? Project::whereIn('id', $invitation->project_ids)->get(['id', 'name', 'description'])
            : collect();

        $inviterName = optional($invitation->inviter)->name;

        return view('auth.invite-register', compact('invitation', 'invitedProjects', 'inviterName'));
    }

    public function register(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->first();

        if (!$invitation) {
            return redirect()->route('login')->with('error', '유효하지 않거나 이미 사용된 초대 링크입니다.');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->route('login')->with('error', '이미 가입된 이메일입니다. 로그인해 주세요.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $invitation->loadMissing('inviter');

        $phone = $request->filled('phone')
            ? preg_replace('/[^\d+\-\s]/', '', trim($request->phone))
            : $invitation->phone;

        $user = User::create([
            'name'             => $request->name,
            'email'            => $invitation->email,
            'phone'            => $phone,
            'password'         => Hash::make($request->password),
            'role'             => 'member',
            'company_group_id' => $invitation->company_group_id
                                  ?? $invitation->inviter?->company_group_id,
        ]);

        // 초대된 프로젝트에 멤버로 자동 등록
        foreach ($invitation->project_ids ?? [] as $projectId) {
            $projectId = (int) $projectId;
            if (!$projectId || !Project::where('id', $projectId)->exists()) continue;

            ProjectMember::firstOrCreate(
                ['project_id' => $projectId, 'user_id' => $user->id],
                ['role' => 'member']
            );
        }

        $invitation->update(['accepted_at' => now()]);

        // 외부 이메일로 보내진 mailbox 메시지들을 새 사용자에게 귀속 (folder는 이미 inbox)
        // 과거에 folder='sent' 로 저장된 호환 레코드도 inbox 로 정상화
        \App\Models\Mailbox\Recipient::where('email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id, 'folder' => 'inbox']);

        Auth::login($user);
        $request->session()->regenerate();

        $projectCount = count($invitation->project_ids ?? []);
        $successMsg   = '환영합니다, ' . $user->name . ' 님! SupportWorks에 합류하셨습니다 🎉';
        if ($projectCount > 0) {
            $successMsg .= " {$projectCount}개 프로젝트에 자동으로 참여되었습니다.";
        }

        return redirect()->route('dashboard')->with('success', $successMsg);
    }
}
