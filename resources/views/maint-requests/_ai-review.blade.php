{{-- AI 사전 검토 패널 — status='ai_review' 일 때 요청자 확인용으로 표시.
     status 가 다른 상태로 전환된 후엔 _ai-review-history 가 결과 요약을 노출. --}}
@php
    $u = auth()->user();
    $isSrPrivileged = $u && ($u->isAdmin() || (bool) ($u->is_sr_agent ?? false));
    $sameCompany    = $u && (int) $u->company_group_id === (int) $r->company_group_id;
    $canConfirm     = $isSrPrivileged || $sameCompany;

    $aiStatus = $r->ai_review_status;     // pending / analyzing / ready / failed / confirmed / null
    $questions = is_array($r->ai_review_questions) ? $r->ai_review_questions : [];
    $hasResult = trim((string) $r->ai_review_summary) !== '' || !empty($questions);
@endphp

<div class="bg-emerald-50/50 border border-emerald-200 rounded-xl p-5 mb-5"
     x-data='aiReviewPanel({{ json_encode([
        'summary'   => (string) $r->ai_review_summary,
        'endpoint'  => route('maint-requests.ai-review.confirm', $r),
        'csrf'      => csrf_token(),
        'isModal'   => $isEmbed ?? false,
     ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT) }})'>

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-emerald-900 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            AI 사전 검토 — 요청자 확인 단계
        </h3>
        <span class="text-[10px] px-2 py-0.5 rounded font-medium
            @if($aiStatus === 'failed') bg-rose-100 text-rose-700
            @elseif($aiStatus === 'ready') bg-emerald-100 text-emerald-700
            @else bg-gray-100 text-gray-600 @endif">
            @switch($aiStatus)
                @case('ready') 분석 완료 @break
                @case('analyzing') 분석 중 @break
                @case('failed') 분석 실패 @break
                @default 대기 중
            @endswitch
            @if($r->ai_review_at) · {{ $r->ai_review_at->diffForHumans() }} @endif
        </span>
    </div>

    @if($aiStatus === 'failed' || !$hasResult)
        <div class="bg-white border border-rose-200 rounded-lg p-3 text-sm text-rose-700 mb-3">
            <div class="font-medium mb-0.5">AI 분석이 적용되지 않았습니다.</div>
            @if($r->ai_review_error)
                <div class="text-xs text-rose-500/80">{{ \Illuminate\Support\Str::limit($r->ai_review_error, 200) }}</div>
            @else
                <div class="text-xs text-rose-500/80">결과가 비어있거나 호출에 실패했습니다. 원본 그대로 담당자에게 전달할 수 있습니다.</div>
            @endif
        </div>
    @else
        {{-- 원본 vs AI 정리본 비교 --}}
        <div class="grid grid-cols-12 gap-3 mb-3">
            <div class="col-span-6">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">원본 (요청자 입력)</div>
                <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm text-gray-800 max-h-72 overflow-y-auto">
                    <div class="font-medium mb-1.5">{{ $r->summary }}</div>
                    @if($r->content)
                        <div class="text-xs text-gray-600 leading-relaxed prose prose-sm max-w-none">
                            {!! $r->content !!}
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-span-6">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">AI 정리본 (수정 가능)</span>
                    <div class="flex gap-2 text-[11px] text-gray-500">
                        @if($r->ai_review_difficulty)
                            <span>난이도 <b class="text-emerald-700">{{ $r->ai_review_difficulty }}/5</b></span>
                        @endif
                        @if($r->ai_review_effort)
                            <span>예상 <b class="text-emerald-700">{{ $r->ai_review_effort }}</b></span>
                        @endif
                    </div>
                </div>
                <textarea x-model="summary" rows="10"
                    class="w-full px-3 py-2 border border-emerald-200 rounded-lg text-sm bg-white font-mono leading-relaxed"
                    placeholder="AI 가 정리한 본문 — 필요하면 직접 수정하세요."
                    @if(!$canConfirm) readonly @endif></textarea>
            </div>
        </div>

        {{-- AI 자동 질문 (있을 때만) --}}
        @if(!empty($questions))
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
            <div class="text-xs font-semibold text-amber-900 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                AI 가 추가 확인을 요청합니다 ({{ count($questions) }}건)
            </div>
            <div class="space-y-2">
                @foreach($questions as $idx => $q)
                <div>
                    <div class="text-xs text-amber-900 mb-1">Q{{ $idx + 1 }}. {{ $q['q'] ?? '' }}</div>
                    <input type="text" x-model="answers[{{ $idx }}]" value="{{ $q['a'] ?? '' }}"
                        placeholder="답변 (선택 — 비워두면 모름으로 처리)"
                        class="w-full px-2.5 py-1.5 border border-amber-200 rounded text-xs bg-white"
                        @if(!$canConfirm) readonly @endif>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endif

    {{-- 액션 버튼 --}}
    @if($canConfirm)
    <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-emerald-200/60">
        <span class="text-xs text-gray-500 mr-auto">확인 후 담당자에게 전달됩니다.</span>

        <button type="button" @click="confirm('skip')" :disabled="sending"
            class="px-3 py-1.5 text-xs border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
            AI 없이 원본 그대로
        </button>
        @if($hasResult && $aiStatus !== 'failed')
        <button type="button" @click="confirm('edit')" :disabled="sending"
            class="px-3 py-1.5 text-xs border border-emerald-300 rounded-md text-emerald-700 bg-white hover:bg-emerald-50 disabled:opacity-50">
            수정한 정리본으로 진행
        </button>
        <button type="button" @click="confirm('as_is')" :disabled="sending"
            class="px-3 py-1.5 text-xs rounded-md text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 font-medium">
            <span x-show="!sending">AI 정리본 그대로 진행</span>
            <span x-show="sending" x-cloak>처리 중...</span>
        </button>
        @endif
    </div>
    @else
    <div class="text-xs text-gray-500 pt-2 border-t border-emerald-200/60">
        본 SR 의 확인은 요청자(또는 SR 담당자)만 진행할 수 있습니다.
    </div>
    @endif
</div>

<script>
window.aiReviewPanel = window.aiReviewPanel || function(init) {
    return {
        summary: init.summary || '',
        answers: {},
        endpoint: init.endpoint,
        csrf: init.csrf,
        isModal: !!init.isModal,
        sending: false,
        confirm(mode) {
            if (this.sending) return;
            const messages = {
                as_is: 'AI 정리본 그대로 담당자에게 전달합니다. 진행할까요?',
                edit:  '수정한 정리본을 적용해 담당자에게 전달합니다. 진행할까요?',
                skip:  'AI 정리본 없이 원본 그대로 담당자에게 전달합니다. 진행할까요?',
            };
            if (!confirm(messages[mode] || '진행할까요?')) return;
            this.sending = true;

            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('mode', mode);
            if (mode === 'edit') body.append('ai_summary', this.summary);
            if (this.isModal) body.append('_modal', '1');
            Object.keys(this.answers).forEach(idx => {
                body.append(`answers[${idx}]`, this.answers[idx] || '');
            });

            fetch(this.endpoint, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            }).then(res => {
                if (res.redirected) {
                    window.location.href = res.url;
                } else if (res.ok) {
                    window.location.reload();
                } else {
                    alert('확인 처리 실패: ' + res.status);
                }
            }).catch(err => {
                alert('통신 오류: ' + err.message);
            }).finally(() => {
                this.sending = false;
            });
        }
    };
};
</script>
