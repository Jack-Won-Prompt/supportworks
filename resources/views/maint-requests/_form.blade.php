@php $isEmbed = $isEmbed ?? false; @endphp

<style>
    .maint-sticky-bar { position: sticky; top: 0; z-index: 30; }
    .maint-sticky-bar.is-stuck { box-shadow: 0 4px 12px -4px rgba(0,0,0,.08); }
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

@if(session('success'))
    <div class="mb-3 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
@endif

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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">상세 내용</label>
                <textarea name="content" rows="10" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono">{{ old('content', $r->content) }}</textarea>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">구분</label>
                    <input type="text" name="category" value="{{ old('category', $r->category) }}" maxlength="100"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">콜로 담당자</label>
                    <input list="colo-list" name="colo_user_name" value="{{ old('colo_user_name', $r->coloUser?->name) }}"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <datalist id="colo-list">
                        @foreach($coloUsers as $u)<option value="{{ $u->name }}"></option>@endforeach
                    </datalist>
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">위드웍스 담당자</label>
                    <input list="dev-list" name="assignee_name" value="{{ old('assignee_name', $r->assignee?->name ?: $r->assignee_raw) }}"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <datalist id="dev-list">
                        @foreach($devUsers as $u)<option value="{{ $u->name }}"></option>@endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">요청일</label>
                    <input type="date" name="request_date" value="{{ old('request_date', optional($r->request_date)->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">완료 예정일</label>
                    <input type="date" name="eta" value="{{ old('eta', optional($r->eta)->format('Y-m-d')) }}"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>
                <div class="col-span-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                        @foreach($statusLabels as $k => $v)
                            <option value="{{ $k }}" {{ old('status', $r->status)===$k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
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
                <div class="flex gap-2">
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">저장</button>
                </div>
            </div>
        </form>

        {{-- 삭제 (관리자 전용) --}}
        @if(auth()->user()->isAdmin())
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

        {{-- 콜로 비고 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">콜로 비고</h3>
                <span class="text-xs text-gray-400">{{ $r->notes->where('note_type','colo')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($r->notes->where('note_type','colo') as $n)
                    <div class="group bg-gray-50 rounded-lg p-2.5 text-sm text-gray-700 whitespace-pre-wrap break-words">
                        {{ $n->body }}
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
                <textarea name="body" rows="2" required placeholder="콜로 측 비고 작성"
                          class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                <button type="submit" class="w-full px-3 py-1.5 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">추가</button>
            </form>
        </div>

        {{-- 링크 비고 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">위드웍스 비고</h3>
                <span class="text-xs text-gray-400">{{ $r->notes->where('note_type','link')->count() }}</span>
            </div>
            <div class="space-y-2 mb-3">
                @forelse($r->notes->where('note_type','link') as $n)
                    <div class="group bg-indigo-50/50 rounded-lg p-2.5 text-sm text-gray-700 whitespace-pre-wrap break-words">
                        {{ $n->body }}
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
                <textarea name="body" rows="2" required placeholder="위드웍스 측 비고 작성"
                          class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs"></textarea>
                <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700">추가</button>
            </form>
        </div>

        {{-- 진행/원본 표시 --}}
        @if($r->progress_raw || $r->colo_check_raw || $r->assignee_raw)
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-xs text-gray-500 space-y-1">
            @if($r->progress_raw)<div><span class="font-medium text-gray-600">진행사항 원본:</span> {{ $r->progress_raw }}</div>@endif
            @if($r->colo_check_raw)<div><span class="font-medium text-gray-600">콜로 확인 원본:</span> {{ $r->colo_check_raw }}</div>@endif
            @if($r->assignee_raw)<div><span class="font-medium text-gray-600">담당자 원본:</span> {{ $r->assignee_raw }}</div>@endif
        </div>
        @endif

    </div>
</div>
