@extends('layouts.app')

@push('styles')
<style>
/* ── 산출물 대시보드 ──────────────────────────────── */
.dlv-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.dlv-header-left h2 { font-size:18px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.dlv-header-left p  { font-size:12.5px; color:#64748b; margin:0; }

/* 뷰 토글 */
.dlv-view-toggle { display:flex; gap:2px; background:#f1f5f9; border-radius:8px; padding:3px; }
.dlv-view-btn { display:flex; align-items:center; gap:5px; padding:5px 12px; border-radius:6px; border:none; background:transparent; font-size:12px; font-weight:600; color:#64748b; cursor:pointer; transition:all .15s; }
.dlv-view-btn.is-active { background:#fff; color:var(--t700); box-shadow:0 1px 3px rgba(0,0,0,.08); }
.dlv-view-btn:hover:not(.is-active) { color:var(--t600); }

/* 필터 바 */
.dlv-filter-bar { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.dlv-filter-select { font-size:12px; padding:5px 10px; border:1.5px solid #e2e8f0; border-radius:7px; color:#374151; background:#fff; cursor:pointer; transition:border-color .15s; }
.dlv-filter-select:focus { outline:none; border-color:var(--t400); }
.dlv-search { flex:1; min-width:180px; max-width:280px; font-size:12px; padding:5px 10px; border:1.5px solid #e2e8f0; border-radius:7px; color:#374151; }
.dlv-search:focus { outline:none; border-color:var(--t400); }

/* 책임 뱃지 */
.dlv-resp { display:inline-block; font-size:10px; font-weight:700; padding:1px 7px; border-radius:4px; }
.dlv-resp.b   { background:#ede9fe; color:#6d28d9; }
.dlv-resp.ab  { background:#d1fae5; color:#065f46; }

/* 카테고리 헤더 */
.dlv-cat-label { font-size:11px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; padding:4px 10px; border-radius:5px; display:inline-flex; align-items:center; gap:6px; margin:20px 0 10px; }

/* ── 카드 뷰 ── */
.dlv-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; margin-bottom:8px; }
.dlv-card { background:#fff; border:1.5px solid #e8e0ff; border-radius:14px; padding:16px 16px 14px; display:flex; flex-direction:column; gap:10px; text-decoration:none; transition:all .18s; position:relative; overflow:hidden; }
.dlv-card:hover { border-color:var(--t400); box-shadow:0 6px 20px rgba(124,58,237,.09); transform:translateY(-2px); }
.dlv-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:14px 14px 0 0; opacity:0; transition:opacity .18s; }
.dlv-card:hover::before { opacity:1; }
.dlv-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.dlv-card-name { font-size:13px; font-weight:700; color:#1e1b2e; line-height:1.4; }
.dlv-card-short { font-size:10px; font-weight:700; color:#94a3b8; }
.dlv-progress-bar { height:4px; background:#f1f5f9; border-radius:2px; overflow:hidden; margin-top:2px; }
.dlv-progress-fill { height:100%; border-radius:2px; transition:width .4s; }
.dlv-progress-fill.completed { background:#16a34a; }
.dlv-progress-fill.in_progress { background:var(--t500); }
.dlv-progress-fill.not_started { background:#e2e8f0; width:0 !important; }
.dlv-card-meta { display:flex; align-items:center; justify-content:space-between; }
.dlv-status-chip { font-size:10px; font-weight:600; padding:2px 7px; border-radius:4px; }
.dlv-status-chip.completed   { background:#dcfce7; color:#15803d; }
.dlv-status-chip.in_progress { background:#ede9fe; color:var(--t700); }
.dlv-status-chip.not_started { background:#f1f5f9; color:#94a3b8; }

/* ── 리스트 뷰 ── */
.dlv-list-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.dlv-list-table thead th { padding:8px 12px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #e8e0ff; white-space:nowrap; }
.dlv-list-table tbody tr { border-bottom:1px solid #f3eeff; transition:background .1s; }
.dlv-list-table tbody tr:hover { background:#faf5ff; }
.dlv-list-table td { padding:9px 12px; vertical-align:middle; }
.dlv-list-link { color:#1e1b2e; font-weight:600; text-decoration:none; }
.dlv-list-link:hover { color:var(--t600); }
.dlv-mini-bar { width:80px; height:5px; background:#f1f5f9; border-radius:3px; overflow:hidden; display:inline-block; vertical-align:middle; }
.dlv-mini-fill { height:100%; border-radius:3px; }

/* ── 통계 하단 ── */
.dlv-stats { display:flex; gap:12px; margin-top:24px; flex-wrap:wrap; }
.dlv-stat-card { background:#fff; border:1.5px solid #e8e0ff; border-radius:12px; padding:14px 20px; flex:1; min-width:120px; }
.dlv-stat-card .num { font-size:22px; font-weight:800; color:#1e1b2e; line-height:1; margin-bottom:4px; }
.dlv-stat-card .lbl { font-size:11.5px; color:#64748b; }

/* 카테고리 색상 */
.cat-design      { --cat-color:#7c3aed; }
.cat-security    { --cat-color:#dc2626; }
.cat-operations  { --cat-color:#0891b2; }
.cat-test_deploy { --cat-color:#059669; }
.cat-contract    { --cat-color:#d97706; }
.dlv-cat-label   { background:color-mix(in srgb, var(--cat-color) 10%, white); color:var(--cat-color); }
.dlv-card::before { background:var(--cat-color,var(--t500)); }
.dlv-progress-fill.in_progress { background:var(--cat-color,var(--t500)); }
</style>
@endpush

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'deliverables'])

{{-- 헤더 --}}
<div class="dlv-header">
    <div class="dlv-header-left">
        <h2>
            <svg width="18" height="18" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" style="display:inline;margin-right:5px;vertical-align:-3px;" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            {{ __('deliverables.title') }}
        </h2>
        <p>{{ __('deliverables.subtitle', ['count' => count($types)]) }}</p>
    </div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="dlv-view-toggle">
            <button class="dlv-view-btn {{ $view==='card' ? 'is-active' : '' }}" onclick="setView('card')" data-view="card">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                {{ __('deliverables.view_card') }}
            </button>
            <button class="dlv-view-btn {{ $view==='list' ? 'is-active' : '' }}" onclick="setView('list')" data-view="list">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                {{ __('deliverables.view_list') }}
            </button>
        </div>
    </div>
</div>

{{-- 필터 바 --}}
<div class="dlv-filter-bar">
    <input id="dlv-search" class="dlv-search" type="text" placeholder="{{ __('deliverables.search_ph') }}" oninput="filterCards()" />
    <select class="dlv-filter-select" id="f-cat" onchange="filterCards()">
        <option value="">{{ __('deliverables.cat_all') }}</option>
        @foreach($categories as $catKey => $cat)
            <option value="{{ $catKey }}" {{ $filterCategory === $catKey ? 'selected' : '' }}>
                {{ __('deliverables.cat_' . $catKey, [], null) ?: $cat['label'] }}
            </option>
        @endforeach
    </select>
    <select class="dlv-filter-select" id="f-resp" onchange="filterCards()">
        <option value="">{{ __('deliverables.resp_all') }}</option>
        <option value="B"   {{ $filterResponsibility === 'B'   ? 'selected' : '' }}>{{ __('deliverables.resp_b') }}</option>
        <option value="A+B" {{ $filterResponsibility === 'A+B' ? 'selected' : '' }}>{{ __('deliverables.resp_ab') }}</option>
    </select>
    <select class="dlv-filter-select" id="f-status" onchange="filterCards()">
        <option value="">{{ __('deliverables.status_all') }}</option>
        <option value="not_started" {{ $filterStatus === 'not_started' ? 'selected' : '' }}>{{ __('deliverables.status_not_started') }}</option>
        <option value="in_progress" {{ $filterStatus === 'in_progress' ? 'selected' : '' }}>{{ __('deliverables.status_in_progress') }}</option>
        <option value="completed"   {{ $filterStatus === 'completed'   ? 'selected' : '' }}>{{ __('deliverables.status_completed') }}</option>
    </select>
</div>

{{-- 카드 뷰 --}}
<div id="view-card" style="{{ $view==='list' ? 'display:none' : '' }}">
    @foreach($categories as $catKey => $cat)
        @php $catTypes = collect($types)->filter(fn($t) => $t['category'] === $catKey)->sortBy('no'); @endphp
        @if($catTypes->count())
        <div class="dlv-cat-label cat-{{ $catKey }}" data-cat="{{ $catKey }}">
            <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/></svg>
            {{ __('deliverables.cat_' . $catKey, [], null) ?: $cat['label'] }}
        </div>
        <div class="dlv-card-grid">
            @foreach($catTypes as $typeId => $type)
                @php
                    $dlv        = $deliverables->get($typeId);
                    $status     = $dlv?->status ?? 'not_started';
                    $stepsDone  = $dlv ? $dlv->current_step - 1 : 0;
                    $stepsTotal = count($type['steps']);
                    $pct        = $stepsTotal ? round($stepsDone / $stepsTotal * 100) : 0;
                    $statusLabel = __('deliverables.status_' . $status);
                @endphp
                <a href="{{ route('ai-agent.projects.deliverables.show', [$project, $typeId]) }}"
                   class="dlv-card cat-{{ $catKey }}"
                   data-name="{{ strtolower($type['name']) }} {{ strtolower($type['shortName']) }}"
                   data-cat="{{ $catKey }}"
                   data-resp="{{ $type['responsibility'] === 'A+B' ? 'A+B' : 'B' }}"
                   data-status="{{ $status }}">
                    <div class="dlv-card-top">
                        <div>
                            <div class="dlv-card-short">{{ $type['shortName'] }}</div>
                            <div class="dlv-card-name">{{ $type['name'] }}</div>
                        </div>
                        <span class="dlv-resp {{ $type['responsibility'] === 'A+B' ? 'ab' : 'b' }}">{{ $type['responsibility'] }}</span>
                    </div>
                    <div class="dlv-card-progress">
                        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#94a3b8;margin-bottom:4px;">
                            <span>{{ __('deliverables.step_progress', ['done' => $stepsDone, 'total' => $stepsTotal]) }}</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="dlv-progress-bar">
                            <div class="dlv-progress-fill {{ $status }}" style="width:{{ $pct }}%;background:var(--cat-color)"></div>
                        </div>
                    </div>
                    <div class="dlv-card-meta">
                        <span class="dlv-status-chip {{ $status }}">{{ $statusLabel }}</span>
                        <svg width="14" height="14" fill="none" stroke="var(--t400)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>
        @endif
    @endforeach
</div>

{{-- 리스트 뷰 --}}
<div id="view-list" style="{{ $view==='card' ? 'display:none' : '' }}">
    <table class="dlv-list-table" id="dlv-list-tbl">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>{{ __('deliverables.col_name') }}</th>
                <th>{{ __('deliverables.col_category') }}</th>
                <th>{{ __('deliverables.col_responsibility') }}</th>
                <th>{{ __('deliverables.col_progress') }}</th>
                <th>{{ __('deliverables.col_step') }}</th>
                <th>{{ __('deliverables.col_status') }}</th>
                <th>{{ __('deliverables.col_timing') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(collect($types)->sortBy('no') as $typeId => $type)
                @php
                    $dlv        = $deliverables->get($typeId);
                    $status     = $dlv?->status ?? 'not_started';
                    $stepsDone  = $dlv ? $dlv->current_step - 1 : 0;
                    $stepsTotal = count($type['steps']);
                    $pct        = $stepsTotal ? round($stepsDone / $stepsTotal * 100) : 0;
                    $cat        = $categories[$type['category']] ?? ['label' => $type['category']];
                    $statusLabel = __('deliverables.status_' . $status);
                @endphp
                <tr data-name="{{ strtolower($type['name']) }} {{ strtolower($type['shortName']) }}"
                    data-cat="{{ $type['category'] }}"
                    data-resp="{{ $type['responsibility'] === 'A+B' ? 'A+B' : 'B' }}"
                    data-status="{{ $status }}">
                    <td style="color:#94a3b8;font-size:11px;">{{ $type['no'] }}</td>
                    <td>
                        <a href="{{ route('ai-agent.projects.deliverables.show', [$project, $typeId]) }}" class="dlv-list-link">
                            <span style="font-size:10px;color:#94a3b8;margin-right:4px;">{{ $type['shortName'] }}</span>
                            {{ $type['name'] }}
                        </a>
                    </td>
                    <td>
                        <span class="dlv-cat-label cat-{{ $type['category'] }}" style="font-size:10px;padding:2px 7px;margin:0;">
                            {{ __('deliverables.cat_' . $type['category'], [], null) ?: $cat['label'] }}
                        </span>
                    </td>
                    <td><span class="dlv-resp {{ $type['responsibility'] === 'A+B' ? 'ab' : 'b' }}">{{ $type['responsibility'] }}</span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="dlv-mini-bar">
                                <div class="dlv-mini-fill {{ $status }}"
                                     style="width:{{ $pct }}%;background:{{ $type['category'] === 'security' ? '#dc2626' : ($type['category'] === 'operations' ? '#0891b2' : ($type['category'] === 'test_deploy' ? '#059669' : ($type['category'] === 'contract' ? '#d97706' : 'var(--t500)'))) }}"></div>
                            </div>
                            <span style="font-size:11px;color:#64748b;">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td style="font-size:11.5px;color:#64748b;">{{ $stepsDone }}/{{ $stepsTotal }}</td>
                    <td><span class="dlv-status-chip {{ $status }}">{{ $statusLabel }}</span></td>
                    <td style="font-size:11px;color:#94a3b8;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $type['timing'] }}">{{ $type['timing'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 하단 통계 --}}
<div class="dlv-stats" id="stats-row">
    <div class="dlv-stat-card">
        <div class="num">{{ $stats['total'] }}</div>
        <div class="lbl">{{ __('deliverables.stat_total') }}</div>
    </div>
    <div class="dlv-stat-card" style="border-color:#bbf7d0;">
        <div class="num" style="color:#16a34a;">{{ $stats['completed'] }}</div>
        <div class="lbl">{{ __('deliverables.status_completed') }}</div>
    </div>
    <div class="dlv-stat-card" style="border-color:#ddd6fe;">
        <div class="num" style="color:var(--t600);">{{ $stats['inProg'] }}</div>
        <div class="lbl">{{ __('deliverables.status_in_progress') }}</div>
    </div>
    <div class="dlv-stat-card">
        <div class="num" style="color:#94a3b8;">{{ $stats['notStart'] }}</div>
        <div class="lbl">{{ __('deliverables.status_not_started') }}</div>
    </div>
    @if($stats['total'] > 0)
    <div class="dlv-stat-card" style="border-color:#bfdbfe;flex:2;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div>
                <div class="num" style="color:#2563eb;">{{ round(($stats['completed'] / $stats['total']) * 100) }}%</div>
                <div class="lbl">{{ __('deliverables.stat_overall') }}</div>
            </div>
            <div style="flex:1;">
                <div style="height:8px;background:#e0e7ff;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:{{ round(($stats['completed'] / $stats['total']) * 100) }}%;background:#2563eb;border-radius:4px;transition:width .6s;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function setView(v) {
    document.getElementById('view-card').style.display = v === 'card' ? '' : 'none';
    document.getElementById('view-list').style.display = v === 'list' ? '' : 'none';
    document.querySelectorAll('.dlv-view-btn').forEach(b => b.classList.toggle('is-active', b.dataset.view === v));
    const url = new URL(location.href);
    url.searchParams.set('view', v);
    history.replaceState({}, '', url);
}

function filterCards() {
    const q      = document.getElementById('dlv-search').value.toLowerCase();
    const cat    = document.getElementById('f-cat').value;
    const resp   = document.getElementById('f-resp').value;
    const status = document.getElementById('f-status').value;

    document.querySelectorAll('.dlv-card').forEach(el => {
        const show = (!q || el.dataset.name.includes(q))
            && (!cat    || el.dataset.cat    === cat)
            && (!resp   || el.dataset.resp   === resp)
            && (!status || el.dataset.status === status);
        el.style.display = show ? '' : 'none';
    });

    document.querySelectorAll('.dlv-cat-label[data-cat]').forEach(hdr => {
        const grid = hdr.nextElementSibling;
        const visible = grid && [...grid.querySelectorAll('.dlv-card')].some(c => c.style.display !== 'none');
        hdr.style.display = visible ? '' : 'none';
        if (grid) grid.style.display = visible ? '' : 'none';
    });

    document.querySelectorAll('#dlv-list-tbl tbody tr').forEach(tr => {
        const show = (!q || tr.dataset.name.includes(q))
            && (!cat    || tr.dataset.cat    === cat)
            && (!resp   || tr.dataset.resp   === resp)
            && (!status || tr.dataset.status === status);
        tr.style.display = show ? '' : 'none';
    });
}
</script>
@endpush
