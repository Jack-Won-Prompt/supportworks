@php $embed = $embed ?? false; @endphp
@extends($embed ? 'mailbox.embed-layout' : 'layouts.app')

@section('title', '메일 · ' . $message->subject)
@section('header-actions')@endsection

@section($embed ? 'embed-content' : 'content')
<div style="{{ $embed ? 'max-width:none;margin:0;padding:0;' : 'max-width:980px;margin:0 auto;padding:8px 0 24px;' }}">

    {{-- 상단 액션 --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
        @if(!$embed)
        <a href="{{ url()->previous() }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:#fff;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;color:#52525b;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            목록
        </a>
        @endif
        <a href="{{ $embed ? 'javascript:void(0)' : route('mailbox.compose', ['reply_to' => $message->id]) }}"
           @if($embed) onclick="mbReply({{ $message->id }})" @endif
           style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:var(--t600);color:#fff;border:none;border-radius:7px;font-size:12.5px;font-weight:700;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            답장
        </a>
        <a href="{{ $embed ? 'javascript:void(0)' : route('mailbox.compose', ['forward' => $message->id]) }}"
           @if($embed) onclick="mbForward({{ $message->id }})" @endif
           style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fff;border:1px solid var(--t300);color:var(--t700);border-radius:7px;font-size:12.5px;font-weight:700;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l7-7m0 0l-7-7m7 7H3"/></svg>
            전달
        </a>
        @php
            $myRecip = $message->recipients->where('user_id', auth()->id())->where('folder', '!=', 'sent')->first();
        @endphp
        @if($myRecip && $myRecip->folder === 'inbox' && !$myRecip->is_read)
            <button type="button" onclick="mbToggleRead({{ $message->id }}, true)" style="padding:7px 12px;background:#fff;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;color:#52525b;cursor:pointer;">읽음 처리</button>
        @elseif($myRecip && $myRecip->folder === 'inbox' && $myRecip->is_read)
            <button type="button" onclick="mbToggleRead({{ $message->id }}, false)" style="padding:7px 12px;background:#fff;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;color:#52525b;cursor:pointer;">읽지 않음 표시</button>
        @endif
        @if($myRecip && $myRecip->folder !== 'trash')
            <button type="button" onclick="mbTrashOne({{ $message->id }})" style="padding:7px 12px;background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;margin-left:auto;">휴지통</button>
        @elseif($myRecip && $myRecip->folder === 'trash')
            <div style="margin-left:auto;display:flex;gap:6px;">
                <button type="button" onclick="mbRestoreOne({{ $message->id }})" style="padding:7px 12px;background:#fff;border:1px solid var(--t300);color:var(--t700);border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;">복원</button>
                <button type="button" data-confirm="이 메일을 영구 삭제합니다. 되돌릴 수 없습니다." onclick="mbDestroyOne({{ $message->id }})" style="padding:7px 12px;background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;">영구 삭제</button>
            </div>
        @endif
    </div>

    {{-- 제목 --}}
    <h1 style="font-size:20px;font-weight:700;color:#1e1b2e;margin:0 0 6px;line-height:1.4;">{{ $message->subject }}</h1>
    <div style="font-size:12px;color:var(--color-text-tertiary);margin-bottom:18px;">
        스레드 {{ $thread->count() }}개 메시지
    </div>

    {{-- 스레드 메시지들 --}}
    @foreach($thread as $idx => $m)
        @php $isCurrent = $m->id === $message->id; @endphp
        <div style="background:#fff;border:1px solid {{ $isCurrent ? 'var(--t300)' : 'var(--color-border-default)' }};border-radius:14px;margin-bottom:12px;overflow:hidden;{{ $isCurrent ? 'box-shadow:0 4px 12px rgba(124,58,237,.08);' : '' }}">
            {{-- 메시지 헤더 --}}
            <div style="padding:14px 20px;border-bottom:1px solid var(--color-bg-muted);display:flex;align-items:flex-start;gap:12px;background:{{ $isCurrent ? 'var(--t50)' : '#fafafa' }};">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--t400),var(--t600));color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">
                    {{ mb_substr($m->sender?->name ?? '?', 0, 1) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;">
                        <span style="font-size:13.5px;font-weight:700;color:#1e1b2e;">{{ $m->sender?->name }}</span>
                        <span style="font-size:11.5px;color:var(--color-text-tertiary);">&lt;{{ $m->sender?->email }}&gt;</span>
                    </div>
                    <div style="font-size:11.5px;color:var(--color-text-tertiary);margin-top:3px;">
                        받는이:
                        @foreach($m->recipients->where('type', 'to') as $r)
                            <span title="{{ $r->email }}">{{ $r->name ?: $r->email }}</span>@if(!$loop->last), @endif
                        @endforeach
                        @php $ccs = $m->recipients->where('type', 'cc'); @endphp
                        @if($ccs->isNotEmpty())
                            · 참조: @foreach($ccs as $r)<span title="{{ $r->email }}">{{ $r->name ?: $r->email }}</span>@if(!$loop->last), @endif @endforeach
                        @endif
                    </div>
                </div>
                <div style="font-size:11.5px;color:var(--color-text-tertiary);flex-shrink:0;">
                    {{ optional($m->sent_at)->format('Y-m-d H:i') }}
                </div>
            </div>

            {{-- 본문 --}}
            <div style="padding:20px;font-size:14px;color:#27272a;line-height:1.7;word-break:break-word;">
                {!! $m->body_html !!}
            </div>

            {{-- 첨부 --}}
            @if($m->attachments->isNotEmpty())
                <div style="padding:12px 20px;border-top:1px solid var(--color-bg-muted);background:#fafafa;">
                    <div style="font-size:11px;font-weight:700;color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">첨부파일 ({{ $m->attachments->count() }})</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($m->attachments as $att)
                            <a href="{{ route('mailbox.attachment', $att->id) }}" target="_blank"
                               style="display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:#fff;border:1px solid var(--t200);border-radius:7px;font-size:12px;color:var(--t700);text-decoration:none;max-width:280px;">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $att->original_name }}</span>
                                <span style="color:var(--color-text-tertiary);font-size:11px;">({{ $att->formatted_size }})</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>

<script>
const MB_SHOW_CSRF = '{{ csrf_token() }}';
async function mbToggleRead(id, asRead) {
    const fd = new FormData();
    if (!asRead) fd.append('unread', '1');
    const res = await fetch(`{{ url('mailbox/messages') }}/${id}/read`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': MB_SHOW_CSRF, 'Accept': 'application/json' }, body: fd
    });
    if (res.ok) location.reload();
}
async function mbAction(url, id) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': MB_SHOW_CSRF },
        body: JSON.stringify({ ids: [id] }),
    });
    if (res.ok) location.href = '{{ route("mailbox.inbox") }}';
}
function mbTrashOne(id)   { mbAction('{{ route("mailbox.trash.move") }}', id); }
function mbRestoreOne(id) { mbAction('{{ route("mailbox.trash.restore") }}', id); }
function mbDestroyOne(id) { mbAction('{{ route("mailbox.destroy-forever") }}', id); }

// iframe(embed) 모드 — 부모 모달로 답장/전달 화면 전환
function mbReply(id)   { if (window.parent && window.parent.mbOpenCompose) window.parent.mbOpenCompose('reply_to=' + id); }
function mbForward(id) { if (window.parent && window.parent.mbOpenCompose) window.parent.mbOpenCompose('forward=' + id); }
</script>
@endsection
