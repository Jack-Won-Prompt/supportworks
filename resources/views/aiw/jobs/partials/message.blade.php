@php
    $isUser     = $message->role === 'user';
    $isHandover = $message->role === 'handover';
@endphp

@if ($isHandover)
    {{-- 세션 교체 구분선 + 접힌 인수인계 요약 --}}
    <div class="relative py-2">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-violet-200"></div></div>
        <div class="relative flex justify-center">
            <span class="bg-white px-2 text-[11px] font-medium text-violet-700">
                세션 교체 · {{ $message->created_at?->format('m-d H:i') }}
            </span>
        </div>
    </div>
    <details class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2">
        <summary class="cursor-pointer text-xs font-semibold text-violet-800">인수인계 요약 보기</summary>
        <div class="prose prose-sm max-w-none mt-2" x-html="render(@js($message->content))"></div>
    </details>
@else
    <div class="rounded-lg px-3 py-2 text-sm {{ $isUser ? 'bg-indigo-50 ml-8' : 'bg-gray-50 mr-8' }}">
        <div class="text-[11px] text-gray-500 mb-1 flex items-center gap-2">
            <span>{{ $isUser ? ($message->author?->name ?? '나') : 'Claude' }}</span>
            <span class="text-gray-400">{{ $message->created_at?->format('m-d H:i') }}</span>
            @if ($isUser && $message->isPendingDelivery())
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] text-amber-700">전달 대기</span>
            @endif
        </div>
        @if ($isUser)
            <div class="whitespace-pre-wrap">{{ $message->content }}</div>
        @else
            {{-- Claude 가 만든 마크다운은 신뢰하지 않는다. DOMPurify 를 거쳐 렌더한다. --}}
            <div class="prose prose-sm max-w-none" x-html="render(@js($message->content))"></div>
        @endif
    </div>
@endif
