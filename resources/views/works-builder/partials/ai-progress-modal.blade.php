{{-- AI 호출 진행 모달 (옵션 입력 / 기획서 검토 화면에서 공유) --}}
{{-- 부모가 window.dispatchEvent(new CustomEvent('wb-ai-start', { detail: { statusUrl, cancelUrl, csrf } })) 호출하면 열림 --}}
<div x-data="wbAiProgressModal()"
     x-show="open" x-cloak
     @wb-ai-start.window="start($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display:none;">
    {{-- dim --}}
    <div class="absolute inset-0 bg-black/45" @click.self="tryClose()"></div>

    {{-- 모달 카드 --}}
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 space-y-4">
        {{-- 헤더 --}}
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 flex-shrink-0 flex items-center justify-center" x-show="state === 'calling'">
                <span class="wb-pulse-dot"></span>
            </div>
            <div class="w-7 h-7 flex-shrink-0 text-emerald-600" x-show="state === 'done'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="w-7 h-7 flex-shrink-0 text-red-500" x-show="state === 'failed'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h2 class="text-base font-semibold text-gray-900">웍스가 HTML을 생성하고 있습니다</h2>
        </div>

        {{-- 프로그레스 바 (shimmer) --}}
        <div class="space-y-1.5">
            <div class="wb-progress-track h-2.5 bg-gray-100 rounded-full overflow-hidden relative">
                <div class="wb-progress-fill h-full rounded-full transition-all duration-500 ease-out"
                     :class="{
                         'wb-shimmer': state === 'calling',
                         'bg-indigo-600': state !== 'failed',
                         'bg-red-500': state === 'failed',
                     }"
                     :style="`width: ${Math.max(progress, 5)}%`"></div>
            </div>
            <div class="flex justify-between text-[11px] text-gray-500 tabular-nums">
                <span x-text="elapsedLabel"></span>
                <span x-text="progressLabel" class="font-medium"></span>
            </div>
        </div>

        {{-- 단계 표시 --}}
        <div class="flex items-center justify-between text-[11px]">
            <template x-for="(step, i) in steps" :key="i">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full transition-colors"
                          :class="i <= currentStep ? 'bg-indigo-600' : 'bg-gray-300'"></span>
                    <span :class="i === currentStep ? 'text-indigo-700 font-medium' : 'text-gray-400'"
                          x-text="step"></span>
                </div>
            </template>
        </div>

        {{-- 완료 시 요약 (한 줄) --}}
        <div x-show="state === 'done' && summary"
             class="text-[11px] text-gray-500 bg-gray-50 border border-gray-200 rounded-md p-2 flex justify-between tabular-nums">
            <span>토큰 <span class="font-mono text-gray-700" x-text="summary?.tokens"></span></span>
            <span>비용 <span class="font-mono text-gray-700" x-text="'$' + summary?.cost"></span></span>
            <span>소요 <span class="font-mono text-gray-700" x-text="summary?.elapsed + 's'"></span></span>
        </div>

        {{-- 실패 시 안내 --}}
        <div x-show="state === 'failed'" class="text-[11px] text-red-600 bg-red-50 border border-red-200 rounded-md p-2.5">
            웍스 호출이 실패했습니다. 옵션을 확인하고 다시 시도해 주세요.
        </div>

        {{-- 액션 --}}
        <div class="flex justify-center pt-1">
            <form method="POST" :action="cfg.cancelUrl" x-show="state === 'calling'" @submit="cancelling = true">
                <input type="hidden" name="_token" :value="cfg.csrf">
                <button type="submit" :disabled="cancelling"
                        class="px-4 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-gray-50 text-gray-700 disabled:opacity-50">
                    호출 취소
                </button>
            </form>
            <button type="button" x-show="state === 'failed'" @click="reset()"
                    class="px-4 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-gray-50 text-gray-700">
                닫기
            </button>
        </div>
    </div>
</div>

<style>
    .wb-progress-fill { position: relative; overflow: hidden; }
    .wb-shimmer::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg,
            rgba(255,255,255,0) 0%,
            rgba(255,255,255,0.35) 50%,
            rgba(255,255,255,0) 100%);
        animation: wb-shimmer-move 1.6s linear infinite;
    }
    @keyframes wb-shimmer-move {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .wb-pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #4f46e5;
        box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.55);
        animation: wb-pulse 1.4s ease-out infinite;
    }
    @keyframes wb-pulse {
        0%   { box-shadow: 0 0 0 0   rgba(79, 70, 229, 0.55); }
        70%  { box-shadow: 0 0 0 9px rgba(79, 70, 229, 0);    }
        100% { box-shadow: 0 0 0 0   rgba(79, 70, 229, 0);    }
    }
</style>

<script>
function wbAiProgressModal() {
    const EST_DURATION_MS = 90_000;
    const STEPS = ['옵션 분석', '프롬프트 구성', '웍스 생성'];
    return {
        open: false,
        cfg: { statusUrl: '', cancelUrl: '', csrf: '' },
        state: 'idle',
        steps: STEPS,
        progress: 0,
        elapsedMs: 0,
        startedAt: 0,
        summary: null,
        nextUrl: null,
        cancelling: false,
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
        get currentStep() {
            if (this.state === 'done')   return STEPS.length - 1;
            if (this.progress < 20) return 0;
            if (this.progress < 70) return 1;
            return 2;
        },

        start(detail) {
            this.cfg.statusUrl = detail.statusUrl;
            this.cfg.cancelUrl = detail.cancelUrl;
            this.cfg.csrf      = detail.csrf;
            this.state         = 'calling';
            this.progress      = 0;
            this.elapsedMs     = 0;
            this.startedAt     = Date.now();
            this.summary       = null;
            this.nextUrl       = null;
            this.cancelling    = false;
            this._stopped      = false;
            this.open          = true;

            this.tickProgress();
            this.poll();
        },

        reset() {
            this.open = false;
            this.state = 'idle';
            this._stopped = true;
        },

        tryClose() {
            if (this.state !== 'calling') this.reset();
        },

        tickProgress() {
            const tick = () => {
                if (this.state === 'done' || this.state === 'failed') return;
                this.elapsedMs = Date.now() - this.startedAt;
                this.progress  = Math.min(95, (this.elapsedMs / EST_DURATION_MS) * 100);
                setTimeout(tick, 250);
            };
            tick();
        },

        async poll() {
            while (!this._stopped) {
                try {
                    const r = await fetch(this.cfg.statusUrl, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();

                    if (d.task_status === 'ai_calling') {
                        // continue
                    } else if (d.task_status === 'in_progress' && d.current_stage === 'result_confirm') {
                        this.state    = 'done';
                        this.progress = 100;
                        if (d.log) {
                            this.summary = {
                                tokens:  d.log.total_tokens ?? 0,
                                cost:    d.log.cost_usd ?? '0.0000',
                                elapsed: d.log.response_time_ms ? Math.round(d.log.response_time_ms / 1000) : 0,
                            };
                        }
                        this.nextUrl  = d.next_url;
                        this._stopped = true;
                        setTimeout(() => { if (this.nextUrl) window.location.href = this.nextUrl; }, 1200);
                        break;
                    } else if (d.log?.status === 'failed' || d.log?.status === 'cancelled') {
                        this.state    = 'failed';
                        this.progress = 100;
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
