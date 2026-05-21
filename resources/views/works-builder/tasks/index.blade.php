@extends('layouts.app')

@section('title', '진행 중 Task')

@section('header-actions')
    <a href="{{ route('wb.tasks.completed') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        완료된 Task
    </a>
    <a href="{{ route('wb.tasks.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        새 작업
    </a>
@endsection

@php
    $stageLabels = [
        'draft'          => '초안',          'option_input'   => '옵션 입력',
        'spec_review'    => '기획서 검토',   'ai_calling'     => '웍스 호출 중',
        'result_confirm' => '결과 1차 확인', 'qa_review'      => '검수 진행 중',
        'ng_input'       => 'NG 미스 입력',
    ];
    $byStage = $inProgress->getCollection()->groupBy('current_stage');
    $aiCallingCnt = $byStage->get('ai_calling', collect())->count();
    $reviewCnt    = $byStage->get('qa_review', collect())->count() + $byStage->get('ng_input', collect())->count();
    $draftCnt     = $byStage->get('option_input', collect())->count() + $byStage->get('spec_review', collect())->count() + $byStage->get('result_confirm', collect())->count();
@endphp

@section('content')
<div class="space-y-6">
    {{-- Works Builder 개요 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">진행 중 Task</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">{{ $inProgress->total() }}건</span>
        </div>
        <p class="text-sm text-gray-500">웍스가 직접 HTML을 생성하는 파이프라인. 완료된 Task는 불변이며 수정은 재실행/복제로 분기됩니다.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">전체 진행 중</p>
                <p class="text-sm font-medium text-gray-700">{{ $inProgress->total() }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">옵션·기획·확인 단계</p>
                <p class="text-sm font-medium text-gray-700">{{ $draftCnt }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">웍스 호출 중</p>
                <p class="text-sm font-medium {{ $aiCallingCnt > 0 ? 'text-amber-600' : 'text-gray-700' }}">{{ $aiCallingCnt }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">검수 / NG 입력</p>
                <p class="text-sm font-medium {{ $reviewCnt > 0 ? 'text-rose-600' : 'text-gray-700' }}">{{ $reviewCnt }}건</p>
            </div>
        </div>
    </div>

    {{-- Task 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Task 목록
                <span class="text-xs font-normal text-gray-400">({{ $inProgress->total() }})</span>
            </h3>
            <a href="{{ route('wb.tasks.completed') }}" class="text-xs text-gray-500 hover:text-gray-700">완료된 Task 보기 →</a>
        </div>

        @if ($inProgress->isEmpty())
            <p class="text-xs text-gray-400 text-center py-10">진행 중인 작업이 없습니다.</p>
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr class="border-t border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left font-medium">#</th>
                            <th class="px-5 py-2.5 text-left font-medium">프로젝트</th>
                            <th class="px-5 py-2.5 text-left font-medium">모드</th>
                            <th class="px-5 py-2.5 text-left font-medium">현재 단계</th>
                            <th class="px-5 py-2.5 text-center font-medium">검수 차수</th>
                            <th class="px-5 py-2.5 text-left font-medium">시작</th>
                            <th class="px-5 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inProgress as $task)
                            @php
                                $stageColor = match($task->current_stage) {
                                    'ai_calling'           => 'bg-amber-100 text-amber-700',
                                    'qa_review','ng_input' => 'bg-rose-100 text-rose-700',
                                    'result_confirm'       => 'bg-green-100 text-green-700',
                                    default                => 'bg-blue-100 text-blue-700',
                                };
                            @endphp
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
                                <td class="px-5 py-3">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $stageColor }}">
                                        {{ $stageLabels[$task->current_stage] ?? $task->current_stage }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center text-xs text-gray-600">{{ $task->current_review_round }}</td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $task->started_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('wb.tasks.show', $task) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">상세 →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-4">{{ $inProgress->links() }}</div>
        @endif
    </div>
</div>
@endsection
