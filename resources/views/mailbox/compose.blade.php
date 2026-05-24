@php $embed = $embed ?? false; @endphp
@extends($embed ? 'mailbox.embed-layout' : 'layouts.app')

@section('title', '메일 작성')
@section('header-actions')@endsection

@section($embed ? 'embed-content' : 'content')
<div style="{{ $embed ? 'max-width:none;margin:0;padding:0;' : 'max-width:980px;margin:0 auto;padding:8px 0 24px;' }}">

    <div style="background:#fff;{{ $embed ? '' : 'border:1px solid var(--color-border-default);border-radius:14px;' }}overflow:hidden;">
        {{-- 헤더 (단독 페이지에서만 — embed 팝업에서는 부모 모달 헤더가 이미 '메일 작성' 표시) --}}
        @if(!$embed)
        <div style="padding:14px 20px;border-bottom:1px solid var(--color-border-default);display:flex;align-items:center;justify-content:space-between;">
            <h1 style="font-size:16px;font-weight:700;color:var(--color-text-primary);margin:0;">메일 작성</h1>
            <button type="button" onclick="mbcCancel()" style="background:none;border:none;font-size:12.5px;color:var(--color-text-tertiary);cursor:pointer;">취소</button>
        </div>
        @endif

        @if(session('error'))
            <div style="margin:12px 20px;padding:10px 14px;background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;border-radius:8px;font-size:13px;">{{ session('error') }}</div>
        @endif

        <form id="mb-compose-form" method="POST" action="{{ route('mailbox.send') }}" enctype="multipart/form-data" style="padding:16px 20px;display:flex;flex-direction:column;gap:14px;">
            @csrf
            @if($prefill['in_reply_to'])
                <input type="hidden" name="in_reply_to" value="{{ $prefill['in_reply_to'] }}">
                <input type="hidden" name="thread_id" value="{{ $prefill['thread_id'] }}">
                <input type="hidden" name="references_chain" value="{{ $prefill['references_chain'] }}">
            @endif

            {{-- 보낸 사람 --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">보낸 사람</label>
                <div style="padding:8px 11px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;">
                    {{ auth()->user()->name }} <span style="color:#9ca3af;font-size:12px;">&lt;{{ auth()->user()->email }}&gt;</span>
                </div>
            </div>

            {{-- 받는 사람 (태그 인라인 입력 + 자동완성) --}}
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.03em;margin:0;">받는 사람</label>
                    <button type="button" onclick="mbcAddSelf()" title="{{ __('app.mail_send_to_self') }}"
                        style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:var(--t50,#f5f3ff);border:1px solid var(--t200,#ddd6fe);color:var(--t700,#6d28d9);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:background .12s;"
                        onmouseover="this.style.background='var(--t100,#ede9fe)'" onmouseout="this.style.background='var(--t50,#f5f3ff)'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ __('app.mail_send_to_self') }}
                    </button>
                </div>
                <div class="mb-taginput" data-field="to" tabindex="-1" onclick="this.querySelector('input').focus()">
                    <div class="mb-tag-chips" id="mbc-chips-to"></div>
                    <input class="mb-tag-input" type="text" autocomplete="off" placeholder="이름으로 검색 후 선택 · 또는 이메일 직접 입력 후 Enter">
                    <div class="mb-tag-dropdown" style="display:none;"></div>
                </div>
            </div>

            {{-- CC / BCC --}}
            <div id="mbc-cc-row" style="display:none;">
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">참조 (CC)</label>
                <div class="mb-taginput" data-field="cc" tabindex="-1" onclick="this.querySelector('input').focus()">
                    <div class="mb-tag-chips" id="mbc-chips-cc"></div>
                    <input class="mb-tag-input" type="text" autocomplete="off" placeholder="이름·이메일">
                    <div class="mb-tag-dropdown" style="display:none;"></div>
                </div>
            </div>
            <div id="mbc-bcc-row" style="display:none;">
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">숨은참조 (BCC)</label>
                <div class="mb-taginput" data-field="bcc" tabindex="-1" onclick="this.querySelector('input').focus()">
                    <div class="mb-tag-chips" id="mbc-chips-bcc"></div>
                    <input class="mb-tag-input" type="text" autocomplete="off" placeholder="이름·이메일">
                    <div class="mb-tag-dropdown" style="display:none;"></div>
                </div>
            </div>
            <div style="display:flex;gap:10px;font-size:12px;">
                <button type="button" onclick="document.getElementById('mbc-cc-row').style.display='block';this.style.display='none';" id="mbc-cc-btn" style="background:none;border:none;color:var(--t600);font-weight:600;cursor:pointer;padding:0;">+ 참조 (CC)</button>
                <button type="button" onclick="document.getElementById('mbc-bcc-row').style.display='block';this.style.display='none';" id="mbc-bcc-btn" style="background:none;border:none;color:var(--t600);font-weight:600;cursor:pointer;padding:0;">+ 숨은참조 (BCC)</button>
            </div>

            {{-- 제목 --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">제목</label>
                <input type="text" name="subject" required maxlength="300" value="{{ $prefill['subject'] }}"
                       style="width:100%;padding:8px 11px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>

            {{-- 본문 (Quill) --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">내용</label>
                <div id="mbc-quill-wrap" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;">
                    <div id="mbc-quill-editor" style="min-height:280px;max-height:520px;"></div>
                </div>
                <input type="hidden" name="body" id="mbc-body">
                <template id="mbc-initial">{!! $prefill['body'] !!}</template>
            </div>

            {{-- 첨부 --}}
            <div>
                <label style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px;letter-spacing:.03em;">
                    <span>첨부파일</span>
                    <label for="mbc-attach" style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#f5f3ff;border:1px solid #ddd6fe;color:#7c3aed;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        파일 선택
                    </label>
                    <input type="file" id="mbc-attach" multiple style="display:none;" onchange="mbcAddFiles(this.files);this.value='';">
                    <button type="button" onclick="mbcOpenPfPicker()" style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/></svg>
                        프로젝트 파일
                    </button>
                    <span style="font-size:11px;color:#94a3b8;font-weight:500;">(최대 10개·개당 20MB)</span>
                </label>
                <div id="mbc-attach-chips" style="display:flex;flex-wrap:wrap;gap:4px;min-height:4px;"></div>
            </div>

            {{-- 프로젝트 파일 picker 모달 --}}
            <div id="mbc-pf-overlay" onclick="if(event.target===this)mbcClosePfPicker()"
                 style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:11000;align-items:center;justify-content:center;">
                <div onclick="event.stopPropagation()" style="background:#fff;border-radius:14px;width:880px;max-width:calc(100vw - 32px);max-height:85vh;display:flex;flex-direction:column;overflow:hidden;">
                    <div style="padding:14px 18px;border-bottom:1px solid var(--color-border-default);display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:14px;font-weight:700;color:#1e1b2e;margin:0;">프로젝트 파일에서 첨부</h3>
                        <button type="button" onclick="mbcClosePfPicker()" style="background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">&times;</button>
                    </div>
                    <div style="padding:10px 18px;border-bottom:1px solid #f4f4f5;display:flex;gap:8px;align-items:center;">
                        <select id="mbc-pf-project" onchange="mbcPfLoad()" style="padding:6px 10px;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;outline:none;">
                            <option value="">전체 프로젝트</option>
                        </select>
                        <input id="mbc-pf-search" type="text" placeholder="파일명 검색" oninput="mbcPfLoad()"
                               style="flex:1;padding:6px 10px;border:1px solid #e4e4e7;border-radius:7px;font-size:12.5px;outline:none;">
                    </div>
                    <div id="mbc-pf-list" style="flex:1;overflow-y:auto;padding:6px 0;"></div>
                    <div style="padding:10px 18px;border-top:1px solid var(--color-border-default);display:flex;justify-content:flex-end;gap:8px;">
                        <button type="button" onclick="mbcClosePfPicker()" style="padding:7px 14px;background:#f4f4f5;color:#52525b;border:none;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;">취소</button>
                        <button type="button" onclick="mbcPfConfirm()" style="padding:7px 18px;background:var(--t600);color:#fff;border:none;border-radius:7px;font-size:12.5px;font-weight:700;cursor:pointer;">선택한 파일 첨부 <span id="mbc-pf-count" style="opacity:.85;">(0)</span></button>
                    </div>
                </div>
            </div>

            {{-- 액션 --}}
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:6px;border-top:1px solid var(--color-bg-muted);">
                <button type="button" onclick="mbcCancel()" style="padding:8px 16px;background:#fff;border:1px solid #e5e7eb;color:#52525b;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
                <button type="submit" id="mbc-submit" style="padding:8px 22px;background:linear-gradient(135deg,var(--t500),var(--t700));color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">발송</button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
    #mbc-quill-wrap .ql-toolbar { border:none;border-bottom:1px solid #e5e7eb;padding:5px 8px;background:#fafafa; }
    #mbc-quill-wrap .ql-container { border:none;font-family:inherit; }
    #mbc-quill-wrap .ql-editor { min-height:280px;max-height:520px;overflow-y:auto;padding:12px 14px;font-size:13.5px;color:#374151;line-height:1.65; }
    #mbc-quill-wrap .ql-editor img { max-width:100%;height:auto;border-radius:4px; }

    /* 태그 인라인 입력 */
    .mb-taginput {
        position:relative; display:flex; flex-wrap:wrap; align-items:center; gap:4px;
        min-height:38px; padding:4px 8px; border:1px solid #e5e7eb; border-radius:8px;
        background:#fff; cursor:text; transition:border-color .12s, box-shadow .12s;
    }
    .mb-taginput:focus-within { border-color:var(--t500); box-shadow:0 0 0 3px rgba(124,58,237,.10); }
    .mb-tag-chips { display:contents; }
    .mb-tag-chip {
        display:inline-flex; align-items:center; gap:4px;
        padding:2px 4px 2px 9px; background:var(--t100); color:var(--t700);
        border:1px solid var(--t200); border-radius:14px;
        font-size:12px; font-weight:600; max-width:240px;
    }
    .mb-tag-chip.is-external { background:#fef3c7; color:#92400e; border-color:#fde68a; }
    .mb-tag-chip-label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mb-tag-chip-x { background:none; border:none; cursor:pointer; color:inherit; font-size:14px; line-height:1; padding:0 2px; }
    .mb-tag-input {
        flex:1 1 120px; min-width:80px; border:none; outline:none; padding:4px 2px;
        font-size:13px; background:transparent; color:#1f2937;
    }
    .mb-tag-dropdown {
        position:absolute; top:calc(100% + 4px); left:0; right:0;
        background:#fff; border:1px solid #e5e7eb; border-radius:8px;
        box-shadow:0 10px 28px rgba(0,0,0,.12); max-height:260px; overflow-y:auto; z-index:50;
    }
    .mb-tag-option {
        display:flex; align-items:center; gap:9px; padding:7px 12px;
        font-size:12.5px; color:#374151; cursor:pointer; border-bottom:1px solid #f5f5f5;
    }
    .mb-tag-option:last-child { border-bottom:none; }
    .mb-tag-option.is-active, .mb-tag-option:hover { background:#f5f3ff; color:#5b21b6; }
    .mb-tag-option-avatar { width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,var(--t400),var(--t600));color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0; }
    .mb-tag-option-meta { font-size:11px; color:#94a3b8; }
    .mb-tag-empty { padding:10px 14px; font-size:12px; color:#9ca3af; }
</style>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function() {
    let _files = [];
    let _to = @json($prefill['recipients']);
    let _cc = [], _bcc = [];
    let _allUsers = [];

    // Quill 초기화 — SR 표준 이미지 동작은 installQuillImageResize() 가 일괄 처리
    let quill = null;
    (function initQuill() {
        quill = new Quill('#mbc-quill-editor', {
            theme: 'snow',
            placeholder: '내용을 입력하세요 (이미지 paste 가능)',
            modules: { toolbar: [
                [{ header: [false, 1, 2] }], ['bold','italic','underline','strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link','image'], ['clean'],
            ]},
        });
        const initial = document.getElementById('mbc-initial')?.innerHTML.trim();
        if (initial) { quill.root.innerHTML = initial; quill.update(); }
        if (window.installQuillImageResize) {
            window.installQuillImageResize(quill, {
                uploadUrl: '{{ route('email-compose.upload-image') }}',
                csrfToken: '{{ csrf_token() }}',
                enableAnnotate: true,
            });
        }
    })();

    // 수신자 로드
    fetch('{{ route("mailbox.recipients") }}', { headers: { 'Accept':'application/json' } })
        .then(r => r.json()).then(d => { _allUsers = d.users || []; }).catch(() => {});

    // 태그 인라인 입력 — 3개 필드(to/cc/bcc) 공통 핸들러
    const TAG_LISTS = { to: _to, cc: _cc, bcc: _bcc };
    function tagList(field) { return field === 'to' ? _to : (field === 'cc' ? _cc : _bcc); }
    function setTagList(field, list) {
        if (field === 'to') _to = list;
        else if (field === 'cc') _cc = list;
        else _bcc = list;
    }

    function renderTagChips(field) {
        const list = tagList(field);
        const box = document.getElementById('mbc-chips-' + field);
        if (!box) return;
        box.innerHTML = list.map((r, i) => {
            const isExternal = !_allUsers.find(u => u.email && u.email.toLowerCase() === (r.email || '').toLowerCase());
            const label = r.name ? (r.name + ' <' + r.email + '>') : r.email;
            return `<span class="mb-tag-chip ${isExternal ? 'is-external' : ''}" title="${escHtml(r.email)}">
                <span class="mb-tag-chip-label">${escHtml(label)}</span>
                <button type="button" class="mb-tag-chip-x" onclick="event.stopPropagation();mbcRemoveTag('${field}',${i})">&times;</button>
            </span>`;
        }).join('');
    }
    window.mbcRemoveTag = function(field, idx) {
        const list = tagList(field);
        list.splice(idx, 1);
        renderTagChips(field);
    };

    // 나에게 메일 보내기 — 본인을 받는 사람 chip 으로 추가
    window.mbcAddSelf = function() {
        const name  = @json(auth()->user()?->name ?? '');
        const email = @json(auth()->user()?->email ?? '');
        if (!email) return;
        if (_to.some(r => (r.email || '').toLowerCase() === email.toLowerCase())) {
            if (window.appToast) window.appToast(@json(__('app.mail_self_already_added')), 'info');
            else if (window.__alert) window.__alert(@json(__('app.mail_self_already_added')));
            return;
        }
        _to.push({ name, email });
        renderTagChips('to');
    };

    // 각 태그 입력 와이어링
    let _activeIdx = {};
    document.querySelectorAll('.mb-taginput').forEach(wrap => {
        const field = wrap.dataset.field;
        const input = wrap.querySelector('input');
        const dd    = wrap.querySelector('.mb-tag-dropdown');
        _activeIdx[field] = -1;

        function refreshDropdown() {
            const q = (input.value || '').trim().toLowerCase();
            const already = new Set(tagList(field).map(r => (r.email||'').toLowerCase()));
            const filtered = _allUsers.filter(u =>
                u.email && !already.has(u.email.toLowerCase()) &&
                (!q || `${u.name||''} ${u.email||''}`.toLowerCase().includes(q))
            );
            if (!q && !filtered.length) { dd.style.display = 'none'; return; }
            if (!filtered.length) {
                dd.innerHTML = `<div class="mb-tag-empty">${q ? '검색 결과 없음 — Enter 로 직접 이메일 추가' : '구성원 없음'}</div>`;
            } else {
                _activeIdx[field] = 0;
                dd.innerHTML = filtered.map((u, i) => {
                    const initial = (u.name || u.email || '?').substring(0, 1);
                    return `<div class="mb-tag-option ${i===0?'is-active':''}" data-idx="${i}"
                                  onmousedown="event.preventDefault();mbcAddFromDropdown('${field}',${u.id})"
                                  onmouseover="mbcSetActive('${field}',${i})">
                        <div class="mb-tag-option-avatar">${escHtml(initial)}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;color:#1f2937;">${escHtml(u.name || '(이름 없음)')}</div>
                            <div class="mb-tag-option-meta">${escHtml(u.email)}${u.company ? ' · ' + escHtml(u.company) : ''}</div>
                        </div>
                    </div>`;
                }).join('');
            }
            dd.style.display = 'block';
        }

        input.addEventListener('focus', refreshDropdown);
        input.addEventListener('input', refreshDropdown);
        // 직접 입력 / 드롭다운 선택 양쪽 모두 지원
        // - 완전한 이메일 형식이면 → 직접 추가 우선 (정확 일치 사용자가 있으면 그쪽 사용)
        // - 부분 텍스트면 → 드롭다운 활성 항목 선택
        function tryAddTyped() {
            const v = (input.value || '').trim().replace(/[,;]+$/, '').trim();
            if (!v) return false;
            if (!/^.+@.+\..+$/.test(v)) return false;
            const list = tagList(field);
            const exact = _allUsers.find(u => (u.email || '').toLowerCase() === v.toLowerCase());
            const payload = exact ? { email: exact.email, name: exact.name } : { email: v, name: null };
            if (!list.find(r => (r.email || '').toLowerCase() === payload.email.toLowerCase())) {
                list.push(payload);
                renderTagChips(field);
            }
            input.value = '';
            dd.style.display = 'none';
            return true;
        }

        input.addEventListener('keydown', e => {
            const opts = dd.querySelectorAll('.mb-tag-option');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!opts.length) return;
                _activeIdx[field] = Math.min(_activeIdx[field] + 1, opts.length - 1);
                opts.forEach((el, i) => el.classList.toggle('is-active', i === _activeIdx[field]));
                opts[_activeIdx[field]]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                _activeIdx[field] = Math.max(_activeIdx[field] - 1, 0);
                opts.forEach((el, i) => el.classList.toggle('is-active', i === _activeIdx[field]));
                opts[_activeIdx[field]]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' || e.key === ',' || e.key === ';' || e.key === 'Tab') {
                const v = (input.value || '').trim();
                const isCompleteEmail = /^.+@.+\..+$/.test(v.replace(/[,;]+$/, '').trim());

                // 1) 완전한 이메일이 입력됐으면 직접 추가 우선 (드롭다운 부분 매칭 무시)
                if (isCompleteEmail) {
                    if (e.key === 'Enter' || e.key === ',' || e.key === ';') e.preventDefault();
                    tryAddTyped();
                    return;
                }

                // 2) 부분 텍스트 + 드롭다운 활성 항목 있음 → 사용자 선택
                const idx = _activeIdx[field];
                if (opts.length && idx >= 0 && (e.key !== 'Tab' || v)) {
                    e.preventDefault();
                    const opts2 = dd.querySelectorAll('.mb-tag-option');
                    const userIdx = Number(opts2[idx]?.dataset.idx ?? -1);
                    const matched = _allUsers.filter(u => {
                        const q = v.toLowerCase();
                        const already = new Set(tagList(field).map(r => (r.email||'').toLowerCase()));
                        return u.email && !already.has(u.email.toLowerCase()) &&
                               (!q || `${u.name||''} ${u.email||''}`.toLowerCase().includes(q));
                    });
                    const picked = matched[userIdx] || matched[0];
                    if (picked) mbcAddFromDropdown(field, picked.id);
                    return;
                }

                // 3) Enter/, 인데 텍스트는 있지만 이메일 아님 → 경고
                if ((e.key === 'Enter' || e.key === ',' || e.key === ';') && v) {
                    e.preventDefault();
                    alert('유효한 이메일 형식이 아닙니다.');
                }
            } else if (e.key === 'Backspace' && !input.value) {
                const list = tagList(field);
                if (list.length) { list.pop(); renderTagChips(field); }
            } else if (e.key === 'Escape') {
                dd.style.display = 'none';
            }
        });

        // 붙여넣기 — 쉼표/세미콜론/줄바꿈으로 구분된 이메일 리스트 자동 분할
        input.addEventListener('paste', e => {
            const text = (e.clipboardData || window.clipboardData)?.getData('text');
            if (!text) return;
            if (!/[,;\n]/.test(text)) return; // 단일 입력은 기본 paste 동작
            e.preventDefault();
            const tokens = text.split(/[,;\n]+/).map(s => s.trim()).filter(Boolean);
            const list = tagList(field);
            tokens.forEach(t => {
                // "name <email>" 또는 "email"
                let name = null, email = t;
                const m = t.match(/^\s*(.+?)\s*<([^>]+)>\s*$/);
                if (m) { name = m[1].trim(); email = m[2].trim(); }
                if (!/^.+@.+\..+$/.test(email)) return;
                const exact = _allUsers.find(u => (u.email || '').toLowerCase() === email.toLowerCase());
                const payload = exact ? { email: exact.email, name: exact.name } : { email, name };
                if (!list.find(r => (r.email || '').toLowerCase() === payload.email.toLowerCase())) {
                    list.push(payload);
                }
            });
            renderTagChips(field);
            input.value = '';
            dd.style.display = 'none';
        });

        // 포커스 잃을 때 유효한 이메일이 남아 있으면 자동 추가 (사용자가 Enter 안 누르고 다른 곳 클릭한 경우)
        input.addEventListener('blur', () => {
            setTimeout(() => {
                tryAddTyped();           // 유효 이메일이면 추가 (아니면 조용히 무시)
                dd.style.display = 'none';
            }, 120);
        });
    });

    window.mbcAddFromDropdown = function(field, userId) {
        const user = _allUsers.find(u => u.id === userId);
        if (!user) return;
        const list = tagList(field);
        if (list.find(r => (r.email||'').toLowerCase() === (user.email||'').toLowerCase())) return;
        list.push({ email: user.email, name: user.name });
        renderTagChips(field);
        // 입력 칸 비우고 다시 표시
        const wrap = document.querySelector(`.mb-taginput[data-field="${field}"]`);
        if (wrap) {
            const input = wrap.querySelector('input');
            input.value = '';
            input.focus();
            input.dispatchEvent(new Event('input'));
        }
    };
    window.mbcSetActive = function(field, idx) {
        _activeIdx[field] = idx;
        const wrap = document.querySelector(`.mb-taginput[data-field="${field}"]`);
        if (!wrap) return;
        wrap.querySelectorAll('.mb-tag-option').forEach((el, i) => el.classList.toggle('is-active', i === idx));
    };

    // 프로젝트 파일 picker 상태
    let _pfFiles = [];           // 검색 결과
    let _pfSelected = new Set();  // id 선택
    let _pfAttached = [];         // 첨부 확정된 프로젝트 파일 {id, name, size_text}

    window.mbcOpenPfPicker = function() {
        document.getElementById('mbc-pf-overlay').style.display = 'flex';
        _pfSelected.clear();
        mbcPfLoad();
    };
    window.mbcClosePfPicker = function() {
        document.getElementById('mbc-pf-overlay').style.display = 'none';
    };
    window.mbcPfLoad = function() {
        const pid = document.getElementById('mbc-pf-project').value;
        const q   = document.getElementById('mbc-pf-search').value.trim();
        const params = new URLSearchParams();
        if (pid) params.set('project_id', pid);
        if (q)   params.set('q', q);
        fetch('{{ route("mailbox.project-files") }}?' + params.toString(), { headers: { 'Accept':'application/json' } })
            .then(r => r.json()).then(d => {
                _pfFiles = d.files || [];
                // 프로젝트 옵션 채움 (1회)
                const sel = document.getElementById('mbc-pf-project');
                if (sel.options.length <= 1 && d.projects) {
                    d.projects.forEach(p => sel.add(new Option(p.name, p.id)));
                }
                mbcPfRender();
            });
    };
    function mbcPfRender() {
        const list = document.getElementById('mbc-pf-list');
        if (!_pfFiles.length) {
            list.innerHTML = '<div style="padding:30px 18px;text-align:center;color:#9ca3af;font-size:12.5px;">파일이 없습니다.</div>';
        } else {
            list.innerHTML = _pfFiles.map(f => `
                <label title="${escHtml(f.name)}" style="display:flex;align-items:flex-start;gap:10px;padding:9px 18px;font-size:12.5px;color:#374151;cursor:pointer;border-bottom:1px solid #f5f5f5;" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                    <input type="checkbox" data-pfid="${f.id}" ${_pfSelected.has(f.id) ? 'checked' : ''} onchange="mbcPfToggle(this)" style="accent-color:var(--t600);margin-top:3px;flex-shrink:0;">
                    <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
                        <span style="font-weight:600;color:#1e1b2e;word-break:break-all;line-height:1.45;">${escHtml(f.name)}</span>
                        <span style="color:#94a3b8;font-size:11px;display:flex;gap:8px;flex-wrap:wrap;">
                            ${f.project ? `<span>📁 ${escHtml(f.project)}</span>` : ''}
                            <span>${escHtml(f.size_text)}</span>
                        </span>
                    </span>
                </label>
            `).join('');
        }
        document.getElementById('mbc-pf-count').textContent = '(' + _pfSelected.size + ')';
    }
    window.mbcPfToggle = function(cb) {
        const id = Number(cb.dataset.pfid);
        if (cb.checked) _pfSelected.add(id);
        else _pfSelected.delete(id);
        document.getElementById('mbc-pf-count').textContent = '(' + _pfSelected.size + ')';
    };
    window.mbcPfConfirm = function() {
        const ids = Array.from(_pfSelected);
        ids.forEach(id => {
            if (_pfAttached.some(a => a.id === id)) return;
            const f = _pfFiles.find(x => x.id === id);
            if (f) _pfAttached.push(f);
        });
        renderAttachChips();
        mbcClosePfPicker();
    };

    // iframe 안일 때 cancel — 부모 모달 닫기, 아니면 inbox 로 (embed 유지)
    window.mbcCancel = function() {
        if (window.parent && window.parent.mbCloseModal && window.parent !== window) {
            window.parent.mbCloseModal();
        } else {
            const isEmbed = new URLSearchParams(location.search).get('embed') === '1';
            location.href = '{{ route("mailbox.inbox") }}' + (isEmbed ? '?embed=1' : '');
        }
    };

    // 첨부
    window.mbcAddFiles = function(files) {
        for (const f of files) {
            if (_files.length >= 10) { alert('첨부파일은 최대 10개까지 가능합니다.'); break; }
            if (f.size > 20*1024*1024) { alert(`"${f.name}" 은 20MB 초과`); continue; }
            if (_files.some(x => x.name === f.name && x.size === f.size)) continue;
            _files.push(f);
        }
        renderAttachChips();
    };
    function renderAttachChips() {
        const localChips = _files.map((f,i) => {
            const sz = f.size<1024*1024 ? Math.round(f.size/102.4)/10+'KB' : Math.round(f.size/(1024*102.4))/10+'MB';
            return `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 9px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:6px;font-size:11.5px;font-weight:500;max-width:240px;">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(f.name)}</span>
                <span style="color:#a16207;font-size:10px;">${sz}</span>
                <button type="button" onclick="_files.splice(${i},1);renderAttachChips()" style="background:none;border:none;cursor:pointer;color:#92400e;font-size:13px;line-height:1;padding:0 2px;">&times;</button>
            </span>`;
        });
        const pfChips = _pfAttached.map((f,i) => {
            return `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 4px 3px 9px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:6px;font-size:11.5px;font-weight:500;max-width:260px;">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/></svg>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(f.name)}</span>
                <span style="color:#10b981;font-size:10px;">${escHtml(f.size_text)}</span>
                <button type="button" onclick="_pfAttached.splice(${i},1);renderAttachChips()" style="background:none;border:none;cursor:pointer;color:#047857;font-size:13px;line-height:1;padding:0 2px;">&times;</button>
            </span>`;
        });
        document.getElementById('mbc-attach-chips').innerHTML = [...localChips, ...pfChips].join('');
    }
    function escHtml(s) { return String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

    // 폼 submit — FormData 로 변환 + Quill HTML 적재
    document.getElementById('mb-compose-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!_to.length) { alert('받는 사람을 1명 이상 입력하세요.'); return; }
        document.getElementById('mbc-body').value = quill.getLength() <= 1 ? '' : quill.root.innerHTML;
        const fd = new FormData(this);
        _to.forEach(r => fd.append('recipients[]', (r.name ? r.name+' <'+r.email+'>' : r.email)));
        _cc.forEach(r => fd.append('cc[]', r.email));
        _bcc.forEach(r => fd.append('bcc[]', r.email));
        _files.forEach(f => fd.append('attachments[]', f, f.name));
        _pfAttached.forEach(f => fd.append('project_file_ids[]', f.id));
        const btn = document.getElementById('mbc-submit');
        btn.disabled = true; const orig = btn.textContent; btn.textContent = '발송 중…';
        fetch(this.action, { method: 'POST', headers: { 'Accept':'application/json,text/html', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: fd })
            .then(r => {
                if (!r.ok) return Promise.reject(r);
                // iframe(embed) 안이면 부모 모달 닫고 새로고침, 아니면 보낸편지함 (embed 유지)
                if (window.parent && window.parent.mbModalReload) {
                    window.parent.mbModalReload({ reload: true });
                } else {
                    const isEmbed = new URLSearchParams(location.search).get('embed') === '1';
                    location.href = '{{ route("mailbox.sent") }}' + (isEmbed ? '?embed=1' : '');
                }
            })
            .catch(async r => {
                let msg = '발송 실패';
                try { const d = await r.json(); msg = d.message || msg; } catch (e) {}
                alert(msg);
                btn.disabled = false; btn.textContent = orig;
            });
    });
    // 초기 칩 렌더
    renderTagChips('to');
    renderTagChips('cc');
    renderTagChips('bcc');
})();
</script>
@endsection
