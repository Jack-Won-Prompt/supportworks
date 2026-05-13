@extends('layouts.admin')

@section('title', __('admin.app_versions'))

@section('header-actions')
<button class="btn-primary" onclick="document.getElementById('modal-add').style.display='flex'">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    {{ __('admin.add_version') }}
</button>
@endsection

@section('content')

<div class="admin-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>{{ __('admin.version') }}</th>
                <th>{{ __('admin.download_url') }}</th>
                <th>{{ __('admin.appv_changes') }}</th>
                <th>{{ __('admin.col_status') }}</th>
                <th>{{ __('admin.col_created') }}</th>
                <th style="text-align:right;">{{ __('admin.appv_job') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($versions as $v)
            <tr>
                <td>
                    <span style="font-weight:700;font-size:14px;color:#1e293b;">v{{ $v->version }}</span>
                </td>
                <td>
                    @if($v->download_url)
                    <a href="{{ $v->download_url }}" target="_blank"
                       style="font-size:12px;color:#6366f1;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;max-width:300px;">
                        {{ $v->download_url }}
                    </a>
                    @else
                    <span style="color:#94a3b8;font-size:12px;">—</span>
                    @endif
                </td>
                <td style="max-width:260px;">
                    <span style="font-size:12px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">
                        {{ $v->release_notes ?: '—' }}
                    </span>
                </td>
                <td>
                    @if($v->is_active)
                        <span class="badge badge-green">{{ __('admin.appv_active_label') }}</span>
                    @else
                        <span class="badge badge-gray">{{ __('admin.appv_inactive_label') }}</span>
                    @endif
                </td>
                <td style="font-size:12px;color:#94a3b8;">{{ $v->created_at->format('Y.m.d') }}</td>
                <td style="text-align:right;">
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        @if(!$v->is_active)
                        <form action="{{ route('admin.app-versions.activate', $v) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-secondary" style="padding:5px 10px;font-size:11px;"
                                data-confirm="v{{ $v->version }}{{ __('admin.appv_confirm_activate') }}">
                                {{ __('admin.appv_activate') }}
                            </button>
                        </form>
                        @endif
                        <button class="btn-secondary" style="padding:5px 10px;font-size:11px;"
                            onclick='openEditModal({{ json_encode(["id"=>$v->id,"version"=>$v->version,"release_notes"=>$v->release_notes,"download_url"=>$v->download_url]) }})'>
                            {{ __('admin.appv_edit') }}
                        </button>
                        <form action="{{ route('admin.app-versions.destroy', $v) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger" style="padding:5px 10px;font-size:11px;"
                                data-confirm="v{{ $v->version }}{{ __('admin.appv_confirm_delete') }}">
                                {{ __('admin.appv_delete') }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">
                    {{ __('admin.no_versions') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- 버전 추가 모달 --}}
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:480px;padding:24px;position:relative;">
        <h3 style="font-size:15px;font-weight:700;margin:0 0 20px;">{{ __('admin.appv_add_modal_title') }}</h3>
        <form action="{{ route('admin.app-versions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin.app-versions._form')
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-add').style.display='none'">{{ __('admin.appv_cancel') }}</button>
                <button type="submit" class="btn-primary">{{ __('admin.appv_save') }}</button>
            </div>
        </form>
        <button onclick="document.getElementById('modal-add').style.display='none'"
            style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
    </div>
</div>

{{-- 버전 수정 모달 --}}
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;width:480px;padding:24px;position:relative;">
        <h3 style="font-size:15px;font-weight:700;margin:0 0 20px;">{{ __('admin.appv_edit_modal_title') }}</h3>
        <form id="form-edit" action="" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.app-versions._form', ['isEdit' => true])
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-edit').style.display='none'">{{ __('admin.appv_cancel') }}</button>
                <button type="submit" class="btn-primary">{{ __('admin.appv_save') }}</button>
            </div>
        </form>
        <button onclick="document.getElementById('modal-edit').style.display='none'"
            style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openEditModal(v) {
    document.getElementById('form-edit').action = '/admin/app-versions/' + v.id;
    document.getElementById('edit-version').value = v.version;
    document.getElementById('edit-notes').value   = v.release_notes || '';
    const urlEl = document.getElementById('edit-current-url');
    if (urlEl) {
        urlEl.textContent = v.download_url ? v.download_url.split('/').pop() : '';
        urlEl.href        = v.download_url || '#';
    }
    document.getElementById('modal-edit').style.display = 'flex';
}
// 모달 외부 클릭 시 닫기
['modal-add','modal-edit'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endsection
