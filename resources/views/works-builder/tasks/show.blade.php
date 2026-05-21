@extends('layouts.app')

@section('title', 'Task #'.$task->id)

@php
    $stageLabels = [
        'draft'             => '초안', 'option_input' => '옵션 입력',
        'spec_review'       => '기획서 검토', 'ai_calling' => '웍스 호출 중',
        'result_confirm'    => '결과 1차 확인', 'qa_review' => '검수 진행 중',
        'ng_input'          => 'NG 미스 입력', 'complete' => '완료',
        'cancelled'         => '취소',
    ];
    $statusBadge = match($task->status) {
        'completed'  => ['bg-green-100','text-green-700','완료'],
        'cancelled'  => ['bg-red-100','text-red-700','취소'],
        'ai_calling' => ['bg-amber-100','text-amber-700','웍스 호출 중'],
        default      => ['bg-blue-100','text-blue-700','진행 중'],
    };
    $lastSession   = $task->reviewSessions->sortByDesc('review_round')->first();
    $latestPrompts = $task->generatedHtml->sortByDesc('created_at');

    $nextHref = null; $nextLabel = null;
    if (!$task->isImmutable()) {
        switch ($task->current_stage) {
            case 'option_input':  $nextHref = route('wb.tasks.options.edit', $task);          $nextLabel = '옵션 입력으로'; break;
            case 'spec_review':   $nextHref = route('wb.tasks.spec-review.show', $task);      $nextLabel = '기획서 검토로'; break;
            case 'ai_calling':    $nextHref = route('wb.tasks.ai-progress.show', $task);     $nextLabel = '웍스 진행 화면'; break;
            case 'result_confirm':$nextHref = route('wb.tasks.result-confirm.show', $task); $nextLabel = '결과 확인'; break;
            case 'qa_review':
                if ($lastSession) { $nextHref = route('wb.tasks.review.show', ['task'=>$task,'session'=>$lastSession->id]); $nextLabel = '검수로'; }
                break;
            case 'ng_input':
                if ($lastSession) { $nextHref = route('wb.tasks.ng-input.create', ['task'=>$task,'session'=>$lastSession->id]); $nextLabel = 'NG 미스 입력'; }
                break;
        }
    }
@endphp

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">Task #{{ $task->id }}</span>
@endsection

@section('header-actions')
    @if ($nextHref)
        <a href="{{ $nextHref }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
            {{ $nextLabel }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    @endif
    @if ($task->isCompleted())
        <form method="POST" action="{{ route('wb.tasks.reopen', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('재실행할까요? 신규 Task로 분기됩니다.')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                재실행
            </button>
        </form>
        <form method="POST" action="{{ route('wb.tasks.clone', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('복제할까요? 옵션만 복사됩니다.')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                복제
            </button>
        </form>
        <a href="{{ route('wb.tasks.package.html', $task) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            HTML
        </a>
        <a href="{{ route('wb.tasks.package.download', $task) }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-green-300 text-green-700 rounded-lg hover:bg-green-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            패키지
        </a>
    @elseif (!$task->isCancelled())
        <form method="POST" action="{{ route('wb.tasks.cancel', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('취소할까요?')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border border-red-300 text-red-700 rounded-lg hover:bg-red-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                취소
            </button>
        </form>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Task 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">Task #{{ $task->id }}</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $statusBadge[0] }} {{ $statusBadge[1] }}">{{ $statusBadge[2] }}</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $task->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                {{ $task->mode === 'new' ? '신규 화면 (A)' : '고도화 (B)' }}
            </span>
        </div>
        <p class="text-xs text-gray-400 font-mono">{{ $task->task_uuid }}</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">프로젝트</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->project?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">현재 단계</p>
                <p class="text-sm font-medium text-gray-700">{{ $stageLabels[$task->current_stage] ?? $task->current_stage }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">담당자</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->assignee?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">검수 차수</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->current_review_round }}차</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">웍스 호출</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->total_ai_calls }}회 · ${{ number_format((float)$task->total_cost_usd, 4) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">시작</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->started_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">완료</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->completed_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">검수 세션</p>
                <p class="text-sm font-medium text-gray-700">{{ $task->reviewSessions->count() }}건</p>
            </div>
        </div>
    </div>

    {{-- 분기 관계 (parent/children) --}}
    @if ($task->parent || $task->children->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7H7v6m0 0l4 4-4-4m4-4l4 4-4-4m-4-4h12M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7"/></svg>
                    연관 Task
                </h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                @if ($task->parent)
                    <div>
                        <p class="text-xs text-gray-400 mb-1">부모 ({{ $task->reopen_reason === 'reopen' ? '재실행' : '복제' }} 원본)</p>
                        <a href="{{ route('wb.tasks.show', $task->parent) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 font-medium">
                            #{{ $task->parent->id }}
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                @endif
                @if ($task->children->isNotEmpty())
                    <div>
                        <p class="text-xs text-gray-400 mb-1">자식 ({{ $task->children->count() }})</p>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach ($task->children as $c)
                                <a href="{{ route('wb.tasks.show', $c) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full hover:bg-indigo-200 font-medium">
                                    #{{ $c->id }}
                                    <span class="text-indigo-400">· {{ $c->reopen_reason }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- 옵션 / 기획서 --}}
    @if ($task->mode === 'new' && $task->currentOption)
        @php $d = $task->currentOption->options_data ?? []; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    현재 옵션 <span class="text-xs font-normal text-gray-400">v{{ $task->currentOption->version }}</span>
                </h3>
                @if (!$task->isImmutable())
                    <a href="{{ route('wb.tasks.options.edit', $task) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">편집 →</a>
                @endif
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 mb-1">GNB 위치</p>
                    <p class="text-sm font-medium text-gray-700">{{ $d['gnb_position'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">탭 구조</p>
                    <p class="text-sm font-medium text-gray-700">{{ $d['tab_structure'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">화면 전환</p>
                    <p class="text-sm font-medium text-gray-700">{{ $d['transition_type'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">메인 색상</p>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 rounded border border-gray-200" style="background: {{ $d['main_color'] ?? '#3b82f6' }};"></span>
                        <code class="text-xs text-gray-700 font-mono">{{ $d['main_color'] ?? '#3b82f6' }}</code>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 생성된 HTML --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    생성된 HTML
                    <span class="text-xs font-normal text-gray-400">({{ $task->generatedHtml->count() }})</span>
                </h3>
                @if (!$task->isImmutable() && $task->current_stage === 'result_confirm')
                    <a href="{{ route('wb.tasks.result-confirm.show', $task) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">결과 확인 →</a>
                @endif
            </div>
            @if ($task->generatedHtml->isEmpty())
                <p class="text-xs text-gray-400 text-center py-8">아직 생성된 HTML이 없습니다.</p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs">
                            <tr class="border-t border-b border-gray-100">
                                <th class="px-5 py-2 text-left font-medium">v</th>
                                <th class="px-5 py-2 text-center font-medium">차수</th>
                                <th class="px-5 py-2 text-left font-medium">엔진</th>
                                <th class="px-5 py-2 text-right font-medium">크기</th>
                                <th class="px-5 py-2 text-left font-medium">생성</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($latestPrompts as $html)
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-2 text-xs font-medium text-gray-700">v{{ $html->version }}</td>
                                    <td class="px-5 py-2 text-center text-xs text-gray-600">{{ $html->review_round }}</td>
                                    <td class="px-5 py-2">
                                        <span class="inline-block text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full">{{ $html->generated_by }}</span>
                                    </td>
                                    <td class="px-5 py-2 text-right text-xs text-gray-500">{{ number_format(strlen($html->html_content)) }} B</td>
                                    <td class="px-5 py-2 text-xs text-gray-500">{{ $html->created_at?->format('m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- 검수 세션 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    검수 세션
                    <span class="text-xs font-normal text-gray-400">({{ $task->reviewSessions->count() }})</span>
                </h3>
            </div>
            @if ($task->reviewSessions->isEmpty())
                <p class="text-xs text-gray-400 text-center py-8">검수 세션이 없습니다.</p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs">
                            <tr class="border-t border-b border-gray-100">
                                <th class="px-5 py-2 text-left font-medium">차수</th>
                                <th class="px-5 py-2 text-left font-medium">결정</th>
                                <th class="px-5 py-2 text-left font-medium">무결성</th>
                                <th class="px-5 py-2 text-left font-medium">종료</th>
                                <th class="px-5 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($task->reviewSessions->sortByDesc('review_round') as $s)
                                @php
                                    $db = match($s->decision) {
                                        'ok' => ['bg-green-100 text-green-700','OK'],
                                        'ng' => ['bg-red-100 text-red-700','NG'],
                                        default => ['bg-amber-100 text-amber-700','진행 중'],
                                    };
                                @endphp
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-2 text-xs font-medium text-gray-700">{{ $s->review_round }}차</td>
                                    <td class="px-5 py-2"><span class="text-xs px-2 py-0.5 rounded-full {{ $db[0] }}">{{ $db[1] }}</span></td>
                                    <td class="px-5 py-2 text-xs">{{ $s->ended_at ? ($s->integrity_passed ? '✓ 통과' : '✗ 실패') : '—' }}</td>
                                    <td class="px-5 py-2 text-xs text-gray-500">{{ $s->ended_at?->format('m-d H:i') ?? '—' }}</td>
                                    <td class="px-5 py-2 text-right">
                                        <a href="{{ route('wb.tasks.review.show', ['task'=>$task,'session'=>$s->id]) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">상세 →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- 패키지 정보 --}}
    @if ($task->isCompleted())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    출력 패키지
                </h3>
                <form method="POST" action="{{ route('wb.tasks.package.rebuild', $task) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">재빌드</button>
                </form>
            </div>
            @if ($latestPackage)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">크기</p>
                        <p class="text-sm font-medium text-gray-700">{{ number_format($latestPackage->file_size_bytes) }} B</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">빌드</p>
                        <p class="text-sm font-medium text-gray-700">{{ $latestPackage->built_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">SHA-256</p>
                        <p class="text-xs font-mono text-gray-600 break-all">{{ $latestPackage->package_hash }}</p>
                    </div>
                </div>
            @else
                <p class="text-xs text-gray-400 text-center py-8">패키지가 아직 빌드되지 않았습니다. (자동 빌드 중)</p>
            @endif
        </div>
    @endif
</div>
@endsection
