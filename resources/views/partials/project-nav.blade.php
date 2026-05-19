@php
$_pnUser  = auth()->user();
$_pnItems = array_values(array_filter([
    ['key'=>'overview',       'url'=>route('projects.show', $project),                 'label'=>__('projects.nav_overview'),  'feature'=>null],
    ['key'=>'planning',       'url'=>route('projects.planning.index', $project),       'label'=>__('projects.planning'),      'feature'=>'planning'],
    ['key'=>'requirements',   'url'=>route('projects.requirements.index', $project),   'label'=>__('projects.nav_requirements'),   'feature'=>'requirements'],
    ['key'=>'discussions',    'url'=>route('projects.discussions.index',  $project),   'label'=>__('projects.nav_discussions'),    'feature'=>null],
    ['key'=>'plan-do-acts',   'url'=>route('plan-do-acts.index', ['project' => $project->id]), 'label'=>__('plan-do-acts.nav'),       'feature'=>null],
    ['key'=>'deliverables',   'url'=>route('ai-agent.projects.deliverables.index', $project), 'label'=>__('projects.nav_deliverables'),'feature'=>null],
    ['key'=>'schedules',      'url'=>route('projects.schedules.index', $project),      'label'=>__('projects.schedule'),      'feature'=>'schedules'],
    ['key'=>'gantt',          'url'=>route('projects.gantt', $project),                'label'=>__('projects.gantt'),         'feature'=>'gantt'],
    ['key'=>'qa',             'url'=>route('projects.questions.index', $project),      'label'=>__('projects.qa'),            'feature'=>'qa'],
    ['key'=>'issues',         'url'=>route('projects.issues.index', $project),         'label'=>__('projects.nav_issues'),         'feature'=>'issues'],
    ['key'=>'files',          'url'=>route('projects.files.index', $project),          'label'=>__('projects.files'),         'feature'=>'files'],
    ['key'=>'members',        'url'=>route('projects.members.index', $project),        'label'=>__('projects.members_btn'),   'feature'=>null,  'popup'=>true],
    ['key'=>'weekly-reports', 'url'=>route('projects.weekly-reports.index', $project), 'label'=>__('projects.nav_weekly_reports'), 'feature'=>'weekly_reports'],
    ['key'=>'leaves',         'url'=>route('projects.leaves.index', $project),         'label'=>__('projects.leave_days'),    'feature'=>'leaves'],
    ['key'=>'maintenances',   'url'=>route('projects.maintenances.index', $project),   'label'=>__('projects.sr_receive'),    'feature'=>'sr'],
], fn($item) => $item['feature'] === null || $_pnUser->hasFeature($item['feature'])));
@endphp

<style>
/* ── 멤버 팝업 전용 스타일 (Tailwind 리셋 차단) ── */
#pnm-modal * { box-sizing: border-box; font-family: inherit; }
#pnm-modal p  { margin: 0; padding: 0; }
#pnm-modal h3 { margin: 0; padding: 0; }

.pnm-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:10400; }
.pnm-modal   {
    display:none; position:fixed; top:50%; left:50%;
    transform:translate(-50%,-50%); z-index:10401;
    background:#fff; border-radius:16px;
    box-shadow:0 8px 40px rgba(0,0,0,.18);
    width:520px; max-width:calc(100vw - 32px); max-height:85vh;
    overflow:hidden; flex-direction:column;
}
.pnm-modal.is-open { display:flex; }

.pnm-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; border-bottom:1px solid #f0f0f0;
    background:#fff; flex-shrink:0;
}
.pnm-head h3 { font-size:15px; font-weight:700; color:#18181b; }
.pnm-head-close {
    background:none !important; border:none !important; cursor:pointer;
    color:#9ca3af; font-size:22px; line-height:1; padding:2px 4px;
}
.pnm-head-close:hover { color:#374151; }

.pnm-body { padding:20px; overflow-y:auto; flex:1; }

/* 추가 섹션 */
.pnm-add-section {
    padding-bottom:18px; margin-bottom:18px;
    border-bottom:1px solid #f3f4f6;
}
.pnm-section-label {
    font-size:11px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.06em;
    margin-bottom:10px !important;
}
.pnm-add-row { display:flex; gap:8px; align-items:center; }
.pnm-select {
    padding:8px 10px !important; border:1.5px solid #e5e7eb !important;
    border-radius:8px !important; font-size:13px !important;
    background:#fff !important; outline:none !important;
    color:#374151 !important; appearance:auto !important;
    -webkit-appearance:auto !important;
}
.pnm-select:focus { border-color:#6366f1 !important; }
.pnm-select-user { flex:1; min-width:0; }
.pnm-select-role { width:90px; flex-shrink:0; }
.pnm-btn-add {
    padding:8px 16px !important; font-size:13px !important;
    font-weight:600 !important; color:#fff !important;
    background:#4f46e5 !important; border:none !important;
    border-radius:8px !important; cursor:pointer !important;
    flex-shrink:0; white-space:nowrap;
}
.pnm-btn-add:hover  { background:#4338ca !important; }
.pnm-btn-add:disabled { opacity:.6; cursor:not-allowed !important; }
.pnm-err { display:none; margin-top:8px; font-size:12px; color:#ef4444; }

/* 멤버 행 */
.pnm-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:11px 0; border-bottom:1px solid #f3f4f6; gap:10px;
}
.pnm-row:last-child { border-bottom:none; }
.pnm-row-left  { display:flex; align-items:center; gap:10px; min-width:0; flex:1; }
.pnm-avatar    {
    width:36px; height:36px; background:#eef2ff; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; font-weight:700; color:#4f46e5; flex-shrink:0;
}
.pnm-name  { font-size:13px; font-weight:600; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pnm-email { font-size:11px; color:#9ca3af; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-top:1px !important; }
.pnm-row-right { display:flex; align-items:center; gap:6px; flex-shrink:0; }

.pnm-role-sel {
    padding:5px 8px !important; border:1.5px solid #e5e7eb !important;
    border-radius:6px !important; font-size:12px !important;
    background:#fff !important; outline:none !important;
    color:#374151 !important; appearance:auto !important;
    -webkit-appearance:auto !important;
}
.pnm-role-sel:focus   { border-color:#6366f1 !important; }
.pnm-role-sel:disabled { opacity:.55; cursor:not-allowed !important; }

.pnm-role-badge {
    display:inline-block; padding:3px 10px;
    font-size:11px; font-weight:600; border-radius:20px;
}
.pnm-btn-del {
    background:none !important; border:none !important;
    cursor:pointer !important; color:#d1d5db !important;
    padding:4px !important; line-height:0; border-radius:4px !important;
}
.pnm-btn-del:hover { color:#ef4444 !important; }

.pnm-empty { text-align:center; padding:30px 0; font-size:13px; color:#9ca3af; }
</style>

<div style="background:#fff;border-radius:10px;border:1px solid #f3f4f6;box-shadow:0 1px 3px rgba(0,0,0,.04);flex-shrink:0;margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 16px;">
        <a href="{{ route('projects.show', $project) }}" style="font-size:14px;font-weight:700;color:#111827;text-decoration:none;white-space:nowrap;flex-shrink:0;" onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#111827'">{{ $project->name }}</a>
        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
            @foreach($_pnItems as $_pni)
            <a href="{{ $_pni['url'] }}"
               @if(!empty($_pni['popup'])) onclick="pnmOpen(); return false;" @endif
               style="display:inline-flex;align-items:center;padding:5px 12px;font-size:12px;font-weight:500;border-radius:7px;border:1.5px solid;text-decoration:none;white-space:nowrap;transition:all .12s;
                      {{ $active === $_pni['key']
                           ? 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;font-weight:600;'
                           : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}"
               @if($active !== $_pni['key'])
               onmouseover="this.style.background='#f9fafb';this.style.borderColor='#d1d5db'"
               onmouseout="this.style.background='#fff';this.style.borderColor='#e5e7eb'"
               @endif
            >{{ $_pni['label'] }}</a>
            @endforeach
        </div>
    </div>
    @hasSection('page-actions')
    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:8px 16px;border-top:1px solid #f3f4f6;">
        @yield('page-actions')
    </div>
    @endif
</div>

{{-- ── 멤버 팝업 ── --}}
<div id="pnm-overlay" class="pnm-overlay" onclick="pnmClose()"></div>
<div id="pnm-modal"   class="pnm-modal">
    <div class="pnm-head">
        <h3>{{ __('projects.member_management') }}</h3>
        <button class="pnm-head-close" onclick="pnmClose()" title="{{ __('common.close') }}">&times;</button>
    </div>
    <div id="pnm-content" class="pnm-body"></div>
</div>

<script>
(async function() {
const PNM_JSON_URL  = '{{ route("projects.members.json",  $project) }}';
const PNM_STORE_URL = '{{ route("projects.members.store", $project) }}';
const PNM_BASE_URL  = '{{ url("projects/" . $project->id . "/members") }}';
const PNM_CSRF      = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const PNM_IS_MGR    = {{ ($_pnUser->isAdmin() || $project->getMemberRole($_pnUser) === 'manager') ? 'true' : 'false' }};

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.pnmOpen = async function() {
    document.getElementById('pnm-overlay').style.display = 'block';
    document.getElementById('pnm-modal').classList.add('is-open');
    document.getElementById('pnm-content').innerHTML =
        '<p class="pnm-empty">{{ __('projects.loading_members') }}</p>';

    fetch(PNM_JSON_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': PNM_CSRF() } })
        .then(r => r.json())
        .then(d => pnmRender(d))
        .catch(() => {
            document.getElementById('pnm-content').innerHTML =
                '<p class="pnm-empty" style="color:#ef4444;">{{ __('projects.loading_failed') }}</p>';
        });
};

window.pnmClose = async function() {
    document.getElementById('pnm-overlay').style.display = 'none';
    document.getElementById('pnm-modal').classList.remove('is-open');
};

async function pnmRender(d) {
    const { members, availableUsers } = d;
    let html = '';

    if (PNM_IS_MGR && availableUsers.length > 0) {
        const opts = availableUsers.map(u =>
            `<option value="${u.id}">${esc(u.name)} (${esc(u.email)})</option>`
        ).join('');
        html += `
        <div class="pnm-add-section">
            <p class="pnm-section-label">{{ __('projects.member_add_section') }}</p>
            <div class="pnm-add-row">
                <select id="pnm-user-sel" class="pnm-select pnm-select-user">
                    <option value="">{{ __('projects.select_user_placeholder') }}</option>${opts}
                </select>
                <select id="pnm-role-sel" class="pnm-select pnm-select-role">
                    <option value="member">{{ __('projects.role_member') }}</option>
                    <option value="manager">{{ __('projects.role_manager') }}</option>
                    <option value="viewer">{{ __('projects.role_viewer_label') }}</option>
                </select>
                <button id="pnm-add-btn" class="pnm-btn-add" onclick="pnmAdd()">{{ __('projects.add_btn') }}</button>
            </div>
            <p id="pnm-add-err" class="pnm-err"></p>
        </div>`;
    }

    html += `<p class="pnm-section-label" id="pnm-mem-title">{{ __('projects.current_members', ['count' => '${members.length}']) }}</p>`;
    html += `<div id="pnm-list">`;
    if (members.length === 0) {
        html += `<p class="pnm-empty">{{ __('projects.member_none') }}</p>`;
    } else {
        members.forEach(m => { html += pnmRow(m); });
    }
    html += `</div>`;

    document.getElementById('pnm-content').innerHTML = html;
}

function pnmRow(m) {
    const initial = esc(m.name.charAt(0));
    const roleMeta = {
        manager: { bg:'#eef2ff', color:'#4f46e5', label:@json(__('projects.role_manager')) },
        member:  { bg:'#f3f4f6', color:'#6b7280', label:@json(__('projects.role_member')) },
        viewer:  { bg:'#d1fae5', color:'#059669', label:@json(__('projects.role_viewer_label')) },
    };
    const rm = roleMeta[m.role] ?? roleMeta.member;

    let right = '';
    if (PNM_IS_MGR) {
        const disAttr = m.is_self ? 'disabled' : '';
        right = `
        <select class="pnm-role-sel" ${disAttr} onchange="pnmChangeRole(${m.id}, this.value)">
            <option value="manager" ${m.role==='manager'?'selected':''}>{{ __('projects.role_manager') }}</option>
            <option value="member"  ${m.role==='member' ?'selected':''}>{{ __('projects.role_member') }}</option>
            <option value="viewer"  ${m.role==='viewer' ?'selected':''}>{{ __('projects.role_viewer_label') }}</option>
        </select>
        ${!m.is_self ? `
        <button class="pnm-btn-del" onclick="pnmRemove(${m.id}, '${esc(m.name)}')" title="{{ __('projects.member_remove_title') }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>` : ''}`;
    } else {
        right = `<span class="pnm-role-badge" style="background:${rm.bg};color:${rm.color};">${rm.label}</span>`;
    }

    return `
    <div class="pnm-row" id="pnm-row-${m.id}">
        <div class="pnm-row-left">
            <div class="pnm-avatar">${initial}</div>
            <div style="min-width:0;">
                <p class="pnm-name">${esc(m.name)}</p>
                <p class="pnm-email">${esc(m.email)}</p>
            </div>
        </div>
        <div class="pnm-row-right">${right}</div>
    </div>`;
}

window.pnmAdd = async function() {
    const sel   = document.getElementById('pnm-user-sel');
    const role  = document.getElementById('pnm-role-sel').value;
    const errEl = document.getElementById('pnm-add-err');
    const btn   = document.getElementById('pnm-add-btn');

    if (!sel.value) {
        errEl.textContent = @json(__('projects.select_user_required'));
        errEl.style.display = 'block'; return;
    }
    errEl.style.display = 'none';
    btn.disabled = true; btn.textContent = @json(__('projects.adding'));

    try {
        const res = await fetch(PNM_STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': PNM_CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: sel.value, role }),
        });
        const d = await res.json();
        if (d.ok) { pnmOpen(); }
        else {
            errEl.textContent = d.message || @json(__('projects.add_failed'));
            errEl.style.display = 'block';
            btn.disabled = false; btn.textContent = @json(__('projects.add_btn'));
        }
    } catch {
        errEl.textContent = @json(__('common.error'));
        errEl.style.display = 'block';
        btn.disabled = false; btn.textContent = @json(__('projects.add_btn'));
    }
};

window.pnmChangeRole = async function(memberId, role) {
    const res = await fetch(`${PNM_BASE_URL}/${memberId}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': PNM_CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ role }),
    });
    const d = await res.json();
    if (!d.ok) { alert(d.message || @json(__('projects.role_change_failed'))); pnmOpen(); }
};

window.pnmRemove = async function(memberId, name) {
    if (!await __confirm(@json(__('projects.member_remove_confirm_name')).replace(':name', name))) return;
    const res = await fetch(`${PNM_BASE_URL}/${memberId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': PNM_CSRF(), 'Accept': 'application/json' },
    });
    const d = await res.json();
    if (d.ok) {
        document.getElementById(`pnm-row-${memberId}`)?.remove();
        const count = document.getElementById('pnm-list')?.querySelectorAll('.pnm-row').length ?? 0;
        const t = document.getElementById('pnm-mem-title');
        if (t) t.textContent = @json(__('projects.current_members')).replace(':count', count);
    } else {
        alert(d.message || @json(__('projects.remove_failed')));
    }
};

document.addEventListener('keydown', e => { if (e.key === 'Escape') pnmClose(); });
})();
</script>
