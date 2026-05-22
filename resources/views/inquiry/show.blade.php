@extends($isPopup ? 'layouts.popup' : 'layouts.app')

@section('title', __('messages.inquiry_detail'))

@if($isPopup)
@section('popup-title')
<div style="display:flex;align-items:center;gap:8px;">
    <svg width="16" height="16" fill="none" stroke="var(--tText)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
    <span style="font-size:14px;font-weight:700;color:#18181b;max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $conversation->name }}</span>
</div>
@endsection
@endif

@push('styles')
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
.reply-quill .ql-toolbar{border:none;border-bottom:1px solid var(--t100);padding:4px 6px;background:var(--t50);border-radius:8px 8px 0 0;}
.reply-quill .ql-container{border:none;font-family:inherit;}
.reply-quill .ql-editor{min-height:120px;max-height:360px;overflow-y:auto;padding:8px 12px;font-size:14px;color:#18181b;line-height:1.6;}
.reply-quill .ql-editor.ql-blank::before{font-style:normal;color:#a1a1aa;}
.reply-quill .ql-toolbar .ql-stroke{stroke:#71717a;}
.reply-quill .ql-toolbar .ql-fill{fill:#71717a;}
.reply-quill .ql-toolbar button:hover .ql-stroke,.reply-quill .ql-toolbar button.ql-active .ql-stroke{stroke:var(--t600);}
.reply-quill .ql-toolbar button:hover .ql-fill,.reply-quill .ql-toolbar button.ql-active .ql-fill{fill:var(--t600);}
/* 메시지 내 서식 렌더링 */
.rte-body p{margin:0;line-height:1.6;}
.rte-body p+p{margin-top:3px;}
.rte-body strong{font-weight:700;}
.rte-body em{font-style:italic;}
.rte-body u{text-decoration:underline;}
.rte-body s{text-decoration:line-through;}
.rte-body ul,.rte-body ol{margin:3px 0 3px 18px;padding:0;}
.rte-body li{margin:2px 0;}
.rte-body a{color:inherit;text-decoration:underline;}
.rte-body blockquote{border-left:3px solid rgba(128,128,128,.4);padding-left:10px;margin:4px 0;}
.rte-body pre{background:rgba(0,0,0,.12);padding:5px 10px;border-radius:4px;font-family:monospace;font-size:12px;white-space:pre-wrap;}
.rte-body code{background:rgba(0,0,0,.1);padding:1px 4px;border-radius:3px;font-family:monospace;font-size:12px;}
.rte-body img{max-width:100%;border-radius:6px;margin:4px 0;display:block;}
.inq-file-bar{display:none;align-items:center;gap:8px;padding:6px 10px;background:var(--t50);border-top:1px solid var(--t100);font-size:12px;color:#52525b;}
.inq-file-thumb{width:28px;height:28px;object-fit:cover;border-radius:4px;display:none;}
.inq-file-icon{color:#a1a1aa;flex-shrink:0;}
.inq-attach-btn{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border:1.5px solid var(--t100);border-radius:8px;cursor:pointer;color:#a1a1aa;flex-shrink:0;transition:all .15s;}
.inq-attach-btn:hover{border-color:var(--t400);color:var(--t500);}
/* 이미지 인라인 의견 */
.inline-img-comments{margin-top:6px;display:flex;flex-direction:column;gap:3px;}
.iic-item{padding:5px 8px;border-radius:6px;font-size:11px;line-height:1.45;background:rgba(0,0,0,.07);}
.iic-item.mine{background:rgba(255,255,255,.22);}
.iic-hdr{display:flex;align-items:center;gap:5px;margin-bottom:1px;}
.iic-name{font-weight:700;font-size:11px;}
.iic-time{font-size:10px;opacity:.65;}
.iic-body{white-space:pre-wrap;word-break:break-word;}
</style>
@endpush

@section('header-breadcrumb')
<svg width="14" height="14" fill="none" stroke="#d4d4d8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<a href="{{ route('inquiry.index') }}" style="font-size:13px;color:#a1a1aa;text-decoration:none;" onmouseover="this.style.color='var(--tText)'" onmouseout="this.style.color='#a1a1aa'">{{ __('messages.inquiry') }}</a>
<svg width="14" height="14" fill="none" stroke="#d4d4d8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
<span style="font-size:13px;color:#52525b;font-weight:600;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $conversation->name }}</span>
@endsection

@section('header-actions')
@if($conversation->status !== 'closed')
<form method="POST" action="{{ route('inquiry.close', $conversation) }}" onsubmit="return confirm('{{ __('messages.close_inquiry_confirm') }}')">
    @csrf
    <button type="submit" style="display:flex;align-items:center;gap:4px;padding:5px 14px;background:transparent;color:#71717a;border:1px solid #d4d4d8;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.borderColor='#fca5a5'" onmouseout="this.style.background='transparent';this.style.color='#71717a';this.style.borderColor='#d4d4d8'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ __('messages.close_inquiry') }}
    </button>
</form>
@endif
@endsection

@section('content')
@php
    $status = $conversation->status ?? 'open';
    $isClosed = $status === 'closed';
    $statusLabel = match($status) { 'open'=>__('messages.status_waiting'), 'active'=>__('messages.status_answering'), 'closed'=>__('messages.status_closed'), default=>__('messages.status_waiting') };
    $statusColor = match($status) { 'open'=>'#d97706,#fef3c7', 'active'=>'var(--tText),var(--t100)', 'closed'=>'#6b7280,#f4f4f5', default=>'#d97706,#fef3c7' };
    [$sc, $sb] = explode(',', $statusColor);
@endphp
<div style="max-width:680px;margin:0 auto;display:flex;flex-direction:column;height:{{ $isPopup ? 'calc(100vh - 92px)' : 'calc(100vh - 110px)' }};">

    {{-- 문의 헤더 --}}
    <div style="background:#fff;border:1px solid var(--t100);border-radius:10px;padding:14px 18px;margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-shrink:0;">
        <div style="width:40px;height:40px;border-radius:9px;background:var(--t100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" fill="none" stroke="var(--tText)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:14px;font-weight:700;color:#18181b;">{{ $conversation->name }}</span>
                <span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:9999px;background:{{ $sb }};color:{{ $sc }};">{{ $statusLabel }}</span>
            </div>
            <div style="font-size:12px;color:#a1a1aa;margin-top:2px;">
                {{ $conversation->created_at->format('Y.m.d H:i') }} {{ __('messages.registered_at') }}
                · {{ __('messages.participants_count', ['count' => $conversation->participants->count()]) }}
            </div>
        </div>
    </div>

    {{-- 메시지 스크롤 영역 --}}
    <div id="msg-area" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:4px 2px 16px;">
        @foreach($conversation->messages as $msg)
        @php
            $isAdmin  = str_starts_with($msg->body, '[관리자');
            $isMe     = !$isAdmin && $msg->sender_id === $user->id;
            $dispBody = $isAdmin ? preg_replace('/^\[관리자 .+?\] /', '', $msg->body) : $msg->body;
            $dispName = $isAdmin ? __('messages.admin') : ($msg->sender?->name ?? '?');
        @endphp
        <div style="display:flex;flex-direction:column;align-items:{{ $isMe ? 'flex-end' : 'flex-start' }};">
            @if(!$isMe)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <div style="width:26px;height:26px;border-radius:50%;background:var(--t500);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">{{ mb_substr($dispName, 0, 1) }}</div>
                <span style="font-size:12px;font-weight:600;color:#52525b;">{{ $dispName }}</span>
                <span style="font-size:11px;color:#a1a1aa;">{{ $msg->created_at->format('H:i') }}</span>
            </div>
            @endif
            @php $isHtmlBody = str_starts_with(ltrim($dispBody), '<'); @endphp
            <div style="max-width:75%;padding:10px 14px;border-radius:{{ $isMe ? '14px 4px 14px 14px' : '4px 14px 14px 14px' }};background:{{ $isMe ? 'var(--t500)' : '#fff' }};color:{{ $isMe ? '#fff' : '#18181b' }};font-size:14px;line-height:1.6;border:{{ $isMe ? 'none' : '1px solid var(--t100)' }};word-break:break-word;">
                @if($isHtmlBody)
                    <div class="rte-body">{!! $dispBody !!}</div>
                @else
                    <div style="white-space:pre-wrap;">{!! preg_replace('/(https?:\/\/[^\s<>"\']+)/', '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">$1</a>', e($dispBody)) !!}</div>
                @endif
                @if($msg->file_path)
                    @if($msg->isImage())
                        <img src="{{ $msg->fileUrl() }}" alt="{{ e($msg->file_name) }}" style="max-width:100%;max-height:240px;border-radius:8px;display:block;margin-top:6px;cursor:pointer;" onclick="openLightbox(this.src,this.alt,{{ $msg->id }})">
                        @if($msg->imageComments->isNotEmpty())
                        <div class="inline-img-comments" id="inline-img-comments-{{ $msg->id }}">
                            @foreach($msg->imageComments as $c)
                            <div class="iic-item{{ $c->user_id === $user->id ? ' mine' : '' }}" data-comment-id="{{ $c->id }}">
                                <div class="iic-hdr">
                                    <span class="iic-name">{{ $c->displayName() }}</span>
                                    <span class="iic-time">{{ $c->created_at->format('m/d H:i') }}</span>
                                </div>
                                <div class="iic-body">{{ $c->content }}</div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="inline-img-comments" id="inline-img-comments-{{ $msg->id }}" style="display:none;"></div>
                        @endif
                    @else
                        <a href="{{ $msg->fileUrl() }}" download="{{ $msg->file_name }}" target="_blank" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;margin-top:6px;text-decoration:none;background:{{ $isMe ? 'rgba(255,255,255,.18)' : 'var(--t50)' }};color:{{ $isMe ? '#fff' : '#18181b' }};">
                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <div><div style="font-size:12.5px;font-weight:600;">{{ $msg->file_name }}</div><div style="font-size:11px;opacity:.7;">{{ $msg->formattedSize() }}</div></div>
                        </a>
                    @endif
                @endif
            </div>
            @if($isMe)
            <span style="font-size:11px;color:#a1a1aa;margin-top:3px;">{{ $msg->created_at->format('H:i') }}</span>
            @endif
        </div>
        @endforeach
        <div id="msg-end"></div>
    </div>

    @include('partials._lightbox')

    {{-- 입력 영역 --}}
    @if(!$isClosed)
    <div style="flex-shrink:0;background:#fff;border:1px solid var(--t100);border-radius:10px;overflow:hidden;margin-top:4px;">
        <form id="reply-form" data-url="{{ route('inquiry.reply', $conversation) }}" data-upload-url="{{ route('inquiry.upload-image') }}">
            @csrf
            <input type="hidden" id="reply-input" name="message">
            <div class="reply-quill" id="reply-editor-wrap"><div id="reply-editor"></div></div>
            {{-- 파일 미리보기 바 --}}
            <div id="inq-file-bar" class="inq-file-bar">
                <img id="inq-file-thumb" class="inq-file-thumb" src="" alt="">
                <svg id="inq-file-icon" class="inq-file-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span id="inq-file-name" style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                <span id="inq-file-size" style="color:#a1a1aa;flex-shrink:0;"></span>
                <button type="button" onclick="clearInqFile()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:16px;line-height:1;padding:0 2px;" title="{{ __('messages.remove_attach') }}">&times;</button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 10px;border-top:1px solid var(--t100);background:var(--t50);">
                <div style="display:flex;align-items:center;gap:8px;">
                    <label for="inq-file-input" class="inq-attach-btn" title="{{ __('messages.attach_file') }}">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </label>
                    <input id="inq-file-input" type="file" style="display:none;" onchange="onInqFileSelect(this)">
                    <span style="font-size:11px;color:#a1a1aa;">{{ __('messages.ctrl_enter_hint') }}</span>
                </div>
                <button type="submit" id="send-btn"
                        style="display:flex;align-items:center;gap:4px;padding:6px 14px;background:var(--t500);color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    {{ __('common.send') }}
                </button>
            </div>
        </form>
    </div>
    @else
    <div style="flex-shrink:0;background:#f4f4f5;border:1px solid #e4e4e7;border-radius:10px;padding:14px;text-align:center;color:#71717a;font-size:13px;">
        {{ __('messages.inquiry_closed_msg') }}
    </div>
    @endif

</div>
@endsection

@section('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
@if($isPopup)
// 팝업 iframe 안에서는 부모 창의 라이트박스를 사용
window.openLightbox = async function(src, alt, msgId) {
    if (window.parent && typeof window.parent.openLightbox === 'function') {
        window.parent.openLightbox(src, alt, msgId);
    }
};
@endif
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
const convId = {{ $conversation->id }};
const myId   = {{ $user->id }};
const convCh = 'conversation.' + convId;
window.OPEN_CONV_ID = convId;

document.getElementById('msg-end')?.scrollIntoView({ behavior: 'instant' });

// ── Quill 답장 에디터 ─────────────────────────────────────────────
const replyQuill = new Quill('#reply-editor', {
    theme: 'snow',
    placeholder: '{{ __("messages.message_placeholder") }}',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image', 'clean'],
        ],
        keyboard: {
            bindings: {
                ctrlEnter: { key: 13, ctrlKey: true, handler() { document.getElementById('reply-form').requestSubmit(); } },
            },
        },
    },
});

// ── 이미지 업로드 (툴바 버튼 + 붙여넣기) ─────────────────────────
const form = document.getElementById('reply-form');
setupQuillImageUpload(replyQuill, form.dataset.uploadUrl);

async function setupQuillImageUpload(quill, uploadUrl) {
    quill.getModule('toolbar').addHandler('image', () => {
        const inp = document.createElement('input');
        inp.type = 'file'; inp.accept = 'image/*';
        inp.onchange = () => { if (inp.files[0]) _uploadToQuill(inp.files[0], quill, uploadUrl); };
        inp.click();
    });
    quill.root.addEventListener('paste', e => {
        const imgItem = [...(e.clipboardData?.items || [])].find(it => it.type.startsWith('image/'));
        if (!imgItem) return;
        e.preventDefault();
        _uploadToQuill(imgItem.getAsFile(), quill, uploadUrl);
    });
}

async function _uploadToQuill(file, quill, uploadUrl) {
    if (!file) return;
    const fd = new FormData();
    fd.append('image', file);
    fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.url) return;
        const range = quill.getSelection(true) || { index: quill.getLength() };
        quill.insertEmbed(range.index, 'image', data.url);
        quill.setSelection(range.index + 1);
    })
    .catch(() => alert('{{ __("messages.image_upload_fail") }}'));
}

// ── 파일 첨부 ─────────────────────────────────────────────────────
async function onInqFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const bar   = document.getElementById('inq-file-bar');
    const thumb = document.getElementById('inq-file-thumb');
    const icon  = document.getElementById('inq-file-icon');
    document.getElementById('inq-file-name').textContent = file.name;
    const kb = file.size / 1024;
    document.getElementById('inq-file-size').textContent = kb >= 1024 ? (kb/1024).toFixed(1)+' MB' : Math.round(kb)+' KB';
    bar.style.display = 'flex';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { thumb.src = e.target.result; thumb.style.display = 'block'; };
        reader.readAsDataURL(file);
        icon.style.display = 'none';
    } else {
        thumb.style.display = 'none'; icon.style.display = 'block';
    }
}

async function clearInqFile() {
    document.getElementById('inq-file-input').value = '';
    document.getElementById('inq-file-bar').style.display = 'none';
    const thumb = document.getElementById('inq-file-thumb');
    thumb.src = ''; thumb.style.display = 'none';
    document.getElementById('inq-file-icon').style.display = 'block';
}

// ── AJAX 전송 (FormData) ──────────────────────────────────────────
const sendBtn = document.getElementById('send-btn');

if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const html   = replyQuill.root.innerHTML;
        const text   = replyQuill.getText().trim();
        const hasImg = html.includes('<img');
        if (!text && !hasImg) { replyQuill.focus(); return; }

        const fileInput = document.getElementById('inq-file-input');
        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('message', html);
        if (fileInput.files[0]) fd.append('file', fileInput.files[0]);

        sendBtn.disabled = true;
        fetch(form.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-Socket-ID': window.Echo?.socketId?.() ?? '',
            },
            body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            replyQuill.setContents([]);
            clearInqFile();
            appendMessage({
                body: html,
                fileHtml: fileAttachHtml(data.file_url, data.file_name, data.is_image, data.formatted_size, true, data.id || 0),
                sender: { id: myId, name: '{{ __("messages.me") }}' },
                created_at: new Date().toISOString(),
                isMe: true,
            });
        })
        .finally(() => { sendBtn.disabled = false; replyQuill.focus(); });
    });
}

// ── 버블 렌더링 헬퍼 ─────────────────────────────────────────────
function renderBody(body) {
    if (body && body.trimStart().startsWith('<')) return `<div class="rte-body">${body}</div>`;
    return `<div style="white-space:pre-wrap;">${linkify(body || '')}</div>`;
}

function fileAttachHtml(fileUrl, fileName, isImage, formattedSize, isMine, msgId) {
    if (!fileUrl || !fileName) return '';
    if (isImage) {
        const clickFn = msgId ? `openLightbox(this.src,this.alt,${msgId})` : `window.open(this.src,'_blank')`;
        const iicDiv = msgId ? `<div class="inline-img-comments" id="inline-img-comments-${msgId}" style="display:none;"></div>` : '';
        return `<img src="${fileUrl}" alt="${escHtml(fileName)}" style="max-width:100%;max-height:240px;border-radius:8px;display:block;margin-top:6px;cursor:pointer;" onclick="${clickFn}">${iicDiv}`;
    }
    const bg   = isMine ? 'rgba(255,255,255,.18)' : '#f8fafc';
    const color = isMine ? '#fff' : '#18181b';
    return `<a href="${fileUrl}" download="${escHtml(fileName)}" target="_blank" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;margin-top:6px;text-decoration:none;background:${bg};color:${color};">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <div><div style="font-size:12.5px;font-weight:600;">${escHtml(fileName)}</div>${formattedSize ? `<div style="font-size:11px;opacity:.7;">${formattedSize}</div>` : ''}</div>
    </a>`;
}

async function appendMessage({ body, fileHtml = '', sender, created_at, isMe }) {
    const area = document.getElementById('msg-area');
    const end  = document.getElementById('msg-end');
    const time = new Date(created_at).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit', hour12: false });

    const wrap = document.createElement('div');
    wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isMe ? 'flex-end' : 'flex-start'}`;

    const base = 'font-size:14px;line-height:1.6;word-break:break-word;max-width:75%;padding:10px 14px;';
    if (!isMe) {
        const init = (sender.name || '?').charAt(0);
        wrap.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <div style="width:26px;height:26px;border-radius:50%;background:var(--t500);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;">${escHtml(init)}</div>
                <span style="font-size:12px;font-weight:600;color:#52525b;">${escHtml(sender.name)}</span>
                <span style="font-size:11px;color:#a1a1aa;">${time}</span>
            </div>
            <div style="${base}border-radius:4px 14px 14px 14px;background:#fff;color:#18181b;border:1px solid var(--t100);">${renderBody(body)}${fileHtml}</div>`;
    } else {
        wrap.innerHTML = `
            <div style="${base}border-radius:14px 4px 14px 14px;background:var(--t500);color:#fff;">${renderBody(body)}${fileHtml}</div>
            <span style="font-size:11px;color:#a1a1aa;margin-top:3px;">${time}</span>`;
    }

    area.insertBefore(wrap, end);
    end.scrollIntoView({ behavior: 'smooth' });
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function linkify(text) {
    return escHtml(text).replace(/(https?:\/\/[^\s<>"']+)/g, url =>
        `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">${url}</a>`
    );
}

// ── 인라인 이미지 의견 헬퍼 ───────────────────────────────────────
async function addInlineImgComment(msgId, comment) {
    const container = document.getElementById('inline-img-comments-' + msgId);
    if (!container) return;
    const isMine = comment.is_mine !== undefined ? comment.is_mine : (comment.user_id === myId);
    const div = document.createElement('div');
    div.className = 'iic-item' + (isMine ? ' mine' : '');
    div.dataset.commentId = comment.id;
    div.innerHTML = `<div class="iic-hdr"><span class="iic-name">${escHtml(comment.user_name)}</span><span class="iic-time">${escHtml(comment.created_at)}</span></div><div class="iic-body">${escHtml(comment.content)}</div>`;
    container.style.display = 'flex';
    container.appendChild(div);
}

async function removeInlineImgComment(msgId, commentId) {
    const container = document.getElementById('inline-img-comments-' + msgId);
    if (!container) return;
    container.querySelector('[data-comment-id="' + commentId + '"]')?.remove();
    if (!container.querySelector('.iic-item')) container.style.display = 'none';
}

// ── 라이트박스 이벤트 수신 (비팝업 모드) ─────────────────────────
window.addEventListener('lbCommentAdded', async function(e) {
    addInlineImgComment(e.detail.msgId, e.detail.comment);
});
window.addEventListener('lbCommentDeleted', async function(e) {
    removeInlineImgComment(e.detail.msgId, e.detail.commentId);
});

// ── postMessage 수신 (팝업 iframe 모드: 부모 창 라이트박스 → iframe) ──
@if($isPopup)
window.addEventListener('message', async function(e) {
    if (!e.data || !e.data.type) return;
    if (e.data.type === 'lbCommentAdded') addInlineImgComment(e.data.msgId, e.data.comment);
    else if (e.data.type === 'lbCommentDeleted') removeInlineImgComment(e.data.msgId, e.data.commentId);
});
@endif

// ── Pusher 실시간 수신 ─────────────────────────────────────────────
async function setupConvEcho() {
    window.Echo.private(convCh)
    .listen('.MessageSent', async function(data) {
        const isAdmin = data.body && data.body.startsWith('[관리자');
        if (!isAdmin && data.sender_id === myId) return;
        const body = isAdmin ? data.body.replace(/^\[관리자 .+?\] /, '') : data.body;
        const name = isAdmin ? '{{ __("messages.admin") }}' : data.sender_name;
        appendMessage({
            body,
            fileHtml: fileAttachHtml(data.file_url, data.file_name, data.is_image, data.formatted_size, false, data.id || 0),
            sender: { id: data.sender_id, name },
            created_at: data.created_at || new Date().toISOString(),
            isMe: false,
        });
    })
    .listen('.ImageCommentPosted', async function(data) {
        addInlineImgComment(data.message_id, {
            id:         data.comment.id,
            content:    data.comment.content,
            user_name:  data.comment.user_name,
            user_id:    data.comment.user_id,
            created_at: data.comment.created_at,
            is_mine:    data.comment.user_id === myId,
        });
    });
}
if (window.Echo) { setupConvEcho(); }
else { window.addEventListener('echoReady', setupConvEcho, { once: true }); }
</script>
@endsection
