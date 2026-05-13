@extends('layouts.admin')

@section('title', __('admin.inquiry_detail'))

@section('header-actions')
@if($conversation->status !== 'closed')
<form action="{{ route('admin.inquiries.close', $conversation) }}" method="POST" style="display:inline;">
    @csrf
    <button type="submit" class="btn-danger" style="padding:6px 12px;font-size:12px;">{{ __('admin.inq_close_btn') }}</button>
</form>
@else
<form action="{{ route('admin.inquiries.reopen', $conversation) }}" method="POST" style="display:inline;">
    @csrf
    <button type="submit" class="btn-secondary" style="padding:6px 12px;font-size:12px;">{{ __('admin.inq_reopen_btn') }}</button>
</form>
@endif
<a href="{{ route('admin.inquiries.index') }}" class="btn-secondary" style="padding:6px 12px;font-size:12px;">{{ __('admin.inq_back_list') }}</a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
/* 라이트박스 공유 CSS 변수 (admin 레이아웃은 --t* 미정의) */
:root{--t50:#f5f3ff;--t100:#ede9fe;--t200:#ddd6fe;--t300:#c4b5fd;--t400:#a78bfa;--t500:#8b5cf6;--t600:#7c3aed;--t700:#6d28d9;--tText:#6d5ce7;--tBg:#f5f3ff;}
</style>
<style>
.adm-quill .ql-toolbar{border:none;border-bottom:1px solid #e2e8f0;padding:4px 8px;background:#f8fafc;border-radius:8px 8px 0 0;}
.adm-quill .ql-container{border:none;font-family:inherit;}
.adm-quill{border:1px solid #e2e8f0;border-radius:8px;transition:border-color .15s;}
.adm-quill.focused{border-color:#6366f1;}
.adm-quill .ql-editor{min-height:60px;max-height:160px;overflow-y:auto;padding:8px 12px;font-size:13px;color:#1e293b;line-height:1.6;}
.adm-quill .ql-editor.ql-blank::before{font-style:normal;color:#94a3b8;}
.adm-quill .ql-toolbar .ql-stroke{stroke:#64748b;}
.adm-quill .ql-toolbar .ql-fill{fill:#64748b;}
.adm-quill .ql-toolbar button:hover .ql-stroke,.adm-quill .ql-toolbar button.ql-active .ql-stroke{stroke:#6366f1;}
.adm-quill .ql-toolbar button:hover .ql-fill,.adm-quill .ql-toolbar button.ql-active .ql-fill{fill:#6366f1;}
.adm-file-bar{display:none;align-items:center;gap:8px;padding:5px 10px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:12px;color:#475569;}
.adm-file-thumb{width:26px;height:26px;object-fit:cover;border-radius:3px;display:none;}
.adm-attach-label{display:flex;align-items:center;justify-content:center;width:28px;height:28px;border:1.5px solid #e2e8f0;border-radius:6px;cursor:pointer;color:#94a3b8;transition:all .15s;}
.adm-attach-label:hover{border-color:#6366f1;color:#6366f1;}
/* 인라인 이미지 의견 */
.inline-img-comments{margin-top:6px;display:flex;flex-direction:column;gap:3px;}
.iic-item{padding:5px 8px;border-radius:6px;font-size:11px;line-height:1.45;background:rgba(0,0,0,.06);}
.iic-hdr{display:flex;align-items:center;gap:5px;margin-bottom:1px;}
.iic-name{font-weight:700;font-size:11px;}
.iic-time{font-size:10px;opacity:.65;}
.iic-body{white-space:pre-wrap;word-break:break-word;}
</style>
@endpush

@section('content')
<style>
.msg-bubble-wrap { position:relative; display:inline-block; max-width:100%; }
.rte-body img { max-width:100%; border-radius:6px; margin:3px 0; display:block; }
.msg-ai-btn {
    display:none; position:absolute; top:-8px; right:-8px; z-index:10;
    align-items:center; gap:3px; padding:2px 7px 2px 5px;
    background:rgba(99,102,241,.88); backdrop-filter:blur(4px);
    color:#fff; border:none; border-radius:6px;
    font-size:10.5px; font-weight:600; cursor:pointer; white-space:nowrap;
    font-family:inherit; transition:background .12s;
}
.msg-ai-btn:hover { background:rgba(99,102,241,1); }
.inq-msg-row:hover .msg-ai-btn { display:inline-flex; }
#ai-analysis-panel {
    display:none; position:fixed; z-index:500; width:300px;
    background:rgba(255,255,255,.97); backdrop-filter:blur(6px);
    border:1px solid #ddd6fe; border-radius:14px;
    box-shadow:0 8px 32px rgba(109,92,231,.18), 0 2px 8px rgba(0,0,0,.06);
    padding:13px 15px; max-height:240px; overflow-y:auto;
}
#ai-analysis-panel.visible { display:block; }
</style>
@php
    $user = $conversation->firstMessage?->sender ?? $conversation->participants->first();
    $badgeClass = match($conversation->status) { 'open'=>'badge-red', 'active'=>'badge-blue', default=>'badge-gray' };
    $badgeText  = match($conversation->status) { 'open'=>__('admin.inq_status_open'), 'active'=>__('admin.inq_status_active'), default=>__('admin.inq_status_closed') };
@endphp

<div style="display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:start;">

    {{-- 채팅 영역 --}}
    <div class="admin-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ $user ? mb_substr($user->name,0,1) : '?' }}
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:#1e293b;">{{ $user?->name ?? __('admin.unknown') }}</p>
                <p style="font-size:11px;color:#94a3b8;">{{ $user?->email }}</p>
            </div>
            <span class="badge {{ $badgeClass }}" style="margin-left:auto;">{{ $badgeText }}</span>
        </div>

        {{-- 메시지 목록 --}}
        <div id="chat-messages" style="height:460px;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f8fafc;">
            @foreach($conversation->messages as $msg)
            @php
                $isAdmin = str_starts_with($msg->body, '[관리자');
                $time = $msg->created_at->format('H:i');
                $displayBody = $isAdmin ? preg_replace('/^\[관리자 .+?\] /', '', $msg->body) : $msg->body;
                $allowedTags = ['p','br','strong','em','b','i','ul','ol','li','a','span','h1','h2','h3','blockquote','img'];
                $isHtmlMsg   = str_starts_with(ltrim($displayBody), '<');
            @endphp
            <div class="inq-msg-row" style="display:flex;{{ $isAdmin ? 'flex-direction:row-reverse;' : '' }}align-items:flex-end;gap:8px;">
                @if(!$isAdmin)
                <div style="width:28px;height:28px;border-radius:7px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#64748b;flex-shrink:0;">
                    {{ $msg->sender ? mb_substr($msg->sender->name,0,1) : '?' }}
                </div>
                @endif
                <div style="max-width:72%;">
                    <div class="msg-bubble-wrap">
                        <div style="padding:10px 14px;border-radius:{{ $isAdmin ? '12px 4px 12px 12px' : '4px 12px 12px 12px' }};font-size:13px;line-height:1.55;
                            {{ $isAdmin ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;' : 'background:#fff;color:#1e293b;border:1px solid #e2e8f0;' }}">
                            @if($isHtmlMsg)
                                <div class="rte-body">{!! strip_tags($displayBody, $allowedTags) !!}</div>
                            @else
                                <div style="white-space:pre-wrap;font-size:13px;line-height:1.55;">{!! preg_replace('/(https?:\/\/[^\s<>"\']+)/', '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">$1</a>', e($displayBody)) !!}</div>
                            @endif
                            @if($msg->file_path)
                                @if($msg->isImage())
                                    <img src="{{ $msg->fileUrl() }}" alt="{{ e($msg->file_name) }}" style="max-width:100%;max-height:220px;border-radius:7px;display:block;margin-top:6px;cursor:pointer;" onclick="openLightbox(this.src,this.alt,{{ $msg->id }})">
                                    @if($msg->imageComments->isNotEmpty())
                                    <div class="inline-img-comments" id="inline-img-comments-{{ $msg->id }}">
                                        @foreach($msg->imageComments as $c)
                                        <div class="iic-item" data-comment-id="{{ $c->id }}">
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
                                    <a href="{{ $msg->fileUrl() }}" download="{{ $msg->file_name }}" target="_blank" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:7px;margin-top:6px;text-decoration:none;background:{{ $isAdmin ? 'rgba(255,255,255,.18)' : '#f1f5f9' }};color:{{ $isAdmin ? '#fff' : '#1e293b' }};">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <div><div style="font-size:12px;font-weight:600;">{{ $msg->file_name }}</div><div style="font-size:11px;opacity:.75;">{{ $msg->formattedSize() }}</div></div>
                                    </a>
                                @endif
                            @endif
                        </div>
                        <button class="msg-ai-btn" type="button" onclick="analyzeMsg({{ $msg->id }}, this)">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            {{ __('admin.inq_ai_analysis') }}
                        </button>
                    </div>
                    <p style="font-size:10px;color:#94a3b8;margin-top:3px;{{ $isAdmin ? 'text-align:right;' : '' }}">
                        {{ $isAdmin ? __('admin.inq_admin_label') . ' · ' : ($msg->sender?->name . ' · ') }}{{ $time }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- 답변 폼 --}}
        @if($conversation->status !== 'closed')
        <div style="padding:12px 16px;border-top:1px solid #e2e8f0;">
            <form id="admin-reply-form" data-url="{{ route('admin.inquiries.reply', $conversation) }}" data-upload-url="{{ route('admin.inquiries.upload-image') }}">
                @csrf
                <input type="hidden" id="admin-reply-input" name="body">
                <div class="adm-quill" id="admin-quill-wrap"><div id="admin-quill-editor"></div></div>
                <div id="adm-file-bar" class="adm-file-bar">
                    <img id="adm-file-thumb" class="adm-file-thumb" src="" alt="">
                    <svg id="adm-file-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#94a3b8;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <span id="adm-file-name" style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></span>
                    <span id="adm-file-size" style="color:#94a3b8;flex-shrink:0;"></span>
                    <button type="button" onclick="clearAdmFile()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:15px;line-height:1;padding:0 2px;">&times;</button>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <label for="adm-file-input" class="adm-attach-label" title="{{ __('admin.inq_paste_available') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </label>
                        <input id="adm-file-input" type="file" style="display:none;" onchange="onAdmFileSelect(this)">
                        <span style="font-size:11px;color:#94a3b8;">{{ __('admin.inq_paste_available') }}</span>
                    </div>
                    <button id="admin-send-btn" type="submit" class="btn-primary" style="padding:7px 16px;font-size:13px;">{{ __('admin.inq_send_btn') }}</button>
                </div>
            </form>
        </div>
        @else
        <div style="padding:12px 16px;text-align:center;font-size:12px;color:#94a3b8;background:#f8fafc;border-top:1px solid #e2e8f0;">{{ __('admin.inq_closed_notice') }}</div>
        @endif
        <div id="ai-analysis-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px;">
                <span style="font-size:11.5px;font-weight:700;color:#7c3aed;letter-spacing:.04em;">✦ {{ __('admin.inq_ai_analysis') }}</span>
                <button onclick="closeAiPanel()" style="cursor:pointer;color:#c4b5fd;font-size:18px;line-height:1;background:none;border:none;padding:0;">&times;</button>
            </div>
            <div id="ai-panel-body"></div>
        </div>
    </div>

    {{-- 사이드 정보 --}}
    <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="admin-card">
            <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">{{ __('admin.inq_user_info') }}</h4>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:12px;">
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.col_name') }}</span><span style="color:#1e293b;font-weight:500;">{{ $user?->name ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.col_email') }}</span><span style="color:#1e293b;">{{ $user?->email ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.col_company') }}</span><span style="color:#1e293b;">{{ $user?->company ?? '-' }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.inq_phone') }}</span><span style="color:#1e293b;">{{ $user?->phone ?? '-' }}</span></div>
            </div>
        </div>
        <div class="admin-card">
            <h4 style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">{{ __('admin.inq_inquiry_info') }}</h4>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:12px;">
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.col_status') }}</span><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.inq_charge') }}</span><span style="color:#1e293b;">{{ $conversation->assignedAdmin?->name ?? __('admin.unassigned') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.inq_msg_count') }}</span><span style="color:#1e293b;">{{ $conversation->messages->count() }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.inq_created_date') }}</span><span style="color:#1e293b;">{{ $conversation->created_at->format('m/d H:i') }}</span></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#94a3b8;">{{ __('admin.inq_recent_activity') }}</span><span style="color:#1e293b;">{{ $conversation->updated_at->format('m/d H:i') }}</span></div>
            </div>
        </div>
    </div>

</div>

@include('partials._lightbox')

@endsection

@section('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const ADMIN_B_STR = {
    analyzing:    '{{ __("admin.inq_analyzing") }}',
    upload_fail:  '{{ __("admin.inq_upload_fail") }}',
    send_fail:    '{{ __("admin.inq_send_fail2") }}',
    error:        '{{ __("admin.inq_error_occurred") }}',
    network_err:  '{{ __("admin.inq_network_error") }}',
    summary:      '{{ __("admin.inq_summary") }}',
    intent:       '{{ __("admin.inq_intent") }}',
    tone:         '{{ __("admin.inq_tone") }}',
    keyword:      '{{ __("admin.inq_keyword") }}',
    context:      '{{ __("admin.inq_context") }}',
    admin_label:  '{{ __("admin.inq_admin_label") }}',
};

const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
const convId   = {{ $conversation->id }};
const convCh   = 'conversation.' + convId;
const ADMIN_ID = {{ auth('admin')->user()->id }};
const sentIds  = new Set();
const recvIds  = new Set();
window.OPEN_CONV_ID = convId;

document.getElementById('chat-messages').scrollTop = 99999;

// ── Quill 에디터 초기화 ───────────────────────────────────────────
const adminQuill = new Quill('#admin-quill-editor', {
    theme: 'snow',
    placeholder: '{{ __("admin.inq_reply_placeholder") }}',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image', 'clean'],
        ],
        keyboard: {
            bindings: {
                enter: { key: 13, ctrlKey: true, handler() { document.getElementById('admin-reply-form').requestSubmit(); } },
            },
        },
    },
});

const admForm = document.getElementById('admin-reply-form');
adminQuill.on('selection-change', r => {
    document.getElementById('admin-quill-wrap').classList.toggle('focused', !!r);
});

// ── 이미지 업로드 ────────────────────────────────────────────────
setupQuillImgUpload(adminQuill, admForm.dataset.uploadUrl);

function setupQuillImgUpload(quill, uploadUrl) {
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

function _uploadToQuill(file, quill, uploadUrl) {
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
    .catch(() => alert(ADMIN_B_STR.upload_fail));
}

// ── 파일 첨부 ─────────────────────────────────────────────────────
function onAdmFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const thumb = document.getElementById('adm-file-thumb');
    const icon  = document.getElementById('adm-file-icon');
    document.getElementById('adm-file-name').textContent = file.name;
    const kb = file.size / 1024;
    document.getElementById('adm-file-size').textContent = kb >= 1024 ? (kb/1024).toFixed(1)+' MB' : Math.round(kb)+' KB';
    document.getElementById('adm-file-bar').style.display = 'flex';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { thumb.src = e.target.result; thumb.style.display = 'block'; };
        reader.readAsDataURL(file);
        icon.style.display = 'none';
    } else {
        thumb.style.display = 'none'; icon.style.display = 'block';
    }
}

function clearAdmFile() {
    document.getElementById('adm-file-input').value = '';
    document.getElementById('adm-file-bar').style.display = 'none';
    const thumb = document.getElementById('adm-file-thumb');
    thumb.src = ''; thumb.style.display = 'none';
    document.getElementById('adm-file-icon').style.display = 'block';
}

// ── AJAX 전송 ──────────────────────────────────────────────────────
const sendBtn = document.getElementById('admin-send-btn');

if (admForm) {
    admForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const html   = adminQuill.root.innerHTML;
        const text   = adminQuill.getText().trim();
        const hasImg = html.includes('<img');
        if (!text && !hasImg) { adminQuill.focus(); return; }

        const fileInput = document.getElementById('adm-file-input');
        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('body', html);
        if (fileInput.files[0]) fd.append('file', fileInput.files[0]);

        sendBtn.disabled = true;
        fetch(admForm.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: fd,
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { alert(data.error || ADMIN_B_STR.send_fail); return; }
            sentIds.add(data.id);
            adminQuill.setContents([]);
            clearAdmFile();
            appendAdminMessage(html, data.admin_name, data.created_at,
                data.file_url, data.file_name, data.is_image, data.formatted_size, data.id || 0);
        })
        .catch(() => alert(ADMIN_B_STR.send_fail))
        .finally(() => { sendBtn.disabled = false; adminQuill.focus(); });
    });
}

// ── Echo real-time ────────────────────────────────────────────────
function handleMsg(data) {
    if (recvIds.has(data.id)) return;
    recvIds.add(data.id);
    if (sentIds.has(data.id)) { sentIds.delete(data.id); return; }

    const isAdmin = data.body && data.body.startsWith('[관리자');
    const body    = isAdmin ? data.body.replace(/^\[관리자 .+?\] /, '') : data.body;
    const name    = isAdmin ? (ADMIN_B_STR.admin_label + ' · ' + (data.body.match(/^\[관리자 (.+?)\]/)?.[1] || '')) : data.sender_name;
    if (isAdmin) {
        appendAdminMessage(body, name, data.created_at || new Date().toISOString(),
            data.file_url, data.file_name, data.is_image, data.formatted_size, data.id || 0);
    } else {
        appendUserMessage(body, name, data.created_at || new Date().toISOString(),
            data.file_url, data.file_name, data.is_image, data.formatted_size, data.id || 0);
    }
}

function setupEcho() {
    window.Echo.private(convCh)
    .listen('.MessageSent', function(data) { handleMsg(data); })
    .listen('.ImageCommentPosted', function(data) {
        addInlineImgComment(data.message_id, {
            id:         data.comment.id,
            content:    data.comment.content,
            user_name:  data.comment.user_name,
            user_id:    data.comment.user_id,
            created_at: data.comment.created_at,
        });
    });
    window.Echo.private('admin.' + ADMIN_ID).listen('.MessageSent', function(data) {
        if (data.room_id !== convId) return;
        handleMsg(data);
    });
}
if (window.Echo) { setupEcho(); }
else { window.addEventListener('echoReady', setupEcho, { once: true }); }

// ── 인라인 이미지 의견 헬퍼 ───────────────────────────────────────
function addInlineImgComment(msgId, comment) {
    const container = document.getElementById('inline-img-comments-' + msgId);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'iic-item';
    div.dataset.commentId = comment.id;
    div.innerHTML = `<div class="iic-hdr"><span class="iic-name">${escHtml(comment.user_name)}</span><span class="iic-time">${escHtml(comment.created_at)}</span></div><div class="iic-body">${escHtml(comment.content)}</div>`;
    container.style.display = 'flex';
    container.appendChild(div);
}

function removeInlineImgComment(msgId, commentId) {
    const container = document.getElementById('inline-img-comments-' + msgId);
    if (!container) return;
    container.querySelector('[data-comment-id="' + commentId + '"]')?.remove();
    if (!container.querySelector('.iic-item')) container.style.display = 'none';
}

window.addEventListener('lbCommentAdded', function(e) {
    addInlineImgComment(e.detail.msgId, e.detail.comment);
});
window.addEventListener('lbCommentDeleted', function(e) {
    removeInlineImgComment(e.detail.msgId, e.detail.commentId);
});

// ── DOM helpers ───────────────────────────────────────────────────
function fileAttachHtml(fileUrl, fileName, isImage, formattedSize, isMine, msgId) {
    if (!fileUrl || !fileName) return '';
    if (isImage) {
        const clickFn = msgId ? `openLightbox(this.src,this.alt,${msgId})` : `window.open(this.src,'_blank')`;
        const iicDiv  = msgId ? `<div class="inline-img-comments" id="inline-img-comments-${msgId}" style="display:none;"></div>` : '';
        return `<img src="${fileUrl}" alt="${escHtml(fileName)}" style="max-width:100%;max-height:220px;border-radius:7px;display:block;margin-top:6px;cursor:pointer;" onclick="${clickFn}">${iicDiv}`;
    }
    const bg    = isMine ? 'rgba(255,255,255,.18)' : '#f1f5f9';
    const color = isMine ? '#fff' : '#1e293b';
    return `<a href="${fileUrl}" download="${escHtml(fileName)}" target="_blank" style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:7px;margin-top:6px;text-decoration:none;background:${bg};color:${color};">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <div><div style="font-size:12px;font-weight:600;">${escHtml(fileName)}</div>${formattedSize ? `<div style="font-size:11px;opacity:.75;">${formattedSize}</div>` : ''}</div>
    </a>`;
}

function appendAdminMessage(body, adminName, createdAt, fileUrl, fileName, isImage, formattedSize, msgId) {
    if (msgId && document.getElementById('msg-' + msgId)) return;
    const time = new Date(createdAt).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit', hour12: false });
    const wrap = document.createElement('div');
    wrap.className = 'inq-msg-row';
    if (msgId) wrap.id = 'msg-' + msgId;
    wrap.style.cssText = 'display:flex;flex-direction:row-reverse;align-items:flex-end;gap:8px;';
    const fHtml = fileAttachHtml(fileUrl, fileName, isImage, formattedSize, true, msgId);
    wrap.innerHTML = `
        <div style="max-width:72%;">
            <div style="padding:10px 14px;border-radius:12px 4px 12px 12px;font-size:13px;line-height:1.55;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;">${sanitizeMsg(body)}${fHtml}</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:3px;text-align:right;">${ADMIN_B_STR.admin_label} · ${escHtml(adminName)} · ${time}</p>
        </div>`;
    document.getElementById('chat-messages').appendChild(wrap);
    document.getElementById('chat-messages').scrollTop = 99999;
}

function appendUserMessage(body, senderName, createdAt, fileUrl, fileName, isImage, formattedSize, msgId) {
    if (msgId && document.getElementById('msg-' + msgId)) return;
    const time = new Date(createdAt).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit', hour12: false });
    const wrap = document.createElement('div');
    wrap.className = 'inq-msg-row';
    if (msgId) wrap.id = 'msg-' + msgId;
    wrap.style.cssText = 'display:flex;align-items:flex-end;gap:8px;';
    const fHtml = fileAttachHtml(fileUrl, fileName, isImage, formattedSize, false, msgId);
    wrap.innerHTML = `
        <div style="width:28px;height:28px;border-radius:7px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#64748b;flex-shrink:0;">${escHtml((senderName||'?').charAt(0))}</div>
        <div style="max-width:72%;">
            <div style="padding:10px 14px;border-radius:4px 12px 12px 12px;font-size:13px;line-height:1.55;background:#fff;color:#1e293b;border:1px solid #e2e8f0;">${sanitizeMsg(body)}${fHtml}</div>
            <p style="font-size:10px;color:#94a3b8;margin-top:3px;">${escHtml(senderName)} · ${time}</p>
        </div>`;
    document.getElementById('chat-messages').appendChild(wrap);
    document.getElementById('chat-messages').scrollTop = 99999;
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function linkify(text) {
    return escHtml(text).replace(/(https?:\/\/[^\s<>"']+)/g, url =>
        `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">${url}</a>`
    );
}

function sanitizeMsg(body) {
    if (!body || !body.trimStart().startsWith('<')) {
        return `<div style="white-space:pre-wrap;">${linkify(body || '')}</div>`;
    }
    const allowed = /^(p|br|strong|em|b|i|ul|ol|li|a|span|h[1-3]|blockquote|img)$/i;
    const tmp = document.createElement('div');
    tmp.innerHTML = body;
    tmp.querySelectorAll('*').forEach(el => {
        if (!allowed.test(el.tagName)) {
            el.replaceWith(document.createTextNode(el.textContent));
        } else if (el.parentNode) {
            [...el.attributes].forEach(attr => {
                if (!['href','target','src','alt'].includes(attr.name)) el.removeAttribute(attr.name);
            });
        }
    });
    return tmp.innerHTML || escHtml(body);
}

// ── 웍스 메시지 분석 ──────────────────────────────────────────
function analyzeMsg(msgId, btn) {
    const panel = document.getElementById('ai-analysis-panel');
    const body  = document.getElementById('ai-panel-body');
    if (!panel) return;

    if (panel.dataset.forMsg == msgId && panel.classList.contains('visible')) {
        closeAiPanel(); return;
    }

    const wrap    = btn.closest('.msg-bubble-wrap');
    const rect    = (wrap || btn).getBoundingClientRect();
    const chatBox = document.getElementById('chat-messages');
    const cr      = chatBox.getBoundingClientRect();
    const panelW  = 300;
    const panelH  = 240;

    let top = rect.top - panelH - 10;
    if (top < cr.top + 8) top = rect.bottom + 8;

    let left = rect.right - panelW;
    if (left < cr.left + 8) left = cr.left + 8;
    if (left + panelW > cr.right - 8) left = cr.right - panelW - 8;

    panel.style.top   = top + 'px';
    panel.style.left  = left + 'px';
    panel.style.right = 'auto';

    panel.dataset.forMsg = msgId;
    panel.classList.add('visible');
    body.innerHTML = `<span style="color:#94a3b8;font-size:11.5px;">${ADMIN_B_STR.analyzing}</span>`;
    btn.disabled = true;

    fetch('{{ route("admin.inquiries.analyze") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ message_id: msgId }),
    })
    .then(async r => {
        const json = await r.json().catch(() => ({ error: `HTTP ${r.status}` }));
        if (!r.ok && !json.error) json.error = json.message || `HTTP ${r.status}`;
        return json;
    })
    .then(data => {
        if (!data.ok) {
            body.innerHTML = `<span style="color:#dc2626;font-size:12px;">⚠ ${escHtml(data.error || data.message || ADMIN_B_STR.error)}</span>`;
            return;
        }
        const r = data.result;
        const kw  = Array.isArray(r.keywords) ? r.keywords.map(k => `<span style="background:#ddd6fe;color:#6d28d9;padding:1px 7px;border-radius:5px;font-size:11px;font-weight:600;">${escHtml(k)}</span>`).join('') : '';
        const ctx = r.context_note ? `<div style="display:flex;gap:6px;margin-bottom:5px;font-size:12px;"><span style="font-weight:700;color:#7c3aed;flex-shrink:0;min-width:38px;">${ADMIN_B_STR.context}</span><span style="color:#312e81;">${escHtml(r.context_note)}</span></div>` : '';
        const row = (l, v) => `<div style="display:flex;gap:6px;margin-bottom:5px;font-size:12px;"><span style="font-weight:700;color:#7c3aed;flex-shrink:0;min-width:38px;">${l}</span><span style="color:#312e81;line-height:1.5;">${v}</span></div>`;
        body.innerHTML = row(ADMIN_B_STR.summary, escHtml(r.summary||'-')) + row(ADMIN_B_STR.intent, escHtml(r.intent||'-')) + row(ADMIN_B_STR.tone, escHtml(r.tone||'-'))
            + `<div style="display:flex;gap:6px;margin-bottom:5px;font-size:12px;"><span style="font-weight:700;color:#7c3aed;flex-shrink:0;min-width:38px;">${ADMIN_B_STR.keyword}</span><div style="display:flex;gap:4px;flex-wrap:wrap;">${kw}</div></div>` + ctx;
    })
    .catch(() => { body.innerHTML = `<span style="color:#dc2626;font-size:12px;">${ADMIN_B_STR.network_err}</span>`; })
    .finally(() => { btn.disabled = false; });
}
function closeAiPanel() {
    const p = document.getElementById('ai-analysis-panel');
    if (p) { p.classList.remove('visible'); p.dataset.forMsg = ''; }
}
</script>
@endsection
