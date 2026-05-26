@extends('layouts.app')

@section('title', $project->name . ' - ' . __('team.files_title_suffix'))

@section('header-actions')@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'files'])
<div style="padding-top:16px;display:flex;gap:16px;align-items:flex-start;">

    {{-- ── 카테고리 사이드바 ── --}}
    <style>
        #cat-list .cat-item-view::before {
            content:'\22EE\22EE'; letter-spacing:-3px; color:#cbd5e1; font-size:12px;
            line-height:1; flex-shrink:0; padding:0 3px 0 1px; cursor:grab; user-select:none;
        }
        #cat-list .cat-item[draggable="true"] { cursor:grab; }
        #cat-list .cat-item.cat-dragging { opacity:.4; }
    </style>
    <div id="cat-sidebar" style="width:200px;flex-shrink:0;display:flex;flex-direction:column;gap:8px;">

        {{-- 전체 --}}
        <a href="{{ route('projects.files.index', $project) }}"
           class="cat-filter-btn {{ !$categoryId ? 'active' : '' }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            <span>{{ __('common.all') }}</span>
            <span class="cat-count">{{ $totalCount }}</span>
        </a>

        {{-- 카테고리 없음 --}}
        <a href="{{ route('projects.files.index', $project) }}?category=none"
           class="cat-filter-btn {{ $categoryId === 'none' ? 'active' : '' }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <span>{{ __('team.uncategorized') }}</span>
            <span class="cat-count">{{ $uncategorizedCount }}</span>
        </a>

        {{-- 카테고리 목록 --}}
        <div id="cat-list" style="display:flex;flex-direction:column;gap:4px;">
            @foreach($categories as $cat)
            <div class="cat-item" data-id="{{ $cat->id }}">
                {{-- 일반 표시 --}}
                <div class="cat-item-view" style="display:flex;align-items:center;width:100%;gap:4px;">
                    <a href="{{ route('projects.files.index', $project) }}?category={{ $cat->id }}"
                       class="cat-filter-btn {{ $categoryId == $cat->id ? 'active' : '' }}"
                       style="flex:1;min-width:0;">
                        <span class="cat-dot" style="background:{{ $cat->color }};"></span>
                        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cat->name }}</span>
                        <span class="cat-count">{{ $cat->files_count }}</span>
                    </a>
                    <button class="cat-edit-btn" onclick="startEditCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')" title="{{ __('common.edit') }}">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="cat-delete-btn" onclick="deleteCategory({{ $cat->id }}, this)" title="{{ __('common.delete') }}">×</button>
                </div>
                {{-- 편집 폼 --}}
                <div class="cat-item-edit" style="display:none;padding:6px 4px 4px;width:100%;">
                    <div style="display:flex;align-items:center;gap:4px;margin-bottom:5px;">
                        <input type="color" class="cat-edit-color" value="{{ $cat->color }}"
                               style="width:26px;height:26px;padding:0;border:1.5px solid #e5e7eb;border-radius:5px;cursor:pointer;background:none;flex-shrink:0;">
                        <input type="text" class="cat-edit-name" value="{{ $cat->name }}"
                               style="flex:1;padding:5px 8px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:12px;outline:none;background:#fff;"
                               onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"
                               onkeydown="if(event.key==='Enter')saveEditCategory({{ $cat->id }},this)">
                    </div>
                    <div style="display:flex;gap:4px;">
                        <button onclick="saveEditCategory({{ $cat->id }}, this)" class="cat-save-btn" style="font-size:11px;padding:4px 10px;">{{ __('common.save') }}</button>
                        <button onclick="cancelEditCategory({{ $cat->id }})" class="cat-cancel-btn" style="font-size:11px;padding:4px 10px;">{{ __('common.cancel') }}</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- 카테고리 추가 --}}
        <div id="cat-add-wrap" style="margin-top:4px;">
            <button onclick="toggleCatForm()" class="cat-add-toggle-btn">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('team.category_add') }}
            </button>
            <div id="cat-form" style="display:none;margin-top:6px;padding:10px;background:#f8f7ff;border:1px solid #e0e7ff;border-radius:10px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <input type="color" id="cat-color-input" value="#6366f1"
                           style="width:28px;height:28px;padding:0;border:1.5px solid #e5e7eb;border-radius:6px;cursor:pointer;background:none;">
                    <input id="cat-name-input" type="text" placeholder="{{ __('team.cat_name_placeholder') }}"
                           style="flex:1;padding:6px 9px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:12px;outline:none;background:#fff;"
                           onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"
                           onkeydown="if(event.key==='Enter')addCategory()">
                </div>
                <div style="display:flex;gap:4px;">
                    <button onclick="addCategory()" class="cat-save-btn">{{ __('team.add_btn') }}</button>
                    <button onclick="toggleCatForm()" class="cat-cancel-btn">{{ __('team.cancel_btn') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 메인 영역 ── --}}
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:16px;">

        {{-- ── 업로드 영역 ── --}}
        <div class="upload-card">

            {{-- 카드 헤더 (클릭 시 본문 토글) --}}
            <div class="upload-card-header" onclick="toggleUploadBody(event)" style="cursor:pointer;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="upload-icon-box">
                        <svg width="17" height="17" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#fff;">{{ __('team.file_upload_tab') }} · {{ __('team.url_register_tab') }}</div>
                        <div style="font-size:11px;color:#a5b4fc;margin-top:1px;" class="project-name-display">{{ $project->name }}</div>
                    </div>
                </div>
                {{-- 세그먼트 탭 + 접기/펼치기 토글 --}}
                <div style="display:flex;align-items:center;gap:8px;" onclick="event.stopPropagation();">
                    <div class="upload-seg">
                        <button id="tab-file-btn" onclick="switchUploadTab('file')" class="upload-seg-btn active">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            {{ __('team.file_upload_tab') }}
                        </button>
                        <button id="tab-url-btn" onclick="switchUploadTab('url')" class="upload-seg-btn">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            {{ __('team.url_register_tab') }}
                        </button>
                    </div>
                </div>
                <button type="button" id="upload-toggle-caret" onclick="event.stopPropagation();toggleUploadBody(event);" title="펼치기/접기"
                        style="background:none;border:none;color:#fff;cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;border-radius:4px;transition:background .12s;"
                        onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='none'">
                    <svg id="upload-toggle-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4" style="transition:transform .15s;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            {{-- 본문 (아코디언 — 기본 접힘, 새로고침 시 다시 접힘) --}}
            <div id="upload-card-body" style="display:none;">

            {{-- ▸ 파일 업로드 탭 --}}
            <div id="tab-file-panel">
                <div id="drop-zone"
                     onclick="document.getElementById('file-picker').click()"
                     ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                    <div class="drop-icon-wrap">
                        <svg width="28" height="28" fill="none" stroke="#6366f1" viewBox="0 0 24 24" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div class="drop-title">{{ __('team.drop_title') }}</div>
                    <div class="drop-hint">{!! __('team.drop_hint') !!}</div>
                    <input type="file" id="file-picker" multiple hidden onchange="addFilesFromInput(this.files)">
                </div>

                {{-- 파일 큐 --}}
                <div id="file-queue" style="display:none;">
                    <div style="padding:4px 16px 0;display:flex;align-items:center;justify-content:space-between;">
                        <span id="queue-label" style="font-size:12px;font-weight:600;color:#6366f1;"></span>
                        <button onclick="clearQueue()" class="queue-clear-btn">{{ __('team.queue_cancel_all') }}</button>
                    </div>
                    {{-- 프로젝트 + 일정 + 카테고리 선택 --}}
                    <div style="padding:6px 16px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                        <label style="font-size:11px;font-weight:600;color:#6b7280;flex-shrink:0;">{{ __('projects.project') }}</label>
                        <select id="upload-project-sel" class="cat-select" onchange="changeUploadProject(this.value, this.options[this.selectedIndex].text)">
                            @foreach($uploadableProjects as $p)
                            <option value="{{ $p->id }}" {{ $p->id == $project->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;flex-shrink:0;margin-left:4px;">{{ __('projects.schedule') }}</label>
                        <select id="upload-schedule-sel" class="cat-select">
                            <option value="">—</option>
                            @foreach($subTasks->groupBy(fn($t) => $t->taskGroup?->title ?? __('team.uncategorized')) as $grpTitle => $grpTasks)
                            <optgroup label="{{ $grpTitle }}">
                                @foreach($grpTasks as $t)
                                <option value="{{ $t->id }}">{{ $t->title }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <label style="font-size:11px;font-weight:600;color:#6b7280;flex-shrink:0;margin-left:4px;">{{ __('team.category_label') }}</label>
                        <select id="upload-category-sel" class="cat-select">
                            <option value="">{{ __('team.uncategorized') }}</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="queue-list" style="padding:8px 14px;display:flex;flex-direction:column;gap:8px;max-height:280px;overflow-y:auto;"></div>
                    <div class="queue-footer">
                        <span id="upload-status" style="font-size:12px;color:#9ca3af;flex:1;"></span>
                        <label class="notify-label">
                            <input type="checkbox" id="notify-email-chk" style="accent-color:#6366f1;width:14px;height:14px;">
                            {{ __('team.email_notify') }}
                        </label>
                        <button id="upload-all-btn" onclick="uploadAll()" class="btn-upload-all">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            {{ __('team.upload_all_btn') }}
                        </button>
                    </div>
                </div>
            </div>{{-- /tab-file-panel --}}

            {{-- ▸ URL 등록 탭 --}}
            <div id="tab-url-panel" style="display:none;flex-direction:column;">
                <div style="padding:18px 20px 14px;">
                    <div class="url-form-row">
                        <div style="flex:2;min-width:0;">
                            <label class="url-label">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                URL <span style="color:#ef4444;">*</span>
                            </label>
                            <input id="url-input" type="url" placeholder="https://..." class="url-field" autocomplete="off">
                        </div>
                        <div style="flex:1;min-width:0;">
                            <label class="url-label">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h6"/></svg>
                                {{ __('team.title_label') }} <span style="color:#ef4444;">*</span>
                            </label>
                            <input id="url-title" type="text" placeholder="{{ __('team.title_placeholder') }}" class="url-field" autocomplete="off">
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;align-items:center;">
                        <input id="url-desc" type="text" placeholder="{{ __('team.desc_placeholder') }}" class="url-field" style="flex:1;min-width:160px;" autocomplete="off">
                        <select id="url-project-sel" class="url-field" style="padding:9px 10px;flex-shrink:0;" onchange="changeUploadProject(this.value, this.options[this.selectedIndex].text)">
                            @foreach($uploadableProjects as $p)
                            <option value="{{ $p->id }}" {{ $p->id == $project->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <select id="url-schedule-sel" class="url-field" style="padding:9px 10px;flex-shrink:0;">
                            <option value="">— {{ __('projects.schedule') }}</option>
                            @foreach($subTasks->groupBy(fn($t) => $t->taskGroup?->title ?? __('team.uncategorized')) as $grpTitle => $grpTasks)
                            <optgroup label="{{ $grpTitle }}">
                                @foreach($grpTasks as $t)
                                <option value="{{ $t->id }}">{{ $t->title }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <select id="url-category-sel" class="url-field" style="padding:9px 10px;flex-shrink:0;">
                            <option value="">{{ __('team.uncategorized') }}</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="url-footer">
                    <div class="url-badges">
                        <span class="url-badge-label">{{ __('team.auto_convert') }}</span>
                        <span class="url-badge figma">Figma</span>
                        <span class="url-badge gdocs">Google Docs</span>
                        <span class="url-badge youtube">YouTube</span>
                        <span class="url-badge all">{{ __('common.all') }} URLs</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                        <span id="url-status" style="font-size:12px;"></span>
                        <button id="url-submit-btn" onclick="submitUrlDirect()" class="btn-url-submit">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            {{ __('team.register_url_btn') }}
                        </button>
                    </div>
                </div>
            </div>
            </div>{{-- /upload-card-body --}}
        </div>

        {{-- ── 파일 목록 ── --}}
        <div id="file-list-wrap" style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
            <div style="padding:14px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:13px;font-weight:700;color:#111827;">{{ __('team.uploaded_files') }}</span>
                    @if($categoryId && $categoryId !== 'none')
                    @php $activeCat = $categories->firstWhere('id', $categoryId); @endphp
                    @if($activeCat)
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;background:{{ $activeCat->color }};">
                        {{ $activeCat->name }}
                    </span>
                    @endif
                    @elseif($categoryId === 'none')
                    <span style="padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#6b7280;background:#f3f4f6;">{{ __('team.uncategorized') }}</span>
                    @endif
                    @if($activeSchedule)
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#4f46e5;background:#eef2ff;border:1px solid #c7d2fe;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $activeSchedule->title }}
                        <a href="{{ route('projects.files.index', $project) }}{{ $categoryId ? '?category='.$categoryId : '' }}" style="color:#6b7280;text-decoration:none;line-height:1;" title="{{ __('common.reset') }}">×</a>
                    </span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    {{-- 파일 검색 --}}
                    <form method="GET" action="{{ route('projects.files.index', $project) }}" style="display:flex;align-items:center;">
                        @if($categoryId)<input type="hidden" name="category" value="{{ $categoryId }}">@endif
                        @if($scheduleId)<input type="hidden" name="schedule" value="{{ $scheduleId }}">@endif
                        <div style="position:relative;">
                            <svg width="13" height="13" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);pointer-events:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="{{ __('common.search') }}"
                                   style="padding:5px 24px 5px 27px;border:1px solid #e5e7eb;border-radius:7px;font-size:12px;width:180px;outline:none;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='#a5b4fc'" onblur="this.style.borderColor='#e5e7eb'">
                            @if(!empty($q))
                            <a href="{{ route('projects.files.index', array_merge(['project' => $project], array_filter(['category' => $categoryId, 'schedule' => $scheduleId]))) }}"
                               style="position:absolute;right:7px;top:50%;transform:translateY(-50%);color:#9ca3af;text-decoration:none;font-size:14px;line-height:1;" title="{{ __('common.reset') }}">&times;</a>
                            @endif
                        </div>
                    </form>
                    <span id="total-file-count" style="font-size:12px;color:#9ca3af;">{{ $files->total() }}</span>
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #f3f4f6;">
                        <th style="text-align:left;padding:10px 20px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_filename') }}</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_category') }}</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_description') }}</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_size') }}</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_uploader') }}</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.col_date') }}</th>
                        <th style="padding:10px 12px;"></th>
                    </tr>
                </thead>
                <tbody id="file-table-body">
                    @forelse($files as $file)
                    <tr style="border-bottom:1px solid #f9fafb;transition:background .12s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                        <td style="padding:12px 20px;max-width:0;width:24%;">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <span style="font-size:20px;flex-shrink:0;">{{ $file->icon }}</span>
                                <div style="min-width:0;flex:1;overflow:hidden;">
                                    @if($file->isUrlType())
                                    <button onclick="openUrlViewer({{ $file->id }}, {{ $project->id }}, '{{ addslashes($file->original_name) }}', '{{ addslashes($file->getEmbedUrl()) }}', '{{ addslashes($file->source_url) }}')"
                                            title="{{ $file->original_name }}"
                                            style="background:none;border:none;cursor:pointer;font-size:13px;font-weight:600;color:#111827;text-align:left;padding:0;display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .12s;"
                                            onmouseover="this.style.color='#6366f1'" onmouseout="this.style.color='#111827'">
                                        {{ $file->original_name }}
                                    </button>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                                        <span style="font-size:10px;padding:1px 7px;background:#e0e7ff;color:#4338ca;border-radius:10px;font-weight:600;">URL</span>
                                        <button id="file-comment-badge-{{ $file->id }}"
                                                onclick="openUrlViewer({{ $file->id }}, {{ $project->id }}, '{{ addslashes($file->original_name) }}', '{{ addslashes($file->getEmbedUrl()) }}', '{{ addslashes($file->source_url) }}')"
                                                style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            {{ __('team.opinion_count') }} {{ $file->comments_count > 0 ? $file->comments_count : '' }}
                                        </button>
                                    </div>
                                    @if($file->reviewRequests->count())
                                    @php $myReview = $file->reviewRequests->firstWhere('reviewer_id', auth()->id()); @endphp
                                    <div style="margin-top:4px;"
                                         onmouseenter="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='inline-flex';"
                                         onmouseleave="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='none';">
                                        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                            <svg width="10" height="10" fill="none" stroke="#6b7280" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            @foreach($file->reviewRequests as $req)
                                            @if($req->reviewed_at)
                                            <span style="font-size:10px;padding:1px 6px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:8px;font-weight:500;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;" title="{{ __('files.review_complete') }}">
                                                <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                {{ $req->reviewer->name }}
                                            </span>
                                            @else
                                            <span style="font-size:10px;padding:1px 6px;background:#fefce8;color:#854d0e;border:1px solid #fde047;border-radius:8px;font-weight:500;white-space:nowrap;" title="{{ __('files.review_pending') }}">{{ $req->reviewer->name }}</span>
                                            @endif
                                            @endforeach
                                        </div>
                                        @if($myReview && !$myReview->reviewed_at)
                                        <button id="review-complete-btn-{{ $file->id }}" onclick="completeReview({{ $file->id }})"
                                                style="display:none;align-items:center;gap:4px;margin-top:4px;font-size:10px;font-weight:600;color:#0891b2;background:#e0f2fe;border:1px solid #7dd3fc;border-radius:6px;padding:2px 8px;cursor:pointer;transition:background .12s;"
                                                onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('files.review_complete') }}
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                    @elseif($file->previewType())
                                    <button onclick="openPreview({{ $file->id }}, {{ $project->id }})"
                                            title="{{ $file->original_name }}"
                                            style="background:none;border:none;cursor:pointer;font-size:13px;font-weight:600;color:#111827;text-align:left;padding:0;display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .12s;"
                                            onmouseover="this.style.color='#6366f1'" onmouseout="this.style.color='#111827'">
                                        {{ $file->original_name }}
                                        @if(($file->versions_count ?? 0) >= 2)
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;margin-left:6px;vertical-align:middle;" title="{{ __('files.versions_tooltip', ['count' => $file->versions_count - 1, 'current' => $file->versions_count]) }}">
                                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                v{{ $file->versions_count }}
                                            </span>
                                        @endif
                                    </button>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                                        <span style="font-size:11px;color:#a5b4fc;">{{ __('team.click_to_review') }}</span>
                                        <button id="file-comment-badge-{{ $file->id }}"
                                                onclick="openComments({{ $file->id }}, '{{ addslashes($file->original_name) }}', {{ $project->id }}, true)"
                                                style="display:{{ $file->comments_count > 0 ? 'inline-flex' : 'none' }};align-items:center;gap:4px;font-size:11px;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            {{ __('team.opinion_count') }} {{ $file->comments_count }}
                                        </button>
                                    </div>
                                    @if($file->reviewRequests->count())
                                    @php $myReview = $file->reviewRequests->firstWhere('reviewer_id', auth()->id()); @endphp
                                    <div style="margin-top:4px;"
                                         onmouseenter="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='inline-flex';"
                                         onmouseleave="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='none';">
                                        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                            <svg width="10" height="10" fill="none" stroke="#6b7280" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            @foreach($file->reviewRequests as $req)
                                            @if($req->reviewed_at)
                                            <span style="font-size:10px;padding:1px 6px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:8px;font-weight:500;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;" title="{{ __('files.review_complete') }}">
                                                <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                {{ $req->reviewer->name }}
                                            </span>
                                            @else
                                            <span style="font-size:10px;padding:1px 6px;background:#fefce8;color:#854d0e;border:1px solid #fde047;border-radius:8px;font-weight:500;white-space:nowrap;" title="{{ __('files.review_pending') }}">{{ $req->reviewer->name }}</span>
                                            @endif
                                            @endforeach
                                        </div>
                                        @if($myReview && !$myReview->reviewed_at)
                                        <button id="review-complete-btn-{{ $file->id }}" onclick="completeReview({{ $file->id }})"
                                                style="display:none;align-items:center;gap:4px;margin-top:4px;font-size:10px;font-weight:600;color:#0891b2;background:#e0f2fe;border:1px solid #7dd3fc;border-radius:6px;padding:2px 8px;cursor:pointer;transition:background .12s;"
                                                onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('files.review_complete') }}
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                    @else
                                    <a href="{{ route('projects.files.download', [$project, $file]) }}"
                                       title="{{ $file->original_name }}"
                                       style="font-size:13px;font-weight:600;color:#111827;text-decoration:none;display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .12s;"
                                       onmouseover="this.style.color='#6366f1'" onmouseout="this.style.color='#111827'">
                                        {{ $file->original_name }}
                                        @if(($file->versions_count ?? 0) >= 2)
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;margin-left:6px;vertical-align:middle;" title="{{ __('files.versions_tooltip', ['count' => $file->versions_count - 1, 'current' => $file->versions_count]) }}">
                                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                v{{ $file->versions_count }}
                                            </span>
                                        @endif
                                    </a>
                                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                                        <button id="file-comment-badge-{{ $file->id }}"
                                                onclick="openComments({{ $file->id }}, '{{ addslashes($file->original_name) }}', {{ $project->id }}, false)"
                                                style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            {{ __('team.opinion_count') }} {{ $file->comments_count > 0 ? $file->comments_count : '' }}
                                        </button>
                                    </div>
                                    @if($file->reviewRequests->count())
                                    @php $myReview = $file->reviewRequests->firstWhere('reviewer_id', auth()->id()); @endphp
                                    <div style="margin-top:4px;"
                                         onmouseenter="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='inline-flex';"
                                         onmouseleave="var b=document.getElementById('review-complete-btn-{{ $file->id }}');if(b)b.style.display='none';">
                                        <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                                            <svg width="10" height="10" fill="none" stroke="#6b7280" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            @foreach($file->reviewRequests as $req)
                                            @if($req->reviewed_at)
                                            <span style="font-size:10px;padding:1px 6px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:8px;font-weight:500;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;" title="{{ __('files.review_complete') }}">
                                                <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                {{ $req->reviewer->name }}
                                            </span>
                                            @else
                                            <span style="font-size:10px;padding:1px 6px;background:#fefce8;color:#854d0e;border:1px solid #fde047;border-radius:8px;font-weight:500;white-space:nowrap;" title="{{ __('files.review_pending') }}">{{ $req->reviewer->name }}</span>
                                            @endif
                                            @endforeach
                                        </div>
                                        @if($myReview && !$myReview->reviewed_at)
                                        <button id="review-complete-btn-{{ $file->id }}" onclick="completeReview({{ $file->id }})"
                                                style="display:none;align-items:center;gap:4px;margin-top:4px;font-size:10px;font-weight:600;color:#0891b2;background:#e0f2fe;border:1px solid #7dd3fc;border-radius:6px;padding:2px 8px;cursor:pointer;transition:background .12s;"
                                                onmouseover="this.style.background='#bae6fd'" onmouseout="this.style.background='#e0f2fe'">
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('files.review_complete') }}
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px;" class="cat-cell" data-file-id="{{ $file->id }}">
                            <div class="cat-badge-wrap" onclick="openCatSelect(this, {{ $file->id }})" title="{{ __('team.click_to_change_cat') }}">
                                @if($file->category)
                                <span class="cat-badge" style="background:{{ $file->category->color }};"
                                      data-cat-id="{{ $file->category->id }}" data-cat-name="{{ $file->category->name }}" data-cat-color="{{ $file->category->color }}">
                                    {{ $file->category->name }}
                                </span>
                                @else
                                <span class="cat-badge cat-badge-none">{{ __('team.uncategorized') }}</span>
                                @endif
                            </div>
                        </td>
                        <td style="padding:12px;max-width:0;width:12%;">
                            <div style="color:#6b7280;font-size:12px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:default;"
                                @if($file->description) onmouseenter="showDescTooltip(event,'{{ addslashes($file->description) }}')" onmouseleave="hideDescTooltip()" @endif
                                title="{{ $file->description ?? '' }}">{{ $file->description ?? '—' }}</div>
                        </td>
                        <td style="padding:12px;color:#6b7280;font-size:12px;white-space:nowrap;">{{ $file->formatted_size }}</td>
                        <td style="padding:12px;color:#6b7280;font-size:12px;">{{ $file->uploader->name }}</td>
                        <td style="padding:12px;color:#6b7280;font-size:12px;white-space:nowrap;">{{ $file->created_at->format('Y.m.d') }}</td>
                        <td style="padding:12px;white-space:nowrap;">
                            <div style="display:inline-flex;align-items:center;gap:10px;flex-wrap:nowrap;white-space:nowrap;">
                                @if($file->isUrlType())
                                <button onclick="logFileAction({{ $file->id }},'view');openUrlViewer({{ $file->id }}, {{ $project->id }}, '{{ addslashes($file->original_name) }}', '{{ addslashes($file->getEmbedUrl()) }}', '{{ addslashes($file->source_url) }}')"
                                        style="font-size:12px;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#6d28d9';showFileActionTooltip(event,{{ $file->id }},'view')" onmouseout="this.style.color='#7c3aed';hideFileActionTooltip()">{{ __('team.viewer_btn') }}</button>
                                @elseif($file->previewType())
                                <button onclick="logFileAction({{ $file->id }},'view');openPreview({{ $file->id }}, {{ $project->id }})"
                                        style="font-size:12px;font-weight:600;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#6d28d9';showFileActionTooltip(event,{{ $file->id }},'view')" onmouseout="this.style.color='#7c3aed';hideFileActionTooltip()">{{ __('team.review_btn') }}</button>
                                @endif
                                <button onclick="openReviewRequest({{ $file->id }}, '{{ addslashes($file->original_name) }}')"
                                        style="font-size:12px;font-weight:600;color:#059669;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#047857';showFileActionTooltip(event,{{ $file->id }},'review_request')" onmouseout="this.style.color='#059669';hideFileActionTooltip()">{{ __('team.review_request_btn') }}</button>
                                @if($file->isShareable())
                                <button id="share-btn-{{ $file->id }}"
                                        onclick="openShareModal({{ $file->id }}, '{{ addslashes($file->original_name) }}', '{{ $file->share_token }}', '{{ $file->share_token ? route('files.public-share', $file->share_token) : '' }}')"
                                        style="font-size:12px;font-weight:600;color:{{ $file->share_token ? '#7c3aed' : '#9ca3af' }};background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#7c3aed';showFileActionTooltip(event,{{ $file->id }},'share')" onmouseout="this.style.color='{{ $file->share_token ? '#7c3aed' : '#9ca3af' }}';hideFileActionTooltip()">
                                    {{ $file->share_token ? __('team.sharing_now') : __('team.share_link') }}
                                </button>
                                @endif
                                @if(!$file->isUrlType())
                                <a href="{{ route('projects.files.download', [$project, $file]) }}"
                                   style="font-size:12px;font-weight:600;color:#6366f1;text-decoration:none;transition:color .12s;"
                                   onmouseover="this.style.color='#4f46e5';showFileActionTooltip(event,{{ $file->id }},'download')" onmouseout="this.style.color='#6366f1';hideFileActionTooltip()">{{ __('team.download_btn') }}</a>
                                @endif
                                @if($copyableProjects->count())
                                <button onclick="openCopyModal({{ $file->id }}, '{{ addslashes($file->original_name) }}')"
                                        style="font-size:12px;font-weight:600;color:#0891b2;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#0e7490';showFileActionTooltip(event,{{ $file->id }},'copy')" onmouseout="this.style.color='#0891b2';hideFileActionTooltip()">{{ __('team.copy_btn') }}</button>
                                @endif
                                @if(auth()->id() === $file->uploaded_by || auth()->user()->isAdmin())
                                <button onclick="openEditModalFromEl(this)"
                                        data-file-id="{{ $file->id }}"
                                        data-file-name="{{ e($file->original_name) }}"
                                        data-file-desc="{{ e($file->description ?? '') }}"
                                        data-file-subtask="{{ $file->sub_task_id ?? '' }}"
                                        data-file-project="{{ $file->project_id }}"
                                        style="font-size:12px;font-weight:600;color:#6366f1;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                        onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#6366f1'">{{ __('files.edit_file') }}</button>
                                <form method="POST" action="{{ route('projects.files.destroy', [$project, $file]) }}"
                                      onsubmit="event.preventDefault();fileDeleteConfirm(this);return false;" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:12px;color:#f87171;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                                            onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#f87171'">{{ __('team.delete_btn') }}</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:48px 20px;text-align:center;color:#9ca3af;font-size:13px;">
                            {{ __('team.no_files_uploaded') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($files->hasPages())
            <div style="padding:14px 20px;border-top:1px solid #f3f4f6;">{{ $files->links() }}</div>
            @endif
        </div>

    </div>{{-- /메인 영역 --}}
</div>

{{-- 검토 요청 모달 --}}
<div id="review-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:92vw;max-height:82vh;overflow-y:auto;box-shadow:0 16px 48px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:16px;font-weight:700;color:#111827;">{{ __('team.review_modal_title') }}</h3>
            <button onclick="closeReviewModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;line-height:1;padding:0;">×</button>
        </div>

        <div style="margin-bottom:16px;padding:12px 14px;background:#f5f3ff;border-radius:8px;border:1px solid #ede9fe;">
            <p style="margin:0 0 2px;font-size:11px;font-weight:600;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.file_label') }}</p>
            <p id="review-file-name" style="margin:0;font-size:13px;font-weight:600;color:#1f2937;word-break:break-all;"></p>
        </div>

        <div style="margin-bottom:16px;">
            <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#374151;">{{ __('team.reviewer_label') }} <span style="color:#ef4444;">*</span></p>
            <div id="review-members" style="display:flex;flex-direction:column;gap:4px;max-height:200px;overflow-y:auto;padding:2px 0;border:1.5px solid #e5e7eb;border-radius:8px;padding:6px 8px;"></div>
        </div>

        <div style="margin-bottom:22px;">
            <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#374151;">{{ __('team.review_message_label') }} <span style="font-size:11px;color:#9ca3af;font-weight:400;">{{ __('team.review_message_hint') }}</span></p>
            <textarea id="review-message" rows="3" placeholder="{{ __('team.review_message_ph') }}"
                      style="width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;resize:vertical;transition:border-color .15s;font-family:inherit;"
                      onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <button onclick="closeReviewModal()"
                    style="padding:8px 18px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px;color:#6b7280;cursor:pointer;transition:background .12s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
            <button id="review-submit-btn" onclick="submitReviewRequest()"
                    style="padding:8px 24px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s;"
                    onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                {{ __('team.submit_review_btn') }}
            </button>
        </div>
    </div>
</div>

{{-- ── 링크 공유 모달 ── --}}
{{-- 커스텀 Confirm 다이얼로그 --}}
<div id="custom-confirm-backdrop" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:340px;padding:28px 24px 20px;text-align:center;">
        <div style="width:44px;height:44px;background:#e0f2fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg width="20" height="20" fill="none" stroke="#0891b2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p id="custom-confirm-msg" style="font-size:14px;font-weight:600;color:#1f2937;margin:0 0 6px;"></p>
        <p id="custom-confirm-sub" style="font-size:12.5px;color:#6b7280;margin:0 0 22px;"></p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button id="custom-confirm-cancel"
                style="flex:1;padding:9px 0;border:1.5px solid #e5e7eb;border-radius:9px;background:#fff;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('files.confirm_cancel') }}</button>
            <button id="custom-confirm-ok"
                style="flex:1;padding:9px 0;border:none;border-radius:9px;background:#0891b2;font-size:13px;font-weight:600;color:#fff;cursor:pointer;transition:background .12s;"
                onmouseover="this.style.background='#0e7490'" onmouseout="this.style.background='#0891b2'">{{ __('files.confirm_ok') }}</button>
        </div>
    </div>
</div>

<div id="share-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1100;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:92vw;box-shadow:0 16px 48px rgba(0,0,0,.18);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#111827;">{{ __('team.share_modal_title') }}</h3>
            <button onclick="closeShareModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;line-height:1;padding:0;">×</button>
        </div>

        <div style="margin-bottom:16px;padding:10px 13px;background:#f5f3ff;border-radius:8px;border:1px solid #ede9fe;">
            <p style="margin:0 0 2px;font-size:10px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;">{{ __('team.file_label') }}</p>
            <p id="share-filename" style="margin:0;font-size:13px;font-weight:600;color:#1f2937;word-break:break-all;"></p>
        </div>

        {{-- 비활성 상태 --}}
        <div id="share-inactive">
            <p style="font-size:13px;color:#6b7280;margin-bottom:18px;line-height:1.6;">
                {{ __('team.share_info') }}<br>
                <span style="font-size:11px;color:#9ca3af;">{{ __('team.share_info_hint') }}</span>
            </p>
            <button id="share-generate-btn" onclick="generateShareLink()"
                    style="width:100%;padding:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;">
                {{ __('team.generate_link') }}
            </button>
        </div>

        {{-- 활성 상태 --}}
        <div id="share-active" style="display:none;">
            <div style="display:flex;gap:8px;margin-bottom:14px;">
                <input id="share-url-input" type="text" readonly
                       style="flex:1;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12px;color:#374151;background:#f9fafb;outline:none;min-width:0;">
                <button id="share-copy-btn" onclick="copyShareLink()"
                        style="padding:9px 16px;background:#6366f1;color:#fff;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0;transition:opacity .15s;white-space:nowrap;">
                    {{ __('team.copy_link') }}
                </button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;">{{ __('team.share_anyone') }}</span>
                <button onclick="revokeShareLink()"
                        style="font-size:12px;color:#f87171;background:none;border:none;cursor:pointer;padding:0;transition:color .12s;"
                        onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#f87171'">{{ __('team.disable_link') }}</button>
            </div>
        </div>
    </div>
</div>

@include('partials.file-preview-modal')

{{-- ── 파일 수정 모달 ── --}}
<div id="edit-file-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1200;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:520px;max-width:94vw;box-shadow:0 16px 48px rgba(0,0,0,.2);">

        {{-- 헤더 --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="16" height="16" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#111827;">{{ __('files.edit_modal_title') }}</h3>
            </div>
            <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:#9ca3af;line-height:1;padding:4px;">×</button>
        </div>

        {{-- 파일명 --}}
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                {{ __('files.edit_name_label') }} <span style="color:#ef4444;">*</span>
            </label>
            <input id="edit-name" type="text" maxlength="255"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#111827;outline:none;transition:border-color .15s;"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'">
        </div>

        {{-- 프로젝트 --}}
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                {{ __('files.edit_project_label') }}
            </label>
            <select id="edit-project-sel"
                    style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#111827;outline:none;background:#fff;cursor:pointer;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"
                    onchange="onEditProjectChange()">
                @foreach($uploadableProjects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <p id="edit-project-hint" style="display:none;margin:5px 0 0;font-size:11px;color:#f59e0b;">
                {{ __('files.edit_project_hint') }}
            </p>
        </div>

        {{-- 일정 (Sub-Task) --}}
        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ __('files.edit_schedule_label') }}
            </label>
            <select id="edit-subtask-sel"
                    style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#111827;outline:none;background:#fff;cursor:pointer;transition:border-color .15s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'">
                <option value="">{{ __('files.edit_schedule_none') }}</option>
                @foreach($subTasks->groupBy(fn($t) => $t->taskGroup?->title ?? __('team.uncategorized')) as $grpTitle => $grpTasks)
                <optgroup label="{{ $grpTitle }}">
                    @foreach($grpTasks as $t)
                    <option value="{{ $t->id }}">{{ $t->title }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>

        {{-- 설명 --}}
        <div style="margin-bottom:24px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h7"/></svg>
                {{ __('files.edit_desc_label') }}
            </label>
            <textarea id="edit-desc" rows="3" maxlength="500" placeholder="{{ __('files.edit_desc_placeholder') }}"
                      style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;outline:none;resize:vertical;transition:border-color .15s;font-family:inherit;"
                      onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>

        {{-- 푸터 --}}
        <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;">
            <span id="edit-status" style="font-size:12px;flex:1;"></span>
            <button onclick="closeEditModal()"
                    style="padding:9px 20px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;font-size:13px;color:#6b7280;cursor:pointer;transition:background .12s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">{{ __('files.confirm_cancel') }}</button>
            <button id="edit-submit-btn" onclick="submitFileEdit()"
                    style="padding:9px 24px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">{{ __('files.edit_save') }}</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── 카테고리 사이드바 ── */
.cat-filter-btn {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 11px; border-radius: 9px;
    font-size: 12px; font-weight: 500; color: #4b5563;
    text-decoration: none; cursor: pointer;
    transition: all .15s; border: 1.5px solid transparent;
    background: transparent; width: 100%; box-sizing: border-box;
}
.cat-filter-btn:hover { background: #f3f4f6; color: #111827; }
.cat-filter-btn.active {
    background: #eef2ff; color: #4338ca;
    border-color: #c7d2fe; font-weight: 600;
}
.cat-count {
    margin-left: auto; font-size: 11px; color: #9ca3af;
    background: #f3f4f6; border-radius: 10px;
    padding: 1px 7px; flex-shrink: 0; font-weight: 600;
}
.cat-filter-btn.active .cat-count { background: #c7d2fe; color: #4338ca; }
.cat-dot {
    width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
}
.cat-item { display: flex; align-items: center; gap: 3px; }
.cat-item .cat-filter-btn { flex: 1; min-width: 0; }
.cat-delete-btn {
    display: none; width: 20px; height: 20px; flex-shrink: 0;
    align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    color: #d1d5db; font-size: 16px; line-height: 1;
    border-radius: 5px; transition: all .12s; padding: 0;
}
.cat-item:hover .cat-delete-btn,
.cat-item:hover .cat-edit-btn { display: flex; }
.cat-delete-btn:hover { color: #ef4444; background: #fef2f2; }
.cat-edit-btn {
    display: none; width: 20px; height: 20px; flex-shrink: 0;
    align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    color: #d1d5db; border-radius: 5px; transition: all .12s; padding: 0;
}
.cat-edit-btn:hover { color: #6366f1; background: #eef2ff; }
.cat-add-toggle-btn {
    display: flex; align-items: center; gap: 5px;
    width: 100%; padding: 7px 11px; border-radius: 9px;
    font-size: 11px; font-weight: 600; color: #6366f1;
    background: none; border: 1.5px dashed #c7d2fe; cursor: pointer;
    transition: all .15s;
}
.cat-add-toggle-btn:hover { background: #eef2ff; border-color: #6366f1; }
.cat-save-btn {
    flex: 1; padding: 5px 0; background: #6366f1; color: #fff;
    border: none; border-radius: 6px; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: opacity .15s;
}
.cat-save-btn:hover { opacity: .85; }
.cat-cancel-btn {
    padding: 5px 10px; background: none; border: 1.5px solid #e5e7eb;
    border-radius: 6px; font-size: 12px; color: #6b7280; cursor: pointer;
}
.cat-select {
    padding: 5px 9px; border: 1.5px solid #e5e7eb; border-radius: 7px;
    font-size: 12px; outline: none; background: #fff; color: #374151;
    transition: border-color .15s; cursor: pointer;
}
.cat-select:focus { border-color: #6366f1; }

/* ── 카테고리 배지 (테이블 셀) ── */
.cat-badge-wrap {
    display: inline-flex; cursor: pointer;
    border-radius: 20px; transition: box-shadow .15s;
}
.cat-badge-wrap:hover { box-shadow: 0 0 0 2px #6366f1; border-radius: 20px; }
.cat-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600; color: #fff; white-space: nowrap;
}
.cat-badge-none {
    background: #f3f4f6; color: #9ca3af;
    border: 1.5px dashed #d1d5db;
}

/* 카테고리 변경 드롭다운 팝업 */
#cat-popup {
    position: fixed; z-index: 9990;
    background: #fff; border: 1.5px solid #e5e7eb; border-radius: 12px;
    box-shadow: 0 8px 28px rgba(0,0,0,.13); padding: 6px;
    min-width: 160px; max-width: 220px;
}
.cat-popup-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 8px; cursor: pointer;
    font-size: 12px; font-weight: 500; color: #374151;
    transition: background .12s; white-space: nowrap;
}
.cat-popup-item:hover { background: #f3f4f6; }
.cat-popup-item.active { background: #eef2ff; color: #4338ca; font-weight: 600; }
.cat-popup-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

/* ── 업로드 카드 ── */
.upload-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(99,102,241,.06);
}
.upload-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    gap: 12px;
}
.upload-icon-box {
    width: 34px; height: 34px; flex-shrink: 0;
    background: rgba(255,255,255,.18);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,.2);
}

/* 세그먼트 탭 */
.upload-seg {
    display: flex; background: rgba(0,0,0,.25); border-radius: 10px; padding: 3px; gap: 2px;
}
.upload-seg-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; border-radius: 8px;
    color: rgba(255,255,255,.5); background: transparent;
    transition: all .2s; white-space: nowrap;
}
.upload-seg-btn.active {
    background: #fff; color: #4338ca;
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
}
.upload-seg-btn:not(.active):hover { color: rgba(255,255,255,.8); }

/* 드롭존 */
#drop-zone {
    margin: 16px;
    border: 2px dashed #c7d2fe;
    border-radius: 12px;
    padding: 36px 24px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: #fafafe;
    user-select: none;
}
#drop-zone:hover, #drop-zone.drag-over {
    background: #eef2ff;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99,102,241,.08);
}
.drop-icon-wrap {
    width: 56px; height: 56px; margin: 0 auto 14px;
    background: linear-gradient(135deg, #eef2ff, #ede9fe);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid #e0e7ff;
    transition: transform .2s;
}
#drop-zone:hover .drop-icon-wrap { transform: translateY(-2px); }
.drop-title { font-size: 14px; font-weight: 700; color: #1f2937; margin-bottom: 6px; }
.drop-hint  { font-size: 12px; color: #9ca3af; line-height: 1.6; }

/* 파일 큐 */
.queue-clear-btn {
    font-size: 11px; color: #9ca3af; background: none; border: none; cursor: pointer;
    padding: 4px 8px; border-radius: 5px; transition: all .12s;
}
.queue-clear-btn:hover { color: #ef4444; background: #fef2f2; }
.queue-footer {
    margin: 4px 14px 14px;
    padding: 10px 14px;
    background: #f8f7ff;
    border: 1px solid #ede9fe;
    border-radius: 10px;
    display: flex; align-items: center; gap: 10px;
}
.notify-label {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: #6b7280; cursor: pointer; user-select: none; flex-shrink: 0;
}
.btn-upload-all {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: opacity .15s; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
}
.btn-upload-all:hover { opacity: .88; }

/* 큐 아이템 */
.queue-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 11px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 9px;
    transition: all .12s;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.queue-item.uploading { background: #eff6ff; border-color: #bfdbfe; }
.queue-item.done      { background: #f0fdf4; border-color: #bbf7d0; }
.queue-item.error     { background: #fef2f2; border-color: #fecaca; }
.queue-item-icon  { font-size: 18px; flex-shrink: 0; }
.queue-item-name  { font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
.queue-item-size  { font-size: 11px; color: #9ca3af; margin-top: 1px; }
.queue-item-desc  { flex: 1; min-width: 0; padding: 5px 10px; border: 1.5px solid #e5e7eb; border-radius: 7px; font-size: 12px; outline: none; background: #fafafe; transition: border-color .15s; }
.queue-item-desc:focus { border-color: #6366f1; background: #fff; }
.queue-progress   { height: 3px; background: #e5e7eb; border-radius: 2px; margin-top: 5px; overflow: hidden; display: none; }
.queue-progress-fill { height: 100%; background: linear-gradient(90deg,#6366f1,#8b5cf6); border-radius: 2px; transition: width .1s; }
.queue-item.uploading .queue-progress { display: block; }
.queue-status     { font-size: 11px; font-weight: 600; flex-shrink: 0; min-width: 32px; text-align: right; }
.queue-remove     { flex-shrink: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: #d1d5db; border-radius: 5px; font-size: 16px; transition: all .12s; }
.queue-remove:hover { color: #ef4444; background: #fef2f2; }

/* URL 폼 */
.url-form-row { display: flex; gap: 10px; }
.url-label {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px;
}
.url-field {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid #e5e7eb; border-radius: 9px;
    font-size: 13px; box-sizing: border-box; outline: none;
    background: #fafafe; color: #1f2937;
    transition: all .18s; font-family: inherit;
}
.url-field:focus {
    border-color: #6366f1; background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.url-field::placeholder { color: #c4c8d4; }
.url-footer {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 20px 14px;
    border-top: 1px solid #f3f4f6;
    background: linear-gradient(135deg, #fafaff, #f5f3ff);
}
.url-badges { display: flex; align-items: center; gap: 5px; flex: 1; flex-wrap: wrap; }
.url-badge-label {
    font-size: 9px; font-weight: 700; color: #a5b4fc;
    text-transform: uppercase; letter-spacing: .06em; margin-right: 3px;
}
.url-badge {
    font-size: 11px; padding: 2px 9px; border-radius: 5px; font-weight: 600;
}
.url-badge.figma   { background: #fff0f9; color: #db2777; border: 1px solid #fce7f3; }
.url-badge.gdocs   { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
.url-badge.youtube { background: #fff7f0; color: #ea580c; border: 1px solid #fed7aa; }
.url-badge.all     { background: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb; }
.btn-url-submit {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 20px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: opacity .15s;
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
}
.btn-url-submit:hover { opacity: .88; }
</style>
@endpush

@section('scripts')
<script>
let   UPLOAD_URL       = '{{ route('projects.files.store', $project) }}';
const CAT_STORE_URL    = '{{ route('projects.file-categories.store', $project) }}';
const CAT_REORDER_URL  = '{{ route('projects.file-categories.reorder', $project) }}';
const CAT_UPDATE_BASE  = '{{ url("projects/{$project->id}/file-categories") }}/';
const CAT_DESTROY_BASE = '{{ url("projects/{$project->id}/file-categories") }}/';
const PROJECTS_BASE_URL = '{{ url("projects") }}/';
const FILE_UPDATE_BASE = '{{ url("projects/{$project->id}/files") }}/';
const CURRENT_PROJECT_ID = {{ $project->id }};

const STR = {
    uncategorized:       '{{ __("team.uncategorized") }}',
    cat_delete_confirm:  '{{ __("team.cat_delete_confirm") }}',
    delete_confirm:      '{{ __("team.delete_confirm") }}',
    delete:              '{{ __("common.delete") }}',
    edit:                '{{ __("common.edit") }}',
    save:                '{{ __("common.save") }}',
    cancel:              '{{ __("common.cancel") }}',
    url_enter:           '{{ __("team.url_label") }}',
    title_enter:         '{{ __("team.title_label") }}',
    registering:         '{{ __("team.register_url_btn") }}',
    registered_ok:       '✓',
    register_fail:       '✗',
    network_error:       '{{ __("team.network_error_msg") }}',
    uploading:           '{{ __("team.uploading") }}',
    upload_all:          '{{ __("team.upload_all_btn") }}',
    no_reviewers:        '{{ __("team.no_reviewers") }}',
    select_reviewer:     '{{ __("team.select_reviewer") }}',
    sending:             '{{ __("team.sending") }}',
    review_sent_tpl:     '{{ __("team.review_sent", ["count" => ":count"]) }}',
    review_fail:         '{{ __("team.review_fail") }}',
    network_error_msg:   '{{ __("team.network_error_msg") }}',
    review_submit:       '{{ __("team.submit_review_btn") }}',
    generating:          '{{ __("team.generating") }}',
    generate_link:       '{{ __("team.generate_link") }}',
    copy_link:           '{{ __("team.copy_link") }}',
    copied:              '{{ __("team.copied") }}',
    revoke_confirm:      '{{ __("team.revoke_confirm") }}',
    sharing_now:         '{{ __("team.sharing_now") }}',
    share_link:          '{{ __("team.share_link") }}',
    select_project_first:'{{ __("team.select_project_first") }}',
    copying:             '{{ __("team.copying") }}',
    copy_btn:            '{{ __("team.copy_btn") }}',
    copy_success:        '{{ __("team.copy_success") }}',
    copy_fail:           '{{ __("team.copy_fail") }}',
    desc_placeholder:    '{{ __("team.desc_placeholder") }}',
    edit_save:           @json(__('files.edit_save')),
    edit_saving:         @json(__('files.edit_saving')),
    edit_name_required:  @json(__('files.edit_name_required')),
    edit_save_failed:    @json(__('files.edit_save_failed')),
    edit_error:          @json(__('files.edit_error')),
    processing:          @json(__('files.processing')),
    error_occurred:      @json(__('files.error_occurred')),
    network_err:         @json(__('files.network_error')),
    tooltip_loading:     @json(__('files.tooltip_loading')),
    tooltip_load_failed: @json(__('files.tooltip_load_failed')),
    review_complete_confirm:     @json(__('files.review_complete_confirm')),
    review_complete_confirm_sub: @json(__('files.review_complete_confirm_sub')),
    log_review_request:  @json(__('files.log_review_request')),
    log_download:        @json(__('files.log_download')),
    log_copy:            @json(__('files.log_copy')),
    log_view:            @json(__('files.log_view')),
    log_share:           @json(__('files.log_share')),
    log_no_history:      @json(__('files.log_no_history')),
    log_history_header:  @json(__('files.log_history_header')),
};

// ── 파일 삭제 — 커스텀 확인 다이얼로그 ──────────────────────────
async function fileDeleteConfirm(form) {
    if (await __confirm(STR.delete_confirm)) form.submit();
}

// ── 업로드 프로젝트 변경 ────────────────────────────────────────
async function changeUploadProject(projectId, projectName) {
    UPLOAD_URL = PROJECTS_BASE_URL + projectId + '/files';

    // keep both project selectors in sync
    ['upload-project-sel', 'url-project-sel'].forEach(id => {
        const sel = document.getElementById(id);
        if (sel && sel.value !== String(projectId)) sel.value = projectId;
    });

    // update project name shown in upload card header
    const nameEl = document.querySelector('.upload-card-header .project-name-display');
    if (nameEl) nameEl.textContent = projectName;

    // fetch categories + schedules in parallel
    try {
        const [catRes, schedRes] = await Promise.all([
            fetch(PROJECTS_BASE_URL + projectId + '/file-categories', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            }),
            fetch(PROJECTS_BASE_URL + projectId + '/sub-tasks', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            }),
        ]);

        if (catRes.ok) {
            const cats = await catRes.json();
            const catOpts = `<option value="">${STR.uncategorized}</option>`
                + cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            const uploadCatSel = document.getElementById('upload-category-sel');
            const urlCatSel    = document.getElementById('url-category-sel');
            if (uploadCatSel) uploadCatSel.innerHTML = catOpts;
            if (urlCatSel)    urlCatSel.innerHTML    = catOpts;
        }

        if (schedRes.ok) {
            const tasks = await schedRes.json();
            const byGroup = {};
            tasks.forEach(t => {
                const g = t.task_group?.title ?? STR.uncategorized;
                if (!byGroup[g]) byGroup[g] = [];
                byGroup[g].push(t);
            });
            const buildOpts = (placeholder) =>
                `<option value="">${placeholder}</option>` +
                Object.entries(byGroup).map(([g, ts]) =>
                    `<optgroup label="${g}">${ts.map(t => `<option value="${t.id}">${t.title}</option>`).join('')}</optgroup>`
                ).join('');
            const uploadSchedSel = document.getElementById('upload-schedule-sel');
            const urlSchedSel    = document.getElementById('url-schedule-sel');
            if (uploadSchedSel) uploadSchedSel.innerHTML = buildOpts('—');
            if (urlSchedSel)    urlSchedSel.innerHTML    = buildOpts('— {{ __("projects.schedule") }}');
        }
    } catch (_) {}
}

// ── 카테고리 추가 ──────────────────────────────────────────────
async function toggleCatForm() {
    const f = document.getElementById('cat-form');
    const isHidden = f.style.display === 'none';
    f.style.display = isHidden ? 'block' : 'none';
    if (isHidden) document.getElementById('cat-name-input').focus();
}

async function addCategory() {
    const name  = document.getElementById('cat-name-input').value.trim();
    const color = document.getElementById('cat-color-input').value;
    if (!name) { document.getElementById('cat-name-input').focus(); return; }

    const res  = await fetch(CAT_STORE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name, color }),
    });
    const data = await res.json();
    if (!data.ok) return;

    const cat = data.category;

    // 사이드바 목록 추가
    const li = document.createElement('div');
    li.className = 'cat-item';
    li.dataset.id = cat.id;
    li.innerHTML = `
        <div class="cat-item-view" style="display:flex;align-items:center;width:100%;gap:4px;">
            <a href="?category=${cat.id}" class="cat-filter-btn" style="flex:1;min-width:0;">
                <span class="cat-dot" style="background:${cat.color};"></span>
                <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(cat.name)}</span>
                <span class="cat-count">0</span>
            </a>
            <button class="cat-edit-btn" onclick="startEditCategory(${cat.id},'${escHtml(cat.name).replace(/'/g,"\\'")}','${cat.color}')" title="${STR.edit}">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button class="cat-delete-btn" onclick="deleteCategory(${cat.id},this)" title="${STR.delete}">×</button>
        </div>
        <div class="cat-item-edit" style="display:none;padding:6px 4px 4px;width:100%;">
            <div style="display:flex;align-items:center;gap:4px;margin-bottom:5px;">
                <input type="color" class="cat-edit-color" value="${cat.color}" style="width:26px;height:26px;padding:0;border:1.5px solid #e5e7eb;border-radius:5px;cursor:pointer;background:none;flex-shrink:0;">
                <input type="text" class="cat-edit-name" value="${escHtml(cat.name)}" style="flex:1;padding:5px 8px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:12px;outline:none;background:#fff;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e5e7eb'" onkeydown="if(event.key==='Enter')saveEditCategory(${cat.id},this)">
            </div>
            <div style="display:flex;gap:4px;">
                <button onclick="saveEditCategory(${cat.id},this)" class="cat-save-btn" style="font-size:11px;padding:4px 10px;">${STR.save}</button>
                <button onclick="cancelEditCategory(${cat.id})" class="cat-cancel-btn" style="font-size:11px;padding:4px 10px;">${STR.cancel}</button>
            </div>
        </div>`;
    document.getElementById('cat-list').appendChild(li);
    setCatDraggable();

    // 파일 카테고리 팝업 배열에 추가
    ALL_CATS.push({ id: cat.id, name: cat.name, color: cat.color });

    // 업로드 셀렉트에 추가
    const opt1 = new Option(cat.name, cat.id);
    const opt2 = new Option(cat.name, cat.id);
    document.getElementById('upload-category-sel').add(opt1);
    document.getElementById('url-category-sel').add(opt2);

    // 폼 초기화
    document.getElementById('cat-name-input').value = '';
    document.getElementById('cat-color-input').value = '#6366f1';
    document.getElementById('cat-form').style.display = 'none';
}

/* ── 카테고리 드래그 순서 변경 ── */
(function () {
    const list = document.getElementById('cat-list');
    if (!list) return;
    let _catDragEl = null;

    window.setCatDraggable = function () {
        list.querySelectorAll('.cat-item').forEach(it => {
            it.setAttribute('draggable', 'true');
            const a = it.querySelector('a.cat-filter-btn');
            if (a) a.setAttribute('draggable', 'false');
        });
    };

    function catDragAfter(y) {
        const els = [...list.querySelectorAll('.cat-item')].filter(el => el !== _catDragEl);
        let closest = null, closestOffset = -Infinity;
        els.forEach(el => {
            const box = el.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closestOffset) { closestOffset = offset; closest = el; }
        });
        return closest;
    }

    async function catSaveOrder() {
        const ids = [...list.querySelectorAll('.cat-item')].map(it => it.dataset.id);
        try {
            await fetch(CAT_REORDER_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids }),
            });
        } catch (e) { /* 순서 저장 실패는 조용히 무시 */ }
    }

    list.addEventListener('dragstart', e => {
        const item = e.target.closest('.cat-item');
        if (!item) return;
        _catDragEl = item;
        item.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragend', () => {
        if (_catDragEl) _catDragEl.style.opacity = '';
        _catDragEl = null;
    });
    list.addEventListener('dragover', e => {
        if (!_catDragEl) return;
        e.preventDefault();
        const after = catDragAfter(e.clientY);
        if (after == null) {
            if (list.lastElementChild !== _catDragEl) list.appendChild(_catDragEl);
        } else if (after !== _catDragEl) {
            list.insertBefore(_catDragEl, after);
        }
    });
    list.addEventListener('drop', e => {
        e.preventDefault();
        catSaveOrder();
    });

    setCatDraggable();
})();

async function startEditCategory(id, name, color) {
    const item = document.querySelector(`.cat-item[data-id="${id}"]`);
    if (!item) return;
    item.querySelector('.cat-item-view').style.display = 'none';
    const editEl = item.querySelector('.cat-item-edit');
    editEl.style.display = 'block';
    editEl.querySelector('.cat-edit-color').value = color;
    const nameInput = editEl.querySelector('.cat-edit-name');
    nameInput.value = name;
    setTimeout(() => nameInput.focus(), 30);
}

async function cancelEditCategory(id) {
    const item = document.querySelector(`.cat-item[data-id="${id}"]`);
    if (!item) return;
    item.querySelector('.cat-item-view').style.display = 'flex';
    item.querySelector('.cat-item-edit').style.display = 'none';
}

async function saveEditCategory(id, el) {
    const item  = document.querySelector(`.cat-item[data-id="${id}"]`);
    if (!item) return;
    const edit  = item.querySelector('.cat-item-edit');
    const name  = edit.querySelector('.cat-edit-name').value.trim();
    const color = edit.querySelector('.cat-edit-color').value;
    if (!name) { edit.querySelector('.cat-edit-name').focus(); return; }

    const res  = await fetch(CAT_UPDATE_BASE + id, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name, color }),
    });
    const data = await res.json();
    if (!data.ok) return;

    // DOM 반영
    const view = item.querySelector('.cat-item-view');
    view.querySelector('.cat-dot').style.background = color;
    view.querySelector('a span:nth-child(2)').textContent = name;
    view.style.display = 'flex';
    edit.style.display = 'none';

    // ALL_CATS 동기화
    const cat = ALL_CATS.find(c => c.id === id);
    if (cat) { cat.name = name; cat.color = color; }

    // 파일 목록 내 배지 동기화
    document.querySelectorAll(`.cat-badge[data-cat-id="${id}"]`).forEach(badge => {
        badge.style.background    = color;
        badge.textContent         = name;
        badge.dataset.catName     = name;
        badge.dataset.catColor    = color;
    });

    // 업로드 셀렉트 옵션 동기화
    document.querySelectorAll('#upload-category-sel option, #url-category-sel option').forEach(opt => {
        if (opt.value == id) opt.textContent = name;
    });
}

async function deleteCategory(id, btn) {
    if (!await __confirm(STR.cat_delete_confirm)) return;
    const res = await fetch(CAT_DESTROY_BASE + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (!data.ok) return;

    const item = btn.closest('.cat-item');
    if (item) item.remove();

    // ALL_CATS에서 제거
    ALL_CATS = ALL_CATS.filter(c => c.id !== id);

    // 셀렉트에서 제거
    for (const sel of ['upload-category-sel','url-category-sel']) {
        const el = document.getElementById(sel);
        const opt = [...el.options].find(o => o.value == id);
        if (opt) opt.remove();
    }
}

// ── 업로드 영역 아코디언 (기본 접힘, 상태 미저장) ───────────────
function toggleUploadBody(e) {
    if (e) e.stopPropagation();
    const body = document.getElementById('upload-card-body');
    const icon = document.getElementById('upload-toggle-icon');
    const isHidden = body.style.display === 'none' || body.style.display === '';
    body.style.display = isHidden ? 'block' : 'none';
    if (icon) icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
}

// ── 탭 전환 ──────────────────────────────────────────────────
async function switchUploadTab(tab) {
    const isFile = tab === 'file';
    document.getElementById('tab-file-panel').style.display = isFile ? 'block' : 'none';
    document.getElementById('tab-url-panel').style.display  = isFile ? 'none'  : 'flex';
    document.getElementById('tab-file-btn').classList.toggle('active', isFile);
    document.getElementById('tab-url-btn').classList.toggle('active', !isFile);
}

// ── URL 등록 ──────────────────────────────────────────────────
async function submitUrlDirect() {
    const url   = document.getElementById('url-input').value.trim();
    const title = document.getElementById('url-title').value.trim();
    if (!url)   { document.getElementById('url-status').textContent = STR.url_enter; document.getElementById('url-status').style.color='#dc2626'; return; }
    if (!title) { document.getElementById('url-status').textContent = STR.title_enter; document.getElementById('url-status').style.color='#dc2626'; return; }
    const btn = document.getElementById('url-submit-btn');
    btn.disabled = true; btn.textContent = STR.registering;
    document.getElementById('url-status').textContent = '';
    const catId   = document.getElementById('url-category-sel').value   || null;
    const schedId = document.getElementById('url-schedule-sel').value || null;
    fetch(UPLOAD_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_type: 'url', source_url: url, original_name: title,
                               description: document.getElementById('url-desc').value,
                               category_id: catId, sub_task_id: schedId }),
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            document.getElementById('url-status').textContent = STR.registered_ok;
            document.getElementById('url-status').style.color = '#059669';
            setTimeout(() => location.reload(), 800);
        } else {
            document.getElementById('url-status').textContent = d.message || STR.register_fail;
            document.getElementById('url-status').style.color = '#dc2626';
            btn.disabled = false; btn.textContent = STR.registering;
        }
    }).catch(() => {
        document.getElementById('url-status').textContent = STR.network_error;
        document.getElementById('url-status').style.color = '#dc2626';
        btn.disabled = false; btn.textContent = STR.registering;
    });
}

let queuedFiles = [];
let queueIdxCnt = 0;
let isUploading = false;

async function addFilesFromInput(fileList) {
    addFilesToQueue(Array.from(fileList));
    document.getElementById('file-picker').value = '';
}

async function addFilesToQueue(files) {
    files.forEach(f => {
        queuedFiles.push({ file: f, desc: '', id: queueIdxCnt++, status: 'pending' });
    });
    renderQueue();
}

async function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('drop-zone').classList.add('drag-over');
}
async function handleDragLeave(e) {
    if (!document.getElementById('drop-zone').contains(e.relatedTarget))
        document.getElementById('drop-zone').classList.remove('drag-over');
}
async function handleDrop(e) {
    e.preventDefault();
    document.getElementById('drop-zone').classList.remove('drag-over');
    const files = Array.from(e.dataTransfer.files);
    if (files.length) addFilesToQueue(files);
}

async function renderQueue() {
    const wrap  = document.getElementById('file-queue');
    const list  = document.getElementById('queue-list');
    const label = document.getElementById('queue-label');

    if (queuedFiles.length === 0) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';
    label.textContent = `${queuedFiles.length} files`;

    list.innerHTML = queuedFiles.map(item => {
        const sizeTxt = formatBytes(item.file.size);
        const iconMap = { 'application/pdf': '📄', 'image/': '🖼️', 'video/': '🎬', 'audio/': '🎵' };
        let icon = '📎';
        for (const [k, v] of Object.entries(iconMap)) if (item.file.type.startsWith(k)) { icon = v; break; }

        const statusHtml = item.status === 'done'
            ? `<span class="queue-status" style="color:#16a34a;">✓</span>`
            : item.status === 'error'
            ? `<span class="queue-status" style="color:#dc2626;">✕</span>`
            : item.status === 'uploading'
            ? `<span class="queue-status" style="color:#6366f1;">…</span>`
            : `<button class="queue-remove" onclick="removeQueued(${item.id})">×</button>`;

        const descDisabled = item.status !== 'pending' ? 'disabled' : '';

        return `<div class="queue-item ${item.status !== 'pending' ? item.status : ''}" id="qitem-${item.id}">
            <span class="queue-item-icon">${icon}</span>
            <div style="min-width:0;flex-shrink:0;max-width:200px;">
                <div class="queue-item-name" title="${escHtml(item.file.name)}">${escHtml(item.file.name)}</div>
                <div class="queue-item-size">${sizeTxt}</div>
                <div class="queue-progress" id="prog-${item.id}"><div class="queue-progress-fill" id="progfill-${item.id}" style="width:0%"></div></div>
            </div>
            <input type="text" class="queue-item-desc" ${descDisabled}
                   placeholder="${STR.desc_placeholder}" value="${escHtml(item.desc)}"
                   oninput="updateDesc(${item.id}, this.value)">
            ${statusHtml}
        </div>`;
    }).join('');
}

async function updateDesc(id, val) {
    const item = queuedFiles.find(f => f.id === id);
    if (item) item.desc = val;
}
async function removeQueued(id) {
    queuedFiles = queuedFiles.filter(f => f.id !== id);
    renderQueue();
}
async function clearQueue() {
    queuedFiles = [];
    renderQueue();
}

async function uploadAll() {
    if (isUploading) return;
    const pending = queuedFiles.filter(f => f.status === 'pending');
    if (!pending.length) return;

    isUploading = true;
    const btn = document.getElementById('upload-all-btn');
    btn.disabled = true; btn.textContent = STR.uploading;

    let successCount = 0;
    for (const item of pending) {
        const ok = await uploadOne(item);
        if (ok) successCount++;
    }

    isUploading = false;
    btn.disabled = false; btn.textContent = STR.upload_all;
    document.getElementById('upload-status').textContent =
        successCount === pending.length
            ? `✓ ${successCount} / ${pending.length}`
            : `${successCount}/${pending.length}`;

    const hasErrors = queuedFiles.some(f => f.status === 'error');
    if (!hasErrors) {
        setTimeout(() => location.reload(), 800);
    } else {
        queuedFiles = queuedFiles.filter(f => f.status !== 'done');
        renderQueue();
    }
}

async function uploadOne(item) {
    return new Promise(resolve => {
        item.status = 'uploading';
        renderQueue();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', UPLOAD_URL);
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                const fill = document.getElementById(`progfill-${item.id}`);
                if (fill) fill.style.width = pct + '%';
            }
        });

        xhr.addEventListener('load', () => {
            try {
                const res = JSON.parse(xhr.responseText);
                item.status = (xhr.status < 300 && res.ok !== false) ? 'done' : 'error';
            } catch {
                item.status = xhr.status < 300 ? 'done' : 'error';
            }
            renderQueue();
            resolve(item.status === 'done');
        });

        xhr.addEventListener('error', () => {
            item.status = 'error'; renderQueue(); resolve(false);
        });

        const fd = new FormData();
        fd.append('file', item.file);
        if (item.desc.trim()) fd.append('description', item.desc.trim());
        if (document.getElementById('notify-email-chk')?.checked) fd.append('notify_email', '1');
        const catId  = document.getElementById('upload-category-sel').value;
        const schedId = document.getElementById('upload-schedule-sel').value;
        if (catId)   fd.append('category_id', catId);
        if (schedId) fd.append('sub_task_id', schedId);
        xhr.send(fd);
    });
}

// ── 카테고리 이동 ──────────────────────────────────────────────
const FILE_CAT_BASE = '{{ url("projects/{$project->id}/files") }}/';
let ALL_CATS = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'color' => $c->color])->values());
let catPopup = null;
let catPopupFileId = null;

async function openCatSelect(wrap, fileId) {
    closeAllCatPopups();

    // 현재 카테고리
    const badge = wrap.querySelector('.cat-badge');
    const curCatId = badge?.dataset?.catId ? parseInt(badge.dataset.catId) : null;

    // 팝업 생성
    catPopup = document.createElement('div');
    catPopup.id = 'cat-popup';
    catPopupFileId = fileId;

    const items = [
        { id: null, name: STR.uncategorized, color: null },
        ...ALL_CATS,
    ];

    catPopup.innerHTML = items.map((c, idx) => {
        const isActive = (c.id === curCatId) || (c.id === null && curCatId === null);
        const dot = c.color
            ? `<span class="cat-popup-dot" style="background:${c.color};"></span>`
            : `<span class="cat-popup-dot" style="background:#d1d5db;border:1.5px dashed #9ca3af;"></span>`;
        return `<div class="cat-popup-item${isActive ? ' active' : ''}" data-idx="${idx}">
            ${dot}<span>${escHtml(c.name)}</span>
        </div>`;
    }).join('');

    // 인라인 onclick 대신 이벤트 리스너 — JSON 큰따옴표가 HTML 속성을 깨는 문제 방지
    catPopup.querySelectorAll('.cat-popup-item').forEach(el => {
        const idx = parseInt(el.dataset.idx);
        el.addEventListener('click', () => applyCatChange(items[idx], fileId));
    });

    document.body.appendChild(catPopup);

    // 위치 계산
    const rect = wrap.getBoundingClientRect();
    const pw = catPopup.offsetWidth || 180;
    const ph = catPopup.offsetHeight || 200;
    let top  = rect.bottom + 6;
    let left = rect.left;
    if (top + ph > window.innerHeight - 8) top = rect.top - ph - 6;
    if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
    catPopup.style.top  = top  + 'px';
    catPopup.style.left = left + 'px';
}

async function closeAllCatPopups() {
    if (catPopup) { catPopup.remove(); catPopup = null; catPopupFileId = null; }
}

document.addEventListener('click', async function(e) {
    if (catPopup && !catPopup.contains(e.target) && !e.target.closest('.cat-badge-wrap')) {
        closeAllCatPopups();
    }
});

async function applyCatChange(cat, fileId) {
    closeAllCatPopups();

    const cell = document.querySelector(`.cat-cell[data-file-id="${fileId}"]`);
    if (!cell) return;
    const bwrap = cell.querySelector('.cat-badge-wrap');
    if (!bwrap) return;

    // 변경 전 카테고리 ID 기록
    const oldBadge = bwrap.querySelector('[data-cat-id]');
    const oldCatId = oldBadge ? parseInt(oldBadge.dataset.catId) : null;

    const res = await fetch(`${FILE_CAT_BASE}${fileId}/category`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ category_id: cat.id }),
    });
    const data = await res.json();
    if (!data.ok) return;

    // 배지 DOM 업데이트
    if (cat.id) {
        bwrap.innerHTML = `<span class="cat-badge" style="background:${cat.color};"
            data-cat-id="${cat.id}" data-cat-name="${escHtml(cat.name)}" data-cat-color="${cat.color}">
            ${escHtml(cat.name)}</span>`;
    } else {
        bwrap.innerHTML = `<span class="cat-badge cat-badge-none">${STR.uncategorized}</span>`;
    }

    // 사이드바 카운트 업데이트
    if (oldCatId !== null) {
        const oldCountEl = document.querySelector(`.cat-item[data-id="${oldCatId}"] .cat-count`);
        if (oldCountEl) oldCountEl.textContent = Math.max(0, parseInt(oldCountEl.textContent || '0') - 1);
    }
    if (cat.id !== null) {
        const newCountEl = document.querySelector(`.cat-item[data-id="${cat.id}"] .cat-count`);
        if (newCountEl) newCountEl.textContent = parseInt(newCountEl.textContent || '0') + 1;
    }
}

// ── 유틸 ──
function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── 검토 요청 모달 ──
const REVIEW_BASE_URL = '{{ url("projects/{$project->id}/files") }}/';
const REVIEW_MEMBERS  = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'email' => $m->email])->values());
let reviewFileId = null;

async function openReviewRequest(fileId, fileName) {
    reviewFileId = fileId;
    document.getElementById('review-file-name').textContent = fileName;
    document.getElementById('review-message').value = '';

    const membersDiv = document.getElementById('review-members');
    if (!REVIEW_MEMBERS.length) {
        membersDiv.innerHTML = `<p style="font-size:13px;color:#9ca3af;margin:4px 0;">${STR.no_reviewers}</p>`;
    } else {
        membersDiv.innerHTML = REVIEW_MEMBERS.map(m => `
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 6px;border-radius:6px;transition:background .12s;"
                   onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                <input type="checkbox" name="review_member" value="${m.id}"
                       style="accent-color:#059669;width:15px;height:15px;flex-shrink:0;">
                <span style="line-height:1.3;">
                    <span style="display:block;font-size:13px;color:#374151;">${escHtml(m.name)}</span>
                    <span style="display:block;font-size:11px;color:#9ca3af;">${escHtml(m.email)}</span>
                </span>
            </label>
        `).join('');
    }

    document.getElementById('review-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function closeReviewModal() {
    document.getElementById('review-modal').style.display = 'none';
    document.body.style.overflow = '';
    reviewFileId = null;
}

async function submitReviewRequest() {
    const checked = [...document.querySelectorAll('input[name="review_member"]:checked')];
    if (!checked.length) { alert(STR.select_reviewer); return; }

    const btn = document.getElementById('review-submit-btn');
    btn.disabled = true; btn.textContent = STR.sending;

    try {
        const res = await fetch(REVIEW_BASE_URL + reviewFileId + '/review-request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ user_ids: checked.map(c => parseInt(c.value)), message: document.getElementById('review-message').value.trim() }),
        });
        const data = await res.json();
        if (data.ok) {
            closeReviewModal();
            const toast = document.createElement('div');
            toast.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%);background:#111827;color:#fff;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2);';
            toast.textContent = STR.review_sent_tpl.replace(':count', data.count);
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        } else {
            alert(data.error || STR.review_fail);
        }
    } catch {
        alert(STR.network_error_msg);
    } finally {
        btn.disabled = false; btn.textContent = STR.review_submit;
    }
}

document.getElementById('review-modal').addEventListener('click', async function(e) {
    if (e.target === this) closeReviewModal();
});

// ── 커스텀 Confirm ──────────────────────────────────────────────
function customConfirm(message, sub, onOk) {
    const backdrop = document.getElementById('custom-confirm-backdrop');
    document.getElementById('custom-confirm-msg').textContent = message;
    document.getElementById('custom-confirm-sub').textContent = sub || '';
    backdrop.style.display = 'flex';

    const okBtn     = document.getElementById('custom-confirm-ok');
    const cancelBtn = document.getElementById('custom-confirm-cancel');

    function cleanup() {
        backdrop.style.display = 'none';
        okBtn.replaceWith(okBtn.cloneNode(true));
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        // hover 이벤트 재등록
        document.getElementById('custom-confirm-ok').onmouseover     = () => document.getElementById('custom-confirm-ok').style.background     = '#0e7490';
        document.getElementById('custom-confirm-ok').onmouseout      = () => document.getElementById('custom-confirm-ok').style.background     = '#0891b2';
        document.getElementById('custom-confirm-cancel').onmouseover = () => document.getElementById('custom-confirm-cancel').style.background = '#f9fafb';
        document.getElementById('custom-confirm-cancel').onmouseout  = () => document.getElementById('custom-confirm-cancel').style.background = '#fff';
    }

    document.getElementById('custom-confirm-ok').addEventListener('click', function() { cleanup(); onOk(); }, { once: true });
    document.getElementById('custom-confirm-cancel').addEventListener('click', function() { cleanup(); }, { once: true });
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) cleanup(); }, { once: true });
}

// ── 검토 완료 ──────────────────────────────────────────────────
function completeReview(fileId) {
    customConfirm(STR.review_complete_confirm, STR.review_complete_confirm_sub, async () => {
        const btn = document.getElementById('review-complete-btn-' + fileId);
        const savedHTML = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.textContent = STR.processing; }

        try {
            const res = await fetch(REVIEW_BASE_URL + fileId + '/review-complete', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.ok) {
                location.reload();
            } else {
                alert(data.error || STR.error_occurred);
                if (btn) { btn.disabled = false; btn.innerHTML = savedHTML; }
            }
        } catch {
            alert(STR.network_err);
            if (btn) { btn.disabled = false; btn.innerHTML = savedHTML; }
        }
    });
}

// ── 링크 공유 모달 ──────────────────────────────────────────────
const SHARE_BASE = '{{ url("projects/{$project->id}/files") }}/';
let shareFileId = null;

async function openShareModal(fileId, fileName, token, url) {
    shareFileId = fileId;
    document.getElementById('share-filename').textContent = fileName;

    if (token) {
        document.getElementById('share-inactive').style.display = 'none';
        document.getElementById('share-active').style.display   = 'block';
        document.getElementById('share-url-input').value = url;
    } else {
        document.getElementById('share-inactive').style.display = 'block';
        document.getElementById('share-active').style.display   = 'none';
    }

    document.getElementById('share-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function closeShareModal() {
    document.getElementById('share-modal').style.display = 'none';
    document.body.style.overflow = '';
    shareFileId = null;
}

async function generateShareLink() {
    const btn = document.getElementById('share-generate-btn');
    btn.disabled = true; btn.textContent = STR.generating;
    try {
        const res  = await fetch(`${SHARE_BASE}${shareFileId}/share`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.ok || !data.active) return;

        logFileAction(shareFileId, 'share');

        // 모달 상태 전환
        document.getElementById('share-inactive').style.display = 'none';
        document.getElementById('share-active').style.display   = 'block';
        document.getElementById('share-url-input').value = data.url;

        // 파일 행 버튼 업데이트
        updateShareBtn(shareFileId, true, data.token, data.url);
    } finally {
        btn.disabled = false; btn.textContent = STR.generate_link;
    }
}

async function revokeShareLink() {
    if (!await __confirm(STR.revoke_confirm)) return;
    const res  = await fetch(`${SHARE_BASE}${shareFileId}/share`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
    });
    const data = await res.json();
    if (!data.ok) return;

    document.getElementById('share-active').style.display   = 'none';
    document.getElementById('share-inactive').style.display = 'block';
    updateShareBtn(shareFileId, false, '', '');
}

async function copyShareLink() {
    const url = document.getElementById('share-url-input').value;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('share-copy-btn');
        const orig = btn.textContent;
        btn.textContent = STR.copied;
        btn.style.background = '#059669';
        setTimeout(() => { btn.textContent = orig; btn.style.background = '#6366f1'; }, 2000);
    }).catch(() => {
        document.getElementById('share-url-input').select();
        document.execCommand('copy');
    });
}

async function updateShareBtn(fileId, active, token, url) {
    const btn = document.getElementById(`share-btn-${fileId}`);
    if (!btn) return;
    btn.textContent = active ? STR.sharing_now : STR.share_link;
    btn.style.color = active ? '#7c3aed' : '#9ca3af';
    btn.setAttribute('onmouseout', `this.style.color='${active ? '#7c3aed' : '#9ca3af'}'`);
    // onclick 재설정
    btn.onclick = () => openShareModal(fileId, document.getElementById('share-filename')?.textContent || '', token, url);
}

document.getElementById('share-modal').addEventListener('click', async function(e) {
    if (e.target === this) closeShareModal();
});
</script>

{{-- 복사 모달 --}}
<div id="copy-modal" onclick="if(event.target===this)closeCopyModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1200;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:420px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f4f4f5;">
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('team.copy_modal_title') }}</h3>
            <button onclick="closeCopyModal()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;">×</button>
        </div>
        <div style="padding:20px 22px 24px;display:flex;flex-direction:column;gap:12px;">
            <div>
                <p id="copy-filename" style="font-size:13px;color:#374151;font-weight:500;margin:0 0 14px;word-break:break-all;"></p>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('team.copy_project_label') }} <span style="color:#ef4444;">*</span></label>
                <select id="copy-target-project" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    <option value="">{{ __('team.copy_project_placeholder') }}</option>
                    @foreach($copyableProjects as $cp)
                    <option value="{{ $cp->id }}">{{ $cp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="closeCopyModal()" style="padding:8px 16px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button id="copy-submit-btn" onclick="submitCopy()" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#0891b2;border:none;border-radius:8px;cursor:pointer;">{{ __('team.copy_btn') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
let _copyFileId = null;
const COPY_BASE = '{{ route("projects.files.index", $project) }}/';

async function openCopyModal(fileId, fileName) {
    _copyFileId = fileId;
    document.getElementById('copy-filename').textContent = fileName;
    document.getElementById('copy-target-project').value = '';
    document.getElementById('copy-modal').style.display = 'flex';
}
async function closeCopyModal() {
    document.getElementById('copy-modal').style.display = 'none';
    _copyFileId = null;
}
async function submitCopy() {
    const projectId = document.getElementById('copy-target-project').value;
    if (!projectId) { alert(STR.select_project_first); return; }

    const btn = document.getElementById('copy-submit-btn');
    btn.disabled = true;
    btn.textContent = STR.copying;

    const res = await fetch(COPY_BASE + _copyFileId + '/copy', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ target_project_id: projectId }),
    });
    const d = await res.json().catch(() => ({}));

    btn.disabled = false;
    btn.textContent = STR.copy_btn;

    if (!res.ok || !d.ok) { alert(d.message || STR.copy_fail); return; }

    closeCopyModal();
    const t = document.createElement('div');
    t.textContent = `"${d.project_name}" ${STR.copy_success}`;
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:99999;color:#fff;background:#0891b2;';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2800);
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCopyModal(); });
</script>

<script>
const FILE_LOG_BASE = '{{ url("projects/{$project->id}/files") }}/';
const _fileActionLogCache = {};

async function logFileAction(fileId, action) {
    fetch(`${FILE_LOG_BASE}${fileId}/log-action`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ action }),
    }).then(() => { delete _fileActionLogCache[fileId + '_' + action]; }).catch(() => {});
}

async function showFileActionTooltip(event, fileId, action) {
    hideFileActionTooltip();
    const el = event.currentTarget;
    const rect = el.getBoundingClientRect();

    const tt = document.createElement('div');
    tt.id = 'file-action-tooltip';
    tt.style.cssText = 'position:fixed;z-index:9999;background:#1e293b;color:#f1f5f9;border-radius:8px;padding:10px 14px;font-size:11px;line-height:1.8;box-shadow:0 4px 20px rgba(0,0,0,.35);pointer-events:none;white-space:nowrap;';

    const cacheKey = fileId + '_' + action;
    tt.innerHTML = _fileActionLogCache[cacheKey] !== undefined
        ? _renderActionLog(_fileActionLogCache[cacheKey], action)
        : '<span style="color:#94a3b8">' + STR.tooltip_loading + '</span>';
    document.body.appendChild(tt);
    _positionTooltip(tt, rect);

    if (_fileActionLogCache[cacheKey] !== undefined) return;

    try {
        const res = await fetch(`${FILE_LOG_BASE}${fileId}/action-logs?action=${action}`);
        const data = await res.json();
        _fileActionLogCache[cacheKey] = data;
        const existing = document.getElementById('file-action-tooltip');
        if (existing) {
            existing.innerHTML = _renderActionLog(data, action);
            _positionTooltip(existing, rect);
        }
    } catch {
        const existing = document.getElementById('file-action-tooltip');
        if (existing) existing.innerHTML = '<span style="color:#f87171">' + STR.tooltip_load_failed + '</span>';
    }
}

async function _positionTooltip(tt, rect) {
    const ttH = tt.offsetHeight, ttW = tt.offsetWidth;
    let top = rect.top - ttH - 8;
    let left = rect.left + rect.width / 2 - ttW / 2;
    if (top < 8) top = rect.bottom + 8;
    if (left < 8) left = 8;
    if (left + ttW > window.innerWidth - 8) left = window.innerWidth - ttW - 8;
    tt.style.top = top + 'px';
    tt.style.left = left + 'px';
}

function _renderActionLog(logs, action) {
    const labels = { review_request: STR.log_review_request, download: STR.log_download, copy: STR.log_copy, view: STR.log_view, share: STR.log_share };
    const label = labels[action] || action;
    if (!logs.length) return `<span style="color:#94a3b8">${escHtml(STR.log_no_history.replace(':label', label))}</span>`;
    const header = `<div style="color:#94a3b8;font-size:10px;font-weight:700;letter-spacing:.05em;margin-bottom:5px;border-bottom:1px solid #334155;padding-bottom:3px;">${escHtml(STR.log_history_header.replace(':label', label))}</div>`;
    const rows = logs.map(l =>
        `<div><span style="color:#e2e8f0;font-weight:600">${l.user_name}</span>&nbsp;<span style="color:#64748b">${l.created_at}</span></div>`
    ).join('');
    return header + rows;
}

async function hideFileActionTooltip() {
    const tt = document.getElementById('file-action-tooltip');
    if (tt) tt.remove();
}

async function showDescTooltip(event, text) {
    hideDescTooltip();
    const el = event.currentTarget;
    const rect = el.getBoundingClientRect();

    const tt = document.createElement('div');
    tt.id = 'desc-tooltip';
    tt.textContent = text;
    tt.style.cssText = 'position:fixed;z-index:9999;background:#1e293b;color:#f1f5f9;border-radius:8px;padding:10px 14px;font-size:12px;line-height:1.7;max-width:320px;white-space:pre-wrap;word-break:break-word;box-shadow:0 4px 20px rgba(0,0,0,.35);pointer-events:none;';
    document.body.appendChild(tt);

    const ttH = tt.offsetHeight, ttW = tt.offsetWidth;
    let top = rect.top - ttH - 8;
    let left = rect.left + rect.width / 2 - ttW / 2;
    if (top < 8) top = rect.bottom + 8;
    if (left < 8) left = 8;
    if (left + ttW > window.innerWidth - 8) left = window.innerWidth - ttW - 8;
    tt.style.top = top + 'px';
    tt.style.left = left + 'px';
}

async function hideDescTooltip() {
    const tt = document.getElementById('desc-tooltip');
    if (tt) tt.remove();
}

// ── 파일 수정 모달 ────────────────────────────────────────────────
let _editFileId      = null;
let _editOrigProject = null;

async function openEditModalFromEl(btn) {
    openEditModal(
        parseInt(btn.dataset.fileId),
        btn.dataset.fileName,
        btn.dataset.fileDesc,
        btn.dataset.fileSubtask || null,
        parseInt(btn.dataset.fileProject)
    );
}

async function openEditModal(id, name, desc, subTaskId, projectId) {
    _editFileId      = id;
    _editOrigProject = projectId;

    document.getElementById('edit-name').value          = name;
    document.getElementById('edit-desc').value          = desc || '';
    document.getElementById('edit-project-sel').value   = projectId;
    document.getElementById('edit-subtask-sel').value   = subTaskId || '';
    document.getElementById('edit-project-hint').style.display = 'none';
    document.getElementById('edit-status').textContent  = '';

    const btn = document.getElementById('edit-submit-btn');
    btn.disabled    = false;
    btn.textContent = STR.edit_save;

    document.getElementById('edit-file-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('edit-name').focus(), 60);
}

async function closeEditModal() {
    document.getElementById('edit-file-modal').style.display = 'none';
    _editFileId = _editOrigProject = null;
}

async function onEditProjectChange() {
    const sel  = document.getElementById('edit-project-sel');
    const hint = document.getElementById('edit-project-hint');
    const changed = parseInt(sel.value) !== _editOrigProject;
    hint.style.display = changed ? 'block' : 'none';
    if (changed) document.getElementById('edit-subtask-sel').value = '';
}

async function submitFileEdit() {
    if (!_editFileId) return;

    const name = document.getElementById('edit-name').value.trim();
    if (!name) {
        document.getElementById('edit-status').textContent = STR.edit_name_required;
        document.getElementById('edit-status').style.color = '#ef4444';
        return;
    }

    const btn    = document.getElementById('edit-submit-btn');
    const status = document.getElementById('edit-status');
    btn.disabled    = true;
    btn.textContent = STR.edit_saving;
    status.textContent = '';

    const body = {
        original_name: name,
        description:   document.getElementById('edit-desc').value.trim() || null,
        sub_task_id:   document.getElementById('edit-subtask-sel').value || null,
        project_id:    parseInt(document.getElementById('edit-project-sel').value) || null,
    };

    try {
        const res = await fetch(FILE_UPDATE_BASE + _editFileId, {
            method:  'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(body),
        });
        const d = await res.json();

        if (d.ok) {
            closeEditModal();
            window.location.reload();
        } else {
            status.textContent = d.message || STR.edit_save_failed;
            status.style.color = '#ef4444';
            btn.disabled    = false;
            btn.textContent = STR.edit_save;
        }
    } catch {
        status.textContent = STR.edit_error;
        status.style.color = '#ef4444';
        btn.disabled    = false;
        btn.textContent = STR.edit_save;
    }
}

document.getElementById('edit-file-modal')
    ?.addEventListener('click', async function (e) {
        if (e.target === this) closeEditModal();
    });

// Auto-open modal when redirected from old preview URL (?preview=fileId)
(async function () {
    const previewId = new URLSearchParams(window.location.search).get('preview');
    if (previewId) {
        setTimeout(() => openPreview(parseInt(previewId, 10), {{ $project->id }}), 0);
    }
})();
</script>
@endsection
