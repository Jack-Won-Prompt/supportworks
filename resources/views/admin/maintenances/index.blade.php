@extends('layouts.admin')

@section('title', __('admin.maint_title'))

@push('styles')
@include('maintenance._quill_assets')
<style>
#adm-detail-modal { display:none; }
#adm-detail-modal.is-open { display:flex; }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="margin:0;font-size:20px;font-weight:700;color:#0f172a;">{{ __('admin.maint_title') }}</h1>
        <p style="margin:4px 0 0;font-size:13px;color:#64748b;">{{ __('admin.maint_subtitle') }}</p>
    </div>
</div>

@php
$statusLabels = [
    'all'         => __('admin.status_all'),
    'pending'     => __('admin.maint_status_pending'),
    'in_progress' => __('admin.maint_status_in_progress'),
    'completed'   => __('admin.maint_status_completed'),
    'rejected'    => __('admin.maint_status_rejected'),
];
$statusColors = [
    'all'         => '#6366f1',
    'pending'     => '#d97706',
    'in_progress' => '#2563eb',
    'completed'   => '#16a34a',
    'rejected'    => '#dc2626',
];
$statusBgs = [
    'all'         => '#eef2ff',
    'pending'     => '#fef3c7',
    'in_progress' => '#dbeafe',
    'completed'   => '#dcfce7',
    'rejected'    => '#fee2e2',
];
@endphp

{{-- 상태 요약 카드 --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;">
    @foreach($statusLabels as $s => $l)
    @php $c = $statusColors[$s]; $bg = $statusBgs[$s]; @endphp
    <a href="{{ request()->fullUrlWithQuery(['status' => $s === 'all' ? null : $s]) }}"
       style="padding:14px 16px;background:{{ request('status','all') === $s ? $bg : '#fff' }};border:1.5px solid {{ request('status','all') === $s ? $c : '#e2e8f0' }};border-radius:12px;text-decoration:none;transition:all .15s;">
        <div style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ $l }}</div>
        <div style="font-size:22px;font-weight:800;color:{{ $c }};">{{ $counts[$s] }}</div>
    </a>
    @endforeach
</div>

{{-- 필터 --}}
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end;">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <div>
        <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ __('admin.project') }}</label>
        <select name="project_id" onchange="this.form.submit()"
                style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;outline:none;background:#fff;min-width:160px;">
            <option value="">{{ __('admin.maint_all_projects') }}</option>
            @foreach($projects as $proj)
            <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>{{ $proj->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ __('admin.priority') }}</label>
        <select name="priority" onchange="this.form.submit()"
                style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#334155;outline:none;background:#fff;">
            <option value="">{{ __('admin.status_all') }}</option>
            <option value="urgent"  {{ request('priority')==='urgent'  ? 'selected':'' }}>{{ __('admin.maint_priority_urgent') }}</option>
            <option value="high"    {{ request('priority')==='high'    ? 'selected':'' }}>{{ __('admin.maint_priority_high') }}</option>
            <option value="normal"  {{ request('priority')==='normal'  ? 'selected':'' }}>{{ __('admin.maint_priority_normal') }}</option>
            <option value="low"     {{ request('priority')==='low'     ? 'selected':'' }}>{{ __('admin.maint_priority_low') }}</option>
        </select>
    </div>
    @if(request()->hasAny(['project_id','priority']))
    <a href="{{ route('admin.maintenances.index') }}" style="padding:7px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;text-decoration:none;background:#fff;">{{ __('admin.maint_reset') }}</a>
    @endif
</form>

{{-- 플래시 --}}
@if(session('success'))
<div style="margin-bottom:14px;padding:10px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:9px;font-size:13px;color:#15803d;font-weight:500;">{{ session('success') }}</div>
@endif

{{-- 목록 --}}
<div class="admin-card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
            <th style="padding:11px 16px;font-size:11px;font-weight:600;color:#64748b;text-align:left;">{{ __('admin.maint_project_title_col') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:90px;">{{ __('admin.priority') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:100px;">{{ __('admin.col_status') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:110px;">{{ __('admin.requester') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:90px;">{{ __('admin.maint_request_date_col') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:90px;">{{ __('admin.maint_schedule_col') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:50px;">{{ __('admin.maint_replies_col') }}</th>
            <th style="padding:11px 12px;font-size:11px;font-weight:600;color:#64748b;text-align:center;width:90px;">{{ __('admin.maint_registered_date') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($maintenances as $item)
        <tr data-detail-url="{{ route('admin.maintenances.detail', $item) }}"
            style="border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .12s;"
            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'"
            onclick="admOpenDetail(this.dataset.detailUrl)">

            <td style="padding:12px 16px;">
                <div style="font-size:11px;color:#94a3b8;margin-bottom:2px;">{{ $item->srTarget?->title ?? $item->project?->name }}</div>
                <div style="font-size:13px;font-weight:600;color:#0f172a;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    @if($item->status === 'pending')
                    <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#ef4444;margin-right:5px;vertical-align:middle;"></span>
                    @endif
                    {{ $item->title }}
                </div>
            </td>
            <td style="padding:12px;text-align:center;">
                <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:8px;
                             color:{{ $item->priority_color }};background:{{ $item->priority === 'urgent' ? '#fee2e2' : ($item->priority === 'high' ? '#fef3c7' : '#f3f4f6') }};">
                    {{ $item->priority_label }}
                </span>
            </td>
            <td style="padding:12px;text-align:center;">
                <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:8px;
                             color:{{ $item->status_color }};background:{{ $item->status_bg }};">
                    {{ $item->status_label }}
                </span>
            </td>
            <td style="padding:12px;text-align:center;font-size:12px;color:#475569;">{{ $item->user->name }}</td>
            <td style="padding:12px;text-align:center;font-size:12px;
                       color:{{ $item->due_date?->isPast() && !in_array($item->status,['completed','rejected']) ? '#dc2626' : '#475569' }};">
                {{ $item->due_date?->format('Y.m.d') ?? '—' }}
            </td>
            <td style="padding:12px;text-align:center;font-size:12px;color:{{ $item->scheduled_date ? '#7c3aed' : '#94a3b8' }};font-weight:{{ $item->scheduled_date ? '600' : '400' }};">
                {{ $item->scheduled_date?->format('Y.m.d') ?? '—' }}
            </td>
            <td style="padding:12px;text-align:center;font-size:12px;color:#64748b;">{{ $item->replies_count }}</td>
            <td style="padding:12px;text-align:center;font-size:11px;color:#94a3b8;">{{ $item->created_at->format('m.d') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;">
                {{ __('admin.maint_no_sr') }}
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>

    @if($maintenances->hasPages())
    <div style="padding:14px 16px;border-top:1px solid #f1f5f9;">{{ $maintenances->links() }}</div>
    @endif
</div>

{{-- SR 상세 모달 --}}
<div id="adm-detail-modal"
     style="position:fixed;inset:0;z-index:10201;background:rgba(15,23,42,.5);align-items:center;justify-content:center;"
     onclick="if(event.target===this)admCloseDetail()">
    <div id="adm-detail-panel"
         style="background:#fff;border-radius:16px;width:92vw;max-width:900px;height:86vh;max-height:860px;
                display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden;position:relative;">

        {{-- 고정 헤더 영역 --}}
        <div id="adm-dt-fixed-header" style="flex-shrink:0;"></div>

        {{-- 로딩 인디케이터 --}}
        <div id="adm-dt-loading" style="flex:1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;">
            {{ __('admin.maint_loading') }}
        </div>

        {{-- 스크롤 영역 --}}
        <div id="adm-dt-content" style="flex:1;overflow-y:auto;display:none;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ADMIN_B_STR = {
    load_fail:           '{{ __("admin.maint_load_fail") }}',
    delete_reply_confirm:'{{ __("admin.maint_delete_reply_confirm") }}',
    reply_placeholder:   '{{ __("admin.maint_reply_placeholder") }}',
};

(async function() {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let _currentDetailUrl = null;
    let _admQuill = null;

    /* ── 모달 열기/닫기 ── */
    window.admOpenDetail = async function(url) {
        _currentDetailUrl = url;
        const modal = document.getElementById('adm-detail-modal');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        _admLoadDetail(url);
    };

    window.admCloseDetail = async function() {
        document.getElementById('adm-detail-modal').classList.remove('is-open');
        document.body.style.overflow = '';
        document.getElementById('adm-dt-fixed-header').innerHTML = '';
        document.getElementById('adm-dt-content').innerHTML = '';
        document.getElementById('adm-dt-content').style.display = 'none';
        document.getElementById('adm-dt-loading').style.display = 'flex';
        _admQuill = null;
        _currentDetailUrl = null;
    };

    /* ── 콘텐츠 로드 ── */
    async function _admLoadDetail(url) {
        const loading = document.getElementById('adm-dt-loading');
        const content = document.getElementById('adm-dt-content');
        loading.style.display = 'flex';
        content.style.display = 'none';
        content.innerHTML = '';
        document.getElementById('adm-dt-fixed-header').innerHTML = '';
        _admQuill = null;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                content.innerHTML = html;
                _admMoveFixedHeader();
                loading.style.display = 'none';
                content.style.display = 'block';
                _admInitInteractivity();
            })
            .catch(() => {
                loading.textContent = ADMIN_B_STR.load_fail;
            });
    }

    async function _admReloadDetail() {
        if (_currentDetailUrl) _admLoadDetail(_currentDetailUrl);
    }

    /* ── 고정 헤더 DOM 이동 ── */
    async function _admMoveFixedHeader() {
        const fh  = document.getElementById('adm-dt-fixed-header');
        const el  = document.getElementById('adm-dt-content').querySelector('[data-fixed-header]');
        fh.innerHTML = '';
        if (el) fh.appendChild(el);
    }

    /* ── 인터랙티브 초기화 ── */
    async function _admInitInteractivity() {
        const panel   = document.getElementById('adm-detail-panel');
        const content = document.getElementById('adm-dt-content');

        /* 상태 변경 select */
        const statusSelect = panel.querySelector('[data-status-select]');
        if (statusSelect) {
            statusSelect.addEventListener('change', async function() {
                const url = this.closest('[data-status-form]').dataset.statusUrl;
                await _admFetch('PATCH', url, { status: this.value });
                _admReloadDetail();
            });
        }

        /* 처리 예정일 form */
        const scheduleForm = panel.querySelector('[data-schedule-form]');
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const url = this.dataset.scheduleUrl;
                const fd  = new FormData(this);
                await _admFetch('PATCH', url, { scheduled_date: fd.get('scheduled_date') || '' });
                _admReloadDetail();
            });
        }

        /* 답글 삭제 */
        panel.querySelectorAll('[data-delete-reply-btn]').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!await __confirm(ADMIN_B_STR.delete_reply_confirm)) return;
                await _admFetch('DELETE', this.dataset.url, {});
                _admReloadDetail();
            });
        });

        /* 답글 작성 Quill + 제출 */
        const replyForm = content.querySelector('[data-reply-form]');
        if (replyForm) {
            const target = replyForm.querySelector('.sr-reply-quill-target');
            const hidden = replyForm.querySelector('.sr-reply-hidden');

            _admQuill = new Quill(target, {
                theme: 'snow',
                placeholder: ADMIN_B_STR.reply_placeholder,
                modules: {
                    toolbar: {
                        container: [['bold','italic','underline'], [{'list':'bullet'}], ['image']],
                        handlers: {
                            image: async function() {
                                const input = document.createElement('input');
                                input.type = 'file'; input.accept = 'image/*';
                                input.onchange = async () => {
                                    const url = await srUploadImage(input.files[0], CSRF);
                                    if (url) {
                                        const range = _admQuill.getSelection(true);
                                        _admQuill.insertEmbed(range.index, 'image', url, 'user');
                                        _admQuill.setSelection(range.index + 1);
                                    }
                                };
                                input.click();
                            }
                        }
                    },
                    clipboard: {
                        matchers: [['img', async function(node, delta) {
                            if (node.src && node.src.startsWith('data:')) return new Delta();
                            return delta;
                        }]]
                    }
                }
            });

            /* 포커스 클래스 */
            _admQuill.on('selection-change', async function(range) {
                const w = _admQuill.root.closest('.sr-reply-editor-wrap');
                if (w) w.classList.toggle('focused', !!range);
            });

            /* 붙여넣기 이미지 업로드 */
            _admQuill.root.addEventListener('paste', async function(e) {
                const item = Array.from((e.clipboardData||e.originalEvent?.clipboardData)?.items||[])
                    .find(i => i.kind === 'file' && i.type.startsWith('image/'));
                if (!item) return;
                e.preventDefault(); e.stopImmediatePropagation();
                srUploadImage(item.getAsFile(), CSRF).then(url => {
                    if (url) {
                        const range = _admQuill.getSelection(true);
                        const idx = range ? range.index : _admQuill.getLength();
                        _admQuill.insertEmbed(idx, 'image', url, 'user');
                        _admQuill.setSelection(idx + 1);
                    }
                });
            }, true);

            /* text-change: base64 차단 + hidden 동기화 */
            _admQuill.on('text-change', async function(delta, oldDelta, source) {
                if (source !== 'silent') {
                    const contents = _admQuill.getContents();
                    const hasBase64 = contents.ops.some(op =>
                        op.insert && op.insert.image && String(op.insert.image).startsWith('data:')
                    );
                    if (hasBase64) {
                        const cleaned = contents.ops.filter(op =>
                            !(op.insert && op.insert.image && String(op.insert.image).startsWith('data:'))
                        );
                        _admQuill.setContents({ ops: cleaned }, 'silent');
                    }
                }
                if (hidden) hidden.value = _admQuill.root.innerHTML;
            });

            /* 제출 */
            replyForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (_admQuill.getText().trim() === '') return;
                hidden.value = _admQuill.root.innerHTML;
                const url = this.dataset.url;
                await _admFetch('POST', url, { content: hidden.value });
                _admReloadDetail();
            });
        }
    }

    /* ── AJAX 헬퍼 ── */
    async function _admFetch(method, url, data) {
        return fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(data),
        });
    }

    /* ESC 닫기 */
    document.addEventListener('keydown', async function(e) {
        if (e.key === 'Escape') admCloseDetail();
    });
})();
</script>
@endsection
