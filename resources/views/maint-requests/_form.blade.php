@php $isEmbed = $isEmbed ?? false; @endphp

{{-- Quill rich editor (SR 상세 내용 리치 에디터 + 이미지 paste) --}}
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<style>
    .maint-sticky-bar { position: sticky; top: 0; z-index: 30; }
    .maint-sticky-bar.is-stuck { box-shadow: 0 4px 12px -4px rgba(0,0,0,.08); }
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

<div class="grid grid-cols-12 gap-5">

    {{-- 좌측: 요청 본문 (편집 폼) --}}
    <div class="col-span-8">
        <form method="POST" action="{{ route('maint-requests.update', $r) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
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

            <div x-data="srSummary({
                    initialSummary: @js(old('ai_summary', $r->ai_summary ?? '')),
                    initialContextIds: @js(old('ai_summary_context_ids', is_array($r->ai_summary_context_ids ?? null) ? $r->ai_summary_context_ids : [])),
                    endpoint: '{{ route('maint-requests.works-summary') }}',
                    srId: {{ $r->id }},
                    csrf: '{{ csrf_token() }}',
                 })">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">상세 내용</label>
                    {{-- 요약 없음 OR 원본(summary/content) 수정됨 → 버튼 표시.
                         요약 있고 원본 미수정 → 버튼 숨김 (저장 후 재 진입 시 자동 적용) --}}
                    <button type="button" @click="generate()" :disabled="loading"
                            x-show="!summary || dirty" x-cloak
                            :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-50 hover:border-indigo-300'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md text-indigo-700 bg-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span x-show="!loading">웍스 요약 생성</span>
                        <span x-show="loading" x-cloak>생성 중...</span>
                    </button>
                </div>
                {{-- 상세 내용 — Quill 리치 에디터 (이미지 paste 지원) --}}
                <div class="sr-quill" id="sr-quill-wrap">
                    <div id="sr-quill-editor"></div>
                </div>
                <input type="hidden" name="content" id="sr-content-input" value="{{ old('content', $r->content) }}">
                <template id="sr-content-initial">{{ old('content', $r->content) }}</template>

                {{-- 웍스 요약 결과 (미리보기 / 편집) --}}
                <div x-show="summary" x-cloak class="mt-3 bg-indigo-50/40 border border-indigo-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold text-indigo-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            웍스 요약 (담당자용)
                        </h4>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-500" x-show="contextIds.length > 0">참고 SR <span x-text="contextIds.length"></span>건</span>
                            <button type="button" @click="reset()" class="text-rose-600 hover:text-rose-800">제거</button>
                        </div>
                    </div>
                    <textarea x-model="summary" rows="8"
                              class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm bg-white"
                              placeholder="AI 가 정리한 요약 — 필요하면 직접 수정하세요."></textarea>
                    <p class="text-xs text-gray-500 mt-1.5">저장 시 원본과 함께 저장됩니다.</p>
                </div>

                <input type="hidden" name="ai_summary" :value="summary || ''">
                <input type="hidden" name="ai_summary_context_ids" :value="contextIds.length > 0 ? JSON.stringify(contextIds) : ''">
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
                    <div class="w-full px-3 py-2 border border-gray-100 bg-gray-50 rounded-lg text-sm text-gray-700">{{ $currentColoName ?: '-' }}</div>
                    <input type="hidden" name="colo_user_name" value="{{ $currentColoName }}">
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
            <form method="POST" action="{{ route('maint-requests.destroy', $r) }}" class="mt-3 text-right"
                  onsubmit="return confirm('정말 삭제하시겠습니까?');">
                @csrf @method('DELETE')
                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">요청 삭제</button>
            </form>
        @endif
    </div>

    {{-- 우측: 비고 --}}
    <div class="col-span-4 space-y-4">

        {{-- 회사 측 비고 (SR 대상 회사명) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ $companyLabel }} 비고</h3>
                <span class="text-xs text-gray-400">{{ $r->notes->where('note_type','colo')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($r->notes->where('note_type','colo') as $n)
                    <div class="group bg-gray-50 rounded-lg p-2.5 text-sm text-gray-700">
                        <div class="whitespace-pre-wrap break-words">{{ $n->body }}</div>
                        <div class="flex items-center justify-between mt-1 text-xs text-gray-400">
                            <span>{{ $n->created_at->format('Y-m-d H:i') }}</span>
                            <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $n]) }}" class="opacity-0 group-hover:opacity-100"
                                  onsubmit="return confirm('삭제하시겠습니까?');">
                                @csrf @method('DELETE')
                                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                            </form>
                        </div>
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
                <span class="text-xs text-gray-400">{{ $r->notes->where('note_type','link')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($r->notes->where('note_type','link') as $n)
                    <div class="group bg-indigo-50/50 rounded-lg p-2.5 text-sm text-gray-700">
                        <div class="whitespace-pre-wrap break-words">{{ $n->body }}</div>
                        <div class="flex items-center justify-between mt-1 text-xs text-gray-400">
                            <span>{{ $n->created_at->format('Y-m-d H:i') }}</span>
                            <form method="POST" action="{{ route('maint-requests.notes.destroy', [$r, $n]) }}" class="opacity-0 group-hover:opacity-100"
                                  onsubmit="return confirm('삭제하시겠습니까?');">
                                @csrf @method('DELETE')
                                @if($isEmbed)<input type="hidden" name="_modal" value="1">@endif
                                <button type="submit" class="text-red-400 hover:text-red-600">삭제</button>
                            </form>
                        </div>
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
            // 추가 개발(유상) 영역 노출 권한 — 관리자 OR 링크더랩 회사 소속
            $linkthelabId = \App\Models\CompanyGroup::where('name', '링크더랩')->value('id');
            $canSeePaidDev = auth()->user()->isAdmin() || (int) auth()->user()->company_group_id === (int) $linkthelabId;
        @endphp
        @if($canSeePaidDev)
        {{-- 추가 개발 (유상) — 매니저 승인 요청 영역 (관리자 + 링크더랩 사용자만) --}}
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
                        <input type="number" name="paid_dev_days" min="0" step="1" x-model="days"
                               class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1">비용 (₩)</label>
                        <input type="number" name="paid_dev_cost" min="0" step="1000" x-model="cost"
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
window.paidDevForm = function(init) {
    return {
        enabled: init.enabled,
        days: init.days,
        cost: init.cost,
        desc: init.desc,
        endpoint: init.endpoint,
        sending: false,
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
