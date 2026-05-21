@extends('layouts.admin')

@section('title', 'AI Fix Jobs')

@php
    $statusBadges = [
        'pending'            => ['label' => '대기',          'classes' => 'bg-slate-100 text-slate-700'],
        'analyzing'          => ['label' => '분석중',        'classes' => 'bg-blue-100 text-blue-700'],
        'awaiting_approval'  => ['label' => '승인 대기',     'classes' => 'bg-yellow-100 text-yellow-700'],
        'auto_approved'     => ['label' => '자동 승인',     'classes' => 'bg-emerald-100 text-emerald-700'],
        'blocked'            => ['label' => '차단',          'classes' => 'bg-red-100 text-red-700'],
        'applying'           => ['label' => '수정중',        'classes' => 'bg-blue-100 text-blue-700'],
        'testing'            => ['label' => '테스트중',      'classes' => 'bg-blue-100 text-blue-700'],
        'tests_failed'       => ['label' => '테스트 실패',   'classes' => 'bg-red-100 text-red-700'],
        'ready_to_deploy'    => ['label' => '배포 대기',     'classes' => 'bg-amber-100 text-amber-700'],
        'deploying'          => ['label' => '배포중',        'classes' => 'bg-indigo-100 text-indigo-700'],
        'deployed'           => ['label' => '배포 완료',     'classes' => 'bg-emerald-100 text-emerald-700'],
        'deploy_failed'      => ['label' => '배포 실패',     'classes' => 'bg-red-100 text-red-700'],
        'rolled_back'        => ['label' => '롤백',          'classes' => 'bg-orange-100 text-orange-700'],
        'rejected'           => ['label' => '거부',          'classes' => 'bg-slate-100 text-slate-500'],
        'cancelled'          => ['label' => '취소',          'classes' => 'bg-slate-100 text-slate-500'],
    ];

    $tabs = [
        'awaiting_approval' => '승인 대기',
        'active'            => '진행중',
        'terminal'          => '완료/실패',
        'all'               => '전체',
    ];
@endphp

@section('content')
<div class="p-6">

    {{-- 헤더 --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">AI Fix Jobs</h1>
            <p class="text-sm text-slate-500 mt-0.5">시스템 에러 자동 수정 작업 승인 큐</p>
        </div>
    </div>

    {{-- 성공/에러 메시지 --}}
    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- 통계 카드 --}}
    <div class="flex gap-3 mb-6">
        <div class="flex-1 bg-white border border-yellow-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center text-base shrink-0">⏳</div>
            <div>
                <div class="text-xl font-bold text-yellow-600 leading-tight">{{ number_format($stats['awaiting']) }}</div>
                <div class="text-xs text-slate-500">승인 대기</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-blue-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center text-base shrink-0">🔧</div>
            <div>
                <div class="text-xl font-bold text-blue-600 leading-tight">{{ number_format($stats['active']) }}</div>
                <div class="text-xs text-slate-500">진행중</div>
            </div>
        </div>
        <div class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-base shrink-0">📋</div>
            <div>
                <div class="text-xl font-bold text-slate-800 leading-tight">{{ number_format($stats['total']) }}</div>
                <div class="text-xs text-slate-500">전체</div>
            </div>
        </div>
    </div>

    {{-- 상태 탭 --}}
    <div class="flex gap-1 mb-4 border-b border-slate-200">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.ai-fix-jobs.index', ['status' => $key]) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 transition
                      {{ $status === $key ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent hover:text-slate-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- 목록 --}}
    @if($jobs->isEmpty())
    <div class="bg-white border border-slate-200 rounded-xl py-16 text-center text-slate-400 text-sm">
        해당 상태의 작업이 없습니다.
    </div>
    @else
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold">#</th>
                    <th class="text-left px-4 py-3 font-semibold">상태</th>
                    <th class="text-left px-4 py-3 font-semibold">에러</th>
                    <th class="text-left px-4 py-3 font-semibold">결정</th>
                    <th class="text-left px-4 py-3 font-semibold">브랜치</th>
                    <th class="text-left px-4 py-3 font-semibold">생성</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($jobs as $job)
                @php $badge = $statusBadges[$job->status] ?? ['label' => $job->status, 'classes' => 'bg-slate-100 text-slate-700']; @endphp
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-4 py-3 font-mono text-xs text-slate-700">#{{ $job->id }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 max-w-md">
                        @if($job->systemErrorLog)
                            <div class="text-xs font-mono text-indigo-500">{{ $job->systemErrorLog->exception }}</div>
                            <div class="text-xs text-slate-600 truncate">{{ $job->systemErrorLog->message }}</div>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($job->decision)
                            <span class="font-mono text-slate-600">{{ $job->decision }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-500">{{ $job->branch_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ $job->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.ai-fix-jobs.show', $job) }}"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition">
                            상세
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
    @endif

</div>
@endsection
