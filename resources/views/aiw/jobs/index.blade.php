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
    $online = $agents->filter(fn ($a) => $a->is_online);
@endphp

<div class="space-y-3">

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

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
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500 cursor-not-allowed"
                      title="온라인 상태인 담당자가 없습니다">새 지시</span>
            @endif
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($agents as $agent)
                @php $mapping = $agent->agentProjects->first(); @endphp
                <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs
                            {{ $agent->is_online ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50' }}">
                    <span class="inline-block h-2 w-2 rounded-full {{ $agent->is_online ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    <span class="font-semibold text-gray-800">{{ $agent->name }}</span>
                    <span class="text-gray-500">
                        실행 중 {{ $runningByAgent[$agent->id] ?? 0 }}
                        / 상한 {{ $agent->capabilities['max_parallel_jobs'] ?? '—' }}
                    </span>
                    @if ($mapping)
                        <span class="text-gray-400" title="{{ $mapping->local_path }}">
                            {{ \Illuminate\Support\Str::limit($mapping->local_path, 28) }}
                        </span>
                    @endif
                    @unless ($agent->is_online)
                        <span class="text-gray-400">
                            {{ $agent->last_seen_at ? $agent->last_seen_at->diffForHumans() : '접속 이력 없음' }}
                        </span>
                    @endunless
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    이 프로젝트에 매핑된 담당자가 없습니다.
                    <span class="font-medium">관리자 › 담당자</span> 메뉴에서 등록·매핑해야 합니다.
                </p>
            @endforelse
        </div>
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
                        <th class="py-2 pr-3 font-medium">등록자</th>
                        <th class="py-2 pr-3 font-medium">등록일</th>
                        <th class="py-2 pr-3 font-medium">소요</th>
                        {{-- 담당자마다 과금 방식이 다를 수 있어 헤더는 중립어를 쓰고 행에서 구분한다. --}}
                        <th class="py-2 pr-3 font-medium">작업량</th>
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
                            <td class="py-2 pr-3 text-gray-600">{{ $job->agent?->name ?? '—' }}</td>
                            <td class="py-2 pr-3">
                                <x-aiw.status-badge :status="$job->status" />
                            </td>
                            <td class="py-2 pr-3 text-gray-600">{{ $job->creator?->name ?? '—' }}</td>
                            <td class="py-2 pr-3 text-gray-500">{{ $job->created_at?->format('m-d H:i') }}</td>
                            <td class="py-2 pr-3 text-gray-500">
                                {{ $job->duration_ms ? round($job->duration_ms / 1000).'초' : '—' }}
                            </td>
                            <td class="py-2 pr-3 text-gray-500" title="{{ $job->agent?->costHint() }}">
                                ${{ number_format((float) $job->cost_usd, 2) }}
                                <span class="text-gray-400">/ ${{ number_format((float) $job->cost_limit_usd, 2) }}</span>
                                @if ($job->agent && ! $job->agent->usesApiKey())
                                    <span class="text-[10px] text-gray-400">추정</span>
                                @endif
                            </td>
                            <td class="py-2 pr-3 text-gray-500">{{ $job->handover_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-sm text-gray-400">
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
@endsection
