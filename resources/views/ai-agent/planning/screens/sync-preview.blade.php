@extends('layouts.ai-agent')
@section('title', '간트 동기화 — 웍스 Agent')

@push('styles')
<style>
.sync-wrap   { max-width:820px; }
.sync-hdr    { margin-bottom:22px; }
.sync-hdr h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 5px; }
.sync-hdr p  { font-size:13.5px; color:#64748b; margin:0; }

.sync-stats  { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.sync-stat   { flex:1; min-width:110px; background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:12px 16px; text-align:center; }
.sync-stat-num { font-size:22px; font-weight:800; }
.sync-stat-label { font-size:11.5px; color:#64748b; margin-top:2px; }
.sync-stat.new { border-color:#86efac; }
.sync-stat.new .sync-stat-num { color:#166534; }
.sync-stat.upd { border-color:#93c5fd; }
.sync-stat.upd .sync-stat-num { color:#1d4ed8; }
.sync-stat.dup { border-color:#e2e8f0; }
.sync-stat.dup .sync-stat-num { color:#94a3b8; }
.sync-stat.arc { border-color:#fca5a5; }
.sync-stat.arc .sync-stat-num { color:#dc2626; }

.sync-table-wrap { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:18px; }
.sync-table      { width:100%; border-collapse:collapse; }
.sync-table th   { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; padding:10px 14px; background:#faf5ff; border-bottom:1.5px solid #ede8ff; text-align:left; }
.sync-table td   { padding:10px 14px; border-bottom:1px solid #f8f5ff; font-size:13px; color:#374151; vertical-align:middle; }
.sync-table tr:last-child td { border-bottom:none; }

.sync-badge       { display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.sync-badge.new   { background:#f0fdf4; color:#166534; }
.sync-badge.updated { background:#eff6ff; color:#1d4ed8; }
.sync-badge.existing { background:#f8fafc; color:#64748b; }
.sync-badge.archived { background:#fef2f2; color:#dc2626; }

.sync-scr-id  { font-size:11.5px; font-weight:800; color:var(--t700,#6d28d9); font-family:monospace; }

.sync-actions  { display:flex; gap:10px; flex-wrap:wrap; padding:14px 0 2px; }
.sync-btn      { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border-radius:10px; font-size:13.5px; font-weight:700; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.sync-btn-primary { background:var(--t600,#7c3aed); color:#fff; }
.sync-btn-primary:hover { background:var(--t700,#6d28d9); }
.sync-btn-primary:disabled { background:#e2e8f0; color:#94a3b8; cursor:not-allowed; }
.sync-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.sync-btn-outline:hover { border-color:#a78bfa; color:var(--t600,#7c3aed); }

.sync-orphan-warn { background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-bottom:18px; display:flex; align-items:flex-start; gap:9px; }
.sync-all-check  { display:flex; align-items:center; gap:8px; font-size:13px; color:#374151; cursor:pointer; padding:8px 0; }
.sync-all-check input { width:15px; height:15px; cursor:pointer; accent-color:var(--t600,#7c3aed); }
</style>
@endpush

@section('page-actions')
<a href="{{ route('ai-agent.projects.planning.index', $project) }}"
   style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:13px;font-weight:600;text-decoration:none;">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    화면 목록으로
</a>
@endsection

@section('ai-agent-content')
<div class="sync-wrap" x-data="syncPage()">

    <div class="sync-hdr">
        <div style="font-size:11px;font-weight:700;color:var(--t600,#7c3aed);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">단계 1: 기획</div>
        <h1>간트 동기화 미리보기</h1>
        <p>Supportworks 간트의 작업 항목을 SCR-XXX 화면으로 가져옵니다. 체크박스로 가져올 항목을 선택하세요.</p>
    </div>

    {{-- 통계 --}}
    <div class="sync-stats">
        <div class="sync-stat new">
            <div class="sync-stat-num">{{ $preview['new_count'] }}</div>
            <div class="sync-stat-label">신규 등록</div>
        </div>
        <div class="sync-stat upd">
            <div class="sync-stat-num">{{ $preview['update_count'] }}</div>
            <div class="sync-stat-label">업데이트</div>
        </div>
        <div class="sync-stat dup">
            <div class="sync-stat-num">{{ $preview['schedules']->where('sync_status','existing')->count() }}</div>
            <div class="sync-stat-label">변경 없음</div>
        </div>
        <div class="sync-stat arc">
            <div class="sync-stat-num">{{ $preview['orphan_count'] }}</div>
            <div class="sync-stat-label">간트 삭제됨</div>
        </div>
    </div>

    {{-- 아카이브 경고 --}}
    @if($preview['orphan_count'] > 0)
    <div class="sync-orphan-warn">
        <svg width="15" height="15" fill="none" stroke="#dc2626" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
            <div style="font-size:13px;font-weight:700;color:#991b1b;margin-bottom:3px;">
                간트에서 삭제된 작업 {{ $preview['orphan_count'] }}건이 있습니다
            </div>
            <div style="font-size:12.5px;color:#b91c1c;">
                아래 "간트 삭제 항목 아카이브" 옵션을 선택하면 해당 화면이 아카이브 처리됩니다. 실제 삭제는 되지 않으며 후속 산출물 참조는 보존됩니다.
            </div>
        </div>
    </div>
    @endif

    {{-- 간트 작업 목록 --}}
    @if($preview['schedules']->isNotEmpty())
    <form method="POST" action="{{ route('ai-agent.projects.planning.sync-gantt', $project) }}" id="sync-form">
        @csrf

        <div style="margin-bottom:10px;">
            <label class="sync-all-check" @click="toggleAll()">
                <input type="checkbox" id="select-all" x-model="allSelected" @click.prevent="toggleAll()">
                전체 선택 / 해제
            </label>
        </div>

        <div class="sync-table-wrap">
            <table class="sync-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:90px;">현재 SCR</th>
                        <th>작업명</th>
                        <th style="width:90px;">상태</th>
                        <th style="width:120px;">일정</th>
                        <th style="width:100px;">담당자</th>
                        <th style="width:90px;">동기화 결과</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['schedules'] as $item)
                    @php $s = $item['schedule']; $existing = $item['existing']; $syncStatus = $item['sync_status']; @endphp
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox"
                                   name="schedule_ids[]"
                                   value="{{ $s->id }}"
                                   x-model="selected"
                                   style="width:14px;height:14px;cursor:pointer;accent-color:var(--t600,#7c3aed);"
                                   @if($syncStatus === 'existing') @endif>
                        </td>
                        <td>
                            @if($existing)
                                <span class="sync-scr-id">{{ $existing->screen_id }}</span>
                            @else
                                <span style="font-size:11.5px;color:#94a3b8;">신규</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:600;color:#1e1b2e;">{{ $s->title }}</div>
                            @if($s->group_name)
                            <div style="font-size:11.5px;color:#94a3b8;margin-top:2px;">{{ $s->group_name }}</div>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:11.5px;padding:2px 7px;border-radius:4px;font-weight:600;
                                background:{{ match($s->status) { 'completed'=>'#f0fdf4', 'in_progress'=>'#eff6ff', 'cancelled'=>'#fef2f2', default=>'#f8fafc' } }};
                                color:{{ match($s->status) { 'completed'=>'#166534', 'in_progress'=>'#1d4ed8', 'cancelled'=>'#dc2626', default=>'#64748b' } }};">
                                {{ $s->status_label }}
                            </span>
                        </td>
                        <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                            @if($s->start_date)
                                {{ \Carbon\Carbon::parse($s->start_date)->format('m/d') }}
                                @if($s->end_date) – {{ \Carbon\Carbon::parse($s->end_date)->format('m/d') }} @endif
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:12.5px;color:#475569;">
                            {{ $s->assignee?->name ?? '—' }}
                        </td>
                        <td>
                            <span class="sync-badge {{ $syncStatus }}">
                                {{ match($syncStatus) {
                                    'new'      => '+ 신규',
                                    'updated'  => '↑ 업데이트',
                                    'existing' => '= 동일',
                                    'archived' => '아카이브됨',
                                    default    => $syncStatus,
                                } }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 고급 옵션 --}}
        @if($preview['orphan_count'] > 0)
        <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:18px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#991b1b;font-weight:600;">
                <input type="checkbox" name="archive_orphans" value="1" style="width:14px;height:14px;accent-color:#dc2626;">
                간트에서 삭제된 화면 {{ $preview['orphan_count'] }}건을 아카이브 처리
            </label>
            <div style="font-size:12px;color:#b91c1c;margin-top:4px;margin-left:22px;">아카이브된 화면은 삭제되지 않으며 복원 가능합니다</div>
        </div>
        @endif

        <div class="sync-actions">
            <button type="submit" class="sync-btn sync-btn-primary"
                    :disabled="selected.length === 0"
                    :style="selected.length === 0 ? 'background:#e2e8f0;color:#94a3b8;cursor:not-allowed;' : ''">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="selected.length > 0 ? `선택 항목 ${selected.length}건 동기화` : '항목을 선택하세요'"></span>
            </button>
            <a href="{{ route('ai-agent.projects.planning.index', $project) }}" class="sync-btn sync-btn-outline">취소</a>
        </div>
    </form>
    @else
    <div style="text-align:center;padding:40px 24px;background:#fff;border:2px dashed #ddd6fe;border-radius:16px;">
        <div style="font-size:32px;margin-bottom:12px;">📅</div>
        <div style="font-size:15px;font-weight:700;color:#1e1b2e;margin-bottom:6px;">간트에 등록된 작업이 없습니다</div>
        <div style="font-size:13px;color:#64748b;margin-bottom:16px;">
            Supportworks 간트에서 이 프로젝트에 작업을 추가한 후 다시 시도하세요.
        </div>
        <a href="{{ route('projects.gantt', $project) }}" class="sync-btn sync-btn-outline" style="display:inline-flex;">
            간트 보기 →
        </a>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function syncPage() {
    return {
        selected: @json($preview['schedules']->map(fn($i) => $i['schedule']->id)->values()),
        get allSelected() {
            return this.selected.length === {{ $preview['schedules']->count() }};
        },
        toggleAll() {
            if (this.allSelected) {
                this.selected = [];
            } else {
                this.selected = @json($preview['schedules']->map(fn($i) => $i['schedule']->id)->values());
            }
        },
    };
}
</script>
@endpush
