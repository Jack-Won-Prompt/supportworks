@extends('layouts.app')

@section('title', '운영 오류 — '.$project->name)

@section('header-actions')
@endsection

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">
    <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a> › 운영 오류
</span>
@endsection

@php
    $labels = [
        'new'      => ['미처리', 'bg-amber-50 text-amber-700'],
        'queued'   => ['지시 생성', 'bg-blue-50 text-blue-700'],
        'patching' => ['수정 중', 'bg-blue-50 text-blue-700'],
        'resolved' => ['해결', 'bg-green-50 text-green-700'],
        'ignored'  => ['무시', 'bg-gray-100 text-gray-500'],
        'blocked'  => ['사람 확인', 'bg-red-50 text-red-700'],
    ];
@endphp

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'aiw-errors'])

<div class="space-y-3">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-bold text-gray-900">운영 오류</h2>
            <span class="text-sm text-gray-500">{{ $project->name }}</span>
        </div>
        <p class="mt-1 text-sm text-gray-500">
            운영 사이트에서 올라온 예외입니다. <span class="font-medium">같은 예외·파일·줄은 한 건으로 묶여</span> 발생 횟수만 올라갑니다.
        </p>

        {{-- 상태별 추림. 평소에는 미처리만 보면 된다. --}}
        <div class="mt-3 flex flex-wrap gap-1.5">
            <a href="{{ route('projects.aiw-errors.index', $project) }}"
               class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $status === '' ? 'bg-gray-900 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                전체 {{ $counts->sum() }}
            </a>
            @foreach ($labels as $key => [$label, $class])
                @if (($counts[$key] ?? 0) > 0)
                    <a href="{{ route('projects.aiw-errors.index', [$project, 'status' => $key]) }}"
                       class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $status === $key ? 'bg-gray-900 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        {{ $label }} {{ $counts[$key] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if ($reports->isEmpty())
            <p class="text-sm text-gray-500">
                아직 올라온 오류가 없습니다.
                @if ($counts->isEmpty())
                    운영 사이트가 아직 보내지 않고 있을 수 있습니다 — 관리자 › 오류 수집 출처에서 토큰을 확인하세요.
                @endif
            </p>
        @else
            <div class="space-y-2">
                @foreach ($reports as $report)
                    @php [$label, $class] = $labels[$report->status] ?? [$report->status, 'bg-gray-100 text-gray-500']; @endphp
                    <div class="rounded-lg border border-gray-100 px-3 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded px-1.5 py-0.5 text-xs {{ $class }}">{{ $label }}</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $report->exception ?: '예외' }}</span>

                            {{-- 몇 번 났는지가 우선순위다. 한 번 난 것과 천 번 난 것은 다른 일이다. --}}
                            @if ($report->count > 1)
                                <span class="rounded bg-red-50 px-1.5 py-0.5 text-xs font-semibold text-red-700">{{ number_format($report->count) }}회</span>
                            @endif

                            <span class="text-xs text-gray-400">{{ $report->last_seen_at?->diffForHumans() }}</span>

                            @if ($report->job)
                                <a href="{{ route('projects.ai-works.show', [$project, $report->job]) }}"
                                   class="text-xs font-medium text-blue-600 hover:underline">작업 지시 #{{ $report->job->id }}</a>
                            @endif

                            <form method="POST" action="{{ route('projects.aiw-errors.ignore', [$project, $report]) }}" class="ml-auto">
                                @csrf
                                <button class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                    {{ $report->status === 'ignored' ? '되돌리기' : '무시' }}
                                </button>
                            </form>
                        </div>

                        @if ($report->message)
                            <div class="mt-1 text-sm text-gray-700">{{ Str::limit($report->message, 200) }}</div>
                        @endif

                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-gray-400">
                            @if ($report->file)
                                <span class="font-mono">{{ $report->file }}{{ $report->line ? ':'.$report->line : '' }}</span>
                            @endif
                            @if ($report->url)
                                <span class="font-mono">{{ Str::limit($report->url, 80) }}</span>
                            @endif
                            @if ($report->patch_attempts > 0)
                                <span>자동 수정 시도 {{ $report->patch_attempts }}회</span>
                            @endif
                            <span>{{ $report->source?->name }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">{{ $reports->links() }}</div>
        @endif
    </div>
</div>
@endsection
