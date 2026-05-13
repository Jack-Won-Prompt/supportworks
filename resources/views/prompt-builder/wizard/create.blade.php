@extends('layouts.app')

@section('title', 'Prompt Builder - 새 빌더 시작')

@push('styles')
<style>
.pb-wizard-step { display:none; }
.pb-wizard-step.active { display:block; }
.pb-step-indicator { display:flex;gap:0;margin-bottom:32px; }
.pb-step-dot { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:all .2s; }
.pb-step-dot.done { background:#7c3aed;color:#fff; }
.pb-step-dot.active { background:#7c3aed;color:#fff;box-shadow:0 0 0 4px #ede9fe; }
.pb-step-dot.pending { background:#e5e7eb;color:#9ca3af; }
.pb-step-line { flex:1;height:2px;background:#e5e7eb;margin-top:15px; }
.pb-step-line.done { background:#7c3aed; }
.pb-card { background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px; }
.pb-select-card { border:2px solid #e5e7eb;border-radius:10px;padding:16px;cursor:pointer;transition:border-color .15s; }
.pb-select-card:hover, .pb-select-card.selected { border-color:#7c3aed;background:#faf5ff; }
.pb-select-card.selected .pb-check { display:block; }
.pb-check { display:none;color:#7c3aed; }
.pb-btn-primary { background:#7c3aed;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s; }
.pb-btn-primary:hover { background:#6d28d9; }
.pb-btn-secondary { background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;padding:10px 24px;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s; }
.pb-btn-secondary:hover { border-color:#7c3aed;color:#7c3aed; }

/* 기술 스택 선택 UI */
.pb-tech-section { margin-bottom:14px; }
.pb-tech-label { display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:6px; }
.pb-tech-tabs { display:flex;gap:4px;margin-bottom:8px;flex-wrap:wrap; }
.pb-tech-tab { padding:3px 11px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;transition:all .15s; }
.pb-tech-tab.active, .pb-tech-tab:hover { background:#7c3aed;color:#fff;border-color:#7c3aed; }
.pb-tech-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:5px;margin-bottom:6px; }
.pb-tech-card { display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 4px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:all .15s;background:#fff; }
.pb-tech-card:hover { border-color:#c4b5fd;background:#faf5ff; }
.pb-tech-card.selected { border-color:#7c3aed;background:#ede9fe; }
.pb-tech-name { font-size:10px;font-weight:600;color:#374151;text-align:center;line-height:1.2; }
.pb-tech-card.selected .pb-tech-name { color:#6d28d9; }
.pb-tech-icon { width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;letter-spacing:-.3px; }
.pb-lang-row { display:flex;flex-wrap:wrap;gap:5px;margin-bottom:6px; }
.pb-lang-pill { padding:5px 13px;border:1.5px solid #e5e7eb;border-radius:20px;font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;color:#374151;background:#fff; }
.pb-lang-pill:hover { border-color:#c4b5fd;color:#6d28d9; }
.pb-lang-pill.selected { border-color:#7c3aed;background:#7c3aed;color:#fff; }
.pb-custom-input { width:100%;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:12px;color:#374151;box-sizing:border-box;margin-top:4px; }
.pb-custom-input:focus { outline:none;border-color:#7c3aed; }
.pb-selected-tag { display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:#ede9fe;color:#6d28d9;border-radius:12px;font-size:11px;font-weight:600; }
</style>
@endpush

@section('content')
<div style="max-width:720px;margin:0 auto;padding:32px 16px;" x-data="pbWizard()" x-init="init()">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:28px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7z"/><path d="M14 2v4a2 2 0 002 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
        <h1 style="font-size:22px;font-weight:700;color:#1e1b4b;margin:0;">새 Prompt Builder 시작</h1>
    </div>

    {{-- 스텝 인디케이터 --}}
    <div class="pb-step-indicator" style="margin-bottom:32px;">
        @foreach(['컨텍스트', '목적', '입력 소스', '분석', '검토/수정', '저장'] as $i => $label)
        <div style="display:flex;align-items:flex-start;flex:{{ $i < 5 ? '1' : '0' }};">
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                <div class="pb-step-dot" :class="{ done: step > {{ $i+1 }}, active: step === {{ $i+1 }}, pending: step < {{ $i+1 }} }">
                    <template x-if="step > {{ $i+1 }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </template>
                    <template x-if="step <= {{ $i+1 }}">
                        <span>{{ $i+1 }}</span>
                    </template>
                </div>
                <span style="font-size:11px;color:#9ca3af;white-space:nowrap;">{{ $label }}</span>
            </div>
            @if($i < 5)
            <div class="pb-step-line" :class="{ done: step > {{ $i+1 }} }" style="margin-top:15px;"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Step 1: 컨텍스트 (프로젝트/워크스페이스/웍스 선택) --}}
    <div class="pb-wizard-step" :class="{ active: step === 1 }">
        <div class="pb-card">
            <h2 style="font-size:16px;font-weight:700;color:#1e1b4b;margin:0 0 20px;">1단계: 컨텍스트 설정</h2>

            {{-- 프로젝트 선택 --}}
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">프로젝트 <span style="color:#ef4444;">*</span></label>
                <select x-model="form.project_id" @change="loadWorkspaces()" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                    <option value="">프로젝트 선택...</option>
                    <template x-for="p in projects" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
            </div>

            {{-- 워크스페이스 선택/생성 --}}
            <div style="margin-bottom:20px;" x-show="form.project_id">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:13px;font-weight:600;color:#374151;margin:0;">워크스페이스 <span style="color:#ef4444;">*</span></label>
                    <button x-show="workspaces.length > 0 && !creatingWorkspace"
                            @click="creatingWorkspace = true"
                            type="button"
                            style="font-size:12px;color:#7c3aed;background:none;border:none;cursor:pointer;padding:0;">+ 새로 만들기</button>
                </div>

                {{-- 기존 워크스페이스 선택 --}}
                <div x-show="workspaces.length > 0 && !creatingWorkspace">
                    <select x-model="form.workspace_id" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                        <option value="">워크스페이스 선택...</option>
                        <template x-for="w in workspaces" :key="w.id">
                            <option :value="w.id" x-text="w.name + (w.framework ? ' (' + w.framework + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                {{-- 인라인 워크스페이스 생성 폼 --}}
                <div x-show="creatingWorkspace || workspaces.length === 0"
                     style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:8px;padding:16px;">
                    <p x-show="workspaces.length === 0" style="font-size:12px;color:#6b7280;margin:0 0 12px;">
                        이 프로젝트에 워크스페이스가 없습니다. 아래에서 새로 만드세요.
                    </p>
                    {{-- 워크스페이스 이름 --}}
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">이름 <span style="color:#ef4444;">*</span></label>
                        <input type="text" x-model="newWs.name" placeholder="예: 메인 워크스페이스"
                               style="width:100%;padding:8px 10px;border:1.5px solid #d1d5db;border-radius:6px;font-size:13px;box-sizing:border-box;">
                    </div>

                    {{-- 프레임워크 선택 --}}
                    <div class="pb-tech-section">
                        <label class="pb-tech-label">
                            프레임워크
                            <span x-show="newWs.framework" style="margin-left:6px;">
                                <span class="pb-selected-tag" x-text="newWs.framework"></span>
                            </span>
                        </label>
                        {{-- 카테고리 탭 --}}
                        <div class="pb-tech-tabs">
                            <button type="button" class="pb-tech-tab" :class="{active: fwCat==='all'}"        @click="fwCat='all'">전체</button>
                            <button type="button" class="pb-tech-tab" :class="{active: fwCat==='frontend'}"   @click="fwCat='frontend'">Frontend</button>
                            <button type="button" class="pb-tech-tab" :class="{active: fwCat==='backend'}"    @click="fwCat='backend'">Backend</button>
                            <button type="button" class="pb-tech-tab" :class="{active: fwCat==='mobile'}"     @click="fwCat='mobile'">Mobile</button>
                            <button type="button" class="pb-tech-tab" :class="{active: fwCat==='fullstack'}"  @click="fwCat='fullstack'">Full-Stack</button>
                        </div>
                        {{-- 카드 그리드 --}}
                        <div class="pb-tech-grid">
                            <template x-for="fw in fwFiltered" :key="fw.v">
                                <div class="pb-tech-card" :class="{selected: newWs.framework===fw.v}" @click="newWs.framework = (newWs.framework===fw.v ? '' : fw.v)">
                                    <div class="pb-tech-icon" :style="`background:${fw.c}`" x-text="fw.ic"></div>
                                    <span class="pb-tech-name" x-text="fw.n"></span>
                                </div>
                            </template>
                        </div>
                        <input type="text" class="pb-custom-input" x-model="newWs.framework" placeholder="직접 입력 또는 위에서 선택">
                    </div>

                    {{-- 언어 선택 --}}
                    <div class="pb-tech-section">
                        <label class="pb-tech-label">
                            언어
                            <span x-show="newWs.language" style="margin-left:6px;">
                                <span class="pb-selected-tag" x-text="newWs.language"></span>
                            </span>
                        </label>
                        <div class="pb-lang-row">
                            <template x-for="lang in langList" :key="lang.v">
                                <button type="button" class="pb-lang-pill" :class="{selected: newWs.language===lang.v}"
                                        @click="newWs.language = (newWs.language===lang.v ? '' : lang.v)"
                                        :style="newWs.language===lang.v ? `background:${lang.c};border-color:${lang.c}` : `border-color:${lang.c}33;color:${lang.c}`"
                                        x-text="lang.n">
                                </button>
                            </template>
                        </div>
                        <input type="text" class="pb-custom-input" x-model="newWs.language" placeholder="직접 입력 또는 위에서 선택">
                    </div>

                    {{-- 스타일링 선택 --}}
                    <div class="pb-tech-section">
                        <label class="pb-tech-label">
                            스타일링
                            <span x-show="newWs.styling" style="margin-left:6px;">
                                <span class="pb-selected-tag" x-text="newWs.styling"></span>
                            </span>
                        </label>
                        <div class="pb-tech-grid">
                            <template x-for="st in stylingList" :key="st.v">
                                <div class="pb-tech-card" :class="{selected: newWs.styling===st.v}" @click="newWs.styling = (newWs.styling===st.v ? '' : st.v)">
                                    <div class="pb-tech-icon" :style="`background:${st.c}`" x-text="st.ic"></div>
                                    <span class="pb-tech-name" x-text="st.n"></span>
                                </div>
                            </template>
                        </div>
                        <input type="text" class="pb-custom-input" x-model="newWs.styling" placeholder="직접 입력 또는 위에서 선택">
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button x-show="workspaces.length > 0" @click="creatingWorkspace = false"
                                type="button"
                                style="padding:6px 14px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;background:#fff;color:#374151;cursor:pointer;">취소</button>
                        <button @click="saveWorkspace()" :disabled="!newWs.name || savingWorkspace"
                                type="button"
                                style="padding:6px 14px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;"
                                :style="(!newWs.name || savingWorkspace) ? 'opacity:0.5;cursor:not-allowed;' : ''">
                            <span x-text="savingWorkspace ? '생성 중...' : '워크스페이스 만들기'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 웍스 타입 선택 --}}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">웍스 도구 <span style="color:#ef4444;">*</span></label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                    @foreach([['cursor','Cursor','IDE 내장 웍스'], ['claude','Claude','XML 형식 최적화'], ['openai','OpenAI','Markdown 형식']] as [$val, $label, $desc])
                    <div class="pb-select-card" :class="{ selected: form.ai_type === '{{ $val }}' }" @click="form.ai_type = '{{ $val }}'">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <span style="font-size:14px;font-weight:600;color:#111827;">{{ $label }}</span>
                            <svg class="pb-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span style="font-size:12px;color:#6b7280;">{{ $desc }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button class="pb-btn-primary" @click="startSession()" :disabled="!form.project_id || !form.workspace_id || !form.ai_type">다음 단계 →</button>
            </div>
        </div>
    </div>

    {{-- Step 2: 목적 선택 --}}
    <div class="pb-wizard-step" :class="{ active: step === 2 }">
        <div class="pb-card">
            <h2 style="font-size:16px;font-weight:700;color:#1e1b4b;margin:0 0 20px;">2단계: 빌더 목적</h2>

            <div style="display:grid;gap:12px;margin-bottom:24px;">
                @foreach([
                    ['standard_assets','표준 자산 생성','Figma에서 레이아웃/컴포넌트/CSS/JS 표준 자산 추출'],
                    ['screen_generation','화면 생성','표준 자산 기반으로 화면 코드 생성'],
                    ['sequence','시퀀스 단계','다단계 작업의 한 단계 프롬프트'],
                ] as [$val, $label, $desc])
                <div class="pb-select-card" :class="{ selected: form.purpose_type === '{{ $val }}' }" @click="form.purpose_type = '{{ $val }}'">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-size:14px;font-weight:600;color:#111827;">{{ $label }}</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;">{{ $desc }}</div>
                        </div>
                        <svg class="pb-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:space-between;">
                <button class="pb-btn-secondary" @click="step = 1">← 이전</button>
                <button class="pb-btn-primary" @click="submitStep2()" :disabled="!form.purpose_type">다음 단계 →</button>
            </div>
        </div>
    </div>

    {{-- Step 3: 입력 소스 --}}
    <div class="pb-wizard-step" :class="{ active: step === 3 }">
        <div class="pb-card">
            <h2 style="font-size:16px;font-weight:700;color:#1e1b4b;margin:0 0 20px;">3단계: 입력 소스</h2>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Figma URL</label>
                <input type="url" x-model="form.figma_url" placeholder="https://www.figma.com/design/..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">Figma 파일 (JSON)</label>
                <input type="file" @change="form.figma_file = $event.target.files[0]" accept=".json,.fig" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">To-Be 화면 이미지</label>
                <input type="file" @change="form.to_be_image = $event.target.files[0]" accept="image/*" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
            </div>

            <div style="display:flex;justify-content:space-between;">
                <button class="pb-btn-secondary" @click="step = 2">← 이전</button>
                <button class="pb-btn-primary" @click="submitStep3()">분석 시작 →</button>
            </div>
        </div>
    </div>

    {{-- Step 4: 분석 중 --}}
    <div class="pb-wizard-step" :class="{ active: step === 4 }">
        <div class="pb-card" style="text-align:center;padding:48px 24px;">
            <div x-show="analyzing">
                <div style="width:48px;height:48px;border:4px solid #ede9fe;border-top-color:#7c3aed;border-radius:50%;margin:0 auto 16px;animation:spin 1s linear infinite;"></div>
                <p style="font-size:16px;font-weight:600;color:#374151;">표준 자산 매핑 분석 중...</p>
                <p style="font-size:13px;color:#9ca3af;margin-top:8px;">Figma 컴포넌트와 표준 자산을 매핑합니다</p>
            </div>
            <div x-show="!analyzing && analysisResult">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="margin:0 auto 16px;display:block;"><circle cx="12" cy="12" r="10"/><polyline points="16 12 12 8 8 12"/><line x1="12" y1="16" x2="12" y2="8"/></svg>
                <p style="font-size:16px;font-weight:600;color:#374151;">분석 완료</p>
                <div x-show="analysisResult" style="background:#faf5ff;border-radius:8px;padding:16px;margin-top:16px;text-align:left;">
                    <p style="font-size:13px;color:#6b7280;" x-text="analysisResult?.impact?.summary"></p>
                </div>
                <button class="pb-btn-primary" @click="generatePrompt()" style="margin-top:20px;">프롬프트 생성 →</button>
            </div>
        </div>
    </div>

    {{-- Step 5: 프롬프트 검토/수정 --}}
    <div class="pb-wizard-step" :class="{ active: step === 5 }">
        <div class="pb-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="font-size:16px;font-weight:700;color:#1e1b4b;margin:0;">5단계: 프롬프트 검토</h2>
                <div style="display:flex;gap:8px;">
                    @foreach(['cursor','claude','openai'] as $aiType)
                    <button style="padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid #d1d5db;background:#fff;color:#374151;transition:all .15s;"
                            :style="form.ai_type === '{{ $aiType }}' ? 'background:#7c3aed;color:#fff;border-color:#7c3aed;' : ''"
                            @click="previewAi('{{ $aiType }}')">{{ strtoupper($aiType) }}</button>
                    @endforeach
                </div>
            </div>

            <textarea x-model="generatedPrompt" rows="20"
                style="width:100%;padding:12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:monospace;box-sizing:border-box;resize:vertical;"
                placeholder="생성된 프롬프트가 여기에 표시됩니다..."></textarea>

            <div style="display:flex;gap:8px;margin-top:12px;justify-content:space-between;">
                <button class="pb-btn-secondary" @click="copyPrompt()">복사</button>
                <div style="display:flex;gap:8px;">
                    <button class="pb-btn-secondary" @click="step = 3">← 이전</button>
                    <button class="pb-btn-primary" @click="step = 6">저장하기 →</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 6: 저장 --}}
    <div class="pb-wizard-step" :class="{ active: step === 6 }">
        <div class="pb-card">
            <h2 style="font-size:16px;font-weight:700;color:#1e1b4b;margin:0 0 20px;">6단계: 저장</h2>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">제목 <span style="color:#ef4444;">*</span></label>
                <input type="text" x-model="form.title" placeholder="빌더 제목 입력..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">설명 (선택)</label>
                <textarea x-model="form.description" rows="3" placeholder="빌더에 대한 설명..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;resize:none;box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex;justify-content:space-between;">
                <button class="pb-btn-secondary" @click="step = 5">← 이전</button>
                <button class="pb-btn-primary" @click="complete()" :disabled="!form.title">저장 완료</button>
            </div>
        </div>
    </div>

    {{-- 에러 메시지 --}}
    <div x-show="error" style="margin-top:16px;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:13px;" x-text="error"></div>

</div>

@push('scripts')
<script>
{{-- 모든 API URL을 서버에서 생성하여 JS에 주입 (서브경로 배포 대응) --}}
const _pbUrls = {
    apiProjects:    '{{ route('builder.api.projects') }}',
    apiWorkspaces:  '{{ url('prompt-builder/api/projects') }}',
    wizardStart:    '{{ route('builder.wizard.start') }}',
    wizardSessions: '{{ url('prompt-builder/wizard/sessions') }}',
    csrf:           '{{ csrf_token() }}',
    perProjectPref: @json($preferences->per_project_workspace ?? []),
    lastProjectId:  {{ $preferences->last_project_id ?? 'null' }},
    lastAiType:     '{{ $preferences->last_ai_type ?? 'cursor' }}',
};


function pbWizard() {
    return {
        step: 1,
        sessionId: null,
        projects: [],
        workspaces: [],
        creatingWorkspace: false,
        savingWorkspace: false,
        newWs: { name: '', framework: '', language: '', styling: '' },
        fwCat: 'all',

        fwData: [
            // Frontend
            {v:'HTML',         n:'HTML',         ic:'H5',  c:'#E34C26', cat:'frontend'},
            {v:'React',        n:'React',        ic:'Re',  c:'#61DAFB', cat:'frontend'},
            {v:'Next.js',      n:'Next.js',      ic:'▲',   c:'#000000', cat:'frontend'},
            {v:'Vue.js',       n:'Vue.js',       ic:'V',   c:'#42B883', cat:'frontend'},
            {v:'Nuxt.js',      n:'Nuxt.js',      ic:'N',   c:'#00DC82', cat:'frontend'},
            {v:'Angular',      n:'Angular',      ic:'Ng',  c:'#DD0031', cat:'frontend'},
            {v:'Svelte',       n:'Svelte',       ic:'S',   c:'#FF3E00', cat:'frontend'},
            {v:'SvelteKit',    n:'SvelteKit',    ic:'SK',  c:'#FF3E00', cat:'frontend'},
            {v:'Astro',        n:'Astro',        ic:'🚀',  c:'#FF5D01', cat:'frontend'},
            {v:'Remix',        n:'Remix',        ic:'Rx',  c:'#3992FF', cat:'frontend'},
            {v:'Solid.js',     n:'Solid.js',     ic:'So',  c:'#2C4F7C', cat:'frontend'},
            // Backend
            {v:'Laravel',      n:'Laravel',      ic:'L',   c:'#FF2D20', cat:'backend'},
            {v:'Express.js',   n:'Express.js',   ic:'Ex',  c:'#303030', cat:'backend'},
            {v:'NestJS',       n:'NestJS',       ic:'Ns',  c:'#E0234E', cat:'backend'},
            {v:'Django',       n:'Django',       ic:'Dj',  c:'#0C4B33', cat:'backend'},
            {v:'FastAPI',      n:'FastAPI',      ic:'FA',  c:'#009688', cat:'backend'},
            {v:'Spring Boot',  n:'Spring Boot',  ic:'Sp',  c:'#6DB33F', cat:'backend'},
            {v:'Ruby on Rails',n:'Rails',        ic:'Rb',  c:'#CC0000', cat:'backend'},
            {v:'Hono',         n:'Hono',         ic:'H',   c:'#E36002', cat:'backend'},
            {v:'Fiber',        n:'Fiber',        ic:'Fi',  c:'#00ACD7', cat:'backend'},
            {v:'Gin',          n:'Gin',          ic:'Gn',  c:'#00ACD7', cat:'backend'},
            // Mobile
            {v:'React Native', n:'React Native', ic:'RN',  c:'#61DAFB', cat:'mobile'},
            {v:'Flutter',      n:'Flutter',      ic:'Fl',  c:'#54C5F8', cat:'mobile'},
            {v:'Expo',         n:'Expo',         ic:'Ex',  c:'#000020', cat:'mobile'},
            {v:'Ionic',        n:'Ionic',        ic:'Io',  c:'#3880FF', cat:'mobile'},
            {v:'Kotlin',       n:'Kotlin',       ic:'Ko',  c:'#7F52FF', cat:'mobile'},
            {v:'Swift UI',     n:'SwiftUI',      ic:'Sw',  c:'#FA7343', cat:'mobile'},
            // Full-Stack
            {v:'Next.js',      n:'Next.js',      ic:'▲',   c:'#000000', cat:'fullstack'},
            {v:'Nuxt.js',      n:'Nuxt.js',      ic:'N',   c:'#00DC82', cat:'fullstack'},
            {v:'SvelteKit',    n:'SvelteKit',    ic:'SK',  c:'#FF3E00', cat:'fullstack'},
            {v:'Remix',        n:'Remix',        ic:'Rx',  c:'#3992FF', cat:'fullstack'},
            {v:'Astro',        n:'Astro',        ic:'🚀',  c:'#FF5D01', cat:'fullstack'},
            {v:'Laravel',      n:'Laravel',      ic:'L',   c:'#FF2D20', cat:'fullstack'},
            {v:'Django',       n:'Django',       ic:'Dj',  c:'#0C4B33', cat:'fullstack'},
            {v:'Rails',        n:'Rails',        ic:'Rb',  c:'#CC0000', cat:'fullstack'},
        ],

        get fwFiltered() {
            const seen = new Set();
            const list = this.fwCat === 'all'
                ? this.fwData.filter(f => { if (seen.has(f.v)) return false; seen.add(f.v); return true; })
                : this.fwData.filter(f => f.cat === this.fwCat);
            return list;
        },

        langList: [
            {v:'TypeScript',   n:'TypeScript',  c:'#3178C6'},
            {v:'JavaScript',   n:'JavaScript',  c:'#F0B429'},
            {v:'PHP',          n:'PHP',         c:'#777BB4'},
            {v:'Python',       n:'Python',      c:'#3776AB'},
            {v:'Java',         n:'Java',        c:'#007396'},
            {v:'Kotlin',       n:'Kotlin',      c:'#7F52FF'},
            {v:'Go',           n:'Go',          c:'#00ADD8'},
            {v:'Rust',         n:'Rust',        c:'#CE422B'},
            {v:'C#',           n:'C#',          c:'#239120'},
            {v:'Swift',        n:'Swift',       c:'#FA7343'},
            {v:'Dart',         n:'Dart',        c:'#0175C2'},
            {v:'Ruby',         n:'Ruby',        c:'#CC342D'},
        ],

        stylingList: [
            {v:'Tailwind CSS',      n:'Tailwind CSS',    ic:'Tw', c:'#06B6D4'},
            {v:'Bootstrap',         n:'Bootstrap',       ic:'Bs', c:'#7952B3'},
            {v:'Material UI',       n:'Material UI',     ic:'MU', c:'#007FFF'},
            {v:'Shadcn/UI',         n:'Shadcn/UI',       ic:'Sh', c:'#18181B'},
            {v:'Chakra UI',         n:'Chakra UI',       ic:'Ch', c:'#319795'},
            {v:'Ant Design',        n:'Ant Design',      ic:'An', c:'#1677FF'},
            {v:'Styled Components', n:'Styled Comp.',    ic:'SC', c:'#DB7093'},
            {v:'CSS Modules',       n:'CSS Modules',     ic:'Cm', c:'#264DE4'},
            {v:'SCSS/SASS',         n:'SCSS/SASS',       ic:'Sc', c:'#CF649A'},
            {v:'Mantine',           n:'Mantine',         ic:'Ma', c:'#339AF0'},
            {v:'Bulma',             n:'Bulma',           ic:'Bu', c:'#00D1B2'},
            {v:'Vanilla CSS',       n:'Vanilla CSS',     ic:'Cs', c:'#E34C26'},
            {v:'NativeWind',        n:'NativeWind',      ic:'NW', c:'#06B6D4'},
            {v:'Tamagui',           n:'Tamagui',         ic:'Tg', c:'#7B61FF'},
            {v:'Daisyui',           n:'daisyUI',         ic:'Da', c:'#FF9903'},
        ],

        analyzing: false,
        analysisResult: null,
        generatedPrompt: '',
        error: null,
        form: {
            project_id: _pbUrls.lastProjectId ? String(_pbUrls.lastProjectId) : '',
            workspace_id: '',
            ai_type: _pbUrls.lastAiType,
            purpose_type: '',
            figma_url: '',
            figma_file: null,
            to_be_image: null,
            title: '',
            description: '',
        },

        async init() {
            const res = await fetch(_pbUrls.apiProjects);
            this.projects = await res.json();
            if (this.form.project_id) await this.loadWorkspaces();
        },

        async loadWorkspaces() {
            if (!this.form.project_id) return;
            const res = await fetch(`${_pbUrls.apiWorkspaces}/${this.form.project_id}/workspaces`);
            if (!res.ok) { this.error = '워크스페이스 로딩 실패: ' + res.status; return; }
            this.workspaces = await res.json();
            const pref = _pbUrls.perProjectPref;
            const pid = String(this.form.project_id);
            if (pref[pid]) this.form.workspace_id = String(pref[pid]);
        },

        async saveWorkspace() {
            if (!this.newWs.name || this.savingWorkspace) return;
            this.savingWorkspace = true;
            this.error = null;
            try {
                const res = await fetch(`${_pbUrls.apiWorkspaces}/${this.form.project_id}/workspaces`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: JSON.stringify(this.newWs),
                });
                if (!res.ok) { const e = await res.json(); this.error = e.message || '워크스페이스 생성 실패'; return; }
                const ws = await res.json();
                await this.loadWorkspaces();
                this.form.workspace_id = String(ws.id);
                this.creatingWorkspace = false;
                this.fwCat = 'all';
                this.newWs = { name: '', framework: '', language: '', styling: '' };
            } catch (e) {
                this.error = '워크스페이스 생성에 실패했습니다.';
            } finally {
                this.savingWorkspace = false;
            }
        },

        async startSession() {
            this.error = null;
            try {
                const res = await fetch(_pbUrls.wizardStart, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: JSON.stringify({
                        project_id: this.form.project_id,
                        workspace_id: this.form.workspace_id,
                        ai_type: this.form.ai_type,
                    }),
                });
                if (!res.ok) { const e = await res.json(); this.error = e.message || '세션 시작 실패'; return; }
                const data = await res.json();
                this.sessionId = data.session.id;
                this.step = 2;
            } catch (e) {
                this.error = '세션 시작에 실패했습니다.';
            }
        },

        async submitStep2() {
            this.error = null;
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/step/2`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: JSON.stringify({ purpose_type: this.form.purpose_type }),
                });
                if (!res.ok) { this.error = '저장 실패'; return; }
                this.step = 3;
            } catch (e) {
                this.error = '저장에 실패했습니다.';
            }
        },

        async submitStep3() {
            this.error = null;
            const fd = new FormData();
            if (this.form.figma_url) fd.append('figma_url', this.form.figma_url);
            if (this.form.figma_file) fd.append('figma_file', this.form.figma_file);
            if (this.form.to_be_image) fd.append('to_be_image', this.form.to_be_image);
            fd.append('_method', 'PUT');
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/step/3`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: fd,
                });
                if (!res.ok) { this.error = '입력 소스 저장 실패'; return; }
                this.step = 4;
                this.startAnalysis();
            } catch (e) {
                this.error = '입력 소스 저장에 실패했습니다.';
            }
        },

        async startAnalysis() {
            this.analyzing = true;
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/analyze`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': _pbUrls.csrf },
                });
                const data = await res.json();
                this.analysisResult = data.analysis;
            } finally {
                this.analyzing = false;
            }
        },

        async generatePrompt() {
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/generate`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': _pbUrls.csrf },
                });
                const data = await res.json();
                this.generatedPrompt = data.prompt;
                this.step = 5;
            } catch (e) {
                this.error = '프롬프트 생성에 실패했습니다.';
            }
        },

        async previewAi(aiType) {
            this.form.ai_type = aiType;
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/preview-ai`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: JSON.stringify({ ai_type: aiType }),
                });
                const data = await res.json();
                this.generatedPrompt = data.prompt;
            } catch (e) {
                this.error = '웍스 형식 변환에 실패했습니다.';
            }
        },

        async complete() {
            this.error = null;
            try {
                const res = await fetch(`${_pbUrls.wizardSessions}/${this.sessionId}/complete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _pbUrls.csrf },
                    body: JSON.stringify({ title: this.form.title, description: this.form.description }),
                });
                const data = await res.json();
                window.location.href = data.redirect_url;
            } catch (e) {
                this.error = '저장에 실패했습니다.';
            }
        },

        copyPrompt() {
            navigator.clipboard.writeText(this.generatedPrompt);
        },
    };
}
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endpush
@endsection
