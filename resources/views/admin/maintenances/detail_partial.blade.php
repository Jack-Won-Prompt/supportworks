<div data-maintenance-id="{{ $maintenance->id }}">

{{-- 고정 영역 --}}
<div data-fixed-header>

    {{-- 헤더: 제목 + 배지 + 상태 변경 --}}
    <div style="padding:18px 22px 14px;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;
                                 color:{{ $maintenance->status_color }};background:{{ $maintenance->status_bg }};">
                        {{ $maintenance->status_label }}
                    </span>
                    <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;
                                 color:{{ $maintenance->priority_color }};background:{{ $maintenance->priority === 'urgent' ? '#fee2e2' : ($maintenance->priority === 'high' ? '#fef3c7' : '#f3f4f6') }};">
                        {{ $maintenance->priority_label }}
                    </span>
                    <span style="font-size:11px;color:#94a3b8;">{{ $maintenance->project->name }}</span>
                </div>
                <h2 style="margin:0 0 5px;font-size:16px;font-weight:700;color:#0f172a;line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $maintenance->title }}</h2>
                <div style="font-size:12px;color:#94a3b8;">{{ $maintenance->user->name }} · {{ $maintenance->created_at->format('Y.m.d H:i') }}</div>
            </div>

            {{-- 상태 변경 (관리자) --}}
            <form data-status-form
                  data-status-url="{{ route('admin.maintenances.status', $maintenance) }}"
                  style="flex-shrink:0;">
                @csrf @method('PATCH')
                <select data-status-select name="status"
                        style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;color:#334155;outline:none;cursor:pointer;background:#fff;">
                    @foreach(['pending'=>__('admin.maint_status_pending'),'in_progress'=>__('admin.maint_status_in_progress'),'completed'=>__('admin.maint_status_completed'),'rejected'=>__('admin.maint_status_rejected')] as $val=>$lbl)
                    <option value="{{ $val }}" {{ $maintenance->status===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </form>

            {{-- 닫기 버튼 --}}
            <button onclick="admCloseDetail()"
                    style="flex-shrink:0;align-self:flex-start;background:transparent;border:none;cursor:pointer;font-size:20px;color:#94a3b8;line-height:1;padding:2px 4px;margin-top:1px;"
                    onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#94a3b8'">✕</button>
        </div>
    </div>

    {{-- 날짜 + 처리 예정일 설정 --}}
    <div style="padding:10px 22px;border-bottom:1px solid #e2e8f0;display:flex;gap:20px;flex-wrap:wrap;align-items:flex-end;background:#fafafa;">
        @if($maintenance->requested_date)
        <div>
            <div style="font-size:10px;color:#94a3b8;font-weight:600;margin-bottom:2px;">{{ __('admin.maint_request_date_label') }}</div>
            <div style="font-size:12px;font-weight:600;color:#334155;">{{ $maintenance->requested_date->format('Y.m.d') }}</div>
        </div>
        @endif
        @if($maintenance->due_date)
        <div>
            <div style="font-size:10px;color:#94a3b8;font-weight:600;margin-bottom:2px;">{{ __('admin.maint_request_date_col') }}</div>
            <div style="font-size:12px;font-weight:600;
                color:{{ $maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']) ? '#dc2626' : '#334155' }};">
                {{ $maintenance->due_date->format('Y.m.d') }}
                @if($maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']))
                <span style="font-size:10px;margin-left:3px;color:#dc2626;">{{ __('admin.maint_overdue') }}</span>
                @elseif(!in_array($maintenance->status,['completed','rejected']))
                <span style="font-size:10px;margin-left:3px;color:#94a3b8;">D-{{ (int) now()->diffInDays($maintenance->due_date) }}</span>
                @endif
            </div>
        </div>
        @endif

        {{-- 처리 예정일 설정 --}}
        <form data-schedule-form
              data-schedule-url="{{ route('admin.maintenances.schedule', $maintenance) }}"
              style="display:flex;align-items:flex-end;gap:7px;margin-left:auto;">
            @csrf @method('PATCH')
            <div>
                <div style="font-size:10px;color:#6366f1;font-weight:600;margin-bottom:2px;">{{ __('admin.maint_schedule_admin') }} <span style="color:#94a3b8;font-weight:400;">{{ __('admin.maint_admin_note') }}</span></div>
                <input type="date" name="scheduled_date"
                       value="{{ $maintenance->scheduled_date?->format('Y-m-d') }}"
                       style="padding:5px 9px;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#ddd6fe'">
            </div>
            <button type="submit"
                    style="padding:5px 13px;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">{{ __('admin.maint_save_btn') }}</button>
        </form>
    </div>

</div>{{-- /data-fixed-header --}}

{{-- 본문 --}}
<div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;min-height:70px;">
    <div class="sr-content-view" style="color:#334155;">{!! $maintenance->content !!}</div>
</div>

{{-- 답글 목록 --}}
<div style="padding:14px 22px 0;border-bottom:1px solid #e2e8f0;">
    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">
        {{ __('admin.maint_reply_history') }}
        <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:4px;">{{ $maintenance->replies->count() }}</span>
    </div>
    @forelse($maintenance->replies as $reply)
    <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #f8fafc;' : '' }}{{ $reply->isAdminReply() ? 'background:transparent;' : '' }}">
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;
                        background:{{ $reply->isAdminReply() ? 'linear-gradient(135deg,#6366f1,#4f46e5)' : 'linear-gradient(135deg,#0ea5e9,#0284c7)' }};">
                {{ mb_substr($reply->authorName(), 0, 1) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;color:#0f172a;">{{ $reply->authorName() }}</span>
                    @if($reply->isAdminReply())
                    <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:5px;background:#eef2ff;color:#6366f1;">{{ __('admin.maint_admin_label') }}</span>
                    @endif
                    <span style="font-size:11px;color:#94a3b8;">{{ $reply->created_at->format('Y.m.d H:i') }}</span>
                    <button data-delete-reply-btn
                            data-url="{{ route('admin.maintenance-replies.destroy', $reply) }}"
                            style="margin-left:auto;padding:2px 8px;background:transparent;border:1px solid #fecaca;border-radius:5px;font-size:11px;color:#ef4444;cursor:pointer;transition:background .12s;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">{{ __('admin.maint_delete') }}</button>
                </div>
                <div class="sr-reply-content" style="color:#334155;">{!! $reply->content !!}</div>
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center;color:#94a3b8;font-size:13px;padding:14px 0;">{{ __('admin.maint_no_replies') }}</div>
    @endforelse
    <div style="height:14px;"></div>
</div>

{{-- 답글 작성 --}}
@if(!in_array($maintenance->status, ['completed', 'rejected']))
<div style="padding:14px 22px;">
    <form data-reply-form
          data-url="{{ route('admin.maintenances.replies.store', $maintenance) }}">
        @csrf
        <div class="sr-reply-editor-wrap">
            <div class="sr-reply-quill-target" style="min-height:80px;"></div>
        </div>
        <input type="hidden" name="content" class="sr-reply-hidden">
        <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <span style="font-size:11px;color:#94a3b8;">{{ __('admin.maint_reply_auto_note2') }}</span>
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                {{ __('admin.maint_reply_submit') }}
            </button>
        </div>
    </form>
</div>
@else
<div style="padding:14px 22px;text-align:center;font-size:12px;color:#94a3b8;">
    {{ $maintenance->status === 'completed' ? __('admin.maint_completed_msg') : __('admin.maint_rejected_msg') }}
</div>
@endif

</div>{{-- /data-maintenance-id --}}
