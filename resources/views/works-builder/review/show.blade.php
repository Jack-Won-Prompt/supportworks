@extends('layouts.app')

@section('title', '검수 ' . $session->review_round . '차수 — Task #'.$task->id)

@push('styles')
<style>
    .wb-overlay { position: absolute; pointer-events: none; transition: all 60ms linear; }
    .wb-overlay-hover { outline: 2px solid #f59e0b; background: rgba(245, 158, 11, 0.08); }
    .wb-overlay-pinned { outline: 2px solid #dc2626; background: rgba(220, 38, 38, 0.10); }
    .wb-iframe-wrap { position: relative; overflow: hidden; }
</style>
@endpush

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">검수 {{ $session->review_round }}차수</span>
@endsection

@section('content')
<div class="pt-4"
     x-data="wbReview(@js([
        'task_id'      => $task->id,
        'session_id'   => $session->id,
        'review_round' => $session->review_round,
        'start_hash'   => $session->start_hash,
        'decideUrl'    => route('wb.tasks.review.decide', ['task' => $task, 'session' => $session]),
        'hasPrevious'  => $previous !== null,
     ]))">

    <div class="flex justify-between items-center mb-3">
        <p class="text-xs text-gray-500">
            HTML #{{ $session->generated_html_id }} ·
            <span class="font-mono">{{ substr($session->start_hash, 0, 16) }}…</span>
        </p>
        @if ($previous)
            <label class="text-xs flex items-center gap-2">
                <input type="checkbox" x-model="compareMode">
                이전 {{ $previous->review_round }}차수와 좌/우 비교
            </label>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
        {{-- 좌측: iframe + overlay --}}
        <div class="lg:col-span-3">
            <div class="grid gap-3" :class="compareMode ? 'grid-cols-2' : 'grid-cols-1'">
                @if ($previous)
                    <div x-show="compareMode" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div class="px-3 py-2 border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-600">
                            이전 {{ $previous->review_round }}차수 (참고)
                        </div>
                        <div class="wb-iframe-wrap" style="height: 640px;">
                            <iframe sandbox="allow-same-origin"
                                    class="w-full h-full bg-white"
                                    srcdoc="{{ \App\Services\WorksBuilder\Preview\PreviewHtmlSanitizer::prepareForIframe($previous->html->html_content) }}"></iframe>
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden"
                     x-data="{ full: false }"
                     :class="full ? 'fixed inset-0 z-50 m-0 rounded-none' : 'relative'"
                     @keydown.escape.window="full = false">
                    <div class="px-3 py-2 border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-600 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span>현재 {{ $session->review_round }}차수 (검수 대상)</span>
                            <span x-show="hoverInfo" x-text="hoverInfo?.tag + (hoverInfo?.id ? '#'+hoverInfo.id : '')"
                                  class="font-mono text-[10px] text-amber-600"></span>
                        </div>
                        <button type="button" @click="full = !full"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-gray-200 rounded-md hover:bg-gray-100">
                            <svg x-show="!full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            <svg x-show="full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span x-text="full ? '닫기 (Esc)' : '전체 화면'"></span>
                        </button>
                    </div>
                    <div class="wb-iframe-wrap" :style="full ? 'height: calc(100vh - 38px);' : 'height: 640px;'" x-ref="wrap">
                        <iframe x-ref="iframe" sandbox="allow-same-origin"
                                class="w-full h-full bg-white"
                                srcdoc="{{ \App\Services\WorksBuilder\Preview\PreviewHtmlSanitizer::prepareForIframe($session->html->html_content) }}"
                                @load="attachListeners"></iframe>
                        <div x-ref="hoverBox" class="wb-overlay wb-overlay-hover" style="display:none;"></div>
                        <div x-ref="pinnedBox" class="wb-overlay wb-overlay-pinned" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 우측: 명령어 박스 + 결정 --}}
        <div class="lg:col-span-1 space-y-3">
            <div class="bg-white rounded-xl border border-gray-100 p-3 text-sm">
                <h3 class="font-semibold mb-2 text-gray-900">선택된 요소</h3>
                <template x-if="pinned">
                    <div class="space-y-1 text-xs">
                        <div><span class="text-gray-500">태그:</span> <code x-text="pinned.tag"></code></div>
                        <div x-show="pinned.id"><span class="text-gray-500">ID:</span> <code x-text="pinned.id"></code></div>
                        <div x-show="pinned.classes"><span class="text-gray-500">class:</span> <code x-text="pinned.classes"></code></div>
                        <div x-show="pinned.text"><span class="text-gray-500">텍스트:</span> <span class="italic" x-text="pinned.text"></span></div>
                        <div class="text-[10px] text-gray-400">
                            x:<span x-text="pinned.x"></span> y:<span x-text="pinned.y"></span>
                            w:<span x-text="pinned.w"></span> h:<span x-text="pinned.h"></span>
                        </div>
                        <div class="pt-1 border-t border-gray-100 text-[10px] text-gray-400">경로: <span x-text="pinned.path"></span></div>
                        <div class="flex gap-1 pt-2">
                            <button @click="copyInfo()" class="flex-1 px-2 py-1 border border-gray-200 rounded text-xs hover:bg-gray-50">
                                <span x-show="!copied">정보 복사</span>
                                <span x-show="copied" class="text-green-600">복사됨!</span>
                            </button>
                            <button @click="appendToCommand()" class="flex-1 px-2 py-1 border border-gray-200 rounded text-xs hover:bg-gray-50">+ 명령어</button>
                        </div>
                    </div>
                </template>
                <p x-show="!pinned" class="text-xs text-gray-400">iframe 안의 요소를 클릭하면 정보가 표시됩니다.</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-3 text-sm">
                <h3 class="font-semibold mb-2 text-gray-900">명령어 박스</h3>
                <textarea x-model="commandBox" rows="6"
                          class="w-full border border-gray-200 rounded-lg p-2 font-mono text-xs"
                          placeholder="요소 선택 후 [+ 명령어]로 누적, 또는 자유 입력"></textarea>
                <button @click="copyCommand()" class="w-full mt-2 px-2 py-1 border border-gray-200 rounded text-xs hover:bg-gray-50"
                        :disabled="!commandBox">
                    명령어 전체 복사
                </button>
            </div>

            <form method="POST" :action="cfg.decideUrl" @submit="onDecideSubmit">
                @csrf
                <input type="hidden" name="decision" x-ref="decisionInput">
                <input type="hidden" name="command_box" :value="commandBox">
                <input type="hidden" name="highlights" :value="JSON.stringify(highlightsToSubmit())">
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-sm space-y-2">
                    <h3 class="font-semibold text-gray-900">검수 결정</h3>
                    <button type="button" @click="decide('ok')" class="w-full px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        ✓ OK — 완료
                    </button>
                    <button type="button" @click="decide('ng')" class="w-full px-3 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
                        ✗ NG — 재생성 필요
                    </button>
                    <p class="text-[10px] text-gray-400 leading-relaxed pt-1 border-t border-gray-100">
                        결정 시 HTML 종료 시점 hash를 시작 hash와 비교하여 무결성을 검증합니다 (명세 §1.6).
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function wbReview(cfg) {
    return {
        cfg,
        compareMode: false,
        hoverInfo: null,
        pinned: null,
        commandBox: '',
        collectedHighlights: [],
        copied: false,

        attachListeners() {
            const iframe = this.$refs.iframe;
            const doc = iframe.contentDocument;
            if (!doc) return;

            doc.addEventListener('mousemove', (e) => this.onIframeMouseMove(e));
            doc.addEventListener('click', (e) => this.onIframeClick(e));
            doc.addEventListener('mouseleave', () => this.hideHover());

            doc.defaultView?.addEventListener('scroll', () => this.refreshPinned(), { passive: true });
        },

        onIframeMouseMove(e) {
            const doc = this.$refs.iframe.contentDocument;
            const el = doc.elementFromPoint(e.clientX, e.clientY);
            if (!el || el === doc.documentElement) { this.hideHover(); return; }

            this.hoverInfo = this.extract(el);
            this.positionBox(this.$refs.hoverBox, el);
        },

        onIframeClick(e) {
            e.preventDefault();
            const doc = this.$refs.iframe.contentDocument;
            const el = doc.elementFromPoint(e.clientX, e.clientY);
            if (!el) return;

            this.pinned = this.extract(el);
            this.positionBox(this.$refs.pinnedBox, el);
        },

        hideHover() {
            this.$refs.hoverBox.style.display = 'none';
            this.hoverInfo = null;
        },

        positionBox(box, el) {
            const iframe = this.$refs.iframe;
            const irect = iframe.getBoundingClientRect();
            const erect = el.getBoundingClientRect();
            const wrapRect = this.$refs.wrap.getBoundingClientRect();

            const top  = (irect.top  - wrapRect.top)  + erect.top;
            const left = (irect.left - wrapRect.left) + erect.left;

            box.style.display = 'block';
            box.style.top    = top  + 'px';
            box.style.left   = left + 'px';
            box.style.width  = erect.width  + 'px';
            box.style.height = erect.height + 'px';
            box.dataset.targetTag = el.tagName.toLowerCase();
        },

        refreshPinned() {
            if (!this.pinned) return;
            const doc = this.$refs.iframe.contentDocument;
            const el = doc.querySelector(this.pinned.selector);
            if (el) this.positionBox(this.$refs.pinnedBox, el);
        },

        extract(el) {
            const rect = el.getBoundingClientRect();
            return {
                tag: el.tagName.toLowerCase(),
                id: el.id || '',
                classes: el.className?.toString?.() || '',
                text: (el.textContent || '').trim().slice(0, 80),
                x: Math.round(rect.left), y: Math.round(rect.top),
                w: Math.round(rect.width), h: Math.round(rect.height),
                selector: this.cssPath(el),
                path: this.elementPath(el),
            };
        },

        cssPath(el) {
            if (!el || el.nodeType !== 1) return '';
            if (el.id) return '#' + CSS.escape(el.id);
            const parts = [];
            while (el && el.nodeType === 1 && parts.length < 6) {
                let p = el.tagName.toLowerCase();
                if (el.className && typeof el.className === 'string') {
                    const cls = el.className.split(/\s+/).filter(Boolean).slice(0, 2).map(c => '.' + CSS.escape(c)).join('');
                    p += cls;
                }
                const parent = el.parentElement;
                if (parent) {
                    const same = Array.from(parent.children).filter(c => c.tagName === el.tagName);
                    if (same.length > 1) p += `:nth-of-type(${same.indexOf(el) + 1})`;
                }
                parts.unshift(p);
                el = el.parentElement;
            }
            return parts.join(' > ');
        },

        elementPath(el) {
            const path = [];
            let cur = el;
            while (cur && cur.nodeType === 1 && path.length < 5) {
                path.unshift(cur.tagName.toLowerCase());
                cur = cur.parentElement;
            }
            return path.join(' > ');
        },

        async copyInfo() {
            if (!this.pinned) return;
            const text = `[${this.pinned.tag}] ${this.pinned.text || '(no text)'}\n`
                       + `  selector: ${this.pinned.selector}\n`
                       + `  classes:  ${this.pinned.classes}\n`
                       + `  box:      ${this.pinned.w}x${this.pinned.h} @ ${this.pinned.x},${this.pinned.y}`;
            await navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => this.copied = false, 1500);
        },

        appendToCommand() {
            if (!this.pinned) return;
            const line = `- ${this.pinned.selector} (${this.pinned.tag}): `;
            this.commandBox = this.commandBox ? (this.commandBox + '\n' + line) : line;
            this.collectedHighlights.push({
                selector_path: this.pinned.selector,
                tag_name:      this.pinned.tag,
                classes:       this.pinned.classes || null,
                text_snippet:  this.pinned.text || null,
                bbox_x: this.pinned.x, bbox_y: this.pinned.y,
                bbox_w: this.pinned.w, bbox_h: this.pinned.h,
            });
        },

        highlightsToSubmit() {
            return this.collectedHighlights;
        },

        async copyCommand() {
            if (!this.commandBox) return;
            await navigator.clipboard.writeText(this.commandBox);
        },

        decide(d) {
            if (d === 'ng' && !confirm('NG로 판정합니다. 미스 입력 화면으로 이동합니다.\n계속할까요?')) return;
            if (d === 'ok' && !confirm('OK로 판정하여 작업을 완료합니다. 계속할까요?')) return;
            this.$refs.decisionInput.value = d;
            this.$refs.decisionInput.form.submit();
        },

        onDecideSubmit() { /* placeholder */ },
    };
}
</script>
@endsection
