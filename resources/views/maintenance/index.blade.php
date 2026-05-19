@extends('layouts.app')

@section('title', __('maintenance.sr_receipt') . ' — ' . $srTarget->title)

@section('breadcrumb')
<span style="color:#9ca3af;">{{ __('maintenance.sr_receipt') }}</span>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ $srTarget->title }}</span>
@endsection

@section('header-actions')@endsection

@section('content')
<div>

    {{-- SR 대상 헤더 --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:#fff;border-radius:10px;border:1px solid #f3f4f6;box-shadow:0 1px 3px rgba(0,0,0,.04);padding:12px 16px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
            <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6M9 16h4"/></svg>
            <span style="font-size:15px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $srTarget->title }}</span>
            @if($srTarget->project)
            <span style="font-size:11px;color:#6b7280;background:#f3f4f6;border-radius:6px;padding:2px 8px;flex-shrink:0;">{{ $srTarget->project->name }}</span>
            @endif
        </div>
        <button onclick="openCreateModal()"
           style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;flex-shrink:0;"
           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('maintenance.sr_register') }}
        </button>
    </div>

    {{-- 플래시 메시지 --}}
    @if(session('success'))
    <div style="margin-bottom:16px;padding:12px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;font-size:13px;color:#15803d;font-weight:500;">
        {{ session('success') }}
    </div>
    @endif

    {{-- SR 현황 카드 --}}
    <div id="sr-status-summary" style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:18px;">
        @foreach(['all'=>[__('maintenance.cat_all'),'#7c3aed','#f5f3ff'], 'pending'=>[__('maintenance.status_pending'),'#d97706','#fef3c7'], 'in_progress'=>[__('maintenance.status_in_progress'),'#2563eb','#dbeafe'], 'completed'=>[__('maintenance.status_completed'),'#16a34a','#dcfce7'], 'rejected'=>[__('maintenance.status_rejected'),'#dc2626','#fee2e2']] as $s=>[$l,$c,$bg])
        <a href="{{ request()->fullUrlWithQuery(['status' => $s === 'all' ? null : $s, 'priority' => request('priority')]) }}"
           style="padding:13px 14px;background:{{ request('status','all') === $s ? $bg : '#fff' }};border:1.5px solid {{ request('status','all') === $s ? $c : '#ede9fe' }};border-radius:12px;text-decoration:none;transition:all .15s;display:block;"
           onmouseover="this.style.borderColor='{{ $c }}'" onmouseout="this.style.borderColor='{{ request('status','all') === $s ? $c : '#ede9fe' }}'">
            <div style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:3px;">{{ $l }}</div>
            <div style="font-size:22px;font-weight:800;color:{{ $c }};">{{ $counts[$s] }}</div>
        </a>
        @endforeach
    </div>

    {{-- 우선순위 필터 + 뷰 전환 --}}
    <div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap;justify-content:space-between;">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:11px;font-weight:600;color:#94a3b8;">{{ __('maintenance.priority_filter') }}</span>
            @foreach(['all'=>__('maintenance.cat_all'),'urgent'=>__('maintenance.priority_urgent'),'high'=>__('maintenance.priority_high'),'normal'=>__('maintenance.priority_normal'),'low'=>__('maintenance.priority_low')] as $pval=>$plbl)
            <a href="{{ request()->fullUrlWithQuery(['priority' => $pval === 'all' ? null : $pval]) }}"
               style="padding:4px 12px;border-radius:16px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid;
                      {{ request('priority','all') === $pval ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">
                {{ $plbl }}
            </a>
            @endforeach
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0;">
            <button id="srg-btn-list" onclick="setSRView('list')"
                    style="padding:5px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #7c3aed;background:#7c3aed;color:#fff;transition:all .12s;">
                {{ __('maintenance.view_list') }}
            </button>
            <button id="srg-btn-gantt" onclick="setSRView('gantt')"
                    style="padding:5px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;transition:all .12s;">
                {{ __('maintenance.view_gantt') }}
            </button>
            <button id="srg-btn-files" onclick="setSRView('files')"
                    style="padding:5px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;transition:all .12s;display:inline-flex;align-items:center;gap:5px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                {{ __('maintenance.view_files') }}
                <span id="srg-btn-files-cnt" style="font-size:10px;background:#ede9fe;color:#7c3aed;border-radius:8px;padding:1px 6px;font-weight:700;">{{ $projectFiles->count() }}</span>
            </button>
            <button onclick="openIdxUploadModal()"
                    style="padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #ddd6fe;background:#f5f3ff;color:#7c3aed;transition:all .12s;display:inline-flex;align-items:center;gap:4px;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('maintenance.tab_file_upload') }}
            </button>
        </div>
    </div>

    {{-- 목록/간트 영역 --}}
    <div id="sr-list-view">

    {{-- 목록 테이블 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;overflow:hidden;box-shadow:0 2px 12px rgba(124,58,237,.06);">

        @if($maintenances->isEmpty())
        <div style="padding:60px 24px;text-align:center;color:#9ca3af;">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p style="font-size:14px;margin:0;">{{ __('maintenance.sr_empty') }}</p>
            <button onclick="openCreateModal()" style="display:inline-block;margin-top:12px;font-size:13px;color:#7c3aed;background:none;border:none;cursor:pointer;font-weight:600;text-decoration:underline;">{{ __('maintenance.sr_register_first') }}</button>
        </div>
        @else
        <table style="width:100%;border-collapse:collapse;">
            <thead>
            <tr style="background:#faf5ff;border-bottom:1px solid #ede9fe;">
                <th style="padding:10px 16px;font-size:11px;font-weight:600;color:#7c3aed;text-align:left;">{{ __('maintenance.col_title') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:68px;">{{ __('maintenance.col_priority') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:68px;">{{ __('maintenance.col_status') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:76px;">{{ __('maintenance.col_requester') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:88px;">{{ __('maintenance.col_due_date') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:88px;">{{ __('maintenance.col_scheduled_date') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:44px;">{{ __('maintenance.col_replies') }}</th>
                <th style="padding:10px 10px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:80px;">{{ __('maintenance.col_created_at') }}</th>
                @if($canManageSr)
                <th style="padding:10px 8px;font-size:11px;font-weight:600;color:#7c3aed;text-align:center;width:108px;">{{ __('maintenance.col_manage') }}</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach($maintenances as $item)
            <tr data-item-id="{{ $item->id }}"
                onclick="openDetail('{{ route('maintenances.detail', $item) }}')"
                style="border-bottom:1px solid #f5f3ff;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#faf8ff'" onmouseout="this.style.background='transparent'">

                <td style="padding:11px 16px;">
                    <div style="font-size:13px;font-weight:600;color:#1e1b2e;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        @if($item->status === 'pending')
                        <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#ef4444;margin-right:5px;vertical-align:middle;flex-shrink:0;"></span>
                        @endif
                        {{ $item->title }}
                    </div>
                </td>
                <td style="padding:11px 10px;text-align:center;">
                    <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:8px;white-space:nowrap;
                                 color:{{ $item->priority_color }};background:{{ $item->priority === 'urgent' ? '#fee2e2' : ($item->priority === 'high' ? '#fef3c7' : '#f3f4f6') }};">
                        {{ $item->priority_label }}
                    </span>
                </td>
                <td style="padding:11px 10px;text-align:center;">
                    <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:8px;white-space:nowrap;
                                 color:{{ $item->status_color }};background:{{ $item->status_bg }};">
                        {{ $item->status_label }}
                    </span>
                </td>
                <td style="padding:11px 10px;text-align:center;font-size:12px;color:#6b7280;">{{ $item->user->name }}</td>
                <td style="padding:11px 10px;text-align:center;font-size:12px;
                           color:{{ $item->due_date?->isPast() && !in_array($item->status,['completed','rejected']) ? '#dc2626' : '#6b7280' }};">
                    @if($item->due_date)
                        {{ $item->due_date->format('Y.m.d') }}
                        @if($item->due_date->isPast() && !in_array($item->status,['completed','rejected']))
                        <div style="font-size:10px;color:#dc2626;margin-top:1px;">{{ __('maintenance.date_overdue') }}</div>
                        @elseif(!in_array($item->status,['completed','rejected']))
                        <div style="font-size:10px;color:#94a3b8;margin-top:1px;">D-{{ (int) now()->diffInDays($item->due_date) }}</div>
                        @endif
                    @else
                        <span style="color:#d1d5db;">—</span>
                    @endif
                </td>
                <td style="padding:11px 10px;text-align:center;font-size:12px;
                           color:{{ $item->scheduled_date ? '#7c3aed' : '#d1d5db' }};font-weight:{{ $item->scheduled_date ? '600' : '400' }};">
                    {{ $item->scheduled_date?->format('Y.m.d') ?? '—' }}
                </td>
                <td style="padding:11px 10px;text-align:center;font-size:12px;color:#6b7280;">{{ $item->replies_count }}</td>
                <td style="padding:11px 10px;text-align:center;font-size:11px;color:#9ca3af;">{{ $item->created_at->format('m.d') }}</td>
                @if($canManageSr)
                <td style="padding:11px 8px;text-align:center;white-space:nowrap;">
                    <button type="button" data-edit-btn
                            data-id="{{ $item->id }}"
                            data-update-url="{{ route('maintenances.update', $item) }}"
                            data-reload-url="{{ route('maintenances.detail', $item) }}"
                            data-title="{{ $item->title }}"
                            data-priority="{{ $item->priority }}"
                            data-requested-date="{{ $item->requested_date?->format('Y-m-d') }}"
                            data-due-date="{{ $item->due_date?->format('Y-m-d') }}"
                            data-content="{{ $item->content }}"
                            onclick="event.stopPropagation();openEditModal(this)"
                            style="padding:3px 8px;border:1px solid #ddd6fe;border-radius:6px;font-size:11px;color:#7c3aed;background:#fff;cursor:pointer;font-weight:600;">{{ __('common.edit') }}</button>
                    <button type="button"
                            data-id="{{ $item->id }}"
                            data-url="{{ route('maintenances.destroy', $item) }}"
                            onclick="event.stopPropagation();srRowDelete(this)"
                            style="padding:3px 8px;border:1px solid #fecaca;border-radius:6px;font-size:11px;color:#ef4444;background:#fff;cursor:pointer;">{{ __('common.delete') }}</button>
                </td>
                @endif
            </tr>
            @endforeach
            </tbody>
        </table>

        @if($maintenances->hasPages())
        <div style="padding:14px 16px;border-top:1px solid #f5f3ff;">{{ $maintenances->links() }}</div>
        @endif
        @endif
    </div>

    </div>{{-- /sr-list-view --}}

    {{-- 간트 뷰 (프로젝트 간트와 동일한 구조) --}}
    <div id="sr-gantt-view" style="display:none;">

        {{-- 툴바 --}}
        <div id="srg2-toolbar">
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="srg2-vm-group">
                    <button class="srg2-vm-btn" onclick="setSRGView('day')">{{ __('maintenance.gantt_day') }}</button>
                    <button class="srg2-vm-btn active" onclick="setSRGView('week')">{{ __('maintenance.gantt_week') }}</button>
                    <button class="srg2-vm-btn" onclick="setSRGView('month')">{{ __('maintenance.gantt_month') }}</button>
                </div>
                <button onclick="srgGoToday()"
                        style="padding:4px 12px;font-size:12px;font-weight:500;border:1px solid #e4e4e7;border-radius:6px;background:#fff;color:#52525b;cursor:pointer;"
                        onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.today') }}</button>
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;"><span class="srg2-ldot" style="background:#f59e0b;"></span>{{ __('maintenance.status_pending') }}</span>
                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;"><span class="srg2-ldot" style="background:#3b82f6;"></span>{{ __('maintenance.status_in_progress') }}</span>
                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;"><span class="srg2-ldot" style="background:#22c55e;"></span>{{ __('maintenance.status_completed') }}</span>
                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:#71717a;"><span class="srg2-ldot" style="background:#9ca3af;"></span>{{ __('maintenance.status_rejected') }}</span>
            </div>
        </div>

        {{-- 간트 본체 --}}
        <div id="srg2-main">
            {{-- 왼쪽 패널 --}}
            <div id="srg2-left">
                <div id="srg2-left-hdr">
                    <div class="srg2-lh srg2-lh-title">{{ __('maintenance.gantt_col_title') }}<div class="srg2-col-resizer" data-col="title"></div></div>
                    <div class="srg2-lh srg2-lh-status">{{ __('maintenance.gantt_col_status') }}<div class="srg2-col-resizer" data-col="status"></div></div>
                    <div class="srg2-lh srg2-lh-prio">{{ __('maintenance.gantt_col_priority') }}<div class="srg2-col-resizer" data-col="prio"></div></div>
                    <div class="srg2-lh srg2-lh-user">{{ __('maintenance.gantt_col_requester') }}<div class="srg2-col-resizer" data-col="user"></div></div>
                    <div class="srg2-lh srg2-lh-start">{{ __('maintenance.gantt_col_start') }}<div class="srg2-col-resizer" data-col="start"></div></div>
                    <div class="srg2-lh srg2-lh-end">{{ __('maintenance.gantt_col_end') }}<div class="srg2-col-resizer" data-col="end"></div></div>
                </div>
                <div id="srg2-left-body"></div>
            </div>

            {{-- 오른쪽 패널 --}}
            <div id="srg2-right">
                <div id="srg2-right-hdr">
                    <div id="srg2-right-hdr-inner"></div>
                </div>
                <div id="srg2-right-body">
                    <div id="srg2-canvas"></div>
                </div>
            </div>
        </div>

        {{-- 빈 상태 --}}
        <div id="srg2-empty" style="display:none;padding:60px 24px;text-align:center;background:#fff;border:1px solid #e4e4e7;border-radius:12px;color:#9ca3af;font-size:14px;">{{ __('maintenance.sr_empty_gantt') }}</div>
    </div>

    {{-- ───── 파일 뷰 ───── --}}
    <div id="sr-files-view" style="display:none;">
        <div style="display:flex;gap:14px;align-items:flex-start;">

            {{-- 카테고리 사이드바 --}}
            <div id="sr-pf-cat-sidebar" style="width:188px;flex-shrink:0;display:flex;flex-direction:column;gap:5px;">

                <button onclick="srPfSetCategory('all')" id="sr-pf-cat-all"
                        style="display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:9px;border:none;cursor:pointer;font-size:12px;font-weight:600;text-align:left;width:100%;background:#7c3aed;color:#fff;transition:all .12s;">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span style="flex:1;">{{ __('maintenance.cat_all') }}</span>
                    <span id="sr-pf-count" style="font-size:10px;background:rgba(255,255,255,.25);padding:1px 6px;border-radius:8px;">{{ $projectFiles->count() }}</span>
                </button>

                <button onclick="srPfSetCategory('none')" id="sr-pf-cat-none"
                        style="display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:9px;border:none;cursor:pointer;font-size:12px;font-weight:600;text-align:left;width:100%;background:#f3f4f6;color:#374151;transition:all .12s;">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <span style="flex:1;">{{ __('maintenance.cat_uncategorized') }}</span>
                    <span style="font-size:10px;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:8px;">{{ $projectFiles->whereNull('maintenance_category_id')->count() }}</span>
                </button>

                <div id="sr-pf-cat-list" style="display:flex;flex-direction:column;gap:4px;margin-top:2px;">
                    @foreach($fileCategories as $fc)
                    <div style="display:flex;align-items:center;gap:2px;">
                        <button onclick="srPfSetCategory({{ $fc->id }})" id="sr-pf-cat-{{ $fc->id }}"
                                data-cat-id="{{ $fc->id }}"
                                style="flex:1;display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:9px;border:none;cursor:pointer;font-size:12px;font-weight:600;text-align:left;background:#f9fafb;color:#374151;transition:all .12s;min-width:0;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $fc->color }};flex-shrink:0;display:inline-block;"></span>
                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $fc->name }}</span>
                            <span style="font-size:10px;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:8px;flex-shrink:0;">{{ $projectFiles->where('maintenance_category_id', $fc->id)->count() }}</span>
                        </button>
                        <button onclick="srPfDeleteCategory({{ $fc->id }}, this)"
                                style="width:20px;height:20px;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;line-height:1;padding:0;flex-shrink:0;border-radius:4px;transition:color .12s;"
                                onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'"
                                title="{{ __('common.delete') }}">×</button>
                    </div>
                    @endforeach
                </div>

                {{-- 카테고리 추가 --}}
                <div style="margin-top:6px;">
                    <button onclick="srPfToggleCatForm()"
                            style="display:flex;align-items:center;gap:5px;padding:6px 10px;width:100%;background:none;border:1.5px dashed #ddd6fe;border-radius:9px;cursor:pointer;font-size:11px;font-weight:600;color:#7c3aed;transition:all .12s;"
                            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('maintenance.cat_add') }}
                    </button>
                    <div id="sr-pf-cat-form" style="display:none;margin-top:6px;padding:10px;background:#faf5ff;border:1px solid #ede9fe;border-radius:10px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                            <input type="color" id="sr-pf-cat-color" value="#7c3aed"
                                   style="width:28px;height:28px;padding:0;border:1.5px solid #ddd6fe;border-radius:6px;cursor:pointer;background:none;flex-shrink:0;">
                            <input id="sr-pf-cat-name" type="text" placeholder="{{ __('maintenance.cat_name_placeholder') }}" maxlength="80"
                                   style="flex:1;padding:6px 9px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:12px;outline:none;background:#fff;"
                                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'"
                                   onkeydown="if(event.key==='Enter')srPfAddCategory()">
                        </div>
                        <div style="display:flex;gap:5px;">
                            <button onclick="srPfAddCategory()"
                                    style="flex:1;padding:5px 0;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;">{{ __('common.add') }}</button>
                            <button onclick="srPfToggleCatForm()"
                                    style="padding:5px 10px;background:#fff;border:1.5px solid #e5e7eb;border-radius:7px;font-size:11px;cursor:pointer;color:#6b7280;">{{ __('common.cancel') }}</button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 파일 목록 영역 --}}
            <div style="flex:1;min-width:0;">
                <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;overflow:hidden;box-shadow:0 2px 12px rgba(124,58,237,.06);">

                    {{-- 헤더 --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #ede9fe;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.sr_files') }}</span>
                            <span style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 8px;border-radius:10px;font-weight:700;" id="sr-pf-count">{{ $projectFiles->count() }}</span>
                        </div>
                        <span style="font-size:11px;color:#9ca3af;">{{ __('maintenance.attachment_hint') }}</span>
                    </div>

                    {{-- 파일 목록 --}}
                    <div id="sr-pf-list">
                    @forelse($projectFiles as $pf)
                    @php
                        $pfIsUrl      = $pf->isUrlType();
                        $pfCanPreview = $pf->previewType() || $pfIsUrl;
                        $pfCanDel     = $pf->uploaded_by === auth()->id() || auth()->user()->isAdmin();
                        $pfCat        = $pf->category;
                    @endphp
                    <div id="sr-pf-{{ $pf->id }}"
                         data-cat="{{ $pf->maintenance_category_id ?? 'none' }}"
                         data-maintenance="{{ $pf->maintenance_id }}"
                         style="display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #f9f5ff;transition:background .1s;"
                         onmouseover="this.style.background='#faf8ff'" onmouseout="this.style.background=''">
                        <span style="font-size:18px;flex-shrink:0;width:24px;text-align:center;">{{ $pf->icon }}</span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $pf->original_name }}</div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                @if($pf->maintenance)
                                <span style="color:#7c3aed;font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $pf->maintenance->title }}">{{ $pf->maintenance->title }}</span>
                                @endif
                                <span>{{ $pf->uploader?->name ?? '—' }}</span>
                                <span>{{ $pf->created_at->format('Y.m.d') }}</span>
                                @if(!$pfIsUrl)<span>{{ number_format($pf->size / 1024, 0) }} KB</span>@endif
                                @if($pf->comments_count > 0)<span style="color:#7c3aed;">{{ __('maintenance.file_comments', ['count' => $pf->comments_count]) }}</span>@endif
                            </div>
                        </div>
                        {{-- 카테고리 배지 --}}
                        <div style="position:relative;flex-shrink:0;">
                            <button onclick="srPfToggleCatDrop({{ $pf->id }})"
                                    id="sr-pf-cat-badge-{{ $pf->id }}"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;border:1px solid {{ $pfCat ? '#ddd6fe' : '#e5e7eb' }};background:{{ $pfCat ? '#f5f3ff' : '#f9fafb' }};color:{{ $pfCat ? '#6d28d9' : '#9ca3af' }};font-size:10px;font-weight:600;cursor:pointer;transition:all .12s;white-space:nowrap;">
                                @if($pfCat)
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $pfCat->color }};flex-shrink:0;display:inline-block;"></span>
                                {{ $pfCat->name }}
                                @else
                                {{ __('maintenance.cat_uncategorized') }}
                                @endif
                                <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="sr-pf-cat-drop-{{ $pf->id }}"
                                 style="display:none;position:absolute;right:0;top:calc(100% + 4px);z-index:100;background:#fff;border:1px solid #ede9fe;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:150px;padding:4px;">
                                <button onclick="srPfAssignCategory({{ $pf->id }}, null)"
                                        style="display:flex;align-items:center;gap:7px;width:100%;padding:6px 10px;background:none;border:none;border-radius:7px;font-size:12px;cursor:pointer;color:#6b7280;transition:background .1s;"
                                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'">
                                    <span style="width:8px;height:8px;border-radius:50%;background:#e5e7eb;flex-shrink:0;display:inline-block;"></span>
                                    {{ __('maintenance.cat_uncategorized') }}
                                </button>
                                @foreach($fileCategories as $fc)
                                <button onclick="srPfAssignCategory({{ $pf->id }}, {{ $fc->id }})"
                                        style="display:flex;align-items:center;gap:7px;width:100%;padding:6px 10px;background:none;border:none;border-radius:7px;font-size:12px;cursor:pointer;color:#374151;transition:background .1s;"
                                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $fc->color }};flex-shrink:0;display:inline-block;"></span>
                                    {{ $fc->name }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        {{-- 액션 버튼 --}}
                        <div style="display:flex;gap:4px;flex-shrink:0;">
                            @php
                                $pfMid       = $pf->maintenance_id;
                                $pfDlUrl     = $pfMid
                                    ? route('maintenances.files.download',    [$pfMid, $pf->id])
                                    : route('sr-targets.maintenance-files.download', [$srTarget->id, $pf->id]);
                                $pfPvUrl     = $pfMid
                                    ? route('maintenances.files.preview-data', [$pfMid, $pf->id])
                                    : route('sr-targets.maintenance-files.preview-data', [$srTarget->id, $pf->id]);
                                $pfMidJs     = $pfMid ?? 'null';
                            @endphp
                            @if($pfCanPreview)
                                @if($pfIsUrl)
                                <button onclick="openUrlViewer({{ $pf->id }}, {{ $srTarget->id }}, {{ json_encode($pf->original_name) }}, {{ json_encode($pf->getEmbedUrl()) }}, {{ json_encode($pf->source_url) }})"
                                        style="padding:3px 8px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">{{ __('maintenance.btn_url_open') }}</button>
                                @else
                                <button onclick="openPreview({{ $pf->id }}, {{ $srTarget->id }}, '{{ $pfPvUrl }}', '{{ $pfDlUrl }}')"
                                        style="padding:3px 8px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">{{ __('maintenance.btn_preview') }}</button>
                                @endif
                            @endif
                            @if(!$pfIsUrl)
                            <a href="{{ $pfDlUrl }}"
                               style="padding:3px 8px;background:#f9fafb;color:#374151;border:1px solid #e5e7eb;border-radius:5px;font-size:10px;font-weight:600;text-decoration:none;">{{ __('maintenance.btn_download') }}</a>
                            @endif
                            @if($pf->isShareable())
                            <button onclick="srPfToggleShare({{ $pf->id }}, {{ $pfMidJs }}, this)"
                                    data-active="{{ $pf->share_token ? '1' : '0' }}"
                                    data-share-url="{{ $pf->share_token ? route('maintenance-files.public-share', $pf->share_token) : '' }}"
                                    style="padding:3px 8px;background:{{ $pf->share_token ? '#dcfce7' : '#f9fafb' }};color:{{ $pf->share_token ? '#16a34a' : '#6b7280' }};border:1px solid {{ $pf->share_token ? '#bbf7d0' : '#e5e7eb' }};border-radius:5px;font-size:10px;font-weight:600;cursor:pointer;">
                                {{ $pf->share_token ? __('maintenance.btn_sharing') : __('maintenance.btn_share') }}
                            </button>
                            @endif
                            @if($pfCanDel)
                            <button onclick="srPfDelete({{ $pf->id }}, {{ $pfMidJs }})"
                                    style="padding:3px 8px;background:#fff;color:#ef4444;border:1px solid #fecaca;border-radius:5px;font-size:10px;cursor:pointer;">{{ __('common.delete') }}</button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div id="sr-pf-empty" style="text-align:center;color:#9ca3af;font-size:13px;padding:48px 0;">
                        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2" style="margin:0 auto 10px;display:block;opacity:.35;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('maintenance.file_empty') }}
                    </div>
                    @endforelse
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

{{-- ───── 상세 모달 ───── --}}
<div id="dt-overlay" onclick="closeDetail()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10200;backdrop-filter:blur(2px);"></div>

<div id="dt-modal"
     style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.96);z-index:10201;
            background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.22);
            width:860px;max-width:calc(100vw - 24px);max-height:88vh;
            flex-direction:column;overflow:hidden;transition:transform .18s,opacity .18s;">

    {{-- 모달 헤더 (닫기 버튼) --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px 10px;border-bottom:1px solid #f0eeff;flex-shrink:0;">
        <span style="font-size:12px;font-weight:600;color:#94a3b8;">{{ __('maintenance.detail_title') }}</span>
        <button onclick="closeDetail()"
                style="width:28px;height:28px;border-radius:50%;background:#f3f4f6;border:none;cursor:pointer;font-size:16px;color:#6b7280;display:flex;align-items:center;justify-content:center;line-height:1;transition:background .15s;"
                onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">×</button>
    </div>

    {{-- 고정 헤더 (partial에서 JS로 이동) --}}
    <div id="dt-fixed-header" style="flex-shrink:0;"></div>

    {{-- 모달 본문 (스크롤) --}}
    <div id="dt-body" style="overflow-y:auto;flex:1;">
        {{-- 로딩 스피너 --}}
        <div id="dt-loading" style="padding:60px;text-align:center;color:#9ca3af;">
            <svg style="animation:spin 1s linear infinite;display:inline-block;" width="28" height="28" fill="none" stroke="#7c3aed" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-opacity=".25" stroke-width="3"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
        <div id="dt-content"></div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#dt-modal { display:none; }
#dt-modal.open { display:flex !important; transform:translate(-50%,-50%) scale(1); }
</style>

{{-- ───── 파일 미리보기 모달 (전역) ───── --}}
@include('partials.file-preview-modal')

{{-- ───── SR 파일 업로드 모달 ───── --}}
<div id="dt-mf-overlay" onclick="dtCloseMfUpload()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10300;"></div>
<div id="dt-mf-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10301;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:480px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid #f0f0f0;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_attachment_add') }}</h3>
        <button onclick="dtCloseMfUpload()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>
    <div style="display:flex;border-bottom:1px solid #f0f0f0;">
        <button id="dt-mf-tab-file" onclick="dtSwitchMfTab('file')"
                style="flex:1;padding:10px 0;background:none;border:none;border-bottom:2px solid #7c3aed;font-size:13px;font-weight:600;color:#7c3aed;cursor:pointer;">{{ __('maintenance.tab_file_upload') }}</button>
        <button id="dt-mf-tab-url" onclick="dtSwitchMfTab('url')"
                style="flex:1;padding:10px 0;background:none;border:none;border-bottom:2px solid transparent;font-size:13px;font-weight:600;color:#9ca3af;cursor:pointer;">{{ __('maintenance.tab_url_register') }}</button>
    </div>
    <div id="dt-mf-panel-file" style="padding:20px;">
        <div onclick="document.getElementById('dt-mf-file-input').click()"
             style="border:2px dashed #ddd6fe;border-radius:10px;padding:28px;text-align:center;cursor:pointer;background:#faf5ff;"
             ondragover="event.preventDefault();this.style.borderColor='#7c3aed'" ondragleave="this.style.borderColor='#ddd6fe'"
             ondrop="event.preventDefault();this.style.borderColor='#ddd6fe';dtMfHandleDrop(event)">
            <svg width="28" height="28" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p style="font-size:13px;font-weight:600;color:#7c3aed;margin:0 0 4px;">{{ __('maintenance.upload_click_drag_short') }}</p>
            <p style="font-size:11px;color:#9ca3af;margin:0;">{{ __('maintenance.upload_max_size') }}</p>
        </div>
        <input type="file" id="dt-mf-file-input" style="display:none;" onchange="dtMfHandleFile(this.files[0])">
        <div id="dt-mf-file-preview" style="display:none;margin-top:10px;padding:9px 12px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;display:flex;align-items:center;gap:10px;">
            <span id="dt-mf-file-name" style="flex:1;font-size:13px;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            <button onclick="dtMfClearFile()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:16px;padding:0;">×</button>
        </div>
        <div style="margin-top:10px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ __('maintenance.field_description') }}</label>
            <input type="text" id="dt-mf-file-desc" maxlength="255" placeholder="{{ __('maintenance.field_description') }}"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div id="dt-mf-file-err" style="display:none;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:12px;color:#dc2626;margin-top:8px;"></div>
        <div style="display:flex;gap:8px;margin-top:14px;">
            <button onclick="dtMfSubmitFile()" id="dt-mf-file-btn" style="flex:1;padding:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">{{ __('maintenance.btn_upload') }}</button>
            <button onclick="dtCloseMfUpload()" style="padding:10px 18px;background:#fff;border:1.5px solid #e5e7eb;color:#52525b;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit;">{{ __('common.cancel') }}</button>
        </div>
    </div>
    <div id="dt-mf-panel-url" style="display:none;padding:20px;">
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">URL <span style="color:#ef4444;">*</span></label>
            <input type="url" id="dt-mf-url-src" placeholder="https://..." maxlength="2048"
                   style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div style="margin-top:10px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ __('maintenance.field_display_name') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="dt-mf-url-name" maxlength="255" placeholder="{{ __('maintenance.field_display_name') }}"
                   style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div id="dt-mf-url-err" style="display:none;padding:8px 10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;font-size:12px;color:#dc2626;margin-top:8px;"></div>
        <div style="display:flex;gap:8px;margin-top:14px;">
            <button onclick="dtMfSubmitUrl()" id="dt-mf-url-btn" style="flex:1;padding:10px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;">{{ __('maintenance.btn_url_register') }}</button>
            <button onclick="dtCloseMfUpload()" style="padding:10px 18px;background:#fff;border:1.5px solid #e5e7eb;color:#52525b;border-radius:8px;font-size:13px;cursor:pointer;font-family:inherit;">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

{{-- ───── 등록 모달 ───── --}}
<div id="m-overlay" onclick="closeCreateModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="m-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:660px;max-width:calc(100vw - 32px);max-height:92vh;overflow-y:auto;">

    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $srTarget->title }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_register') }}</h3>
        </div>
        <button onclick="closeCreateModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="m-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
        @csrf

        {{-- 제목 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.field_title') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="m-title" name="title" required placeholder="{{ __('maintenance.field_title') }}..."
                   style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        {{-- 우선순위 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;">{{ __('maintenance.field_priority') }} <span style="color:#ef4444;">*</span></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;" id="priority-chips">
                @foreach(['low' => [__('maintenance.priority_low'),'#6b7280'], 'normal' => [__('maintenance.priority_normal'),'#2563eb'], 'high' => [__('maintenance.priority_high'),'#d97706'], 'urgent' => [__('maintenance.priority_urgent'),'#dc2626']] as $val => [$lbl, $clr])
                <label style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;transition:all .15s;user-select:none;" id="pri-chip-{{ $val }}">
                    <input type="radio" name="priority" value="{{ $val }}" {{ $val === 'normal' ? 'checked' : '' }}
                           style="display:none;" onchange="updateChip()">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></span>
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>

        {{-- 날짜 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_requested') }}</label>
                <input type="date" name="requested_date" id="m-requested-date"
                       value="{{ date('Y-m-d') }}"
                       style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_due') }}</label>
                <input type="date" name="due_date" id="m-due-date"
                       style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
        </div>

        {{-- 내용 --}}
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                <label style="font-size:12px;font-weight:600;color:#374151;">{{ __('maintenance.field_content') }} <span style="color:#ef4444;">*</span></label>
                <button type="button" onclick="mRefineCreateContent(this)"
                        style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    {{ __('maintenance.weeks_refine') }}
                </button>
            </div>
            <div class="sr-editor-wrap">
                <div id="m-content-editor" style="min-height:130px;"></div>
            </div>
            <input type="hidden" id="m-content" name="content">
        </div>

        {{-- 파일/URL 첨부 --}}
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;">
                <label style="font-size:12px;font-weight:600;color:#374151;">{{ __('maintenance.field_attachment') }} <span style="font-weight:400;color:#9ca3af;">({{ __('common.optional') }})</span></label>
                <div style="display:flex;gap:0;border:1.5px solid #e4e4e7;border-radius:7px;overflow:hidden;">
                    <button type="button" id="m-attach-tab-file" onclick="mSwitchAttachTab('file')"
                            style="padding:4px 12px;font-size:11px;font-weight:600;border:none;cursor:pointer;background:#7c3aed;color:#fff;transition:all .12s;">{{ __('maintenance.btn_attach_file') }}</button>
                    <button type="button" id="m-attach-tab-url" onclick="mSwitchAttachTab('url')"
                            style="padding:4px 12px;font-size:11px;font-weight:600;border:none;cursor:pointer;background:#f9fafb;color:#6b7280;transition:all .12s;">{{ __('maintenance.btn_attach_url') }}</button>
                </div>
            </div>

            {{-- 파일 탭 --}}
            <div id="m-attach-panel-file">
                <div id="m-drop-zone"
                     style="border:2px dashed #ddd6fe;border-radius:9px;padding:16px;text-align:center;cursor:pointer;background:#faf5ff;transition:all .15s;"
                     onclick="document.getElementById('m-file-input').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#7c3aed';this.style.background='#ede9fe';"
                     ondragleave="this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';"
                     ondrop="event.preventDefault();this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';mHandleFileDrop(event.dataTransfer.files);">
                    <svg width="20" height="20" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 6px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <p style="font-size:12px;color:#7c3aed;font-weight:600;margin:0;">{{ __('maintenance.upload_click_or_drag') }}</p>
                </div>
                <input type="file" id="m-file-input" name="attachments[]" multiple style="display:none;" onchange="mHandleFileSelect(this.files)">
                <div id="m-file-list" style="display:flex;flex-direction:column;gap:4px;margin-top:6px;"></div>
            </div>

            {{-- URL 탭 --}}
            <div id="m-attach-panel-url" style="display:none;">
                <div style="display:flex;gap:6px;margin-bottom:6px;">
                    <input type="text" id="m-url-name" placeholder="{{ __('maintenance.field_display_name') }}"
                           style="flex:1;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="url" id="m-url-src" placeholder="https://..."
                           style="flex:2;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                    <button type="button" onclick="mAddUrl()"
                            style="padding:7px 12px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">{{ __('common.add') }}</button>
                </div>
                <div id="m-url-list" style="display:flex;flex-direction:column;gap:4px;"></div>
            </div>
        </div>

        <div id="m-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:2px;">
            <button type="submit" id="m-submit"
                    style="flex:1;padding:10px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none;border-radius:9px;cursor:pointer;font-family:inherit;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                {{ __('maintenance.btn_register') }}
            </button>
            <button type="button" onclick="closeCreateModal()"
                    style="padding:10px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
        </div>
    </form>
</div>

{{-- ───── 파일 업로드 모달 (인덱스) ───── --}}
<div id="idx-upload-overlay" onclick="closeIdxUploadModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10200;"></div>
<div id="idx-upload-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10201;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:520px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $srTarget->title }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_file_upload') }}</h3>
        </div>
        <button onclick="closeIdxUploadModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>
    <div style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">

        {{-- SR 항목 선택 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.sr_receipt') }} <span style="font-size:11px;color:#9ca3af;font-weight:400;">({{ __('maintenance.idx_sr_optional') }})</span></label>
            <select id="idx-upload-maint-id"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;cursor:pointer;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                <option value="">{{ __('maintenance.idx_sr_unlinked') }}</option>
                @foreach($allMaintenances as $m)
                <option value="{{ $m->id }}">{{ $m->title }}</option>
                @endforeach
            </select>
        </div>

        {{-- 카테고리 --}}
        @if($fileCategories->isNotEmpty())
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('common.category') }}</label>
            <select id="idx-upload-cat-id"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;cursor:pointer;"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                <option value="">{{ __('maintenance.cat_uncategorized') }}</option>
                @foreach($fileCategories as $fc)
                <option value="{{ $fc->id }}">{{ $fc->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- 파일/URL 탭 --}}
        <div>
            <div style="display:flex;border-bottom:2px solid #f3f4f6;margin-bottom:12px;gap:0;">
                <button type="button" id="idx-tab-file" onclick="idxSwitchTab('file')"
                        style="padding:7px 16px;font-size:12px;font-weight:600;border:none;background:none;cursor:pointer;color:#7c3aed;border-bottom:2px solid #7c3aed;margin-bottom:-2px;transition:all .12s;">{{ __('maintenance.btn_attach_file') }}</button>
                <button type="button" id="idx-tab-url" onclick="idxSwitchTab('url')"
                        style="padding:7px 16px;font-size:12px;font-weight:600;border:none;background:none;cursor:pointer;color:#9ca3af;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .12s;">{{ __('maintenance.btn_attach_url') }}</button>
            </div>

            {{-- 파일 패널 --}}
            <div id="idx-panel-file">
                <div id="idx-drop-zone"
                     style="border:2px dashed #ddd6fe;border-radius:9px;padding:20px;text-align:center;cursor:pointer;background:#faf5ff;transition:all .15s;"
                     onclick="document.getElementById('idx-file-input').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#7c3aed';this.style.background='#ede9fe';"
                     ondragleave="this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';"
                     ondrop="event.preventDefault();this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';idxHandleDrop(event.dataTransfer.files);">
                    <svg width="22" height="22" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <p style="font-size:12px;color:#7c3aed;font-weight:600;margin:0 0 2px;">{{ __('maintenance.upload_click_drag_idx') }}</p>
                    <p style="font-size:11px;color:#9ca3af;margin:0;">{{ __('maintenance.upload_max_hint') }}</p>
                </div>
                <input type="file" id="idx-file-input" multiple style="display:none;" onchange="idxHandleFileSelect(this.files)">
                <div id="idx-file-list" style="display:flex;flex-direction:column;gap:4px;margin-top:8px;"></div>
            </div>

            {{-- URL 패널 --}}
            <div id="idx-panel-url" style="display:none;">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <input type="text" id="idx-url-name" placeholder="{{ __('maintenance.field_display_name') }}"
                           style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                    <input type="url" id="idx-url-src" placeholder="https://..."
                           style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
        </div>

        <div id="idx-upload-err" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;">
            <button type="button" id="idx-upload-btn" onclick="idxDoUpload()"
                    style="flex:1;padding:10px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,#7c3aed,#6d28d9);border:none;border-radius:9px;cursor:pointer;font-family:inherit;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('maintenance.btn_upload') }}</button>
            <button type="button" onclick="closeIdxUploadModal()"
                    style="padding:10px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

{{-- ───── 수정 모달 ───── --}}
<div id="edit-overlay" onclick="closeEditModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10300;"></div>
<div id="edit-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10301;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:560px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">

    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('maintenance.sr_edit') }}</h3>
        <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="edit-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:14px;">
        @csrf
        <input type="hidden" name="_method" value="PATCH">

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.field_title') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="edit-title" name="title" required
                   style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:7px;">{{ __('maintenance.field_priority') }} <span style="color:#ef4444;">*</span></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach(['low' => [__('maintenance.priority_low'),'#6b7280'], 'normal' => [__('maintenance.priority_normal'),'#2563eb'], 'high' => [__('maintenance.priority_high'),'#d97706'], 'urgent' => [__('maintenance.priority_urgent'),'#dc2626']] as $val => [$lbl, $clr])
                <label style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;color:#374151;transition:all .15s;user-select:none;" id="edit-pri-chip-{{ $val }}">
                    <input type="radio" name="priority" value="{{ $val }}" style="display:none;" onchange="updateEditChip()">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $clr }};flex-shrink:0;"></span>
                    {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_requested') }}</label>
                <input type="date" name="requested_date" id="edit-requested-date"
                       style="width:100%;padding:9px 12px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('maintenance.date_due') }}</label>
                <input type="date" name="due_date" id="edit-due-date"
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
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                {{ __('common.save') }}
            </button>
            <button type="button" onclick="closeEditModal()"
                    style="padding:10px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
        </div>
    </form>
</div>

@include('meeting-minutes._refine')

@endsection

@push('styles')
@include('maintenance._quill_assets')
<style>
/* ═══ SR 간트 (프로젝트 간트와 동일한 스타일) ═══ */
:root { --srg-row-h:36px; --srg-hdr-h:52px; --srg-bar-h:22px; }

#srg2-toolbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:10px; }
#srg2-main {
    display:flex;background:#fff;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;
    height:calc(100vh - 260px);min-height:380px;
}

/* Left panel */
#srg2-left {
    --sc-title:180px;--sc-status:74px;--sc-prio:68px;--sc-user:70px;--sc-start:62px;--sc-end:74px;
    width:calc(var(--sc-title)+var(--sc-status)+var(--sc-prio)+var(--sc-user)+var(--sc-start)+var(--sc-end));
    flex-shrink:0;display:flex;flex-direction:column;border-right:2px solid #e4e4e7;
}
#srg2-left-hdr { display:flex;align-items:center;height:var(--srg-hdr-h);background:#f8fafc;border-bottom:1px solid #e4e4e7;flex-shrink:0; }
#srg2-left-body { flex:1;overflow-y:auto;overflow-x:hidden;scrollbar-width:none; }
#srg2-left-body::-webkit-scrollbar { display:none; }

.srg2-lh { display:flex;align-items:center;font-size:11px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:.04em;padding:0 10px;height:100%;border-right:1px solid #e4e4e7;position:relative;user-select:none;flex-shrink:0; }
.srg2-lh:last-child { border-right:none; }
.srg2-lh-title  { width:var(--sc-title); }
.srg2-lh-status { width:var(--sc-status); }
.srg2-lh-prio   { width:var(--sc-prio); }
.srg2-lh-user   { width:var(--sc-user); }
.srg2-lh-start  { width:var(--sc-start); }
.srg2-lh-end    { width:var(--sc-end); }

.srg2-row { display:flex;align-items:center;height:var(--srg-row-h);border-bottom:1px solid #f4f4f5;cursor:pointer; }
.srg2-row:hover { background:#fafafa; }
.srg2-lc { display:flex;align-items:center;padding:0 8px;height:100%;overflow:hidden;font-size:12px;color:#3f3f46;border-right:1px solid #f4f4f5;flex-shrink:0; }
.srg2-lc:last-child { border-right:none; }
.srg2-lc-title  { width:var(--sc-title);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.srg2-lc-status { width:var(--sc-status); }
.srg2-lc-prio   { width:var(--sc-prio); }
.srg2-lc-user   { width:var(--sc-user);font-size:12px;color:#71717a; }
.srg2-lc-start  { width:var(--sc-start);font-size:12px;color:#71717a; }
.srg2-lc-end    { width:var(--sc-end);font-size:12px;color:#71717a; }

.srg2-col-resizer { position:absolute;right:-4px;top:0;bottom:0;width:8px;cursor:col-resize;z-index:20; }
.srg2-col-resizer::after { content:'';position:absolute;left:50%;top:20%;bottom:20%;width:2px;transform:translateX(-50%);background:#d4d4d8;border-radius:1px;opacity:0;transition:opacity .15s; }
.srg2-col-resizer:hover::after { opacity:1;background:#7c3aed; }

/* Right panel */
#srg2-right { flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0; }
#srg2-right-hdr { height:var(--srg-hdr-h);overflow:hidden;flex-shrink:0;border-bottom:1px solid #e4e4e7;background:#f8fafc; }
#srg2-right-hdr-inner { display:flex;flex-direction:column;width:max-content; }
.srg2-th-row { display:flex; }
.srg2-th-major { display:flex;align-items:center;padding:0 10px;font-size:11.5px;font-weight:600;color:#52525b;border-right:1px solid #e4e4e7;border-bottom:1px solid #e4e4e7;height:22px;flex-shrink:0;white-space:nowrap;background:#f8fafc; }
.srg2-th-minor { display:flex;align-items:center;justify-content:center;font-size:11px;color:#94a3b8;border-right:1px solid #f4f4f5;height:30px;flex-shrink:0;white-space:nowrap; }
.srg2-th-minor.today    { color:#7c3aed;font-weight:700;background:#f5f3ff; }
.srg2-th-minor.wkend   { color:#d1d5db; }
.srg2-th-minor.holiday { color:#ef4444 !important;font-weight:600; }
.srg2-col-holiday-bg   { position:absolute;top:0;background:rgba(239,68,68,.045);pointer-events:none;z-index:0; }
.srg2-col-wkend-bg     { position:absolute;top:0;background:rgba(59,130,246,.03);pointer-events:none;z-index:0; }

#srg2-right-body { flex:1;overflow:auto;position:relative; }
#srg2-canvas { position:relative; }

/* Chart decorations */
.srg2-row-bg { position:absolute;left:0;right:0; }
.srg2-row-bg.even { background:#fafafa; }
.srg2-today-line { position:absolute;top:0;width:2px;background:#ef4444;opacity:.6;pointer-events:none;z-index:5; }
.srg2-today-top { position:absolute;top:0;width:10px;height:10px;border-radius:50%;background:#ef4444;margin-left:-4px;z-index:6; }
.srg2-grid-line { position:absolute;top:0;width:1px;background:#f0f0f0;pointer-events:none; }

/* Bars */
.srg2-bar { position:absolute;border-radius:5px;cursor:grab;z-index:3;overflow:hidden;display:flex;align-items:center;transition:filter .12s,box-shadow .12s; }
.srg2-bar:hover { filter:brightness(1.08);box-shadow:0 2px 8px rgba(0,0,0,.15);z-index:4; }
.srg2-bar.dragging { opacity:.82;z-index:10;box-shadow:0 6px 20px rgba(0,0,0,.28);cursor:grabbing;transition:none; }
.srg2-bar-label { position:relative;z-index:1;padding:0 7px;font-size:11.5px;font-weight:500;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;pointer-events:none; }
.srg2-bar-resize { position:absolute;right:0;top:0;bottom:0;width:7px;cursor:col-resize;z-index:5;border-radius:0 5px 5px 0;flex-shrink:0; }
.srg2-bar-resize:hover { background:rgba(0,0,0,.2); }
.srg2-bar.s-pending     { background:#f59e0b; }
.srg2-bar.s-in_progress { background:#3b82f6; }
.srg2-bar.s-completed   { background:#22c55e; }
.srg2-bar.s-rejected    { background:#9ca3af; }

/* Toolbar */
.srg2-vm-group { display:flex;background:#f4f4f5;border-radius:8px;padding:3px;gap:2px; }
.srg2-vm-btn { padding:4px 12px;font-size:12px;font-weight:500;border:none;border-radius:6px;cursor:pointer;background:transparent;color:#71717a;transition:all .12s; }
.srg2-vm-btn.active { background:#fff;color:#18181b;box-shadow:0 1px 3px rgba(0,0,0,.1); }
.srg2-ldot { width:10px;height:10px;border-radius:3px;display:inline-block; }
</style>
@endpush

@section('scripts')
@php
$_srgHolidays = [];
for ($_yr = 2023; $_yr <= 2028; $_yr++) {
    $_srgHolidays = array_merge($_srgHolidays, array_keys(\App\Helpers\KoreanHolidays::getHolidays($_yr)));
}
$_srgHolidays = array_values(array_unique($_srgHolidays));
@endphp
<script>
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const STORE_URL = '{{ route('sr-targets.maintenances.store', $srTarget) }}';

/* ── i18n strings ─────────────────────────────── */
const IDX_STR = {
    enterContent:      '{{ __("maintenance.js_enter_content") }}',
    saving:            '{{ __("maintenance.js_saving") }}',
    register:          '{{ __("maintenance.btn_register") }}',
    saveFail:          '{{ __("maintenance.js_save_fail") }}',
    networkError:      '{{ __("maintenance.js_network_error") }}',
    save:              '{{ __("common.save") }}',
    registering:       '{{ __("maintenance.js_registering_reply") }}',
    replyFail:         '{{ __("maintenance.js_reply_fail") }}',
    confirmDeleteSr:   '{{ __("maintenance.confirm_delete_sr") }}',
    confirmDeleteFile: '{{ __("maintenance.confirm_delete_file") }}',
    deleteFail:        '{{ __("maintenance.js_delete_fail") }}',
    loadFail:          '{{ __("maintenance.js_load_fail2") }}',
    scheduleFail:      '{{ __("maintenance.js_schedule_fail") }}',
    srAttachAdd:       '{{ __("maintenance.sr_attachment_add") }}',
    selectFile:        '{{ __("maintenance.js_select_file") }}',
    uploading:         '{{ __("maintenance.js_uploading") }}',
    upload:            '{{ __("maintenance.btn_upload") }}',
    uploadFail:        '{{ __("maintenance.js_upload_fail") }}',
    enterUrl:          '{{ __("maintenance.js_enter_url") }}',
    enterDisplayName:  '{{ __("maintenance.js_enter_display_name") }}',
    regFail:           '{{ __("maintenance.js_reg_fail") }}',
    sharing:           '{{ __("maintenance.btn_sharing") }}',
    share:             '{{ __("maintenance.btn_share") }}',
    shareDisableConfirm: '{{ __("maintenance.js_share_disable_confirm") }}',
    copied:            '{{ __("maintenance.js_copy_copied") }}',
    catDeleteConfirm:  '{{ __("maintenance.cat_delete_confirm") }}',
    catChangeFail:     '{{ __("maintenance.js_save_fail") }}',
    catAddFail:        '{{ __("maintenance.js_save_fail") }}',
    uncategorized:     '{{ __("maintenance.cat_uncategorized") }}',
    enterUrlName:      '{{ __("maintenance.js_enter_url_name") }}',
    fileEmpty:         '{{ __("maintenance.file_empty") }}',
    monthNames:        {!! json_encode([__('maintenance.month_1'), __('maintenance.month_2'), __('maintenance.month_3'), __('maintenance.month_4'), __('maintenance.month_5'), __('maintenance.month_6'), __('maintenance.month_7'), __('maintenance.month_8'), __('maintenance.month_9'), __('maintenance.month_10'), __('maintenance.month_11'), __('maintenance.month_12')]) !!},
    yearSuffix:        '{{ __("maintenance.gantt_year_suffix") }}',
};

/* ══════════════════════════════════════════════
   SR 간트 (프로젝트 간트와 동일한 방식)
══════════════════════════════════════════════ */
const SRG_ITEMS     = @json($ganttItems);
const SRG_HOLIDAYS  = new Set(@json($_srgHolidays));

// ── Constants ─────────────────────────────────
const SRG_ROW_H   = 36;
const SRG_BAR_H   = 22;
const SRG_BAR_PAD = (SRG_ROW_H - SRG_BAR_H) / 2;
const SRG_DW      = { day: 38, week: 18, month: 7 };

// ── State ──────────────────────────────────────
let srgView  = 'week';
let srgFlat  = [];
let srgStart, srgEnd;

const srgToday = new Date(); srgToday.setHours(0,0,0,0);

// ── Utilities ──────────────────────────────────
function srgPD(s)   { const [y,m,d] = s.split('-').map(Number); return new Date(y,m-1,d); }
function srgFD(d)   { return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; }
function srgFS(d)   { return `${d.getMonth()+1}/${d.getDate()}`; }
function srgDD(a,b) { return Math.round((b-a)/86400000); }
function srgE(s)    { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function $srg(id)   { return document.getElementById(id); }
function srgTW()    { return (srgDD(srgStart,srgEnd)+1) * SRG_DW[srgView]; }
function srgTH()    { return srgFlat.length * SRG_ROW_H; }
function srgX(d)    { return srgDD(srgStart,d) * SRG_DW[srgView]; }
function srgBW(s,e) { return Math.max(SRG_DW[srgView], (srgDD(s,e)+1) * SRG_DW[srgView]); }

// ── Build flat rows ────────────────────────────
async function srgBuild() {
    srgFlat = SRG_ITEMS.map(t => ({ type:'task', task:t }));
}

// ── Calc range ─────────────────────────────────
async function srgCalc() {
    let mn = null, mx = null;
    SRG_ITEMS.forEach(t => {
        const s = srgPD(t.start);
        const e = t.end ? srgPD(t.end) : new Date(s);
        if (!mn || s < mn) mn = new Date(s);
        if (!mx || e > mx) mx = new Date(e);
    });
    if (!mn) { mn = new Date(srgToday); mx = new Date(srgToday); }
    mn.setDate(mn.getDate() - 14);
    mx.setDate(mx.getDate() + 28);
    const dow = mn.getDay();
    mn.setDate(mn.getDate() - (dow===0 ? 6 : dow-1));
    srgStart = mn; srgEnd = mx;
}

// ── Left panel ─────────────────────────────────
async function srgRenderLeft() {
    const SB = { pending:['#fef3c7','#b45309'], in_progress:['#dbeafe','#1d4ed8'], completed:['#dcfce7','#15803d'], rejected:['#f3f4f6','#6b7280'] };
    const PB = { urgent:['#fee2e2','#dc2626'], high:['#fef3c7','#d97706'], normal:['#dbeafe','#2563eb'], low:['#f3f4f6','#6b7280'] };
    $srg('srg2-left-body').innerHTML = srgFlat.map(r => {
        const t = r.task;
        const [sb,sc] = SB[t.status] || SB.pending;
        const [pb,pc] = PB[t.priority] || PB.normal;
        const sd = t.start ? srgFS(srgPD(t.start)) : '—';
        const ed = t.end   ? srgFS(srgPD(t.end))   : '—';
        return `<div class="srg2-row" data-id="${t.id}" onclick="openDetail('${t.detail_url}');event.stopPropagation()">
            <div class="srg2-lc srg2-lc-title" title="${srgE(t.title)}">${srgE(t.title)}</div>
            <div class="srg2-lc srg2-lc-status"><span style="font-size:11px;font-weight:500;padding:2px 6px;border-radius:4px;white-space:nowrap;background:${sb};color:${sc};">${srgE(t.status_label)}</span></div>
            <div class="srg2-lc srg2-lc-prio"><span style="font-size:11px;font-weight:600;padding:2px 5px;border-radius:4px;white-space:nowrap;background:${pb};color:${pc};">${srgE(t.priority_label)}</span></div>
            <div class="srg2-lc srg2-lc-user">${srgE(t.user_name||'—')}</div>
            <div class="srg2-lc srg2-lc-start">${sd}</div>
            <div class="srg2-lc srg2-lc-end">${ed}</div>
        </div>`;
    }).join('');
}

// ── Right header ───────────────────────────────
async function srgRenderHdr() {
    const tw = srgTW();
    const MO = IDX_STR.monthNames;
    let maj = '', min = '';

    if (srgView === 'day') {
        let d = new Date(srgStart);
        while (d <= srgEnd) {
            const nm = new Date(d.getFullYear(), d.getMonth()+1, 1);
            const days = Math.min(srgDD(d,nm), srgDD(d,srgEnd)+1);
            maj += `<div class="srg2-th-major" style="width:${days*SRG_DW.day}px;">${d.getFullYear()}.${String(d.getMonth()+1).padStart(2,'0')}</div>`;
            d = nm;
        }
        let day = new Date(srgStart);
        while (day <= srgEnd) {
            const ds  = srgFD(day);
            const isT = day.toDateString() === srgToday.toDateString();
            const isH = SRG_HOLIDAYS.has(ds);
            const dow = day.getDay();
            const cls = isT ? ' today' : isH ? ' holiday' : (dow===0||dow===6) ? ' wkend' : '';
            min += `<div class="srg2-th-minor${cls}" style="width:${SRG_DW.day}px;">${day.getDate()}</div>`;
            day.setDate(day.getDate()+1);
        }
    } else if (srgView === 'week') {
        let d = new Date(srgStart);
        while (d <= srgEnd) {
            const nm = new Date(d.getFullYear(), d.getMonth()+1, 1);
            const end = nm < srgEnd ? nm : new Date(srgEnd); end.setDate(end.getDate()+1);
            maj += `<div class="srg2-th-major" style="width:${srgDD(d,end)*SRG_DW.week}px;">${d.getFullYear()}.${String(d.getMonth()+1).padStart(2,'0')}</div>`;
            d = nm;
        }
        let wk = new Date(srgStart);
        while (wk <= srgEnd) {
            const we = new Date(wk); we.setDate(we.getDate()+6);
            const days = Math.min(7, srgDD(wk,srgEnd)+1);
            const isCur = wk <= srgToday && srgToday <= we;
            min += `<div class="srg2-th-minor${isCur?' today':''}" style="width:${days*SRG_DW.week}px;">${wk.getMonth()+1}/${wk.getDate()}</div>`;
            wk.setDate(wk.getDate()+7);
        }
    } else {
        let d = new Date(srgStart.getFullYear(), srgStart.getMonth(), 1);
        let cy = -1, cyw = 0;
        while (d <= srgEnd) {
            const yr = d.getFullYear();
            if (yr !== cy) {
                if (cy !== -1) maj += `<div class="srg2-th-major" style="width:${cyw}px;">${cy}${IDX_STR.yearSuffix}</div>`;
                cy = yr; cyw = 0;
            }
            const nm = new Date(d.getFullYear(), d.getMonth()+1, 1);
            const endBound = nm < srgEnd ? nm : new Date(srgEnd.getFullYear(), srgEnd.getMonth()+1, 1);
            const days = srgDD(d, endBound);
            cyw += days * SRG_DW.month;
            const isC = d.getMonth()===srgToday.getMonth()&&d.getFullYear()===srgToday.getFullYear();
            min += `<div class="srg2-th-minor${isC?' today':''}" style="width:${days*SRG_DW.month}px;">${MO[d.getMonth()]}</div>`;
            d = nm;
        }
        if (cy !== -1) maj += `<div class="srg2-th-major" style="width:${cyw}px;">${cy}${IDX_STR.yearSuffix}</div>`;
    }

    $srg('srg2-right-hdr-inner').innerHTML =
        `<div class="srg2-th-row" style="width:${tw}px;">${maj}</div>
         <div class="srg2-th-row" style="width:${tw}px;">${min}</div>`;
}

// ── Canvas ─────────────────────────────────────
async function srgRenderCanvas() {
    const tw = srgTW(), th = srgTH();
    const canvas = $srg('srg2-canvas');
    canvas.style.width  = tw + 'px';
    canvas.style.height = th + 'px';
    const dw = SRG_DW[srgView];
    let html = '';

    // Holiday / weekend column backgrounds (day view only)
    if (srgView === 'day') {
        let d = new Date(srgStart), x = 0;
        while (d <= srgEnd) {
            const ds  = srgFD(d);
            const isH = SRG_HOLIDAYS.has(ds);
            const dow = d.getDay();
            if (isH) {
                html += `<div class="srg2-col-holiday-bg" style="left:${x}px;width:${dw}px;height:${th}px;"></div>`;
            } else if (dow === 0 || dow === 6) {
                html += `<div class="srg2-col-wkend-bg" style="left:${x}px;width:${dw}px;height:${th}px;"></div>`;
            }
            d.setDate(d.getDate()+1); x += dw;
        }
    }

    // Grid lines
    if (srgView === 'day') {
        let d = new Date(srgStart), x = 0;
        while (d <= srgEnd) {
            if (d.getDay()===1) html += `<div class="srg2-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d.setDate(d.getDate()+1); x += dw;
        }
    } else if (srgView === 'week') {
        let d = new Date(srgStart), x = 0;
        while (d <= srgEnd) {
            html += `<div class="srg2-grid-line" style="left:${x}px;height:${th}px;"></div>`;
            d.setDate(d.getDate()+7); x += dw*7;
        }
    } else {
        let d = new Date(srgStart.getFullYear(), srgStart.getMonth(), 1);
        while (d <= srgEnd) {
            html += `<div class="srg2-grid-line" style="left:${srgX(d)}px;height:${th}px;"></div>`;
            d = new Date(d.getFullYear(), d.getMonth()+1, 1);
        }
    }

    // Row backgrounds
    srgFlat.forEach((r,i) => {
        if (i%2===0) html += `<div class="srg2-row-bg even" style="top:${i*SRG_ROW_H}px;height:${SRG_ROW_H}px;width:${tw}px;"></div>`;
    });

    // Today line
    if (srgToday >= srgStart && srgToday <= srgEnd) {
        const tx = srgX(srgToday) + dw/2;
        html += `<div class="srg2-today-line" style="left:${tx}px;height:${th}px;"></div>`;
        html += `<div class="srg2-today-top" style="left:${tx}px;"></div>`;
    }

    // Bars
    srgFlat.forEach((r,i) => {
        const t  = r.task;
        const s  = srgPD(t.start);
        const e  = t.end ? srgPD(t.end) : new Date(s);
        const bx = srgX(s), bw = srgBW(s,e);
        const by = i * SRG_ROW_H + SRG_BAR_PAD;
        const resize = t.can_edit ? `<div class="srg2-bar-resize"></div>` : '';
        html += `<div class="srg2-bar s-${t.status}" data-id="${t.id}" data-url="${t.detail_url}" data-update-url="${t.update_url||''}" data-start="${t.start}" data-end="${t.end||''}" data-can-edit="${t.can_edit?'1':'0'}" title="${srgE(t.title)}" style="left:${bx}px;top:${by}px;width:${bw}px;height:${SRG_BAR_H}px;">${resize}<span class="srg2-bar-label">${srgE(t.title)}</span></div>`;
    });

    canvas.innerHTML = html;
}

// ── Bar drag (move + resize) ────────────────────
let _srgDrag = null, _srgDragMoved = false;

async function srgBindCanvasEvents() {
    const canvas = $srg('srg2-canvas');

    canvas.addEventListener('mousedown', e => {
        const bar = e.target.closest('.srg2-bar');
        if (!bar || bar.dataset.canEdit !== '1') return;
        e.preventDefault();
        _srgDragMoved = false;
        const isResize = e.target.classList.contains('srg2-bar-resize');
        const taskId   = parseInt(bar.dataset.id);
        const taskIdx  = srgFlat.findIndex(r => r.task.id === taskId);
        _srgDrag = {
            type: isResize ? 'resize' : 'move',
            barEl: bar, taskIdx,
            startX: e.clientX,
            origLeft: parseFloat(bar.style.left),
            origWidth: parseFloat(bar.style.width),
        };
        bar.classList.add('dragging');
    });

    canvas.addEventListener('click', e => {
        if (_srgDragMoved) { _srgDragMoved = false; return; }
        const b = e.target.closest('.srg2-bar');
        if (b && !e.target.classList.contains('srg2-bar-resize')) openDetail(b.dataset.url);
    });
}

// ── Scroll sync ────────────────────────────────
async function srgBindScroll() {
    const rb = $srg('srg2-right-body'), lb = $srg('srg2-left-body'), hi = $srg('srg2-right-hdr-inner');
    let sync = false;
    rb.addEventListener('scroll', () => {
        if (sync) return; sync = true;
        lb.scrollTop = rb.scrollTop;
        hi.style.transform = `translateX(-${rb.scrollLeft}px)`;
        sync = false;
    });
    lb.addEventListener('scroll', () => {
        if (sync) return; sync = true;
        rb.scrollTop = lb.scrollTop;
        sync = false;
    });
}

// ── Column resize ──────────────────────────────
const SRG_COL_KEY = 'srg_cols_sr{{ $srTarget->id }}';
const SRG_COL_DEF = { title:180, status:74, prio:68, user:70, start:62, end:74 };

async function srgLoadCols() {
    const gL = $srg('srg2-left');
    const saved = JSON.parse(localStorage.getItem(SRG_COL_KEY)||'{}');
    Object.entries(SRG_COL_DEF).forEach(([c,def]) => {
        gL.style.setProperty('--sc-'+c, (saved[c]||def)+'px');
    });
}

async function srgSaveCols() {
    const gL = $srg('srg2-left');
    const w = {};
    Object.keys(SRG_COL_DEF).forEach(c => {
        w[c] = parseInt(getComputedStyle(gL).getPropertyValue('--sc-'+c)) || SRG_COL_DEF[c];
    });
    localStorage.setItem(SRG_COL_KEY, JSON.stringify(w));
}

async function srgBindCols() {
    const gL = $srg('srg2-left');
    const minW = { title:80, status:50, prio:44, user:50, start:46, end:50 };
    let act = null;
    document.querySelectorAll('.srg2-col-resizer').forEach(h => {
        h.addEventListener('mousedown', e => {
            e.preventDefault(); e.stopPropagation();
            const col = h.dataset.col;
            const sw = parseInt(getComputedStyle(gL).getPropertyValue('--sc-'+col)) || SRG_COL_DEF[col] || 80;
            act = { col, sx: e.clientX, sw };
        });
    });
    document.addEventListener('mousemove', e => {
        if (!act) return;
        const nw = Math.max(minW[act.col]||44, act.sw + e.clientX - act.sx);
        $srg('srg2-left').style.setProperty('--sc-'+act.col, nw+'px');
    });
    document.addEventListener('mouseup', () => { if (act) { srgSaveCols(); act = null; } });
}

// ── View mode ──────────────────────────────────
async function setSRGView(mode) {
    srgView = mode;
    document.querySelectorAll('.srg2-vm-btn').forEach((b,i) => {
        b.classList.toggle('active', ['day','week','month'][i] === mode);
    });
    srgRenderHdr(); srgRenderCanvas();
    srgScrollToday();
}

async function srgGoToday() { srgScrollToday(); }

async function srgScrollToday() {
    const rb = $srg('srg2-right-body');
    if (!rb || srgToday < srgStart || srgToday > srgEnd) return;
    rb.scrollLeft = Math.max(0, srgX(srgToday) - rb.clientWidth / 2);
}

// ── setSRView (list / gantt / files toggle) ────
async function setSRView(mode) {
    $srg('sr-list-view').style.display    = mode === 'list'  ? 'block' : 'none';
    $srg('sr-gantt-view').style.display   = mode === 'gantt' ? 'block' : 'none';
    $srg('sr-files-view').style.display   = mode === 'files' ? 'block' : 'none';
    $srg('sr-status-summary').style.display = mode === 'list' ? 'grid' : 'none';

    const applyBtn = (id, active) => {
        const el = $srg(id);
        el.style.background  = active ? '#7c3aed' : '#fff';
        el.style.color       = active ? '#fff'    : '#6b7280';
        el.style.borderColor = active ? '#7c3aed' : '#e5e7eb';
    };
    applyBtn('srg-btn-list',  mode === 'list');
    applyBtn('srg-btn-gantt', mode === 'gantt');
    applyBtn('srg-btn-files', mode === 'files');

    if (mode === 'gantt' && !$srg('sr-gantt-view').dataset.init) {
        initSRGantt();
        $srg('sr-gantt-view').dataset.init = '1';
    }
    try { localStorage.setItem('sr_view', mode); } catch(e) {}
}

// ── Bar drag handlers (document level, bound once) ─
document.addEventListener('mousemove', e => {
    if (!_srgDrag) return;
    const dw = SRG_DW[srgView];
    const dx = e.clientX - _srgDrag.startX;
    if (Math.abs(dx) > 4) _srgDragMoved = true;
    const days = Math.round(dx / dw);
    if (_srgDrag.type === 'move') {
        _srgDrag.barEl.style.left = (_srgDrag.origLeft + days * dw) + 'px';
    } else {
        _srgDrag.barEl.style.width = Math.max(dw, _srgDrag.origWidth + days * dw) + 'px';
    }
});

document.addEventListener('mouseup', async e => {
    if (!_srgDrag) return;
    const drag = _srgDrag;
    _srgDrag = null;
    drag.barEl.classList.remove('dragging');

    if (!_srgDragMoved) return;

    const dw   = SRG_DW[srgView];
    const days = Math.round((e.clientX - drag.startX) / dw);
    if (days === 0) return;

    const task = srgFlat[drag.taskIdx].task;
    const s0   = srgPD(task.start);
    const e0   = task.end ? srgPD(task.end) : new Date(s0);
    let ns = new Date(s0), ne = new Date(e0);

    if (drag.type === 'move') {
        ns.setDate(ns.getDate() + days);
        ne.setDate(ne.getDate() + days);
    } else {
        ne.setDate(e0.getDate() + days);
        if (ne < ns) ne = new Date(ns);
    }

    const res = await fetch(task.update_url, {
        method: 'PATCH',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ requested_date: srgFD(ns), due_date: srgFD(ne) }),
    });
    const d = await res.json().catch(() => ({}));

    if (!res.ok || !d.ok) {
        drag.barEl.style.left  = drag.origLeft  + 'px';
        drag.barEl.style.width = drag.origWidth + 'px';
        alert(d.message || IDX_STR.scheduleFail);
        return;
    }

    task.start = d.start; task.end = d.end || null;
    drag.barEl.dataset.start = d.start;
    drag.barEl.dataset.end   = d.end || '';
    const rs = srgPD(d.start), re = d.end ? srgPD(d.end) : new Date(rs);
    drag.barEl.style.left  = srgX(rs) + 'px';
    drag.barEl.style.width = srgBW(rs, re) + 'px';

    const row = $srg('srg2-left-body').querySelectorAll('.srg2-row')[drag.taskIdx];
    if (row) {
        const sc = row.querySelector('.srg2-lc-start');
        const ec = row.querySelector('.srg2-lc-end');
        if (sc) sc.textContent = srgFS(rs);
        if (ec) ec.textContent = d.end ? srgFS(re) : '—';
    }
});

// ── Init ───────────────────────────────────────
async function initSRGantt() {
    if (!SRG_ITEMS.length) {
        $srg('srg2-empty').style.display = 'block';
        $srg('srg2-main').style.display  = 'none';
        return;
    }
    srgLoadCols();
    srgBuild();
    srgCalc();
    srgRenderLeft();
    srgRenderHdr();
    srgRenderCanvas();
    srgBindScroll();
    srgBindCols();
    srgBindCanvasEvents();
    setTimeout(srgScrollToday, 200);
}

document.addEventListener('DOMContentLoaded', () => {
    let sv = 'list';
    try { sv = localStorage.getItem('sr_view') || 'list'; } catch(e) {}
    setSRView(sv);
});
/* ══════════════════════════════════════════════ */
const PRIORITY_COLORS = { low:'#6b7280', normal:'#2563eb', high:'#d97706', urgent:'#dc2626' };
const PRIORITY_BGS    = { low:'#f3f4f6', normal:'#dbeafe', high:'#fef3c7', urgent:'#fee2e2' };

/* ── 등록 모달 ── */
let _createQuill = null;

async function updateChip() {
    document.querySelectorAll('#m-form input[name=priority]').forEach(async function(r) {
        const chip = document.getElementById('pri-chip-' + r.value);
        if (!chip) return;
        if (r.checked) {
            chip.style.borderColor = PRIORITY_COLORS[r.value];
            chip.style.background  = PRIORITY_BGS[r.value];
            chip.style.color       = PRIORITY_COLORS[r.value];
        } else {
            chip.style.borderColor = '#e4e4e7';
            chip.style.background  = 'transparent';
            chip.style.color       = '#374151';
        }
    });
}

/* ── 등록 모달 파일 첨부 ── */
let _mCreateFiles = [];
let _mUrlQueue    = [];

async function mSwitchAttachTab(tab) {
    const isFile = tab === 'file';
    document.getElementById('m-attach-panel-file').style.display = isFile ? 'block' : 'none';
    document.getElementById('m-attach-panel-url').style.display  = isFile ? 'none'  : 'block';
    document.getElementById('m-attach-tab-file').style.background = isFile ? '#7c3aed' : '#f9fafb';
    document.getElementById('m-attach-tab-file').style.color      = isFile ? '#fff' : '#6b7280';
    document.getElementById('m-attach-tab-url').style.background  = isFile ? '#f9fafb' : '#7c3aed';
    document.getElementById('m-attach-tab-url').style.color       = isFile ? '#6b7280' : '#fff';
}
async function mHandleFileDrop(files) { mHandleFileSelect(files); }
async function mHandleFileSelect(files) {
    Array.from(files).forEach(f => {
        if (_mCreateFiles.find(x => x.name === f.name && x.size === f.size)) return;
        _mCreateFiles.push(f);
    });
    mRenderFileList();
}
async function mRenderFileList() {
    const list = document.getElementById('m-file-list');
    list.innerHTML = '';
    _mCreateFiles.forEach((f, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:7px;padding:5px 9px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:7px;font-size:11px;';
        row.innerHTML = `<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#374151;">${f.name}</span>
            <span style="color:#9ca3af;flex-shrink:0;">${(f.size/1024/1024).toFixed(1)}MB</span>
            <button type="button" onclick="mRemoveFile(${i})" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>`;
        list.appendChild(row);
    });
}
async function mRemoveFile(i) { _mCreateFiles.splice(i, 1); mRenderFileList(); }

async function mAddUrl() {
    const name = document.getElementById('m-url-name').value.trim();
    const src  = document.getElementById('m-url-src').value.trim();
    if (!name || !src) return;
    _mUrlQueue.push({ name, src });
    document.getElementById('m-url-name').value = '';
    document.getElementById('m-url-src').value  = '';
    mRenderUrlList();
}
async function mRenderUrlList() {
    const list = document.getElementById('m-url-list');
    list.innerHTML = '';
    _mUrlQueue.forEach((u, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:7px;padding:5px 9px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:7px;font-size:11px;';
        row.innerHTML = `<span style="font-size:13px;">🔗</span>
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#374151;">${u.name}</span>
            <button type="button" onclick="mRemoveUrl(${i})" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>`;
        list.appendChild(row);
    });
}
async function mRemoveUrl(i) { _mUrlQueue.splice(i, 1); mRenderUrlList(); }

async function openCreateModal() {
    document.getElementById('m-form').reset();
    document.getElementById('m-error').style.display = 'none';
    _mCreateFiles = []; _mUrlQueue = [];
    document.getElementById('m-file-list').innerHTML = '';
    document.getElementById('m-url-list').innerHTML  = '';
    document.getElementById('m-file-input').value = '';
    mSwitchAttachTab('file');
    updateChip();
    if (!_createQuill) {
        _createQuill = createSrEditor('m-content-editor', 'm-content',
            '{{ __("maintenance.request_placeholder_short") }}', false, CSRF);
    } else {
        _createQuill.setText('');
    }
    document.getElementById('m-content').value = '';
    document.getElementById('m-modal').style.display = 'block';
    document.getElementById('m-overlay').style.display = 'block';
    setTimeout(() => document.getElementById('m-title').focus(), 50);
}

async function closeCreateModal() {
    document.getElementById('m-modal').style.display = 'none';
    document.getElementById('m-overlay').style.display = 'none';
}

/* 웍스 정제 — SR 등록 요청 내용 / 상세 답글 (Quill) */
window.mRefineCreateContent = function (btn) {
    if (typeof mmRefineQuill !== 'function') return;
    mmRefineQuill(_createQuill, null, btn);
};
window.dtRefineReply = function (btn) {
    if (typeof mmRefineQuill !== 'function') return;
    const form = btn.closest('[data-reply-form]');
    const t = form && form.querySelector('.sr-reply-quill-target');
    if (t && t._quill) mmRefineQuill(t._quill, null, btn);
    else alert('정제할 내용을 입력하세요.');
};

document.addEventListener('DOMContentLoaded', updateChip);

document.getElementById('m-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('m-submit');
    const errEl = document.getElementById('m-error');

    /* Quill 내용 동기화 및 검증 */
    if (_createQuill) {
        const html = _createQuill.root.innerHTML;
        document.getElementById('m-content').value = html;
        if (_createQuill.getText().trim() === '') {
            errEl.textContent = IDX_STR.enterContent;
            errEl.style.display = 'block';
            return;
        }
    }

    btn.disabled = true; btn.textContent = IDX_STR.saving;
    errEl.style.display = 'none';
    try {
        // 파일 목록을 FormData에 동기화
        const fd = new FormData(this);
        fd.delete('attachments[]');
        _mCreateFiles.forEach(f => fd.append('attachments[]', f));

        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
            // URL 큐 업로드
            if (_mUrlQueue.length > 0 && data.id) {
                const mfUrl = `${BASE_URL}/maintenances/${data.id}/files`;
                for (const u of _mUrlQueue) {
                    await fetch(mfUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ file_type: 'url', source_url: u.src, original_name: u.name }),
                    });
                }
            }
            location.href = data.redirect || location.href;
        } else {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || IDX_STR.saveFail);
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = IDX_STR.networkError;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = IDX_STR.register;
    }
});

/* ── 상세 모달 ── */
let _currentDetailUrl = null;

async function openDetail(url) {
    _currentDetailUrl = url;
    const modal   = document.getElementById('dt-modal');
    const overlay = document.getElementById('dt-overlay');
    const loading = document.getElementById('dt-loading');
    const content = document.getElementById('dt-content');

    content.innerHTML = '';
    loading.style.display = 'block';
    overlay.style.display = 'block';
    overlay.style.pointerEvents = 'none';
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => { overlay.style.pointerEvents = ''; }, 400);

    try {
        const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        loading.style.display = 'none';
        content.innerHTML = html;
        _moveFixedHeader();
        _initDetailEditors();
    } catch {
        loading.style.display = 'none';
        content.innerHTML = `<div style="padding:32px;text-align:center;color:#ef4444;font-size:13px;">${IDX_STR.loadFail}</div>`;
    }
}

async function closeDetail() {
    document.getElementById('dt-modal').classList.remove('open');
    document.getElementById('dt-overlay').style.display = 'none';
    document.getElementById('dt-fixed-header').innerHTML = '';
    document.body.style.overflow = '';
    _currentDetailUrl = null;
}

async function _moveFixedHeader() {
    const fh = document.getElementById('dt-fixed-header');
    const el = document.getElementById('dt-content').querySelector('[data-fixed-header]');
    fh.innerHTML = '';
    if (el) fh.appendChild(el);
}

async function _reloadDetail(url) {
    const loading = document.getElementById('dt-loading');
    const content = document.getElementById('dt-content');
    content.innerHTML = '';
    loading.style.display = 'block';
    const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const html = await res.text();
    loading.style.display = 'none';
    content.innerHTML = html;
    _moveFixedHeader();
    _initDetailEditors();
}

async function _initDetailEditors() {
    document.querySelectorAll('[data-reply-form] .sr-reply-quill-target').forEach(async function(target) {
        if (target._quill) return;
        const form   = target.closest('[data-reply-form]');
        const hidden = form.querySelector('.sr-reply-hidden');
        const ph     = form.closest('[data-reply-form]').querySelector('button[type=submit]')
                           ?.textContent?.includes('{{ __("maintenance.admin_label") }}') ? '{{ __("maintenance.reply_placeholder_admin") }}' : '{{ __("maintenance.reply_placeholder_user") }}';

        const q = new Quill(target, {
            theme: 'snow',
            placeholder: ph,
            modules: {
                toolbar: {
                    container: [['bold','italic','underline'],[{'list':'bullet'}],['image']],
                    handlers: {
                        image: async function() {
                            const inp = document.createElement('input');
                            inp.type = 'file'; inp.accept = 'image/*';
                            inp.onchange = async () => {
                                const url = await srUploadImage(inp.files[0], CSRF);
                                if (url) { const r = q.getSelection(true); q.insertEmbed(r.index,'image',url,'user'); q.setSelection(r.index+1); }
                            };
                            inp.click();
                        }
                    }
                },
                clipboard: {
                    matchers: [
                        ['img', async function(node, delta) {
                            return (node.src && node.src.startsWith('data:')) ? new Delta() : delta;
                        }]
                    ]
                }
            }
        });

        q.on('selection-change', rng => target.closest('.sr-reply-editor-wrap').classList.toggle('focused', !!rng));
        q.on('text-change', (delta, oldDelta, source) => {
            if (source !== 'silent') {
                const contents = q.getContents();
                const hasBase64 = contents.ops.some(op =>
                    op.insert && op.insert.image && String(op.insert.image).startsWith('data:')
                );
                if (hasBase64) {
                    const cleaned = contents.ops.filter(op =>
                        !(op.insert && op.insert.image && String(op.insert.image).startsWith('data:'))
                    );
                    q.setContents({ ops: cleaned }, 'silent');
                }
            }
            if (hidden) hidden.value = q.root.innerHTML;
        });
        q.root.addEventListener('paste', async function(e) {
            const clipData = e.clipboardData || e.originalEvent?.clipboardData;
            if (!clipData) return;
            const imageItem = Array.from(clipData.items).find(
                item => item.kind === 'file' && item.type.startsWith('image/')
            );
            if (!imageItem) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            srUploadImage(imageItem.getAsFile(), CSRF).then(url => {
                if (url) { const r = q.getSelection(true); const idx = r ? r.index : q.getLength(); q.insertEmbed(idx,'image',url,'user'); q.setSelection(idx+1); }
            });
        }, true);
        target._quill = q;
    });
}

/* 상세 모달 이벤트 위임 */
document.getElementById('dt-body').addEventListener('submit', async function(e) {
    const form = e.target.closest('[data-reply-form]');
    if (!form) return;
    e.preventDefault();

    /* Quill 내용 동기화 */
    const quillTarget = form.querySelector('.sr-reply-quill-target');
    const hidden      = form.querySelector('.sr-reply-hidden');
    if (quillTarget && quillTarget._quill) {
        const q = quillTarget._quill;
        hidden.value = q.root.innerHTML;
        if (q.getText().trim() === '') return;
    }

    const btn = form.querySelector('button[type=submit]');
    const origText = btn.textContent;
    btn.disabled = true; btn.textContent = IDX_STR.registering;

    try {
        const res = await fetch(form.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: new FormData(form),
        });
        if (res.ok) {
            await _reloadDetail(form.dataset.reloadUrl);
        } else {
            alert(IDX_STR.replyFail);
            btn.disabled = false; btn.textContent = origText;
        }
    } catch {
        alert(IDX_STR.networkError);
        btn.disabled = false; btn.textContent = origText;
    }
});

document.getElementById('dt-modal').addEventListener('click', async function(e) {
    /* 삭제 버튼 */
    const delBtn = e.target.closest('[data-delete-btn]');
    if (delBtn) {
        if (!await __confirm(IDX_STR.confirmDeleteSr)) return;
        const id  = delBtn.dataset.id;
        const url = delBtn.dataset.url;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_method=DELETE',
            });
            if (res.ok) {
                closeDetail();
                const row = document.querySelector(`[data-item-id="${id}"]`);
                if (row) row.remove();
            } else {
                alert(IDX_STR.deleteFail);
            }
        } catch {
            alert(IDX_STR.networkError);
        }
        return;
    }

    /* 수정 버튼 */
    const editBtn = e.target.closest('[data-edit-btn]');
    if (editBtn) {
        openEditModal(editBtn);
    }
});

/* SR 목록 행 삭제 (관리자·연결 프로젝트 매니저) */
async function srRowDelete(btn) {
    if (!await __confirm(IDX_STR.confirmDeleteSr)) return;
    const id  = btn.dataset.id;
    const url = btn.dataset.url;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_method=DELETE',
        });
        if (res.ok) {
            const row = document.querySelector(`[data-item-id="${id}"]`);
            if (row) row.remove();
        } else {
            alert(IDX_STR.deleteFail);
        }
    } catch {
        alert(IDX_STR.networkError);
    }
}

document.addEventListener('keydown', async function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('edit-modal').style.display === 'block') {
            closeEditModal();
        } else if (document.getElementById('dt-modal').classList.contains('open')) {
            closeDetail();
        } else {
            closeCreateModal();
        }
    }
});

/* ── 수정 모달 ── */
let _editQuill      = null;
let _editReloadUrl  = null;
let _editItemId     = null;

async function updateEditChip() {
    document.querySelectorAll('#edit-form input[name=priority]').forEach(async function(r) {
        const chip = document.getElementById('edit-pri-chip-' + r.value);
        if (!chip) return;
        if (r.checked) {
            chip.style.borderColor = PRIORITY_COLORS[r.value];
            chip.style.background  = PRIORITY_BGS[r.value];
            chip.style.color       = PRIORITY_COLORS[r.value];
        } else {
            chip.style.borderColor = '#e4e4e7';
            chip.style.background  = 'transparent';
            chip.style.color       = '#374151';
        }
    });
}

async function openEditModal(btn) {
    const d        = btn.dataset;
    _editReloadUrl = d.reloadUrl;
    _editItemId    = d.id;
    document.getElementById('edit-form').dataset.updateUrl = d.updateUrl;

    document.getElementById('edit-title').value          = d.title          || '';
    document.getElementById('edit-requested-date').value = d.requestedDate  || '';
    document.getElementById('edit-due-date').value       = d.dueDate        || '';

    document.querySelectorAll('#edit-form input[name=priority]').forEach(async function(r) {
        r.checked = (r.value === d.priority);
    });
    updateEditChip();

    if (!_editQuill) {
        _editQuill = createSrEditor('edit-content-editor', 'edit-content',
            '{{ __("maintenance.request_placeholder_short") }}', false, CSRF);
    }
    /* 기존 내용 설정 */
    _editQuill.root.innerHTML = d.content || '';
    document.getElementById('edit-content').value = _editQuill.root.innerHTML;

    document.getElementById('edit-error').style.display = 'none';
    document.getElementById('edit-modal').style.display  = 'block';
    document.getElementById('edit-overlay').style.display = 'block';
    setTimeout(async function() { document.getElementById('edit-title').focus(); }, 50);
}

async function closeEditModal() {
    document.getElementById('edit-modal').style.display  = 'none';
    document.getElementById('edit-overlay').style.display = 'none';
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = document.getElementById('edit-submit');
    const errEl  = document.getElementById('edit-error');
    const action = this.dataset.updateUrl;

    if (_editQuill) {
        document.getElementById('edit-content').value = _editQuill.root.innerHTML;
        const hasContent = _editQuill.root.innerText.trim() !== ''
                        || _editQuill.root.querySelector('img') !== null;
        if (!hasContent) {
            errEl.textContent = IDX_STR.enterContent;
            errEl.style.display = 'block';
            return;
        }
    }

    btn.disabled = true; btn.textContent = IDX_STR.saving;
    errEl.style.display = 'none';

    try {
        const res  = await fetch(action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: new FormData(this),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
            /* 목록 행 제목 업데이트 */
            if (data.title && _editItemId) {
                const row = document.querySelector(`[data-item-id="${_editItemId}"]`);
                if (row) {
                    const titleEl = row.querySelector('td:first-child div');
                    if (titleEl) {
                        const dot = titleEl.querySelector('span');
                        titleEl.textContent = data.title;
                        if (dot) titleEl.prepend(dot);
                    }
                }
            }
            /* 간트 아이템 업데이트 */
            if (_editItemId) {
                const idx = SRG_ITEMS.findIndex(t => t.id == _editItemId);
                if (idx !== -1) {
                    if (data.title)          SRG_ITEMS[idx].title          = data.title;
                    if (data.priority)       SRG_ITEMS[idx].priority       = data.priority;
                    if (data.priority_label) SRG_ITEMS[idx].priority_label = data.priority_label;
                    if (data.priority_color) SRG_ITEMS[idx].priority_color = data.priority_color;
                    if (data.start)          SRG_ITEMS[idx].start          = data.start;
                    SRG_ITEMS[idx].end = data.end ?? null;
                    /* 현재 간트 뷰면 즉시 재렌더 */
                    const ganttView = document.getElementById('sr-gantt-view');
                    if (ganttView && ganttView.style.display !== 'none') {
                        srgBuild(); srgCalc(); srgRenderLeft(); srgRenderHdr(); srgRenderCanvas();
                    }
                }
            }
            closeEditModal();
            await _reloadDetail(_editReloadUrl);
        } else {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || IDX_STR.saveFail);
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = IDX_STR.networkError;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = IDX_STR.save;
    }
});

/* ══════════════════════════════════════════════
   SR 첨부파일 (dt-modal 내 파일 관련 기능)
══════════════════════════════════════════════ */
let _dtMfMaintenanceId = null;
let _dtMfSelectedFile  = null;

async function dtOpenUpload(maintenanceId) {
    _dtMfMaintenanceId = maintenanceId;
    _dtMfSelectedFile  = null;
    document.getElementById('dt-mf-file-input').value = '';
    document.getElementById('dt-mf-file-preview').style.display = 'none';
    document.getElementById('dt-mf-file-desc').value = '';
    document.getElementById('dt-mf-url-src').value = '';
    document.getElementById('dt-mf-url-name').value = '';
    document.getElementById('dt-mf-file-err').style.display = 'none';
    document.getElementById('dt-mf-url-err').style.display  = 'none';
    document.querySelector('#dt-mf-modal h3').textContent = IDX_STR.srAttachAdd;
    dtSwitchMfTab('file');
    document.getElementById('dt-mf-modal').style.display   = 'block';
    document.getElementById('dt-mf-overlay').style.display = 'block';
}
async function dtCloseMfUpload() {
    document.getElementById('dt-mf-modal').style.display   = 'none';
    document.getElementById('dt-mf-overlay').style.display = 'none';
}
async function dtSwitchMfTab(tab) {
    const isFile = tab === 'file';
    document.getElementById('dt-mf-panel-file').style.display = isFile ? 'block' : 'none';
    document.getElementById('dt-mf-panel-url').style.display  = isFile ? 'none'  : 'block';
    document.getElementById('dt-mf-tab-file').style.borderBottomColor = isFile ? '#7c3aed' : 'transparent';
    document.getElementById('dt-mf-tab-file').style.color = isFile ? '#7c3aed' : '#9ca3af';
    document.getElementById('dt-mf-tab-url').style.borderBottomColor = isFile ? 'transparent' : '#7c3aed';
    document.getElementById('dt-mf-tab-url').style.color = isFile ? '#9ca3af' : '#7c3aed';
}
async function dtMfHandleDrop(e) { const f = e.dataTransfer.files[0]; if (f) dtMfHandleFile(f); }
async function dtMfHandleFile(file) {
    if (!file) return;
    _dtMfSelectedFile = file;
    document.getElementById('dt-mf-file-name').textContent = file.name;
    document.getElementById('dt-mf-file-preview').style.display = 'flex';
}
async function dtMfClearFile() {
    _dtMfSelectedFile = null;
    document.getElementById('dt-mf-file-input').value = '';
    document.getElementById('dt-mf-file-preview').style.display = 'none';
}
async function dtMfSubmitFile() {
    const errEl = document.getElementById('dt-mf-file-err');
    errEl.style.display = 'none';
    if (!_dtMfSelectedFile) { errEl.textContent = IDX_STR.selectFile; errEl.style.display = 'block'; return; }
    const btn = document.getElementById('dt-mf-file-btn');
    btn.disabled = true; btn.textContent = IDX_STR.uploading;
    const fd = new FormData();
    fd.append('file', _dtMfSelectedFile);
    fd.append('description', document.getElementById('dt-mf-file-desc').value);
    try {
        const url = `${BASE_URL}/maintenances/${_dtMfMaintenanceId}/files`;
        const res = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN}, body:fd });
        const d   = await res.json().catch(()=>({}));
        if (res.ok && d.ok) { dtCloseMfUpload(); openDetail(_currentDetailUrl); }
        else { errEl.textContent = d.message || IDX_STR.uploadFail; errEl.style.display = 'block'; }
    } catch { errEl.textContent = IDX_STR.networkError; errEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = IDX_STR.upload; }
}
async function dtMfSubmitUrl() {
    const errEl = document.getElementById('dt-mf-url-err');
    errEl.style.display = 'none';
    const src  = document.getElementById('dt-mf-url-src').value.trim();
    const name = document.getElementById('dt-mf-url-name').value.trim();
    if (!src)  { errEl.textContent = IDX_STR.enterUrl;         errEl.style.display = 'block'; return; }
    if (!name) { errEl.textContent = IDX_STR.enterDisplayName; errEl.style.display = 'block'; return; }
    const btn = document.getElementById('dt-mf-url-btn');
    btn.disabled = true; btn.textContent = IDX_STR.registering;
    try {
        const url = `${BASE_URL}/maintenances/${_dtMfMaintenanceId}/files`;
        const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},
            body: JSON.stringify({ file_type:'url', source_url:src, original_name:name }) });
        const d = await res.json().catch(()=>({}));
        if (res.ok && d.ok) { dtCloseMfUpload(); openDetail(_currentDetailUrl); }
        else { errEl.textContent = d.message || IDX_STR.regFail; errEl.style.display = 'block'; }
    } catch { errEl.textContent = IDX_STR.networkError; errEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = IDX_STR.register; }
}
async function dtDeleteFile(fileId, maintenanceId) {
    if (!await __confirm(IDX_STR.confirmDeleteFile)) return;
    const url = `${BASE_URL}/maintenances/${maintenanceId}/files/${fileId}`;
    const res = await fetch(url, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (res.ok && d.ok) {
        const row = document.getElementById('dt-mf-' + fileId);
        if (row) row.remove();
    } else { alert(d.message || IDX_STR.deleteFail); }
}
let _dtShareFileId = null, _dtShareMaintenanceId = null, _dtShareBtn = null;

async function dtToggleShare(fileId, maintenanceId, btn) {
    const active = btn.dataset.active === '1';
    if (active) {
        dtOpenSharePopup(btn.dataset.shareUrl, fileId, maintenanceId, btn);
        return;
    }
    const url = `${BASE_URL}/maintenances/${maintenanceId}/files/${fileId}/share`;
    const res = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.saveFail); return; }
    btn.dataset.active = '1'; btn.dataset.shareUrl = d.url;
    btn.textContent = IDX_STR.sharing; btn.style.background='#dcfce7'; btn.style.color='#16a34a'; btn.style.borderColor='#bbf7d0';
    dtOpenSharePopup(d.url, fileId, maintenanceId, btn);
}
async function dtOpenSharePopup(url, fileId, maintenanceId, btn) {
    _dtShareFileId = fileId; _dtShareMaintenanceId = maintenanceId; _dtShareBtn = btn;
    document.getElementById('dt-share-url-input').value = url;
    document.getElementById('dt-share-popup').style.display  = 'block';
    document.getElementById('dt-share-overlay').style.display = 'block';
}
async function dtCloseSharePopup() {
    document.getElementById('dt-share-popup').style.display  = 'none';
    document.getElementById('dt-share-overlay').style.display = 'none';
}
async function dtDisableShareFromPopup() {
    if (!_dtShareFileId || !await __confirm(IDX_STR.shareDisableConfirm)) return;
    const url = _srFileUrl(_dtShareMaintenanceId, _dtShareFileId, '/share');
    const res = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.saveFail); return; }
    if (_dtShareBtn) {
        _dtShareBtn.dataset.active = '0'; _dtShareBtn.dataset.shareUrl = '';
        _dtShareBtn.textContent = IDX_STR.share; _dtShareBtn.style.background='#f9fafb'; _dtShareBtn.style.color='#6b7280'; _dtShareBtn.style.borderColor='#e5e7eb';
    }
    dtCloseSharePopup();
}
async function dtCopyShareUrl() {
    const inp = document.getElementById('dt-share-url-input');
    inp.select();
    navigator.clipboard?.writeText(inp.value).catch(()=>document.execCommand('copy'));
    const btn = document.querySelector('#dt-share-popup button[onclick="dtCopyShareUrl()"]');
    if (btn) { const t=btn.textContent; btn.textContent=IDX_STR.copied; setTimeout(()=>btn.textContent=t, 1500); }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { dtCloseMfUpload(); dtCloseSharePopup(); }
});

/* ══════════════════════════════════════════════
   SR 파일 뷰 (sr-files-view)
══════════════════════════════════════════════ */
const SR_MF_BASE      = '{{ url('maintenances') }}';
const SR_PMF_BASE     = '{{ url('sr-targets/' . $srTarget->id . '/maintenance-files') }}';

async function _srFileUrl(maintenanceId, fileId, suffix = '') {
    return maintenanceId
        ? `${SR_MF_BASE}/${maintenanceId}/files/${fileId}${suffix}`
        : `${SR_PMF_BASE}/${fileId}${suffix}`;
}

async function srPfDelete(fileId, maintenanceId) {
    if (!await __confirm(IDX_STR.confirmDeleteFile)) return;
    const url = _srFileUrl(maintenanceId, fileId);
    const res = await fetch(url, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (res.ok && d.ok) {
        const row = document.getElementById('sr-pf-' + fileId);
        if (row) row.remove();
        const cnt = document.getElementById('sr-pf-count');
        if (cnt) cnt.textContent = Math.max(0, parseInt(cnt.textContent) - 1);
        const bcnt = document.getElementById('srg-btn-files-cnt');
        if (bcnt) bcnt.textContent = Math.max(0, parseInt(bcnt.textContent) - 1);
        const list = document.getElementById('sr-pf-list');
        if (list && !list.querySelector('[id^="sr-pf-"]')) {
            list.innerHTML = `<div style="text-align:center;color:#9ca3af;font-size:13px;padding:48px 0;">${IDX_STR.fileEmpty}</div>`;
        }
    } else { alert(d.message || IDX_STR.deleteFail); }
}

async function srPfToggleShare(fileId, maintenanceId, btn) {
    const active = btn.dataset.active === '1';
    if (active) {
        dtOpenSharePopup(btn.dataset.shareUrl, fileId, maintenanceId, btn);
        return;
    }
    const url = _srFileUrl(maintenanceId, fileId, '/share');
    const res = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.saveFail); return; }
    btn.dataset.active = '1'; btn.dataset.shareUrl = d.url;
    btn.textContent = IDX_STR.sharing; btn.style.background='#dcfce7'; btn.style.color='#16a34a'; btn.style.borderColor='#bbf7d0';
    dtOpenSharePopup(d.url, fileId, maintenanceId, btn);
}

/* ── SR 파일 카테고리 ── */
const SR_PF_CAT_STORE_URL   = '{{ route('sr-targets.maintenance-file-categories.store', $srTarget) }}';
const SR_PF_CAT_DESTROY_BASE = '{{ url('sr-targets/' . $srTarget->id . '/maintenance-file-categories') }}';

let _srPfActiveCat = 'all';

/* 카테고리 필터 */
async function srPfSetCategory(catId) {
    _srPfActiveCat = catId;

    /* 버튼 스타일 */
    const btnAll  = document.getElementById('sr-pf-cat-all');
    const btnNone = document.getElementById('sr-pf-cat-none');
    const active  = 'background:#7c3aed;color:#fff;';
    const normal  = 'background:#f3f4f6;color:#374151;';

    if (btnAll)  btnAll.style.cssText  = btnAll.style.cssText.replace(/background:[^;]+;color:[^;]+;/, catId === 'all'  ? active : normal);
    if (btnNone) btnNone.style.cssText = btnNone.style.cssText.replace(/background:[^;]+;color:[^;]+;/, catId === 'none' ? active : normal);

    document.querySelectorAll('#sr-pf-cat-list button[data-cat-id]').forEach(btn => {
        const isActive = String(btn.dataset.catId) === String(catId);
        btn.style.background = isActive ? '#ede9fe' : '#f9fafb';
        btn.style.color      = isActive ? '#6d28d9' : '#374151';
        btn.style.fontWeight = isActive ? '700' : '600';
    });

    /* 파일 행 필터 */
    document.querySelectorAll('#sr-pf-list [id^="sr-pf-"]').forEach(row => {
        if (catId === 'all') {
            row.style.display = 'flex';
        } else if (catId === 'none') {
            row.style.display = row.dataset.cat === 'none' ? 'flex' : 'none';
        } else {
            row.style.display = String(row.dataset.cat) === String(catId) ? 'flex' : 'none';
        }
    });
}

/* 카테고리 드롭다운 토글 */
let _srPfOpenDrop = null;
async function srPfToggleCatDrop(fileId) {
    const drop = document.getElementById('sr-pf-cat-drop-' + fileId);
    if (!drop) return;
    if (_srPfOpenDrop && _srPfOpenDrop !== drop) _srPfOpenDrop.style.display = 'none';
    const isOpen = drop.style.display !== 'none';
    drop.style.display = isOpen ? 'none' : 'block';
    _srPfOpenDrop = isOpen ? null : drop;
}
document.addEventListener('click', e => {
    if (_srPfOpenDrop && !e.target.closest('[id^="sr-pf-cat-drop-"]') && !e.target.closest('[id^="sr-pf-cat-badge-"]')) {
        _srPfOpenDrop.style.display = 'none';
        _srPfOpenDrop = null;
    }
});

/* 카테고리 배지 업데이트 */
async function _srPfUpdateBadge(fileId, catId, catName, catColor) {
    const badge = document.getElementById('sr-pf-cat-badge-' + fileId);
    if (!badge) return;
    if (catId) {
        badge.innerHTML = `<span style="width:6px;height:6px;border-radius:50%;background:${catColor};flex-shrink:0;display:inline-block;"></span>${catName}<svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>`;
        badge.style.borderColor = '#ddd6fe'; badge.style.background = '#f5f3ff'; badge.style.color = '#6d28d9';
    } else {
        badge.innerHTML = `${IDX_STR.uncategorized}<svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>`;
        badge.style.borderColor = '#e5e7eb'; badge.style.background = '#f9fafb'; badge.style.color = '#9ca3af';
    }
    const row = document.getElementById('sr-pf-' + fileId);
    if (row) row.dataset.cat = catId ?? 'none';
}

/* 사이드바 카테고리 카운트 갱신 */
async function _srPfRefreshCatCounts() {
    const rows = document.querySelectorAll('#sr-pf-list [data-cat]');
    const counts = {};
    rows.forEach(r => {
        const c = r.dataset.cat || 'none';
        counts[c] = (counts[c] || 0) + 1;
    });
    const noneBtn = document.getElementById('sr-pf-cat-none');
    if (noneBtn) {
        const sp = noneBtn.querySelector('span:last-child');
        if (sp) sp.textContent = counts['none'] || 0;
    }
    document.querySelectorAll('#sr-pf-cat-list button[data-cat-id]').forEach(btn => {
        const sp = btn.querySelector('span:last-child');
        if (sp) sp.textContent = counts[btn.dataset.catId] || 0;
    });
}

/* 파일에 카테고리 배정 */
async function srPfAssignCategory(fileId, catId) {
    if (_srPfOpenDrop) { _srPfOpenDrop.style.display = 'none'; _srPfOpenDrop = null; }

    const row = document.getElementById('sr-pf-' + fileId);
    const rawMid = row ? row.dataset.maintenance : null;
    const maintenanceId = (rawMid && rawMid !== 'null' && rawMid !== '') ? rawMid : null;

    const url = maintenanceId
        ? `${SR_MF_BASE}/${maintenanceId}/files/${fileId}/category`
        : `${SR_PMF_BASE}/${fileId}/category`;
    const res = await fetch(url, {
        method: 'PATCH',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ maintenance_category_id: catId }),
    });
    const d = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.catChangeFail); return; }
    _srPfUpdateBadge(fileId, catId, d.name, d.color);
    _srPfRefreshCatCounts();
}

/* 카테고리 추가 폼 토글 */
async function srPfToggleCatForm() {
    const form = document.getElementById('sr-pf-cat-form');
    const isOpen = form.style.display !== 'none';
    form.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) setTimeout(() => document.getElementById('sr-pf-cat-name').focus(), 50);
}

/* 카테고리 추가 */
async function srPfAddCategory() {
    const name  = document.getElementById('sr-pf-cat-name').value.trim();
    const color = document.getElementById('sr-pf-cat-color').value;
    if (!name) { document.getElementById('sr-pf-cat-name').focus(); return; }
    const res = await fetch(SR_PF_CAT_STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ name, color }),
    });
    const d = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.catAddFail); return; }

    const cat = d.category;
    /* 사이드바에 추가 */
    const list = document.getElementById('sr-pf-cat-list');
    const item = document.createElement('div');
    item.style.cssText = 'display:flex;align-items:center;gap:2px;';
    item.innerHTML = `
        <button onclick="srPfSetCategory(${cat.id})" id="sr-pf-cat-${cat.id}" data-cat-id="${cat.id}"
                style="flex:1;display:flex;align-items:center;gap:7px;padding:7px 10px;border-radius:9px;border:none;cursor:pointer;font-size:12px;font-weight:600;text-align:left;background:#f9fafb;color:#374151;transition:all .12s;min-width:0;">
            <span style="width:8px;height:8px;border-radius:50%;background:${cat.color};flex-shrink:0;display:inline-block;"></span>
            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${cat.name}</span>
            <span style="font-size:10px;background:#e5e7eb;color:#6b7280;padding:1px 6px;border-radius:8px;flex-shrink:0;">0</span>
        </button>
        <button onclick="srPfDeleteCategory(${cat.id}, this)"
                style="width:20px;height:20px;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;line-height:1;padding:0;flex-shrink:0;border-radius:4px;transition:color .12s;"
                onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'" title="{{ __('common.delete') }}">×</button>`;
    list.appendChild(item);

    /* 모든 파일의 카테고리 드롭다운에도 옵션 추가 */
    document.querySelectorAll('[id^="sr-pf-cat-drop-"]').forEach(drop => {
        const btn = document.createElement('button');
        const fileId = drop.id.replace('sr-pf-cat-drop-', '');
        btn.onclick = () => srPfAssignCategory(fileId, cat.id);
        btn.style.cssText = 'display:flex;align-items:center;gap:7px;width:100%;padding:6px 10px;background:none;border:none;border-radius:7px;font-size:12px;cursor:pointer;color:#374151;transition:background .1s;';
        btn.onmouseover = () => btn.style.background = '#f9fafb';
        btn.onmouseout  = () => btn.style.background = 'none';
        btn.innerHTML = `<span style="width:8px;height:8px;border-radius:50%;background:${cat.color};flex-shrink:0;display:inline-block;"></span>${cat.name}`;
        drop.appendChild(btn);
    });

    document.getElementById('sr-pf-cat-name').value = '';
    document.getElementById('sr-pf-cat-color').value = '#7c3aed';
    srPfToggleCatForm();
}

/* 카테고리 삭제 */
async function srPfDeleteCategory(catId, btn) {
    if (!await __confirm(IDX_STR.catDeleteConfirm)) return;
    const url = `${SR_PF_CAT_DESTROY_BASE}/${catId}`;
    const res = await fetch(url, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF_TOKEN} });
    const d   = await res.json().catch(()=>({}));
    if (!res.ok || !d.ok) { alert(d.message || IDX_STR.deleteFail); return; }

    btn.closest('div').remove();

    /* 해당 카테고리 파일들의 배지를 미분류로 */
    document.querySelectorAll(`#sr-pf-list [data-cat="${catId}"]`).forEach(row => {
        const fileId = row.id.replace('sr-pf-', '');
        _srPfUpdateBadge(fileId, null, null, null);
    });
    _srPfRefreshCatCounts();

    /* 드롭다운에서도 제거 */
    document.querySelectorAll(`[id^="sr-pf-cat-drop-"]`).forEach(drop => {
        drop.querySelectorAll('button').forEach(b => {
            if (b.onclick && b.onclick.toString().includes(`, ${catId})`)) b.remove();
        });
    });

    if (_srPfActiveCat == catId) srPfSetCategory('all');
}

/* ══════════════════════════════════════════════
   인덱스 파일 업로드 모달
══════════════════════════════════════════════ */
let _idxFiles = [];
let _idxTab   = 'file';

async function openIdxUploadModal() {
    _idxFiles = [];
    _idxTab   = 'file';
    document.getElementById('idx-file-input').value = '';
    document.getElementById('idx-file-list').innerHTML = '';
    document.getElementById('idx-url-name').value = '';
    document.getElementById('idx-url-src').value  = '';
    document.getElementById('idx-upload-maint-id').value = '';
    const catEl = document.getElementById('idx-upload-cat-id');
    if (catEl) catEl.value = '';
    document.getElementById('idx-upload-err').style.display = 'none';
    idxSwitchTab('file');
    document.getElementById('idx-upload-overlay').style.display = 'block';
    document.getElementById('idx-upload-modal').style.display   = 'block';
}
async function closeIdxUploadModal() {
    document.getElementById('idx-upload-overlay').style.display = 'none';
    document.getElementById('idx-upload-modal').style.display   = 'none';
}

async function idxSwitchTab(tab) {
    _idxTab = tab;
    const isFile = tab === 'file';
    document.getElementById('idx-panel-file').style.display = isFile ? 'block' : 'none';
    document.getElementById('idx-panel-url').style.display  = isFile ? 'none'  : 'block';
    document.getElementById('idx-tab-file').style.color            = isFile ? '#7c3aed' : '#9ca3af';
    document.getElementById('idx-tab-file').style.borderBottomColor= isFile ? '#7c3aed' : 'transparent';
    document.getElementById('idx-tab-url').style.color             = isFile ? '#9ca3af' : '#7c3aed';
    document.getElementById('idx-tab-url').style.borderBottomColor = isFile ? 'transparent' : '#7c3aed';
}

async function idxHandleDrop(files) { idxHandleFileSelect(files); }
async function idxHandleFileSelect(files) {
    Array.from(files).forEach(f => {
        if (_idxFiles.find(x => x.name === f.name && x.size === f.size)) return;
        _idxFiles.push(f);
    });
    idxRenderFileList();
}
async function idxRenderFileList() {
    const list = document.getElementById('idx-file-list');
    list.innerHTML = '';
    _idxFiles.forEach((f, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:7px;padding:5px 9px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:7px;font-size:11px;';
        row.innerHTML = `<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#374151;">${f.name}</span>
            <span style="color:#9ca3af;flex-shrink:0;">${(f.size/1024/1024).toFixed(1)}MB</span>
            <button type="button" onclick="idxRemoveFile(${i})" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>`;
        list.appendChild(row);
    });
}
async function idxRemoveFile(i) { _idxFiles.splice(i, 1); idxRenderFileList(); }

async function idxDoUpload() {
    const errEl  = document.getElementById('idx-upload-err');
    const btn    = document.getElementById('idx-upload-btn');
    const sel    = document.getElementById('idx-upload-maint-id');
    const catId  = document.getElementById('idx-upload-cat-id')?.value || '';
    errEl.style.display = 'none';

    const maintId = sel.value;
    const mfUrl = maintId
        ? `${SR_MF_BASE}/${maintId}/files`
        : SR_PMF_BASE;
    btn.disabled = true; btn.textContent = IDX_STR.uploading;

    try {
        if (_idxTab === 'file') {
            if (!_idxFiles.length) { errEl.textContent = IDX_STR.selectFile; errEl.style.display = 'block'; btn.disabled = false; btn.textContent = IDX_STR.upload; return; }
            for (const f of _idxFiles) {
                const fd = new FormData();
                fd.append('file', f);
                if (catId) fd.append('maintenance_category_id', catId);
                const res = await fetch(mfUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: fd });
                const d   = await res.json().catch(() => ({}));
                if (!res.ok || !d.ok) { console.error('[idxDoUpload] HTTP', res.status, d); errEl.textContent = (d.message || d.error || IDX_STR.uploadFail) + (res.status !== 200 ? ' (HTTP ' + res.status + ')' : ''); errEl.style.display = 'block'; return; }
            }
        } else {
            const name = document.getElementById('idx-url-name').value.trim();
            const src  = document.getElementById('idx-url-src').value.trim();
            if (!name || !src) { errEl.textContent = IDX_STR.enterUrlName; errEl.style.display = 'block'; btn.disabled = false; btn.textContent = IDX_STR.upload; return; }
            const res = await fetch(mfUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ file_type: 'url', source_url: src, original_name: name }),
            });
            const d = await res.json().catch(() => ({}));
            if (!res.ok || !d.ok) { console.error('[idxDoUpload] HTTP', res.status, d); errEl.textContent = (d.message || d.error || IDX_STR.regFail) + (res.status !== 200 ? ' (HTTP ' + res.status + ')' : ''); errEl.style.display = 'block'; return; }
        }
        closeIdxUploadModal();
        try { localStorage.setItem('sr_view', 'files'); } catch(e) {}
        location.reload();
    } catch(e) { console.error('[idxDoUpload]', e); errEl.textContent = IDX_STR.networkError; errEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = IDX_STR.upload; }
}
</script>
@endsection

@section('modals')
{{-- ───── 공유 링크 팝업 (main 바깥에 렌더링 — position:fixed 클리핑 방지) ───── --}}
<div id="dt-share-overlay" onclick="dtCloseSharePopup()" style="display:none;position:fixed;inset:0;z-index:10350;background:rgba(0,0,0,.3);"></div>
<div id="dt-share-popup" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10351;background:#fff;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.2);padding:20px 22px;width:420px;max-width:calc(100vw - 32px);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <span style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.share_title') }}</span>
        <button onclick="dtCloseSharePopup()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:0;">&times;</button>
    </div>
    <div style="display:flex;gap:6px;">
        <input id="dt-share-url-input" type="text" readonly style="flex:1;padding:8px 10px;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;color:#374151;background:#faf5ff;outline:none;">
        <button onclick="dtCopyShareUrl()" style="padding:8px 14px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">{{ __('common.copy') }}</button>
    </div>
    <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">{{ __('maintenance.share_hint') }}</p>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6;">
        <button onclick="dtDisableShareFromPopup()"
                style="width:100%;padding:8px 0;background:#fff;color:#ef4444;border:1.5px solid #fecaca;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
            {{ __('maintenance.share_disable') }}
        </button>
    </div>
</div>
@endsection
