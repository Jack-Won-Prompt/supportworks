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
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
            <button type="button" onclick="closeModal()" class="btn-secondary">취소</button>
            <button type="submit" class="btn-primary">저장</button>
        </div>
    </form>
</div>

<script>
const BASE_URL = '{{ route('admin.announcements.index') }}';

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
