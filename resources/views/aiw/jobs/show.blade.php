@extends('layouts.app')

@section('title', $job->title.' — 작업 지시')

@section('header-actions')
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.ai-works.index', $project) }}" class="hover:text-indigo-500 transition-colors">작업 지시</a>
<span>›</span>
<span style="color:var(--color-text-secondary);font-weight:500;">#{{ $job->id }}</span>
@endsection

@push('scripts')
    @vite(['resources/js/aiw-echo.js', 'resources/js/aiw-markdown.js'])
@endpush

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'ai-works'])

<div class="space-y-3" x-data="aiwJob({
        jobId: {{ $job->id }},
        status: @js($job->status->value),
        contextTokens: {{ (int) $job->context_tokens }},
        contextLimit: {{ (int) $job->context_limit_tokens }},
        costUsd: {{ (float) $job->cost_usd }},
        costLimit: {{ (float) $job->cost_limit_usd }},
        handoverCount: {{ (int) $job->handover_count }},
     })">

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- ── 헤더 ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl font-bold text-gray-900">{{ $job->title }}</h2>
                    <span class="inline-block rounded px-1.5 py-0.5 text-[11px] font-medium"
                          :class="statusTone" x-text="statusLabel"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3">
                    <span>{{ $job->mode === 'interactive' ? '대화형' : '단발' }}</span>
                    <span>{{ $job->agent?->name ?? '담당자 없음' }}</span>
                    @if ($job->branchName())<span><code>{{ $job->branchName() }}</code></span>@endif
                    <span>{{ $job->model ?: '기본 모델' }}</span>
                    @if ($job->parent)
                        <a href="{{ route('projects.ai-works.show', [$project, $job->parent_job_id]) }}"
                           class="text-indigo-600 hover:text-indigo-700">원 작업 #{{ $job->parent_job_id }}</a>
                    @endif
                </p>
            </div>

            {{-- 액션 --}}
            @if ($canEdit)
                <div class="flex flex-wrap gap-2">
                    <form method="POST" x-show="isActive || status === 'dispatched'" x-cloak
                          action="{{ route('projects.ai-works.action', [$project, $job, 'cancel']) }}"
                          onsubmit="return confirm('작업을 취소하시겠습니까?')">
                        @csrf
                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">취소</button>
                    </form>

                    <form method="POST" x-show="status === 'dispatched'" x-cloak
                          action="{{ route('projects.ai-works.action', [$project, $job, 'redispatch']) }}">
                        @csrf
                        <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">재전송</button>
                    </form>

                    @if ($job->mode === 'interactive')
                        <form method="POST" x-show="['running','waiting_input'].includes(status)" x-cloak
                              action="{{ route('projects.ai-works.action', [$project, $job, 'handover']) }}"
                              onsubmit="return confirm('현재 세션을 인수인계 문서로 정리하고 새 세션으로 교체합니다. 진행할까요?')">
                            @csrf
                            <button class="rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-medium text-violet-700 hover:bg-violet-50">컨텍스트 정리</button>
                        </form>

                        <form method="POST" x-show="isActive" x-cloak
                              action="{{ route('projects.ai-works.action', [$project, $job, 'end']) }}"
                              onsubmit="return confirm('세션을 종료합니다. 마지막 턴이 끝나면 완료 처리됩니다.')">
                            @csrf
                            <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">세션 종료</button>
                        </form>
                    @endif

                    <a x-show="isTerminal" x-cloak
                       href="{{ route('projects.ai-works.create', [$project, 'parent' => $job->id]) }}"
                       class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">후속 지시</a>
                </div>
            @endif
        </div>

        {{-- 게이지 --}}
        <div class="grid gap-4 md:grid-cols-2 mt-4">
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-gray-700">컨텍스트</span>
                    <span class="text-gray-500">
                        <span x-text="contextTokens.toLocaleString()"></span> /
                        <span x-text="contextLimit.toLocaleString()"></span>
                        <span class="ml-1 text-gray-400">세션 교체 <span x-text="handoverCount"></span>회</span>
                    </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="contextTone" :style="`width:${contextPct}%`"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-gray-700" title="{{ $job->agent?->costHint() }}">
                        {{ $job->agent?->costLabel() ?? '비용' }}
                    </span>
                    <span class="text-gray-500">
                        $<span x-text="costUsd.toFixed(4)"></span> / $<span x-text="costLimit.toFixed(2)"></span>
                    </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="costTone" :style="`width:${costPct}%`"></div>
                </div>
                <p x-show="costPct >= 80" x-cloak class="mt-1 text-xs text-red-600">상한에 근접했습니다.</p>
                @if ($job->agent && ! $job->agent->usesApiKey())
                    <p class="mt-1 text-[11px] text-gray-400">
                        구독 로그인으로 실행되어 실제 청구액이 아닙니다. 폭주를 막는 상한으로만 쓰입니다.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- ── 본문: 대화 + 활동 로그 ───────────────────────────────────── --}}
    <div class="grid gap-2 lg:grid-cols-3">

        {{-- 대화 패널 --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col" style="min-height:420px;">
            <h3 class="text-sm font-bold text-gray-900 mb-2">대화</h3>

            <div class="flex-1 space-y-2 overflow-y-auto" style="max-height:60vh;" x-ref="messages">
                @foreach ($messages as $message)
                    @include('aiw.jobs.partials.message', ['message' => $message, 'project' => $project, 'job' => $job])
                @endforeach

                {{-- 실시간으로 도착한 메시지 --}}
                <template x-for="m in liveMessages" :key="m.id">
                    <div class="rounded-lg px-3 py-2 text-sm"
                         :class="m.role === 'user' ? 'bg-indigo-50 ml-8' : (m.role === 'handover' ? 'bg-violet-50 border border-violet-200' : 'bg-gray-50 mr-8')">
                        <div class="text-[11px] text-gray-500 mb-1" x-text="roleLabel(m.role)"></div>
                        <div class="prose prose-sm max-w-none" x-html="render(m.content)"></div>
                    </div>
                </template>

                {{-- 승인 요청 카드 (pending 은 새로고침해도 유지된다) --}}
                @foreach ($pending as $request)
                    <div class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2">
                        <div class="text-xs font-semibold text-orange-800 mb-1">
                            승인 요청 — {{ $request->tool_name }}
                        </div>
                        <pre class="text-[11px] bg-white rounded p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($request->tool_input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('projects.ai-works.decide', [$project, $job, $request]) }}"
                                  class="mt-2 flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="text" name="deny_reason" maxlength="191" placeholder="거부 사유 (선택)"
                                       class="flex-1 min-w-[160px] rounded border-gray-200 text-xs py-1">
                                <button name="decision" value="allow"
                                        class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">허용</button>
                                <button name="decision" value="deny"
                                        class="rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">거부</button>
                            </form>
                        @endif
                    </div>
                @endforeach

                <template x-for="p in livePermissions" :key="p.request_key">
                    <div class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2">
                        <div class="text-xs font-semibold text-orange-800 mb-1">
                            승인 요청 — <span x-text="p.tool_name"></span>
                        </div>
                        <pre class="text-[11px] bg-white rounded p-2 overflow-x-auto whitespace-pre-wrap"
                             x-text="JSON.stringify(p.tool_input, null, 2)"></pre>
                        <p class="mt-1 text-[11px] text-orange-700">새로고침하면 허용/거부 버튼이 나타납니다.</p>
                    </div>
                </template>

                {{-- 결정된 요청 --}}
                @foreach ($decided as $request)
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-[11px] text-gray-500">
                        {{ $request->tool_name }} —
                        <span class="font-medium {{ $request->status === 'allowed' ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ ['allowed' => '허용됨', 'denied' => '거부됨', 'expired' => '시간 초과'][$request->status] ?? $request->status }}
                        </span>
                        @if ($request->decider) · {{ $request->decider->name }} @endif
                        @if ($request->deny_reason) · {{ $request->deny_reason }} @endif
                    </div>
                @endforeach
            </div>

            {{-- 입력창 --}}
            @if ($job->mode === 'interactive' && $canEdit)
                <div x-show="! isTerminal" x-cloak class="mt-3 border-t border-gray-100 pt-3">
                    <p x-show="status === 'waiting_input'" x-cloak class="mb-1 text-xs font-medium text-amber-700">
                        담당자가 답변을 기다리고 있습니다.
                    </p>
                    <p x-show="status === 'handover'" x-cloak class="mb-1 text-xs text-violet-700">
                        컨텍스트 정리 중 — 보낸 메시지는 세션 교체 후 전달됩니다.
                    </p>
                    <form method="POST" action="{{ route('projects.ai-works.message', [$project, $job]) }}" class="flex gap-2">
                        @csrf
                        <textarea name="content" rows="2" required maxlength="20000"
                                  class="flex-1 rounded-lg border-gray-200 text-sm"
                                  :class="status === 'waiting_input' ? 'ring-2 ring-amber-300' : ''"
                                  placeholder="담당자에게 보낼 메시지"></textarea>
                        <button class="self-end rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">전송</button>
                    </form>
                </div>
            @endif
        </div>

        {{-- 활동 로그 패널 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col" style="min-height:420px;">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-gray-900">활동 로그</h3>
                <button x-show="! autoScroll" x-cloak @click="jumpToBottom()"
                        class="rounded bg-indigo-600 px-2 py-0.5 text-[11px] font-medium text-white">새 로그</button>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto text-xs" style="max-height:60vh;"
                 x-ref="logs" @scroll="onLogScroll()">
                @foreach ($logs as $log)
                    <div class="rounded px-2 py-1 {{ ['error' => 'bg-red-50 text-red-700', 'daemon' => 'bg-slate-100 text-slate-700', 'handover' => 'bg-violet-50 text-violet-700'][$log->type] ?? 'text-gray-600' }}">
                        <span class="text-gray-400">#{{ $log->seq }}</span>
                        <span class="text-[10px] text-gray-400">{{ $log->type }}</span>
                        <div class="truncate" title="{{ $log->content }}">{{ $log->content }}</div>
                    </div>
                @endforeach

                <template x-for="l in liveLogs" :key="l.id">
                    <div class="rounded px-2 py-1" :class="logTone(l.type)">
                        <span class="text-gray-400" x-text="'#' + l.seq"></span>
                        <span class="text-[10px] text-gray-400" x-text="l.type"></span>
                        <div class="truncate" :title="l.content" x-text="l.content"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── 결과 ─────────────────────────────────────────────────────── --}}
    @if ($job->status->isTerminal())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900 mb-2">결과</h3>

            @if ($job->error_message)
                <div class="mb-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
                    {{ $job->error_message }}
                </div>
            @endif

            @if ($job->result_summary)
                <div class="prose prose-sm max-w-none mb-3" x-html="render(@js($job->result_summary))"></div>
            @endif

            @if ($job->changed_files)
                <div class="mb-3">
                    <div class="text-xs font-semibold text-gray-700 mb-1">변경 파일 {{ count($job->changed_files) }}개</div>
                    <ul class="text-xs text-gray-600 space-y-0.5">
                        @foreach ($job->changed_files as $file)
                            <li><code>{{ $file }}</code></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($job->git_diff)
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">diff 보기</summary>
                    <pre class="mt-2 overflow-x-auto rounded bg-gray-50 p-3 text-[11px]">{{ $job->git_diff }}</pre>
                </details>
            @elseif ($job->git_diff_path)
                <p class="text-xs text-gray-500">
                    diff 가 너무 커서 파일로 보관했습니다: <code>{{ $job->git_diff_path }}</code>
                </p>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script>
function aiwJob(initial) {
    return {
        ...initial,
        liveMessages: [],
        liveLogs: [],
        livePermissions: [],
        autoScroll: true,

        init() {
            // Reverb 미설정 환경에서는 EchoAiw 가 null 이다. 화면은 정적으로 동작한다.
            if (!window.EchoAiw) return;

            const ch = window.EchoAiw.private('aiw.job.' + this.jobId);

            ch.listen('.job.status-changed', (e) => {
                this.status = e.status;
                this.contextTokens = e.context_tokens;
                this.contextLimit = e.context_limit_tokens;
                this.costUsd = e.cost_usd;
                this.costLimit = e.cost_limit_usd;
                this.handoverCount = e.handover_count;
                // 종료되면 결과 영역이 서버 렌더라 새로고침이 필요하다.
                if (['completed', 'failed', 'cancelled'].includes(e.status)) {
                    setTimeout(() => window.location.reload(), 1200);
                }
            });

            ch.listen('.message.appended', (e) => {
                if (!this.liveMessages.some((m) => m.id === e.id)) this.liveMessages.push(e);
            });

            ch.listen('.log.appended', (e) => {
                if (!this.liveLogs.some((l) => l.id === e.id)) this.liveLogs.push(e);
                this.$nextTick(() => { if (this.autoScroll) this.jumpToBottom(); });
            });

            ch.listen('.permission.requested', (e) => {
                if (!this.livePermissions.some((p) => p.request_key === e.request_key)) {
                    this.livePermissions.push(e);
                }
            });
        },

        get isTerminal() { return ['completed', 'failed', 'cancelled'].includes(this.status); },
        get isActive() { return ['running', 'waiting_input', 'waiting_permission', 'handover'].includes(this.status); },

        get statusLabel() {
            return {
                queued: '대기', dispatched: '전달됨', running: '실행 중',
                waiting_input: '답변 대기', waiting_permission: '승인 대기',
                handover: '컨텍스트 정리 중', completed: '완료', failed: '실패', cancelled: '취소됨',
            }[this.status] ?? this.status;
        },
        get statusTone() {
            if (this.status === 'running') return 'bg-blue-50 text-blue-700';
            if (this.status === 'completed') return 'bg-emerald-50 text-emerald-700';
            if (this.status === 'failed') return 'bg-red-50 text-red-700';
            if (this.status.startsWith('waiting')) return 'bg-amber-50 text-amber-700';
            if (this.status === 'handover') return 'bg-violet-50 text-violet-700';
            return 'bg-gray-100 text-gray-600';
        },

        get contextPct() { return this.contextLimit > 0 ? Math.min(100, (this.contextTokens / this.contextLimit) * 100) : 0; },
        get contextTone() {
            if (this.contextPct >= 80) return 'bg-red-500';
            if (this.contextPct >= 60) return 'bg-orange-400';
            return 'bg-indigo-500';
        },
        get costPct() { return this.costLimit > 0 ? Math.min(100, (this.costUsd / this.costLimit) * 100) : 0; },
        get costTone() { return this.costPct >= 80 ? 'bg-red-500' : 'bg-emerald-500'; },

        render(text) { return window.aiwRenderMarkdown ? window.aiwRenderMarkdown(text) : text; },
        roleLabel(role) { return { user: '나', assistant: '담당자', handover: '인수인계' }[role] ?? role; },
        logTone(type) {
            return { error: 'bg-red-50 text-red-700', daemon: 'bg-slate-100 text-slate-700', handover: 'bg-violet-50 text-violet-700' }[type] ?? 'text-gray-600';
        },

        // 사용자가 위로 스크롤하면 자동 스크롤을 끈다(읽는 중에 끌려가지 않게).
        onLogScroll() {
            const el = this.$refs.logs;
            this.autoScroll = el.scrollHeight - el.scrollTop - el.clientHeight < 40;
        },
        jumpToBottom() {
            const el = this.$refs.logs;
            el.scrollTop = el.scrollHeight;
            this.autoScroll = true;
        },
    };
}
</script>
@endpush
@endsection
