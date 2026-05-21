@extends('layouts.app')

@section('title', '완료된 Task')

@section('header-actions')
    <a href="{{ route('wb.tasks.index') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        ← 진행 중 Task
    </a>
@endsection

@php
    $totalCalls = $completed->getCollection()->sum('total_ai_calls');
    $totalCost  = $completed->getCollection()->sum(fn ($t) => (float) ($t->total_cost_usd ?? 0));
@endphp

@section('content')
<div class="space-y-6">
    {{-- 개요 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">완료된 Task</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">{{ $completed->total() }}건</span>
        </div>
        <p class="text-sm text-gray-500">완료된 Task는 불변입니다. 재실행/복제로 분기하여 수정합니다.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">완료 건수</p>
                <p class="text-sm font-medium text-gray-700">{{ $completed->total() }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">현재 페이지 호출수</p>
                <p class="text-sm font-medium text-gray-700">{{ number_format($totalCalls) }}회</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">현재 페이지 비용</p>
                <p class="text-sm font-medium text-gray-700">${{ number_format($totalCost, 4) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">표시 개수</p>
                <p class="text-sm font-medium text-gray-700">{{ $completed->count() }} / {{ $completed->total() }}</p>
            </div>
        </div>
    </div>

    {{-- 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                완료 목록
                <span class="text-xs font-normal text-gray-400">({{ $completed->total() }})</span>
            </h3>
            <a href="{{ route('wb.tasks.index') }}" class="text-xs text-gray-500 hover:text-gray-700">진행 중 Task 보기 →</a>
        </div>

        @if ($completed->isEmpty())
            <p class="text-xs text-gray-400 text-center py-10">완료된 작업이 없습니다.</p>
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr class="border-t border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left font-medium">#</th>
                            <th class="px-5 py-2.5 text-left font-medium">프로젝트</th>
                            <th class="px-5 py-2.5 text-left font-medium">모드</th>
                            <th class="px-5 py-2.5 text-center font-medium">검수 차수</th>
                            <th class="px-5 py-2.5 text-left font-medium">시작</th>
                            <th class="px-5 py-2.5 text-left font-medium">완료</th>
                            <th class="px-5 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($completed as $task)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-gray-400">#{{ $task->id }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                                            {{ mb_substr($task->project?->name ?? '?', 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-800 font-medium">{{ $task->project?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $task->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $task->mode === 'new' ? '신규' : '고도화' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center text-xs text-gray-600">{{ $task->current_review_round }}</td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $task->started_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ $task->completed_at?->format('Y-m-d H:i') }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('wb.tasks.show', $task) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">상세 →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-4">{{ $completed->links() }}</div>
        @endif
    </div>
</div>
@endsection
