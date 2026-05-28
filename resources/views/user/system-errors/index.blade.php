@extends('layouts.app')

@section('title', __('app.user_system_errors'))

@section('content')
@php
    $isAdmin = auth()->user()?->isAdmin();
@endphp
<div class="p-6">

    {{-- 헤더 --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ __('app.user_system_errors') }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('app.user_syserr_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            @if($stats['unresolved'] > 0)
            <form method="POST" action="{{ route('user.system-errors.resolve-all') }}"
                  onsubmit="return confirm('{{ __('app.user_syserr_resolve_all_confirm', ['count' => $stats['unresolved']]) }}')">
                @csrf @method('PATCH')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('app.user_syserr_resolve_all') }}
                </button>
            </form>
            @endif
            @if($isAdmin && $stats['resolved'] > 0)
            <form method="POST" action="{{ route('user.system-errors.destroy-resolved') }}"
                  onsubmit="return confirm('{{ __('app.user_syserr_delete_resolved_confirm', ['count' => $stats['resolved']]) }}')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    {{ __('app.user_syserr_delete_resolved') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- 통계 카드 --}}
    <div class="flex gap-3 mb-4">
        <div class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-base shrink-0">📋</div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-slate-800 leading-tight">{{ number_format($stats['total']) }}</div>
                <div class="text-xs text-slate-500">{{ __('app.user_syserr_stat_total') }}</div>
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
                <div class="text-xs text-slate-500">{{ __('app.user_syserr_stat_unresolved') }}</div>
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
                <div class="text-xs text-slate-500">{{ __('app.user_syserr_stat_resolved') }}</div>
            </div>
        </div>
    </div>

    {{-- 필터 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            {{-- 상태 탭 --}}
            <div class="flex rounded-lg border border-slate-200 overflow-hidden text-sm">
                @foreach(['unresolved' => __('app.user_syserr_stat_unresolved'), 'resolved' => __('app.user_syserr_stat_resolved'), 'all' => __('app.user_syserr_status_all')] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => 1]) }}"
                   class="px-4 py-2 font-medium transition {{ $status === $val ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            {{-- 레벨 필터 --}}
            <select name="level" onchange="this.form.submit()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">{{ __('app.user_syserr_all_levels') }}</option>
                @foreach(['error' => '🔴 Error', 'warning' => '🟡 Warning', 'info' => '🔵 Info'] as $val => $label)
                <option value="{{ $val }}" {{ $level === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            {{-- origin 필터 --}}
            <select name="origin" onchange="this.form.submit()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">{{ __('app.user_syserr_all_origins') }}</option>
                @foreach(['server' => 'Server', 'client' => 'Client', 'console' => 'Console', 'job' => 'Job'] as $val => $label)
                <option value="{{ $val }}" {{ $origin === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            {{-- 검색 --}}
            <div class="flex flex-1 min-w-52 gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input name="search" value="{{ $search }}" placeholder="{{ __('app.user_syserr_search_placeholder') }}"
                    class="flex-1 text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <button type="submit"
                    class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">{{ __('app.user_syserr_search_btn') }}</button>
                @if($search)
                <a href="{{ route('user.system-errors.index', ['status' => $status, 'level' => $level]) }}"
                   class="px-3 py-2 text-sm text-slate-500 border border-slate-200 rounded-lg hover:bg-slate-50 transition">{{ __('app.user_syserr_search_reset') }}</a>
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
                <div class="flex-1 min-w-0">
                    <a href="{{ route('user.system-errors.show', $err) }}"
                       class="font-medium text-slate-800 hover:text-indigo-600 transition text-sm line-clamp-1 block">
                        {{ $err->message }}
                    </a>
                    <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs text-slate-400">
                        @if($err->origin)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 font-mono">
                            {{ $err->origin }}
                        </span>
                        @endif
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
                    <form method="POST" action="{{ route('user.system-errors.resolve', $err) }}">
                        @csrf @method('PATCH')
                        <button type="submit" title="{{ __('app.user_syserr_mark_resolved') }}"
                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </form>
                    @else
                    <span class="text-xs text-emerald-600 font-medium">{{ __('app.user_syserr_resolved_badge') }}</span>
                    @endif
                    <a href="{{ route('user.system-errors.show', $err) }}" title="{{ __('app.user_syserr_detail') }}"
                        class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-indigo-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @if($isAdmin)
                    <form method="POST" action="{{ route('user.system-errors.destroy', $err) }}"
                          onsubmit="return confirm('{{ __('app.user_syserr_delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" title="{{ __('app.user_syserr_delete') }}"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-16 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-medium">{{ __('app.user_syserr_no_errors_msg') }}</p>
            <p class="text-sm mt-1">
                @if($status === 'unresolved') {{ __('app.user_syserr_all_resolved') }}
                @else {{ __('app.user_syserr_no_match') }}
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
@endsection
