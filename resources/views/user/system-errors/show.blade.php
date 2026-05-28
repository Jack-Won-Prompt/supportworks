@extends('layouts.app')

@section('title', __('app.user_syserr_detail'))

@section('content')
@php
    $isAdmin = auth()->user()?->isAdmin();
    $ctx = is_array($error->context) ? $error->context : [];
@endphp
<div class="p-6 max-w-5xl mx-auto">

    {{-- 뒤로가기 + 상단 메타 --}}
    <div class="flex items-center justify-between mb-5">
        <a href="{{ route('user.system-errors.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('app.user_syserr_back_to_list') }}
        </a>
        <div class="flex items-center gap-2">
            @if(!$error->is_resolved)
            <form method="POST" action="{{ route('user.system-errors.resolve', $error) }}">
                @csrf @method('PATCH')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('app.user_syserr_mark_resolved') }}
                </button>
            </form>
            @endif
            @if($isAdmin)
            <form method="POST" action="{{ route('user.system-errors.destroy', $error) }}"
                  onsubmit="return confirm('{{ __('app.user_syserr_delete_confirm') }}')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    {{ __('app.user_syserr_delete') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- 헤더 카드 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-4">
        <div class="flex items-center gap-2 mb-2">
            @if($error->level === 'info')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">🔵 INFO</span>
            @elseif($error->level === 'warning')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">🟡 WARNING</span>
            @elseif(in_array($error->level, ['critical','alert','emergency']))
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">🟣 {{ strtoupper($error->level) }}</span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">🔴 ERROR</span>
            @endif
            @if($error->is_resolved)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">{{ __('app.user_syserr_resolved_badge') }}</span>
            @endif
            @if($error->source)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 font-mono">
                    {{ $error->source }}@if($error->origin) · {{ $error->origin }}@endif
                </span>
            @endif
            <span class="text-xs text-slate-400">{{ $error->created_at->format('Y-m-d H:i:s') }}</span>
        </div>
        <p class="text-base font-semibold text-slate-800 break-words">{{ $error->message }}</p>
        @if($error->exception)
            <p class="mt-1 text-xs font-mono text-indigo-500">{{ $error->exception }}</p>
        @endif
    </div>

    {{-- 발생 위치 & 요청 컨텍스트 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('app.user_syserr_col_occurrence') }}</h3>
            <dl class="space-y-2 text-xs">
                @if($error->file)
                <div>
                    <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_file_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all">{{ $error->file }}@if($error->line):{{ $error->line }}@endif</dd>
                </div>
                @endif
                @if(!empty($ctx['route_name']))
                <div>
                    <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_route_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all">{{ $ctx['route_name'] }}</dd>
                </div>
                @endif
                @if(!empty($ctx['route_action']))
                <div>
                    <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_action_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all">{{ $ctx['route_action'] }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('app.user_syserr_col_request_context') }}</h3>
            <dl class="space-y-2 text-xs">
                @if(!empty($ctx['url']))
                <div>
                    <dt class="text-slate-400 mb-0.5">URL</dt>
                    <dd class="text-slate-700 break-all">
                        <span class="font-mono font-semibold text-indigo-500">{{ $ctx['method'] ?? '' }}</span> {{ $ctx['url'] }}
                    </dd>
                </div>
                @endif
                @if(!empty($ctx['ip']))
                <div>
                    <dt class="text-slate-400 mb-0.5">IP</dt>
                    <dd class="font-mono text-slate-700">{{ $ctx['ip'] }}</dd>
                </div>
                @endif
                @if(!empty($ctx['user_agent']))
                <div>
                    <dt class="text-slate-400 mb-0.5">User-Agent</dt>
                    <dd class="text-slate-700 break-all">{{ $ctx['user_agent'] }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- 사용자 정보 (있을 때만) --}}
    @if(!empty($ctx['user_name']) || !empty($ctx['user_email']) || !empty($ctx['user_id']))
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('app.user_syserr_user_info') }}</h3>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
            @if(!empty($ctx['user_name']) || !empty($ctx['user_id']))
            <div>
                <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_user_label') }}</dt>
                <dd class="text-slate-700">
                    {{ $ctx['user_name'] ?? '' }}
                    @if(!empty($ctx['user_id']))<span class="text-slate-400 font-mono ml-1">#{{ $ctx['user_id'] }}</span>@endif
                </dd>
            </div>
            @endif
            @if(!empty($ctx['user_email']))
            <div>
                <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_user_email_label') }}</dt>
                <dd class="text-slate-700 break-all">{{ $ctx['user_email'] }}</dd>
            </div>
            @endif
            @if(!empty($ctx['user_role']))
            <div>
                <dt class="text-slate-400 mb-0.5">{{ __('app.user_syserr_user_role_label') }}</dt>
                <dd class="text-slate-700">{{ $ctx['user_role'] }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @endif

    {{-- 요청 데이터 (query / input) --}}
    @if(!empty($ctx['query']) || !empty($ctx['input']))
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('app.user_syserr_request_data') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-slate-400 mb-2">{{ __('app.user_syserr_query_label') }}</p>
                @if(!empty($ctx['query']))
                <dl class="space-y-1 text-xs font-mono">
                    @foreach($ctx['query'] as $k => $v)
                    <div class="flex gap-2">
                        <dt class="text-indigo-500 shrink-0">{{ $k }}:</dt>
                        <dd class="text-slate-700 break-all">{{ is_scalar($v) ? $v : json_encode($v) }}</dd>
                    </div>
                    @endforeach
                </dl>
                @else
                <p class="text-xs text-slate-400">—</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-2">{{ __('app.user_syserr_input_label') }}</p>
                @if(!empty($ctx['input']))
                <dl class="space-y-1 text-xs font-mono">
                    @foreach($ctx['input'] as $k => $v)
                    <div class="flex gap-2">
                        <dt class="text-indigo-500 shrink-0">{{ $k }}:</dt>
                        <dd class="text-slate-700 break-all">{{ is_scalar($v) ? $v : json_encode($v) }}</dd>
                    </div>
                    @endforeach
                </dl>
                @else
                <p class="text-xs text-slate-400">—</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- 전체 context (raw JSON, fallback) --}}
    @if(!empty($ctx))
    <details class="bg-white border border-slate-200 rounded-xl mb-4">
        <summary class="px-5 py-3 cursor-pointer text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('app.user_syserr_raw_context') }}</summary>
        <pre class="px-5 pb-5 text-xs text-slate-700 font-mono whitespace-pre-wrap break-all overflow-x-auto">{{ json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
    </details>
    @endif

    {{-- Stack Trace --}}
    @if($error->trace)
    <div class="bg-slate-900 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stack Trace</h3>
            <button type="button" onclick="copyTrace(this)" class="text-xs text-slate-400 hover:text-slate-200 transition px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">
                {{ __('app.user_syserr_copy') }}
            </button>
        </div>
        <pre id="trace-content" class="text-xs text-slate-300 font-mono whitespace-pre-wrap break-all leading-relaxed" style="max-height:24rem;overflow-y:auto;">{{ $error->trace }}</pre>
    </div>
    @endif

</div>

<script>
function copyTrace(btn) {
    const text = document.getElementById('trace-content').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.textContent;
        btn.textContent = '{{ __("app.user_syserr_copied") }}';
        setTimeout(() => btn.textContent = original, 2000);
    });
}
</script>
@endsection
