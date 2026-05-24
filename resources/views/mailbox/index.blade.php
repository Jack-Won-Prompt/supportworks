@extends('layouts.app')

@php
    $folderLabels = ['inbox' => '받은편지함', 'sent' => '보낸편지함', 'trash' => '휴지통'];
    $currentLabel = $folderLabels[$folder] ?? '메일';
@endphp

@section('title', '메일 · ' . $currentLabel)
@section('header-actions')@endsection

@section('content')
<div class="flex gap-4" style="margin:-20px -24px -24px;height:calc(100vh - 52px);">

    {{-- 좌측 네비 --}}
    <aside style="width:200px;flex-shrink:0;background:#fff;border-right:1px solid var(--color-border-default);padding:16px 12px;overflow-y:auto;">
        <button type="button" onclick="mbOpenCompose()"
           style="display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 14px;background:linear-gradient(135deg,var(--t500),var(--t700));color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(124,58,237,.25);margin-bottom:14px;width:100%;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            메일 작성
        </button>
        @php
            $navItems = [
                ['key'=>'inbox', 'route'=>'mailbox.inbox', 'label'=>'받은편지함', 'count'=>$counts['inbox'], 'badge'=>$counts['inbox_unread'], 'icon'=>'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['key'=>'sent',  'route'=>'mailbox.sent',  'label'=>'보낸편지함', 'count'=>$counts['sent'],  'badge'=>0, 'icon'=>'M12 19l9 2-9-18-9 18 9-2zm0 0v-8'],
                ['key'=>'trash', 'route'=>'mailbox.trash', 'label'=>'휴지통',     'count'=>$counts['trash'], 'badge'=>0, 'icon'=>'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
            ];
        @endphp
        @foreach($navItems as $it)
            <a href="{{ route($it['route']) }}"
               class="{{ $folder === $it['key'] ? 'is-active' : '' }}"
               style="display:flex;align-items:center;gap:8px;padding:8px 11px;border-radius:7px;font-size:13px;color:{{ $folder === $it['key'] ? 'var(--t700)' : 'var(--color-text-secondary)' }};background:{{ $folder === $it['key'] ? 'var(--t50)' : 'transparent' }};font-weight:{{ $folder === $it['key'] ? '700' : '500' }};text-decoration:none;margin-bottom:2px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $it['icon'] }}"/></svg>
                <span style="flex:1;">{{ $it['label'] }}</span>
                @if($it['badge'] > 0)
                    <span style="background:#ef4444;color:#fff;font-size:10.5px;font-weight:700;border-radius:10px;padding:1px 6px;">{{ $it['badge'] }}</span>
                @elseif($it['count'] > 0)
                    <span style="color:var(--color-text-tertiary);font-size:11px;">{{ $it['count'] }}</span>
                @endif
            </a>
        @endforeach
    </aside>

    {{-- 우측 리스트 --}}
    <main style="flex:1;min-width:0;display:flex;flex-direction:column;background:#fff;border-radius:0;">
        {{-- 상단바: 제목 + 검색 + 일괄 액션 --}}
        <div style="padding:14px 20px;border-bottom:1px solid var(--color-border-default);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <h1 style="font-size:16px;font-weight:700;color:var(--color-text-primary);margin:0;">{{ $currentLabel }}</h1>
            <span style="font-size:11.5px;color:var(--color-text-tertiary);">{{ $items->total() }}</span>

            <form method="GET" style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="folder" value="{{ $folder }}">
                <div style="position:relative;">
                    <svg style="position:absolute;left:9px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="제목·본문 검색"
                           style="padding:6px 10px 6px 28px;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;width:200px;outline:none;">
                </div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" title="시작일"
                       style="padding:5px 8px;border:1px solid #e4e4e7;border-radius:7px;font-size:12px;">
                <span style="font-size:11px;color:#9ca3af;">~</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" title="종료일"
                       style="padding:5px 8px;border:1px solid #e4e4e7;border-radius:7px;font-size:12px;">
                @if($folder === 'inbox')
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#52525b;cursor:pointer;">
                    <input type="checkbox" name="unread" value="1" {{ request('unread') ? 'checked' : '' }} onchange="this.form.submit()">
                    안 읽음
                </label>
                @endif
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#52525b;cursor:pointer;">
                    <input type="checkbox" name="has_attachment" value="1" {{ request('has_attachment') ? 'checked' : '' }} onchange="this.form.submit()">
                    첨부
                </label>
                <button type="submit" style="padding:6px 14px;background:#f4f4f5;color:#52525b;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">조회</button>
            </form>
        </div>

        {{-- 일괄 액션 바 --}}
        <div id="mb-bulk-bar" style="display:none;align-items:center;gap:8px;padding:10px 20px;border-bottom:1px solid var(--color-border-default);background:var(--t50);">
            <span style="font-size:12.5px;color:var(--t700);font-weight:600;"><span id="mb-bulk-count">0</span>개 선택됨</span>
            @if($folder === 'inbox')
                <button type="button" onclick="mbMarkReadSelected()" style="padding:5px 10px;background:#fff;border:1px solid var(--t300);color:var(--t700);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">읽음 처리</button>
                <button type="button" onclick="mbTrashSelected()" style="padding:5px 10px;background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">휴지통</button>
            @elseif($folder === 'sent')
                <button type="button" onclick="mbTrashSelected()" style="padding:5px 10px;background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">휴지통</button>
            @elseif($folder === 'trash')
                <button type="button" onclick="mbRestoreSelected()" style="padding:5px 10px;background:#fff;border:1px solid var(--t300);color:var(--t700);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">복원</button>
                <button type="button" data-confirm="선택한 메일을 영구 삭제합니다. 되돌릴 수 없습니다." onclick="mbDestroySelected()" style="padding:5px 10px;background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">영구 삭제</button>
            @endif
        </div>

        {{-- 리스트 --}}
        <div style="flex:1;overflow-y:auto;">
            @forelse($items as $r)
                @php
                    $m = $r->message;
                    $isUnread = !$r->is_read && $folder === 'inbox';
                    $preview = mb_strimwidth(strip_tags((string) $m?->body_text), 0, 90, '…', 'UTF-8');
                @endphp
                <a href="javascript:void(0)" onclick="mbOpenShow({{ $m->id }})"
                   class="mb-row" data-msg-id="{{ $m->id }}"
                   style="display:flex;align-items:center;gap:10px;padding:11px 20px;border-bottom:1px solid #f5f5f5;text-decoration:none;color:inherit;background:{{ $isUnread ? '#faf5ff' : '#fff' }};transition:background .12s;"
                   onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='{{ $isUnread ? '#faf5ff' : '#fff' }}'">
                    <input type="checkbox" class="mb-chk" value="{{ $m->id }}"
                           onclick="event.stopPropagation();event.preventDefault();this.checked=!this.checked;mbBulkRefresh();" style="accent-color:var(--t600);cursor:pointer;flex-shrink:0;">
                    <div style="width:130px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;font-weight:{{ $isUnread ? '700' : '500' }};color:{{ $isUnread ? '#1e1b2e' : '#52525b' }};">
                        {{ $folder === 'sent'
                            ? '받는이: ' . optional($m->recipients->where('user_id', '!=', auth()->id())->first())->name
                            : ($m->sender?->name ?? $m->sender?->email) }}
                    </div>
                    <div style="flex:1;min-width:0;display:flex;align-items:center;gap:6px;overflow:hidden;">
                        <span style="font-size:13px;font-weight:{{ $isUnread ? '700' : '500' }};color:{{ $isUnread ? '#1e1b2e' : '#374151' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;">
                            {{ $m->subject }}
                        </span>
                        @if($m->has_attachment)
                            <svg width="12" height="12" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        @endif
                        <span style="font-size:12px;color:var(--color-text-tertiary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">— {{ $preview }}</span>
                    </div>
                    <span style="font-size:11.5px;color:var(--color-text-tertiary);flex-shrink:0;">{{ optional($m->sent_at)->format('Y-m-d H:i') }}</span>
                </a>
            @empty
                <div style="padding:60px 20px;text-align:center;color:var(--color-text-tertiary);">
                    <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 8px;color:#cbd5e1;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p style="font-size:13px;">메일이 없습니다.</p>
                </div>
            @endforelse
        </div>

        <div style="border-top:1px solid var(--color-border-default);padding:10px 20px;">
            {{ $items->links() }}
        </div>
    </main>
</div>

{{-- ─── 메일 작성/보기 팝업 (iframe) ─── --}}
<div id="mb-modal-overlay" onclick="if(event.target===this)mbCloseModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:10900;"></div>
<div id="mb-modal"
     style="display:none;flex-direction:column;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10901;
            background:#fff;border-radius:14px;box-shadow:0 24px 60px rgba(0,0,0,.3);
            width:1100px;max-width:calc(100vw - 32px);height:calc(100vh - 60px);overflow:hidden;">
    <div style="flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #f4f4f5;">
        <h3 id="mb-modal-title" style="font-size:14px;font-weight:700;color:#1e1b2e;margin:0;">메일</h3>
        <button onclick="mbCloseModal()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:2px 8px;">&times;</button>
    </div>
    <iframe id="mb-modal-frame" src="about:blank" style="flex:1 1 0;width:100%;border:0;background:#fafafa;"></iframe>
</div>

<script>
function mbOpenCompose(extra) {
    const params = extra || '';
    mbOpenIframe('{{ route("mailbox.compose") }}?embed=1' + (params ? '&' + params : ''), '메일 작성');
}
function mbOpenShow(id) {
    mbOpenIframe('{{ url("mailbox/messages") }}/' + id + '?embed=1', '메일');
}
function mbOpenIframe(url, title) {
    document.getElementById('mb-modal-title').textContent = title;
    document.getElementById('mb-modal-frame').src = url;
    document.getElementById('mb-modal-overlay').style.display = 'block';
    document.getElementById('mb-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function mbCloseModal() {
    document.getElementById('mb-modal-overlay').style.display = 'none';
    document.getElementById('mb-modal').style.display = 'none';
    document.getElementById('mb-modal-frame').src = 'about:blank';
    document.body.style.overflow = '';
}
window.mbCloseModal = mbCloseModal;
// iframe 내부에서 호출
window.mbModalReload = function(opts) {
    mbCloseModal();
    if (opts && opts.reload) location.reload();
};
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('mb-modal').style.display === 'flex') mbCloseModal();
});

const MB_CSRF = '{{ csrf_token() }}';
const MB_FOLDER = @json($folder);

function mbSelectedIds() {
    return Array.from(document.querySelectorAll('.mb-chk:checked')).map(el => Number(el.value));
}
function mbBulkRefresh() {
    const ids = mbSelectedIds();
    const bar = document.getElementById('mb-bulk-bar');
    bar.style.display = ids.length ? 'flex' : 'none';
    document.getElementById('mb-bulk-count').textContent = ids.length;
}
async function mbApiCall(url, ids) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': MB_CSRF },
        body: JSON.stringify({ ids }),
    });
    if (res.ok) location.reload();
    else alert('작업 실패');
}
async function mbMarkReadSelected() {
    const ids = mbSelectedIds(); if (!ids.length) return;
    for (const id of ids) {
        await fetch(`{{ url('mailbox/messages') }}/${id}/read`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': MB_CSRF, 'Accept': 'application/json' }
        });
    }
    location.reload();
}
function mbTrashSelected()   { const ids = mbSelectedIds(); if (ids.length) mbApiCall('{{ route("mailbox.trash.move") }}', ids); }
function mbRestoreSelected() { const ids = mbSelectedIds(); if (ids.length) mbApiCall('{{ route("mailbox.trash.restore") }}', ids); }
function mbDestroySelected() { const ids = mbSelectedIds(); if (ids.length) mbApiCall('{{ route("mailbox.destroy-forever") }}', ids); }
</script>
@endsection
