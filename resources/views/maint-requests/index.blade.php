@extends('layouts.app')

@section('title', 'SR 요청')

@section('content')
<div id="maint-page" class="pt-4">

    {{-- 통계 칩 (클릭 시 버킷 필터) — $statusCounts는 컨트롤러에서 권한 범위 적용 후 전달됨 --}}
    @php
        $total          = array_sum($statusCounts);
        $bucketDefs     = \App\Http\Controllers\MaintRequestController::bucketStatuses();
        $bucketCount    = fn(array $statuses) => array_sum(array_map(fn($s) => $statusCounts[$s] ?? 0, $statuses));
        $inProgress     = $bucketCount($bucketDefs['in_progress']);
        $reviewing      = $bucketCount($bucketDefs['reviewing']);
        $completed      = $bucketCount($bucketDefs['completed']);

        $bucketUrl = fn($b) => route('maint-requests.index', array_merge(request()->except(['page', 'bucket']), ['bucket' => $b]));
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        {{-- 전체 (indigo) --}}
        <a href="{{ $bucketUrl('all') }}"
           class="flex items-center gap-3 p-4 rounded-xl border transition-all
                  {{ $bucket==='all' ? 'bg-indigo-50 border-indigo-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                        {{ $bucket==='all' ? 'bg-indigo-100 text-indigo-700' : 'bg-indigo-50 text-indigo-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-400">전체</div>
                <div class="text-lg font-semibold {{ $bucket==='all' ? 'text-indigo-700' : 'text-gray-900' }}">{{ number_format($total) }}</div>
            </div>
        </a>

        {{-- 진행/예정 (amber) --}}
        <a href="{{ $bucketUrl('in_progress') }}"
           class="flex items-center gap-3 p-4 rounded-xl border transition-all
                  {{ $bucket==='in_progress' ? 'bg-amber-50 border-amber-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                        {{ $bucket==='in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-amber-50 text-amber-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-400">진행/예정</div>
                <div class="text-lg font-semibold {{ $bucket==='in_progress' ? 'text-amber-700' : 'text-gray-900' }}">{{ number_format($inProgress) }}</div>
            </div>
        </a>

        {{-- 확인/검토 (rose) --}}
        <a href="{{ $bucketUrl('reviewing') }}"
           class="flex items-center gap-3 p-4 rounded-xl border transition-all
                  {{ $bucket==='reviewing' ? 'bg-rose-50 border-rose-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                        {{ $bucket==='reviewing' ? 'bg-rose-100 text-rose-700' : 'bg-rose-50 text-rose-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-400">확인/검토</div>
                <div class="text-lg font-semibold {{ $bucket==='reviewing' ? 'text-rose-700' : 'text-gray-900' }}">{{ number_format($reviewing) }}</div>
            </div>
        </a>

        {{-- 완료 (emerald) --}}
        <a href="{{ $bucketUrl('completed') }}"
           class="flex items-center gap-3 p-4 rounded-xl border transition-all
                  {{ $bucket==='completed' ? 'bg-emerald-50 border-emerald-300 shadow-sm' : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-sm' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                        {{ $bucket==='completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-emerald-50 text-emerald-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-400">완료</div>
                <div class="text-lg font-semibold {{ $bucket==='completed' ? 'text-emerald-700' : 'text-gray-900' }}">{{ number_format($completed) }}</div>
            </div>
        </a>
    </div>

    {{-- Flash (토스트로 전달) --}}
    @php
        $flashSuccess = session('success');
        $flashError   = session('error');

        // 상태 변경 권한 — 관리자 또는 링크더랩 회사 소속 사용자만
        $linkthelabId   = \App\Models\CompanyGroup::where('name', '링크더랩')->value('id');
        $canChangeStatus = auth()->user()->isAdmin()
            || (int) auth()->user()->company_group_id === (int) $linkthelabId;
    @endphp

    {{-- 필터 + 페이지 사이즈 + 엑셀 업로드 --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="bucket" value="{{ $bucket }}">

        @if($canFilterByCompany)
        <select name="company_group_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">회사: 전체</option>
            @foreach($companyGroups as $cg)
                <option value="{{ $cg->id }}" {{ (int)request('company_group_id')===$cg->id ? 'selected' : '' }}>{{ $cg->name }}</option>
            @endforeach
        </select>
        @endif

        <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">상태: 전체</option>
            @foreach($statusLabels as $k => $v)
                <option value="{{ $k }}" {{ request('status')===$k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>

        <select name="priority" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">우선순위: 전체</option>
            @foreach($priorityLabels as $k => $v)
                <option value="{{ $k }}" {{ request('priority')===$k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>

        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="요약·내용 검색"
                   class="pl-9 pr-3 py-2 w-72 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <select name="assignee_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">담당자: 전체</option>
            @foreach($devUsers as $u)
                <option value="{{ $u->id }}" {{ (int)request('assignee_id')===$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>

        <select name="per_page" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            @foreach([30, 50, 100] as $n)
                <option value="{{ $n }}" {{ $perPage===$n ? 'selected' : '' }}>{{ $n }}개씩</option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition-colors">조회</button>
    </form>

    {{-- 엑셀 업로드 (회사 선택 + 비동기 + 프로그레스) — 관리자/SR 담당자만 노출 --}}
    @if($canFilterByCompany)
    <div class="inline-flex flex-col" style="min-width:260px;">
        <form action="{{ route('maint-requests.import') }}" enctype="multipart/form-data" id="maint-import-form" class="inline-flex items-center gap-1.5">
            @csrf
            <select name="company_group_id" id="maint-import-company" required
                    class="px-2.5 py-2 border border-emerald-200 bg-white text-emerald-800 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">회사 선택…</option>
                @foreach($companyGroups as $cg)
                    <option value="{{ $cg->id }}">{{ $cg->name }}</option>
                @endforeach
            </select>
            <label id="maint-import-label" data-disabled="1"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm cursor-pointer transition-colors hover:bg-emerald-100"
                   title="먼저 회사를 선택하세요. 원본 xlsx와 동일한 형식 — 신규 행만 추가됩니다">
                <svg id="maint-import-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span id="maint-import-text">엑셀 업로드</span>
                <input type="file" name="file" id="maint-import-file" accept=".xlsx,.xls" hidden>
            </label>
        </form>
        {{-- 프로그레스 바 (업로드 시작 시 표시) --}}
        <div id="maint-import-progress-wrap" class="hidden mt-1.5">
            <div class="h-2 w-full bg-emerald-100 rounded-full overflow-hidden">
                <div id="maint-import-progress-bar" class="h-full bg-emerald-500 transition-all duration-200" style="width:0%"></div>
            </div>
            <div id="maint-import-progress-label" class="text-xs font-medium text-emerald-700 mt-1 text-center">0%</div>
        </div>
    </div>
    @endif

    @if(request()->hasAny(['q','status','priority','assignee_id','colo_user_id','company_group_id','bucket']))
        <a href="{{ route('maint-requests.index') }}" class="px-3 py-2 text-gray-500 rounded-lg text-sm hover:bg-gray-100 transition-colors">초기화</a>
    @endif

    <button type="button" onclick="maintOpenCreateModal()"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        신규 요청
    </button>

    {{-- 엑셀 다운로드 — 현재 필터 그대로 반영, 화면 전환 없이 바로 다운로드 --}}
    <a href="{{ route('maint-requests.export-excel', request()->query()) }}"
       class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 border border-emerald-300 text-emerald-700 text-sm font-medium rounded-lg hover:bg-emerald-100 transition-colors"
       title="현재 조회 조건의 SR을 엑셀 파일로 다운로드">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
        엑셀 다운로드
    </a>

    <div class="ml-auto text-sm text-gray-500">검색 결과 <span class="font-semibold text-gray-900">{{ number_format($requests->total()) }}</span>건</div>
    </div>

    {{-- 테이블 (thead 고정 + 본문만 스크롤) --}}
    <div id="maint-table-box" class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left w-16">#</th>
                    <th class="px-4 py-3 text-left">메뉴</th>
                    <th class="px-4 py-3 text-left w-24">우선순위</th>
                    <th class="px-4 py-3 text-left">요약</th>
                    <th class="px-4 py-3 text-left w-28">상태</th>
                    <th class="px-4 py-3 text-left w-24">콜로</th>
                    <th class="px-4 py-3 text-left w-24">링크더랩</th>
                    <th class="px-4 py-3 text-left w-28">요청일</th>
                    <th class="px-4 py-3 text-left w-28">완료예정</th>
                    @if(auth()->user()->isAdmin())
                        <th class="px-4 py-3 text-center w-16">삭제</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                <tr class="hover:bg-indigo-50/40 cursor-pointer transition-colors" onclick="maintOpenDetailModal({{ $r->id }})">
                    <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $r->id }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $r->menu?->name ?? '-' }}</td>
                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                        <select class="maint-quick-priority maint-pill-select"
                                data-id="{{ $r->id }}"
                                data-original="{{ $r->priority }}"
                                style="{{ $priorityStyles[$r->priority] ?? '' }}">
                            @foreach($priorityLabels as $k => $v)
                                <option value="{{ $k }}" data-style="{{ $priorityStyles[$k] ?? '' }}" style="{{ $priorityStyles[$k] ?? '' }}" {{ $r->priority===$k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-4 py-3 text-gray-900 max-w-md truncate" title="{{ $r->summary }}">{{ $r->summary }}</td>
                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                        @if($canChangeStatus)
                            <select class="maint-quick-status maint-pill-select"
                                    data-id="{{ $r->id }}"
                                    data-original="{{ $r->status }}"
                                    style="{{ $statusStyles[$r->status] ?? '' }}">
                                @foreach($statusLabels as $k => $v)
                                    <option value="{{ $k }}" data-style="{{ $statusStyles[$k] ?? '' }}" style="{{ $statusStyles[$k] ?? '' }}" {{ $r->status===$k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="maint-pill-static" style="{{ $statusStyles[$r->status] ?? '' }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $r->coloUser?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $r->assignee?->name ?? ($r->assignee_raw ?? '-') }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ optional($r->request_date)->format('Y.m.d') ?: '-' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ optional($r->eta)->format('Y.m.d') ?: '-' }}</td>
                    @if(auth()->user()->isAdmin())
                        <td class="px-2 py-3 text-center" onclick="event.stopPropagation()">
                            <form method="POST" action="{{ route('maint-requests.destroy', $r) }}" class="inline"
                                  onsubmit="return confirm('요청 #{{ $r->id }} 을(를) 삭제하시겠습니까?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-300 hover:text-red-600 p-1 rounded transition-colors" title="삭제">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ auth()->user()->isAdmin() ? 10 : 9 }}" class="px-4 py-20 text-center text-gray-400">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm">등록된 요청이 없습니다</p>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex items-center justify-between">
        <div class="text-xs text-gray-400">
            {{ number_format($requests->firstItem() ?? 0) }}–{{ number_format($requests->lastItem() ?? 0) }}
            <span class="text-gray-300">/</span>
            {{ number_format($requests->total()) }}
        </div>
        {{ $requests->links('vendor.pagination.maint') }}
        <div class="w-32"></div>
    </div>
</div>

{{-- ===== 토스트 컨테이너 ===== --}}
<div id="maint-toast-container" style="position:fixed;top:20px;right:20px;z-index:12000;display:flex;flex-direction:column;gap:8px;pointer-events:none;"></div>

<script>
(function(){
    // ── 토스트 ──────────────────────────────────────────
    window.maintToast = function(type, message, duration){
        duration = duration || 4200;
        var c = document.getElementById('maint-toast-container');
        if (!c) return;
        var t = document.createElement('div');
        var palette = type === 'error'
            ? {bg:'#fef2f2', border:'#fecaca', text:'#b91c1c', icon:'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'}
            : {bg:'#ecfdf5', border:'#a7f3d0', text:'#047857', icon:'M5 13l4 4L19 7'};
        t.style.cssText =
            'background:'+palette.bg+';border:1px solid '+palette.border+';color:'+palette.text+
            ';padding:10px 14px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.08);'+
            'font-size:13px;min-width:280px;max-width:480px;display:flex;align-items:flex-start;gap:8px;'+
            'pointer-events:auto;transform:translateX(20px);opacity:0;transition:transform .2s, opacity .2s;';
        t.innerHTML =
            '<svg style="width:18px;height:18px;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">'+
              '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="'+palette.icon+'"/>'+
            '</svg>'+
            '<div style="flex:1;line-height:1.5;">'+(message||'').replace(/</g,'&lt;')+'</div>'+
            '<button style="background:none;border:0;color:inherit;cursor:pointer;font-size:18px;line-height:1;padding:0 0 0 4px;opacity:.5;">&times;</button>';
        var dismiss = function(){
            t.style.opacity = '0';
            t.style.transform = 'translateX(20px)';
            setTimeout(function(){ t.remove(); }, 220);
        };
        t.querySelector('button').onclick = dismiss;
        c.appendChild(t);
        requestAnimationFrame(function(){
            t.style.opacity = '1';
            t.style.transform = 'translateX(0)';
        });
        setTimeout(dismiss, duration);
    };

    // 세션 플래시 토스트
    @if($flashSuccess)
        document.addEventListener('DOMContentLoaded', function(){ window.maintToast('success', @json($flashSuccess), 5500); });
    @endif
    @if($flashError)
        document.addEventListener('DOMContentLoaded', function(){ window.maintToast('error', @json($flashError), 6000); });
    @endif

    // 리로드를 가로지르는 토스트 전달
    document.addEventListener('DOMContentLoaded', function(){
        try {
            var pending = sessionStorage.getItem('maint_pending_toast');
            if (pending) {
                sessionStorage.removeItem('maint_pending_toast');
                var p = JSON.parse(pending);
                if (p && p.message) window.maintToast(p.type || 'success', p.message, 6000);
            }
        } catch(e){}
    });

    // ── 엑셀 업로드 (XHR + 프로그레스 + 회사 선택) ──────
    var form     = document.getElementById('maint-import-form');
    var input    = document.getElementById('maint-import-file');
    var label    = document.getElementById('maint-import-label');
    var text     = document.getElementById('maint-import-text');
    var icon     = document.getElementById('maint-import-icon');
    var wrap     = document.getElementById('maint-import-progress-wrap');
    var bar      = document.getElementById('maint-import-progress-bar');
    var pct      = document.getElementById('maint-import-progress-label');
    var company  = document.getElementById('maint-import-company');

    if (!form || !input) return;

    // 회사 선택 여부에 따른 업로드 버튼 활성/비활성
    function refreshImportEnabled(){
        if (!company) return;
        var enabled = !!company.value;
        if (enabled) {
            label.dataset.disabled = '0';
            label.style.opacity = '';
            label.style.cursor = 'pointer';
            label.title = '원본 xlsx와 동일한 형식 — 신규 행만 추가됩니다';
        } else {
            label.dataset.disabled = '1';
            label.style.opacity = '0.45';
            label.style.cursor = 'not-allowed';
            label.title = '먼저 회사를 선택하세요';
        }
    }
    if (company) {
        company.addEventListener('change', refreshImportEnabled);
        refreshImportEnabled();
        // 비활성 상태에서 파일 픽커 차단
        label.addEventListener('click', function(e){
            if (label.dataset.disabled === '1') {
                e.preventDefault();
                window.maintToast && window.maintToast('error', '엑셀 업로드 전 회사를 먼저 선택하세요.', 3500);
                company.focus();
            }
        });
    }

    var setProgress = function(p, lbl){
        bar.style.width = Math.max(0, Math.min(100, p)) + '%';
        pct.textContent = lbl || (Math.round(p) + '%');
    };
    var beginUpload = function(){
        wrap.classList.remove('hidden');
        label.style.pointerEvents = 'none';
        label.style.opacity = '0.7';
        text.textContent = '업로드 중…';
        setProgress(0);
    };
    var endUpload = function(){
        label.style.pointerEvents = '';
        label.style.opacity = '';
        text.textContent = '엑셀 업로드';
        setTimeout(function(){
            wrap.classList.add('hidden');
            setProgress(0, '0%');
        }, 800);
        input.value = '';
    };

    input.addEventListener('change', function(){
        if (!input.files.length) return;
        if (company && !company.value) {
            window.maintToast && window.maintToast('error', '회사를 먼저 선택하세요.', 3500);
            input.value = '';
            return;
        }
        var companyName = company ? (company.options[company.selectedIndex]?.text || '') : '';
        if (!confirm('「' + companyName + '」의 신규 SR을 추가합니다. 진행할까요?')) {
            input.value = '';
            return;
        }

        var fd = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        beginUpload();

        xhr.upload.addEventListener('progress', function(e){
            if (e.lengthComputable) {
                var p = (e.loaded / e.total) * 90; // 0~90%는 업로드, 90~100%는 서버 처리
                setProgress(p);
            }
        });
        xhr.upload.addEventListener('load', function(){
            setProgress(90, '서버 처리 중…');
        });

        xhr.onload = function(){
            setProgress(100, '완료');
            var ok = xhr.status >= 200 && xhr.status < 300;
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch(e){}

            if (ok && data && data.ok) {
                try { sessionStorage.setItem('maint_pending_toast', JSON.stringify({type:'success', message:data.message})); } catch(e){}
                setTimeout(function(){ window.location.reload(); }, 350);
            } else {
                var msg = (data && data.message) ? data.message
                        : '업로드 실패 (' + xhr.status + ')';
                window.maintToast('error', msg, 6500);
                endUpload();
            }
        };
        xhr.onerror = function(){
            window.maintToast('error', '네트워크 오류로 업로드에 실패했습니다.', 6000);
            endUpload();
        };
        xhr.send(fd);
    });

    // ── 인라인 우선순위/상태 빠른 변경 ──────────────────
    var csrfTok = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var quickBase = '{{ url('maint-requests') }}';

    function maintQuickPatch(sel, field){
        var id = sel.dataset.id;
        var newVal = sel.value;
        var oldVal = sel.dataset.original;
        if (newVal === oldVal) return;

        var opt = sel.options[sel.selectedIndex];
        var newStyle = opt.dataset.style || '';
        // 이전 옵션의 스타일 저장 (롤백용)
        var oldOpt = Array.prototype.find.call(sel.options, function(o){ return o.value === oldVal; });
        var oldStyle = oldOpt ? (oldOpt.dataset.style || '') : '';

        // 즉시 색상 적용 (낙관적)
        sel.setAttribute('style', newStyle);
        sel.classList.add('is-saving');
        sel.disabled = true;

        var body = {};
        body[field] = newVal;

        fetch(quickBase + '/' + id + '/quick', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfTok,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        })
        .then(function(res){
            return res.json().then(function(j){ return {ok: res.ok && j.ok, data: j, status: res.status}; });
        })
        .then(function(r){
            sel.classList.remove('is-saving');
            sel.disabled = false;
            if (r.ok) {
                sel.dataset.original = newVal;
                sel.classList.add('is-saved');
                setTimeout(function(){ sel.classList.remove('is-saved'); }, 900);
                window.maintToast && window.maintToast('success',
                    (field==='priority'?'우선순위':'상태') + ' 변경됨 (#' + id + ')', 2200);
            } else {
                // 롤백: 값 + 색상
                sel.value = oldVal;
                sel.setAttribute('style', oldStyle);
                window.maintToast && window.maintToast('error',
                    (r.data && r.data.message) ? r.data.message : '변경 실패 (' + r.status + ')', 4000);
            }
        })
        .catch(function(){
            sel.classList.remove('is-saving');
            sel.disabled = false;
            sel.value = oldVal;
            sel.setAttribute('style', oldStyle);
            window.maintToast && window.maintToast('error', '네트워크 오류', 4000);
        });
    }

    document.addEventListener('change', function(e){
        if (e.target.matches('.maint-quick-priority')) maintQuickPatch(e.target, 'priority');
        else if (e.target.matches('.maint-quick-status')) maintQuickPatch(e.target, 'status');
    });
})();
</script>

@push('styles')
<style>
    /* 테이블 박스: 자체적으로 높이 제한 + 스크롤 */
    #maint-table-box {
        max-height: calc(100vh - 360px);
        overflow-y: auto;
    }
    /* thead 행 sticky */
    #maint-table-box thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #fafafa;
        border-bottom: 1px solid #f0f0f0;
        color: #6b7280;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    /* tbody 행 구분선 (얇게) */
    #maint-table-box tbody tr + tr td { border-top: 1px solid #f5f5f5; }

    /* 인라인 변경용 알약 셀렉트 (우선순위·상태) */
    .maint-pill-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: none;
        border: 1px solid transparent;
        border-radius: 9999px;
        padding: 2px 22px 2px 10px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
        cursor: pointer;
        max-width: 100%;
        background-repeat: no-repeat;
        background-position: right 6px center;
        background-size: 10px 10px;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2.5'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3e%3c/svg%3e");
        transition: filter .15s, box-shadow .15s, transform .1s;
    }
    .maint-pill-select:hover { filter: brightness(.96); }
    .maint-pill-select:focus { outline: none; box-shadow: 0 0 0 2px rgba(99,102,241,.35); }
    .maint-pill-select.is-saving { opacity: .6; }
    .maint-pill-select.is-saved {
        animation: maintPillFlash .9s ease;
    }
    /* 읽기 전용 상태 pill (권한 없는 사용자) */
    .maint-pill-static {
        display:inline-block;
        border-radius:9999px;
        padding:2px 10px;
        font-size:12px;
        font-weight:500;
        line-height:1.4;
    }
    @keyframes maintPillFlash {
        0%   { box-shadow: 0 0 0 0 rgba(16,185,129,.0); }
        20%  { box-shadow: 0 0 0 3px rgba(16,185,129,.45); }
        100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
    }
</style>
@endpush

{{-- ===== 상세 보기 모달 (iframe) ===== --}}
<div id="maint-detail-overlay" onclick="maintCloseDetailModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:10900;"></div>

<div id="maint-detail-modal"
     style="display:none;flex-direction:column;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10901;
            background:var(--t50);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);
            width:1100px;max-width:calc(100vw - 32px);height:calc(100vh - 60px);overflow:hidden;">
    <div class="flex items-center justify-between px-5 py-3 bg-white border-b border-gray-100" style="flex:0 0 auto;">
        <h3 class="text-base font-semibold text-gray-900">SR 요청 상세 <span id="maint-detail-id" class="text-gray-400 font-mono text-sm ml-1"></span></h3>
        <div class="flex items-center gap-2">
            <button type="button" onclick="maintCloseDetailModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
    </div>
    <iframe id="maint-detail-iframe" src="about:blank" style="flex:1 1 0;width:100%;border:0;background:var(--t50);"></iframe>
</div>

<script>
function maintOpenDetailModal(id){
    var url = '{{ url('maint-requests') }}/' + id + '/embed';
    document.getElementById('maint-detail-iframe').src = url;
    document.getElementById('maint-detail-id').textContent = '#' + id;
    document.getElementById('maint-detail-overlay').style.display = 'block';
    var m = document.getElementById('maint-detail-modal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function maintCloseDetailModal(){
    document.getElementById('maint-detail-overlay').style.display = 'none';
    document.getElementById('maint-detail-modal').style.display   = 'none';
    document.getElementById('maint-detail-iframe').src = 'about:blank';
    document.body.style.overflow = '';
}
// iframe 내부에서 호출: 삭제 후 모달 닫고 인덱스 새로고침
function maintHandleModalClose(reloadList){
    maintCloseDetailModal();
    if (reloadList) {
        // 목록 영역 새로고침을 위해 페이지 리로드 (쿼리 보존)
        window.location.reload();
    }
}
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && document.getElementById('maint-detail-modal').style.display === 'flex') {
        maintCloseDetailModal();
    }
});
</script>

{{-- ===== 새 요청 모달 ===== --}}
<div id="maint-create-overlay" onclick="maintCloseCreateModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:11000;"></div>

<div id="maint-create-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:11001;
            background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.2);
            width:720px;max-width:calc(100vw - 32px);max-height:calc(100vh - 60px);overflow:auto;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">새 SR 요청</h3>
        <button type="button" onclick="maintCloseCreateModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
    </div>

    <form method="POST" action="{{ route('maint-requests.store') }}" class="p-5 space-y-4">
        @csrf

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-8">
                <label class="block text-sm font-medium text-gray-700 mb-1">메뉴</label>
                <input list="modal-menu-list" name="menu_name" value="{{ old('menu_name') }}"
                       placeholder="메뉴명 입력 (목록에 없으면 자동 등록)"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <datalist id="modal-menu-list">
                    @foreach($menus as $m)<option value="{{ $m->name }}"></option>@endforeach
                </datalist>
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">우선순위 <span class="text-red-500">*</span></label>
                <select name="priority" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                    @foreach($priorityLabels as $k => $v)
                        <option value="{{ $k }}" {{ old('priority', 'normal')===$k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">요약 <span class="text-red-500">*</span></label>
            <input type="text" name="summary" value="{{ old('summary') }}" required maxlength="500"
                   placeholder="한 줄 요약"
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">상세 내용</label>
            {{-- Quill 리치 에디터 (이미지 paste · 리사이즈 · 뷰어) — SR 상세와 동일 동작 --}}
            <div class="sr-quill" id="sr-modal-quill-wrap">
                <div id="sr-modal-quill-editor"></div>
            </div>
            <input type="hidden" name="content" id="sr-modal-content-input" value="{{ old('content') }}">
        </div>

        {{-- 콜로 담당자(요청자) 자동, 요청일은 등록 시각, 상태는 'requested' 자동 --}}
        <input type="hidden" name="colo_user_name" value="{{ auth()->user()->name }}">
        <input type="hidden" name="request_date" value="{{ now()->toDateString() }}">
        <input type="hidden" name="status" value="requested">

        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
            <button type="button" onclick="maintCloseCreateModal()"
                    class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">취소</button>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">등록</button>
        </div>
    </form>
</div>

<script>
function maintOpenCreateModal(){
    document.getElementById('maint-create-overlay').style.display = 'block';
    document.getElementById('maint-create-modal').style.display   = 'block';
    document.body.style.overflow = 'hidden';
}
function maintCloseCreateModal(){
    document.getElementById('maint-create-overlay').style.display = 'none';
    document.getElementById('maint-create-modal').style.display   = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && document.getElementById('maint-create-modal').style.display === 'block') {
        maintCloseCreateModal();
    }
});
@if($errors->any())
    // 검증 실패 시 모달 자동 재오픈
    document.addEventListener('DOMContentLoaded', maintOpenCreateModal);
@endif
</script>

{{-- 공유 이미지 라이트박스 (전체 창 · 다운로드) --}}
@include('maint-requests._image-lightbox')

{{-- 신규 SR 모달의 Quill 리치 에디터 (이미지 paste · 리사이즈 · 뷰어) — SR 상세와 동일한 기능 --}}
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
    .sr-quill { border:1px solid var(--color-border-default); border-radius:8px; transition:border-color .15s; }
    .sr-quill.focused { border-color:#6366f1; }
    .sr-quill .ql-toolbar { border:none; border-bottom:1px solid var(--color-border-default); padding:6px 10px; background:#f8fafc; border-radius:8px 8px 0 0; }
    .sr-quill .ql-container { border:none; font-family:inherit; }
    .sr-quill .ql-editor { min-height:180px; max-height:380px; overflow-y:auto; padding:12px 14px; font-size:13.5px; color:var(--color-text-primary); line-height:1.6; }
    .sr-quill .ql-editor.ql-blank::before { font-style:normal; color:var(--color-text-placeholder); }
    .sr-quill .ql-editor img { max-width:100%; height:auto; border-radius:6px; margin:6px 0; cursor:pointer; }
    .sr-quill .ql-editor img.sr-img-selected { outline:2px solid var(--t500); outline-offset:1px; }
</style>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function() {
    const editorEl = document.getElementById('sr-modal-quill-editor');
    const wrapEl   = document.getElementById('sr-modal-quill-wrap');
    const hiddenEl = document.getElementById('sr-modal-content-input');
    if (!editorEl || !hiddenEl) return;

    const UPLOAD_URL = @json(route('maint-requests.upload-image'));
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token());

    const quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: '상세 내용을 입력하세요. 이미지는 복사·붙여넣기(Ctrl+V) 또는 툴바 아이콘으로 첨부됩니다.',
        modules: {
            toolbar: [
                [{ header: [false, 1, 2, 3] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                ['link', 'image'],
                ['clean'],
            ],
        },
    });

    // 초기 콘텐츠 (old() 값) 로드
    const initial = (hiddenEl.value || '').trim();
    if (initial) {
        if (/<\w+[\s\S]*?>/.test(initial)) {
            quill.clipboard.dangerouslyPasteHTML(0, initial);
        } else {
            quill.setText(initial);
        }
    }

    quill.on('selection-change', r => { wrapEl.classList.toggle('focused', !!r); });

    const form = editorEl.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            const html = quill.getLength() <= 1 ? '' : quill.root.innerHTML;
            hiddenEl.value = html;
        });
    }

    function uploadImage(file) {
        if (!file) return;
        const fd = new FormData();
        fd.append('image', file);
        fetch(UPLOAD_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
            credentials: 'same-origin',
        })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
            if (!data.url) return;
            const range = quill.getSelection(true) || { index: quill.getLength() };
            quill.insertEmbed(range.index, 'image', data.url);
            quill.setSelection(range.index + 1);
        })
        .catch(err => alert('이미지 업로드 실패: ' + err));
    }

    quill.getModule('toolbar').addHandler('image', () => {
        const inp = document.createElement('input');
        inp.type = 'file'; inp.accept = 'image/*';
        inp.onchange = () => { if (inp.files[0]) uploadImage(inp.files[0]); };
        inp.click();
    });
    quill.root.addEventListener('paste', e => {
        const imgItem = [...(e.clipboardData?.items || [])].find(it => it.type.startsWith('image/'));
        if (!imgItem) return;
        e.preventDefault();
        uploadImage(imgItem.getAsFile());
    });

    // 이미지 클릭 → 라이트박스로 큰 화면 보기 (전체 창 · 다운로드 기능)
    quill.root.addEventListener('click', (e) => {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            if (window.openSrImageLightbox) window.openSrImageLightbox(e.target.src, e.target.alt || '');
        }
    });
})();
</script>
@endsection
