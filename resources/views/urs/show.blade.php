@extends('layouts.app')
@section('title', $project->name . ' — URS')

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('urs.breadcrumb_projects') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">URS</span>
@endsection

@section('header-actions')@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'urs'])

@php
$statusMap = [
    'draft'          => ['label'=>__('urs.status_draft'),          'bg'=>'#f1f5f9','color'=>'#64748b'],
    'qa_in_progress' => ['label'=>__('urs.status_qa_in_progress'), 'bg'=>'#eff6ff','color'=>'#2563eb'],
    'generating'     => ['label'=>__('urs.status_generating'),     'bg'=>'#fef9c3','color'=>'#ca8a04'],
    'completed'      => ['label'=>__('urs.status_completed'),       'bg'=>'#dcfce7','color'=>'#16a34a'],
];
$st = $statusMap[$urs->status] ?? $statusMap['draft'];
@endphp

<div style="max-width:1600px;margin:0 auto;">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div>
                <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">{{ __('urs.page_title') }}</h1>
                <div style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $project->name }}</div>
            </div>
            <span style="font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;background:{{ $st['bg'] }};color:{{ $st['color'] }};">{{ $st['label'] }}</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            {{-- 서버사이드: 이미 완성된 경우 즉시 표시 --}}
            <div id="download-btn-wrap" style="display:{{ ($urs->status === 'completed' && $urs->content) ? 'flex' : 'none' }};align-items:center;gap:8px;">
                @if($urs->status === 'completed' && $urs->content)
                {{-- Word 다운로드 (언어 선택 드롭다운) --}}
                <div style="position:relative;" id="word-dl-wrap">
                    <button onclick="toggleWordLangMenu(event)"
                        style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;"
                        onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('urs.download_word') }}
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="word-lang-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.12);min-width:160px;z-index:100;overflow:hidden;">
                        <div style="padding:6px 0;">
                            <a href="{{ route('projects.urs.download.word', [$project, $urs]) }}?lang=ko"
                               style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;color:#374151;text-decoration:none;"
                               onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                <span style="font-size:15px;">🇰🇷</span> {{ __('urs.lang_korean') }}
                            </a>
                            <div id="en-dl-item" style="position:relative;">
                                @if($urs->content_en)
                                <a href="{{ route('projects.urs.download.word', [$project, $urs]) }}?lang=en"
                                   style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;color:#374151;text-decoration:none;"
                                   onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                    <span style="font-size:15px;">🇺🇸</span> {{ __('urs.lang_english') }}
                                </a>
                                @else
                                <button onclick="generateEnglishTranslation()" id="btn-gen-en"
                                    style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;color:#6b7280;background:none;border:none;width:100%;cursor:pointer;text-align:left;"
                                    onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                    <span style="font-size:15px;">🇺🇸</span>
                                    <span id="en-btn-label">{{ __('urs.lang_english') }} <span style="font-size:10px;color:#9ca3af;">{{ __('urs.generate_translation') }}</span></span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('projects.urs.download.pdf', [$project, $urs]) }}"
                   style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"
                   onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('urs.download_pdf') }}
                </a>
                @endif
            </div>
            <button onclick="resetURS()"
                    style="font-size:12px;color:#9ca3af;background:none;border:1px solid #e5e7eb;border-radius:7px;padding:5px 12px;cursor:pointer;"
                    onmouseover="this.style.color='#ef4444';this.style.borderColor='#fca5a5'"
                    onmouseout="this.style.color='#9ca3af';this.style.borderColor='#e5e7eb'">{{ __('common.reset') }}</button>
        </div>
    </div>

    {{-- ── 웍스 Q&A 섹션 ── --}}
    <div id="qa-section" style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:24px;margin-bottom:20px;{{ $urs->status === 'completed' ? 'display:none;' : '' }}">

        <div style="display:flex;align-items:center;gap:8px;margin-bottom:18px;">
            <div style="width:28px;height:28px;background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.414 2.798H4.213c-1.444 0-2.414-1.798-1.414-2.798L4.8 15.3"/></svg>
            </div>
            <span style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('urs.qa_section_title') }}</span>
            <span id="qa-progress-badge" style="font-size:11px;padding:2px 9px;border-radius:20px;background:#ede9fe;color:var(--t600);font-weight:600;display:none;"></span>
        </div>

        @if($urs->status === 'draft')
        {{-- 시작 화면 --}}
        <div id="qa-start-panel" style="text-align:center;padding:20px 0;">
            @if($planningDoc && $planningDoc->content)
            <div style="font-size:13px;color:#6b7280;margin-bottom:18px;line-height:1.7;">
                {!! __('urs.qa_intro_with_doc', ['title' => e($planningDoc->title)]) !!}
            </div>
            @else
            <div style="font-size:13px;color:#6b7280;margin-bottom:18px;line-height:1.7;">
                {!! __('urs.qa_intro_without_doc') !!}
            </div>
            @endif
            <button onclick="startQA()"
                    id="btn-start-qa"
                    style="padding:11px 28px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                {{ __('urs.start_qa_button') }}
            </button>
        </div>
        @elseif($urs->status === 'qa_in_progress' || $urs->status === 'generating')
        {{-- Q&A 진행 패널 --}}
        <div id="qa-start-panel" style="display:none;"></div>
        @endif

        {{-- 로딩 --}}
        <div id="qa-loading" style="display:none;text-align:center;padding:24px 0;">
            <div style="display:inline-flex;align-items:center;gap:12px;color:#7c3aed;font-size:13px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                </svg>
                {{ __('urs.qa_generating_questions') }}
            </div>
        </div>

        {{-- Q&A 질문 패널 --}}
        <div id="qa-panel" style="{{ ($urs->status === 'qa_in_progress') ? '' : 'display:none;' }}">
            <div id="qa-history" style="max-height:280px;overflow-y:auto;margin-bottom:16px;display:flex;flex-direction:column;gap:12px;"></div>

            <div id="current-question-wrap" style="background:#f8f7ff;border:1px solid #e8e3ff;border-radius:12px;padding:18px;">
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
                    <div style="width:24px;height:24px;background:var(--t600);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                        <span id="qa-q-num" style="font-size:11px;font-weight:700;color:#fff;">1</span>
                    </div>
                    <p id="qa-question-text" style="font-size:14px;font-weight:600;color:#1e1b2e;margin:0;line-height:1.6;flex:1;"></p>
                </div>

                <div style="margin-bottom:12px;">
                    <div style="font-size:11px;font-weight:600;color:#7c3aed;margin-bottom:5px;display:flex;align-items:center;gap:4px;">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/></svg>
                        {{ __('urs.qa_ai_suggestion_label') }}
                    </div>
                    <div id="qa-ai-suggestion" style="background:#fff;border:1px solid #e8e3ff;border-radius:8px;padding:10px 12px;font-size:13px;color:#374151;line-height:1.6;"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <div style="font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('urs.qa_my_answer_label') }}</div>
                    <textarea id="qa-answer-input"
                              rows="3"
                              placeholder="{{ __('urs.qa_answer_placeholder') }}"
                              style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;resize:vertical;outline:none;box-sizing:border-box;font-family:inherit;"
                              onfocus="this.style.borderColor='#7c3aed'"
                              onblur="this.style.borderColor='#e5e7eb'"></textarea>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button onclick="useAiSuggestion()"
                            style="padding:8px 16px;background:#f5f3ff;color:#7c3aed;border:1px solid #e8e3ff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                        {{ __('urs.qa_use_suggestion') }}
                    </button>
                    <button onclick="submitAnswer()"
                            id="btn-next"
                            style="padding:8px 20px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                        {{ __('urs.qa_next') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- 모든 질문 완료 → URS 생성 대기 --}}
        <div id="qa-done-panel" style="{{ ($urs->status === 'generating') ? '' : 'display:none;' }}">
            <div style="text-align:center;padding:20px 0;">
                <div style="font-size:32px;margin-bottom:10px;">✅</div>
                <div style="font-size:14px;font-weight:600;color:#1e1b2e;margin-bottom:6px;">{{ __('urs.qa_all_done') }}</div>
                <div style="font-size:13px;color:#6b7280;margin-bottom:20px;">{{ __('urs.qa_all_done_hint') }}</div>
                <button onclick="generateURS()" id="btn-generate"
                        style="padding:11px 28px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    {{ __('urs.generate_urs_button') }}
                </button>
            </div>
        </div>

        {{-- 생성 중 --}}
        <div id="generating-panel" style="display:none;text-align:center;padding:24px 0;">
            <div style="display:inline-flex;align-items:center;gap:12px;color:#7c3aed;font-size:13px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                </svg>
                {{ __('urs.generating_urs') }}
            </div>
        </div>
    </div>

    {{-- ── URS 탭 섹션 ── --}}
    <div id="urs-tabs-section" style="background:#fff;border:1px solid #f0eeff;border-radius:14px;overflow:hidden;">

        {{-- 탭 헤더 --}}
        <div style="display:flex;align-items:stretch;border-bottom:1px solid #f0eeff;">
            <button id="tab-view-btn" onclick="switchTab('view')"
                    style="flex:1;padding:13px 20px;background:#fff;border:none;border-right:1px solid #f0eeff;font-size:13px;font-weight:600;color:var(--t600);cursor:pointer;border-bottom:2px solid var(--t500);transition:all .15s;">
                {{ __('urs.tab_view') }}
            </button>
            <button id="tab-edit-btn" onclick="switchTab('edit')"
                    style="flex:1;padding:13px 20px;background:#fafafa;border:none;font-size:13px;font-weight:500;color:#6b7280;cursor:pointer;border-bottom:2px solid transparent;transition:all .15s;">
                {{ __('urs.tab_edit') }}
            </button>
        </div>

        {{-- ── URS 보기 탭 ── --}}
        <div id="tab-view" style="min-height:400px;">

            {{-- 보기 모드 토글 --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid #f8f7ff;background:#faf9ff;">
                <span style="font-size:12px;color:#6b7280;">{{ __('urs.view_mode') }}</span>
                <div style="display:flex;background:#f0eeff;border-radius:8px;padding:2px;gap:4px;">
                    <button id="view-mode-full-btn" onclick="setViewMode('full')"
                            style="padding:5px 14px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:var(--t600);color:#fff;transition:all .15s;">
                        {{ __('urs.view_mode_full') }}
                    </button>
                    <button id="view-mode-section-btn" onclick="setViewMode('section')"
                            style="padding:5px 14px;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;background:transparent;color:#7c3aed;transition:all .15s;">
                        {{ __('urs.view_mode_section') }}
                    </button>
                </div>
            </div>

            {{-- 전체 보기 --}}
            <div id="view-full" style="padding:28px 32px;">
                <div id="view-empty-hint" style="text-align:center;padding:60px 20px;color:#9ca3af;{{ ($urs->content) ? 'display:none;' : '' }}">
                    <div style="font-size:40px;margin-bottom:12px;">📋</div>
                    <div style="font-size:14px;">{{ __('urs.empty_title') }}</div>
                    <div style="font-size:12px;margin-top:6px;">{{ __('urs.empty_hint') }}</div>
                </div>
                <div id="urs-rendered" class="urs-markdown-body"></div>
            </div>

            {{-- 단락별 보기 --}}
            <div id="view-section" style="display:none;padding:16px 20px;">
                <div id="section-view-list"></div>
            </div>
        </div>

        {{-- ── URS 수정 탭 ── --}}
        <div id="tab-edit" style="display:none;min-height:400px;">

            {{-- 편집 모드 토글 --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid #f8f7ff;background:#faf9ff;">
                <span style="font-size:12px;color:#6b7280;">{{ __('urs.edit_mode') }}</span>
                <div style="display:flex;background:#f0eeff;border-radius:8px;padding:2px;gap:4px;">
                    <button id="edit-mode-full-btn" onclick="setEditMode('full')"
                            style="padding:5px 14px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:var(--t600);color:#fff;transition:all .15s;">
                        {{ __('urs.edit_mode_full') }}
                    </button>
                    <button id="edit-mode-section-btn" onclick="setEditMode('section')"
                            style="padding:5px 14px;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;background:transparent;color:#7c3aed;transition:all .15s;">
                        {{ __('urs.edit_mode_section') }}
                    </button>
                </div>
            </div>

            {{-- 전체 편집 --}}
            <div id="edit-full" style="padding:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <span style="font-size:12px;color:#9ca3af;">{{ __('urs.edit_full_hint') }}</span>
                    <button onclick="saveMarkdown()" id="btn-save-md"
                            style="padding:7px 18px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.save') }}</button>
                </div>
                <textarea id="urs-editor" rows="32" placeholder="{{ __('urs.editor_placeholder') }}"
                          style="width:100%;padding:14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:'JetBrains Mono','Fira Code',monospace;color:#374151;resize:vertical;outline:none;box-sizing:border-box;line-height:1.7;"
                          onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">{{ $urs->content ?? '' }}</textarea>
            </div>

            {{-- 단락별 편집 --}}
            <div id="edit-section" style="display:none;padding:16px 20px;">
                <div id="section-edit-list"></div>
                <button onclick="saveAllSections()" id="btn-save-all-sections"
                        style="margin-top:14px;width:100%;padding:11px;background:var(--t600);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    {{ __('urs.save_all') }}
                </button>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* Markdown 렌더링 스타일 */
.urs-markdown-body { font-size:14px; color:#374151; line-height:1.8; }
.urs-markdown-body h1 { font-size:22px; font-weight:800; color:#111827; border-bottom:2px solid #ede9fe; padding-bottom:10px; margin:0 0 20px; }
.urs-markdown-body h2 { font-size:16px; font-weight:700; color:#1e1b2e; border-bottom:1px solid #f0eeff; padding-bottom:6px; margin:28px 0 12px; }
.urs-markdown-body h3 { font-size:14px; font-weight:700; color:#374151; margin:18px 0 8px; }
.urs-markdown-body h4 { font-size:13px; font-weight:700; color:#4b5563; margin:14px 0 6px; }
.urs-markdown-body p  { margin:0 0 10px; }
.urs-markdown-body ul, .urs-markdown-body ol { padding-left:22px; margin:0 0 10px; }
.urs-markdown-body li { margin-bottom:4px; }
.urs-markdown-body strong { font-weight:700; color:#111827; }
.urs-markdown-body code { background:#f3f4f6; padding:1px 6px; border-radius:4px; font-size:12px; font-family:monospace; color:#7c3aed; }
.urs-markdown-body pre  { background:#1e1b2e; color:#e2e8f0; padding:14px 18px; border-radius:10px; overflow-x:auto; margin:0 0 14px; }
.urs-markdown-body pre code { background:none; color:inherit; padding:0; }
.urs-markdown-body blockquote { border-left:3px solid #c4b5fd; padding:8px 16px; background:#faf9ff; color:#6b7280; margin:0 0 12px; border-radius:0 6px 6px 0; font-size:13px; }
.urs-markdown-body table { width:100%; border-collapse:collapse; margin:0 0 14px; font-size:13px; }
.urs-markdown-body th { background:#f5f3ff; padding:8px 12px; text-align:left; font-weight:700; border:1px solid #e8e3ff; color:#4c1d95; }
.urs-markdown-body td { padding:8px 12px; border:1px solid #f0eeff; }
.urs-markdown-body tr:nth-child(even) td { background:#faf9ff; }
.urs-markdown-body hr { border:none; border-top:1px solid #f0eeff; margin:20px 0; }
</style>

<script>
// 번역 문자열 (서버 렌더링)
const URS_I18N = {
    save:              @json(__('common.save')),
    saving:            @json(__('common.saving')),
    saved_check:       @json(__('urs.saved_check')),
    save_all:          @json(__('urs.save_all')),
    saved_all_check:   @json(__('urs.saved_all_check')),
    no_content:        @json(__('urs.no_content')),
    section_preamble:  @json(__('urs.section_preamble')),
    generating_short:  @json(__('urs.generating_short')),
    generate_urs:      @json(__('urs.generate_urs_button')),
    start_qa:          @json(__('urs.start_qa_button')),
    qa_next:           @json(__('urs.qa_next')),
    qa_done_badge:     @json(__('urs.qa_done_badge')),
    qa_q_prefix:       @json(__('urs.qa_question_prefix')),
    translating:       @json(__('urs.translating')),
    translation_failed:@json(__('urs.translation_failed')),
    lang_english:      @json(__('urs.lang_english')),
    error_prefix:      @json(__('urs.error_prefix')),
    generate_error:    @json(__('urs.generate_error_prefix')),
    default_error:     @json(__('urs.default_error')),
    save_failed:       @json(__('urs.save_failed')),
    generate_failed:   @json(__('urs.generate_failed')),
    question_gen_failed:@json(__('urs.question_gen_failed')),
    reset_failed:      @json(__('urs.reset_failed')),
    confirm_reset:     @json(__('urs.confirm_reset')),
};

const URS_ID        = {{ $urs->id }};
const PROJECT_ID    = {{ $project->id }};
const START_URL     = '{{ route("projects.urs.qa.start",    [$project, $urs]) }}';
const ANSWER_URL    = '{{ route("projects.urs.qa.answer",   [$project, $urs]) }}';
const GENERATE_URL  = '{{ route("projects.urs.generate",    [$project, $urs]) }}';
const UPDATE_URL    = '{{ route("projects.urs.update",      [$project, $urs]) }}';
const RESET_URL     = '{{ route("projects.urs.reset",       [$project, $urs]) }}';
const WORD_URL         = '{{ route("projects.urs.download.word",  [$project, $urs]) }}';
const PDF_URL          = '{{ route("projects.urs.download.pdf",   [$project, $urs]) }}';
const TRANSLATE_EN_URL = '{{ route("projects.urs.translate-en",   [$project, $urs]) }}';
const CSRF             = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Word 언어 선택 드롭다운 ─────────────────────────────────────
function toggleWordLangMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('word-lang-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', () => {
    const menu = document.getElementById('word-lang-menu');
    if (menu) menu.style.display = 'none';
});

async function generateEnglishTranslation() {
    const btn = document.getElementById('btn-gen-en');
    const label = document.getElementById('en-btn-label');
    if (!btn || btn.disabled) return;

    btn.disabled = true;
    label.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg> ' + URS_I18N.translating;

    try {
        const res = await fetch(TRANSLATE_EN_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.message ?? URS_I18N.default_error);

        const item = document.getElementById('en-dl-item');
        item.innerHTML = `<a href="${WORD_URL}?lang=en"
            style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;color:#374151;text-decoration:none;"
            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
            <span style="font-size:15px;">🇺🇸</span> English
        </a>`;
    } catch (err) {
        label.innerHTML = URS_I18N.lang_english + ' <span style="font-size:10px;color:#ef4444;">' + URS_I18N.translation_failed + '</span>';
        btn.disabled = false;
    }
}

let currentIndex      = {{ $urs->current_q_index }};
let currentSuggestion = '';
let ursStatus         = '{{ $urs->status }}';
let currentSections   = [];
let currentViewMode   = 'full';
let currentEditMode   = 'full';

// ── 초기 렌더링 ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    @if($urs->status === 'qa_in_progress' && $urs->qa_questions)
    restoreQAState();
    @endif

    @if($urs->content)
    const initialMd = {{ Js::from($urs->content) }};
    currentSections = parseMarkdownSections(initialMd);
    renderMarkdown(initialMd);
    @endif

    @if($urs->status === 'completed' && $urs->content)
    document.getElementById('qa-section').style.display = 'none';
    @endif
});

// ── 섹션 파싱 / 조합 ─────────────────────────────────────
function parseMarkdownSections(md) {
    if (!md) return [];
    const lines    = md.split('\n');
    const sections = [];
    let current    = null;

    for (const line of lines) {
        if (line.startsWith('## ')) {
            if (current) sections.push({ title: current.title, content: current.lines.join('\n').trimEnd() });
            current = { title: line.slice(3).trim(), lines: [] };
        } else {
            if (!current) current = { title: '__preamble__', lines: [] };
            current.lines.push(line);
        }
    }
    if (current) sections.push({ title: current.title, content: current.lines.join('\n').trimEnd() });

    return sections.filter(s => s.title !== '__preamble__' || s.content.trim());
}

function rebuildMarkdown(sections) {
    return sections.map(s =>
        s.title === '__preamble__' ? s.content.trim() : `## ${s.title}\n\n${s.content.trim()}`
    ).join('\n\n');
}

// ── 보기 모드 토글 ────────────────────────────────────────
async function setViewMode(mode) {
    currentViewMode = mode;
    const fullDiv = document.getElementById('view-full');
    const secDiv  = document.getElementById('view-section');
    const fullBtn = document.getElementById('view-mode-full-btn');
    const secBtn  = document.getElementById('view-mode-section-btn');

    if (mode === 'full') {
        fullDiv.style.display = 'block'; secDiv.style.display = 'none';
        setToggleActive(fullBtn, secBtn);
    } else {
        fullDiv.style.display = 'none'; secDiv.style.display = 'block';
        setToggleActive(secBtn, fullBtn);
        renderSectionView(currentSections);
    }
}

// ── 편집 모드 토글 ────────────────────────────────────────
async function setEditMode(mode) {
    currentEditMode = mode;
    const fullDiv = document.getElementById('edit-full');
    const secDiv  = document.getElementById('edit-section');
    const fullBtn = document.getElementById('edit-mode-full-btn');
    const secBtn  = document.getElementById('edit-mode-section-btn');

    if (mode === 'full') {
        fullDiv.style.display = 'block'; secDiv.style.display = 'none';
        setToggleActive(fullBtn, secBtn);
    } else {
        const md = document.getElementById('urs-editor').value;
        currentSections = parseMarkdownSections(md);
        fullDiv.style.display = 'none'; secDiv.style.display = 'block';
        setToggleActive(secBtn, fullBtn);
        renderSectionEdit(currentSections);
    }
}

async function setToggleActive(activeBtn, inactiveBtn) {
    activeBtn.style.background   = 'var(--t600)';
    activeBtn.style.color        = '#fff';
    activeBtn.style.fontWeight   = '600';
    inactiveBtn.style.background = 'transparent';
    inactiveBtn.style.color      = '#7c3aed';
    inactiveBtn.style.fontWeight = '500';
}

// ── 섹션 보기 렌더링 ──────────────────────────────────────
async function renderSectionView(sections) {
    const list = document.getElementById('section-view-list');
    if (!list) return;
    if (!sections || sections.length === 0) {
        list.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#9ca3af;">' + escHtml(URS_I18N.no_content) + '</div>';
        return;
    }
    list.innerHTML = sections.map((s, i) => {
        const title = s.title === '__preamble__' ? URS_I18N.section_preamble : s.title;
        const html  = renderMdToHtml(s.content);
        return `<div style="border:1px solid #e8e3ff;border-radius:10px;margin-bottom:8px;overflow:hidden;">
            <div onclick="toggleSectionView(${i})"
                 style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8f7ff;cursor:pointer;user-select:none;">
                <span style="font-size:14px;font-weight:600;color:#1e1b2e;">${escHtml(title)}</span>
                <svg id="sv-arrow-${i}" width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"
                     style="transition:transform .2s;flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div id="sv-content-${i}" style="display:none;padding:20px 24px;" class="urs-markdown-body">${html}</div>
        </div>`;
    }).join('');
}

async function toggleSectionView(i) {
    const el    = document.getElementById(`sv-content-${i}`);
    const arrow = document.getElementById(`sv-arrow-${i}`);
    if (!el) return;
    const open = el.style.display === 'none';
    el.style.display = open ? 'block' : 'none';
    if (arrow) arrow.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}

// ── 섹션 편집 렌더링 ──────────────────────────────────────
async function renderSectionEdit(sections) {
    const list = document.getElementById('section-edit-list');
    if (!list) return;
    if (!sections || sections.length === 0) {
        list.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#9ca3af;">' + escHtml(URS_I18N.no_content) + '</div>';
        return;
    }
    list.innerHTML = sections.map((s, i) => {
        const label = s.title === '__preamble__' ? URS_I18N.section_preamble : `## ${s.title}`;
        return `<div style="border:1px solid #e8e3ff;border-radius:10px;margin-bottom:12px;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:#f8f7ff;border-bottom:1px solid #e8e3ff;">
                <span style="font-size:13px;font-weight:600;color:#1e1b2e;">${escHtml(label)}</span>
                <button onclick="saveSection(${i})" id="btn-save-sec-${i}"
                        style="padding:4px 12px;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">
                    ${escHtml(URS_I18N.save)}
                </button>
            </div>
            <div style="padding:12px 16px;">
                <textarea id="se-ta-${i}" rows="8"
                          style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:'JetBrains Mono','Fira Code',monospace;color:#374151;resize:vertical;outline:none;box-sizing:border-box;line-height:1.7;"
                          onfocus="this.style.borderColor='#7c3aed'"
                          onblur="this.style.borderColor='#e5e7eb'">${escHtml(s.content)}</textarea>
            </div>
        </div>`;
    }).join('');
}

// ── 개별 섹션 저장 ────────────────────────────────────────
async function saveSection(idx) {
    const ta  = document.getElementById(`se-ta-${idx}`);
    const btn = document.getElementById(`btn-save-sec-${idx}`);
    if (!ta || !btn) return;
    btn.disabled = true; btn.textContent = URS_I18N.saving;
    currentSections[idx].content = ta.value;
    const fullMd = rebuildMarkdown(currentSections);
    document.getElementById('urs-editor').value = fullMd;
    try {
        await saveMdToServer(fullMd);
        renderMarkdown(fullMd);
        btn.textContent = URS_I18N.saved_check;
        setTimeout(() => { btn.disabled = false; btn.textContent = URS_I18N.save; }, 1500);
    } catch(e) {
        btn.disabled = false; btn.textContent = URS_I18N.save;
        alert(URS_I18N.error_prefix + e.message);
    }
}

// ── 전체 섹션 저장 ────────────────────────────────────────
async function saveAllSections() {
    currentSections.forEach((s, i) => {
        const ta = document.getElementById(`se-ta-${i}`);
        if (ta) s.content = ta.value;
    });
    const fullMd = rebuildMarkdown(currentSections);
    document.getElementById('urs-editor').value = fullMd;
    const btn = document.getElementById('btn-save-all-sections');
    btn.disabled = true; btn.textContent = URS_I18N.saving;
    try {
        await saveMdToServer(fullMd);
        renderMarkdown(fullMd);
        btn.textContent = URS_I18N.saved_all_check;
        setTimeout(() => { btn.disabled = false; btn.textContent = URS_I18N.save_all; }, 1500);
    } catch(e) {
        btn.disabled = false; btn.textContent = URS_I18N.save_all;
        alert(URS_I18N.error_prefix + e.message);
    }
}

// ── Markdown 저장 (전체 편집) ─────────────────────────────
async function saveMarkdown() {
    const content = document.getElementById('urs-editor').value;
    const btn     = document.getElementById('btn-save-md');
    btn.disabled  = true; btn.textContent = URS_I18N.saving;
    try {
        await saveMdToServer(content);
        currentSections = parseMarkdownSections(content);
        renderMarkdown(content);
        if (currentViewMode === 'section') renderSectionView(currentSections);
        if (currentEditMode === 'section') renderSectionEdit(currentSections);
        btn.textContent = URS_I18N.saved_check;
        setTimeout(() => { btn.disabled = false; btn.textContent = URS_I18N.save; }, 1500);
    } catch(e) {
        btn.disabled = false; btn.textContent = URS_I18N.save;
        alert(URS_I18N.error_prefix + e.message);
    }
}

// ── 서버 저장 공통 ────────────────────────────────────────
async function saveMdToServer(content) {
    const res = await fetch(UPDATE_URL, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ content }),
    });
    const d = await res.json();
    if (!d.ok) throw new Error(d.message || URS_I18N.save_failed);
    return d;
}

// ── 탭 전환 ──────────────────────────────────────────────
async function switchTab(tab) {
    const viewBtn = document.getElementById('tab-view-btn');
    const editBtn = document.getElementById('tab-edit-btn');
    const viewEl  = document.getElementById('tab-view');
    const editEl  = document.getElementById('tab-edit');

    if (tab === 'view') {
        viewEl.style.display = 'block'; editEl.style.display = 'none';
        viewBtn.style.background = '#fff'; viewBtn.style.color = 'var(--t600)';
        viewBtn.style.borderBottom = '2px solid var(--t500)'; viewBtn.style.fontWeight = '600';
        editBtn.style.background = '#fafafa'; editBtn.style.color = '#6b7280';
        editBtn.style.borderBottom = '2px solid transparent'; editBtn.style.fontWeight = '500';
        // 편집 탭에서 변경된 내용 동기화
        const md = document.getElementById('urs-editor').value;
        if (md) {
            currentSections = parseMarkdownSections(md);
            renderMarkdown(md);
            if (currentViewMode === 'section') renderSectionView(currentSections);
        }
    } else {
        viewEl.style.display = 'none'; editEl.style.display = 'block';
        editBtn.style.background = '#fff'; editBtn.style.color = 'var(--t600)';
        editBtn.style.borderBottom = '2px solid var(--t500)'; editBtn.style.fontWeight = '600';
        viewBtn.style.background = '#fafafa'; viewBtn.style.color = '#6b7280';
        viewBtn.style.borderBottom = '2px solid transparent'; viewBtn.style.fontWeight = '500';
        // 단락별 편집 모드라면 섹션 동기화
        if (currentEditMode === 'section') {
            const md = document.getElementById('urs-editor').value;
            currentSections = parseMarkdownSections(md);
            renderSectionEdit(currentSections);
        }
    }
}

// ── Markdown 렌더링 ───────────────────────────────────────
function renderMarkdown(md) {
    const el   = document.getElementById('urs-rendered');
    const hint = document.getElementById('view-empty-hint');
    if (!md || !md.trim()) {
        if (hint) hint.style.display = 'block';
        if (el)   el.innerHTML = '';
        return;
    }
    if (hint) hint.style.display = 'none';
    if (el)   el.innerHTML = renderMdToHtml(md);
}

function renderMdToHtml(md) {
    if (!md) return '';
    if (typeof marked !== 'undefined') return marked.parse(md);
    return md
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/^#{4} (.+)$/gm,'<h4>$1</h4>')
        .replace(/^#{3} (.+)$/gm,'<h3>$1</h3>')
        .replace(/^#{2} (.+)$/gm,'<h2>$1</h2>')
        .replace(/^# (.+)$/gm,  '<h1>$1</h1>')
        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
        .replace(/`(.+?)`/g,'<code>$1</code>')
        .replace(/^---$/gm,'<hr>')
        .replace(/^> (.+)$/gm,'<blockquote>$1</blockquote>')
        .replace(/^[-*] (.+)$/gm,'<li>$1</li>')
        .replace(/(<li>.*<\/li>)/gs,'<ul>$1</ul>')
        .replace(/\n\n/g,'</p><p>');
}

// ── URS 생성 ─────────────────────────────────────────────
async function generateURS() {
    const btn = document.getElementById('btn-generate');
    btn.disabled = true; btn.textContent = URS_I18N.generating_short;
    document.getElementById('qa-done-panel').style.display = 'none';
    document.getElementById('generating-panel').style.display = 'block';
    try {
        const res = await fetch(GENERATE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const d = await res.json();
        document.getElementById('generating-panel').style.display = 'none';
        if (!d.ok) throw new Error(d.message || URS_I18N.generate_failed);

        document.getElementById('qa-section').style.display = 'none';
        document.getElementById('urs-editor').value = d.content;
        currentSections = parseMarkdownSections(d.content);
        renderMarkdown(d.content);
        if (currentViewMode === 'section') renderSectionView(currentSections);
        if (currentEditMode === 'section') renderSectionEdit(currentSections);
        showDownloadButtons();
        ursStatus = 'completed';
    } catch(e) {
        document.getElementById('generating-panel').style.display = 'none';
        document.getElementById('qa-done-panel').style.display = 'block';
        btn.disabled = false; btn.textContent = URS_I18N.generate_urs;
        alert(URS_I18N.generate_error + e.message);
    }
}

// ── 다운로드 버튼 동적 추가 ──────────────────────────────
async function showDownloadButtons() {
    const container = document.getElementById('download-btn-wrap');
    if (!container || container.querySelector('a')) return;
    container.innerHTML = `
        <a href="${WORD_URL}"
           style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"
           onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Word
        </a>
        <a href="${PDF_URL}"
           style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#dc2626;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;"
           onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            PDF
        </a>`;
    container.style.display = 'flex';
}

// ── Q&A ──────────────────────────────────────────────────
async function restoreQAState() {
    const questions = @json($urs->qa_questions ?? []);
    const idx       = {{ $urs->current_q_index }};
    const historyEl = document.getElementById('qa-history');
    for (let i = 0; i < idx && i < questions.length; i++) {
        const q = questions[i];
        historyEl.appendChild(makeHistoryRow(i + 1, q.q, q.answer || q.ai_suggestion));
    }
    historyEl.scrollTop = historyEl.scrollHeight;
    if (idx >= questions.length) { showDonePanel(); return; }
    const q = questions[idx];
    showQuestion(idx, q.q, q.ai_suggestion, questions.length);
}

function makeHistoryRow(num, question, answer) {
    const div = document.createElement('div');
    div.style.cssText = 'background:#f8f7ff;border:1px solid #e8e3ff;border-radius:8px;padding:10px 14px;';
    div.innerHTML = `
        <div style="font-size:11px;font-weight:700;color:var(--t600);margin-bottom:4px;">${URS_I18N.qa_q_prefix}${num}. ${escHtml(question)}</div>
        <div style="font-size:12px;color:#374151;">${escHtml(answer)}</div>`;
    return div;
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function startQA() {
    const btn = document.getElementById('btn-start-qa');
    btn.disabled = true; btn.textContent = URS_I18N.generating_short;
    document.getElementById('qa-start-panel').style.display = 'none';
    document.getElementById('qa-loading').style.display = 'block';
    try {
        const res = await fetch(START_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const d = await res.json();
        document.getElementById('qa-loading').style.display = 'none';
        if (!d.ok) throw new Error(d.message || URS_I18N.question_gen_failed);
        currentIndex = d.index;
        document.getElementById('qa-panel').style.display = 'block';
        showQuestion(d.index, d.question, d.ai_suggestion, d.total);
        updateProgressBadge(d.index, d.total);
    } catch(e) {
        document.getElementById('qa-loading').style.display = 'none';
        document.getElementById('qa-start-panel').style.display = 'block';
        btn.disabled = false; btn.textContent = URS_I18N.start_qa;
        alert(URS_I18N.error_prefix + e.message);
    }
}

async function showQuestion(index, question, aiSuggestion, total) {
    currentIndex      = index;
    currentSuggestion = aiSuggestion;
    document.getElementById('qa-q-num').textContent         = index + 1;
    document.getElementById('qa-question-text').textContent = question;
    document.getElementById('qa-ai-suggestion').textContent = aiSuggestion;
    document.getElementById('qa-answer-input').value        = '';
    document.getElementById('current-question-wrap').style.display = 'block';
    updateProgressBadge(index, total);
}

async function updateProgressBadge(index, total) {
    const badge = document.getElementById('qa-progress-badge');
    badge.textContent = `${index + 1} / ${total}`;
    badge.style.display = 'inline-block';
}

async function useAiSuggestion() {
    document.getElementById('qa-answer-input').value = currentSuggestion;
}

async function submitAnswer() {
    const answer = document.getElementById('qa-answer-input').value.trim() || currentSuggestion;
    const btn    = document.getElementById('btn-next');
    btn.disabled = true; btn.textContent = URS_I18N.saving;
    try {
        const res = await fetch(ANSWER_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ answer, index: currentIndex }),
        });
        const d = await res.json();
        if (!d.ok) throw new Error(d.message || URS_I18N.save_failed);
        const historyEl = document.getElementById('qa-history');
        const q = document.getElementById('qa-question-text').textContent;
        historyEl.appendChild(makeHistoryRow(currentIndex + 1, q, answer));
        historyEl.scrollTop = historyEl.scrollHeight;
        document.getElementById('qa-answer-input').value = '';
        btn.disabled = false; btn.textContent = URS_I18N.qa_next;
        if (d.done) showDonePanel();
        else        showQuestion(d.index, d.question, d.ai_suggestion, d.total);
    } catch(e) {
        btn.disabled = false; btn.textContent = URS_I18N.qa_next;
        alert(URS_I18N.error_prefix + e.message);
    }
}

async function showDonePanel() {
    document.getElementById('current-question-wrap').style.display = 'none';
    document.getElementById('qa-done-panel').style.display = 'block';
    const badge = document.getElementById('qa-progress-badge');
    badge.textContent = URS_I18N.qa_done_badge;
    badge.style.background = '#dcfce7'; badge.style.color = '#16a34a';
}

// ── 초기화 ───────────────────────────────────────────────
async function resetURS() {
    if (!await __confirm(URS_I18N.confirm_reset)) return;
    const res = await fetch(RESET_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    const d = await res.json();
    if (d.ok) location.reload();
    else alert(URS_I18N.reset_failed);
}

// Enter키로 답변 제출
document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 'Enter') {
        const panel = document.getElementById('current-question-wrap');
        if (panel && panel.style.display !== 'none') submitAnswer();
    }
});
</script>

{{-- marked.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>

@endsection
