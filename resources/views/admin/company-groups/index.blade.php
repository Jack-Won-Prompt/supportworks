@extends('layouts.admin')

@section('title', __('admin.company_manage'))

@section('header-actions')
<a href="{{ route('admin.company-groups.create') }}" class="btn-primary">+ {{ __('admin.company_create') }}</a>
@endsection

@section('content')
<div class="admin-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>{{ __('admin.company_name') }}</th>
                <th>{{ __('admin.col_code') }}</th>
                <th>{{ __('admin.col_description') }}</th>
                <th style="text-align:center;">{{ __('admin.user') }}</th>
                <th style="text-align:center;">{{ __('admin.col_assigned_admin') }}</th>
                <th style="text-align:center;">{{ __('admin.col_status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
            <tr>
                <td style="font-weight:600;color:#1e293b;">{{ $group->name }}</td>
                <td><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">{{ $group->code }}</code></td>
                <td style="color:#64748b;font-size:12px;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $group->description ?? '-' }}</td>
                <td style="text-align:center;">
                    <span style="font-size:13px;font-weight:600;color:#6366f1;">{{ $group->users_count }}</span>
                    <span style="font-size:11px;color:#94a3b8;">{{ __('admin.members_suffix') }}</span>
                </td>
                <td style="text-align:center;">
                    <span style="font-size:13px;font-weight:600;color:#0891b2;">{{ $group->admin_users_count }}</span>
                    <span style="font-size:11px;color:#94a3b8;">{{ __('admin.members_suffix') }}</span>
                </td>
                <td style="text-align:center;">
                    @if($group->is_active)
                    <span class="badge badge-green">{{ __('admin.active') }}</span>
                    @else
                    <span class="badge badge-gray">{{ __('admin.inactive') }}</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="button" onclick="openInviteFor({{ $group->id }}, '{{ addslashes($group->name) }}')"
                                style="font-size:12px;color:#7c3aed;background:none;border:none;cursor:pointer;font-weight:600;">{{ __('admin.admin_invite') }}</button>
                        <a href="{{ route('admin.company-groups.edit', $group) }}" style="font-size:12px;color:#6366f1;text-decoration:none;">{{ __('admin.edit') }}</a>
                        @if($group->users_count === 0)
                        @php $deleteConfirm = __('admin.confirm_delete_group', ['name' => $group->name]); @endphp
                        <form method="POST" action="{{ route('admin.company-groups.destroy', $group) }}"
                              onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $deleteConfirm }}">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;">{{ __('admin.delete') }}</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                    {{ __('admin.no_company_groups') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($groups->hasPages())
<div style="margin-top:16px;">{{ $groups->links() }}</div>
@endif

{{-- 이메일 초대 모달 --}}
<div id="idx-invite-modal" onclick="if(event.target===this)closeIdxInvite()" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;" onclick="event.stopPropagation()">
        <div style="padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h3 style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">{{ __('admin.invite_email_modal') }}</h3>
                <p style="font-size:12px;color:#94a3b8;margin:2px 0 0;"><span id="idx-invite-group-name" style="color:#7c3aed;font-weight:600;"></span> {{ __('admin.invite_group_suffix') }}</p>
            </div>
            <button onclick="closeIdxInvite()" style="background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;line-height:1;">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.users.invite') }}" id="idx-invite-form">
            @csrf
            <input type="hidden" name="company_group_id" id="idx-invite-group-id">
            <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">{{ __('admin.col_email') }} <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" required placeholder="invite@example.com"
                           style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;">{{ __('admin.invite_message_label') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.optional') }}</span></label>
                    <textarea name="message" rows="2" maxlength="500" placeholder="{{ __('admin.invite_msg_placeholder') }}"
                              style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:none;outline:none;box-sizing:border-box;"
                              onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>
            </div>
            <div style="padding:12px 22px 18px;display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeIdxInvite()"
                        style="padding:8px 18px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#64748b;background:#fff;cursor:pointer;">{{ __('admin.cancel') }}</button>
                <button type="submit"
                        style="padding:8px 22px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('admin.send_invite_email') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const _idxModal = document.getElementById('idx-invite-modal');
async function openInviteFor(groupId, groupName) {
    document.getElementById('idx-invite-group-id').value = groupId;
    document.getElementById('idx-invite-group-name').textContent = groupName;
    _idxModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
async function closeIdxInvite() {
    _idxModal.style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeIdxInvite(); });
</script>
@endsection
