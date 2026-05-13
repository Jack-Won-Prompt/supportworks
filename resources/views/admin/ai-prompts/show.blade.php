@extends('layouts.admin')

@section('title', __('admin.aiprompt_detail_title'))

@section('content')
<div class="p-6 max-w-4xl mx-auto">

    {{-- 뒤로 --}}
    <div class="mb-5">
        <a href="{{ route('admin.ai-prompts.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('admin.aiprompt_back_to_list') }}
        </a>
    </div>

    {{-- 세션 정보 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-base font-bold text-slate-800">{{ $session->title }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-500">
                    @if($session->user)
                    <span class="flex items-center gap-1.5">
                        <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                            {{ mb_substr($session->user->name, 0, 1) }}
                        </div>
                        <span class="font-medium text-slate-700">{{ $session->user->name }}</span>
                        <span class="text-slate-400">{{ $session->user->email }}</span>
                    </span>
                    @endif
                    <span>{{ __('admin.aiprompt_session_id', ['id' => $session->id]) }}</span>
                    <span>{{ __('admin.aiprompt_start_time', ['time' => $session->created_at->format('Y-m-d H:i:s')]) }}</span>
                    <span>{{ __('admin.aiprompt_messages_summary', ['total' => $session->messages->count(), 'user' => $session->messages->where('role','user')->count()]) }}</span>
                </div>
            </div>
            @php
                $providerList = $session->messages->whereNotNull('ai_provider')->pluck('ai_provider')->unique();
            @endphp
            <div class="flex gap-1.5 shrink-0">
                @foreach($providerList as $p)
                @php
                    $badge = match($p) {
                        'claude' => 'bg-orange-50 text-orange-600 border border-orange-200',
                        'openai' => 'bg-green-50 text-green-600 border border-green-200',
                        'manus'  => 'bg-blue-50 text-blue-600 border border-blue-200',
                        default  => 'bg-slate-50 text-slate-500 border border-slate-200',
                    };
                @endphp
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}">{{ ucfirst($p) }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 대화 목록 --}}
    <div class="space-y-4">
        @foreach($session->messages as $msg)
        @if($msg->role === 'user')
        {{-- 사용자 메시지 --}}
        <div class="flex justify-end">
            <div class="max-w-[80%]">
                <div class="flex items-center justify-end gap-2 mb-1.5">
                    <span class="text-xs text-slate-400">{{ $msg->created_at->format('H:i:s') }}</span>
                    <span class="text-xs font-medium text-indigo-600">
                        {{ $session->user?->name ?? __('admin.aiprompt_user_label') }}
                    </span>
                    <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold text-xs">
                        {{ mb_substr($session->user?->name ?? 'U', 0, 1) }}
                    </div>
                </div>
                <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap break-words shadow-sm">{{ $msg->content }}</div>
            </div>
        </div>
        @else
        {{-- 웍스 응답 --}}
        <div class="flex justify-start">
            <div class="max-w-[85%]">
                <div class="flex items-center gap-2 mb-1.5">
                    @php
                        $aiIcon = match($msg->ai_provider) {
                            'claude' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'C'],
                            'openai' => ['bg' => 'bg-green-100',  'text' => 'text-green-700',  'label' => 'G'],
                            'manus'  => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'label' => 'M'],
                            default  => ['bg' => 'bg-slate-100',  'text' => 'text-slate-600',  'label' => '웍스'],
                        };
                    @endphp
                    <div class="w-6 h-6 rounded-full {{ $aiIcon['bg'] }} flex items-center justify-center {{ $aiIcon['text'] }} font-bold text-xs">
                        {{ $aiIcon['label'] }}
                    </div>
                    <span class="text-xs font-medium text-slate-600">
                        {{ $msg->ai_provider ? ucfirst($msg->ai_provider) : '웍스' }}
                    </span>
                    <span class="text-xs text-slate-400">{{ $msg->created_at->format('H:i:s') }}</span>
                    @if($msg->doc_file_name)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $msg->doc_file_name }}
                    </span>
                    @endif
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-4 py-3 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap break-words shadow-sm">{{ $msg->content }}</div>
                @if($msg->html_output || $msg->css_output || $msg->js_output)
                <div class="mt-2 flex gap-2">
                    @if($msg->html_output)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-orange-50 text-orange-600 font-mono">HTML</span>
                    @endif
                    @if($msg->css_output)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-600 font-mono">CSS</span>
                    @endif
                    @if($msg->js_output)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 font-mono">JS</span>
                    @endif
                    <button onclick="toggleCode({{ $msg->id }})"
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs text-slate-500 border border-slate-200 hover:bg-slate-50 transition">
                        {{ __('admin.aiprompt_code_view') }}
                    </button>
                </div>
                <div id="code-{{ $msg->id }}" class="hidden mt-2 space-y-2">
                    @if($msg->html_output)
                    <pre class="bg-slate-900 text-slate-100 rounded-lg p-3 text-xs overflow-auto max-h-48 font-mono leading-relaxed">{{ $msg->html_output }}</pre>
                    @endif
                    @if($msg->css_output)
                    <pre class="bg-slate-900 text-slate-100 rounded-lg p-3 text-xs overflow-auto max-h-48 font-mono leading-relaxed">{{ $msg->css_output }}</pre>
                    @endif
                    @if($msg->js_output)
                    <pre class="bg-slate-900 text-slate-100 rounded-lg p-3 text-xs overflow-auto max-h-48 font-mono leading-relaxed">{{ $msg->js_output }}</pre>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif
        @endforeach

        @if($session->messages->isEmpty())
        <div class="text-center py-12 text-slate-400">
            <p class="text-sm">{{ __('admin.aiprompt_no_messages') }}</p>
        </div>
        @endif
    </div>

</div>

<script>
function toggleCode(id) {
    const el = document.getElementById('code-' + id);
    if (el) el.classList.toggle('hidden');
}
</script>
@endsection
