@php
    $statusBadges = $statusBadges ?? [
        'pending'            => ['label' => '대기',         'classes' => 'bg-slate-100 text-slate-700'],
        'analyzing'          => ['label' => '분석중',       'classes' => 'bg-blue-100 text-blue-700'],
        'awaiting_approval'  => ['label' => '승인 대기',    'classes' => 'bg-yellow-100 text-yellow-700'],
        'auto_approved'      => ['label' => '자동 승인',    'classes' => 'bg-emerald-100 text-emerald-700'],
        'blocked'            => ['label' => '차단',         'classes' => 'bg-red-100 text-red-700'],
        'applying'           => ['label' => '수정중',       'classes' => 'bg-blue-100 text-blue-700'],
        'testing'            => ['label' => '테스트중',     'classes' => 'bg-blue-100 text-blue-700'],
        'tests_failed'       => ['label' => '테스트 실패',  'classes' => 'bg-red-100 text-red-700'],
        'ready_to_deploy'    => ['label' => '배포 대기',    'classes' => 'bg-amber-100 text-amber-700'],
        'deploying'          => ['label' => '배포중',       'classes' => 'bg-indigo-100 text-indigo-700'],
        'deployed'           => ['label' => '배포 완료',    'classes' => 'bg-emerald-100 text-emerald-700'],
        'deploy_failed'      => ['label' => '배포 실패',    'classes' => 'bg-red-100 text-red-700'],
        'rolled_back'        => ['label' => '롤백',         'classes' => 'bg-orange-100 text-orange-700'],
        'rejected'           => ['label' => '거부',         'classes' => 'bg-slate-100 text-slate-500'],
        'cancelled'          => ['label' => '취소',         'classes' => 'bg-slate-100 text-slate-500'],
    ];
    $badge = $statusBadges[$job->status] ?? ['label' => $job->status, 'classes' => 'bg-slate-100 text-slate-700'];
    $canAct = in_array($job->status, ['awaiting_approval', 'ready_to_deploy'], true);
    $actionLabel = $job->status === 'awaiting_approval' ? '수정 승인' : '배포 승인';
@endphp

{{-- 헤더 카드 --}}
<div class="bg-white border border-slate-200 rounded-xl p-5 mb-4">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="text-base font-bold text-slate-800">AI Fix Job #{{ $job->id }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">{{ $badge['label'] }}</span>
                @if($job->decision)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono bg-slate-100 text-slate-600">decision: {{ $job->decision }}</span>
                @endif
            </div>
            @if($job->proposed_fix_summary)
            <p class="text-sm text-slate-700 leading-relaxed">{{ $job->proposed_fix_summary }}</p>
            @endif
            @if($job->decision_reason)
            <p class="text-xs text-slate-500 mt-2">사유: {{ $job->decision_reason }}</p>
            @endif
        </div>
    </div>

    @if($canAct)
    <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">
        <form method="POST" action="{{ route('admin.ai-fix-jobs.approve', $job) }}" class="flex-1"
              onsubmit="return confirm('{{ $actionLabel }} 처리하시겠습니까?')">
            @csrf
            <button type="submit" class="w-full px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                ✓ {{ $actionLabel }}
            </button>
        </form>
        <form method="POST" action="{{ route('admin.ai-fix-jobs.reject', $job) }}" class="flex-1"
              onsubmit="this.querySelector('input[name=reason]').value = (prompt('거부 사유 (선택)') ?? ''); return confirm('거부 처리하시겠습니까?');">
            @csrf
            <input type="hidden" name="reason" value="">
            <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                거부
            </button>
        </form>
    </div>
    @endif
</div>

{{-- 신호 chip --}}
@php $reds = $job->red_signals ?? []; $yellows = $job->yellow_signals ?? []; @endphp
@if(!empty($reds) || !empty($yellows))
<div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">에스컬레이션 신호</h3>
    <div class="flex flex-wrap gap-1.5">
        @foreach($reds as $sig)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">🔴 {{ $sig }}</span>
        @endforeach
        @foreach($yellows as $sig)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200">🟡 {{ $sig }}</span>
        @endforeach
    </div>
    @if($job->blocked_path)
    <p class="mt-3 text-xs text-red-600">차단된 경로: <span class="font-mono">{{ $job->blocked_path }}</span></p>
    @endif
</div>
@endif

{{-- 상세 정보 그리드 --}}
<div class="grid grid-cols-2 gap-4 mb-4">
    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Job 정보</h3>
        <dl class="space-y-2 text-sm">
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">브랜치</dt>
                <dd class="font-mono text-slate-700 text-xs" style="word-break: break-all;">{{ $job->branch_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">PR</dt>
                <dd class="font-mono text-slate-700 text-xs" style="word-break: break-all;">
                    @if($job->pr_url)
                        <a href="{{ $job->pr_url }}" target="_blank" class="text-indigo-600 hover:underline">{{ $job->pr_url }}</a>
                    @elseif(!empty($job->test_result['merge']['pr_url'] ?? null))
                        <a href="{{ $job->test_result['merge']['pr_url'] }}" target="_blank" class="text-indigo-600 hover:underline">{{ $job->test_result['merge']['pr_url'] }}</a>
                    @else — @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">배포 commit</dt>
                <dd class="font-mono text-slate-700 text-xs">{{ $job->deployed_commit ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">승인자</dt>
                <dd class="text-slate-700 text-xs">
                    @if($job->approved_by_id && $job->approved_by_type)
                        {{ optional($job->approvedBy)->name ?? '(unknown)' }}
                        <span class="text-slate-400">({{ class_basename($job->approved_by_type) }} #{{ $job->approved_by_id }})</span>
                    @elseif($job->approved_by_admin_id)
                        admin id #{{ $job->approved_by_admin_id }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400 mb-0.5">재시도</dt>
                <dd class="text-slate-700 text-xs">{{ $job->retry_count }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-4">
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">타임라인</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-xs text-slate-400">생성</dt><dd class="text-xs text-slate-700">{{ $job->created_at->format('Y-m-d H:i:s') }}</dd></div>
            @if($job->escalated_at)
            <div class="flex justify-between"><dt class="text-xs text-slate-400">에스컬레이트</dt><dd class="text-xs text-slate-700">{{ $job->escalated_at->format('Y-m-d H:i:s') }}</dd></div>
            @endif
            @if($job->approved_at)
            <div class="flex justify-between"><dt class="text-xs text-slate-400">승인</dt><dd class="text-xs text-slate-700">{{ $job->approved_at->format('Y-m-d H:i:s') }}</dd></div>
            @endif
            @if($job->deployed_at)
            <div class="flex justify-between"><dt class="text-xs text-slate-400">배포</dt><dd class="text-xs text-slate-700">{{ $job->deployed_at->format('Y-m-d H:i:s') }}</dd></div>
            @endif
            @if($job->finished_at)
            <div class="flex justify-between"><dt class="text-xs text-slate-400">종료</dt><dd class="text-xs text-slate-700">{{ $job->finished_at->format('Y-m-d H:i:s') }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-xs text-slate-400">변경</dt><dd class="text-xs text-slate-700">{{ $job->updated_at->format('Y-m-d H:i:s') }}</dd></div>
        </dl>
    </div>
</div>

@if(!empty($job->changed_files))
<div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">변경된 파일 ({{ count($job->changed_files) }})</h3>
    <ul class="space-y-1 text-xs font-mono text-slate-700">
        @foreach($job->changed_files as $f)
        <li style="word-break: break-all;">• {{ $f }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($error)
<div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">원본 에러
        <a href="{{ route('admin.system-errors.show', $error) }}" class="ml-2 font-normal text-indigo-600 hover:underline normal-case">→ SystemErrorLog #{{ $error->id }}</a>
    </h3>
    <p class="text-sm text-slate-800 font-semibold" style="word-break: break-all;">{{ $error->message }}</p>
    <p class="text-xs font-mono text-indigo-500 mt-1">{{ $error->exception }}</p>
    @if($error->file)
    <p class="text-xs font-mono text-slate-500 mt-1" style="word-break: break-all;">{{ $error->file }}@if($error->line):{{ $error->line }}@endif</p>
    @endif
</div>
@endif

@if(!empty($job->test_result))
<div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">테스트·머지·배포 결과</h3>
    <pre class="text-xs font-mono text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-3" style="overflow-x: auto; white-space: pre-wrap; word-break: break-all; max-height: 280px; overflow-y: auto;">{{ json_encode($job->test_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
@endif

@if($job->error_message)
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
    <h3 class="text-xs font-semibold text-red-700 uppercase tracking-wider mb-2">실패 메시지</h3>
    <p class="text-sm text-red-800" style="word-break: break-all;">{{ $job->error_message }}</p>
</div>
@endif
