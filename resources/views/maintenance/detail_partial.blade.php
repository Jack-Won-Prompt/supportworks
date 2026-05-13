@php
    $canEdit   = ($maintenance->user_id === auth()->id() || auth()->user()->isAdmin())
                  && in_array($maintenance->status, ['pending', 'in_progress']);
    $canDelete = $maintenance->user_id === auth()->id() || auth()->user()->isAdmin();
    $canReply  = (auth()->user()->isAdmin() || $maintenance->user_id === auth()->id())
                  && !in_array($maintenance->status, ['completed', 'rejected']);
@endphp

{{-- 고정 영역: 헤더 + 날짜 (JS가 dt-fixed-header로 이동) --}}
<div data-fixed-header>

{{-- 헤더 --}}
<div style="padding:18px 22px 14px;border-bottom:1px solid #ede9fe;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
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
    <h2 style="margin:0 0 6px;font-size:17px;font-weight:700;color:#1e1b2e;line-height:1.4;">{{ $maintenance->title }}</h2>
    <div style="font-size:12px;color:#9ca3af;">
        {{ $maintenance->user->name }} · {{ $maintenance->created_at->format('Y.m.d H:i') }}
    </div>
</div>

{{-- 날짜 --}}
@if($maintenance->requested_date || $maintenance->due_date || $maintenance->scheduled_date)
<div style="padding:10px 22px;border-bottom:1px solid #ede9fe;display:flex;gap:20px;flex-wrap:wrap;background:#fafafa;">
    @if($maintenance->requested_date)
    <div>
        <div style="font-size:10px;color:#9ca3af;font-weight:600;margin-bottom:2px;">{{ __('maintenance.date_requested') }}</div>
        <div style="font-size:12px;font-weight:600;color:#374151;">{{ $maintenance->requested_date->format('Y.m.d') }}</div>
    </div>
    @endif
    @if($maintenance->due_date)
    <div>
        <div style="font-size:10px;color:#9ca3af;font-weight:600;margin-bottom:2px;">{{ __('maintenance.date_due') }}</div>
        <div style="font-size:12px;font-weight:600;
            color:{{ $maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']) ? '#dc2626' : '#374151' }};">
            {{ $maintenance->due_date->format('Y.m.d') }}
            @if($maintenance->due_date->isPast() && !in_array($maintenance->status,['completed','rejected']))
            <span style="font-size:10px;margin-left:3px;color:#dc2626;">{{ __('maintenance.date_overdue') }}</span>
            @elseif(!in_array($maintenance->status,['completed','rejected']))
            <span style="font-size:10px;margin-left:3px;color:#9ca3af;">D-{{ (int) now()->diffInDays($maintenance->due_date) }}</span>
            @endif
        </div>
    </div>
    @endif
    @if($maintenance->scheduled_date)
    <div>
        <div style="font-size:10px;color:#7c3aed;font-weight:600;margin-bottom:2px;">{{ __('maintenance.date_scheduled') }}</div>
        <div style="font-size:12px;font-weight:600;color:#7c3aed;">{{ $maintenance->scheduled_date->format('Y.m.d') }}</div>
    </div>
    @endif
</div>
@endif

{{-- 액션 버튼 --}}
@if($canEdit || $canDelete)
<div style="padding:8px 22px;border-bottom:1px solid #ede9fe;display:flex;align-items:center;justify-content:flex-end;gap:8px;">
    @if($canEdit)
    <button data-edit-btn
            data-id="{{ $maintenance->id }}"
            data-update-url="{{ route('maintenances.update', $maintenance) }}"
            data-reload-url="{{ route('maintenances.detail', $maintenance) }}"
            data-title="{{ $maintenance->title }}"
            data-priority="{{ $maintenance->priority }}"
            data-requested-date="{{ $maintenance->requested_date?->format('Y-m-d') }}"
            data-due-date="{{ $maintenance->due_date?->format('Y-m-d') }}"
            data-content="{{ $maintenance->content }}"
            style="padding:5px 14px;border:1px solid #ddd6fe;border-radius:7px;font-size:12px;color:#7c3aed;background:transparent;cursor:pointer;font-weight:600;transition:background .15s;"
            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='transparent'">
        {{ __('common.edit') }}
    </button>
    @endif
    @if($canDelete)
    <button data-delete-btn
            data-id="{{ $maintenance->id }}"
            data-url="{{ route('maintenances.destroy', $maintenance) }}"
            style="padding:5px 14px;border:1px solid #fecaca;border-radius:7px;font-size:12px;color:#ef4444;background:transparent;cursor:pointer;transition:background .15s;"
            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
        {{ __('common.delete') }}
    </button>
    @endif
</div>
@endif

</div>{{-- /data-fixed-header --}}

{{-- 본문 --}}
<div style="padding:18px 22px;border-bottom:1px solid #ede9fe;min-height:70px;">
    <div class="sr-content-view">{!! $maintenance->content !!}</div>
</div>

{{-- 첨부파일 --}}
<div style="padding:12px 22px;border-bottom:1px solid #ede9fe;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.attachments') }}</span>
            <span style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700;">{{ $maintenance->files->count() }}</span>
        </div>
        <button onclick="dtOpenUpload({{ $maintenance->id }})"
                style="display:inline-flex;align-items:center;gap:4px;padding:4px 11px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('maintenance.btn_file_add') }}
        </button>
    </div>
    @forelse($maintenance->files as $mf)
    @php
        $mfIsUrl     = $mf->isUrlType();
        $mfCanPreview= $mf->previewType() || $mfIsUrl;
        $mfCanDel    = $mf->uploaded_by === auth()->id() || auth()->user()->isAdmin();
    @endphp
    <div id="dt-mf-{{ $mf->id }}" style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f9f5ff;">
        <span style="font-size:16px;flex-shrink:0;width:22px;text-align:center;">{{ $mf->icon }}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mf->original_name }}</div>
            <div style="font-size:10px;color:#9ca3af;margin-top:1px;display:flex;gap:6px;">
                <span>{{ $mf->uploader?->name ?? '-' }}</span>
                <span>{{ $mf->created_at->format('Y.m.d') }}</span>
                @if($mf->comments_count > 0)<span style="color:#7c3aed;">의견 {{ $mf->comments_count }}</span>@endif
            </div>
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0;">
            @if($mfCanPreview)
            @if($mfIsUrl)
            <button onclick="openUrlViewer({{ $mf->id }}, {{ $maintenance->project_id }}, {{ json_encode($mf->original_name) }}, {{ json_encode($mf->getEmbedUrl()) }}, {{ json_encode($mf->source_url) }})"
                    style="padding:3px 8px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">{{ __('maintenance.btn_url_open') }}</button>
            @else
            <button onclick="openPreview({{ $mf->id }}, {{ $maintenance->project_id }}, '{{ route('maintenances.files.preview-data', [$maintenance->id, $mf->id]) }}', '{{ route('maintenances.files.download', [$maintenance->id, $mf->id]) }}')"
                    style="padding:3px 8px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">{{ __('maintenance.btn_preview') }}</button>
            @endif
            @endif
            @if(!$mfIsUrl)
            <a href="{{ route('maintenances.files.download', [$maintenance->id, $mf->id]) }}"
               style="padding:3px 8px;background:#f9fafb;color:#374151;border:1px solid #e5e7eb;border-radius:5px;font-size:10px;font-weight:600;text-decoration:none;">{{ __('maintenance.btn_download') }}</a>
            @endif
            @if($mf->isShareable())
            <button onclick="dtToggleShare({{ $mf->id }}, {{ $maintenance->id }}, this)"
                    data-active="{{ $mf->share_token ? '1' : '0' }}"
                    data-share-url="{{ $mf->share_token ? route('maintenance-files.public-share', $mf->share_token) : '' }}"
                    style="padding:3px 8px;background:{{ $mf->share_token ? '#dcfce7' : '#f9fafb' }};color:{{ $mf->share_token ? '#16a34a' : '#6b7280' }};border:1px solid {{ $mf->share_token ? '#bbf7d0' : '#e5e7eb' }};border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">
                {{ $mf->share_token ? __('maintenance.btn_sharing') : __('maintenance.btn_share') }}
            </button>
            @endif
            @if($mfCanDel)
            <button onclick="dtDeleteFile({{ $mf->id }}, {{ $maintenance->id }})"
                    style="padding:3px 8px;background:#fff;color:#ef4444;border:1px solid #fecaca;border-radius:5px;font-size:10px;cursor:pointer;">{{ __('common.delete') }}</button>
            @endif
        </div>
    </div>
    @empty
    <div style="text-align:center;color:#9ca3af;font-size:12px;padding:12px 0;">{{ __('maintenance.attachment_empty') }}</div>
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
