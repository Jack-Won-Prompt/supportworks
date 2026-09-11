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

@push('styles')
<style>
/*
 * 담당자 답변의 마크다운 서식.
 *
 * Tailwind preflight 가 p·ul·h* 의 여백을 모두 지우기 때문에, 마크다운을
 * 렌더해도 문단 구분이 없는 한 덩어리로 보인다. typography 플러그인을
 * 들이는 대신 이 화면에 필요한 만큼만 되살린다.
 */
.aiw-md { font-size: 13.5px; line-height: 1.7; color: #374151; word-break: break-word; }
.aiw-md > :first-child { margin-top: 0; }
.aiw-md > :last-child { margin-bottom: 0; }
.aiw-md p { margin: 0 0 0.7em; }
.aiw-md strong { font-weight: 700; color: #111827; }
.aiw-md em { font-style: italic; }
.aiw-md ul, .aiw-md ol { margin: 0 0 0.7em; padding-left: 1.25em; }
.aiw-md ul { list-style: disc; }
.aiw-md ol { list-style: decimal; }
.aiw-md li { margin: 0.2em 0; }
.aiw-md li > ul, .aiw-md li > ol { margin: 0.2em 0; }
.aiw-md h1, .aiw-md h2, .aiw-md h3, .aiw-md h4 {
    margin: 1.1em 0 0.5em; font-weight: 700; color: #111827; line-height: 1.35;
}
.aiw-md h1 { font-size: 1.25em; }
.aiw-md h2 { font-size: 1.15em; }
.aiw-md h3 { font-size: 1.05em; }
.aiw-md h4 { font-size: 1em; }
.aiw-md code {
    background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px;
    padding: 0.1em 0.35em; font-size: 0.88em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.aiw-md pre {
    background: #1f2937; color: #f9fafb; border-radius: 8px;
    padding: 0.75em 0.9em; margin: 0 0 0.7em; overflow-x: auto; font-size: 0.85em;
}
.aiw-md pre code { background: none; border: 0; padding: 0; color: inherit; }
.aiw-md a { color: #4f46e5; text-decoration: underline; }
.aiw-md blockquote {
    margin: 0 0 0.7em; padding: 0.1em 0 0.1em 0.8em;
    border-left: 3px solid #e5e7eb; color: #6b7280;
}
.aiw-md hr { margin: 1em 0; border: 0; border-top: 1px solid #e5e7eb; }
.aiw-md table { width: 100%; margin: 0 0 0.7em; border-collapse: collapse; font-size: 0.92em; }
.aiw-md th, .aiw-md td { border: 1px solid #e5e7eb; padding: 0.35em 0.6em; text-align: left; }
.aiw-md th { background: #f9fafb; font-weight: 600; }
</style>
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

    @if (session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-800">
            {{ session('error') }}
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

    @if ($blockingJob)
        <div x-show="status === 'dispatched'" x-cloak
             class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-900">
            같은 담당자의 작업
            <a href="{{ route('projects.ai-works.show', [$project, $blockingJob->id]) }}"
               class="font-medium underline">#{{ $blockingJob->id }} {{ $blockingJob->title }}</a>
            이(가) 실행 중이라 대기하고 있습니다. 같은 작업 폴더에서 둘이 동시에 돌면
            브랜치와 변경이 뒤섞이므로 끝나면 <span class="font-medium">자동으로 시작</span>합니다.
        </div>
    @endif

    {{-- ── 결과 반영(커밋·푸시) ──────────────────────────────────────
         담당자는 push 를 할 수 없다. 사람이 결과를 확인하고 누른 이 버튼만이
         커밋·머지·푸시를 시킨다. --}}
    @if ($canEdit && $job->branchName())
        @php
            $lastPublish = $publishes->first();
            $published   = $publishes->firstWhere('status', 'succeeded');
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-gray-900">결과 반영</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        작업 브랜치 <code>{{ $job->branchName() }}</code> 를 커밋하고 기본 브랜치에 합쳐 원격에 올립니다.
                        <span class="text-gray-400">결과를 확인한 뒤 눌러 주세요.</span>
                    </p>
                </div>

                @if ($published)
                    <span class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                        반영 완료 · {{ $published->created_at?->format('m-d H:i') }}
                    </span>
                @elseif ($lastPublish && $lastPublish->isRunning())
                    <span class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800">
                        {{ $lastPublish->statusLabel() }}…
                    </span>
                @elseif ($job->status->isTerminal())
                    <form method="POST" action="{{ route('projects.ai-works.publish', [$project, $job]) }}"
                          class="flex items-end gap-2 flex-wrap"
                          onsubmit="return confirm('원격 저장소에 올립니다. 되돌리려면 git 으로 직접 작업해야 합니다. 진행할까요?')">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">커밋 메시지 (비우면 제목)</label>
                            <input type="text" name="commit_message" maxlength="480"
                                   value="{{ old('commit_message') }}"
                                   placeholder="{{ $job->title }} (작업 지시 #{{ $job->id }})"
                                   class="w-80 max-w-full rounded-lg border-gray-200 text-xs">
                        </div>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            커밋 &amp; 푸시
                        </button>
                    </form>
                @else
                    <span class="text-xs text-gray-400">작업이 끝나면 반영할 수 있습니다.</span>
                @endif
            </div>

            @foreach ($publishes as $publish)
                <div class="mt-3 rounded-lg border px-3 py-2 text-xs
                            {{ $publish->status === 'succeeded' ? 'border-emerald-200 bg-emerald-50'
                               : ($publish->status === 'failed' ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50') }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $publish->statusLabel() }}</span>
                        <span class="text-gray-500">{{ $publish->source_branch }} → {{ $publish->target_branch }}</span>
                        <span class="text-gray-400">{{ $publish->requester?->name }}</span>
                        <span class="text-gray-400">{{ $publish->created_at?->format('m-d H:i') }}</span>
                        @if ($publish->commit_sha)
                            <code class="text-gray-500">{{ \Illuminate\Support\Str::limit($publish->commit_sha, 10, '') }}</code>
                        @endif
                    </div>
                    @if ($publish->output)
                        <details class="mt-1">
                            <summary class="cursor-pointer text-gray-500">실행 내용 보기</summary>
                            <pre class="mt-1 overflow-x-auto whitespace-pre-wrap rounded bg-white p-2 text-[11px] text-gray-700">{{ $publish->output }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── 배포 ──────────────────────────────────────────────────────
         원격에 올린 뒤에만 의미가 있다. 아직 push 하지 않았다면 서버가 받아 갈
         커밋이 없다. --}}
    @if ($canEdit && $deployTargets->isNotEmpty() && $publishes->firstWhere('status', 'succeeded'))
        @php $runningDeploy = $deploys->first(fn ($d) => $d->isRunning()); @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-bold text-gray-900">배포</h3>
            <p class="mt-1 text-xs text-gray-500">
                원격에 올린 변경을 운영 서버가 받아 갑니다.
                <span class="text-amber-700">배포 스크립트는 대개 <code>migrate</code> 를 포함해 DB 스키마까지 바꿉니다.</span>
            </p>

            @if ($runningDeploy)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    <span class="font-semibold">{{ $runningDeploy->statusLabel() }}</span> —
                    {{ $runningDeploy->target?->name }} · {{ $runningDeploy->requester?->name }}
                    <span class="text-amber-700">새로고침하면 진행 상황이 갱신됩니다.</span>
                </div>
            @else
                {{-- 확인 문구는 고른 대상의 이름이다. 대상이 여러 개면 어느 이름인지
                     헷갈리므로, 고른 것에 따라 안내와 placeholder 가 함께 바뀐다. --}}
                <form method="POST" action="{{ route('projects.ai-works.deploy', [$project, $job]) }}"
                      class="mt-3 flex flex-wrap items-end gap-2"
                      x-data="{
                          targets: @js($deployTargets->pluck('name', 'id')),
                          id: @js((string) $deployTargets->first()->id),
                          get name() { return this.targets[this.id] ?? ''; },
                          get typed() { return this.$refs.confirm?.value ?? ''; },
                      }">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">배포 대상</label>
                        <select name="target_id" x-model="id" required class="rounded-lg border-gray-200 text-xs">
                            @foreach ($deployTargets as $target)
                                <option value="{{ $target->id }}">{{ $target->name }} — {{ $target->command }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                            확인을 위해 <span class="font-mono font-bold text-amber-700" x-text="name"></span> 을(를) 그대로 입력하세요
                        </label>
                        <input type="text" name="confirmation" x-ref="confirm" required maxlength="100" autocomplete="off"
                               :placeholder="name"
                               class="w-56 rounded-lg border-gray-200 text-xs">
                    </div>
                    <button class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700">
                        배포 실행
                    </button>
                </form>
            @endif

            @foreach ($deploys as $deploy)
                <details class="mt-2 rounded-lg border px-3 py-2 text-xs
                         {{ $deploy->status === 'succeeded' ? 'border-emerald-200 bg-emerald-50'
                            : ($deploy->status === 'failed' ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50') }}">
                    <summary class="cursor-pointer flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $deploy->statusLabel() }}</span>
                        <span class="text-gray-600">{{ $deploy->target?->name }}</span>
                        <span class="text-gray-400">{{ $deploy->requester?->name }}</span>
                        <span class="text-gray-400">{{ $deploy->created_at?->format('m-d H:i') }}</span>
                        @if ($deploy->duration())<span class="text-gray-400">{{ $deploy->duration() }}</span>@endif
                        @if ($deploy->exit_code !== null)<span class="text-gray-400">exit {{ $deploy->exit_code }}</span>@endif
                    </summary>
                    @if ($deploy->output)
                        <pre class="mt-1 max-h-80 overflow-auto whitespace-pre-wrap rounded bg-gray-900 p-2 text-[11px] text-gray-100">{{ $deploy->output }}</pre>
                    @endif
                </details>
            @endforeach
        </div>
    @endif

    {{-- ── 본문: 대화 + 활동 로그 ───────────────────────────────────── --}}
    <div class="grid gap-2 lg:grid-cols-3">

        {{-- 대화 패널 --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col" style="min-height:420px;">
            <h3 class="text-sm font-bold text-gray-900 mb-2">대화</h3>

            <div class="flex-1 space-y-2 overflow-y-auto" style="max-height:60vh;" x-ref="messages">
                @php
                    // 선택지 버튼은 마지막 질문에만 띄운다(지난 질문의 버튼은 이미 답한 것이다).
                    $latestChoiceId = $messages->last(fn ($m) => ! empty($m->choices))?->id;
                @endphp
                @foreach ($messages as $message)
                    @include('aiw.jobs.partials.message', ['message' => $message, 'project' => $project, 'job' => $job, 'latestChoiceId' => $latestChoiceId, 'canEdit' => $canEdit])
                @endforeach

                {{-- 실시간으로 도착한 메시지. 새로고침 후 모습과 같아야 한다. --}}
                <template x-for="m in liveMessages" :key="m.id">
                    <div class="flex gap-2" :class="m.role === 'user' ? 'flex-row-reverse' : ''">
                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                             :class="m.role === 'user' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                             x-text="roleLabel(m.role).slice(0, 1)"></div>

                        <div class="flex min-w-0 max-w-[85%] flex-col" :class="m.role === 'user' ? 'items-end' : ''">
                            <div class="mb-0.5 flex items-center gap-2 text-[11px] text-gray-500"
                                 :class="m.role === 'user' ? 'flex-row-reverse' : ''">
                                <span class="font-medium text-gray-700" x-text="roleLabel(m.role)"></span>
                            </div>

                            <div class="rounded-2xl px-3.5 py-2.5 text-left"
                                 :class="m.role === 'user'
                                     ? 'rounded-tr-sm bg-indigo-500 text-white'
                                     : (m.role === 'handover'
                                         ? 'rounded-tl-sm border border-violet-200 bg-violet-50'
                                         : 'rounded-tl-sm border border-gray-200 bg-white')">
                                <div class="aiw-md" x-html="render(m.content)"></div>
                            </div>

                            @if ($job->mode === 'interactive' && $canEdit)
                                {{-- 실시간으로 도착한 질문의 선택지. 마지막 것만 띄운다. --}}
                                <div x-show="m.choices && m.choices.length && m.id === latestLiveChoiceId && ! isTerminal" x-cloak
                                     class="mt-1.5 flex flex-wrap gap-1.5">
                                    <template x-for="c in (m.choices || [])" :key="c">
                                        <form method="POST" action="{{ route('projects.ai-works.message', [$project, $job]) }}">
                                            @csrf
                                            <input type="hidden" name="content" :value="c">
                                            <button class="rounded-full border border-indigo-300 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                                                    x-text="c"></button>
                                        </form>
                                    </template>
                                </div>
                            @endif
                        </div>
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
                    <form method="POST" action="{{ route('projects.ai-works.message', [$project, $job]) }}"
                          enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <div class="flex gap-2">
                            <textarea name="content" rows="2" required maxlength="20000"
                                      class="flex-1 rounded-lg border-gray-200 text-sm"
                                      :class="status === 'waiting_input' ? 'ring-2 ring-amber-300' : ''"
                                      placeholder="담당자에게 보낼 메시지">{{ old('content') }}</textarea>
                            <button class="self-end rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">전송</button>
                        </div>
                        <input type="file" name="images[]" accept="image/png,image/jpeg,image/webp,image/gif" multiple
                               class="block w-full text-xs text-gray-500 file:mr-3 file:rounded file:border-0
                                      file:bg-gray-100 file:px-2 file:py-1 file:text-[11px] file:text-gray-700">
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
                    @if ($code = $job->failureCode())
                        <div class="font-semibold mb-0.5">{{ $code->label() }}</div>
                    @endif
                    {{ $job->error_message }}
                </div>

                {{-- 복구 안내: 사유를 코드로 받았을 때만 "무엇을 할 수 있는지" 제시할 수 있다.
                     버튼은 바로 실행하지 않고 프리필된 등록 폼으로 보낸다 — 사용자가
                     내용을 보고 확인한 뒤 실행하게 한다. --}}
                @if ($code)
                    @php $detail = $job->error_detail ?? []; @endphp
                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                        <div class="text-xs font-semibold text-amber-900 mb-1.5">이렇게 해결할 수 있습니다</div>

                        @if ($code->retryableWithoutBranch() && ! empty($detail['files']))
                            <div class="mb-2 text-xs text-amber-900">
                                정리되지 않은 항목 {{ $detail['count'] ?? count($detail['files']) }}건:
                                <ul class="mt-0.5 ml-3 list-disc space-y-0.5">
                                    @foreach (array_slice($detail['files'], 0, 10) as $file)
                                        <li><code>{{ $file }}</code></li>
                                    @endforeach
                                </ul>
                                @if (count($detail['files']) > 10)
                                    <div class="ml-3 text-amber-700">외 {{ count($detail['files']) - 10 }}건</div>
                                @endif
                            </div>
                        @endif

                        @if ($code === \App\Enums\AiWork\AiwFailureCode::MissingBranch && ! empty($detail['available']))
                            <div class="mb-2 text-xs text-amber-900">
                                이 저장소에 있는 브랜치:
                                @foreach ($detail['available'] as $branch)
                                    <code class="mr-1">{{ $branch }}</code>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            @if ($code->retryableWithoutBranch())
                                <a href="{{ route('projects.ai-works.create', [$project, 'parent' => $job->id, 'use_branch' => 0]) }}"
                                   class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                    브랜치 없이 다시 지시
                                </a>
                            @endif

                            @if ($code->retryableAsIs())
                                <a href="{{ route('projects.ai-works.create', [$project, 'parent' => $job->id]) }}"
                                   class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                    같은 설정으로 다시 지시
                                </a>
                            @endif

                            @if ($code->needsMappingFix())
                                @if ($canManageAgents)
                                    <a href="{{ route('settings.aiw-agents.index') }}"
                                       class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                        담당자 매핑 수정
                                    </a>
                                @else
                                    <span class="text-xs text-amber-800">
                                        관리자에게 <span class="font-medium">관리자 › 담당자</span> 매핑 수정을 요청하세요.
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            @if ($job->result_summary)
                <div class="aiw-md mb-3" x-html="render(@js($job->result_summary))"></div>
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
        markdownReady: typeof window.aiwRenderMarkdown === 'function',
        liveLogs: [],
        livePermissions: [],
        autoScroll: true,

        init() {
            // 렌더러가 아직 안 올라왔으면 올라온 뒤 다시 그린다.
            if (! this.markdownReady) {
                window.addEventListener(
                    'aiw:markdown-ready',
                    () => { this.markdownReady = true; },
                    { once: true },
                );
            }

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
        /** 실시간 메시지 중 선택지를 가진 마지막 것. 지난 질문의 버튼은 숨긴다. */
        get latestLiveChoiceId() {
            const withChoices = this.liveMessages.filter((m) => m.choices && m.choices.length);
            return withChoices.length ? withChoices[withChoices.length - 1].id : null;
        },

        get costPct() { return this.costLimit > 0 ? Math.min(100, (this.costUsd / this.costLimit) * 100) : 0; },
        get costTone() { return this.costPct >= 80 ? 'bg-red-500' : 'bg-emerald-500'; },

        /**
         * 마크다운 렌더.
         *
         * Vite 가 내보내는 모듈은 defer 라 Alpine 이 먼저 뜰 수 있다(주석에 at-vite 를
         * 쓰면 Blade 가 디렉티브로 컴파일한다). 그때 원문이 그대로 남아 ** 같은 기호가
         * 보인다. markdownReady 를 참조해 두면 모듈이
         * 올라온 뒤 x-html 이 자동으로 다시 그려진다.
         *
         * 폴백은 반드시 이스케이프한다 — 렌더러가 없다고 원문을 innerHTML 에
         * 그대로 넣으면 담당자가 만든 텍스트가 스크립트가 된다.
         */
        render(text) {
            if (this.markdownReady && window.aiwRenderMarkdown) {
                return window.aiwRenderMarkdown(text);
            }

            const div = document.createElement('div');
            div.textContent = String(text ?? '');

            return div.innerHTML;
        },
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
