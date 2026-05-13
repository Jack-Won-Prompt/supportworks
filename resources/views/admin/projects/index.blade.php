@extends('layouts.admin')
@section('title', '프로젝트 현황')

@section('content')

<div class="admin-stat-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#6366f1;">{{ $stats['total'] }}</div>
        <div class="admin-stat-lbl">전체 프로젝트</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#16a34a;">{{ $stats['active'] }}</div>
        <div class="admin-stat-lbl">진행 중</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#d97706;">{{ $stats['on_hold'] }}</div>
        <div class="admin-stat-lbl">보류</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#0891b2;">{{ $stats['completed'] }}</div>
        <div class="admin-stat-lbl">완료</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-val" style="color:#94a3b8;">{{ $stats['cancelled'] }}</div>
        <div class="admin-stat-lbl">취소</div>
    </div>
</div>

<div class="admin-card">
    <form method="GET" action="{{ route('admin.projects.index') }}">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="프로젝트명, 고객사 검색…" value="{{ request('search') }}">

            <select name="status" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;cursor:pointer;">
                <option value="">전체 상태</option>
                <option value="active"    {{ request('status')=='active'    ? 'selected':'' }}>진행 중</option>
                <option value="on_hold"   {{ request('status')=='on_hold'   ? 'selected':'' }}>보류</option>
                <option value="completed" {{ request('status')=='completed' ? 'selected':'' }}>완료</option>
                <option value="cancelled" {{ request('status')=='cancelled' ? 'selected':'' }}>취소</option>
            </select>

            <select name="group_id" style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;background:#fff;outline:none;cursor:pointer;">
                <option value="">전체 회사</option>
                @foreach($groups as $g)
                <option value="{{ $g->id }}" {{ request('group_id')==$g->id ? 'selected':'' }}>{{ $g->name }}</option>
                @endforeach
            </select>

            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#334155;cursor:pointer;">
                <input type="checkbox" name="si_mode" value="1" {{ request('si_mode') ? 'checked':'' }} style="accent-color:#6366f1;">
                SI 모드만
            </label>

            <button type="submit" class="btn-primary">검색</button>
            @if(request()->hasAny(['search','status','group_id','si_mode']))
            <a href="{{ route('admin.projects.index') }}" class="btn-secondary">초기화</a>
            @endif
        </div>
    </form>

    @if($projects->isEmpty())
    <div style="text-align:center;padding:48px 0;color:#94a3b8;font-size:14px;">프로젝트가 없습니다.</div>
    @else
    <table class="admin-table">
        <thead>
            <tr>
                <th>프로젝트명</th>
                <th>고객사</th>
                <th>소속 회사</th>
                <th>상태</th>
                <th>기간</th>
                <th>멤버</th>
                <th>SI</th>
                <th>생성일</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
            <tr>
                <td>
                    <div style="font-weight:600;color:#1e293b;">{{ $project->name }}</div>
                    @if($project->description)
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $project->description }}</div>
                    @endif
                </td>
                <td>
                    @if($project->client_name)
                    <span style="font-size:13px;color:#334155;">{{ $project->client_name }}</span>
                    @else
                    <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td>
                    @if($project->companyGroup)
                    <span class="badge badge-purple">{{ $project->companyGroup->name }}</span>
                    @else
                    <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td>
                    @php
                        $statusMap = [
                            'active'    => ['badge-green',  '진행 중'],
                            'on_hold'   => ['badge-yellow', '보류'],
                            'completed' => ['badge-blue',   '완료'],
                            'cancelled' => ['badge-gray',   '취소'],
                        ];
                        [$cls, $lbl] = $statusMap[$project->status] ?? ['badge-gray', $project->status];
                    @endphp
                    <span class="badge {{ $cls }}">{{ $lbl }}</span>
                </td>
                <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                    @if($project->start_date || $project->end_date)
                        {{ $project->start_date?->format('Y.m.d') ?? '—' }}
                        ~
                        {{ $project->end_date?->format('Y.m.d') ?? '—' }}
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="font-size:13px;color:#334155;">
                    {{ $project->projectMembers->count() }}명
                </td>
                <td>
                    @if($project->si_mode_enabled)
                    <span class="badge badge-purple">SI</span>
                    @else
                    <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
                <td style="font-size:12px;color:#94a3b8;white-space:nowrap;">
                    {{ $project->created_at->format('Y.m.d') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($projects->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center;">
        {{ $projects->links() }}
    </div>
    @endif
    @endif
</div>


@if(auth('admin')->user()?->isSuperAdmin())
<div class="admin-card" style="margin-top:20px;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        <svg width="15" height="15" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <h3 style="font-size:13px;font-weight:700;color:#1e293b;margin:0;">데이터 초기화</h3>
        <span style="font-size:11px;color:#94a3b8;">Super Admin 전용 · 되돌릴 수 없습니다</span>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        {{-- 회의록 초기화 --}}
        <form method="POST" action="{{ route('admin.reset.meeting-minutes') }}"
              onsubmit="return confirm('회의록 전체를 초기화합니다.\n첨부된 메모·액션아이템도 모두 삭제됩니다.\n이 작업은 되돌릴 수 없습니다. 계속하시겠습니까?')">
            @csrf @method('DELETE')
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:12px;font-weight:700;color:#dc2626;background:#fff;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                회의록 전체 초기화
            </button>
        </form>

        {{-- 위클리 리포트 초기화 --}}
        <form method="POST" action="{{ route('admin.reset.weekly-reports') }}"
              onsubmit="return confirm('위클리 리포트 전체를 초기화합니다.\nAI 요약 데이터도 함께 삭제됩니다.\n이 작업은 되돌릴 수 없습니다. 계속하시겠습니까?')">
            @csrf @method('DELETE')
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:12px;font-weight:700;color:#dc2626;background:#fff;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                위클리 리포트 전체 초기화
            </button>
        </form>

    </div>
</div>
@endif

@endsection
