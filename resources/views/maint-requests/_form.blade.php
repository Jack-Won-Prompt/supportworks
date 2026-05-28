@php $isEmbed = $isEmbed ?? false; @endphp

{{-- Quill rich editor (SR 상세 내용 리치 에디터 + 이미지 paste) --}}
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
{{-- marked.js — 웍스 요약 탭의 markdown 렌더링용 --}}
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
    .maint-sticky-bar { position: sticky; top: 0; z-index: 30; }
    .maint-sticky-bar.is-stuck { box-shadow: 0 4px 12px -4px rgba(0,0,0,.08); }
    /* 웍스 요약 markdown 렌더링 스타일 */
    .maint-md-render h1, .maint-md-render h2, .maint-md-render h3, .maint-md-render h4 { font-weight:700; color:#1e293b; margin-top:0.9em; margin-bottom:0.4em; line-height:1.3; }
    .maint-md-render h1:first-child, .maint-md-render h2:first-child, .maint-md-render h3:first-child, .maint-md-render h4:first-child { margin-top:0; }
    .maint-md-render h1 { font-size:1.05rem; }
    .maint-md-render h2 { font-size:1rem; }
    .maint-md-render h3 { font-size:0.95rem; }
    .maint-md-render h4 { font-size:0.9rem; color:#334155; }
    .maint-md-render p { margin-bottom:0.55em; }
    .maint-md-render p:last-child { margin-bottom:0; }
    .maint-md-render ul, .maint-md-render ol { padding-left:1.25em; margin-bottom:0.55em; }
    .maint-md-render li { margin-bottom:0.2em; }
    .maint-md-render strong { font-weight:700; color:#1e293b; }
    .maint-md-render em { font-style:italic; }
    .maint-md-render code { background:#f1f5f9; padding:1px 5px; border-radius:4px; font-size:0.85em; color:#0f172a; }
    .maint-md-render pre { background:#0f172a; color:#e2e8f0; padding:10px 12px; border-radius:6px; font-size:.78rem; overflow-x:auto; margin:0.6em 0; }
    .maint-md-render pre code { background:transparent; padding:0; color:inherit; }
    .maint-md-render blockquote { border-left:3px solid #c7d2fe; padding:0.2em 0.8em; color:#475569; background:#f8fafc; margin:0.6em 0; border-radius:0 4px 4px 0; }
    .maint-md-render a { color:#4338ca; text-decoration:underline; }
    .maint-md-render hr { border:0; border-top:1px solid #e2e8f0; margin:0.8em 0; }
    /* SR 상세 내용 리치 에디터 */
    .sr-quill { border:1px solid var(--color-border-default); border-radius:8px; transition:border-color .15s; }
    .sr-quill.focused { border-color:#6366f1; }
    .sr-quill .ql-toolbar { border:none; border-bottom:1px solid var(--color-border-default); padding:6px 10px; background:#f8fafc; border-radius:8px 8px 0 0; }
    .sr-quill .ql-container { border:none; font-family:inherit; }
    .sr-quill .ql-editor { min-height:220px; max-height:480px; overflow-y:auto; padding:12px 14px; font-size:13.5px; color:var(--color-text-primary); line-height:1.6; }
    .sr-quill .ql-editor.ql-blank::before { font-style:normal; color:var(--color-text-placeholder); }
    .sr-quill .ql-editor img { max-width:100%; height:auto; border-radius:6px; margin:6px 0; cursor:pointer; }
    .sr-quill .ql-editor img.sr-img-selected { outline:2px solid var(--t500); outline-offset:1px; }

    /* 이미지 리사이즈/뷰어/이미지 주석은 표준 partial(_quill-image-resize)이 담당 */

    /* 이미지 라이트박스 */
    #sr-img-lightbox { display:none; position:fixed; inset:0; z-index:10500; background:rgba(0,0,0,.86); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:40px; }
    #sr-img-lightbox.is-open { display:flex; }
    #sr-img-lightbox img { max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 20px 60px rgba(0,0,0,.5); }
    #sr-img-lightbox-close { position:absolute; top:18px; right:18px; width:40px; height:40px; border-radius:50%; background:rgba(255,255,255,.15); color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:18px; transition:background .12s; }
    #sr-img-lightbox-close:hover { background:rgba(255,255,255,.28); }
    .sr-quill .ql-toolbar .ql-stroke { stroke:#64748b; }
    .sr-quill .ql-toolbar .ql-fill { fill:#64748b; }
    .sr-quill .ql-toolbar button:hover .ql-stroke, .sr-quill .ql-toolbar button.ql-active .ql-stroke { stroke:#6366f1; }
    .sr-quill .ql-toolbar button:hover .ql-fill, .sr-quill .ql-toolbar button.ql-active .ql-fill { fill:#6366f1; }
</style>

<script>
    (function(){
        var observed = false;
        function init(){
            if (observed) return;
            var bars = document.querySelectorAll('.maint-sticky-bar');
            if (!bars.length) return;
            observed = true;
            bars.forEach(function(bar){
                // 스크롤되어 상단에 닿으면 그림자 표시
                var sentinel = document.createElement('div');
                sentinel.style.cssText = 'position:absolute;left:0;right:0;top:-1px;height:1px;';
                bar.parentNode.insertBefore(sentinel, bar);
                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function(entries){
                        bar.classList.toggle('is-stuck', !entries[0].isIntersecting);
                    }, { threshold: 0 });
                    io.observe(sentinel);
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>

{{-- 세션 플래시는 전역 토스트(window.appToast)로 표시됨 — 페이지 인라인 배너 제거 --}}

{{-- AI 사전 검토 패널 (등록 직후 요청자 확인 단계) --}}
@if($r->status === 'ai_review')
    @include('maint-requests._ai-review')
@elseif($r->ai_review_status === 'confirmed' && (is_array($r->ai_review_questions) && !empty($r->ai_review_questions)))
{{-- 확인된 SR의 AI Q&A 이력 (담당자가 참고할 수 있도록 작게 표시) --}}
<details class="bg-white border border-gray-200 rounded-lg p-3 mb-3 text-sm">
    <summary class="cursor-pointer text-gray-600 font-medium">AI 사전 검토 Q&A ({{ count($r->ai_review_questions) }}건)
        @if($r->ai_review_difficulty)<span class="text-xs text-gray-400 ml-2">난이도 {{ $r->ai_review_difficulty }}/5</span>@endif
        @if($r->ai_review_effort)<span class="text-xs text-gray-400 ml-1">· 예상 {{ $r->ai_review_effort }}</span>@endif
    </summary>
    <div class="mt-2 space-y-1.5">
        @foreach($r->ai_review_questions as $i => $qa)
            <div class="text-xs">
                <div class="text-gray-700">Q{{ $i + 1 }}. {{ $qa['q'] ?? '' }}</div>
                <div class="text-gray-500 pl-3">A. {{ trim((string) ($qa['a'] ?? '')) !== '' ? $qa['a'] : '(미답변)' }}</div>
            </div>
        @endforeach
    </div>
</details>
@endif

<div class="grid grid-cols-12 gap-5">

    {{-- 좌측: 요청 본문 (편집 폼) --}}
    <div class="col-span-8">
        <form method="POST" action="{{ route('maint-requests.update', $r) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5" enctype="multipart/form-data">
            @csrf @method('PUT')
            @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif

            <div class="maint-sticky-bar -mt-6 -mx-6 px-6 py-3 bg-white border-b border-gray-100 rounded-t-xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-xs text-gray-500">#{{ $r->id }}</span>
                    @if($r->excel_no)<span class="text-xs text-gray-400">· 원본 No {{ $r->excel_no }}</span>@endif
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium" style="{{ $priorityStyles[$r->priority] ?? '' }}">{{ $priorityLabels[$r->priority] }}</span>
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium" style="{{ $statusStyles[$r->status] ?? '' }}">{{ $statusLabels[$r->status] }}</span>
                </div>
                <button type="submit" class="shrink-0 px-4 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">저장</button>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-8">
                    <label class="block text-sm font-medium text-gray-700 mb-1">메뉴</label>
                    <input list="menu-list" name="menu_name" value="{{ old('menu_name', $r->menu?->name) }}"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <datalist id="menu-list">
                        @foreach($menus as $m)<option value="{{ $m->name }}"></option>@endforeach
                    </datalist>
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">우선순위</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                        @foreach($priorityLabels as $k => $v)
                            <option value="{{ $k }}" {{ old('priority', $r->priority)===$k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">요약</label>
                <input type="text" name="summary" value="{{ old('summary', $r->summary) }}" required maxlength="500"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>

            <div x-data="(() => {
                    const base = srSummary({
                        initialSummary: @js(old('ai_summary', $r->ai_summary ?? '')),
                        initialContextIds: @js(old('ai_summary_context_ids', is_array($r->ai_summary_context_ids ?? null) ? $r->ai_summary_context_ids : [])),
                        initialClassification: @js($r->ai_classification ?? ''),
                        endpoint: '{{ route('maint-requests.works-summary') }}',
                        srId: {{ $r->id }},
                        csrf: '{{ csrf_token() }}',
                    });
                    return {
                        ...base,
                        activeTab: @js(request('tab') === 'detail' ? 'detail' : (request('tab') === 'summary' || !empty($r->ai_summary) ? 'summary' : 'detail')),
                        autoSaveEndpoint: @js(route('maint-requests.ai-summary', $r)),
                        autoSaveCsrf: @js(csrf_token()),
                        // 웍스 요약 버튼 클릭 핸들러:
                        //   - 이미 저장된 요약 있으면 그대로 노출 (재생성 안 함)
                        //   - 없으면 generate() 후 즉시 백엔드로 자동 저장
                        async runWorksSummary() {
                            if (this.summary && (this.summary || '').trim() !== '') {
                                this.activeTab = 'summary';
                                return; // 저장된 내용 그대로
                            }
                            await this.generate();
                            if (this.summary) {
                                try {
                                    const fd = new FormData();
                                    fd.append('_token',  this.autoSaveCsrf);
                                    fd.append('_method', 'PATCH');
                                    fd.append('ai_summary', this.summary);
                                    if (this.classification) fd.append('ai_classification', this.classification);
                                    (this.contextIds || []).forEach((v, i) => fd.append('ai_summary_context_ids[' + i + ']', v));
                                    await fetch(this.autoSaveEndpoint, {
                                        method: 'POST',
                                        body: fd,
                                        credentials: 'same-origin',
                                        headers: { 'X-CSRF-TOKEN': this.autoSaveCsrf, 'Accept': 'application/json' },
                                    });
                                } catch (e) {
                                    console.warn('웍스 요약 자동 저장 실패:', e);
                                }
                            }
                        },
                    };
                 })()">
                {{-- 탭 헤더 — 상세 내용 / 웍스 요약 --}}
                <div class="flex items-end justify-between border-b border-gray-200 mb-2">
                    <div class="flex gap-1">
                        <button type="button" @click="activeTab='detail'"
                                :class="activeTab==='detail' ? 'border-indigo-500 text-indigo-700 bg-indigo-50/50' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px rounded-t-md transition-colors">
                            상세 내용
                        </button>
                        <button type="button" @click="activeTab='summary'"
                                :class="activeTab==='summary' ? 'border-indigo-500 text-indigo-700 bg-indigo-50/50' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px rounded-t-md transition-colors inline-flex items-center gap-1.5">
                            웍스 요약
                            <span x-show="summary" x-cloak class="text-[10px] px-1.5 py-0.5 bg-indigo-100 text-indigo-700 rounded-full">✓</span>
                        </button>
                    </div>
                </div>

                {{-- 상세 내용 탭 — Quill 리치 에디터 (x-show 로 숨기되 DOM 유지: 에디터 상태 보존) --}}
                <div x-show="activeTab==='detail'">
                    <div class="sr-quill" id="sr-quill-wrap">
                        <div id="sr-quill-editor"></div>
                    </div>
                </div>

                {{-- 웍스 요약 탭 — 등록 시 모달에서 생성된 요약을 표시. 매니저 이상은 우측 버튼으로 (재)생성 가능. --}}
                @php
                    $__u = auth()->user();
                    $__canRunWorks = $__u && ($__u->isAdmin() || $__u->role === 'manager');
                @endphp
                <div x-show="activeTab==='summary'" x-cloak>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-gray-500" x-show="summary && contextIds.length > 0" x-cloak>참고 SR <span x-text="contextIds.length"></span>건</span>
                        <div class="flex items-center gap-2 ml-auto">
                            <span class="text-xs text-gray-400" x-show="summary" x-cloak>등록 시 생성된 웍스 요약</span>
                            @if($__canRunWorks)
                                {{-- 웍스 요약 (관리자) — 저장된 내용 있으면 그대로 노출, 없으면 생성 후 자동 저장 --}}
                                <button type="button" @click="runWorksSummary()" :disabled="loading"
                                        :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-50 hover:border-indigo-300'"
                                        :title="summary ? '저장된 웍스 요약이 있습니다' : '웍스 요약 생성 + 자동 저장'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md text-indigo-700 bg-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-show="!loading">웍스 요약</span>
                                    <span x-show="loading" x-cloak>생성 중...</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- 빈 상태 --}}
                    <div x-show="!summary && !loading" x-cloak class="border border-dashed border-gray-200 rounded-xl py-12 text-center text-sm text-gray-400">
                        @if($__canRunWorks)
                            등록 시 생성된 웍스 요약이 없습니다. 우측 상단 <b>웍스 요약</b> 버튼으로 생성하세요.
                        @else
                            이 SR 은 등록 시 웍스 요약이 생성되지 않았습니다.
                        @endif
                    </div>

                    {{-- 요약 본문 (markdown 렌더링 · 읽기 전용) --}}
                    <div x-show="summary" x-cloak class="bg-indigo-50/40 border border-indigo-200 rounded-xl p-4">
                        <div class="maint-md-render text-sm leading-relaxed text-gray-800"
                             x-html="(window.marked && window.marked.parse) ? window.marked.parse(summary || '') : (summary || '').replace(/\n/g, '<br>')"></div>
                    </div>
                </div>

                {{-- 폼 제출용 hidden 필드 (탭과 무관하게 항상 DOM 에 존재) --}}
                <input type="hidden" name="content" id="sr-content-input" value="{{ old('content', $r->content) }}">
                <template id="sr-content-initial">{{ old('content', $r->content) }}</template>
                <input type="hidden" name="ai_summary" :value="summary || ''">
                <input type="hidden" name="ai_summary_context_ids" :value="contextIds.length > 0 ? JSON.stringify(contextIds) : ''">
                <input type="hidden" name="ai_classification" :value="classification || ''">
            </div>

            {{-- 첨부파일 — 기존 명세 (다운로드만) + 추가 업로드 (최대 10개, 각 10MB. 등록 후 삭제 불가) --}}
            @php $atSlotsLeft = 10 - $r->attachments->count(); @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">첨부파일
                    <span class="text-xs text-gray-400 font-normal">({{ $r->attachments->count() }}/10 · 등록 후 삭제 불가)</span>
                </label>
                @if($r->attachments->isNotEmpty())
                    <ul class="space-y-1 mb-2">
                        @foreach($r->attachments as $att)
                            <li class="flex items-center justify-between gap-2 px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded-md text-xs">
                                <a href="{{ URL::signedRoute('maint-requests.attachments.download', ['attachment' => $att->id], now()->addMinutes(15)) }}"
                                   class="flex items-center gap-2 flex-1 min-w-0 text-gray-700 hover:text-indigo-700">
                                    <svg class="w-3.5 h-3.5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="truncate" title="{{ $att->original_name }}">{{ $att->original_name }}</span>
                                    <span class="text-gray-400 flex-shrink-0">{{ $att->formatted_size }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if($atSlotsLeft > 0)
                    <div class="flex items-center gap-2" x-data="srEditAttach({ max: {{ $atSlotsLeft }} })">
                        <button type="button" @click="$refs.fi.click()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-md text-xs text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            파일 추가
                        </button>
                        <span class="text-xs text-gray-500">남은 슬롯: <span x-text="max - picked.length"></span>/{{ $atSlotsLeft }}</span>
                        <input type="file" name="attachments[]" multiple x-ref="fi" style="display:none" @change="onPick($event)">
                        <ul class="ml-2 text-[11px] text-indigo-700 space-y-0.5 flex-1">
                            <template x-for="(f, i) in picked" :key="i">
                                <li class="flex items-center gap-1.5">
                                    <span x-text="f.name"></span>
                                    <span class="text-gray-400" x-text="'(' + fmt(f.size) + ')'"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                @else
                    <div class="text-[11px] text-gray-400">첨부파일 슬롯이 가득 찼습니다 (최대 10개).</div>
                @endif
            </div>

            @php
                $companyLabel = $r->companyGroup?->name ?: '콜로';
                $currentCategory = old('category', $r->category);
                $currentColoName = old('colo_user_name', $r->coloUser?->name);
                $currentDevName  = old('assignee_name', $r->assignee?->name ?: $r->assignee_raw);
            @endphp
            @php
                // 링크더랩 필드 편집 권한 — 관리자 또는 링크더랩 사용자만 (구분, 링크더랩 담당자, 완료 예정일, 상태)
                $canEditCategory = auth()->user()->isAdmin()
                    || (int) auth()->user()->company_group_id === (int) (\App\Models\CompanyGroup::where('name', '링크더랩')->value('id'));
                $canEditLinkthelabFields = $canEditCategory;
            @endphp
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">구분</label>
                    @if($canEditCategory)
                        <select name="category"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                            <option value="">— 선택 —</option>
                            @foreach(($categories ?? []) as $cat)
                                <option value="{{ $cat }}" @selected($currentCategory === $cat)>{{ $cat }}</option>
                            @endforeach
                            @if($currentCategory && !($categories ?? collect())->contains($currentCategory))
                                <option value="{{ $currentCategory }}" selected>{{ $currentCategory }}</option>
                            @endif
                        </select>
                    @else
                        <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">{{ $currentCategory ?: '-' }}</div>
                        <input type="hidden" name="category" value="{{ $currentCategory }}">
                    @endif
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $companyLabel }} 담당자</label>
                    <select name="colo_user_name"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                        <option value="">— 선택 —</option>
                        @foreach($coloUsers as $cu)
                            <option value="{{ $cu->name }}" @selected($currentColoName === $cu->name)>{{ $cu->name }}</option>
                        @endforeach
                        @if($currentColoName && !$coloUsers->pluck('name')->contains($currentColoName))
                            <option value="{{ $currentColoName }}" selected>{{ $currentColoName }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">링크더랩 담당자</label>
                    @if($canEditLinkthelabFields)
                        <select name="assignee_name"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                            <option value="">— 선택 —</option>
                            @foreach($devUsers as $u)
                                <option value="{{ $u->name }}" @selected($currentDevName === $u->name)>{{ $u->name }}</option>
                            @endforeach
                            @if($currentDevName && !$devUsers->pluck('name')->contains($currentDevName))
                                <option value="{{ $currentDevName }}" selected>{{ $currentDevName }}</option>
                            @endif
                        </select>
                    @else
                        <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">{{ $currentDevName ?: '-' }}</div>
                        <input type="hidden" name="assignee_name" value="{{ $currentDevName }}">
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">요청일</label>
                    @php $reqDt = $r->request_date ?? $r->created_at; @endphp
                    <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">
                        {{ $reqDt ? $reqDt->format('Y-m-d H:i') : '-' }}
                    </div>
                    <input type="hidden" name="request_date" value="{{ optional($r->request_date)->format('Y-m-d') }}">
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">완료 예정일</label>
                    @if($canEditCategory)
                        {{-- 관리자 + 링크더랩 사용자: 날짜 선택 --}}
                        <input type="date" name="eta" value="{{ old('eta', optional($r->eta)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    @else
                        {{-- 일반 사용자: 읽기 전용 텍스트 --}}
                        <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">
                            {{ optional($r->eta)->format('Y-m-d') ?: '-' }}
                        </div>
                        <input type="hidden" name="eta" value="{{ optional($r->eta)->format('Y-m-d') }}">
                    @endif
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                    @if($canEditLinkthelabFields)
                        <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                            @foreach($statusLabels as $k => $v)
                                <option value="{{ $k }}" {{ old('status', $r->status)===$k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">{{ $statusLabels[$r->status] ?? $r->status }}</div>
                        <input type="hidden" name="status" value="{{ $r->status }}">
                    @endif
                </div>
            </div>

            {{-- 난이도 점수 (1~5) — 관리자/SR 담당자만 편집 --}}
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">난이도 점수
                        <span class="text-xs text-gray-400 font-normal">(1=쉬움 ~ 5=매우 어려움)</span>
                    </label>
                    @if($canEditLinkthelabFields)
                        <select name="difficulty_score" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                            <option value="" {{ old('difficulty_score', $r->difficulty_score) ? '' : 'selected' }}>— 미매핑 —</option>
                            <option value="1" {{ (string)old('difficulty_score', $r->difficulty_score)==='1' ? 'selected' : '' }}>1 · 매우 낮음 (표준 CRUD)</option>
                            <option value="2" {{ (string)old('difficulty_score', $r->difficulty_score)==='2' ? 'selected' : '' }}>2 · 낮음</option>
                            <option value="3" {{ (string)old('difficulty_score', $r->difficulty_score)==='3' ? 'selected' : '' }}>3 · 중간 (다중 모델·트랜잭션)</option>
                            <option value="4" {{ (string)old('difficulty_score', $r->difficulty_score)==='4' ? 'selected' : '' }}>4 · 높음 (도메인 룰·알고리즘)</option>
                            <option value="5" {{ (string)old('difficulty_score', $r->difficulty_score)==='5' ? 'selected' : '' }}>5 · 매우 높음 (외부 API·규제)</option>
                        </select>
                    @else
                        <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">{{ $r->difficulty_score ?? '미매핑' }}</div>
                    @endif
                </div>
            </div>

            @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <div class="text-xs text-gray-400">
                    @if($r->completed_at)완료 {{ $r->completed_at->format('Y-m-d H:i') }} · @endif
                    수정 {{ $r->updated_at->format('Y-m-d H:i') }}
                </div>
            </div>
        </form>

        {{-- 삭제 — SR 담당자(또는 관리자) + 상태가 '요청' 일 때만 노출 --}}
        @php
            $__u = auth()->user();
            $__canDeleteSr = $__u && ($__u->isAdmin() || (bool) ($__u->is_sr_agent ?? false));
        @endphp
        @if($__canDeleteSr && $r->status === 'requested')
            <form method="POST" action="{{ route('maint-requests.destroy', $r) }}" class="mt-3 text-right">
                @csrf @method('DELETE')
                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                <button type="submit" data-confirm="정말 삭제하시겠습니까?" class="text-xs text-red-500 hover:text-red-700">요청 삭제</button>
            </form>
        @endif
    </div>

    {{-- 우측: 비고 --}}
    <div class="col-span-4 space-y-4">

        @php
            // 비고는 1단계 트리: 최상위(parent_id=null) 만 렌더링하고 각 아래에 답글을 indent 로 표시.
            $allNotes      = $r->notes;
            $topColoNotes  = $allNotes->where('note_type','colo')->whereNull('parent_id')->sortBy('id');
            $topLinkNotes  = $allNotes->where('note_type','link')->whereNull('parent_id')->sortBy('id');
            $repliesByPid  = $allNotes->whereNotNull('parent_id')->groupBy('parent_id');
        @endphp

        {{-- 회사 측 비고 (SR 대상 회사명) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ $companyLabel }} 비고</h3>
                <span class="text-xs text-gray-400">{{ $allNotes->where('note_type','colo')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($topColoNotes as $n)
                    <div class="group bg-gray-50 rounded-lg p-2.5 text-sm text-gray-700" x-data="{ replyOpen: false }">
                        <div class="whitespace-pre-wrap break-words">{{ $n->body }}</div>
                        <div class="flex items-center justify-between mt-1 text-xs text-gray-400">
                            <span>{{ $n->created_at->format('Y-m-d H:i') }}</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="replyOpen = !replyOpen"
                                        class="text-gray-500 hover:text-indigo-600">
                                    <span x-show="!replyOpen">답글</span>
                                    <span x-show="replyOpen" x-cloak>닫기</span>
                                </button>
                                <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $n]) }}" class="opacity-0 group-hover:opacity-100"
                                      onsubmit="return confirm('답글이 있으면 함께 삭제됩니다. 진행할까요?');">
                                    @csrf @method('DELETE')
                                    @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                    <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                                </form>
                            </div>
                        </div>

                        {{-- 답글 목록 --}}
                        @php $replies = $repliesByPid->get($n->id) ?? collect(); @endphp
                        @if($replies->count() > 0)
                        <div class="mt-2 ml-3 pl-3 border-l-2 border-gray-300 space-y-1.5">
                            @foreach($replies->sortBy('id') as $reply)
                                <div class="group/reply bg-white rounded p-2 text-xs text-gray-700 border border-gray-100">
                                    <div class="whitespace-pre-wrap break-words">{{ $reply->body }}</div>
                                    <div class="flex items-center justify-between mt-1 text-[10px] text-gray-400">
                                        <span>↳ {{ $reply->created_at->format('Y-m-d H:i') }}</span>
                                        <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $reply]) }}" class="opacity-0 group-hover/reply:opacity-100"
                                              onsubmit="return confirm('삭제하시겠습니까?');">
                                            @csrf @method('DELETE')
                                            @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                            <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- 답글 작성 폼 (토글) --}}
                        <form x-show="replyOpen" x-cloak method="POST" action="{{ route('maint-requests.notes.store', $r) }}" class="mt-2 ml-3 pl-3 border-l-2 border-gray-300 space-y-1.5">
                            @csrf
                            @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                            <input type="hidden" name="note_type" value="colo">
                            <input type="hidden" name="parent_id" value="{{ $n->id }}">
                            <textarea name="body" rows="2" required placeholder="답글 작성"
                                      class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                            <button type="submit" class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-xs font-medium hover:bg-gray-300">답글 등록</button>
                        </form>
                    </div>
                @empty
                    <div class="text-xs text-gray-400 py-2">비고가 없습니다.</div>
                @endforelse
            </div>
            <form method="POST" action="{{ route('maint-requests.notes.store', $r) }}" class="space-y-2">
                @csrf
                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                <input type="hidden" name="note_type" value="colo">
                <textarea name="body" rows="2" required placeholder="{{ $companyLabel }} 측 비고 작성"
                          class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                <button type="submit" class="w-full px-3 py-1.5 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">추가</button>
            </form>
        </div>

        {{-- 링크 비고 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">링크더랩 비고</h3>
                <span class="text-xs text-gray-400">{{ $allNotes->where('note_type','link')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($topLinkNotes as $n)
                    <div class="group bg-indigo-50/50 rounded-lg p-2.5 text-sm text-gray-700" x-data="{ replyOpen: false }">
                        <div class="whitespace-pre-wrap break-words">{{ $n->body }}</div>
                        <div class="flex items-center justify-between mt-1 text-xs text-gray-400">
                            <span>{{ $n->created_at->format('Y-m-d H:i') }}</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="replyOpen = !replyOpen"
                                        class="text-gray-500 hover:text-indigo-600">
                                    <span x-show="!replyOpen">답글</span>
                                    <span x-show="replyOpen" x-cloak>닫기</span>
                                </button>
                                <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $n]) }}" class="opacity-0 group-hover:opacity-100"
                                      onsubmit="return confirm('답글이 있으면 함께 삭제됩니다. 진행할까요?');">
                                    @csrf @method('DELETE')
                                    @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                    <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                                </form>
                            </div>
                        </div>

                        @php $replies = $repliesByPid->get($n->id) ?? collect(); @endphp
                        @if($replies->count() > 0)
                        <div class="mt-2 ml-3 pl-3 border-l-2 border-indigo-200 space-y-1.5">
                            @foreach($replies->sortBy('id') as $reply)
                                <div class="group/reply bg-white rounded p-2 text-xs text-gray-700 border border-indigo-100">
                                    <div class="whitespace-pre-wrap break-words">{{ $reply->body }}</div>
                                    <div class="flex items-center justify-between mt-1 text-[10px] text-gray-400">
                                        <span>↳ {{ $reply->created_at->format('Y-m-d H:i') }}</span>
                                        <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $reply]) }}" class="opacity-0 group-hover/reply:opacity-100"
                                              onsubmit="return confirm('삭제하시겠습니까?');">
                                            @csrf @method('DELETE')
                                            @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                            <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <form x-show="replyOpen" x-cloak method="POST" action="{{ route('maint-requests.notes.store', $r) }}" class="mt-2 ml-3 pl-3 border-l-2 border-indigo-200 space-y-1.5">
                            @csrf
                            @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                            <input type="hidden" name="note_type" value="link">
                            <input type="hidden" name="parent_id" value="{{ $n->id }}">
                            <textarea name="body" rows="2" required placeholder="답글 작성"
                                      class="w-full px-2 py-1.5 border border-indigo-200 rounded text-xs"></textarea>
                            <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700">답글 등록</button>
                        </form>
                    </div>
                @empty
                    <div class="text-xs text-gray-400 py-2">비고가 없습니다.</div>
                @endforelse
            </div>
            <form method="POST" action="{{ route('maint-requests.notes.store', $r) }}" class="space-y-2">
                @csrf
                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                <input type="hidden" name="note_type" value="link">
                <textarea name="body" rows="2" required placeholder="링크더랩 측 비고 작성"
                          class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700">추가</button>
            </form>
        </div>

        @php
            // 추가 개발(유상) 영역 노출 권한 — 관리자 OR SR 담당자(is_sr_agent)
            $canSeePaidDev = auth()->user()->isAdmin() || (bool) (auth()->user()->is_sr_agent ?? false);

            // 웍스 요약 판단 (등록 시 AI 분류 결과)
            $clsMap = [
                'free'    => ['label' => '무상',           'sub' => '에러 / 데이터 확인',                 'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0'],
                'paid'    => ['label' => '유상 추가 개발', 'sub' => '기능 추가 / 프로세스 변경 / 추가 기능', 'bg' => '#fef3c7', 'fg' => '#92400e', 'border' => '#fde68a'],
                'discuss' => ['label' => '논의 필요',      'sub' => '판단 보류 — 추가 협의 권장',         'bg' => '#eef2ff', 'fg' => '#3730a3', 'border' => '#c7d2fe'],
            ];
            $clsInfo = $clsMap[$r->ai_classification] ?? null;
        @endphp
        @if($canSeePaidDev)
        {{-- 웍스 요약 판단 (추가 개발 상단 — 관리자/SR 담당자만 편집 가능,
             웍스 요약 (재)생성 시 sr-ai-updated 이벤트로 cls/saved 반응형 갱신) --}}
        <div x-data='@json([
                'srId'     => $r->id,
                'cls'      => $r->ai_classification ?? '',
                'saved'    => $r->ai_classification ?? '',
                'saving'   => false,
                'flash'    => '',
                'map'      => $clsMap,
                'endpoint' => route('maint-requests.classification', $r),
             ])'
             @sr-ai-updated.window="if ($event.detail && map[$event.detail.classification]) { cls = $event.detail.classification; saved = cls; }"
             x-cloak>
            <div class="bg-white rounded-xl border border-gray-200 p-3" :style="`border-left:3px solid ${(map[cls]||map[saved]||{}).border || '#e5e7eb'}`">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">웍스 요약 판단</div>
                    <span x-show="flash" x-cloak class="text-[10px] text-emerald-600" x-text="flash"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- 현재 라벨 배지 (cls 가 비어 있으면 미분류 표기) --}}
                    <template x-if="map[cls]">
                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold rounded-md"
                              :style="`background:${(map[cls]||{}).bg};color:${(map[cls]||{}).fg};`"
                              x-text="(map[cls]||{}).label"></span>
                    </template>
                    <template x-if="!map[cls]">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-500">미분류</span>
                    </template>
                    <span class="text-xs text-gray-500" x-text="(map[cls]||{}).sub || '직접 선택해 저장하세요'"></span>

                    {{-- 편집 컨트롤 --}}
                    <div class="ml-auto flex items-center gap-1.5">
                        <select x-model="cls"
                                class="px-2 py-1 border border-gray-200 rounded text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">선택…</option>
                            <option value="free">무상</option>
                            <option value="paid">유상 추가 개발</option>
                            <option value="discuss">논의 필요</option>
                        </select>
                        <button type="button"
                                @click="
                                    if (!cls || cls === saved || saving) return;
                                    saving = true; flash = '';
                                    fetch(endpoint, {
                                        method: 'PATCH',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        body: JSON.stringify({ ai_classification: cls }),
                                    })
                                    .then(async r => {
                                        if (!r.ok) throw new Error('HTTP ' + r.status);
                                        return r.json();
                                    })
                                    .then(j => {
                                        saved = j.ai_classification || cls;
                                        cls   = saved;
                                        flash = '저장됨';
                                        setTimeout(() => flash = '', 1500);
                                        // 부모창(리스트) 갱신 예약 — 모달 닫힐 때 해당 행 배지만 교체
                                        try {
                                            if (window.parent && window.parent !== window) {
                                                window.parent.maintQueueRowClsUpdate &&
                                                window.parent.maintQueueRowClsUpdate(srId, saved, j.category || null);
                                            }
                                        } catch (e) {}
                                        // 같은 폼 안의 다른 분류 의존 UI 도 즉시 반영 (paid 시 category 자동매핑 등)
                                        window.dispatchEvent(new CustomEvent('sr-ai-updated', { detail: { classification: saved } }));
                                        if (j.category) {
                                            const catInput = document.querySelector('input[name=category]');
                                            if (catInput && catInput.value !== j.category) catInput.value = j.category;
                                        }
                                    })
                                    .catch(err => { flash = '저장 실패'; console.error(err); })
                                    .finally(() => { saving = false; });
                                "
                                :disabled="!cls || cls === saved || saving"
                                class="px-2.5 py-1 text-xs font-semibold rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                            <span x-show="!saving">저장</span>
                            <span x-show="saving" x-cloak>저장 중…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($canSeePaidDev)
        {{-- 추가 개발 (유상) — 관리자 + SR 담당자만 노출 --}}
        @php
            $paidDevInit = json_encode([
                'enabled'  => (bool) $r->paid_dev_enabled,
                'days'     => $r->paid_dev_days ?? '',
                'cost'     => $r->paid_dev_cost ?? '',
                'desc'     => $r->paid_dev_description ?? '',
                'endpoint' => route('maint-requests.send-to-manager', $r),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT);
        @endphp
        <div class="bg-white rounded-xl border border-amber-200 p-4"
             x-data='paidDevForm({!! $paidDevInit !!})'>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-amber-800 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    추가 개발 (유상)
                </h3>
                @if($r->paid_dev_sent_at)
                    <span class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-700 rounded">매니저 전송: {{ $r->paid_dev_sent_at->format('m-d H:i') }}</span>
                @endif
            </div>

            {{-- 유상 여부 체크 --}}
            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                <input type="checkbox" name="paid_dev_enabled" value="1" x-model="enabled"
                       class="w-4 h-4 accent-amber-500 cursor-pointer">
                <span class="text-xs font-medium text-gray-700">유상 처리 (체크 시 입력 가능)</span>
            </label>

            <div x-show="enabled" x-cloak class="space-y-2.5">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1">걸리는 일(Day)</label>
                        <input type="number" name="paid_dev_days" min="0" step="1" x-model.number="days"
                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1 flex items-center justify-between">
                            <span>비용 (₩)</span>
                            <span class="text-[10px] text-amber-600">일당 ₩<span x-text="dailyRate.toLocaleString()"></span> 자동계산</span>
                        </label>
                        <input type="number" name="paid_dev_cost" min="0" step="1000" x-model.number="cost"
                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] text-gray-500 mb-1">추가 개발 대상 설명</label>
                    <textarea name="paid_dev_description" rows="3" x-model="desc"
                              placeholder="개발 범위·요구사항·산출물 등 매니저가 검토할 정보를 작성"
                              class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                </div>
                <button type="button" @click="sendToManager()" :disabled="sending"
                        class="w-full px-3 py-2 bg-amber-500 text-white rounded text-xs font-semibold hover:bg-amber-600 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-1.5">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span x-show="!sending">매니저 전송 (이메일 + 상태 → '추가 개발')</span>
                    <span x-show="sending" x-cloak>전송 중...</span>
                </button>
            </div>
            <div x-show="!enabled" x-cloak class="text-[11px] text-gray-400 py-1.5">
                유상 처리가 필요하면 위 체크박스를 활성화하세요.
            </div>
        </div>
        @endif

        {{-- 진행/원본 표시 --}}
        @if($r->progress_raw || $r->colo_check_raw || $r->assignee_raw)
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-xs text-gray-500 space-y-1">
            @if($r->progress_raw)<div><span class="font-medium text-gray-600">진행사항 원본:</span> {{ $r->progress_raw }}</div>@endif
            @if($r->colo_check_raw)<div><span class="font-medium text-gray-600">{{ $companyLabel }} 확인 원본:</span> {{ $r->colo_check_raw }}</div>@endif
            @if($r->assignee_raw)<div><span class="font-medium text-gray-600">담당자 원본:</span> {{ $r->assignee_raw }}</div>@endif
        </div>
        @endif

    </div>
</div>

{{-- 이미지 라이트박스 — embed(iframe)에서는 부모 창의 라이트박스를 사용하므로 포함하지 않음 --}}
@if(!$isEmbed)
    @include('maint-requests._image-lightbox')
@endif

{{-- 이미지 리사이즈/뷰어/이미지 주석 오버레이는 표준 partial 이 자체 생성 --}}

@include('maint-requests._summary-js')

{{-- Alpine 데이터 컴포넌트: 추가 개발(유상) 폼 --}}
<script>
/* 상세 폼 첨부파일 픽커 — 신규 업로드만 (기존은 표시·다운로드만). 등록 후 삭제 불가 */
window.srEditAttach = function(init) {
    return {
        max: init.max || 0,
        picked: [],
        fmt(b) {
            if (b < 1024) return b + 'B';
            if (b < 1024*1024) return (b/1024).toFixed(1) + 'KB';
            return (b/(1024*1024)).toFixed(1) + 'MB';
        },
        onPick(e) {
            const MAX_BYTES = 10*1024*1024;
            const picked = [...(e.target.files || [])];
            const errors = [];
            const accepted = [];
            for (const f of picked) {
                if (accepted.length + this.picked.length >= this.max) {
                    errors.push('남은 슬롯이 부족하여 일부 파일이 제외되었습니다.');
                    break;
                }
                if (f.size > MAX_BYTES) {
                    errors.push(f.name + ' 은 10MB 를 초과하여 제외되었습니다.');
                    continue;
                }
                accepted.push(f);
            }
            // 누적 (multiple input 의 file 리스트 자체는 새로 picked 된 것이므로, picked 갱신만 하면 됨)
            this.picked = [...this.picked, ...accepted];
            // input.files 를 picked 와 동기화 (제출시 attachments[] 로 전송)
            const dt = new DataTransfer();
            this.picked.forEach(f => dt.items.add(f));
            e.target.files = dt.files;
            if (errors.length) alert(errors.join('\n'));
        },
    };
};

window.paidDevForm = function(init) {
    return {
        enabled: init.enabled,
        days: init.days,
        cost: init.cost,
        desc: init.desc,
        endpoint: init.endpoint,
        sending: false,
        dailyRate: 340000,   // 일당 (₩) — 변경 시 이 값만 수정
        init: function() {
            const self = this;
            // days 변경 시 cost 자동 재계산 (수동 입력 후 days 가 다시 바뀌면 다시 덮어씀)
            this.$watch('days', function(v) {
                const d = Number(v) || 0;
                self.cost = d * self.dailyRate;
            });
        },
        sendToManager: function() {
            const self = this;
            if (!self.enabled) { alert('먼저 유상 여부를 체크해 주세요.'); return; }
            if (!self.days || !self.cost || !String(self.desc || '').trim()) {
                alert('걸리는 일·비용·설명을 모두 입력해 주세요.'); return;
            }
            if (!confirm("SR 매니저에게 추가개발 승인 요청 메일을 보내시겠습니까?\n• SR 상태가 '추가 개발'로 변경됩니다.")) return;
            self.sending = true;
            fetch(self.endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ days: self.days, cost: self.cost, description: self.desc }),
            })
            .then(function(r) { return r.json().then(function(d) { return { status: r.status, ok: r.ok, data: d }; }); })
            .then(function(res) {
                if (res.ok && res.data.ok) {
                    alert(res.data.message || '매니저에게 전송되었습니다.');
                    window.location.reload();
                } else {
                    alert(res.data.message || '전송에 실패했습니다.');
                }
            })
            .catch(function(e) { alert('전송 중 오류: ' + e.message); })
            .finally(function() { self.sending = false; });
        }
    };
};
</script>

{{-- Quill rich editor 초기화 --}}
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function() {
    const editorEl = document.getElementById('sr-quill-editor');
    const wrapEl   = document.getElementById('sr-quill-wrap');
    const hiddenEl = document.getElementById('sr-content-input');
    if (!editorEl || !hiddenEl) return;

    const UPLOAD_URL = @json(route('maint-requests.upload-image'));
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content
              || @json(csrf_token());

    // StyledImage blot 등록 + paste/upload/8핸들 리사이즈/이미지 주석 모두 표준 partial 이 일괄 처리
    const quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: '상세 내용을 입력하세요. 이미지는 복사·붙여넣기(Ctrl+V) 또는 툴바 아이콘으로 첨부됩니다.',
        modules: {
            toolbar: [
                [{ header: [false, 1, 2, 3] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                ['link', 'image'],
                ['clean'],
            ],
        },
    });

    // 초기 콘텐츠 로드 — HTML 이면 innerHTML 직접 주입(이미지 style·width·height 보존),
    // 일반 텍스트면 setText
    const initial = (hiddenEl.value || '').trim();
    if (initial) {
        if (/<\w+[\s\S]*?>/.test(initial)) {
            quill.root.innerHTML = initial;
            quill.update();   // Parchment/Delta 내부 모델 재동기화
        } else {
            quill.setText(initial);
        }
    }

    // focus 표시
    quill.on('selection-change', r => { wrapEl.classList.toggle('focused', !!r); });

    // 폼 submit 직전 HTML 을 hidden 으로 sync. 빈 콘텐츠는 빈 문자열.
    const form = editorEl.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            const html = quill.getLength() <= 1 ? '' : quill.root.innerHTML;
            hiddenEl.value = html;
        });
    }

    // ────────────────────────────────────────────────────────────
    // SR 표준: Copy & Paste + 8 방향 리사이즈 + 이미지 주석 (이미지 뷰어 버튼 제거됨)
    // ────────────────────────────────────────────────────────────
    if (window.installQuillImageResize) {
        window.installQuillImageResize(quill, {
            uploadUrl: UPLOAD_URL,
            csrfToken: CSRF,
            enableAnnotate: true,
        });
    }
})();
</script>
