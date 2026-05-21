{{-- AI 호출 진행 모달 (옵션 입력 / 기획서 검토 화면에서 공유) --}}
{{-- 부모가 window.dispatchEvent(new CustomEvent('wb-ai-start', { detail: { statusUrl, cancelUrl, csrf, taskUrl, previewSvg } })) 호출하면 열림 --}}
<div x-data="wbAiProgressModal()"
     x-show="open" x-cloak
     @wb-ai-start.window="start($event.detail)"
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display:none;">
    {{-- dim --}}
    <div class="absolute inset-0 bg-black/45" @click.self="tryClose()"></div>

    {{-- 모달 카드 --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[92vh] flex flex-col overflow-hidden">
        {{-- 헤더 --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 flex items-center justify-center" x-show="state === 'calling'">
                    <span class="wb-pulse-dot"></span>
                </div>
                <div class="w-7 h-7 text-green-600" x-show="state === 'done'" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="w-7 h-7 text-red-500" x-show="state === 'failed'" x-cloak>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h2 class="text-base font-semibold text-gray-900">웍스가 HTML을 생성하고 있습니다</h2>
            </div>
            <button type="button" @click="tryClose()"
                    class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100"
                    title="닫기 (백그라운드 진행)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- 본문 (스크롤 가능) --}}
        <div class="px-5 py-4 overflow-y-auto space-y-4">
            {{-- 와이어프레임 --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 aspect-[16/10] flex items-center justify-center overflow-hidden">
                <template x-if="previewSvg">
                    <div class="w-full h-full" x-html="previewSvg"></div>
                </template>
                <template x-if="!previewSvg">
                    <div class="text-xs text-gray-400">레이아웃 와이어프레임</div>
                </template>
            </div>

            {{-- 안내 문구 --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2.5 text-xs text-amber-800 flex items-start gap-2">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="leading-relaxed">
                    <span class="font-semibold">생성에 수 분까지 걸릴 수 있습니다.</span>
                    이 창을 닫고 다른 화면으로 이동해도 됩니다 — 작업이 완료되면 <span class="font-semibold">알림 센터</span>와 <span class="font-semibold">FCM 푸시</span>로 알려드립니다.
                </div>
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

            {{-- 완료 시 요약 --}}
            <div x-show="state === 'done' && summary" x-cloak
                 class="text-[11px] text-gray-500 bg-gray-50 border border-gray-200 rounded-md px-3 py-2 flex justify-between tabular-nums">
                <span>토큰 <span class="font-mono text-gray-700" x-text="summary?.tokens"></span></span>
                <span>비용 <span class="font-mono text-gray-700" x-text="'$' + summary?.cost"></span></span>
                <span>소요 <span class="font-mono text-gray-700" x-text="summary?.elapsed + 's'"></span></span>
            </div>

            {{-- 실패 시 안내 --}}
            <div x-show="state === 'failed'" x-cloak class="text-xs text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2.5">
                웍스 호출이 실패했습니다. 옵션을 확인하고 다시 시도해 주세요.
            </div>
        </div>

        {{-- 푸터 액션 --}}
        <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-2 flex-shrink-0">
            <div class="text-[11px] text-gray-500" x-show="state === 'calling'">
                <span x-show="!cancelling">백그라운드로 계속 진행됩니다.</span>
                <span x-show="cancelling" x-cloak>취소 중...</span>
            </div>
            <div class="text-[11px] text-emerald-700 font-medium" x-show="state === 'done'" x-cloak>생성 완료. 결과 확인 화면으로 이동합니다.</div>
            <div class="text-[11px] text-red-600 font-medium" x-show="state === 'failed'" x-cloak>호출이 종료되었습니다.</div>

            <div class="flex items-center gap-2 ml-auto">
                <a :href="taskUrl" x-show="state === 'calling' && taskUrl" x-cloak
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-white text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                    Task로 이동
                </a>

                <button type="button" @click="reset()" x-show="state === 'calling'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-white text-gray-700">
                    닫고 백그라운드 진행
                </button>

                <form method="POST" :action="cfg.cancelUrl" x-show="state === 'calling'" @submit="cancelling = true">
                    <input type="hidden" name="_token" :value="cfg.csrf">
                    <button type="submit" :disabled="cancelling"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-red-200 text-red-600 rounded-md hover:bg-red-50 disabled:opacity-50">
                        호출 취소
                    </button>
                </form>

                <button type="button" x-show="state === 'failed'" @click="reset()" x-cloak
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-white text-gray-700">
                    닫기
                </button>
            </div>
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
        taskUrl: '',
        previewSvg: '',
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
            this.taskUrl       = detail.taskUrl    || '';
            this.previewSvg    = detail.previewSvg || '';
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
            // 모달 닫혀도 백엔드 job 은 계속 진행 — polling 만 중단.
            // 완료 시점에 NotificationDispatcher 가 in-app + FCM 알림 발송.
            this._stopped = true;
            this.state = 'idle';
        },

        tryClose() {
            // 호출 중에도 닫기 허용 (백그라운드 진행)
            this.reset();
        },

        tickProgress() {
            const tick = () => {
                if (this.state === 'done' || this.state === 'failed') return;
                if (this._stopped) return;
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
