@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.doc-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.doc-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.doc-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.doc-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.doc-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.doc-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.doc-btn.primary:hover { background:var(--t700,#6d28d9); }
.doc-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.doc-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.doc-btn.secondary:hover { background:#e2e8f0; }
.doc-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.doc-btn.ghost:hover { background:#f5f3ff; }
.doc-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Cards ───────────────────────────────────────────────────────── */
.doc-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.doc-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Data status grid ────────────────────────────────────────────── */
.data-status-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; }
.data-card { border-radius:10px; border:1.5px solid; padding:12px 14px; display:flex; align-items:flex-start; gap:10px; }
.data-card.ready   { background:#f0fdf4; border-color:#86efac; }
.data-card.missing { background:#fef2f2; border-color:#fca5a5; }
.data-card.optional-missing { background:#fffbeb; border-color:#fde68a; }
.data-card-icon { font-size:20px; flex-shrink:0; margin-top:1px; }
.data-card-title { font-size:13px; font-weight:700; color:#1e1b2e; }
.data-card-count { font-size:11.5px; color:#64748b; margin-top:1px; }
.data-card-link  { font-size:11px; font-weight:600; color:var(--t600,#7c3aed); text-decoration:none; display:inline-block; margin-top:4px; }
.data-card-link:hover { text-decoration:underline; }

/* ── Generation info box ─────────────────────────────────────────── */
.gen-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; margin-bottom:16px; }
.gen-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.gen-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.gen-info-value { font-size:18px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Progress ────────────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:16px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }

.section-list { list-style:none; margin:0; padding:0; max-height:360px; overflow-y:auto; }
.section-list li { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; font-size:13px; }
.section-list li:hover { background:#fdfcff; }
.section-status-icon { width:18px; text-align:center; flex-shrink:0; }
.section-title { flex:1; color:#374151; }
.section-elapsed { font-size:11px; color:#94a3b8; }

/* ── Complete stats ──────────────────────────────────────────────── */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px; }
.stat-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 14px; text-align:center; }
.stat-value { font-size:18px; font-weight:800; color:var(--t600,#7c3aed); }
.stat-label { font-size:11px; color:#64748b; font-weight:600; }

/* ── Markdown preview ────────────────────────────────────────────── */
.doc-preview-wrap { background:#fafafa; border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.doc-preview-toolbar { display:flex; align-items:center; gap:8px; padding:10px 14px; border-bottom:1px solid #e2e8f0; background:#fff; }
.doc-preview-toolbar span { font-size:12px; font-weight:600; color:#475569; flex:1; }
.doc-markdown-area { width:100%; min-height:400px; padding:16px 18px; font-size:13px; line-height:1.85; color:#374151; font-family:'Courier New',monospace; border:none; resize:vertical; background:#fafafa; box-sizing:border-box; }
.doc-markdown-area:focus { outline:2px solid var(--t300,#c4b5fd); }

/* ── Failed alert ────────────────────────────────────────────────── */
.failed-alert { background:#fef3c7; border:1.5px solid #f59e0b; border-radius:10px; padding:12px 16px; display:flex; align-items:flex-start; gap:10px; }
.failed-alert-body { font-size:12.5px; color:#92400e; }
.failed-list { margin:6px 0 0; padding-left:16px; }

/* ── Proceed / blocked banner ────────────────────────────────────── */
.proceed-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; }
.proceed-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.proceed-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.proceed-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 22px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.proceed-start-btn:hover { background:#f5f3ff; }
.proceed-start-btn:disabled { opacity:.5; cursor:not-allowed; }

.blocked-banner { background:#f1f5f9; border:1.5px solid #e2e8f0; border-radius:14px; padding:20px 24px; text-align:center; color:#475569; }
.blocked-banner h3 { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.blocked-banner p  { font-size:13px; margin:0; }

.missing-alert { background:#fef3c7; border:1.5px solid #f59e0b; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:10px; }
.missing-alert ul { margin:4px 0 0; padding-left:16px; font-size:12.5px; color:#92400e; }
</style>
@endpush

@section('ai-agent-content')

{{-- JSON 데이터 아일랜드 --}}
<script type="application/json" id="doc-data">
{
    "initialState": "{{ $hasDocument ? 'complete' : ($canProceed ? 'ready' : 'prerequisites') }}",
    "hasDocument": {{ $hasDocument ? 'true' : 'false' }},
    "canProceed": {{ $canProceed ? 'true' : 'false' }},
    "aiSectionCount": {{ $aiSectionCount }},
    "screenCount": {{ $screenCount }},
    "failedSections": @json(array_keys($failedSections)),
    "existingContent": @json($artifact->content ?? ''),
    "meta": @json($meta),
    "startUrl": @json($startUrl),
    "sseUrlTpl": @json($sseUrlTpl),
    "saveUrl": @json($saveUrl),
    "exportUrl": @json($exportUrl),
    "regenerateUrl": @json($regenerateUrl),
    "cancelUrlTpl": @json($cancelUrlTpl)
}
</script>

<div x-data="docIndex()" x-init="init()">

{{-- ── 헤더 ────────────────────────────────────────────────────────── --}}
<div class="doc-header">
    <div class="doc-header-left">
        <h1>웍스 기획서</h1>
        <p>AS-IS · TO-BE · Gap 분석을 종합하여 프로젝트 기획서를 자동 작성합니다.</p>
    </div>
    <div class="doc-header-right">
        @if($hasDocument)
        <a href="{{ $historyUrl }}" class="doc-btn secondary sm">버전 이력</a>
        <a href="{{ $traceLinksUrl }}" class="doc-btn secondary sm">추적성</a>
        <a href="{{ $exportUrl }}" class="doc-btn secondary sm">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            내보내기
        </a>
        <a href="{{ $templatePreviewUrl }}" class="doc-btn ghost sm">템플릿 구조</a>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- 상태 A: 필수 데이터 미준비 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<template x-if="state === 'prerequisites'">
    <div>
        @if(!empty($missingRequired))
        <div class="missing-alert">
            <span style="font-size:18px; flex-shrink:0;">⚠️</span>
            <div>
                <strong style="font-size:13px; color:#92400e;">기획서 작성에 필요한 데이터가 준비되지 않았습니다.</strong>
                <ul>
                    @foreach($missingRequired as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div class="doc-section">
            <div class="doc-section-title">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                입력 데이터 현황
            </div>
            <div class="data-status-grid">
                @foreach($dataStatus as $key => $status)
                @php
                    $isReady = $status['ready'];
                    $isOpt = $status['optional'] ?? false;
                    $cardClass = $isReady ? 'ready' : ($isOpt ? 'optional-missing' : 'missing');
                    $icon = $isReady ? '✅' : ($isOpt ? '⚠️' : '❌');
                    $urlMap = ['asis' => $asIsUrl, 'tobe' => $toBeUrl, 'gap' => $gapUrl, 'screens' => $screensUrl];
                    $url = $urlMap[$key] ?? '#';
                @endphp
                <div class="data-card {{ $cardClass }}">
                    <span class="data-card-icon">{{ $icon }}</span>
                    <div>
                        <div class="data-card-title">{{ $status['label'] }}</div>
                        @if($isReady)
                            <div class="data-card-count">{{ $status['count'] }}{{ $status['count_label'] ? ' '.$status['count_label'] : '' }} 준비됨</div>
                        @else
                            <div class="data-card-count">{{ $isOpt ? '선택 사항' : '필수 — 미완료' }}</div>
                            <a href="{{ $url }}" class="data-card-link">→ 지금 완료하기</a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="blocked-banner">
            <h3>위의 필수 데이터를 먼저 완료해주세요</h3>
            <p>AS-IS 분석, TO-BE 요구사항, Gap 분석이 모두 완료되면 기획서를 작성할 수 있습니다.</p>
        </div>
    </div>
</template>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- 상태 B: 생성 가능 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<template x-if="state === 'ready'">
    <div>
        <div class="doc-section">
            <div class="doc-section-title">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                입력 데이터 현황
            </div>
            <div class="data-status-grid">
                @foreach($dataStatus as $key => $status)
                @php
                    $isReady = $status['ready'];
                    $isOpt = $status['optional'] ?? false;
                    $cardClass = $isReady ? 'ready' : 'optional-missing';
                    $icon = $isReady ? '✅' : '⚠️';
                    $urlMap = ['asis' => $asIsUrl, 'tobe' => $toBeUrl, 'gap' => $gapUrl, 'screens' => $screensUrl];
                    $url = $urlMap[$key] ?? '#';
                @endphp
                <div class="data-card {{ $cardClass }}">
                    <span class="data-card-icon">{{ $icon }}</span>
                    <div>
                        <div class="data-card-title">{{ $status['label'] }}</div>
                        @if($isReady)
                            <div class="data-card-count">{{ $status['count'] }}{{ $status['count_label'] ? ' '.$status['count_label'] : '' }} 준비됨</div>
                        @else
                            <div class="data-card-count">선택 사항 (없음)</div>
                        @endif
                        <a href="{{ $url }}" class="data-card-link">→ 결과 보기</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-section-title">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                작성될 기획서
            </div>
            <div class="gen-info-grid">
                <div class="gen-info-card">
                    <div class="gen-info-label">데이터 섹션</div>
                    <div class="gen-info-value">{{ count($sectionStatuses) > 0 ? count($sectionStatuses) : 8 }}</div>
                </div>
                <div class="gen-info-card">
                    <div class="gen-info-label">웍스 생성 섹션</div>
                    <div class="gen-info-value">{{ $aiSectionCount }}</div>
                </div>
                <div class="gen-info-card">
                    <div class="gen-info-label">화면 상세</div>
                    <div class="gen-info-value">{{ $screenCount }}</div>
                </div>
                <div class="gen-info-card">
                    <div class="gen-info-label">템플릿</div>
                    <div class="gen-info-value" style="font-size:13px; margin-top:4px;">{{ $template?->name ?? '표준 v1' }}</div>
                </div>
            </div>
            <p style="font-size:12.5px; color:#64748b; margin:0;">
                웍스가 {{ $aiSectionCount }}개 섹션을 순차 생성합니다. 예상 소요 시간 약 {{ ceil($aiSectionCount * 5 / 60) }}~{{ ceil($aiSectionCount * 10 / 60) }}분.
                <a href="{{ $templatePreviewUrl }}" style="color:var(--t600,#7c3aed); font-weight:600;">템플릿 구조 보기 →</a>
            </p>
        </div>

        <div class="proceed-banner">
            <div class="proceed-banner-text">
                <h3>모든 사전 조건 충족 — 기획서 작성 시작</h3>
                <p>웍스가 {{ $aiSectionCount }}개 섹션을 자동 작성합니다. 완료 후 편집하여 저장할 수 있습니다.</p>
            </div>
            <button class="proceed-start-btn" @click="startGeneration()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                기획서 자동 작성 시작
            </button>
        </div>
    </div>
</template>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- 상태 C: 생성 진행 중 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<template x-if="state === 'generating'">
    <div>
        <div class="doc-section">
            <div class="doc-section-title">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed; animation:spin 1.2s linear infinite"><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                기획서 작성 중...
            </div>

            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progressPct}%`"></div>
            </div>
            <p style="font-size:12.5px; color:#64748b; margin:0 0 14px; text-align:right;"
               x-text="`${progress.done} / ${progress.total} 섹션 완료`"></p>

            <ul class="section-list">
                <template x-for="sec in sections" :key="sec.key">
                    <li>
                        <span class="section-status-icon">
                            <template x-if="sec.status === 'processing'"><span style="animation:spin .8s linear infinite; display:inline-block;">⏳</span></template>
                            <template x-if="sec.status === 'done'"><span>✅</span></template>
                            <template x-if="sec.status === 'failed'"><span>❌</span></template>
                            <template x-if="sec.status === 'pending'"><span style="opacity:.4;">○</span></template>
                        </span>
                        <span class="section-title" x-text="sec.title"
                              :style="sec.status === 'processing' ? 'color:#7c3aed; font-weight:600;' : ''"></span>
                        <span class="section-elapsed" x-show="sec.elapsed" x-text="`${sec.elapsed}s`"></span>
                    </li>
                </template>
            </ul>

            <div style="margin-top:14px; display:flex; gap:8px;">
                <button class="doc-btn secondary sm" @click="cancelGeneration()" x-show="!cancelling">취소</button>
                <span x-show="cancelling" style="font-size:12px; color:#64748b;">취소 중...</span>
            </div>
        </div>
    </div>
</template>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- 상태 D: 완료 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<template x-if="state === 'complete'">
    <div>
        {{-- 완료 통계 (방금 완료된 경우만 표시) --}}
        <template x-if="completionStats">
            <div class="doc-section" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7); border-color:#86efac;">
                <div class="doc-section-title" style="color:#166534;">
                    🎉 기획서 작성 완료
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" x-text="completionStats.total"></div>
                        <div class="stat-label">총 섹션</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" x-text="completionStats.total - completionStats.failed_count"></div>
                        <div class="stat-label">성공</div>
                    </div>
                    <div class="stat-card" x-show="completionStats.failed_count > 0">
                        <div class="stat-value" style="color:#dc2626;" x-text="completionStats.failed_count"></div>
                        <div class="stat-label">실패</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" x-text="(completionStats.tokens_in + completionStats.tokens_out).toLocaleString()"></div>
                        <div class="stat-label">토큰</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" x-text="`$${completionStats.cost_usd}`"></div>
                        <div class="stat-label">비용</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" x-text="`${completionStats.elapsed}s`"></div>
                        <div class="stat-label">소요 시간</div>
                    </div>
                </div>
            </div>
        </template>

        {{-- 실패 섹션 재시도 --}}
        <template x-if="failedKeys.length > 0">
            <div class="failed-alert" style="margin-bottom:16px;">
                <span style="font-size:18px; flex-shrink:0;">⚠️</span>
                <div class="failed-alert-body">
                    <strong>일부 섹션 생성 실패:</strong>
                    <ul class="failed-list">
                        <template x-for="key in failedKeys" :key="key">
                            <li x-text="key" style="display:flex; align-items:center; gap:8px;">
                                <span x-text="key"></span>
                                <button class="doc-btn ghost sm" @click="regenerateSection(key)"
                                        :disabled="regenerating === key" style="margin-left:8px;">
                                    <span x-text="regenerating === key ? '재생성 중...' : '재시도'"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>

        {{-- 기획서 미리보기 + 편집 --}}
        <div class="doc-section">
            <div class="doc-section-title">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                기획서 내용
                <span style="font-size:11px; color:#64748b; font-weight:400; margin-left:4px;">내용을 직접 수정할 수 있습니다</span>
                <span x-show="saveStatus === 'saved'" style="font-size:11px; color:#166534; font-weight:600; margin-left:auto;">✅ 저장됨</span>
                <span x-show="saveStatus === 'saving'" style="font-size:11px; color:#7c3aed; font-weight:600; margin-left:auto;">저장 중...</span>
            </div>

            <div class="doc-preview-wrap">
                <div class="doc-preview-toolbar">
                    <span>Markdown</span>
                    <button class="doc-btn secondary sm" @click="copyContent()">복사</button>
                    <button class="doc-btn primary sm" @click="saveContent()" :disabled="saveStatus === 'saving'">저장</button>
                </div>
                <textarea
                    class="doc-markdown-area"
                    x-model="documentContent"
                    @input="saveStatus = 'unsaved'"
                    @keydown.ctrl.s.prevent="saveContent()"
                    placeholder="기획서 내용이 여기에 표시됩니다..."
                    spellcheck="false"
                ></textarea>
            </div>
        </div>

        {{-- 하단 액션 --}}
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <a :href="_d.exportUrl" class="doc-btn secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                마크다운 다운로드
            </a>
            <button class="doc-btn ghost" @click="startRegeneration()" :disabled="regenerating !== null">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                전체 재작성
            </button>
            <span style="font-size:12px; color:#94a3b8; margin-left:auto;">
                @if($artifact->version)v{{ $artifact->version }}@endif
                @if(!empty($meta['generated_at']))· {{ \Illuminate\Support\Str::limit($meta['generated_at'], 19, '') }}@endif
            </span>
        </div>
    </div>
</template>

{{-- 오류 표시 --}}
<div x-show="error" x-transition style="background:#fef2f2; border:1.5px solid #fca5a5; border-radius:10px; padding:12px 16px; margin-top:16px; display:flex; align-items:center; gap:12px; font-size:13px; color:#991b1b;">
    <span>❌</span>
    <span x-text="error"></span>
    <button @click="error = null" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:16px; color:#991b1b;">✕</button>
</div>

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<style>@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }</style>
<script>
function docIndex() {
    const _d = JSON.parse(document.getElementById('doc-data').textContent);

    return {
        _d,
        state: _d.initialState,          // 'prerequisites' | 'ready' | 'generating' | 'complete'
        sections: [],
        progress: { done: 0, total: _d.aiSectionCount },
        completionStats: null,
        documentContent: _d.existingContent || '',
        saveStatus: 'saved',             // 'saved' | 'unsaved' | 'saving'
        failedKeys: _d.failedSections || [],
        error: null,
        cancelling: false,
        regenerating: null,
        _sseSource: null,
        _sessionId: null,

        get progressPct() {
            if (!this.progress.total) return 0;
            return Math.round((this.progress.done / this.progress.total) * 100);
        },

        init() {
            if (this.state === 'complete' && this.documentContent) {
                // already loaded
            }
        },

        // ── 생성 시작 ──────────────────────────────────────────────
        async startGeneration() {
            this.error = null;
            try {
                const res = await axios.post(_d.startUrl, {}, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
                });
                if (!res.data.success) throw new Error(res.data.message);

                this._sessionId = res.data.sessionId;
                const total = res.data.totalSections;
                this.progress = { done: 0, total };
                this.sections = this._buildSectionList(total);
                this.state = 'generating';
                this.completionStats = null;

                const sseUrl = _d.sseUrlTpl.replace('SESSION_ID', this._sessionId);
                this._connectSse(sseUrl);
            } catch (e) {
                this.error = e.response?.data?.message || e.message || '생성 시작 실패';
            }
        },

        startRegeneration() {
            this.state = 'ready';
            this.completionStats = null;
        },

        // ── SSE 연결 ────────────────────────────────────────────────
        _connectSse(url) {
            if (this._sseSource) this._sseSource.close();
            const es = new EventSource(url);
            this._sseSource = es;

            es.addEventListener('status', e => {
                const d = JSON.parse(e.data);
                this._updateSectionStatus(null, 'info', d.message);
            });

            es.addEventListener('section_progress', e => {
                const d = JSON.parse(e.data);
                this.progress.done = d.done;
                this.progress.total = d.total;
                this._applySectionProgress(d);
            });

            es.addEventListener('complete', e => {
                const d = JSON.parse(e.data);
                es.close();
                this.completionStats = d;
                this.failedKeys = Object.keys(d.failed || {});
                this.state = 'complete';
                // reload page to get fresh content from DB
                window.location.reload();
            });

            es.addEventListener('error', e => {
                es.close();
                try {
                    const d = JSON.parse(e.data);
                    this.error = d.message || '생성 중 오류가 발생했습니다.';
                } catch (_) {
                    this.error = '연결이 종료되었습니다.';
                }
                this.state = _d.initialState === 'complete' ? 'complete' : 'ready';
            });

            es.onerror = () => {
                if (es.readyState === EventSource.CLOSED) {
                    this.state = _d.initialState === 'complete' ? 'complete' : 'ready';
                }
            };
        },

        cancelGeneration() {
            if (!this._sessionId) return;
            this.cancelling = true;
            const url = _d.cancelUrlTpl.replace('SESSION_ID', this._sessionId);
            axios.post(url, {}, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } })
                .finally(() => {
                    if (this._sseSource) this._sseSource.close();
                    this.cancelling = false;
                    this.state = _d.initialState === 'complete' ? 'complete' : 'ready';
                });
        },

        // ── 섹션 목록 헬퍼 ─────────────────────────────────────────
        _buildSectionList(total) {
            // 서버가 섹션 상세 정보를 순서대로 보내므로 초기엔 빈 목록
            return Array.from({ length: total }, (_, i) => ({
                key: `section_${i}`,
                title: `섹션 ${i + 1}`,
                status: 'pending',
                elapsed: null,
            }));
        },

        _applySectionProgress(d) {
            const idx = this.sections.findIndex(s => s.key === d.key);
            const entry = {
                key: d.key,
                title: d.title || d.key,
                status: d.status,
                elapsed: d.elapsed || null,
            };
            if (idx >= 0) {
                this.sections[idx] = entry;
            } else {
                // Fill from the back of pending slots or push
                const pendingIdx = this.sections.findIndex(s => s.status === 'pending');
                if (pendingIdx >= 0) {
                    this.sections[pendingIdx] = entry;
                } else {
                    this.sections.push(entry);
                }
            }
        },

        _updateSectionStatus(key, status, message) {
            // used for generic status messages
        },

        // ── 저장 ────────────────────────────────────────────────────
        async saveContent() {
            if (!this.documentContent.trim()) return;
            this.saveStatus = 'saving';
            try {
                await axios.post(_d.saveUrl, { content: this.documentContent }, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
                });
                this.saveStatus = 'saved';
            } catch (e) {
                this.saveStatus = 'unsaved';
                this.error = '저장 실패: ' + (e.response?.data?.message || e.message);
            }
        },

        copyContent() {
            navigator.clipboard?.writeText(this.documentContent)
                .then(() => { /* toast */ })
                .catch(() => {});
        },

        // ── 단일 섹션 재생성 ─────────────────────────────────────────
        async regenerateSection(sectionKey) {
            this.regenerating = sectionKey;
            try {
                await axios.post(_d.regenerateUrl, { section_key: sectionKey }, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
                });
                this.failedKeys = this.failedKeys.filter(k => k !== sectionKey);
                window.location.reload();
            } catch (e) {
                this.error = '재생성 실패: ' + (e.response?.data?.message || e.message);
            } finally {
                this.regenerating = null;
            }
        },
    };
}
</script>
@endpush
