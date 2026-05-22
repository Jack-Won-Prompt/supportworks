@extends('layouts.app')

@section('title', __('messages.inquiry'))

@push('styles')
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
.inq-quill .ql-toolbar{border:none;border-bottom:1px solid var(--t100);padding:5px 8px;background:var(--t50);border-radius:8px 8px 0 0;}
.inq-quill .ql-container{border:none;font-family:inherit;}
.inq-quill{border:1px solid var(--t100);border-radius:8px;background:var(--t50);transition:border-color .15s,background .15s;}
.inq-quill.ql-focus{border-color:var(--t500);background:#fff;}
.inq-quill .ql-editor{min-height:220px;max-height:400px;overflow-y:auto;padding:9px 12px;font-size:14px;color:#18181b;line-height:1.6;}
.inq-quill .ql-editor.ql-blank::before{font-style:normal;color:#a1a1aa;}
.inq-quill .ql-toolbar .ql-stroke{stroke:#71717a;}
.inq-quill .ql-toolbar .ql-fill{fill:#71717a;}
.inq-quill .ql-toolbar button:hover .ql-stroke,.inq-quill .ql-toolbar button.ql-active .ql-stroke{stroke:var(--t600);}
.inq-quill .ql-toolbar button:hover .ql-fill,.inq-quill .ql-toolbar button.ql-active .ql-fill{fill:var(--t600);}
</style>
@endpush

@section('header-actions')
<button onclick="document.getElementById('new-inquiry-modal').style.display='flex'" style="display:flex;align-items:center;gap:4px;padding:5px 14px;background:var(--t500);color:#fff;border:none;border-radius:9999px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    {{ __('messages.new_inquiry') }}
</button>
@endsection

@section('content')
<div>

    {{-- 상태 요약 --}}
    <div style="display:flex;gap:12px;margin-bottom:20px;">
        <div style="flex:1;background:#fff;border:1px solid var(--t100);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:9px;background:var(--t100);display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="var(--tText)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#18181b;line-height:1;">{{ $inquiries->count() }}</div>
                <div style="font-size:12px;color:#a1a1aa;margin-top:1px;">{{ __('messages.total_inquiries') }}</div>
            </div>
        </div>
        <div style="flex:1;background:#fff;border:1px solid var(--t100);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:9px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#18181b;line-height:1;">{{ $openCount }}</div>
                <div style="font-size:12px;color:#a1a1aa;margin-top:1px;">{{ __('messages.in_progress') }}</div>
            </div>
        </div>
        <div style="flex:1;background:#fff;border:1px solid var(--t100);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:9px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div style="font-size:22px;font-weight:800;color:#18181b;line-height:1;">{{ $inquiries->where('status','closed')->count() }}</div>
                <div style="font-size:12px;color:#a1a1aa;margin-top:1px;">{{ __('messages.resolved') }}</div>
            </div>
        </div>
    </div>

    {{-- 문의 목록 --}}
    @forelse($inquiries as $inq)
    @php
        $unread = $inq->unreadCount($user->id);
        $last   = $inq->lastMessage;
        $status = $inq->status ?? 'open';
        $statusLabel = match($status) { 'open'=>__('messages.status_waiting'), 'active'=>__('messages.status_answering'), 'closed'=>__('messages.status_closed'), default=>__('messages.status_waiting') };
        $statusColor = match($status) { 'open'=>'#d97706,#fef3c7', 'active'=>'var(--tText),var(--t100)', 'closed'=>'#6b7280,#f4f4f5', default=>'#d97706,#fef3c7' };
        [$sc, $sb] = explode(',', $statusColor);
    @endphp
    <a href="{{ route('inquiry.show', $inq) }}" onclick="event.preventDefault();openInqView(this.href)" style="display:flex;align-items:center;gap:12px;background:#fff;border:1px solid var(--t100);border-radius:10px;padding:14px 16px;margin-bottom:8px;text-decoration:none;transition:border-color .12s,box-shadow .12s;" onmouseover="this.style.borderColor='var(--t300)';this.style.boxShadow='0 2px 12px rgba(0,0,0,.06)'" onmouseout="this.style.borderColor='var(--t100)';this.style.boxShadow='none'">
        {{-- 아이콘 --}}
        <div style="width:42px;height:42px;border-radius:10px;background:var(--t100);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="20" height="20" fill="none" stroke="var(--tText)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
        </div>
        {{-- 내용 --}}
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                <span style="font-size:14px;font-weight:700;color:#18181b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px;">{{ $inq->name }}</span>
                <span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:9999px;background:{{ $sb }};color:{{ $sc }};flex-shrink:0;">{{ $statusLabel }}</span>
                @if($unread > 0)
                <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:9999px;background:var(--t500);color:#fff;flex-shrink:0;">{{ $unread }}</span>
                @endif
            </div>
            <div style="font-size:12px;color:#a1a1aa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                @if($last)
                    <span style="color:#6b7280;">{{ $last->sender->name }}:</span>
                    @php
                        $preview = preg_replace('/^\[관리자 .+?\] /', '', $last->body);
                        $preview = trim(strip_tags($preview));
                        if ($preview === '') $preview = $last->file_name ? '📎 '.$last->file_name : __('messages.image_placeholder');
                    @endphp
                    {{ Str::limit($preview, 60) }}
                @else
                    {{ __('messages.no_messages_yet') }}
                @endif
            </div>
        </div>
        {{-- 시간 --}}
        <div style="font-size:11px;color:#a1a1aa;flex-shrink:0;text-align:right;">
            {{ $inq->updated_at->diffForHumans() }}
        </div>
        <svg width="14" height="14" fill="none" stroke="#d4d4d8" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    @empty
    <div style="text-align:center;padding:60px 20px;background:#fff;border:1px solid var(--t100);border-radius:12px;">
        <div style="font-size:3rem;margin-bottom:12px;">💬</div>
        <div style="font-size:15px;font-weight:700;color:#18181b;margin-bottom:6px;">{{ __('messages.no_inquiry_yet') }}</div>
        <div style="font-size:13px;color:#a1a1aa;margin-bottom:20px;">{{ __('messages.no_inquiry_sub') }}</div>
        <button onclick="document.getElementById('new-inquiry-modal').style.display='flex'" style="padding:8px 20px;background:var(--t500);color:#fff;border:none;border-radius:9999px;font-size:13px;font-weight:700;cursor:pointer;">{{ __('messages.write_new_inquiry') }}</button>
    </div>
    @endforelse
</div>

{{-- 문의 보기 팝업 --}}
<div id="inq-view-popup" onclick="if(event.target===this)closeInqView()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.58);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;">
    <div style="width:min(1000px,94vw);height:90vh;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.4);display:flex;flex-direction:column;">
        <iframe id="inq-view-frame" src="" style="flex:1;width:100%;border:none;"></iframe>
    </div>
</div>

{{-- 이미지 리뷰 라이트박스 (팝업 iframe에서 위임받아 표시) --}}
@include('partials._lightbox')

{{-- 새 문의 모달 --}}
<div id="new-inquiry-modal" onclick="if(event.target===this)this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:100%;max-width:660px;max-height:90vh;overflow-y:auto;box-shadow:0 16px 48px rgba(0,0,0,.2);">
        <div style="font-size:16px;font-weight:700;color:#18181b;margin-bottom:16px;">{{ __('messages.new_inquiry_write') }}</div>
        <form method="POST" action="{{ route('inquiry.store') }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#52525b;margin-bottom:5px;">{{ __('messages.subject') }}</label>
                <input type="text" name="subject" required maxlength="200" placeholder="{{ __('messages.subject_placeholder') }}"
                       style="width:100%;padding:9px 12px;border:1px solid var(--t100);border-radius:8px;font-size:14px;color:#18181b;outline:none;background:var(--t50);transition:border-color .15s;font-family:inherit;"
                       onfocus="this.style.borderColor='var(--t500)';this.style.background='#fff'" onblur="this.style.borderColor='var(--t100)';this.style.background='var(--t50)'">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#52525b;margin-bottom:5px;">{{ __('messages.inquiry_content') }}</label>
                <div class="inq-quill" id="inq-editor-wrap"><div id="inq-editor"></div></div>
                <input type="hidden" name="message" id="inq-message-val">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('new-inquiry-modal').style.display='none'"
                        style="padding:8px 16px;border:1px solid #d4d4d8;background:transparent;color:#71717a;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit"
                        style="padding:8px 20px;background:var(--t500);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('messages.inquiry_register') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const inqQuill = new Quill('#inq-editor', {
    theme: 'snow',
    placeholder: '{{ __("messages.inquiry_content_ph") }}',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'clean'],
        ],
    },
});

const inqWrap = document.getElementById('inq-editor-wrap');
inqQuill.on('selection-change', range => {
    if (range) { inqWrap.classList.add('ql-focus'); }
    else        { inqWrap.classList.remove('ql-focus'); }
});

document.querySelector('#new-inquiry-modal form').addEventListener('submit', function(e) {
    if (!inqQuill.getText().trim()) {
        e.preventDefault();
        inqWrap.style.borderColor = '#ef4444';
        inqQuill.focus();
        return;
    }
    document.getElementById('inq-message-val').value = inqQuill.root.innerHTML;
});

function openInqView(url) {
    const sep = url.includes('?') ? '&' : '?';
    document.getElementById('inq-view-frame').src = url + sep + 'popup=1';
    const p = document.getElementById('inq-view-popup');
    p.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeInqView() {
    document.getElementById('inq-view-popup').style.display = 'none';
    document.getElementById('inq-view-frame').src = '';
    document.body.style.overflow = '';
}

@if(session('new_inquiry_id'))
(function() {
    const url = '{{ route("inquiry.show", session("new_inquiry_id")) }}';
    setTimeout(() => openInqView(url), 200);
})();
@endif

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('img-lightbox')?.classList.contains('open')) { closeLightbox(); return; }
        if (document.getElementById('inq-view-popup').style.display !== 'none') { closeInqView(); return; }
        document.getElementById('new-inquiry-modal').style.display = 'none';
    }
});

// 라이트박스 의견 이벤트 → iframe으로 relay
window.addEventListener('lbCommentAdded', function(e) {
    const frame = document.getElementById('inq-view-frame');
    if (frame && frame.contentWindow) {
        frame.contentWindow.postMessage({ type: 'lbCommentAdded', msgId: e.detail.msgId, comment: e.detail.comment }, '*');
    }
});
window.addEventListener('lbCommentDeleted', function(e) {
    const frame = document.getElementById('inq-view-frame');
    if (frame && frame.contentWindow) {
        frame.contentWindow.postMessage({ type: 'lbCommentDeleted', msgId: e.detail.msgId, commentId: e.detail.commentId }, '*');
    }
});
</script>
@endsection
