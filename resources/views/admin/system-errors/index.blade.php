@extends('layouts.admin')

@section('title', __('admin.system_errors'))

@section('content')
<div class="p-6">

    {{-- 헤더 --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ __('admin.system_errors') }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('admin.syserr_app_exceptions') }}</p>
        </div>
        <div class="flex gap-2">
            @if($stats['unresolved'] > 0)
            <form method="POST" action="{{ route('admin.system-errors.resolve-all') }}"
                  onsubmit="return confirm('{{ __('admin.syserr_resolve_all_confirm', ['count' => $stats['unresolved']]) }}')">
                @csrf @method('PATCH')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('admin.syserr_resolve_all') }}
                </button>
            </form>
            @endif
            @if($stats['resolved'] > 0)
            <form method="POST" action="{{ route('admin.system-errors.destroy-resolved') }}"
                  onsubmit="return confirm('{{ __('admin.syserr_delete_resolved_confirm', ['count' => $stats['resolved']]) }}')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    {{ __('admin.syserr_delete_resolved') }}
                </button>
            </form>
            @endif
            @if($stats['total'] > 0 && auth('admin')->user()?->isSuperAdmin())
            <form method="POST" action="{{ route('admin.reset.system-errors') }}"
                  onsubmit="return confirm('{{ __('admin.syserr_reset_all_confirm', ['count' => $stats['total']]) }}')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-red-600 border border-red-600 rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    {{ __('admin.syserr_reset_all') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

    {{-- 통계 카드 --}}
    <div class="flex gap-3 mb-6">
        <div class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-base shrink-0">📋</div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-slate-800 leading-tight">{{ number_format($stats['total']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.syserr_stat_total') }}</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center text-base shrink-0">🔴</div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-red-600 leading-tight">{{ number_format($stats['error']) }}</div>
                <div class="text-xs text-slate-500">Error</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-yellow-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center text-base shrink-0">🟡</div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-yellow-600 leading-tight">{{ number_format($stats['warning']) }}</div>
                <div class="text-xs text-slate-500">Warning</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-blue-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-base shrink-0">🔵</div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-blue-600 leading-tight">{{ number_format($stats['info']) }}</div>
                <div class="text-xs text-slate-500">Info</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center text-red-500 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-red-600 leading-tight">{{ number_format($stats['unresolved']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.syserr_stat_unresolved') }}</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-emerald-600 leading-tight">{{ number_format($stats['resolved']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.syserr_stat_resolved') }}</div>
            </div>
        </div>
    </div>

    {{-- 필터 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            {{-- 상태 탭 --}}
            <div class="flex rounded-lg border border-slate-200 overflow-hidden text-sm">
                @foreach(['unresolved' => __('admin.syserr_stat_unresolved'), 'resolved' => __('admin.syserr_stat_resolved'), 'all' => __('admin.status_all')] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => 1]) }}"
                   class="px-4 py-2 font-medium transition {{ $status === $val ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    @if($val === 'unresolved' && $stats['unresolved'] > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs rounded-full {{ $status === 'unresolved' ? 'bg-red-400 text-white' : 'bg-red-100 text-red-600' }}">
                        {{ $stats['unresolved'] > 99 ? '99+' : $stats['unresolved'] }}
                    </span>
                    @endif
                </a>
                @endforeach
            </div>

            {{-- 레벨 필터 --}}
            <select name="level" onchange="this.form.submit()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">{{ __('admin.syserr_all_levels') }}</option>
                @foreach(['error' => '🔴 Error', 'warning' => '🟡 Warning', 'info' => '🔵 Info'] as $val => $label)
                <option value="{{ $val }}" {{ $level === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            {{-- 검색 --}}
            <div class="flex flex-1 min-w-52 gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input name="search" value="{{ $search }}" placeholder="{{ __('admin.syserr_search_placeholder') }}"
                    class="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <button type="submit"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">{{ __('admin.search_btn') }}</button>
                @if($search)
                <a href="{{ route('admin.system-errors.index', ['status' => $status, 'level' => $level]) }}"
                   class="px-3 py-2 text-sm text-slate-500 border border-slate-200 rounded-lg hover:bg-slate-50 transition">{{ __('admin.syserr_search_reset') }}</a>
                @endif
            </div>
        </form>
    </div>

    {{-- 에러 목록 --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        @forelse($errorLogs as $err)
        <div class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition group">
            <div class="flex items-start gap-3 px-5 py-4">
                {{-- 레벨 뱃지 --}}
                <div class="mt-0.5 shrink-0">
                    @if($err->level === 'info')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">🔵 INFO</span>
                    @elseif($err->level === 'warning')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">🟡 WARNING</span>
                    @elseif(in_array($err->level, ['critical', 'alert', 'emergency']))
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">🟣 {{ strtoupper($err->level) }}</span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">🔴 ERROR</span>
                    @endif
                </div>

                {{-- 본문 --}}
                <div class="flex-1 min-w-0" data-err="{{ json_encode(['id'=>$err->id,'level'=>$err->level,'is_resolved'=>$err->is_resolved,'message'=>$err->message,'exception'=>$err->exception,'file'=>$err->file,'line'=>$err->line,'context'=>$err->context,'trace'=>$err->trace,'created_at'=>$err->created_at->format('Y-m-d H:i:s')]) }}">
                    <button type="button" onclick="openErrModal(this.closest('[data-err]'))"
                       class="font-medium text-slate-800 hover:text-indigo-600 transition text-sm line-clamp-1 text-left w-full">
                        {{ $err->message }}
                    </button>
                    <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs text-slate-400">
                        @if($err->exception)
                        <span class="font-mono text-indigo-500">{{ class_basename($err->exception) }}</span>
                        @endif
                        @if($err->file)
                        <span class="truncate max-w-sm" title="{{ $err->file }}">
                            {{ basename($err->file) }}@if($err->line):{{ $err->line }}@endif
                        </span>
                        @endif
                        @if(!empty($err->context['url']))
                        <span class="truncate max-w-sm" title="{{ $err->context['url'] }}">
                            {{ $err->context['method'] ?? '' }} {{ parse_url($err->context['url'], PHP_URL_PATH) }}
                        </span>
                        @endif
                        <span>{{ $err->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>

                {{-- 액션 --}}
                <div class="flex items-center gap-2 shrink-0 opacity-0 group-hover:opacity-100 transition">
                    @if(!$err->is_resolved)
                    <form method="POST" action="{{ route('admin.system-errors.resolve', $err) }}">
                        @csrf @method('PATCH')
                        <button type="submit" title="{{ __('admin.syserr_mark_resolved') }}"
                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </form>
                    @else
                    <span class="text-xs text-emerald-600 font-medium">{{ __('admin.syserr_resolved_badge') }}</span>
                    @endif
                    <a href="{{ route('admin.system-errors.show', $err) }}" title="{{ __('admin.log_detail') }}"
                        class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-indigo-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('admin.system-errors.destroy', $err) }}"
                          onsubmit="return confirm('{{ __('admin.syserr_delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" title="{{ __('admin.syserr_delete') }}"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-16 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-medium">{{ __('admin.syserr_no_errors_msg') }}</p>
            <p class="text-sm mt-1">
                @if($status === 'unresolved') {{ __('admin.syserr_all_resolved') }}
                @else {{ __('admin.syserr_no_match') }}
                @endif
            </p>
        </div>
        @endforelse
    </div>

    {{-- 페이지네이션 --}}
    @if($errorLogs->hasPages())
    <div class="mt-4">
        {{ $errorLogs->links() }}
    </div>
    @endif

</div>

{{-- 에러 상세 팝업 --}}
<div id="errModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeErrModal()">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl" style="max-height:90vh;display:flex;flex-direction:column;overflow:hidden;">
        {{-- 모달 헤더 --}}
        <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-100" style="flex-shrink:0;">
            <div class="flex-1 min-w-0">
                <div id="em-badges" class="flex items-center gap-2 mb-1"></div>
                <p id="em-message" class="text-sm font-semibold text-slate-800 leading-snug"></p>
                <p id="em-exception" class="mt-0.5 text-xs font-mono text-indigo-500"></p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button id="em-resolve-btn" type="button" onclick="errModalResolve()"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('admin.syserr_mark_resolved') }}
                </button>
                <button id="em-delete-btn" type="button" onclick="errModalDelete()"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    {{ __('admin.syserr_delete') }}
                </button>
                <button type="button" onclick="closeErrModal()" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        {{-- 모달 본문 --}}
        <div class="p-6 space-y-4" style="flex:1;min-height:0;overflow-y:auto;">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_col_occurrence') }}</h3>
                    <dl id="em-occurrence" class="space-y-2 text-xs"></dl>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_col_request_context') }}</h3>
                    <dl id="em-context" class="space-y-2 text-xs"></dl>
                </div>
            </div>
            <div id="em-user-wrap" class="bg-slate-50 border border-slate-200 rounded-xl p-4 hidden">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_user_info') }}</h3>
                <dl id="em-user" class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs"></dl>
            </div>
            <div id="em-reqdata-wrap" class="bg-slate-50 border border-slate-200 rounded-xl p-4 hidden">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_request_data') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 mb-2">{{ __('admin.syserr_query_label') }}</p>
                        <dl id="em-query" class="space-y-1 text-xs font-mono"></dl>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-2">{{ __('admin.syserr_input_label') }}</p>
                        <dl id="em-input" class="space-y-1 text-xs font-mono"></dl>
                    </div>
                </div>
            </div>
            <div id="em-trace-wrap" class="bg-slate-900 rounded-xl p-5 hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stack Trace</h3>
                    <button onclick="copyErrTrace()" class="text-xs text-slate-400 hover:text-slate-200 transition px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">{{ __('admin.syserr_copy') }}</button>
                </div>
                <pre id="em-trace" class="text-xs text-slate-300 font-mono whitespace-pre-wrap break-all leading-relaxed" style="max-height:18rem;overflow-y:auto;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
const ADMIN_C_STR = {
    resolved:       '{{ __("admin.syserr_resolved_badge") }}',
    noInfo:         '{{ __("admin.syserr_no_info") }}',
    contextNoInfo:  '{{ __("admin.syserr_context_no_info") }}',
    userIdLabel:    '{{ __("admin.syserr_user_id_label") }}',
    notLoggedIn:    '{{ __("admin.syserr_not_logged_in") }}',
    sourceLabel:    '{{ __("admin.syserr_source_label") }}',
    fileLabel:      '{{ __("admin.syserr_file_label") }}',
    routeLabel:     '{{ __("admin.syserr_route_label") }}',
    actionLabel:    '{{ __("admin.syserr_action_label") }}',
    userLabel:      '{{ __("admin.syserr_user_label") }}',
    userEmailLabel: '{{ __("admin.syserr_user_email_label") }}',
    userRoleLabel:  '{{ __("admin.syserr_user_role_label") }}',
    userCompanyLabel: '{{ __("admin.syserr_user_company_label") }}',
    adminUserLabel: '{{ __("admin.syserr_admin_user_label") }}',
    roleAdmin:      '{{ __("admin.syserr_role_admin") }}',
    roleMember:     '{{ __("admin.syserr_role_member") }}',
    roleGuest:      '{{ __("admin.syserr_role_guest") }}',
    deleteConfirm:  '{{ __("admin.syserr_delete_confirm") }}',
    copy:           '{{ __("admin.syserr_copy") }}',
    copied:         '{{ __("admin.syserr_copied") }}',
};

function _roleLabel(role) {
    return ({admin: ADMIN_C_STR.roleAdmin, member: ADMIN_C_STR.roleMember, guest: ADMIN_C_STR.roleGuest})[role] || role;
}

let _emId = null;
const _emCsrf = '{{ csrf_token() }}';

async function openErrModal(el) {
    const d = JSON.parse(el.dataset.err);
    _emId = d.id;

    // badges
    const badges = document.getElementById('em-badges');
    const levelMap = {critical:'bg-purple-100 text-purple-700', warning:'bg-yellow-100 text-yellow-700', info:'bg-blue-100 text-blue-700'};
    const lClass = levelMap[d.level] || 'bg-red-100 text-red-700';
    let html = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${lClass}">${d.level.toUpperCase()}</span>`;
    if (d.is_resolved) html += `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">${ADMIN_C_STR.resolved}</span>`;
    html += `<span class="text-xs text-slate-400">${d.created_at}</span>`;
    badges.innerHTML = html;

    document.getElementById('em-message').textContent = d.message || '';
    document.getElementById('em-exception').textContent = d.exception || '';
    document.getElementById('em-exception').style.display = d.exception ? '' : 'none';

    const ctx = d.context || {};

    // 발생 위치 (file + route)
    let occHtml = '';
    if (d.file) occHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.fileLabel}</dt><dd class="font-mono text-slate-700 break-all">${escH(d.file + (d.line ? ':' + d.line : ''))}</dd></div>`;
    if (ctx.route_name)   occHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.routeLabel}</dt><dd class="font-mono text-slate-700 break-all">${escH(ctx.route_name)}</dd></div>`;
    if (ctx.route_action) occHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.actionLabel}</dt><dd class="font-mono text-slate-700 break-all">${escH(ctx.route_action)}</dd></div>`;
    document.getElementById('em-occurrence').innerHTML = occHtml || `<p class="text-slate-400">${ADMIN_C_STR.noInfo}</p>`;

    // context (URL/IP/source)
    let ctxHtml = '';
    if (ctx.url) ctxHtml += `<div><dt class="text-slate-400 mb-0.5">URL</dt><dd class="text-slate-700 break-all"><span class="font-mono font-semibold text-indigo-500">${escH(ctx.method||'')}</span> ${escH(ctx.url)}</dd></div>`;
    if (ctx.ip) ctxHtml += `<div><dt class="text-slate-400 mb-0.5">IP</dt><dd class="font-mono text-slate-700">${escH(ctx.ip)}</dd></div>`;
    if (ctx.source) ctxHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.sourceLabel}</dt><dd class="text-slate-700">${escH(ctx.source)}</dd></div>`;
    document.getElementById('em-context').innerHTML = ctxHtml || `<p class="text-slate-400">${ADMIN_C_STR.contextNoInfo}</p>`;

    // 사용자 정보
    const hasUser = ctx.user_name || ctx.user_email || ctx.user_role || ctx.user_company || ctx.admin_user_id || ctx.user_id !== undefined;
    const userWrap = document.getElementById('em-user-wrap');
    if (hasUser) {
        let userHtml = '';
        if (ctx.user_name || ctx.user_id !== undefined) {
            const uname = ctx.user_name
                ? `${escH(ctx.user_name)}${ctx.user_id ? ` <span class="text-slate-400 font-mono ml-1">#${escH(String(ctx.user_id))}</span>` : ''}`
                : (ctx.user_id ? `<span class="font-mono">#${escH(String(ctx.user_id))}</span>` : ADMIN_C_STR.notLoggedIn);
            userHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.userLabel}</dt><dd class="text-slate-700">${uname}</dd></div>`;
        }
        if (ctx.user_email)   userHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.userEmailLabel}</dt><dd class="text-slate-700 break-all">${escH(ctx.user_email)}</dd></div>`;
        if (ctx.user_role)    userHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.userRoleLabel}</dt><dd class="text-slate-700">${escH(_roleLabel(ctx.user_role))}</dd></div>`;
        if (ctx.user_company) userHtml += `<div><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.userCompanyLabel}</dt><dd class="text-slate-700">${escH(ctx.user_company)}</dd></div>`;
        if (ctx.admin_user_id) {
            userHtml += `<div class="col-span-2"><dt class="text-slate-400 mb-0.5">${ADMIN_C_STR.adminUserLabel}</dt><dd class="text-slate-700">${escH(ctx.admin_user_name || '')} <span class="text-slate-400 font-mono ml-1">#${escH(String(ctx.admin_user_id))}</span></dd></div>`;
        }
        document.getElementById('em-user').innerHTML = userHtml;
        userWrap.classList.remove('hidden');
    } else {
        userWrap.classList.add('hidden');
    }

    // 요청 데이터 (query / input)
    const hasReq = (ctx.query && Object.keys(ctx.query).length) || (ctx.input && Object.keys(ctx.input).length);
    const reqWrap = document.getElementById('em-reqdata-wrap');
    if (hasReq) {
        document.getElementById('em-query').innerHTML = _kvList(ctx.query);
        document.getElementById('em-input').innerHTML = _kvList(ctx.input);
        reqWrap.classList.remove('hidden');
    } else {
        reqWrap.classList.add('hidden');
    }

    // trace
    const traceWrap = document.getElementById('em-trace-wrap');
    if (d.trace) {
        document.getElementById('em-trace').textContent = d.trace;
        traceWrap.classList.remove('hidden');
    } else {
        traceWrap.classList.add('hidden');
    }

    // resolve button
    document.getElementById('em-resolve-btn').style.display = d.is_resolved ? 'none' : '';

    document.getElementById('errModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

async function closeErrModal() {
    document.getElementById('errModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function errModalResolve() {
    if (!_emId) return;
    const url = `{{ url('admin/system-errors') }}/${_emId}/resolve`;
    const res = await fetch(url, { method:'PATCH', headers:{'X-CSRF-TOKEN':_emCsrf,'Accept':'application/json'} });
    if (res.ok) { closeErrModal(); location.reload(); }
}

async function errModalDelete() {
    if (!_emId || !await __confirm(ADMIN_C_STR.deleteConfirm)) return;
    const url = `{{ url('admin/system-errors') }}/${_emId}`;
    const res = await fetch(url, { method:'DELETE', headers:{'X-CSRF-TOKEN':_emCsrf,'Accept':'application/json'} });
    if (res.ok) { closeErrModal(); location.reload(); }
}

async function copyErrTrace() {
    const text = document.getElementById('em-trace').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        btn.textContent = ADMIN_C_STR.copied;
        setTimeout(() => btn.textContent = ADMIN_C_STR.copy, 2000);
    });
}

function escH(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function _kvList(obj) {
    if (!obj || !Object.keys(obj).length) return `<p class="text-slate-400">—</p>`;
    return Object.entries(obj).map(([k, v]) =>
        `<div class="flex gap-2"><dt class="text-indigo-500 shrink-0">${escH(k)}:</dt><dd class="text-slate-700 break-all">${escH(String(v ?? ''))}</dd></div>`
    ).join('');
}
</script>
@endsection
