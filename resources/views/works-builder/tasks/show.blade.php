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
        'completed'  => ['bg-emerald-100','text-emerald-800','완료'],
        'cancelled'  => ['bg-red-100','text-red-800','취소'],
        'ai_calling' => ['bg-amber-100','text-amber-800','웍스 호출 중'],
        default      => ['bg-blue-100','text-blue-800','진행 중'],
    };
    $lastSession = $task->reviewSessions->sortByDesc('review_round')->first();
    $latestPrompts = $task->generatedHtml->sortByDesc('created_at');

    $nextHref = null; $nextLabel = null;
    if (!$task->isImmutable()) {
        switch ($task->current_stage) {
            case 'option_input': $nextHref = route('wb.tasks.options.edit', $task); $nextLabel = '옵션 입력으로'; break;
            case 'spec_review':  $nextHref = route('wb.tasks.spec-review.show', $task); $nextLabel = '기획서 검토로'; break;
            case 'ai_calling':   $nextHref = route('wb.tasks.ai-progress.show', $task); $nextLabel = '웍스 진행 화면'; break;
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
        <a href="{{ $nextHref }}" class="inline-flex items-center px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ $nextLabel }} →</a>
    @endif
    @if ($task->isCompleted())
        <form method="POST" action="{{ route('wb.tasks.reopen', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('재실행할까요? 신규 Task로 분기됩니다.')"
                    class="px-4 py-2 text-sm border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50">🔄 재실행</button>
        </form>
        <form method="POST" action="{{ route('wb.tasks.clone', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('복제할까요? 옵션만 복사됩니다.')"
                    class="px-4 py-2 text-sm border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50">📋 복제</button>
        </form>
        <a href="{{ route('wb.tasks.package.html', $task) }}"
           class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">📥 HTML 다운</a>
        <a href="{{ route('wb.tasks.package.download', $task) }}"
           class="px-4 py-2 text-sm border border-emerald-300 text-emerald-700 rounded-lg hover:bg-emerald-50">📦 패키지</a>
    @elseif (!$task->isCancelled())
        <form method="POST" action="{{ route('wb.tasks.cancel', $task) }}" class="inline">
            @csrf
            <button type="submit" onclick="return confirm('취소할까요?')"
                    class="px-3 py-2 text-sm border border-red-300 text-red-700 rounded-lg hover:bg-red-50">취소</button>
        </form>
    @endif
@endsection

@section('content')
<div class="pt-4 space-y-5">
    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- UUID 표시 --}}
    <div class="text-xs text-gray-400 font-mono">{{ $task->task_uuid }}</div>

    {{-- 메타 카드 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">프로젝트</div>
            <div class="text-sm font-medium">{{ $task->project?->name ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">모드</div>
            <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $task->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                {{ $task->mode === 'new' ? '신규 화면 (A)' : '고도화 (B)' }}
            </span>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">현재 단계</div>
            <div class="text-sm font-medium">{{ $stageLabels[$task->current_stage] ?? $task->current_stage }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">상태</div>
            <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $statusBadge[0] }} {{ $statusBadge[1] }}">{{ $statusBadge[2] }}</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">담당자</div>
            <div class="text-sm">{{ $task->assignee?->name ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">검수 차수</div>
            <div class="text-sm font-mono">{{ $task->current_review_round }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">웍스 호출</div>
            <div class="text-sm font-mono">{{ $task->total_ai_calls }} · ${{ number_format((float)$task->total_cost_usd, 4) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">시작 / 완료</div>
            <div class="text-xs">{{ $task->started_at?->format('m-d H:i') ?? '—' }} / {{ $task->completed_at?->format('m-d H:i') ?? '—' }}</div>
        </div>
    </div>

    {{-- 분기 관계 (parent/children) --}}
    @if ($task->parent || $task->children->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold mb-3 text-gray-900">연관 Task</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                @if ($task->parent)
                    <div>
                        <dt class="text-xs text-gray-500">부모 Task ({{ $task->reopen_reason === 'reopen' ? '재실행' : '복제' }} 원본)</dt>
                        <dd><a href="{{ route('wb.tasks.show', $task->parent) }}" class="text-indigo-600 hover:underline">#{{ $task->parent->id }}</a></dd>
                    </div>
                @endif
                @if ($task->children->isNotEmpty())
                    <div>
                        <dt class="text-xs text-gray-500">자식 Task ({{ $task->children->count() }})</dt>
                        <dd class="flex flex-wrap gap-2 mt-1">
                            @foreach ($task->children as $c)
                                <a href="{{ route('wb.tasks.show', $c) }}"
                                   class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-700 rounded-full hover:bg-indigo-100">
                                    #{{ $c->id }} · {{ $c->reopen_reason }}
                                </a>
                            @endforeach
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif

    {{-- 옵션 / 기획서 --}}
    @if ($task->mode === 'new' && $task->currentOption)
        @php $d = $task->currentOption->options_data ?? []; @endphp
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-gray-900">현재 옵션 (v{{ $task->currentOption->version }})</h2>
                @if (!$task->isImmutable())
                    <a href="{{ route('wb.tasks.options.edit', $task) }}" class="text-xs text-indigo-600 hover:underline">편집 →</a>
                @endif
            </div>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><dt class="text-xs text-gray-500">GNB 위치</dt><dd>{{ $d['gnb_position'] ?? '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">탭 구조</dt><dd>{{ $d['tab_structure'] ?? '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">화면 전환</dt><dd>{{ $d['transition_type'] ?? '—' }}</dd></div>
                <div>
                    <dt class="text-xs text-gray-500">메인 색상</dt>
                    <dd class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 rounded border border-gray-200" style="background: {{ $d['main_color'] ?? '#3b82f6' }};"></span>
                        <code class="text-xs">{{ $d['main_color'] ?? '#3b82f6' }}</code>
                    </dd>
                </div>
            </dl>
        </div>
    @endif

    {{-- 생성된 HTML --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-semibold text-gray-900">생성된 HTML <span class="text-xs text-gray-400 font-normal">({{ $task->generatedHtml->count() }})</span></h2>
            @if (!$task->isImmutable() && $task->current_stage === 'result_confirm')
                <a href="{{ route('wb.tasks.result-confirm.show', $task) }}" class="text-xs text-indigo-600 hover:underline">결과 확인 →</a>
            @endif
        </div>
        @if ($task->generatedHtml->isEmpty())
            <p class="text-sm text-gray-500">아직 생성된 HTML이 없습니다.</p>
        @else
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr><th class="py-2 text-left">v</th><th class="py-2 text-left">차수</th><th class="py-2 text-left">엔진</th><th class="py-2 text-left">크기</th><th class="py-2 text-left">SHA-256</th><th class="py-2 text-left">생성</th></tr>
                </thead>
                <tbody>
                    @foreach ($latestPrompts as $html)
                        <tr class="border-t border-gray-50">
                            <td class="py-2">v{{ $html->version }}</td>
                            <td class="py-2 text-center">{{ $html->review_round }}</td>
                            <td class="py-2 text-xs"><span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full">{{ $html->generated_by }}</span></td>
                            <td class="py-2 text-xs text-gray-500">{{ number_format(strlen($html->html_content)) }} B</td>
                            <td class="py-2 text-xs font-mono text-gray-400">{{ \Illuminate\Support\Str::limit($html->html_hash, 12, '…') }}</td>
                            <td class="py-2 text-xs text-gray-500">{{ $html->created_at?->format('m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 검수 세션 --}}
    @if ($task->reviewSessions->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold mb-3 text-gray-900">검수 세션 ({{ $task->reviewSessions->count() }})</h2>
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr><th class="py-2 text-left">차수</th><th class="py-2 text-left">결정</th><th class="py-2 text-left">무결성</th><th class="py-2 text-left">시작</th><th class="py-2 text-left">종료</th><th class="py-2"></th></tr>
                </thead>
                <tbody>
                    @foreach ($task->reviewSessions->sortByDesc('review_round') as $s)
                        @php
                            $db = match($s->decision) {
                                'ok'=>['bg-emerald-100 text-emerald-800','OK'],
                                'ng'=>['bg-red-100 text-red-800','NG'],
                                default=>['bg-amber-100 text-amber-800','진행 중'],
                            };
                        @endphp
                        <tr class="border-t border-gray-50">
                            <td class="py-2 font-mono">{{ $s->review_round }}</td>
                            <td class="py-2"><span class="text-xs px-2 py-0.5 rounded-full {{ $db[0] }}">{{ $db[1] }}</span></td>
                            <td class="py-2 text-xs">{{ $s->ended_at ? ($s->integrity_passed ? '✓ 통과' : '✗ 실패') : '—' }}</td>
                            <td class="py-2 text-xs text-gray-500">{{ $s->started_at?->format('m-d H:i') }}</td>
                            <td class="py-2 text-xs text-gray-500">{{ $s->ended_at?->format('m-d H:i') ?? '—' }}</td>
                            <td class="py-2 text-right"><a href="{{ route('wb.tasks.review.show', ['task'=>$task,'session'=>$s->id]) }}" class="text-xs text-indigo-600 hover:underline">상세</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- 패키지 정보 --}}
    @if ($task->isCompleted())
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-gray-900">출력 패키지</h2>
                <form method="POST" action="{{ route('wb.tasks.package.rebuild', $task) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:underline">재빌드</button>
                </form>
            </div>
            @if ($latestPackage)
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><dt class="text-xs text-gray-500">크기</dt><dd>{{ number_format($latestPackage->file_size_bytes) }} B</dd></div>
                    <div><dt class="text-xs text-gray-500">빌드</dt><dd>{{ $latestPackage->built_at?->format('Y-m-d H:i') }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-500">SHA-256</dt><dd class="text-xs font-mono break-all">{{ $latestPackage->package_hash }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-gray-500">패키지가 아직 빌드되지 않았습니다. (자동 빌드 중)</p>
            @endif
        </div>
    @endif
</div>
@endsection
