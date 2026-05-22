@extends('layouts.app')

@section('title', $meetingMinute->title)

@section('header-breadcrumb')
<span style="color:#d1d5db;margin:0 6px;">/</span>
<a href="{{ route('meeting-minutes.index') }}" style="font-size:13px;color:var(--color-text-tertiary);text-decoration:none;"
   onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#94a3b8'">{{ __('maintenance.meeting_minutes') }}</a>
@endsection

@section('header-actions')
<div style="display:flex;gap:8px;">
    <a href="{{ route('meeting-minutes.download', $meetingMinute) }}"
       style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;border:1.5px solid var(--t200);border-radius:8px;font-size:13px;font-weight:600;color:var(--t600);text-decoration:none;background:#faf5ff;">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        {{ __('maintenance.word_download') }}
    </a>
    @if(auth()->id() === $meetingMinute->author_id || auth()->user()->isAdmin())
    <a href="{{ route('meeting-minutes.index') }}?edit={{ $meetingMinute->id }}"
       style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;font-weight:600;color:#64748b;text-decoration:none;background:#fff;">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        {{ __('common.edit') }}
    </a>
    <form method="POST" action="{{ route('meeting-minutes.destroy', $meetingMinute) }}"
          onsubmit="return confirm('{{ __('maintenance.confirm_delete_meeting') }}')">
        @csrf @method('DELETE')
        <button type="submit"
                style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;border:1.5px solid #fecaca;border-radius:8px;font-size:13px;font-weight:600;color:var(--color-alert-warning-500);background:#fff;cursor:pointer;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            {{ __('common.delete') }}
        </button>
    </form>
    @endif
</div>
@endsection

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:24px;">

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:var(--color-alert-success-500);">
        {{ session('success') }}
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

        {{-- 메인 콘텐츠 --}}
        <div>
            {{-- 기본 정보 카드 --}}
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:6px;
                        {{ $meetingMinute->type==='project' ? 'background:var(--t100);color:var(--t600);' : 'background:#dcfce7;color:var(--color-alert-success-500);' }}">
                        {{ $meetingMinute->type_label }}
                    </span>
                    @if($meetingMinute->project)
                    <span style="font-size:12px;color:var(--t600);background:var(--t50);padding:3px 10px;border-radius:6px;">{{ $meetingMinute->project->name }}</span>
                    @endif
                </div>
                <h2 style="font-size:20px;font-weight:800;color:var(--color-text-primary);margin-bottom:16px;">{{ $meetingMinute->title }}</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;font-size:13px;">
                    <div style="display:flex;align-items:center;gap:8px;color:#64748b;">
                        <span>📅</span>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ __('maintenance.meeting_date') }}</div>
                            <div style="font-weight:600;color:var(--color-text-primary);">{{ $meetingMinute->meeting_date->format('Y.m.d (D) H:i') }}</div>
                        </div>
                    </div>
                    @if($meetingMinute->location)
                    <div style="display:flex;align-items:center;gap:8px;color:#64748b;">
                        <span>📍</span>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ __('maintenance.location') }}</div>
                            <div style="font-weight:600;color:var(--color-text-primary);">{{ $meetingMinute->location }}</div>
                        </div>
                    </div>
                    @endif
                    @if($meetingMinute->project_code)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span>🔖</span>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ __('maintenance.project_code') }}</div>
                            <div style="font-weight:600;color:var(--color-text-primary);">{{ $meetingMinute->project_code }}</div>
                        </div>
                    </div>
                    @endif
                    @if($meetingMinute->weekly_department)
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span>🏢</span>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ __('maintenance.weekly_dept') }}</div>
                            <div style="font-weight:600;color:var(--color-text-primary);">{{ $meetingMinute->weekly_department }}</div>
                        </div>
                    </div>
                    @endif
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span>✍️</span>
                        <div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ __('common.author') }}</div>
                            <div style="font-weight:600;color:var(--color-text-primary);">{{ $meetingMinute->author->name }}</div>
                        </div>
                    </div>
                </div>

                @if($meetingMinute->attendees->count())
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--color-border-default);">
                    <div style="font-size:11px;color:var(--color-text-tertiary);margin-bottom:8px;font-weight:600;">{{ __('maintenance.attendees') }}</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($meetingMinute->attendees as $att)
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:var(--t50);border:1px solid var(--t200);border-radius:20px;font-size:12px;font-weight:600;color:var(--t700);">
                            <span style="width:18px;height:18px;border-radius:50%;background:var(--t200);display:inline-flex;align-items:center;justify-content:center;font-size:10px;">{{ mb_substr($att->name,0,1) }}</span>
                            {{ $att->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- 안건 --}}
            @if($meetingMinute->agenda)
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <span style="width:4px;height:16px;background:var(--t500);border-radius:2px;display:inline-block;"></span>{{ __('maintenance.agenda') }}
                </div>
                <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.75;white-space:pre-wrap;">{{ $meetingMinute->agenda }}</div>
            </div>
            @endif

            {{-- 논의 내용 --}}
            @if($meetingMinute->discussion)
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <span style="width:4px;height:16px;background:#3b82f6;border-radius:2px;display:inline-block;"></span>{{ __('maintenance.discussion') }}
                </div>
                <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.75;white-space:pre-wrap;">{{ $meetingMinute->discussion }}</div>
            </div>
            @endif

            {{-- 결정 사항 --}}
            @if($meetingMinute->decisions)
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <span style="width:4px;height:16px;background:#10b981;border-radius:2px;display:inline-block;"></span>{{ __('maintenance.decisions') }}
                </div>
                <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.75;white-space:pre-wrap;">{{ $meetingMinute->decisions }}</div>
            </div>
            @endif

            {{-- 웍스 요약 --}}
            @if($meetingMinute->ai_summary)
            <div style="background:linear-gradient(135deg,var(--t50),var(--t100));border:1px solid var(--t300);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--t600);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    🤖 {{ __('maintenance.ai_summary') }}
                </div>
                <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.75;white-space:pre-wrap;">{{ $meetingMinute->ai_summary }}</div>
            </div>
            @endif

            {{-- 회의 녹음 (모바일 앱 업로드) --}}
            @if($meetingMinute->recordings->count())
            <div style="background:#fff;border:1px solid #e0e7ff;border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <span style="width:4px;height:16px;background:#4f46e5;border-radius:2px;display:inline-block;"></span>
                    {{ __('maintenance.recordings_section', ['count' => $meetingMinute->recordings->count()]) }}
                </div>

                @foreach($meetingMinute->recordings as $rec)
                <div style="border:1px solid var(--color-border-default);border-radius:12px;padding:16px;margin-bottom:{{ !$loop->last ? '12px' : '0' }};">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#4f46e5,#6366f1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;">🎙️</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);">{{ $rec->title ?? __('maintenance.recording_default') }}</div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">
                                {{ $rec->formatted_duration }} · {{ $rec->formatted_size }}
                                @if($rec->user) · {{ $rec->user->name }} @endif
                                @if($rec->recorded_at) · {{ \Carbon\Carbon::parse($rec->recorded_at)->format('Y-m-d H:i') }} @endif
                            </div>
                        </div>
                        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:8px;
                            @if($rec->status==='completed') background:#d1fae5;color:#059669;
                            @elseif($rec->status==='failed') background:var(--color-bg-danger-subtle);color:var(--color-alert-warning-500);
                            @elseif(in_array($rec->status,['transcribing','summarizing'])) background:#fef3c7;color:#d97706;
                            @else background:#e0e7ff;color:#4f46e5; @endif">
                            {{ $rec->status_label }}
                        </span>
                    </div>

                    {{-- 오디오 플레이어 --}}
                    <audio controls preload="none" style="width:100%;height:38px;margin-bottom:8px;">
                        <source src="{{ route('meeting-minutes.recordings.audio', [$meetingMinute, $rec]) }}" type="{{ $rec->mime_type ?? 'audio/mp4' }}">
                    </audio>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="{{ route('meeting-minutes.recordings.download', [$meetingMinute, $rec]) }}"
                           style="font-size:11px;font-weight:600;color:#4f46e5;text-decoration:none;padding:5px 10px;border:1px solid #c7d2fe;border-radius:8px;">
                            ⬇ {{ __('maintenance.recording_download') }}
                        </a>
                        @if($rec->transcription)
                        <button type="button" onclick="var e=document.getElementById('rec-tr-{{ $rec->id }}');e.style.display=e.style.display==='none'?'block':'none';"
                           style="font-size:11px;font-weight:600;color:#0284c7;background:none;cursor:pointer;padding:5px 10px;border:1px solid #bae6fd;border-radius:8px;">
                            📝 {{ __('maintenance.recording_transcript') }}
                        </button>
                        @endif
                    </div>

                    @if($rec->transcription)
                    <div id="rec-tr-{{ $rec->id }}" style="display:none;margin-top:10px;padding:12px;background:#f8fafc;border-radius:10px;font-size:12px;color:var(--color-text-secondary);line-height:1.7;white-space:pre-wrap;max-height:280px;overflow-y:auto;">{{ $rec->transcription }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- 메모 섹션 --}}
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:22px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <span style="width:4px;height:16px;background:#f59e0b;border-radius:2px;display:inline-block;"></span>
                    {{ __('maintenance.memo_section', ['count' => $meetingMinute->memos->count()]) }}
                </div>

                {{-- 메모 입력 --}}
                <form method="POST" action="{{ route('meeting-minutes.memos.store', $meetingMinute) }}" style="margin-bottom:20px;">
                    @csrf
                    <textarea name="content" rows="3" placeholder="{{ __('maintenance.memo_placeholder') }}"
                              style="width:100%;padding:10px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;resize:vertical;font-family:inherit;line-height:1.6;transition:border-color .15s;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    <div style="text-align:right;margin-top:8px;">
                        <button type="submit"
                                style="padding:7px 16px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            {{ __('maintenance.memo_add') }}
                        </button>
                    </div>
                </form>

                {{-- 메모 목록 --}}
                @forelse($meetingMinute->memos as $memo)
                <div style="border-top:1px solid var(--color-border-default);padding-top:14px;margin-top:14px;">
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--t200);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--t700);flex-shrink:0;">
                            {{ mb_substr($memo->user->name,0,1) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                <span style="font-size:12px;font-weight:700;color:var(--color-text-primary);">{{ $memo->user->name }}</span>
                                <span style="font-size:11px;color:var(--color-text-tertiary);">{{ $memo->created_at->format('m.d H:i') }}</span>
                            </div>
                            <div style="font-size:13px;color:var(--color-text-secondary);line-height:1.65;white-space:pre-wrap;">{{ $memo->content }}</div>
                            @if($memo->actionItems->count())
                            <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach($memo->actionItems as $ai)
                                <span style="font-size:11px;padding:2px 8px;background:#fef3c7;border:1px solid #fde68a;border-radius:5px;color:#92400e;">
                                    ⚡ {{ $ai->title }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @if(auth()->id() === $memo->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('meeting-minutes.memos.destroy', $memo) }}" onsubmit="return confirm('{{ __('maintenance.confirm_delete_memo') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" style="border:none;background:transparent;cursor:pointer;color:var(--color-text-tertiary);padding:4px;" title="{{ __('common.delete') }}">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div style="font-size:13px;color:var(--color-text-tertiary);text-align:center;padding:20px 0;">{{ __('maintenance.memo_empty') }}</div>
                @endforelse
            </div>
        </div>

        {{-- 사이드: Action Items --}}
        <div>
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:18px;position:sticky;top:72px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                    <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);display:flex;align-items:center;gap:8px;">
                        ⚡ {{ __('maintenance.action_items') }}
                        <span style="font-size:11px;background:var(--t100);color:var(--t700);padding:1px 7px;border-radius:10px;font-weight:700;">{{ $meetingMinute->actionItems->count() }}</span>
                    </div>
                    <button onclick="document.getElementById('ai-modal').classList.add('show')"
                            style="font-size:11px;font-weight:700;padding:5px 10px;background:var(--t600);color:#fff;border:none;border-radius:7px;cursor:pointer;">
                        + {{ __('common.add') }}
                    </button>
                </div>

                @php
                    $grouped = $meetingMinute->actionItems->groupBy('status');
                    $statusOrder = [
                        'pending'     => __('maintenance.action_status_pending'),
                        'in_progress' => __('maintenance.action_status_in_progress'),
                        'completed'   => __('maintenance.action_status_completed'),
                    ];
                @endphp

                @foreach($statusOrder as $statusKey => $statusLabel)
                @php $items = $grouped[$statusKey] ?? collect(); @endphp
                @if($items->count())
                <div style="margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:700;color:var(--color-text-tertiary);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">
                        {{ $statusLabel }} ({{ $items->count() }})
                    </div>
                    @foreach($items as $item)
                    <div style="border:1px solid var(--color-border-default);border-radius:10px;padding:12px;margin-bottom:7px;{{ $item->isOverdue() ? 'border-color:#fecaca;' : '' }}">
                        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:7px;">
                            <span style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:5px;background:{{ $item->priority_color }}20;color:{{ $item->priority_color }};flex-shrink:0;margin-top:1px;">
                                {{ $item->priority_label }}
                            </span>
                            <span style="font-size:12px;font-weight:600;color:var(--color-text-primary);line-height:1.4;flex:1;">{{ $item->title }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--color-text-tertiary);display:flex;flex-direction:column;gap:4px;">
                            <span>👤 {{ $item->owner_display }}</span>
                            @if($item->due_date)
                            <span style="{{ $item->isOverdue() ? 'color:var(--color-alert-warning-500);' : '' }}">
                                📅 {{ $item->due_date->format('Y.m.d') }} {{ $item->isOverdue() ? '⚠️ ' . __('maintenance.overdue') : '' }}
                            </span>
                            @endif
                        </div>
                        <div style="display:flex;gap:4px;margin-top:8px;">
                            @foreach(['pending' => __('maintenance.action_status_pending'), 'in_progress' => __('maintenance.action_status_in_progress'), 'completed' => __('maintenance.action_status_completed')] as $sk => $sl)
                            @if($sk !== $item->status)
                            <form method="POST" action="{{ route('meeting-minutes.action-items.status', $item) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $sk }}">
                                <button type="submit"
                                        style="font-size:10px;padding:3px 7px;border:1px solid #e8e3ff;border-radius:5px;background:#fff;color:#64748b;cursor:pointer;">
                                    {{ $sl }}
                                </button>
                            </form>
                            @endif
                            @endforeach
                            <form method="POST" action="{{ route('meeting-minutes.action-items.destroy', $item) }}"
                                  onsubmit="return confirm('{{ __('maintenance.confirm_delete_action') }}')" style="margin-left:auto;">
                                @csrf @method('DELETE')
                                <button type="submit" style="font-size:10px;padding:3px 7px;border:1px solid #fecaca;border-radius:5px;background:#fff;color:var(--color-alert-warning-500);cursor:pointer;">{{ __('common.delete') }}</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @endforeach

                @if($meetingMinute->actionItems->isEmpty())
                <div style="font-size:13px;color:var(--color-text-tertiary);text-align:center;padding:20px 0;">{{ __('maintenance.action_item_empty') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Action Item 추가 모달 --}}
@section('modals')
<div id="ai-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10000;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.classList.remove('show')">
    <style>#ai-modal.show{display:flex!important;}</style>
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <span style="font-size:15px;font-weight:700;color:var(--color-text-primary);">{{ __('maintenance.action_item_add') }}</span>
            <button onclick="document.getElementById('ai-modal').classList.remove('show')"
                    style="border:none;background:transparent;cursor:pointer;color:var(--color-text-tertiary);font-size:20px;line-height:1;">×</button>
        </div>
        <form method="POST" action="{{ route('meeting-minutes.action-items.store', $meetingMinute) }}">
            @csrf
            @php $minp = 'width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;font-family:inherit;margin-bottom:10px;'; @endphp
            <input type="text" name="title" required placeholder="{{ __('maintenance.field_task_name') }}" style="{{ $minp }}"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
            <textarea name="description" rows="2" placeholder="{{ __('maintenance.field_task_desc') }}" style="{{ $minp }}resize:none;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <select name="owner_id" style="{{ $minp }}"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    <option value="">{{ __('maintenance.field_owner') }}</option>
                    @foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}@if($tm->email) ({{ $tm->email }})@endif</option>@endforeach
                </select>
                <input type="text" name="owner_name" placeholder="{{ __('maintenance.field_owner_manual') }}" style="{{ $minp }}"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                <input type="date" name="due_date" style="{{ $minp }}"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                <select name="priority" style="{{ $minp }}"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    <option value="medium">{{ __('maintenance.action_priority_medium') }}</option>
                    <option value="high">{{ __('maintenance.action_priority_high') }}</option>
                    <option value="low">{{ __('maintenance.action_priority_low') }}</option>
                </select>
            </div>
            @if($meetingMinute->memos->count())
            <select name="memo_id" style="{{ $minp }}"
                    onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                <option value="">{{ __('maintenance.field_related_memo') }}</option>
                @foreach($meetingMinute->memos as $m)
                <option value="{{ $m->id }}">{{ mb_substr($m->content,0,40) }}</option>
                @endforeach
            </select>
            @endif
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:6px;">
                <button type="button" onclick="document.getElementById('ai-modal').classList.remove('show')"
                        style="padding:8px 16px;border:1.5px solid #e8e3ff;border-radius:8px;background:#fff;font-size:13px;color:#64748b;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit"
                        style="padding:8px 20px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">{{ __('common.add') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
@endsection
