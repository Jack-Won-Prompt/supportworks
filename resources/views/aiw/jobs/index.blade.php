@extends('layouts.app')

@section('title', '작업 지시 — '.$project->name)

@section('header-actions')
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:var(--color-text-secondary);font-weight:500;">작업 지시</span>
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'ai-works'])

@php
    // 점검 요청을 보낸 직후에는 결과가 곧 도착한다. 화면이 스스로 따라잡지 않으면
    // 사람이 새로고침을 눌러야 하고, 그러면 "요청했는데 아무 반응이 없다" 가 된다.
    $awaitSetup = (bool) session('status');
    $checkedAt  = $agents->mapWithKeys(fn ($a) => [
        $a->id => $a->agentProjects->first()?->setup_checked_at?->toIso8601String(),
    ]);
@endphp

@php
    // 온라인 판정은 이 프로젝트를 맡은 프로세스 기준이다. 한 PC 가 프로젝트마다
    // 따로 띄우면 그중 하나만 죽을 수 있어, 담당자 전체로 보면 멈춘 프로젝트가
    // 온라인으로 보인다.
    $online = $agents->filter(fn ($a) => $a->agentProjects->first()?->is_online ?? $a->is_online);
@endphp

<div class="space-y-3">

    {{-- 알림은 레이아웃이 전역 토스트로 띄운다(window.appToast). 여기서 배너로
         또 그리면 같은 문장이 두 번 보이고, 화면 위쪽이 밀려 내려간다. --}}

    {{-- 담당자 상태 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-1">
            <div>
                <h2 class="text-xl font-bold text-gray-900">작업 지시</h2>
                <p class="text-sm text-gray-500 mt-1">
                    지시를 등록하면 작업 담당자에게 전달하고 진행 상황을 실시간으로 확인합니다.
                </p>
            </div>
            @if ($online->isNotEmpty())
                <a href="{{ route('projects.ai-works.create', $project) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    새 지시
                </a>
            @else
                {{-- 왜 못 누르는지가 상황마다 다르다. 같은 문구를 쓰면 무엇을 해야
                     하는지 알 수 없다(매핑이 없는 것과 오프라인인 것은 조치가 다르다). --}}
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500 cursor-not-allowed"
                      title="{{ $agents->isEmpty()
                          ? '이 프로젝트에 매핑된 담당자가 없습니다'
                          : '매핑된 담당자가 모두 오프라인입니다' }}">새 지시</span>
            @endif
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($agents as $agent)
                @php
                    $mapping = $agent->agentProjects->first();
                    $isOnline = $mapping?->is_online ?? $agent->is_online;
                    $lastSeen = $mapping?->last_seen_at ?? $agent->last_seen_at;
                @endphp
                <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs
                            {{ $isOnline ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50' }}">
                    <span class="inline-block h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    <span class="font-semibold text-gray-800">{{ $agentNames[$agent->id] ?? $agent->name }}</span>
                    <span class="text-gray-500">
                        실행 중 {{ $runningByAgent[$agent->id] ?? 0 }}
                        / 상한 {{ $agent->capabilities['max_parallel_jobs'] ?? '—' }}
                    </span>
                    {{-- 소스 경로는 담당자 PC 의 내부 구조다. 이 화면에서 할 일은 지시를
                         내리고 진행을 보는 것이라 경로를 볼 이유가 없다. 고쳐야 할 때는
                         설정 › 담당자 에서 본다. --}}
                    @unless ($isOnline)
                        <span class="text-gray-400">
                            {{ $lastSeen ? $lastSeen->diffForHumans() : '접속 이력 없음' }}
                        </span>
                    @endunless

                    {{-- 준비 상태는 담당자 PC 가 점검해 보고한 것이다. 예전에는 지시를
                         넣어 봐야 드러나서, 화면에는 이유 없이 멈춘 것처럼 보였다. --}}
                    @if ($mapping?->setup_status && ! $mapping->setup_status->isReady())
                        {{-- Tailwind 는 조립한 클래스명을 못 찾는다. 전체 이름을 그대로 적는다. --}}
                        @php
                            $badge = match ($mapping->setup_status->tone()) {
                                'amber' => 'bg-amber-100 text-amber-800',
                                default => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="rounded px-1.5 py-0.5 font-medium {{ $badge }}"
                              title="{{ $mapping->setup_message }}">
                            {{ $mapping->setup_status->label() }}
                        </span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    이 프로젝트에 매핑된 담당자가 없습니다.
                    <span class="font-medium">관리자 › 담당자</span> 메뉴에서 등록·매핑해야 합니다.
                </p>
            @endforelse
        </div>

        @foreach ($agents as $agent)
            @php $m = $agent->agentProjects->first(); @endphp
            @if ($m?->setup_status && ! $m->setup_status->isReady())
                @php
                    $panel = match ($m->setup_status->tone()) {
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
                        default => 'border-red-200 bg-red-50 text-red-900',
                    };
                @endphp
                <div class="mt-3 rounded-lg border px-3 py-2 text-xs {{ $panel }}">
                    <div>
                        <span class="font-semibold">{{ $m->setup_status->label() }}</span> —
                        {{ $m->setup_message ?: '담당자 PC 에서 소스 폴더를 확인해야 합니다.' }}
                        @if ($m->setup_checked_at)
                            <span class="text-gray-500">({{ $m->setup_checked_at->diffForHumans() }} 점검)</span>
                        @endif
                    </div>

                    {{-- 여기서 바로 풀 수 있게 한다. 지금까지는 작업 PC 앞에 가야 했다. --}}
                    @if ($canCreate && ($m->is_online ?? false))
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <form method="POST"
                                  action="{{ route('projects.ai-works.mappings.action', [$project, $agent, 'recheck']) }}">
                                @csrf
                                <button class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 font-medium text-gray-700 hover:bg-gray-50">
                                    다시 점검
                                </button>
                            </form>

                            @if ($m->setup_status === \App\Enums\AiWork\AiwSetupStatus::DirtyTree)
                                <form method="POST"
                                      action="{{ route('projects.ai-works.mappings.action', [$project, $agent, 'cleanup']) }}"
                                      onsubmit="return confirm('작업 폴더의 미커밋 변경을 보관 브랜치(aiw/wip-…)로 옮기고 폴더를 정리합니다. 내용은 지워지지 않습니다. 진행할까요?')">
                                    @csrf
                                    <button class="rounded-lg border border-amber-300 bg-white px-2.5 py-1 font-medium text-amber-800 hover:bg-amber-100">
                                        작업 정리
                                    </button>
                                </form>
                                <span class="text-gray-500">정리하면 변경은 보관 브랜치로 옮겨집니다 — 지워지지 않습니다.</span>
                            @endif
                        </div>
                    @elseif ($canCreate)
                        <p class="mt-2 text-gray-500">담당자 PC 가 오프라인이라 여기서 점검·정리할 수 없습니다.</p>
                    @endif
                </div>
            @endif
        @endforeach

        @if ($agents->isNotEmpty() && $online->isEmpty())
            <p class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-900">
                매핑된 담당자가 모두 오프라인이라 새 지시를 등록할 수 없습니다.
                @if ($agents->every(fn ($a) => ($a->agentProjects->first()?->last_seen_at ?? $a->last_seen_at) === null))
                    {{-- 한 번도 접속한 적이 없다면 설치 자체가 안 된 것이다. --}}
                    <span class="font-medium">아직 한 번도 접속한 적이 없습니다</span> —
                    해당 PC 에서 데몬을 설치·기동해야 합니다.
                @else
                    해당 PC 에서 데몬이 실행 중인지 확인하세요.
                @endif
            </p>
        @endif
    </div>

    {{-- 지시 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between gap-2 mb-2">
            <h3 class="text-sm font-bold text-gray-900">지시 내역</h3>
            <form method="GET" class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border-gray-200 text-xs py-1.5 pr-8">
                    <option value="">전체 상태</option>
                    @foreach (\App\Enums\AiWork\AiwJobStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('status') === $case->value)>
                            {{ $case->label() }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                        <th class="py-2 pr-3 font-medium">제목</th>
                        <th class="py-2 pr-3 font-medium">모드</th>
                        <th class="py-2 pr-3 font-medium">담당자</th>
                        <th class="py-2 pr-3 font-medium">상태</th>
                        @if ($isAdmin)
                            <th class="py-2 pr-3 font-medium">등록자</th>
                        @endif
                        <th class="py-2 pr-3 font-medium">등록일</th>
                        @if ($isAdmin)
                            <th class="py-2 pr-3 font-medium">소요</th>
                            {{-- 담당자마다 과금 방식이 다를 수 있어 헤더는 중립어를 쓰고 행에서 구분한다. --}}
                            <th class="py-2 pr-3 font-medium">작업량</th>
                        @endif
                        <th class="py-2 pr-3 font-medium">세션</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="py-2 pr-3">
                                <a href="{{ route('projects.ai-works.show', [$project, $job]) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-700">{{ $job->title }}</a>
                                @if ($job->parent_job_id)
                                    <span class="ml-1 text-[11px] text-gray-400">후속 #{{ $job->parent_job_id }}</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3">
                                <span class="rounded px-1.5 py-0.5 text-[11px] {{ $job->mode === 'interactive' ? 'bg-violet-50 text-violet-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $job->mode === 'interactive' ? '대화형' : '단발' }}
                                </span>
                            </td>
                            <td class="py-2 pr-3 text-gray-600">{{ $agentNames[$job->agent_id] ?? $job->agent?->name ?? '—' }}</td>
                            <td class="py-2 pr-3">
                                <x-aiw.status-badge :status="$job->status" />
                            </td>
                            @if ($isAdmin)
                                <td class="py-2 pr-3 text-gray-600">{{ $job->creator?->name ?? '—' }}</td>
                            @endif
                            <td class="py-2 pr-3 text-gray-500">{{ $job->created_at?->format('m-d H:i') }}</td>
                            @if ($isAdmin)
                                <td class="py-2 pr-3 text-gray-500">
                                    {{ $job->duration_ms ? round($job->duration_ms / 1000).'초' : '—' }}
                                </td>
                                <td class="py-2 pr-3 text-gray-500" title="{{ $job->agent?->costHint() }}">
                                    ${{ number_format((float) $job->cost_usd, 2) }}
                                    <span class="text-gray-400">
                                        @if ($job->hasNoCostLimit())
                                            / 제한 없음
                                        @else
                                            / ${{ number_format((float) $job->cost_limit_usd, 2) }}
                                        @endif
                                    </span>
                                    @if ($job->agent && ! $job->agent->usesApiKey())
                                        <span class="text-[10px] text-gray-400">추정</span>
                                    @endif
                                </td>
                            @endif
                            <td class="py-2 pr-3 text-gray-500">{{ $job->handover_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 9 : 6 }}" class="py-8 text-center text-sm text-gray-400">
                                아직 등록된 지시가 없습니다.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $jobs->links() }}</div>
    </div>
</div>

@push('scripts')
<script>
// 점검·정리 결과 따라잡기.
//
// 요청은 데몬으로 나가고 결과는 나중에 /mappings/setup 으로 돌아온다. 마지막
// 점검 시각이 바뀌면 그 결과가 도착한 것이므로 화면을 다시 그린다. 서버 렌더를
// 그대로 쓰기 위해 부분 갱신 대신 새로고침을 쓴다 — 배지·문구·버튼 조건이
// 한곳(블레이드)에만 있어야 화면이 어긋나지 않는다.
(function () {
    if (!@json($awaitSetup)) { return; }

    const before = @json($checkedAt);
    const url = @json(route('projects.ai-works.mappings.status', $project));
    let tries = 0;

    const tick = async () => {
        if (tries++ >= 20) { return; }          // 3초 × 20 = 1분이면 충분하다

        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });

            if (res.ok) {
                const data = await res.json();
                const changed = (data.mappings || []).some(
                    (m) => (before[m.agent_id] ?? null) !== (m.checked_at ?? null)
                );

                if (changed) { window.location.reload(); return; }
            }
        } catch (e) { /* 통신이 끊겨도 계속 시도한다 */ }

        setTimeout(tick, 3000);
    };

    setTimeout(tick, 1500);
})();
</script>
@endpush

@endsection
