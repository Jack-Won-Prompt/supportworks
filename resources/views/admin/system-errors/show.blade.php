@extends('layouts.admin')

@section('title', __('admin.error_detail'))

@section('content')
<div class="p-6 max-w-5xl mx-auto">

    {{-- 뒤로 --}}
    <div class="mb-5">
        <a href="{{ route('admin.system-errors.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('admin.syserr_back_to_list') }}
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- 헤더 카드 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    @if($error->level === 'critical')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">CRITICAL</span>
                    @elseif($error->level === 'warning')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">WARNING</span>
                    @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">ERROR</span>
                    @endif
                    @if($error->is_resolved)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">{{ __('admin.syserr_resolved_badge') }}</span>
                    @endif
                    <span class="text-xs text-slate-400">{{ $error->created_at->format('Y-m-d H:i:s') }}</span>
                </div>
                <p class="text-base font-semibold text-slate-800 leading-snug">{{ $error->message }}</p>
                @if($error->exception)
                <p class="mt-1 text-sm font-mono text-indigo-500">{{ $error->exception }}</p>
                @endif
            </div>
            <div class="flex gap-2 shrink-0">
                @if(!$error->is_resolved)
                <form method="POST" action="{{ route('admin.system-errors.resolve', $error) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('admin.syserr_mark_resolved') }}
                    </button>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.system-errors.destroy', $error) }}"
                      onsubmit="return confirm('{{ __('admin.syserr_delete_confirm') }}')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('admin.syserr_delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 상세 정보 그리드 --}}
    @php
        $ctx = $error->context ?? [];
        $hasUserInfo = !empty($ctx['user_name']) || !empty($ctx['user_email']) || !empty($ctx['user_role']) || !empty($ctx['user_company']) || !empty($ctx['admin_user_id']) || isset($ctx['user_id']);
        $roleLabels = [
            'admin'  => __('admin.syserr_role_admin'),
            'member' => __('admin.syserr_role_member'),
            'guest'  => __('admin.syserr_role_guest'),
        ];
    @endphp
    <div class="grid grid-cols-2 gap-4 mb-4">
        {{-- 발생 위치 --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_col_occurrence') }}</h3>
            <dl class="space-y-2 text-sm">
                @if($error->file)
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_file_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all text-xs">{{ $error->file }}@if($error->line):{{ $error->line }}@endif</dd>
                </div>
                @endif
                @if(!empty($ctx['route_name']))
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_route_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all text-xs">{{ $ctx['route_name'] }}</dd>
                </div>
                @endif
                @if(!empty($ctx['route_action']))
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_action_label') }}</dt>
                    <dd class="font-mono text-slate-700 break-all text-xs">{{ $ctx['route_action'] }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- 요청 컨텍스트 --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_col_request_context') }}</h3>
            @if(!empty($ctx))
            <dl class="space-y-2 text-sm">
                @if(!empty($ctx['url']))
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">URL</dt>
                    <dd class="text-slate-700 break-all text-xs">
                        <span class="font-mono font-semibold text-indigo-500">{{ $ctx['method'] ?? '' }}</span>
                        {{ $ctx['url'] }}
                    </dd>
                </div>
                @endif
                @if(!empty($ctx['ip']))
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">IP</dt>
                    <dd class="font-mono text-slate-700 text-xs">{{ $ctx['ip'] }}</dd>
                </div>
                @endif
                @if(!empty($ctx['source']))
                <div>
                    <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_source_label') }}</dt>
                    <dd class="text-slate-700 text-xs">{{ $ctx['source'] }}</dd>
                </div>
                @endif
            </dl>
            @else
            <p class="text-xs text-slate-400">{{ __('admin.syserr_context_no_info') }}</p>
            @endif
        </div>
    </div>

    {{-- 사용자 정보 --}}
    @if($hasUserInfo)
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_user_info') }}</h3>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
            @if(!empty($ctx['user_name']) || isset($ctx['user_id']))
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_user_label') }}</dt>
                <dd class="text-slate-700 text-xs">
                    @if(!empty($ctx['user_name']))
                        {{ $ctx['user_name'] }}
                        @if(isset($ctx['user_id']))<span class="text-slate-400 font-mono ml-1">#{{ $ctx['user_id'] }}</span>@endif
                    @else
                        @if($ctx['user_id'])<span class="font-mono">#{{ $ctx['user_id'] }}</span>@else{{ __('admin.syserr_not_logged_in') }}@endif
                    @endif
                </dd>
            </div>
            @endif
            @if(!empty($ctx['user_email']))
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_user_email_label') }}</dt>
                <dd class="text-slate-700 text-xs break-all">{{ $ctx['user_email'] }}</dd>
            </div>
            @endif
            @if(!empty($ctx['user_role']))
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_user_role_label') }}</dt>
                <dd class="text-slate-700 text-xs">{{ $roleLabels[$ctx['user_role']] ?? $ctx['user_role'] }}</dd>
            </div>
            @endif
            @if(!empty($ctx['user_company']))
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_user_company_label') }}</dt>
                <dd class="text-slate-700 text-xs">{{ $ctx['user_company'] }}</dd>
            </div>
            @endif
            @if(!empty($ctx['admin_user_id']))
            <div class="col-span-2">
                <dt class="text-xs text-slate-400 mb-0.5">{{ __('admin.syserr_admin_user_label') }}</dt>
                <dd class="text-slate-700 text-xs">
                    {{ $ctx['admin_user_name'] ?? '' }}
                    <span class="text-slate-400 font-mono ml-1">#{{ $ctx['admin_user_id'] }}</span>
                </dd>
            </div>
            @endif
        </dl>
    </div>
    @endif

    {{-- 요청 데이터 --}}
    @php
        $hasReqData = !empty($ctx['query']) || !empty($ctx['input']);
    @endphp
    @if($hasReqData)
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ __('admin.syserr_request_data') }}</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-slate-400 mb-2">{{ __('admin.syserr_query_label') }}</p>
                @if(!empty($ctx['query']))
                <dl class="space-y-1 text-xs font-mono">
                    @foreach($ctx['query'] as $k => $v)
                    <div class="flex gap-2">
                        <dt class="text-indigo-500 shrink-0">{{ $k }}:</dt>
                        <dd class="text-slate-700 break-all">{{ $v }}</dd>
                    </div>
                    @endforeach
                </dl>
                @else
                <p class="text-xs text-slate-400">—</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-2">{{ __('admin.syserr_input_label') }}</p>
                @if(!empty($ctx['input']))
                <dl class="space-y-1 text-xs font-mono">
                    @foreach($ctx['input'] as $k => $v)
                    <div class="flex gap-2">
                        <dt class="text-indigo-500 shrink-0">{{ $k }}:</dt>
                        <dd class="text-slate-700 break-all">{{ $v }}</dd>
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

    {{-- 스택 트레이스 --}}
    @if($error->trace)
    <div class="bg-slate-900 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stack Trace</h3>
            <button onclick="copyTrace()" class="text-xs text-slate-400 hover:text-slate-200 transition px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">{{ __('admin.syserr_copy') }}</button>
        </div>
        <pre id="trace-content" class="text-xs text-slate-300 font-mono whitespace-pre-wrap break-all leading-relaxed overflow-auto max-h-[32rem]">{{ $error->trace }}</pre>
    </div>
    @endif

</div>

<script>
const ADMIN_C_STR = {
    copy:   '{{ __("admin.syserr_copy") }}',
    copied: '{{ __("admin.syserr_copied") }}',
};

async function copyTrace() {
    const text = document.getElementById('trace-content').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        btn.textContent = ADMIN_C_STR.copied;
        setTimeout(() => btn.textContent = ADMIN_C_STR.copy, 2000);
    });
}
</script>
@endsection
