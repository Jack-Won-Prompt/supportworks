@extends('layouts.app')
@section('title', __('ai.prompt_library_title'))

@push('styles')
<style>
.pl-wrap { display:flex; height:calc(100vh - 52px); margin:-20px -24px -24px; overflow:hidden; background:#f8fafc; }
.pl-side { width:220px; min-width:220px; background:#fff; border-right:1px solid #e8e3ff; display:flex; flex-direction:column; }
.pl-side-header { padding:14px 14px 10px; border-bottom:1px solid #f0ecff; }
.pl-side-header h2 { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 10px; }
.pl-side-scroll { flex:1; overflow-y:auto; padding:8px 10px 12px; }
.pl-label { font-size:10px; font-weight:700; color:#a1a1aa; letter-spacing:.07em; text-transform:uppercase; padding:8px 4px 4px; }
.pl-nav-item { display:flex; align-items:center; justify-content:space-between; padding:7px 10px; border-radius:8px; cursor:pointer; font-size:12px; color:#64748b; transition:background .12s; }
.pl-nav-item:hover { background:#f8f7ff; }
.pl-nav-item.active { background:#ede9fe; color:#7c3aed; font-weight:600; }
.pl-main { flex:1; display:flex; flex-direction:column; overflow:hidden; }
.pl-toolbar { padding:14px 20px 12px; background:#fff; border-bottom:1px solid #e8e3ff; display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex-shrink:0; }
.pl-search { flex:1; min-width:200px; padding:7px 12px; border:1.5px solid #e5e7eb; border-radius:8px; font-size:13px; outline:none; }
.pl-search:focus { border-color:#7c3aed; }
.pl-content { flex:1; overflow-y:auto; padding:16px 20px; }
.pl-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:14px; }
.pl-card { background:#fff; border:1.5px solid #e8e3ff; border-radius:12px; padding:16px; transition:border-color .15s,box-shadow .15s; }
.pl-card:hover { border-color:#c4b5fd; box-shadow:0 4px 16px rgba(124,58,237,.08); }
.pl-card-header { display:flex; align-items:flex-start; gap:10px; margin-bottom:10px; }
.pl-card-icon { width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg,#ede9fe,#ddd6fe); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pl-card-title { font-size:13px; font-weight:700; color:#1e1b2e; line-height:1.3; }
.pl-card-meta { font-size:11px; color:#94a3b8; margin-top:2px; }
.pl-card-prompt { font-size:12px; color:#64748b; line-height:1.5; max-height:56px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; margin-bottom:12px; }
.pl-card-footer { display:flex; align-items:center; gap:6px; }
.pl-badge { padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; }
.pl-badge-dev { background:#ede9fe; color:#7c3aed; }
.pl-badge-doc { background:#dbeafe; color:#1d4ed8; }
.pl-badge-data { background:#dcfce7; color:#16a34a; }
.pl-badge-etc { background:#f1f5f9; color:#64748b; }
.pl-card-actions { margin-left:auto; display:flex; gap:4px; }
.pl-btn { padding:5px 10px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; border:none; transition:opacity .15s; }
.pl-btn-primary { background:linear-gradient(135deg,#7c3aed,#6366f1); color:#fff; }
.pl-btn-primary:hover { opacity:.88; }
.pl-btn-outline { background:#f8f7ff; color:#7c3aed; border:1px solid #c4b5fd; }
.pl-btn-outline:hover { background:#ede9fe; }
.pl-btn-danger { background:#fee2e2; color:#dc2626; }
.pl-empty { text-align:center; padding:60px 20px; color:#94a3b8; }
.pl-empty-icon { font-size:48px; margin-bottom:12px; opacity:.4; }

/* 모달 */
.pl-modal-bg { display:none; position:fixed; inset:0; background:rgba(15,14,26,.45); z-index:1000; align-items:center; justify-content:center; }
.pl-modal-bg.show { display:flex; }
.pl-modal { background:#fff; border-radius:16px; padding:28px; width:680px; max-width:95vw; max-height:90vh; overflow-y:auto; box-shadow:0 24px 64px rgba(15,14,26,.3); }
.pl-modal h3 { font-size:16px; font-weight:700; color:#1e1b2e; margin:0 0 20px; display:flex; align-items:center; gap:8px; }
.pl-field { margin-bottom:14px; }
.pl-field label { display:block; font-size:12px; font-weight:600; color:#6b7280; margin-bottom:5px; }
.pl-field input, .pl-field textarea, .pl-field select {
    width:100%; padding:8px 12px; border:1.5px solid #e5e7eb; border-radius:8px;
    font-size:13px; color:#1e1b2e; outline:none; background:#fafafa; box-sizing:border-box; transition:border-color .15s;
}
.pl-field input:focus, .pl-field textarea:focus, .pl-field select:focus { border-color:#7c3aed; background:#fff; }
.pl-field textarea { resize:vertical; min-height:80px; }
.pl-row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.pl-confidence { display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#64748b; padding:4px 10px; background:#f8fafc; border-radius:20px; }
.pl-modal-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9; }
.pl-final-prompt { background:#1e1b2e; color:#e9d5ff; border-radius:8px; padding:12px; font-family:monospace; font-size:12px; line-height:1.6; white-space:pre-wrap; position:relative; }
.pl-copy-btn { position:absolute; top:8px; right:8px; background:rgba(124,58,237,.3); color:#e9d5ff; border:none; border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; }
.pl-copy-btn:hover { background:rgba(124,58,237,.5); }
</style>
@endpush

@section('content')
<div class="pl-wrap">

    {{-- 사이드바 --}}
    <div class="pl-side">
        <div class="pl-side-header">
            <h2>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('ai.prompt_library_h2') }}
            </h2>
            <a href="{{ route('ai.index') }}" style="font-size:11px;color:#7c3aed;text-decoration:none;display:flex;align-items:center;gap:4px;">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                웍스 Agent
            </a>
        </div>
        <div class="pl-side-scroll">
            <div class="pl-label">{{ __('ai.project') }}</div>
            <div class="pl-nav-item {{ !$projectId ? 'active' : '' }}" onclick="filterProject(null)">{{ __('ai.all') }}</div>
            @foreach($projects as $proj)
            <div class="pl-nav-item {{ $projectId == $proj->id ? 'active' : '' }}" onclick="filterProject({{ $proj->id }})">
                {{ $proj->name }}
            </div>
            @endforeach

            <div class="pl-label" style="margin-top:12px;display:flex;align-items:center;justify-content:space-between;">
                {{ __('ai.prompt_category_label') }}
                <button onclick="openCatModal()" style="background:none;border:none;cursor:pointer;color:#7c3aed;padding:0;line-height:1;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
            <div class="pl-nav-item {{ !$catId ? 'active' : '' }}" onclick="filterCategory(null)">{{ __('ai.all') }}</div>
            @foreach($categories as $cat)
            <div class="pl-nav-item {{ $catId == $cat->id ? 'active' : '' }}" onclick="filterCategory({{ $cat->id }})">
                <span>{{ $cat->name }}</span>
                @if($cat->source !== 'system')
                <button onclick="event.stopPropagation();deleteCategory({{ $cat->id }}, this)" style="background:none;border:none;cursor:pointer;color:#dc2626;padding:0;opacity:.5;line-height:1;">
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @endif
            </div>
            @endforeach

            <div style="height:1px;background:#f0ecff;margin:14px 0;"></div>
            <a href="{{ route('ai.executions.index') }}" class="pl-nav-item" style="text-decoration:none;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('ai.execution_history') }}
            </a>
        </div>
    </div>

    {{-- 메인 --}}
    <div class="pl-main">
        <div class="pl-toolbar">
            <input type="text" class="pl-search" id="search-input" placeholder="{{ __('ai.prompt_search_ph') }}" value="{{ $search }}" onkeydown="if(event.key==='Enter')applySearch()">
            <button class="pl-btn pl-btn-outline" onclick="openRefineModal()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                {{ __('ai.prompt_ai_refine') }}
            </button>
            <button class="pl-btn pl-btn-primary" onclick="openAddModal()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('ai.prompt_new') }}
            </button>
        </div>

        <div class="pl-content">
            @if($prompts->isEmpty())
            <div class="pl-empty">
                <div class="pl-empty-icon">📝</div>
                <div style="font-size:14px;font-weight:600;color:#64748b;margin-bottom:6px;">{{ __('ai.prompt_empty') }}</div>
                <div style="font-size:12px;">{{ __('ai.prompt_empty_hint') }}</div>
            </div>
            @else
            <div class="pl-grid">
                @foreach($prompts as $p)
                @php
                    $badgeClass = match($p->category?->name ?? $p->type ?? '') {
                        '개발 관련' => 'pl-badge-dev',
                        '문서 작성' => 'pl-badge-doc',
                        '데이터 처리' => 'pl-badge-data',
                        default => 'pl-badge-etc',
                    };
                    $catLabel  = $p->category?->name ?? __('ai.prompt_unclassified');
                    $isSystem  = $p->source === 'system';
                @endphp
                <div class="pl-card">
                    <div class="pl-card-header">
                        <div class="pl-card-icon" style="{{ $isSystem ? 'background:linear-gradient(135deg,#dbeafe,#bfdbfe);' : '' }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $isSystem ? '#1d4ed8' : '#7c3aed' }}" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="pl-card-title">{{ $p->name }}</div>
                            <div class="pl-card-meta">{{ $catLabel }}{{ $p->type ? ' · '.$p->type : '' }} · {{ $p->created_at->format('Y.m.d') }}</div>
                        </div>
                    </div>
                    <div class="pl-card-prompt">{{ $p->final_prompt }}</div>
                    <div class="pl-card-footer">
                        <span class="pl-badge {{ $badgeClass }}">{{ $catLabel }}</span>
                        @if($isSystem)
                        <span class="pl-badge" style="background:#dbeafe;color:#1d4ed8;">{{ __('ai.prompt_system_badge') }}</span>
                        @endif
                        @if($p->confidence_score < 0.7)
                        <span class="pl-badge" style="background:#fef3c7;color:#d97706;">{{ __('ai.prompt_low_confidence') }}</span>
                        @endif
                        <div class="pl-card-actions">
                            <button class="pl-btn pl-btn-outline" onclick="usePrompt({{ $p->id }})">{{ __('ai.use') }}</button>
                            @if(!$isSystem)
                            <button class="pl-btn pl-btn-outline" onclick="editPrompt({{ $p->id }})">{{ __('ai.edit') }}</button>
                            <button class="pl-btn pl-btn-danger" onclick="deletePrompt({{ $p->id }}, this)">{{ __('ai.delete') }}</button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($prompts->hasPages())
            <div style="margin-top:20px;">{{ $prompts->links() }}</div>
            @endif
            @endif
        </div>
    </div>
</div>

{{-- 프롬프트 데이터 (JS용) --}}
@php
$promptsJs = collect($prompts->items())->map(fn($p) => [
    'id' => $p->id, 'name' => $p->name, 'type' => $p->type, 'purpose' => $p->purpose,
    'ai_role' => $p->ai_role, 'input_data' => $p->input_data, 'conditions' => $p->conditions,
    'output_format' => $p->output_format, 'final_prompt' => $p->final_prompt,
    'category_id' => $p->category_id, 'project_id' => $p->project_id,
    'confidence_score' => $p->confidence_score,
])->values();
@endphp
<script>
const PROMPTS_DATA = @json($promptsJs);
</script>

{{-- 웍스 정제 모달 --}}
<div class="pl-modal-bg" id="refine-modal">
    <div class="pl-modal" style="width:720px;">
        <h3>
            <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            {{ __('ai.refine_modal_title') }}
        </h3>

        {{-- Step 1: 입력 --}}
        <div id="refine-step1">
            <div class="pl-field">
                <label>{{ __('ai.refine_nl_request') }} <span style="color:#ef4444;">*</span></label>
                <textarea id="refine-input" rows="4" placeholder="{{ __('ai.refine_nl_ph') }}"></textarea>
            </div>
            <div class="pl-row2">
                <div class="pl-field">
                    <label>{{ __('ai.refine_project_opt') }}</label>
                    <select id="refine-project">
                        <option value="">{{ __('ai.refine_no_project') }}</option>
                        @foreach($projects as $proj)
                        <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pl-field">
                    <label>{{ __('ai.refine_based_on') }}</label>
                    <select id="refine-based-on">
                        <option value="">{{ __('ai.refine_new_create') }}</option>
                    </select>
                </div>
            </div>
            <div id="refine-status" style="font-size:12px;color:#7c3aed;min-height:18px;margin-bottom:10px;"></div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button class="pl-btn pl-btn-outline" onclick="closePLModal('refine-modal')">{{ __('ai.cancel') }}</button>
                <button class="pl-btn pl-btn-primary" id="refine-run-btn" onclick="runRefine()">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ __('ai.refine_run') }}
                </button>
            </div>
        </div>

        {{-- Step 2: 결과 (정제 후 표시) --}}
        <div id="refine-step2" style="display:none;">
            <div style="background:#f8f7ff;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#7c3aed;display:flex;align-items:center;gap:8px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('ai.refine_done_note') }}
                <span id="refine-confidence" style="margin-left:auto;background:#ede9fe;border-radius:20px;padding:2px 10px;font-weight:700;"></span>
            </div>
            <div class="pl-row2">
                <div class="pl-field">
                    <label>{{ __('ai.refine_prompt_name') }} <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="r-name" maxlength="200">
                </div>
                <div class="pl-field">
                    <label>{{ __('ai.edit_category') }}</label>
                    <select id="r-category">
                        <option value="">{{ __('ai.select_none') }}</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="pl-row2">
                <div class="pl-field">
                    <label>{{ __('ai.refine_ai_classified') }}</label>
                    <input type="text" id="r-category-label" readonly style="background:#f1f5f9;color:#64748b;">
                </div>
                <div class="pl-field">
                    <label>{{ __('ai.refine_prompt_type') }}</label>
                    <input type="text" id="r-type" maxlength="100">
                </div>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.refine_purpose') }}</label>
                <textarea id="r-purpose" rows="2"></textarea>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.refine_ai_role') }}</label>
                <textarea id="r-ai-role" rows="2"></textarea>
            </div>
            <div class="pl-row2">
                <div class="pl-field">
                    <label>{{ __('ai.refine_input_data') }}</label>
                    <textarea id="r-input-data" rows="2"></textarea>
                </div>
                <div class="pl-field">
                    <label>{{ __('ai.refine_conditions') }}</label>
                    <textarea id="r-conditions" rows="2"></textarea>
                </div>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.refine_output_format') }}</label>
                <textarea id="r-output-format" rows="2"></textarea>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.refine_final_prompt') }} <span style="color:#ef4444;">*</span></label>
                <div class="pl-final-prompt" id="r-final-prompt-display" style="margin-bottom:6px;"></div>
                <textarea id="r-final-prompt" rows="5" placeholder="{{ __('ai.refine_final_ph') }}"></textarea>
            </div>
            <div class="pl-modal-footer">
                <button class="pl-btn pl-btn-outline" onclick="document.getElementById('refine-step1').style.display='';document.getElementById('refine-step2').style.display='none';">{{ __('ai.refine_again') }}</button>
                <button class="pl-btn pl-btn-outline" onclick="closePLModal('refine-modal')">{{ __('ai.cancel') }}</button>
                <button class="pl-btn pl-btn-primary" onclick="saveRefined()">{{ __('ai.refine_save') }}</button>
                <button class="pl-btn pl-btn-primary" style="background:linear-gradient(135deg,#059669,#10b981);" onclick="saveAndUseRefined()">{{ __('ai.refine_save_and_run') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- 직접 추가/수정 모달 --}}
<div class="pl-modal-bg" id="edit-modal">
    <div class="pl-modal">
        <h3 id="edit-modal-title">{{ __('ai.edit_modal_add') }}</h3>
        <input type="hidden" id="edit-id">
        <div class="pl-row2">
            <div class="pl-field">
                <label>{{ __('ai.refine_prompt_name') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" id="e-name" maxlength="200">
            </div>
            <div class="pl-field">
                <label>{{ __('ai.edit_category') }}</label>
                <select id="e-category">
                    <option value="">{{ __('ai.select_none') }}</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="pl-row2">
            <div class="pl-field">
                <label>{{ __('ai.edit_project') }}</label>
                <select id="e-project">
                    <option value="">{{ __('ai.select_none') }}</option>
                    @foreach($projects as $proj)
                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.edit_type') }}</label>
                <input type="text" id="e-type" maxlength="100" placeholder="{{ __('ai.edit_type_ph') }}">
            </div>
        </div>
        <div class="pl-field">
            <label>{{ __('ai.edit_purpose') }}</label>
            <textarea id="e-purpose" rows="2"></textarea>
        </div>
        <div class="pl-field">
            <label>{{ __('ai.edit_ai_role') }}</label>
            <textarea id="e-ai-role" rows="2"></textarea>
        </div>
        <div class="pl-row2">
            <div class="pl-field">
                <label>{{ __('ai.edit_input_data') }}</label>
                <textarea id="e-input-data" rows="2"></textarea>
            </div>
            <div class="pl-field">
                <label>{{ __('ai.edit_conditions') }}</label>
                <textarea id="e-conditions" rows="2"></textarea>
            </div>
        </div>
        <div class="pl-field">
            <label>{{ __('ai.edit_output_format') }}</label>
            <textarea id="e-output-format" rows="2"></textarea>
        </div>
        <div class="pl-field">
            <label>{{ __('ai.edit_final_prompt') }} <span style="color:#ef4444;">*</span></label>
            <textarea id="e-final-prompt" rows="5" placeholder="{{ __('ai.edit_final_ph') }}"></textarea>
        </div>
        <div class="pl-modal-footer">
            <button class="pl-btn pl-btn-outline" onclick="closePLModal('edit-modal')">{{ __('ai.cancel') }}</button>
            <button class="pl-btn pl-btn-primary" onclick="savePrompt()">{{ __('ai.refine_save') }}</button>
        </div>
    </div>
</div>

{{-- 카테고리 추가 모달 --}}
<div class="pl-modal-bg" id="cat-modal">
    <div class="pl-modal" style="width:400px;">
        <h3>{{ __('ai.cat_modal_title') }}</h3>
        <div class="pl-field">
            <label>{{ __('ai.cat_name_label') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="cat-name" maxlength="100" placeholder="{{ __('ai.cat_name_ph') }}" onkeydown="if(event.key==='Enter')saveCat()">
        </div>
        <div class="pl-field">
            <label>{{ __('ai.cat_project_opt') }}</label>
            <select id="cat-project">
                <option value="">{{ __('ai.cat_project_all') }}</option>
                @foreach($projects as $proj)
                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="cat-status" style="font-size:12px;min-height:16px;"></div>
        <div class="pl-modal-footer">
            <button class="pl-btn pl-btn-outline" onclick="closePLModal('cat-modal')">{{ __('ai.cancel') }}</button>
            <button class="pl-btn pl-btn-primary" onclick="saveCat()">{{ __('ai.cat_add_btn') }}</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const AI_BASE = '{{ url("/ai") }}';
const PL_STR = {
    promptAddTitle:    '{{ __('ai.edit_modal_add') }}',
    promptEditTitle:   '{{ __('ai.edit_modal_edit') }}',
    nameRequired:      '{{ __('ai.prompt_name_required') }}',
    catDeleteConfirm:  '{{ __('ai.cat_delete_confirm') }}',
    promptDeleteConfirm: '{{ __('ai.prompt_delete_confirm') }}',
    analyzing:         '{{ __('ai.analyzing') }}',
    adding:            '{{ __('ai.adding') }}',
    addFail:           '{{ __('ai.add_fail') }}',
    deleteFail:        '{{ __('ai.delete_fail') }}',
    saveFail:          '{{ __('ai.save_fail') }}',
    enterRequest:      '{{ __('ai.enter_request') }}',
    refineFail:        '{{ __('ai.refine_fail') }}',
    newCreate:         '{{ __('ai.refine_new_create') }}',
    refineConfidence:  '{{ __('ai.refine_confidence') }}',
};
let currentProjectId = @json($projectId);
let currentCatId = @json($catId);

async function post(url, data={}) {
    const r = await fetch(url, {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify(data)
    });
    return r.json();
}
async function put(url, data={}) {
    const r = await fetch(url, {
        method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify(data)
    });
    return r.json();
}
async function del(url) {
    const r = await fetch(url, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
    return r.json();
}

async function openPLModal(id) { document.getElementById(id).classList.add('show'); }
async function closePLModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.pl-modal-bg').forEach(el => el.addEventListener('click', e => { if(e.target===el) el.classList.remove('show'); }));

async function filterProject(id) {
    const url = new URL(location.href);
    if (id) url.searchParams.set('project_id', id); else url.searchParams.delete('project_id');
    url.searchParams.delete('category_id');
    location.href = url.toString();
}
async function filterCategory(id) {
    const url = new URL(location.href);
    if (id) url.searchParams.set('category_id', id); else url.searchParams.delete('category_id');
    location.href = url.toString();
}
async function applySearch() {
    const url = new URL(location.href);
    const v = document.getElementById('search-input').value.trim();
    if (v) url.searchParams.set('search', v); else url.searchParams.delete('search');
    location.href = url.toString();
}

// ── 카테고리 ────────────────────────────────────────────────────
async function openCatModal() { document.getElementById('cat-name').value=''; document.getElementById('cat-status').textContent=''; openPLModal('cat-modal'); }
async function saveCat() {
    const name = document.getElementById('cat-name').value.trim();
    const project_id = document.getElementById('cat-project').value || null;
    if (!name) return;
    const el = document.getElementById('cat-status');
    el.style.color='#6b7280'; el.textContent=PL_STR.adding;
    const res = await post(AI_BASE+'/categories', { name, project_id });
    if (res.ok) { closePLModal('cat-modal'); location.reload(); }
    else { el.style.color='#dc2626'; el.textContent=res.error||PL_STR.addFail; }
}
async function deleteCategory(id, btn) {
    if (!await __confirm(PL_STR.catDeleteConfirm)) return;
    btn.style.opacity='.4';
    const res = await del(AI_BASE+'/categories/'+id);
    if (res.ok) location.reload();
    else { btn.style.opacity='1'; alert(res.error||PL_STR.deleteFail); }
}

// ── 프롬프트 CRUD ───────────────────────────────────────────────
async function openAddModal() {
    ['e-name','e-type','e-purpose','e-ai-role','e-input-data','e-conditions','e-output-format','e-final-prompt'].forEach(id => { const el=document.getElementById(id); if(el)el.value=''; });
    document.getElementById('edit-id').value = '';
    document.getElementById('e-category').value = '';
    document.getElementById('e-project').value = '';
    document.getElementById('edit-modal-title').textContent = PL_STR.promptAddTitle;
    openPLModal('edit-modal');
}
async function editPrompt(id) {
    const p = PROMPTS_DATA.find(x => x.id === id);
    if (!p) return;
    document.getElementById('edit-id').value = p.id;
    document.getElementById('e-name').value = p.name || '';
    document.getElementById('e-type').value = p.type || '';
    document.getElementById('e-purpose').value = p.purpose || '';
    document.getElementById('e-ai-role').value = p.ai_role || '';
    document.getElementById('e-input-data').value = p.input_data || '';
    document.getElementById('e-conditions').value = p.conditions || '';
    document.getElementById('e-output-format').value = p.output_format || '';
    document.getElementById('e-final-prompt').value = p.final_prompt || '';
    document.getElementById('e-category').value = p.category_id || '';
    document.getElementById('e-project').value = p.project_id || '';
    document.getElementById('edit-modal-title').textContent = PL_STR.promptEditTitle;
    openPLModal('edit-modal');
}
async function savePrompt() {
    const id = document.getElementById('edit-id').value;
    const data = {
        name:          document.getElementById('e-name').value.trim(),
        final_prompt:  document.getElementById('e-final-prompt').value.trim(),
        category_id:   document.getElementById('e-category').value || null,
        project_id:    document.getElementById('e-project').value || null,
        type:          document.getElementById('e-type').value.trim() || null,
        purpose:       document.getElementById('e-purpose').value.trim() || null,
        ai_role:       document.getElementById('e-ai-role').value.trim() || null,
        input_data:    document.getElementById('e-input-data').value.trim() || null,
        conditions:    document.getElementById('e-conditions').value.trim() || null,
        output_format: document.getElementById('e-output-format').value.trim() || null,
    };
    if (!data.name || !data.final_prompt) return alert(PL_STR.nameRequired);
    const res = id
        ? await put(AI_BASE+'/prompts/'+id, data)
        : await post(AI_BASE+'/prompts', data);
    if (res.ok) { closePLModal('edit-modal'); location.reload(); }
    else alert(res.error || PL_STR.saveFail);
}
async function deletePrompt(id, btn) {
    if (!await __confirm(PL_STR.promptDeleteConfirm)) return;
    btn.style.opacity = '.4';
    const res = await del(AI_BASE+'/prompts/'+id);
    if (res.ok) btn.closest('.pl-card').remove();
    else { btn.style.opacity='1'; alert(res.error||PL_STR.deleteFail); }
}
async function usePrompt(id) {
    const p = PROMPTS_DATA.find(x => x.id === id);
    if (!p) return;
    window.location.href = '{{ route("ai.index") }}?prompt_id=' + id + '&prompt_text=' + encodeURIComponent(p.final_prompt);
}

// ── 웍스 정제 ─────────────────────────────────────────────────────
let refinedData = null;

async function openRefineModal() {
    document.getElementById('refine-input').value = '';
    document.getElementById('refine-status').textContent = '';
    document.getElementById('refine-step1').style.display = '';
    document.getElementById('refine-step2').style.display = 'none';
    refinedData = null;

    // 기존 프롬프트 목록 채우기
    const sel = document.getElementById('refine-based-on');
    sel.innerHTML = '<option value="">' + PL_STR.newCreate + '</option>';
    PROMPTS_DATA.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        sel.appendChild(opt);
    });

    openPLModal('refine-modal');
}

async function runRefine() {
    const input = document.getElementById('refine-input').value.trim();
    if (!input) return alert(PL_STR.enterRequest);

    const basedOnId = document.getElementById('refine-based-on').value;
    const existing = basedOnId ? PROMPTS_DATA.find(p => p.id == basedOnId) : null;

    const btn = document.getElementById('refine-run-btn');
    const status = document.getElementById('refine-status');
    btn.disabled = true; btn.style.opacity = '.6';
    status.style.color = '#7c3aed'; status.textContent = PL_STR.analyzing;

    try {
        const res = await post(AI_BASE+'/prompts/refine', { input, existing });
        if (!res.ok) throw new Error(res.error || PL_STR.refineFail);
        refinedData = res.data;
        fillRefinedFields(refinedData);
        document.getElementById('refine-step1').style.display = 'none';
        document.getElementById('refine-step2').style.display = '';
    } catch(e) {
        status.style.color = '#dc2626';
        status.textContent = e.message;
    }
    btn.disabled = false; btn.style.opacity = '1';
}

async function fillRefinedFields(d) {
    document.getElementById('r-name').value = d.name || '';
    document.getElementById('r-category-label').value = d.category || '';
    document.getElementById('r-type').value = d.type || '';
    document.getElementById('r-purpose').value = d.purpose || '';
    document.getElementById('r-ai-role').value = d.ai_role || '';
    document.getElementById('r-input-data').value = d.input_data || '';
    document.getElementById('r-conditions').value = d.conditions || '';
    document.getElementById('r-output-format').value = d.output_format || '';
    document.getElementById('r-final-prompt').value = d.final_prompt || '';
    document.getElementById('r-final-prompt-display').textContent = d.final_prompt || '';
    const score = Math.round((d.confidence_score || 1) * 100);
    document.getElementById('r-confidence').textContent = PL_STR.refineConfidence + ' ' + score + '%';
    document.getElementById('r-confidence').style.background = score >= 80 ? '#dcfce7' : score >= 60 ? '#fef3c7' : '#fee2e2';
    document.getElementById('r-confidence').style.color = score >= 80 ? '#16a34a' : score >= 60 ? '#d97706' : '#dc2626';
}

function getRefinedFormData() {
    return {
        name:          document.getElementById('r-name').value.trim(),
        final_prompt:  document.getElementById('r-final-prompt').value.trim(),
        category_id:   document.getElementById('r-category').value || null,
        project_id:    document.getElementById('refine-project').value || null,
        type:          document.getElementById('r-type').value.trim() || null,
        purpose:       document.getElementById('r-purpose').value.trim() || null,
        ai_role:       document.getElementById('r-ai-role').value.trim() || null,
        input_data:    document.getElementById('r-input-data').value.trim() || null,
        conditions:    document.getElementById('r-conditions').value.trim() || null,
        output_format: document.getElementById('r-output-format').value.trim() || null,
        confidence_score: refinedData?.confidence_score || 1,
    };
}

async function saveRefined() {
    const data = getRefinedFormData();
    if (!data.name || !data.final_prompt) return alert(PL_STR.nameRequired);
    const res = await post(AI_BASE+'/prompts', data);
    if (res.ok) { closePLModal('refine-modal'); location.reload(); }
    else alert(res.error || PL_STR.saveFail);
}

async function saveAndUseRefined() {
    const data = getRefinedFormData();
    if (!data.name || !data.final_prompt) return alert(PL_STR.nameRequired);
    const res = await post(AI_BASE+'/prompts', data);
    if (res.ok) {
        closePLModal('refine-modal');
        window.location.href = '{{ route("ai.index") }}?prompt_id=' + res.prompt.id + '&prompt_text=' + encodeURIComponent(data.final_prompt);
    } else alert(res.error || PL_STR.saveFail);
}
</script>
@endsection
