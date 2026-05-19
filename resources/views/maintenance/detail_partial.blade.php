@php
    $canReply  = (auth()->user()->isAdmin() || auth()->user()->isSrAgent() || $maintenance->user_id === auth()->id())
                  && !in_array($maintenance->status, ['completed', 'rejected']);
@endphp

{{-- 고정 영역: 헤더 + 날짜 (JS가 dt-fixed-header로 이동) --}}
<div data-fixed-header>

{{-- 헤더 (타이틀 + 날짜 한 칸) --}}
<div style="padding:16px 22px 14px;border-bottom:1px solid #ede9fe;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:9px;">
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;color:{{ $maintenance->status_color }};background:{{ $maintenance->status_bg }};">{{ $maintenance->status_label }}</span>
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:10px;color:{{ $maintenance->priority_color }};background:{{ $maintenance->priority === 'urgent' ? '#fee2e2' : ($maintenance->priority === 'high' ? '#fef3c7' : '#f3f4f6') }};">{{ $maintenance->priority_label }}</span>
        <span style="font-size:11px;color:#94a3b8;">{{ $maintenance->srTarget?->title }}</span>
    </div>
    <h2 style="margin:0 0 6px;font-size:17px;font-weight:700;color:#1e1b2e;line-height:1.4;">{{ $maintenance->title }}</h2>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:11.5px;color:#9ca3af;">
        <span>{{ $maintenance->user->name }} · {{ $maintenance->created_at->format('Y.m.d H:i') }}</span>
        @if($maintenance->requested_date)
        <span>{{ __('maintenance.date_requested') }} <b style="color:#374151;font-weight:600;">{{ $maintenance->requested_date->format('Y.m.d') }}</b></span>
        @endif
        @if($maintenance->due_date)
        @php $dtDueOverdue = $maintenance->due_date->isPast() && !in_array($maintenance->status, ['completed','rejected']); @endphp
        <span>{{ __('maintenance.date_due') }} <b style="color:{{ $dtDueOverdue ? '#dc2626' : '#374151' }};font-weight:600;">{{ $maintenance->due_date->format('Y.m.d') }}</b>
            @if($dtDueOverdue)<span style="color:#dc2626;">{{ __('maintenance.date_overdue') }}</span>
            @elseif(!in_array($maintenance->status, ['completed','rejected']))<span style="color:#9ca3af;">D-{{ (int) now()->diffInDays($maintenance->due_date) }}</span>@endif
        </span>
        @endif
        @if($maintenance->scheduled_date)
        <span style="color:#7c3aed;">{{ __('maintenance.date_scheduled') }} <b style="font-weight:700;">{{ $maintenance->scheduled_date->format('Y.m.d') }}</b></span>
        @endif
    </div>
</div>

</div>{{-- /data-fixed-header --}}

{{-- 본문 --}}
<div style="padding:18px 22px;border-bottom:1px solid #ede9fe;min-height:70px;">
    <div class="sr-content-view">{!! $maintenance->content !!}</div>
</div>

{{-- 첨부파일 (컴팩트 뱃지) --}}
<div style="padding:9px 22px;border-bottom:1px solid #ede9fe;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
    <span style="font-size:12px;font-weight:700;color:#1e1b2e;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
        <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        {{ __('maintenance.attachments') }} {{ $maintenance->files->count() }}
    </span>
    <button onclick="dtOpenUpload({{ $maintenance->id }})"
            style="display:inline-flex;align-items:center;gap:3px;padding:3px 9px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;flex-shrink:0;">
        <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        {{ __('maintenance.btn_file_add') }}
    </button>
    @forelse($maintenance->files as $mf)
    @php
        $mfIsUrl    = $mf->isUrlType();
        $mfCanDel   = $mf->uploaded_by === auth()->id() || auth()->user()->isAdmin();
        $mfDownload = route('maintenances.files.download', [$maintenance->id, $mf->id]);
    @endphp
    <span id="dt-mf-{{ $mf->id }}" title="{{ $mf->original_name }}"
          style="display:inline-flex;align-items:center;gap:3px;padding:2px 3px 2px 8px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;font-size:11px;font-weight:600;color:#6d28d9;max-width:190px;">
        @if($mfIsUrl)
        <span onclick="openUrlViewer({{ $mf->id }}, {{ $maintenance->sr_target_id }}, {{ json_encode($mf->original_name) }}, {{ json_encode($mf->getEmbedUrl()) }}, {{ json_encode($mf->source_url) }})"
              style="display:inline-flex;align-items:center;gap:3px;min-width:0;cursor:pointer;">
            <span style="flex-shrink:0;">{{ $mf->icon }}</span><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $mf->original_name }}</span>
        </span>
        @elseif($mf->previewType())
        <span onclick="openPreview({{ $mf->id }}, {{ $maintenance->sr_target_id }}, '{{ route('maintenances.files.preview-data', [$maintenance->id, $mf->id]) }}', '{{ $mfDownload }}')"
              style="display:inline-flex;align-items:center;gap:3px;min-width:0;cursor:pointer;">
            <span style="flex-shrink:0;">{{ $mf->icon }}</span><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $mf->original_name }}</span>
        </span>
        @else
        <a href="{{ $mfDownload }}" style="display:inline-flex;align-items:center;gap:3px;min-width:0;text-decoration:none;color:inherit;">
            <span style="flex-shrink:0;">{{ $mf->icon }}</span><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $mf->original_name }}</span>
        </a>
        @endif
        @if($mfCanDel)
        <button onclick="dtDeleteFile({{ $mf->id }}, {{ $maintenance->id }})" title="{{ __('common.delete') }}"
                style="background:none;border:none;cursor:pointer;color:#a78bfa;font-size:13px;line-height:1;padding:0 3px;flex-shrink:0;">×</button>
        @endif
    </span>
    @empty
    <span style="font-size:11px;color:#9ca3af;">{{ __('maintenance.attachment_empty') }}</span>
    @endforelse
</div>

{{-- 답글 목록 --}}
<div style="padding:14px 22px 0;border-bottom:1px solid #ede9fe;">
    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:10px;">
        {{ __('maintenance.replies') }}
        <span style="font-size:12px;font-weight:400;color:#9ca3af;margin-left:4px;">{{ $maintenance->replies->count() }}개</span>
    </div>
    @forelse($maintenance->replies as $reply)
    @php $isAdminR = $reply->isAdminReply(); @endphp
    <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #f9f5ff;' : '' }}">
        <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;
                        background:{{ $isAdminR ? 'linear-gradient(135deg,#7c3aed,#6d28d9)' : 'linear-gradient(135deg,#0ea5e9,#0284c7)' }};">
                {{ mb_substr($reply->authorName(), 0, 1) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;color:#1e1b2e;">{{ $reply->authorName() }}</span>
                    @if($isAdminR)
                    <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:5px;background:#ede9fe;color:#7c3aed;">{{ __('maintenance.admin_label') }}</span>
                    @elseif($reply->user?->is_sr_agent)
                    <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:5px;background:#dcfce7;color:#16a34a;">{{ __('maintenance.sr_agent_badge') }}</span>
                    @endif
                    <span style="font-size:11px;color:#9ca3af;">{{ $reply->created_at->format('Y.m.d H:i') }}</span>
                </div>
                <div class="sr-reply-content">{!! $reply->content !!}</div>
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center;color:#9ca3af;font-size:13px;padding:14px 0;">{{ __('maintenance.reply_empty') }}</div>
    @endforelse
    <div style="height:14px;"></div>
</div>

{{-- 답글 작성 --}}
@if($canReply)
<div style="padding:14px 22px;">
    <form data-reply-form
          data-url="{{ route('maintenances.replies.store', $maintenance) }}"
          data-reload-url="{{ route('maintenances.detail', $maintenance) }}">
        @csrf
        <div style="display:flex;justify-content:flex-end;margin-bottom:5px;">
            <button type="button" onclick="dtRefineReply(this)"
                    style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                {{ __('maintenance.weeks_refine') }}
            </button>
        </div>
        <div class="sr-reply-editor-wrap">
            <div class="sr-reply-quill-target" style="min-height:80px;"></div>
        </div>
        <input type="hidden" name="content" class="sr-reply-hidden">
        <div style="margin-top:8px;text-align:right;">
            <button type="submit"
                    style="padding:8px 22px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                {{ __('maintenance.btn_reply_submit') }}
            </button>
        </div>
    </form>
</div>
@else
<div style="padding:14px 22px;text-align:center;font-size:12px;color:#9ca3af;">
    {{ $maintenance->status === 'completed' ? __('maintenance.status_completed_msg') : __('maintenance.status_rejected_msg') }}
</div>
@endif
