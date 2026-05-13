@extends('layouts.admin')

@section('title', __('admin.maintenance_detail') . ' — ' . $maintenance->title)

@section('content')
<div style="max-width:820px;">

    {{-- 뒤로가기 --}}
    <a href="{{ route('admin.maintenances.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;text-decoration:none;margin-bottom:16px;"
       onmouseover="this.style.color='#334155'" onmouseout="this.style.color='#64748b'">
        {{ __('admin.maint_back') }}
    </a>

    @if(session('success'))
    <div style="margin-bottom:14px;padding:10px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:9px;font-size:13px;color:#15803d;font-weight:500;">{{ session('success') }}</div>
    @endif

    {{-- 요청 카드 --}}
    <div class="admin-card" style="padding:0;overflow:hidden;margin-bottom:16px;">

        {{-- 헤더 --}}
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f8fafc,#f1f5f9);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
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
                    <h2 style="margin:0 0 6px;font-size:17px;font-weight:700;color:#0f172a;line-height:1.4;">{{ $maintenance->title }}</h2>
                    <div style="font-size:12px;color:#94a3b8;">
                        {{ $maintenance->user->name }} · {{ $maintenance->created_at->format('Y.m.d H:i') }}
                    </div>
                </div>

                {{-- 상태 변경 --}}
                <form method="POST" action="{{ route('admin.maintenances.status', $maintenance) }}" style="flex-shrink:0;">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;color:#334155;outline:none;cursor:pointer;background:#fff;">
                        @foreach(['pending'=>__('admin.maint_status_pending'),'in_progress'=>__('admin.maint_status_in_progress'),'completed'=>__('admin.maint_status_completed'),'rejected'=>__('admin.maint_status_rejected')] as $val=>$lbl)
                        <option value="{{ $val }}" {{ $maintenance->status===$val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        {{-- 날짜 정보 --}}
        <div style="padding:14px 24px;border-bottom:1px solid #f1f5f9;display:flex;gap:28px;flex-wrap:wrap;background:#fafafa;">
            @if($maintenance->requested_date)
            <div>
                <div style="font-size:11px;color:#94a3b8;font-weight:600;">{{ __('admin.maint_request_date_label') }}</div>
                <div style="font-size:13px;font-weight:600;color:#334155;margin-top:2px;">{{ $maintenance->requested_date->format('Y.m.d') }}</div>
            </div>
            @endif
            @if($maintenance->due_date)
            <div>
                <div style="font-size:11px;color:#94a3b8;font-weight:600;">{{ __('admin.maint_request_date_col') }}</div>
                <div style="font-size:13px;font-weight:600;margin-top:2px;
                    color:{{ $maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']) ? '#dc2626' : '#334155' }};">
                    {{ $maintenance->due_date->format('Y.m.d') }}
                    @if($maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']))
                    <span style="font-size:10px;color:#dc2626;margin-left:3px;">{{ __('admin.maint_overdue') }}</span>
                    @elseif(!in_array($maintenance->status,['completed','rejected']))
                    <span style="font-size:10px;color:#94a3b8;margin-left:3px;">D-{{ (int) now()->diffInDays($maintenance->due_date) }}</span>
                    @endif
                </div>
            </div>
            @endif
            {{-- 처리 예정일 인라인 설정 --}}
            <form method="POST" action="{{ route('admin.maintenances.schedule', $maintenance) }}" style="display:flex;align-items:flex-end;gap:8px;">
                @csrf @method('PATCH')
                <div>
                    <div style="font-size:11px;color:#6366f1;font-weight:600;">{{ __('admin.maint_schedule_admin') }} <span style="color:#94a3b8;">{{ __('admin.maint_admin_note') }}</span></div>
                    <input type="date" name="scheduled_date"
                           value="{{ $maintenance->scheduled_date?->format('Y-m-d') }}"
                           style="margin-top:2px;padding:6px 10px;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#ddd6fe'">
                </div>
                <button type="submit"
                        style="padding:6px 14px;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('admin.maint_save_btn') }}</button>
            </form>
        </div>

        {{-- 본문 --}}
        <div style="padding:20px 24px;">
            <div class="sr-content-view" style="color:#334155;">{!! $maintenance->content !!}</div>
        </div>
    </div>

    {{-- 답글 목록 --}}
    <div class="admin-card" style="padding:0;overflow:hidden;margin-bottom:16px;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:14px;font-weight:700;color:#0f172a;">{{ __('admin.maint_reply_history') }}</h3>
            <span style="font-size:12px;color:#94a3b8;">{{ $maintenance->replies->count() }}</span>
        </div>

        @forelse($maintenance->replies as $reply)
        <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;{{ $reply->isAdminReply() ? 'background:#f8f7ff;' : '' }}">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="width:34px;height:34px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;
                            background:{{ $reply->isAdminReply() ? 'linear-gradient(135deg,#6366f1,#4f46e5)' : 'linear-gradient(135deg,#0ea5e9,#0284c7)' }};">
                    {{ mb_substr($reply->authorName(), 0, 1) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:700;color:#0f172a;">{{ $reply->authorName() }}</span>
                        @if($reply->isAdminReply())
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:5px;background:#eef2ff;color:#6366f1;">{{ __('admin.maint_admin_label') }}</span>
                        @endif
                        <span style="font-size:11px;color:#94a3b8;">{{ $reply->created_at->format('Y.m.d H:i') }}</span>
                    </div>
                    <div class="sr-reply-content" style="color:#334155;">{!! $reply->content !!}</div>
                </div>
                <form method="POST" action="{{ route('admin.maintenance-replies.destroy', $reply) }}"
                      onsubmit="return confirm('{{ __('admin.maint_delete_reply_confirm') }}')" style="flex-shrink:0;">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="padding:4px 8px;background:transparent;border:1px solid #fecaca;border-radius:6px;font-size:11px;color:#ef4444;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">{{ __('admin.maint_delete') }}</button>
                </form>
            </div>
        </div>
        @empty
        <div style="padding:28px;text-align:center;color:#94a3b8;font-size:13px;">{{ __('admin.maint_no_replies') }}</div>
        @endforelse
    </div>

    {{-- 답글 작성 --}}
    @if(!in_array($maintenance->status, ['completed', 'rejected']))
    <div class="admin-card" style="overflow:hidden;">
        <h3 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#0f172a;">{{ __('admin.maint_write_reply') }}</h3>
        <form method="POST" action="{{ route('admin.maintenances.replies.store', $maintenance) }}" id="admin-reply-form">
            @csrf
            <div class="sr-reply-editor-wrap" style="--border-focus:#6366f1;">
                <div id="admin-reply-editor" style="min-height:110px;"></div>
            </div>
            <input type="hidden" name="content" id="admin-reply-content">
            @error('content')
            <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
            @enderror
            <div style="margin-top:12px;display:flex;align-items:center;gap:10px;">
                <button type="submit" id="admin-reply-btn"
                        style="padding:10px 24px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    {{ __('admin.maint_reply_submit') }}
                </button>
                <span style="font-size:12px;color:#94a3b8;">{{ __('admin.maint_reply_auto_note') }}</span>
            </div>
        </form>
    </div>
    @else
    <div style="padding:14px 20px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;text-align:center;font-size:13px;color:#94a3b8;">
        {{ $maintenance->status === 'completed' ? __('admin.maint_completed_msg') : __('admin.maint_rejected_msg') }}
    </div>
    @endif

</div>
@endsection

@push('styles')
@include('maintenance._quill_assets')
@endpush

@section('scripts')
<script>
@if(!in_array($maintenance->status, ['completed','rejected']))
(async function(){
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const q = createSrEditor('admin-reply-editor', 'admin-reply-content',
        '{{ __("admin.maint_reply_placeholder") }}', true, CSRF);

    document.getElementById('admin-reply-form').addEventListener('submit', async function(e) {
        if (q.getText().trim() === '') { e.preventDefault(); return; }
        document.getElementById('admin-reply-content').value = q.root.innerHTML;
    });
})();
@endif
</script>
@endsection
