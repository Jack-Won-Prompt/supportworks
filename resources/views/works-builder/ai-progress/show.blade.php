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
<div x-data="wbAiProgress(@js([
        'statusUrl'     => route('wb.tasks.ai-progress.status', $task),
        'cancelUrl'     => route('wb.tasks.ai-progress.cancel', $task),
        'csrf'          => csrf_token(),
        'initialStatus' => $task->status,
     ]))" x-init="poll(); tickProgress()" class="space-y-6">

    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">웍스 호출 진행</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700" x-show="state === 'calling'">진행 중</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700" x-show="state === 'done'" x-cloak>완료</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700" x-show="state === 'failed'" x-cloak>실패</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
        </div>
        <p class="text-sm text-gray-500">웍스가 HTML을 생성하고 있습니다. 완료되면 결과 1차 확인 화면으로 자동 이동합니다.</p>
    </div>

    {{-- 진행 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <div class="max-w-2xl mx-auto space-y-6">
            {{-- 상태 아이콘 --}}
            <div class="flex justify-center" x-show="state !== 'calling'" x-cloak>
                <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center" x-show="state === 'done'">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center" x-show="state === 'failed'">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>

            {{-- 메시지 --}}
            <div class="text-center">
                <h3 class="text-base font-semibold text-gray-900">웍스가 HTML을 생성하고 있습니다</h3>
                <p class="text-sm text-gray-500 mt-1" x-text="message"></p>
            </div>

            {{-- 프로그레스 바 --}}
            <div class="space-y-2">
                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500 ease-out rounded-full"
                         :class="state === 'failed' ? 'bg-red-500' : 'bg-indigo-600'"
                         :style="`width: ${progress}%`"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 tabular-nums">
                    <span x-text="elapsedLabel"></span>
                    <span x-text="progressLabel" class="font-semibold"></span>
                </div>
            </div>

            {{-- 완료 시 요약 --}}
            <div x-show="state === 'done' && summary" x-cloak
                 class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-50">
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">토큰</p>
                    <p class="text-sm font-mono font-medium text-gray-700" x-text="summary?.tokens"></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">비용</p>
                    <p class="text-sm font-mono font-medium text-gray-700" x-text="'$' + summary?.cost"></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">소요</p>
                    <p class="text-sm font-mono font-medium text-gray-700" x-text="summary?.elapsed + 's'"></p>
                </div>
            </div>

            {{-- 실패 안내 --}}
            <div x-show="state === 'failed'" x-cloak class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
                웍스 호출이 실패했습니다. 옵션을 확인하고 다시 시도해 주세요.
            </div>

            {{-- 액션 --}}
            <div class="flex justify-center gap-2 pt-2">
                <form method="POST" :action="cfg.cancelUrl" x-show="state === 'calling'">
                    <input type="hidden" name="_token" :value="cfg.csrf">
                    <button type="submit" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">호출 취소</button>
                </form>
                <a :href="nextUrl" x-show="nextUrl" x-cloak
                   class="inline-flex items-center gap-2 px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                    다음 단계로
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none !important}</style>

<script>
function wbAiProgress(cfg) {
    const EST_DURATION_MS = 90_000;
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
