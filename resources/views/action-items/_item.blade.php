@php
    $borderColor = $item->is_completed
        ? '#e5e7eb'
        : ($item->isOverdue() ? '#ef4444' : ($item->isDueSoon() ? '#f59e0b' : 'var(--t400)'));
    $bgBorder = $item->is_completed
        ? '#e5e7eb'
        : ($item->isOverdue() ? '#fecaca' : '#e5e7eb');
@endphp
<div style="background:#fff;border:1px solid {{ $bgBorder }};border-left:3px solid {{ $borderColor }};border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:12px;{{ $item->is_completed ? 'opacity:.6;' : '' }}">

    {{-- 체크박스 --}}
    <form action="{{ route('action-items.toggle', $item) }}" method="POST" style="flex-shrink:0;margin-top:1px;">
        @csrf @method('PATCH')
        <button type="submit"
            style="width:18px;height:18px;border-radius:5px;border:2px solid {{ $item->is_completed ? '#16a34a' : '#d1d5db' }};background:{{ $item->is_completed ? '#16a34a' : '#fff' }};cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;flex-shrink:0;"
            title="{{ $item->is_completed ? __('work.action_mark_incomplete') : __('work.action_mark_complete') }}">
            @if($item->is_completed)
            <svg width="10" height="10" fill="none" stroke="#fff" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            @endif
        </button>
    </form>

    {{-- 내용 --}}
    <div style="flex:1;min-width:0;">
        <p style="font-size:13px;font-weight:500;color:{{ $item->is_completed ? '#9ca3af' : '#111827' }};{{ $item->is_completed ? 'text-decoration:line-through;' : '' }}margin-bottom:2px;word-break:break-word;">
            {{ $item->title }}
        </p>

        @if($item->description)
        <p style="font-size:12px;color:#6b7280;margin-bottom:5px;word-break:break-word;">{{ $item->description }}</p>
        @endif

        @if($item->sourceMessage?->conversation)
        <a href="{{ route('messages.show', $item->sourceMessage->conversation) }}#message-{{ $item->source_message_id }}"
           style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#7c3aed;background:#f5f3ff;border-radius:10px;padding:2px 7px;text-decoration:none;margin-bottom:5px;">
            {{ __('messages.action_source_message') }}
        </a>
        @endif

        <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
            {{-- 마감일 --}}
            @if($item->due_date)
            <span style="font-size:11px;padding:2px 7px;border-radius:10px;background:{{ $item->is_completed ? '#f3f4f6' : ($item->isOverdue() ? '#fee2e2' : '#fef3c7') }};color:{{ $item->is_completed ? '#9ca3af' : ($item->isOverdue() ? '#dc2626' : '#d97706') }};">
                {{ $item->due_date->format('m/d') }}{{ (!$item->is_completed && $item->isOverdue()) ? __('work.action_delayed') : '' }}
            </span>
            @endif

            {{-- 담당자 --}}
            @if($item->assignedUser)
            <span style="font-size:11px;padding:2px 7px;border-radius:10px;background:#eff6ff;color:#3b82f6;">
                → {{ $item->assignedUser->name }}
            </span>
            @elseif($item->assigned_to === null && $item->user_id !== auth()->id())
            <span style="font-size:11px;padding:2px 7px;border-radius:10px;background:#eff6ff;color:#3b82f6;">
                → {{ $item->creator?->name ?? __('work.action_by_creator') }}
            </span>
            @endif

            {{-- 프로젝트 --}}
            @if($item->project)
            <span style="font-size:11px;padding:2px 7px;border-radius:10px;background:#f3f4f6;color:#6b7280;">
                {{ $item->project->name }}
            </span>
            @endif

            {{-- 등록자 (본인이 아닐 경우) --}}
            @if($item->user_id !== auth()->id())
            <span style="font-size:11px;color:#9ca3af;">by {{ $item->creator?->name }}</span>
            @endif

            {{-- 완료 시각 --}}
            @if($item->is_completed && $item->completed_at)
            <span style="font-size:11px;color:#9ca3af;">{{ __('work.action_completed_at') }}{{ $item->completed_at->format('m/d H:i') }}</span>
            @endif
        </div>
    </div>

    {{-- 삭제 버튼 --}}
    @if($item->user_id === auth()->id())
    <form action="{{ route('action-items.destroy', $item) }}" method="POST"
        onsubmit="return confirm('{{ __('work.action_confirm_delete') }}')"
        style="flex-shrink:0;">
        @csrf @method('DELETE')
        <button type="submit"
            style="background:none;border:none;cursor:pointer;padding:4px;color:#d1d5db;line-height:0;"
            onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'"
            title="{{ __('common.delete') }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </form>
    @endif

</div>
