@php
    $isMine     = $lv->user_id === auth()->id();
    $canEdit    = $isMine || $isManager;
    $isUpcoming = $lv->status === 'approved' && $lv->start_date->startOfDay()->gt(now()->startOfDay());
    $dateRange = $lv->start_date->format('Y-m-d') === $lv->end_date->format('Y-m-d')
        ? $lv->start_date->format('Y.m.d')
        : $lv->start_date->format('Y.m.d') . ' ~ ' . $lv->end_date->format('Y.m.d');
    $days = in_array($lv->leave_type, ['half_day_am','half_day_pm'])
        ? __('work.leave_half_day_unit')
        : $lv->days_count . __('work.leave_unit_days');
    $lvJson = json_encode([
        'id'          => $lv->id,
        'user_id'     => $lv->user_id,
        'approver_id' => $lv->approver_id,
        'start_date'  => $lv->start_date->format('Y-m-d'),
        'end_date'    => $lv->end_date->format('Y-m-d'),
        'leave_type'  => $lv->leave_type,
        'reason'      => $lv->reason,
        'status'      => $lv->status,
    ]);
@endphp
<tr id="lv-row-{{ $lv->id }}" data-user-id="{{ $lv->user_id }}" style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
    <td style="padding:12px 20px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ mb_substr($lv->user->name, 0, 1) }}
            </div>
            <span style="font-size:13px;font-weight:500;color:#1e293b;">{{ $lv->user->name }}</span>
        </div>
    </td>
    <td style="padding:12px;">
        <span style="font-size:12px;padding:2px 9px;border-radius:20px;font-weight:600;background:{{ $lv->leave_type_bg }};color:{{ $lv->leave_type_color }};border:1px solid {{ $lv->leave_type_color }};">
            {{ $lv->leave_type_label }}
        </span>
    </td>
    <td style="padding:12px;">
        <div style="font-size:12px;color:#374151;">{{ $dateRange }}</div>
        <div style="font-size:11px;color:#94a3b8;">{{ $days }}</div>
    </td>
    <td style="padding:12px;max-width:200px;">
        <span style="font-size:12px;color:#64748b;">{{ $lv->reason ?: '—' }}</span>
    </td>
    <td style="padding:12px;">
        @if($isUpcoming)
        <span style="font-size:12px;padding:2px 9px;border-radius:20px;font-weight:600;background:#ede9fe;color:#6d28d9;border:1px dashed #a78bfa;">
            {{ __('work.leave_status_upcoming') }}
        </span>
        @else
        <span style="font-size:12px;padding:2px 9px;border-radius:20px;font-weight:600;background:{{ $lv->status_bg }};color:{{ $lv->status_color }};">
            {{ $lv->status_label }}
        </span>
        @endif
        @if($lv->approver)
        <div style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ __('work.leave_approver_label') }}{{ $lv->approver->name }}</div>
        @endif
        @php $canDecide = $lv->status === 'pending' && ($isManager || $lv->approver_id === auth()->id()); @endphp
        @if($canDecide)
        <div style="margin-top:5px;display:flex;gap:4px;">
            <button onclick="changeStatus({{ $lv->id }},'approved')" style="font-size:11px;padding:3px 9px;border-radius:6px;background:#d1fae5;color:#059669;border:none;cursor:pointer;font-weight:600;">{{ __('work.leave_status_approve') }}</button>
            <button onclick="changeStatus({{ $lv->id }},'rejected')" style="font-size:11px;padding:3px 9px;border-radius:6px;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;font-weight:600;">{{ __('work.leave_status_reject') }}</button>
        </div>
        @endif
    </td>
    <td style="padding:12px 20px;text-align:right;white-space:nowrap;">
        @if($canEdit)
        <button onclick="openLeaveModal({{ $lvJson }})"
                style="font-size:12px;color:#6366f1;background:none;border:none;cursor:pointer;padding:0 4px;">{{ __('common.edit') }}</button>
        <button onclick="deleteLeave({{ $lv->id }})"
                style="font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;padding:0 4px;">{{ __('common.delete') }}</button>
        @endif
    </td>
</tr>
