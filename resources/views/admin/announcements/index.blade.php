@extends('layouts.admin')
@section('title', '공지사항 관리')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div style="font-size:13px;color:#64748b;">총 {{ $announcements->total() }}건</div>
    <button onclick="openCreateModal()" class="btn-primary">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        새 공지사항
    </button>
</div>

<div class="admin-card" style="padding:0;overflow:hidden;">
    @if($announcements->isEmpty())
    <div style="text-align:center;padding:48px 0;color:#94a3b8;font-size:14px;">등록된 공지사항이 없습니다.</div>
    @else
    <table class="admin-table">
        <thead>
            <tr>
                <th>구분</th>
                <th>제목</th>
                <th>노출 기간</th>
                <th>상태</th>
                <th>등록자</th>
                <th>등록일</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($announcements as $ann)
            @php
                $typeMap = [
                    'info'        => ['badge-blue',   '안내'],
                    'warning'     => ['badge-yellow', '주의'],
                    'maintenance' => ['badge-red',    '점검'],
                    'update'      => ['badge-purple', '업데이트'],
                ];
                [$cls, $lbl] = $typeMap[$ann->type] ?? ['badge-gray', $ann->type];
            @endphp
            <tr>
                <td><span class="badge {{ $cls }}">{{ $lbl }}</span></td>
                <td>
                    <div style="font-weight:500;color:#1e293b;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ann->title }}</div>
                </td>
                <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                    @if($ann->starts_at || $ann->ends_at)
                        {{ $ann->starts_at?->format('Y.m.d') ?? '—' }} ~ {{ $ann->ends_at?->format('Y.m.d') ?? '—' }}
                    @else
                        <span style="color:#cbd5e1;">상시</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.announcements.toggle', $ann) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="badge {{ $ann->is_active ? 'badge-green' : 'badge-gray' }}" style="border:none;cursor:pointer;">
                            {{ $ann->is_active ? '활성' : '비활성' }}
                        </button>
                    </form>
                </td>
                <td style="font-size:12px;color:#64748b;">{{ $ann->creator?->name ?? '—' }}</td>
                <td style="font-size:12px;color:#94a3b8;white-space:nowrap;">{{ $ann->created_at->format('Y.m.d') }}</td>
                <td>
                    <div style="display:flex;gap:8px;">
                        <button onclick='openEditModal(@json($ann))' class="btn-secondary" style="padding:4px 10px;font-size:12px;">수정</button>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger" style="padding:4px 10px;font-size:12px;">삭제</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($announcements->hasPages())
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;display:flex;justify-content:center;">
        {{ $announcements->links() }}
    </div>
    @endif
    @endif
</div>

{{-- Create / Edit Modal --}}
<div id="ann-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9998;" onclick="closeModal()"></div>
<div id="ann-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;">
        <span id="modal-title" style="font-size:15px;font-weight:700;color:#1e293b;">새 공지사항</span>
        <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;line-height:1;">&times;</button>
    </div>
    <form id="ann-form" method="POST" style="padding:18px 22px 22px;">
        @csrf
        <input type="hidden" id="form-method" name="_method" value="POST">
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">구분</label>
                <select name="type" id="f-type" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;">
                    <option value="info">안내</option>
                    <option value="warning">주의</option>
                    <option value="maintenance">점검</option>
                    <option value="update">업데이트</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">제목 <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" id="f-title" required style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">내용 <span style="color:#ef4444;">*</span></label>
                <textarea name="body" id="f-body" required rows="5" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">시작일 (선택)</label>
                    <input type="datetime-local" name="starts_at" id="f-starts" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">종료일 (선택)</label>
                    <input type="datetime-local" name="ends_at" id="f-ends" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;">
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;cursor:pointer;">
                <input type="checkbox" name="is_active" id="f-active" value="1" checked style="accent-color:#6366f1;width:15px;height:15px;">
                즉시 활성화
            </label>

            {{-- 수신 대상 --}}
            <div style="border-top:1px solid #f1f5f9;padding-top:14px;margin-top:4px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">수신 대상</label>
                <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:#334155;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="target_type" id="t-all" value="all" checked style="accent-color:#6366f1;" onchange="toggleTargetCompanies()">
                        전체 사용자
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="target_type" id="t-withworks" value="withworks" style="accent-color:#7c3aed;" onchange="toggleTargetCompanies()">
                        WITHWORKS 사용 회사 소속만
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="target_type" id="t-companies" value="companies" style="accent-color:#0284c7;" onchange="toggleTargetCompanies()">
                        특정 회사 선택
                    </label>
                </div>
                <div id="target-companies-box" style="display:none;margin-top:8px;padding:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;max-height:160px;overflow-y:auto;">
                    @foreach($companyGroups as $cg)
                        <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:#334155;cursor:pointer;padding:3px 2px;">
                            <input type="checkbox" name="target_company_group_ids[]" value="{{ $cg->id }}" class="f-target-cg" style="accent-color:#0284c7;">
                            <span>{{ $cg->name }}</span>
                            @if($cg->uses_withworks)
                                <span style="font-size:10px;background:#ede9fe;color:#6d28d9;padding:1px 5px;border-radius:4px;">WITHWORKS</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 이메일 발송 옵션 --}}
            <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#334155;cursor:pointer;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:8px 10px;">
                <input type="checkbox" name="send_email" id="f-send-email" value="1" style="accent-color:#d97706;width:15px;height:15px;margin-top:2px;">
                <span>
                    <strong>이메일도 함께 발송</strong>
                    <span style="display:block;font-size:11.5px;color:#92400e;margin-top:2px;">대상 사용자에게 SMTP 이메일을 백그라운드로 발송합니다. (체크 해제 시 메일함에만 적재)</span>
                </span>
            </label>

            {{-- 수정 모드에서만: 재발송 옵션 --}}
            <label id="resend-row" style="display:none;align-items:flex-start;gap:8px;font-size:13px;color:#334155;cursor:pointer;background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;">
                <input type="checkbox" name="resend" id="f-resend" value="1" style="accent-color:#dc2626;width:15px;height:15px;margin-top:2px;">
                <span>
                    <strong>재발송</strong>
                    <span style="display:block;font-size:11.5px;color:#b91c1c;margin-top:2px;">체크 시 저장 후 대상자에게 다시 발송합니다 (메일함에 새 메시지 추가).</span>
                </span>
            </label>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
            <button type="button" onclick="closeModal()" class="btn-secondary">취소</button>
            <button type="submit" class="btn-primary">저장</button>
        </div>
    </form>
</div>

<script>
const BASE_URL = '{{ route('admin.announcements.index') }}';

function toggleTargetCompanies() {
    const isCompanies = document.getElementById('t-companies').checked;
    document.getElementById('target-companies-box').style.display = isCompanies ? 'block' : 'none';
}

function setTargetType(t) {
    document.getElementById('t-all').checked        = (t === 'all');
    document.getElementById('t-withworks').checked  = (t === 'withworks');
    document.getElementById('t-companies').checked  = (t === 'companies');
    toggleTargetCompanies();
}

function setTargetCompanyIds(ids) {
    const set = new Set((ids || []).map(Number));
    document.querySelectorAll('.f-target-cg').forEach(cb => {
        cb.checked = set.has(Number(cb.value));
    });
}

async function openCreateModal() {
    document.getElementById('modal-title').textContent = '새 공지사항';
    document.getElementById('ann-form').action = BASE_URL;
    document.getElementById('form-method').value = 'POST';
    document.getElementById('f-type').value    = 'info';
    document.getElementById('f-title').value   = '';
    document.getElementById('f-body').value    = '';
    document.getElementById('f-starts').value  = '';
    document.getElementById('f-ends').value    = '';
    document.getElementById('f-active').checked = true;
    document.getElementById('f-send-email').checked = false;
    setTargetType('all');
    setTargetCompanyIds([]);
    document.getElementById('resend-row').style.display = 'none';
    document.getElementById('f-resend').checked = false;
    showModal();
}

async function openEditModal(ann) {
    document.getElementById('modal-title').textContent = '공지사항 수정';
    document.getElementById('ann-form').action = BASE_URL + '/' + ann.id;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('f-type').value    = ann.type;
    document.getElementById('f-title').value   = ann.title;
    document.getElementById('f-body').value    = ann.body;
    document.getElementById('f-starts').value  = ann.starts_at ? ann.starts_at.replace(' ', 'T').substring(0,16) : '';
    document.getElementById('f-ends').value    = ann.ends_at   ? ann.ends_at.replace(' ', 'T').substring(0,16)   : '';
    document.getElementById('f-active').checked = !!ann.is_active;
    document.getElementById('f-send-email').checked = !!ann.send_email;
    setTargetType(ann.target_type || 'all');
    setTargetCompanyIds(ann.target_company_group_ids || []);
    document.getElementById('resend-row').style.display = 'flex';
    document.getElementById('f-resend').checked = false;
    showModal();
}

async function showModal() {
    document.getElementById('ann-overlay').style.display = 'block';
    document.getElementById('ann-modal').style.display   = 'block';
}

async function closeModal() {
    document.getElementById('ann-overlay').style.display = 'none';
    document.getElementById('ann-modal').style.display   = 'none';
}
</script>

@endsection
