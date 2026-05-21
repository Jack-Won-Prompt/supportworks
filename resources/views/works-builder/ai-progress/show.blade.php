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
        'initialSteps'  => $steps->map(fn ($s) => [
            'sequence'    => $s->sequence,
            'code'        => $s->code,
            'label'       => $s->label,
            'status'      => $s->status,
            'context'     => $s->context,
            'started_at'  => $s->started_at?->toIso8601String(),
            'ended_at'    => $s->ended_at?->toIso8601String(),
            'duration_ms' => $s->duration_ms,
        ])->toArray(),
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

    {{-- 처리 과정 audit 로그 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                처리 과정 로그
                <span class="text-xs font-normal text-gray-400" x-text="`(${steps.length})`"></span>
            </h3>
            <span class="text-[11px] text-gray-400">실시간 폴링 · 2초 간격</span>
        </div>

        <template x-if="steps.length === 0">
            <p class="text-xs text-gray-400 text-center py-8">아직 기록된 단계가 없습니다.</p>
        </template>

        <ol class="relative border-l-2 border-gray-100 ml-2 space-y-3" x-show="steps.length > 0">
            <template x-for="step in steps" :key="step.sequence">
                <li class="ml-4">
                    <span class="absolute -left-[7px] mt-1.5 w-3 h-3 rounded-full ring-4 ring-white"
                          :class="{
                              'bg-emerald-500': step.status === 'success',
                              'bg-rose-500':    step.status === 'failed',
                              'bg-amber-500':   step.status === 'running',
                              'bg-gray-300':    step.status === 'pending' || step.status === 'skipped',
                          }"></span>
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] text-gray-400 font-mono" x-text="'#' + step.sequence"></span>
                            <span class="text-sm font-medium text-gray-800" x-text="step.label"></span>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 text-gray-500" x-text="step.code"></span>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500 tabular-nums">
                            <span x-show="step.duration_ms !== null && step.duration_ms !== undefined"
                                  x-text="(step.duration_ms / 1000).toFixed(2) + 's'"></span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium"
                                  :class="{
                                      'bg-emerald-100 text-emerald-700': step.status === 'success',
                                      'bg-rose-100 text-rose-700':       step.status === 'failed',
                                      'bg-amber-100 text-amber-700':     step.status === 'running',
                                      'bg-gray-100 text-gray-600':       step.status === 'pending' || step.status === 'skipped',
                                  }"
                                  x-text="step.status"></span>
                            <span x-show="step.started_at" class="text-gray-400" x-text="formatTime(step.started_at)"></span>
                        </div>
                    </div>
                    <template x-if="step.context && Object.keys(step.context).length > 0">
                        <div class="mt-1 text-[11px] text-gray-500 font-mono bg-gray-50 border border-gray-100 rounded px-2 py-1 break-all" x-text="JSON.stringify(step.context)"></div>
                    </template>
                </li>
            </template>
        </ol>
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
        steps: cfg.initialSteps || [],
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

        formatTime(iso) {
            if (!iso) return '';
            try {
                const d = new Date(iso);
                return d.toLocaleTimeString('ko-KR', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } catch (e) { return ''; }
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

                    if (Array.isArray(d.steps)) this.steps = d.steps;

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
