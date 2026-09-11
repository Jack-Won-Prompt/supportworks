@php
    $isUser     = $message->role === 'user';
    $isHandover = $message->role === 'handover';

    // 선택지는 "지금 답을 기다리는" 마지막 질문에만 띄운다. 지난 질문의 버튼이
    // 남아 있으면 이미 답한 것을 다시 누르게 된다.
    $showChoices = ! $isUser
        && ! $isHandover
        && ! empty($message->choices)
        && ($latestChoiceId ?? null) === $message->id
        && ! $job->status->isTerminal()
        && ($canEdit ?? false);

    $author = $isUser ? ($message->author?->name ?? '나') : '담당자';
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
        <div class="aiw-md mt-2" x-html="render(@js($message->content))"></div>
    </details>
@else
    {{-- 사람이 주고받는 대화처럼 보이게: 아바타 + 이름·시각 + 말풍선 --}}
    <div class="flex gap-2 {{ $isUser ? 'flex-row-reverse' : '' }}">
        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold
                    {{ $isUser ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700' }}">
            {{ mb_substr($author, 0, 1) }}
        </div>

        <div class="min-w-0 max-w-[85%] {{ $isUser ? 'items-end text-right' : '' }} flex flex-col">
            <div class="mb-0.5 flex items-center gap-2 text-[11px] text-gray-500 {{ $isUser ? 'flex-row-reverse' : '' }}">
                <span class="font-medium text-gray-700">{{ $author }}</span>
                <span class="text-gray-400">{{ $message->created_at?->format('m-d H:i') }}</span>
                @if ($isUser && $message->isPendingDelivery())
                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] text-amber-700">전달 대기</span>
                @endif
            </div>

            <div class="rounded-2xl px-3.5 py-2.5 text-left
                        {{ $isUser
                            ? 'rounded-tr-sm bg-indigo-500 text-white'
                            : 'rounded-tl-sm border border-gray-200 bg-white' }}">
                @if ($isUser)
                    <div class="whitespace-pre-wrap text-sm leading-relaxed">{{ $message->content }}</div>
                @else
                    {{-- 담당자가 만든 마크다운은 신뢰하지 않는다. DOMPurify 를 거쳐 렌더한다. --}}
                    <div class="aiw-md" x-html="render(@js($message->content))"></div>
                @endif

                @if ($message->attachments->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($message->attachments as $attachment)
                            @php $src = route('projects.ai-works.attachment', [$project, $job, $attachment]); @endphp
                            <a href="{{ $src }}" target="_blank" rel="noopener"
                               title="{{ $attachment->original_name }} · {{ $attachment->humanSize() }}">
                                <img src="{{ $src }}" alt="{{ $attachment->original_name }}"
                                     class="h-24 w-auto rounded border border-gray-200 object-cover hover:border-indigo-400">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($showChoices)
                {{-- 한 번 클릭으로 답한다. 누르면 그 문구가 그대로 사용자 메시지로 전송된다. --}}
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($message->choices as $choice)
                        <form method="POST" action="{{ route('projects.ai-works.message', [$project, $job]) }}">
                            @csrf
                            <input type="hidden" name="content" value="{{ $choice }}">
                            <button class="rounded-full border border-indigo-300 bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-50">
                                {{ $choice }}
                            </button>
                        </form>
                    @endforeach
                </div>
                <p class="mt-1 text-[11px] text-gray-400">직접 입력해서 답해도 됩니다.</p>
            @endif
        </div>
    </div>
@endif
