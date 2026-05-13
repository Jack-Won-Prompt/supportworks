@extends('layouts.app')

@section('title', $maintenance->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('common.list') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $maintenance->project) }}" class="hover:text-indigo-500 transition-colors">{{ $maintenance->project->name }}</a>
<span>›</span>
<a href="{{ route('projects.maintenances.index', $maintenance->project) }}" class="hover:text-indigo-500 transition-colors">{{ __('maintenance.sr_receipt') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('common.detail') }}</span>
@endsection

@section('content')
<div style="max-width:800px;margin:0 auto;display:flex;flex-direction:column;gap:16px;">

    {{-- 플래시 메시지 --}}
    @if(session('success'))
    <div style="padding:12px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;font-weight:500;">
        {{ session('success') }}
    </div>
    @endif

    {{-- 요청 본문 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">

        {{-- 헤더 --}}
        <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
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
                    </div>
                    <h2 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#1e1b2e;line-height:1.4;">{{ $maintenance->title }}</h2>
                    <div style="font-size:12px;color:#9ca3af;">
                        {{ $maintenance->user->name }} · {{ $maintenance->created_at->format('Y.m.d H:i') }}
                        ({{ $maintenance->created_at->diffForHumans() }})
                    </div>
                </div>

                {{-- 상태 변경 (관리자 전용) --}}
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('maintenances.status', $maintenance) }}" style="flex-shrink:0;">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            style="padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;color:#374151;outline:none;cursor:pointer;background:#fff;">
                        @foreach(['pending' => __('maintenance.status_pending'), 'in_progress' => __('maintenance.status_in_progress'), 'completed' => __('maintenance.status_completed'), 'rejected' => __('maintenance.status_rejected')] as $val => $label)
                        <option value="{{ $val }}" {{ $maintenance->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>
        </div>

        {{-- 날짜 정보 --}}
        <div style="padding:16px 24px 0;display:flex;gap:24px;flex-wrap:wrap;">
            @if($maintenance->requested_date)
            <div>
                <span style="font-size:11px;color:#9ca3af;font-weight:600;">{{ __('maintenance.date_requested') }}</span>
                <p style="margin:2px 0 0;font-size:13px;font-weight:600;color:#374151;">{{ $maintenance->requested_date->format('Y.m.d') }}</p>
            </div>
            @endif
            @if($maintenance->due_date)
            <div>
                <span style="font-size:11px;color:#9ca3af;font-weight:600;">{{ __('maintenance.date_due') }}</span>
                <p style="margin:2px 0 0;font-size:13px;font-weight:600;
                   color:{{ $maintenance->due_date->isPast() && !in_array($maintenance->status, ['completed','rejected']) ? '#dc2626' : '#374151' }};">
                    {{ $maintenance->due_date->format('Y.m.d') }}
                    @if($maintenance->due_date->isPast() && !in_array($maintenance->status, ['completed','rejected']))
                    <span style="font-size:10px;color:#dc2626;margin-left:4px;">{{ __('maintenance.date_overdue') }}</span>
                    @elseif(!in_array($maintenance->status, ['completed','rejected']))
                    <span style="font-size:10px;color:#9ca3af;margin-left:4px;">D-{{ (int) now()->diffInDays($maintenance->due_date) }}</span>
                    @endif
                </p>
            </div>
            @endif
            @if($maintenance->scheduled_date)
            <div>
                <span style="font-size:11px;color:#9ca3af;font-weight:600;">{{ __('maintenance.date_scheduled_admin') }}</span>
                <p style="margin:2px 0 0;font-size:13px;font-weight:600;color:#7c3aed;">{{ $maintenance->scheduled_date->format('Y.m.d') }}</p>
            </div>
            @endif
        </div>

        {{-- 내용 --}}
        <div style="padding:24px;">
            <div class="sr-content-view">{!! $maintenance->content !!}</div>
        </div>

        {{-- 수정/삭제 버튼 --}}
        @if($maintenance->user_id === auth()->id() || auth()->user()->isAdmin())
        <div style="padding:0 24px 20px;display:flex;justify-content:flex-end;gap:8px;">
            @if($maintenance->status === 'pending' || $maintenance->status === 'in_progress' || auth()->user()->isAdmin())
            <button onclick="openEditModal()"
                    style="padding:7px 16px;background:transparent;border:1px solid #ddd6fe;border-radius:7px;font-size:12px;color:#7c3aed;cursor:pointer;transition:background .15s;font-weight:600;"
                    onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='transparent'">
                {{ __('common.edit') }}
            </button>
            @endif
            <form method="POST" action="{{ route('maintenances.destroy', $maintenance) }}"
                  onsubmit="return confirm('{{ __('maintenance.confirm_delete_sr') }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="padding:7px 16px;background:transparent;border:1px solid #fecaca;border-radius:7px;font-size:12px;color:#ef4444;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                    {{ __('common.delete') }}
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- ── 첨부파일 ── --}}
    <div id="mf-section" style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <h3 style="margin:0;font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.attachments') }}</h3>
                <span id="mf-count" style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700;">{{ $maintenance->files->count() }}</span>
            </div>
            <button onclick="openMfUpload()"
                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 13px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('maintenance.btn_file_add') }}
            </button>
        </div>

        {{-- 파일 목록 --}}
        <div id="mf-list">
            @forelse($maintenance->files as $mf)
            @php
                $mfCanDel = $mf->uploaded_by === auth()->id() || auth()->user()->isAdmin();
                $mfPt     = $mf->previewType();
                $mfIsUrl  = $mf->isUrlType();
                $mfCanPreview = $mfPt || $mfIsUrl;
            @endphp
            <div id="mf-row-{{ $mf->id }}" style="display:flex;align-items:center;gap:10px;padding:10px 20px;border-bottom:1px solid #faf5ff;transition:background .12s;" onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
                <span style="font-size:18px;flex-shrink:0;width:28px;text-align:center;">{{ $mf->icon }}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $mf->original_name }}</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:1px;display:flex;gap:8px;flex-wrap:wrap;">
                        <span>{{ $mf->uploader?->name ?? __('common.none') }}</span>
                        <span>{{ $mf->created_at->format('Y.m.d') }}</span>
                        @if(!$mfIsUrl)<span>{{ $mf->formatted_size }}</span>@endif
                        @if($mf->comments_count > 0)
                        <span style="color:#7c3aed;">{{ __('common.comment') }} {{ $mf->comments_count }}</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
                    @if($mfCanPreview)
                    @if($mfIsUrl)
                    <button onclick="openUrlViewer({{ $mf->id }}, {{ $maintenance->project_id }}, {{ json_encode($mf->original_name) }}, {{ json_encode($mf->getEmbedUrl()) }}, {{ json_encode($mf->source_url) }})"
                            style="padding:4px 10px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:background .12s;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">{{ __('maintenance.btn_url_open') }}</button>
                    @else
                    <button onclick="openPreview({{ $mf->id }}, {{ $maintenance->project_id }}, '{{ route('maintenances.files.preview-data', [$maintenance->id, $mf->id]) }}', '{{ route('maintenances.files.download', [$maintenance->id, $mf->id]) }}')"
                            style="padding:4px 10px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:background .12s;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">{{ __('maintenance.btn_preview') }}</button>
                    @endif
                    @endif
                    @if(!$mfIsUrl)
                    <a href="{{ route('maintenances.files.download', [$maintenance->id, $mf->id]) }}"
                       style="padding:4px 10px;background:#f9fafb;color:#374151;border:1px solid #e5e7eb;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;transition:background .12s;"
                       onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">
                        {{ __('maintenance.btn_download') }}
                    </a>
                    @endif
                    @if($mf->isShareable())
                    <button onclick="mfToggleShare({{ $mf->id }}, this)"
                            data-active="{{ $mf->share_token ? '1' : '0' }}"
                            data-token="{{ $mf->share_token ?? '' }}"
                            data-share-url="{{ $mf->share_token ? route('maintenance-files.public-share', $mf->share_token) : '' }}"
                            style="padding:4px 10px;background:{{ $mf->share_token ? '#dcfce7' : '#f9fafb' }};color:{{ $mf->share_token ? '#16a34a' : '#6b7280' }};border:1px solid {{ $mf->share_token ? '#bbf7d0' : '#e5e7eb' }};border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:background .12s;">
                        {{ $mf->share_token ? __('maintenance.btn_sharing') : __('maintenance.btn_share') }}
                    </button>
                    @endif
                    @if($mfCanDel)
                    <button onclick="mfDelete({{ $mf->id }}, {{ $maintenance->id }})"
                            style="padding:4px 10px;background:#fff;color:#ef4444;border:1px solid #fecaca;border-radius:6px;font-size:11px;cursor:pointer;transition:background .12s;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                        {{ __('common.delete') }}
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div id="mf-empty" style="padding:24px;text-align:center;font-size:13px;color:#9ca3af;">{{ __('maintenance.attachment_empty') }}</div>
            @endforelse
        </div>
    </div>

    {{-- 답글 목록 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
            <h3 style="margin:0;font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.replies') }}</h3>
            <span style="font-size:12px;color:#9ca3af;">{{ __('maintenance.reply_count', ['count' => $maintenance->replies->count()]) }}</span>
        </div>

        @forelse($maintenance->replies as $reply)
        @php
            $isAdminReply = $reply->isAdminReply();
            $isMe         = !$isAdminReply && $reply->user_id === auth()->id();
        @endphp
        <div style="padding:16px 24px;border-bottom:1px solid #f9f5ff;{{ $isAdminReply ? 'background:#faf8ff;' : '' }}">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;
                            background:{{ $isAdminReply ? 'linear-gradient(135deg,#7c3aed,#6d28d9)' : 'linear-gradient(135deg,#0ea5e9,#0284c7)' }};">
                    {{ mb_substr($reply->authorName(), 0, 1) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:wrap;">
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ $reply->authorName() }}</span>
                        @if($isAdminReply)
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;background:#ede9fe;color:#7c3aed;">{{ __('maintenance.admin_label') }}</span>
                        @endif
                        <span style="font-size:11px;color:#9ca3af;">{{ $reply->created_at->format('Y.m.d H:i') }}</span>
                    </div>
                    <div class="sr-reply-content">{!! $reply->content !!}</div>
                </div>
                @if($isMe || auth()->user()->isAdmin())
                <form method="POST" action="{{ route('maintenance-replies.destroy', $reply) }}"
                      onsubmit="return confirm('{{ __('maintenance.confirm_delete_reply') }}')" style="flex-shrink:0;">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="padding:4px 8px;background:transparent;border:1px solid #fecaca;border-radius:6px;font-size:11px;color:#ef4444;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                        {{ __('common.delete') }}
                    </button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div style="padding:32px 24px;text-align:center;color:#9ca3af;font-size:13px;">{{ __('maintenance.reply_empty') }}</div>
        @endforelse
    </div>

    {{-- 관리자 일정 조정 --}}
    @if(auth()->user()->isAdmin())
    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:14px 24px;border-bottom:1px solid #f3f4f6;background:#faf5ff;">
            <h3 style="margin:0;font-size:14px;font-weight:700;color:#7c3aed;">{{ __('maintenance.admin_schedule') }}</h3>
        </div>
        <form method="POST" action="{{ route('maintenances.update', $maintenance) }}" style="padding:16px 24px;display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            @csrf @method('PATCH')
            <input type="hidden" name="title"    value="{{ $maintenance->title }}">
            <input type="hidden" name="content"  value="{{ $maintenance->content }}">
            <input type="hidden" name="priority" value="{{ $maintenance->priority }}">
            <input type="hidden" name="requested_date" value="{{ $maintenance->requested_date?->format('Y-m-d') }}">
            <input type="hidden" name="due_date"       value="{{ $maintenance->due_date?->format('Y-m-d') }}">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_scheduled') }}</label>
                <input type="date" name="scheduled_date"
                       value="{{ $maintenance->scheduled_date?->format('Y-m-d') }}"
                       style="padding:8px 12px;border:1.5px solid #ddd6fe;border-radius:8px;font-size:13px;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#ddd6fe'">
            </div>
            <button type="submit"
                    style="padding:9px 20px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                {{ __('maintenance.date_save') }}
            </button>
            @if($maintenance->scheduled_date)
            <button type="submit" name="scheduled_date" value=""
                    style="padding:9px 16px;background:transparent;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#9ca3af;cursor:pointer;transition:background .15s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                {{ __('common.reset') }}
            </button>
            @endif
        </form>
    </div>
    @endif

    {{-- 답글 작성 --}}
    @if(auth()->user()->isAdmin() || $maintenance->user_id === auth()->id())
    @if($maintenance->status !== 'completed' && $maintenance->status !== 'rejected')
    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid #f3f4f6;">
            <h3 style="margin:0;font-size:14px;font-weight:700;color:#1e1b2e;">
                {{ auth()->user()->isAdmin() ? __('maintenance.reply_write_admin') : __('maintenance.reply_write_user') }}
            </h3>
        </div>
        <form method="POST" action="{{ route('maintenances.replies.store', $maintenance) }}" id="show-reply-form" style="padding:20px 24px;">
            @csrf
            <div class="sr-reply-editor-wrap">
                <div id="show-reply-editor" style="min-height:100px;"></div>
            </div>
            <input type="hidden" name="content" id="show-reply-content">
            @error('content')
                <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
            @enderror
            <div style="margin-top:12px;text-align:right;">
                <button type="submit" id="show-reply-btn"
                        style="padding:10px 24px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    {{ __('maintenance.btn_reply_submit') }}
                </button>
            </div>
        </form>
    </div>
    @else
    <div style="padding:14px 20px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;text-align:center;font-size:13px;color:#9ca3af;">
        {{ $maintenance->status === 'completed' ? __('maintenance.status_completed_msg') : __('maintenance.status_rejected_msg') }}
    </div>
    @endif
    @endif

</div>

{{-- ───── 파일 업로드 모달 ───── --}}
<div id="mf-upload-overlay" onclick="closeMfUpload()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="mf-upload-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:500px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid #f0f0f0;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_attachment_add') }}</h3>
        <button onclick="closeMfUpload()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>
    {{-- 탭 --}}
    <div style="display:flex;border-bottom:1px solid #f0f0f0;">
        <button id="mf-tab-file" onclick="switchMfTab('file')"
                style="flex:1;padding:10px 0;background:none;border:none;border-bottom:2px solid #7c3aed;font-size:13px;font-weight:600;color:#7c3aed;cursor:pointer;">
            {{ __('maintenance.tab_file_upload') }}
        </button>
        <button id="mf-tab-url" onclick="switchMfTab('url')"
                style="flex:1;padding:10px 0;background:none;border:none;border-bottom:2px solid transparent;font-size:13px;font-weight:600;color:#9ca3af;cursor:pointer;">
            {{ __('maintenance.tab_url_register') }}
        </button>
    </div>
    {{-- 파일 탭 --}}
    <div id="mf-panel-file" style="padding:20px;">
        <div id="mf-drop-zone" onclick="document.getElementById('mf-file-input').click()"
             style="border:2px dashed #ddd6fe;border-radius:10px;padding:28px;text-align:center;cursor:pointer;transition:border-color .15s;background:#faf5ff;"
             ondragover="event.preventDefault();this.style.borderColor='#7c3aed'" ondragleave="this.style.borderColor='#ddd6fe'"
             ondrop="event.preventDefault();this.style.borderColor='#ddd6fe';mfHandleDrop(event)">
            <svg width="28" height="28" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p style="font-size:13px;font-weight:600;color:#7c3aed;margin:0 0 4px;">{{ __('maintenance.upload_click_drag_short') }}</p>
            <p style="font-size:11px;color:#9ca3af;margin:0;">{{ __('maintenance.upload_max_size') }}</p>
        </div>
        <input type="file" id="mf-file-input" style="display:none;" onchange="mfHandleFile(this.files[0])">
        <div id="mf-file-preview" style="display:none;margin-top:12px;padding:10px 12px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;display:flex;align-items:center;gap:10px;">
            <span id="mf-file-name" style="flex:1;font-size:13px;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            <button onclick="mfClearFile()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:16px;padding:0;flex-shrink:0;">×</button>
        </div>
        <div style="margin-top:10px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ __('maintenance.field_description') }}</label>
            <input type="text" id="mf-file-desc" maxlength="255" placeholder="{{ __('maintenance.field_description') }}"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div id="mf-file-err" style="display:none;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:12px;color:#dc2626;margin-top:8px;"></div>
        <div style="display:flex;gap:8px;margin-top:14px;">
            <button onclick="mfSubmitFile()" id="mf-file-btn"
                    style="flex:1;padding:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">
                {{ __('maintenance.btn_upload') }}
            </button>
            <button onclick="closeMfUpload()"
                    style="padding:10px 18px;background:#fff;border:1.5px solid #e5e7eb;color:#52525b;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit;">
                {{ __('common.cancel') }}
            </button>
        </div>
    </div>
    {{-- URL 탭 --}}
    <div id="mf-panel-url" style="display:none;padding:20px;">
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">URL <span style="color:#ef4444;">*</span></label>
            <input type="url" id="mf-url-src" placeholder="https://..." maxlength="2048"
                   style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div style="margin-top:10px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ __('maintenance.field_display_name') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="mf-url-name" maxlength="255" placeholder="{{ __('maintenance.field_display_name') }}"
                   style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div id="mf-url-err" style="display:none;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:12px;color:#dc2626;margin-top:8px;"></div>
        <div style="display:flex;gap:8px;margin-top:14px;">
            <button onclick="mfSubmitUrl()" id="mf-url-btn"
                    style="flex:1;padding:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">
                {{ __('maintenance.btn_url_register') }}
            </button>
            <button onclick="closeMfUpload()"
                    style="padding:10px 18px;background:#fff;border:1.5px solid #e5e7eb;color:#52525b;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit;">
                {{ __('common.cancel') }}
            </button>
        </div>
    </div>
</div>


{{-- ───── 파일 미리보기 모달 ───── --}}
@include('partials.file-preview-modal')

{{-- ───── 수정 모달 ───── --}}
@if($maintenance->user_id === auth()->id() || auth()->user()->isAdmin())
<div id="edit-overlay" onclick="closeEditModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="edit-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:560px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">

    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $maintenance->project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_edit') }}</h3>
        </div>
        <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="edit-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
        @csrf
        <input type="hidden" name="_method" value="PATCH">

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.field_title') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="edit-title" name="title" required value="{{ $maintenance->title }}"
                   style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;">{{ __('maintenance.field_priority') }} <span style="color:#ef4444;">*</span></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach(['low' => ['priority_low','#6b7280'], 'normal' => ['priority_normal','#2563eb'], 'high' => ['priority_high','#d97706'], 'urgent' => ['priority_urgent','#dc2626']] as $val => [$lkey, $clr])
                <label style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;transition:all .15s;user-select:none;" id="edit-chip-{{ $val }}">
                    <input type="radio" name="priority" value="{{ $val }}" {{ $maintenance->priority === $val ? 'checked' : '' }}
                           style="display:none;" onchange="updateEditChip()">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></span>
                    {{ __('maintenance.' . $lkey) }}
                </label>
                @endforeach
            </div>
        </div>

        {{-- 날짜 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_requested') }}</label>
                <input type="date" name="requested_date"
                       value="{{ $maintenance->requested_date?->format('Y-m-d') }}"
                       style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_due') }}</label>
                <input type="date" name="due_date"
                       value="{{ $maintenance->due_date?->format('Y-m-d') }}"
                       style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.field_content') }} <span style="color:#ef4444;">*</span></label>
            <div class="sr-editor-wrap">
                <div id="edit-content-editor" style="min-height:130px;"></div>
            </div>
            <input type="hidden" id="edit-content" name="content">
        </div>

        <div id="edit-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:2px;">
            <button type="submit" id="edit-submit"
                    style="flex:1;padding:10px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none;border-radius:9px;cursor:pointer;font-family:inherit;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('common.save') }}</button>
            <button type="button" onclick="closeEditModal()"
                    style="padding:10px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
        </div>
    </form>
</div>
@endif

@push('styles')
@include('maintenance._quill_assets')
@endpush

@section('scripts')
<script>
/* CSRF_TOKEN / BASE_URL 은 file-preview-modal 파티셜이 이미 선언 */

/* ── 답글 에디터 ── */
@if(auth()->user()->isAdmin() || $maintenance->user_id === auth()->id())
@if(!in_array($maintenance->status, ['completed','rejected']))
(async function() {
    const replyQ = createSrEditor('show-reply-editor', 'show-reply-content',
        '{{ auth()->user()->isAdmin() ? __('maintenance.reply_placeholder_admin') : __('maintenance.reply_placeholder_user') }}',
        true, CSRF_TOKEN);

    document.getElementById('show-reply-form').addEventListener('submit', async function(e) {
        if (replyQ.getText().trim() === '') {
            e.preventDefault();
            return;
        }
        document.getElementById('show-reply-content').value = replyQ.root.innerHTML;
    });
})();
@endif
@endif

/* ── 수정 모달 에디터 ── */
@if($maintenance->user_id === auth()->id() || auth()->user()->isAdmin())
const UPDATE_URL  = '{{ route('maintenances.update', $maintenance) }}';
const EDIT_COLORS = { low:'#6b7280', normal:'#2563eb', high:'#d97706', urgent:'#dc2626' };
const EDIT_BGS    = { low:'#f3f4f6', normal:'#dbeafe', high:'#fef3c7', urgent:'#fee2e2' };
let _editQuill = null;

async function updateEditChip() {
    document.querySelectorAll('input[name=priority]').forEach(async function(r) {
        const chip = document.getElementById('edit-chip-' + r.value);
        if (!chip) return;
        if (r.checked) {
            chip.style.borderColor = EDIT_COLORS[r.value];
            chip.style.background  = EDIT_BGS[r.value];
            chip.style.color       = EDIT_COLORS[r.value];
        } else {
            chip.style.borderColor = '#e4e4e7';
            chip.style.background  = 'transparent';
            chip.style.color       = '#374151';
        }
    });
}

async function openEditModal() {
    document.getElementById('edit-error').style.display = 'none';
    updateEditChip();
    if (!_editQuill) {
        _editQuill = createSrEditor('edit-content-editor', 'edit-content', '{{ __('common.content') }}...', false, CSRF_TOKEN);
        _editQuill.root.innerHTML = {!! json_encode($maintenance->content) !!};
        document.getElementById('edit-content').value = _editQuill.root.innerHTML;
    }
    document.getElementById('edit-modal').style.display   = 'block';
    document.getElementById('edit-overlay').style.display = 'block';
    setTimeout(() => document.getElementById('edit-title').focus(), 50);
}

async function closeEditModal() {
    document.getElementById('edit-modal').style.display   = 'none';
    document.getElementById('edit-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
document.addEventListener('DOMContentLoaded', updateEditChip);

document.getElementById('edit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('edit-submit');
    const errEl = document.getElementById('edit-error');

    if (_editQuill) {
        document.getElementById('edit-content').value = _editQuill.root.innerHTML;
        if (_editQuill.getText().trim() === '') {
            errEl.textContent = '{{ __('common.content') }}...';
            errEl.style.display = 'block';
            return;
        }
    }

    btn.disabled = true; btn.textContent = '{{ __('common.saving') }}';
    errEl.style.display = 'none';

    try {
        const res = await fetch(UPDATE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: new FormData(this),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
            closeEditModal();
            location.reload();
        } else {
            errEl.textContent = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || '{{ __('common.error') }}');
            errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = '{{ __('common.error') }}';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '{{ __('common.save') }}';
    }
});
@endif

/* ── SR 첨부파일 ── */
const MF_STORE_URL  = '{{ route('maintenances.files.store',  $maintenance) }}';
const MF_DESTROY_BASE = '{{ url('maintenances/'.$maintenance->id.'/files') }}/';
const MF_SHARE_BASE   = '{{ url('maintenances/'.$maintenance->id.'/files') }}/';
const MF_PROJECT_ID   = {{ $maintenance->project_id }};

let _mfSelectedFile = null;

async function openMfUpload() {
    _mfSelectedFile = null;
    document.getElementById('mf-file-input').value = '';
    document.getElementById('mf-file-preview').style.display = 'none';
    document.getElementById('mf-file-desc').value = '';
    document.getElementById('mf-url-src').value = '';
    document.getElementById('mf-url-name').value = '';
    document.getElementById('mf-file-err').style.display = 'none';
    document.getElementById('mf-url-err').style.display = 'none';
    switchMfTab('file');
    document.getElementById('mf-upload-modal').style.display = 'block';
    document.getElementById('mf-upload-overlay').style.display = 'block';
}
async function closeMfUpload() {
    document.getElementById('mf-upload-modal').style.display = 'none';
    document.getElementById('mf-upload-overlay').style.display = 'none';
}
async function switchMfTab(tab) {
    const isFile = tab === 'file';
    document.getElementById('mf-panel-file').style.display = isFile ? 'block' : 'none';
    document.getElementById('mf-panel-url').style.display  = isFile ? 'none'  : 'block';
    document.getElementById('mf-tab-file').style.borderBottomColor = isFile ? '#7c3aed' : 'transparent';
    document.getElementById('mf-tab-file').style.color = isFile ? '#7c3aed' : '#9ca3af';
    document.getElementById('mf-tab-url').style.borderBottomColor = isFile ? 'transparent' : '#7c3aed';
    document.getElementById('mf-tab-url').style.color = isFile ? '#9ca3af' : '#7c3aed';
}
async function mfHandleDrop(e) {
    const file = e.dataTransfer.files[0];
    if (file) mfHandleFile(file);
}
async function mfHandleFile(file) {
    if (!file) return;
    _mfSelectedFile = file;
    document.getElementById('mf-file-name').textContent = file.name;
    document.getElementById('mf-file-preview').style.display = 'flex';
}
async function mfClearFile() {
    _mfSelectedFile = null;
    document.getElementById('mf-file-input').value = '';
    document.getElementById('mf-file-preview').style.display = 'none';
}
async function mfSubmitFile() {
    const errEl = document.getElementById('mf-file-err');
    errEl.style.display = 'none';
    if (!_mfSelectedFile) { errEl.textContent = '{{ __('common.select') }}...'; errEl.style.display = 'block'; return; }
    const btn = document.getElementById('mf-file-btn');
    btn.disabled = true; btn.textContent = '{{ __('common.loading') }}';
    const fd = new FormData();
    fd.append('file', _mfSelectedFile);
    fd.append('description', document.getElementById('mf-file-desc').value);
    try {
        const res = await fetch(MF_STORE_URL, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN}, body:fd });
        const d = await res.json().catch(()=>({}));
        if (res.ok && d.ok) { closeMfUpload(); location.reload(); }
        else { errEl.textContent = d.message || '{{ __('common.error') }}'; errEl.style.display = 'block'; }
    } catch { errEl.textContent = '{{ __('common.error') }}'; errEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = '{{ __('maintenance.btn_upload') }}'; }
}
async function mfSubmitUrl() {
    const errEl = document.getElementById('mf-url-err');
    errEl.style.display = 'none';
    const src  = document.getElementById('mf-url-src').value.trim();
    const name = document.getElementById('mf-url-name').value.trim();
    if (!src)  { errEl.textContent = 'URL...';  errEl.style.display = 'block'; return; }
    if (!name) { errEl.textContent = '{{ __('maintenance.field_display_name') }}...'; errEl.style.display = 'block'; return; }
    const btn = document.getElementById('mf-url-btn');
    btn.disabled = true; btn.textContent = '{{ __('common.loading') }}';
    try {
        const res = await fetch(MF_STORE_URL, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},
            body: JSON.stringify({ file_type:'url', source_url:src, original_name:name }) });
        const d = await res.json().catch(()=>({}));
        if (res.ok && d.ok) { closeMfUpload(); location.reload(); }
        else { errEl.textContent = d.message || '{{ __('common.error') }}'; errEl.style.display = 'block'; }
    } catch { errEl.textContent = '{{ __('common.error') }}'; errEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = '{{ __('maintenance.btn_url_register') }}'; }
}
async function mfDelete(fileId, maintenanceId) {
    if (!await __confirm('{{ __('maintenance.confirm_delete_file') }}')) return;
    const res = await fetch(MF_DESTROY_BASE + fileId, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d = await res.json().catch(()=>({}));
    if (res.ok && d.ok) {
        const row = document.getElementById('mf-row-' + fileId);
        if (row) row.remove();
        const list = document.getElementById('mf-list');
        const rows = list.querySelectorAll('[id^="mf-row-"]');
        if (!rows.length) {
            const empty = document.createElement('div');
            empty.id = 'mf-empty';
            empty.style.cssText = 'padding:24px;text-align:center;font-size:13px;color:#9ca3af;';
            empty.textContent = '{{ __('maintenance.attachment_empty') }}';
            list.appendChild(empty);
        }
        const cnt = document.getElementById('mf-count');
        if (cnt) cnt.textContent = rows.length;
    } else { alert(d.message || '{{ __('common.error') }}'); }
}
let _mfShareFileId = null, _mfShareBtn = null;

async function mfToggleShare(fileId, btn) {
    const active = btn.dataset.active === '1';
    if (active) {
        openMfSharePopup(btn.dataset.shareUrl, fileId, btn);
        return;
    }
    const res = await fetch(MF_SHARE_BASE + fileId + '/share', { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || '{{ __('common.error') }}'); return; }
    btn.dataset.active = '1'; btn.dataset.token = d.token; btn.dataset.shareUrl = d.url;
    btn.textContent = '{{ __('maintenance.btn_sharing') }}';
    btn.style.background = '#dcfce7'; btn.style.color = '#16a34a'; btn.style.borderColor = '#bbf7d0';
    openMfSharePopup(d.url, fileId, btn);
}
async function openMfSharePopup(url, fileId, btn) {
    _mfShareFileId = fileId; _mfShareBtn = btn;
    document.getElementById('mf-share-url-input').value = url;
    document.getElementById('mf-share-popup').style.display = 'block';
    document.getElementById('mf-share-overlay').style.display = 'block';
}
async function closeMfSharePopup() {
    document.getElementById('mf-share-popup').style.display = 'none';
    document.getElementById('mf-share-overlay').style.display = 'none';
}
async function mfDisableShareFromPopup() {
    if (!_mfShareFileId || !await __confirm('{{ __('maintenance.share_disable') }}?')) return;
    const res = await fetch(MF_SHARE_BASE + _mfShareFileId + '/share', { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || '{{ __('common.error') }}'); return; }
    if (_mfShareBtn) {
        _mfShareBtn.dataset.active = '0'; _mfShareBtn.dataset.token = '';
        _mfShareBtn.textContent = '{{ __('maintenance.btn_share') }}';
        _mfShareBtn.style.background = '#f9fafb'; _mfShareBtn.style.color = '#6b7280'; _mfShareBtn.style.borderColor = '#e5e7eb';
    }
    closeMfSharePopup();
}
async function mfCopyShareUrl() {
    const inp = document.getElementById('mf-share-url-input');
    inp.select();
    navigator.clipboard?.writeText(inp.value).catch(()=>document.execCommand('copy'));
    const btn = document.querySelector('#mf-share-popup button[onclick="mfCopyShareUrl()"]');
    if (btn) { const t=btn.textContent; btn.textContent='{{ __('common.copy') }}!'; setTimeout(()=>btn.textContent=t, 1500); }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeMfUpload(); closeMfSharePopup(); }
});
</script>
@endsection

@endsection

@section('modals')
<div id="mf-share-overlay" onclick="closeMfSharePopup()" style="display:none;position:fixed;inset:0;z-index:10199;background:rgba(0,0,0,.3);"></div>
<div id="mf-share-popup" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10200;background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.2);padding:20px 22px;width:420px;max-width:calc(100vw - 32px);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <span style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.share_title') }}</span>
        <button onclick="closeMfSharePopup()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:0;">&times;</button>
    </div>
    <div style="display:flex;gap:6px;">
        <input id="mf-share-url-input" type="text" readonly
               style="flex:1;padding:8px 10px;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;color:#374151;background:#faf5ff;outline:none;">
        <button onclick="mfCopyShareUrl()"
                style="padding:8px 14px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">{{ __('common.copy') }}</button>
    </div>
    <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">{{ __('maintenance.share_hint') }}</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6;">
        <button onclick="mfDisableShareFromPopup()"
                style="width:100%;padding:8px 0;background:#fff;color:#ef4444;border:1.5px solid #fecaca;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
            {{ __('maintenance.share_disable') }}
        </button>
    </div>
</div>
@endsection
