@extends('layouts.app')

@section('title', '웍스 호출 진행 — Task #'.$task->id)

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">웍스 호출 진행</span>
@endsection

@section('content')
<div class="pt-4 max-w-3xl mx-auto"
     x-data="wbAiProgress(@js([
        'statusUrl' => route('wb.tasks.ai-progress.status', $task),
        'cancelUrl' => route('wb.tasks.ai-progress.cancel', $task),
        'csrf'      => csrf_token(),
        'initialStatus' => $task->status,
     ]))" x-init="poll(); tickProgress()">

    <div class="bg-white rounded-xl border border-gray-100 p-8 text-center space-y-6">
        <h2 class="text-xl font-semibold text-gray-900">웍스가 HTML을 생성하고 있습니다</h2>

        {{-- 상태 아이콘 (완료/실패만 표시) --}}
        <div class="flex justify-center" x-show="state !== 'calling'">
            <div class="w-12 h-12 text-emerald-600" x-show="state === 'done'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="w-12 h-12 text-red-500" x-show="state === 'failed'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
        </div>

        {{-- 프로그레스 바 --}}
        <div class="space-y-2 max-w-md mx-auto">
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-500 ease-out rounded-full"
                     :class="state === 'failed' ? 'bg-red-500' : 'bg-indigo-600'"
                     :style="`width: ${progress}%`"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <span x-text="elapsedLabel"></span>
                <span x-text="progressLabel"></span>
            </div>
        </div>

        <div class="text-sm text-gray-700" x-text="message"></div>

        {{-- 완료 시 간단 요약 --}}
        <div x-show="state === 'done' && summary"
             class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg p-3 max-w-md mx-auto inline-flex gap-4">
            <span>토큰 <span class="font-mono text-gray-700" x-text="summary?.tokens"></span></span>
            <span>비용 <span class="font-mono text-gray-700" x-text="'$' + summary?.cost"></span></span>
            <span>소요 <span class="font-mono text-gray-700" x-text="summary?.elapsed + 's'"></span></span>
        </div>

        {{-- 실패 시 안내 메시지 --}}
        <div x-show="state === 'failed'" class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg p-3 max-w-md mx-auto">
            웍스 호출이 실패했습니다. 옵션을 확인하고 다시 시도해 주세요.
        </div>

        <div class="flex justify-center gap-2 pt-2">
            <form method="POST" :action="cfg.cancelUrl" x-show="state === 'calling'">
                <input type="hidden" name="_token" :value="cfg.csrf">
                <button type="submit" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">호출 취소</button>
            </form>
            <a :href="nextUrl" x-show="nextUrl"
               class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">다음 단계로 →</a>
        </div>
    </div>
</div>

<script>
function wbAiProgress(cfg) {
    const EST_DURATION_MS = 90_000; // 평균 약 90초 소요 — 시간 기반 추정 진척률
    return {
        cfg,
        state: cfg.initialStatus === 'ai_calling' ? 'calling' : 'idle',
        message: '잠시만 기다려 주세요...',
        nextUrl: null,
        progress: 0,
        elapsedMs: 0,
        startedAt: Date.now(),
        summary: null,
        _stopped: false,

        get elapsedLabel() {
            const s = Math.floor(this.elapsedMs / 1000);
            return `${s}s 경과`;
        },
        get progressLabel() {
            if (this.state === 'done')   return '완료';
            if (this.state === 'failed') return '실패';
            return `${Math.floor(this.progress)}%`;
        },

        tickProgress() {
            const tick = () => {
                if (this.state === 'done' || this.state === 'failed') return;
                this.elapsedMs = Date.now() - this.startedAt;
                // 시간 기반 진척률 — 95%까지만 차오르고 완료 시 100%
                const pct = Math.min(95, (this.elapsedMs / EST_DURATION_MS) * 100);
                this.progress = pct;
                setTimeout(tick, 250);
            };
            tick();
        },

        async poll() {
            while (!this._stopped) {
                try {
                    const r = await fetch(cfg.statusUrl, {headers:{'Accept':'application/json'}});
                    const d = await r.json();

                    if (d.task_status === 'ai_calling') {
                        this.state = 'calling';
                        this.message = '잠시만 기다려 주세요...';
                    } else if (d.task_status === 'in_progress' && d.current_stage === 'result_confirm') {
                        this.state = 'done';
                        this.progress = 100;
                        this.message = '생성이 완료되었습니다.';
                        if (d.log) {
                            this.summary = {
                                tokens:  d.log.total_tokens ?? 0,
                                cost:    d.log.cost_usd ?? '0.0000',
                                elapsed: d.log.response_time_ms ? Math.round(d.log.response_time_ms / 1000) : 0,
                            };
                        }
                        this.nextUrl = d.next_url;
                        this._stopped = true;
                        setTimeout(() => { if (this.nextUrl) window.location.href = this.nextUrl; }, 800);
                        break;
                    } else if (d.log?.status === 'failed' || d.log?.status === 'cancelled') {
                        this.state = 'failed';
                        this.progress = 100;
                        this.message = d.log.status === 'cancelled'
                            ? '호출이 취소되었습니다.'
                            : '웍스 호출이 실패했습니다.';
                        this.nextUrl = d.next_url;
                        this._stopped = true;
                        break;
                    }
                } catch (e) { /* swallow */ }
                await new Promise(r => setTimeout(r, 2000));
            }
        },
    };
}
</script>
@endsection
