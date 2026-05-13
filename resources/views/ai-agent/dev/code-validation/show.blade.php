@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────── */
.cvs-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.cvs-header-left { display:flex; flex-direction:column; gap:4px; }
.cvs-breadcrumb { font-size:12px; color:#94a3b8; display:flex; align-items:center; gap:5px; }
.cvs-breadcrumb a { color:#7c3aed; text-decoration:none; }
.cvs-breadcrumb a:hover { text-decoration:underline; }
.cvs-title { font-size:20px; font-weight:800; color:#1e1b2e; }
.cvs-subtitle { font-size:13px; color:#64748b; margin-top:2px; }

/* ── Buttons ─────────────────────────────────────────── */
.cv-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.cv-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.cv-btn.primary:hover { background:var(--t700,#6d28d9); }
.cv-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.cv-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cv-btn.secondary:hover { background:#e2e8f0; }
.cv-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.cv-btn.ghost:hover { background:#f5f3ff; }
.cv-btn.danger    { background:#fee2e2; color:#b91c1c; border:1.5px solid #fecaca; }
.cv-btn.danger:hover { background:#fecaca; }
.cv-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Score panel ──────────────────────────────────────── */
.cvs-score-panel { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:14px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.cvs-score-main { text-align:center; flex-shrink:0; min-width:80px; }
.cvs-score-num { font-size:40px; font-weight:900; line-height:1; }
.cvs-score-num.high { color:#16a34a; }
.cvs-score-num.mid  { color:#d97706; }
.cvs-score-num.low  { color:#b91c1c; }
.cvs-score-label { font-size:11px; font-weight:600; color:#94a3b8; margin-top:4px; }
.cvs-cat-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; flex:1; }
.cvs-cat-item { background:#f8f7ff; border-radius:10px; padding:10px 14px; }
.cvs-cat-name { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.cvs-cat-score { font-size:18px; font-weight:800; color:#1e1b2e; }
.cvs-cat-bar { height:4px; border-radius:99px; background:#ede8ff; margin-top:5px; overflow:hidden; }
.cvs-cat-bar-fill { height:100%; border-radius:99px; }

/* ── Sections ─────────────────────────────────────────── */
.cvs-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:14px; }
.cvs-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 12px; display:flex; align-items:center; gap:8px; }

/* ── Violation cards ──────────────────────────────────── */
.v-list { display:flex; flex-direction:column; gap:10px; }
.v-card { border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; background:#fff; transition:border-color .15s; }
.v-card.critical { border-color:#fecaca; background:#fff8f8; }
.v-card.warning  { border-color:#fde68a; background:#fffdf5; }
.v-card.info     { border-color:#bfdbfe; background:#f8fbff; }
.v-card.ignored  { opacity:.5; }
.v-card.fixed    { border-color:#bbf7d0; background:#f0fdf4; }
.v-card-header { display:flex; align-items:flex-start; gap:10px; margin-bottom:8px; }
.v-severity-icon { font-size:15px; flex-shrink:0; margin-top:1px; }
.v-card-title { font-size:13.5px; font-weight:700; color:#1e1b2e; flex:1; }
.v-card-body { font-size:13px; color:#475569; margin-bottom:8px; }
.v-card-file { font-family:monospace; font-size:11.5px; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:2px 8px; display:inline-block; margin-bottom:8px; }
.v-suggestion { font-size:12.5px; color:#334155; background:#f8f7ff; border-left:3px solid #c4b5fd; padding:8px 12px; border-radius:0 6px 6px 0; margin-bottom:8px; }
.v-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:99px; }
.v-badge.cat-spec       { background:#dbeafe; color:#1d4ed8; }
.v-badge.cat-quality    { background:#f0fdf4; color:#15803d; }
.v-badge.cat-security   { background:#fee2e2; color:#b91c1c; }
.v-badge.cat-practices  { background:#fef3c7; color:#92400e; }
.v-badge.cat-perf       { background:#f5f3ff; color:#5b21b6; }
.v-badge.source-ai      { background:#f5f3ff; color:#5b21b6; }
.v-badge.source-static  { background:#f0fdf4; color:#15803d; }
.v-card-actions { display:flex; gap:6px; flex-wrap:wrap; }

/* ── Strengths ────────────────────────────────────────── */
.strength-item { font-size:13px; color:#334155; padding:8px 12px; background:#f0fdf4; border-left:3px solid #86efac; border-radius:0 6px 6px 0; margin-bottom:6px; }

/* ── Filter tabs ──────────────────────────────────────── */
.v-filter-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
.v-filter-tab { padding:4px 14px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #ede8ff; background:#fff; color:#475569; transition:all .12s; }
.v-filter-tab.active { background:#7c3aed; color:#fff; border-color:#7c3aed; }

/* ── No validation empty state ──────────────────────── */
.cvs-empty { text-align:center; padding:60px 24px; color:#94a3b8; }
.cvs-empty h3 { font-size:16px; font-weight:700; color:#475569; margin-bottom:8px; }
.cvs-empty p  { font-size:13.5px; margin-bottom:20px; }

/* ── Meta chips ──────────────────────────────────────── */
.cvs-meta-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
.cvs-chip { display:inline-flex; align-items:center; gap:4px; font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:99px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

/* ── Delete modal ────────────────────────────────────── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.modal-box { background:#fff; border-radius:16px; padding:28px; max-width:400px; width:100%; }
.modal-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 12px; }
.modal-box p  { font-size:13.5px; color:#64748b; margin:0 0 20px; }
.modal-actions { display:flex; gap:8px; justify-content:flex-end; }

@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="cvs-data">
{
    "hasValidation": {{ $hasValidation ? 'true' : 'false' }},
    "hasCode": {{ $hasCode ? 'true' : 'false' }},
    "validateUrl": "{{ $validateUrl }}",
    "autoFixUrl": "{{ $autoFixUrl }}",
    "ignoreUrlTpl": "{{ $ignoreUrlTpl }}",
    "destroyUrl": "{{ $destroyUrl }}",
    "indexUrl": "{{ $indexUrl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="cvsShow()" x-init="init()">

    {{-- 헤더 --}}
    <div class="cvs-header">
        <div class="cvs-header-left">
            <div class="cvs-breadcrumb">
                <a href="{{ $indexUrl }}">Output 검증</a>
                <span>/</span>
                <span>{{ $screen->screen_id }}</span>
            </div>
            <div class="cvs-title">[{{ $screen->screen_id }}] {{ $screen->title }}</div>
            @if($screen->description)
            <div class="cvs-subtitle">{{ $screen->description }}</div>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;padding-top:2px;">
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="cv-btn secondary sm" target="_blank">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                버전 이력
            </a>
            @endif
            @if($hasValidation)
            <button class="cv-btn ghost sm" :disabled="isValidating" @click="validate()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isValidating ? 'animation:spin 1s linear infinite':''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="isValidating ? '검증 중...' : '재검증'"></span>
            </button>
            <button class="cv-btn danger sm" @click="showDeleteConfirm = true">삭제</button>
            @elseif($hasCode)
            <button class="cv-btn primary" :disabled="isValidating" @click="validate()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isValidating ? 'animation:spin 1s linear infinite':''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-text="isValidating ? '검증 중...' : '웍스 검증 시작'"></span>
            </button>
            @endif
        </div>
    </div>

    {{-- 메시지 --}}
    <template x-if="statusMessage">
        <div :style="statusOk ? 'border-color:#bbf7d0;background:#f0fdf4' : 'border-color:#fecaca;background:#fff1f2'"
             style="border:1.5px solid;border-radius:10px;padding:10px 16px;margin-bottom:14px;font-size:13px;"
             x-text="statusMessage"></div>
    </template>

    @if($hasValidation && $decoded)

    {{-- 메타 --}}
    @php $meta = $validationArtifact->meta ?? []; @endphp
    <div class="cvs-meta-row">
        @if(($validationArtifact->version ?? 1) > 1)
        <span class="cvs-chip">v{{ $validationArtifact->version }}</span>
        @endif
        @if(!empty($meta['model']))
        <span class="cvs-chip" style="background:#f5f3ff;color:#5b21b6;border-color:#ddd6fe;">{{ Str::after($meta['model'], 'claude-') }}</span>
        @endif
        @if(!empty($meta['tokens_in']))
        <span class="cvs-chip" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">↑{{ number_format($meta['tokens_in']) }} / ↓{{ number_format($meta['tokens_out'] ?? 0) }} tok</span>
        @endif
        @if(!empty($meta['validated_at']))
        <span class="cvs-chip">{{ \Carbon\Carbon::parse($meta['validated_at'])->format('Y-m-d H:i') }}</span>
        @endif
        <span class="cvs-chip">{{ $decoded['static_available'] ? '정적 분석 + 웍스' : '웍스 검수만' }}</span>
    </div>

    {{-- 점수 패널 --}}
    @php
        $score      = $decoded['compliance_score'] ?? 0;
        $scoreClass = $score >= 80 ? 'high' : ($score >= 60 ? 'mid' : 'low');
        $catScores  = $decoded['category_scores'] ?? [];
        $catLabels  = [
            'spec_compliance' => '명세 부합도',
            'code_quality'    => '코드 품질',
            'security'        => '보안',
            'best_practices'  => '베스트 프랙티스',
            'performance'     => '성능',
        ];
        $catColors = [
            'spec_compliance' => '#3b82f6',
            'code_quality'    => '#10b981',
            'security'        => '#ef4444',
            'best_practices'  => '#f59e0b',
            'performance'     => '#8b5cf6',
        ];
    @endphp
    <div class="cvs-score-panel">
        <div class="cvs-score-main">
            <div class="cvs-score-num {{ $scoreClass }}">{{ $score }}</div>
            <div class="cvs-score-label">/ 100점</div>
        </div>
        <div class="cvs-cat-grid">
            @foreach($catLabels as $key => $label)
            @php $cs = $catScores[$key] ?? 0; @endphp
            <div class="cvs-cat-item">
                <div class="cvs-cat-name">{{ $label }}</div>
                <div class="cvs-cat-score">{{ $cs }}</div>
                <div class="cvs-cat-bar">
                    <div class="cvs-cat-bar-fill" style="width:{{ $cs }}%;background:{{ $catColors[$key] ?? '#7c3aed' }};"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- 위반 사항 --}}
    @php
        $violations    = $decoded['violations'] ?? [];
        $activeViol    = array_values(array_filter($violations, fn($v) => empty($v['ignored']) && empty($v['fixed'])));
        $ignoredViol   = array_values(array_filter($violations, fn($v) => !empty($v['ignored'])));
        $fixedViol     = array_values(array_filter($violations, fn($v) => !empty($v['fixed'])));
        $criticals     = array_filter($activeViol, fn($v) => ($v['severity'] ?? '') === 'critical');
        $warnings      = array_filter($activeViol, fn($v) => ($v['severity'] ?? '') === 'warning');
        $infos         = array_filter($activeViol, fn($v) => ($v['severity'] ?? '') === 'info');
    @endphp
    <div class="cvs-section">
        <div class="cvs-section-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            위반 사항
            <span style="font-size:11.5px;font-weight:400;color:#94a3b8;">{{ count($activeViol) }}건 활성</span>
            @if(count($criticals) > 0)
            <span style="font-size:11.5px;background:#fee2e2;color:#b91c1c;padding:1px 8px;border-radius:99px;">🔴 {{ count($criticals) }}</span>
            @endif
            @if(count($warnings) > 0)
            <span style="font-size:11.5px;background:#fef3c7;color:#92400e;padding:1px 8px;border-radius:99px;">🟡 {{ count($warnings) }}</span>
            @endif
        </div>

        {{-- Filter tabs --}}
        <div class="v-filter-tabs">
            <div class="v-filter-tab" :class="activeFilter === 'all' ? 'active' : ''" @click="activeFilter = 'all'">전체 ({{ count($activeViol) }})</div>
            @if(count($criticals) > 0)
            <div class="v-filter-tab" :class="activeFilter === 'critical' ? 'active' : ''" @click="activeFilter = 'critical'">🔴 Critical ({{ count($criticals) }})</div>
            @endif
            @if(count($warnings) > 0)
            <div class="v-filter-tab" :class="activeFilter === 'warning' ? 'active' : ''" @click="activeFilter = 'warning'">🟡 Warning ({{ count($warnings) }})</div>
            @endif
            @if(count($infos) > 0)
            <div class="v-filter-tab" :class="activeFilter === 'info' ? 'active' : ''" @click="activeFilter = 'info'">🔵 Info ({{ count($infos) }})</div>
            @endif
        </div>

        @if(count($activeViol) === 0)
        <div style="text-align:center;padding:30px 20px;color:#16a34a;">
            <div style="font-size:24px;margin-bottom:8px;">✅</div>
            <div style="font-size:14px;font-weight:700;">활성 위반 사항 없음</div>
        </div>
        @else
        <div class="v-list">
            @foreach($activeViol as $viol)
            @php
                $sev       = $viol['severity'] ?? 'info';
                $cat       = $viol['category'] ?? '';
                $icon      = match($sev) { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };
                $catClass  = match($cat) {
                    'spec_compliance' => 'cat-spec',
                    'code_quality'    => 'cat-quality',
                    'security'        => 'cat-security',
                    'best_practices'  => 'cat-practices',
                    'performance'     => 'cat-perf',
                    default           => 'cat-quality',
                };
                $catLabel  = match($cat) {
                    'spec_compliance' => '명세 부합도',
                    'code_quality'    => '코드 품질',
                    'security'        => '보안',
                    'best_practices'  => '베스트 프랙티스',
                    'performance'     => '성능',
                    default           => $cat,
                };
                $violId    = $viol['id'] ?? '';
                $autoFixed = !empty($viol['auto_fixable']);
                $srcClass  = ($viol['source'] ?? 'ai') === 'static' ? 'source-static' : 'source-ai';
                $srcLabel  = ($viol['source'] ?? 'ai') === 'static' ? '정적분석' : '웍스';
            @endphp
            <div class="v-card {{ $sev }}" x-show="activeFilter === 'all' || activeFilter === '{{ $sev }}'">
                <div class="v-card-header">
                    <span class="v-severity-icon">{{ $icon }}</span>
                    <div style="flex:1;">
                        <div class="v-card-title">{{ $viol['title'] ?? '' }}</div>
                        <div style="display:flex;gap:5px;margin-top:4px;flex-wrap:wrap;">
                            <span class="v-badge {{ $catClass }}">{{ $catLabel }}</span>
                            <span class="v-badge {{ $srcClass }}">{{ $srcLabel }}</span>
                            @if($autoFixed)
                            <span class="v-badge" style="background:#f0fdf4;color:#15803d;">✨ 자동수정 가능</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="v-card-body">{{ $viol['description'] ?? '' }}</div>
                @if(!empty($viol['file']))
                <div>
                    <span class="v-card-file">{{ $viol['file'] }}@if(!empty($viol['line'])):{{ $viol['line'] }}@endif</span>
                </div>
                @endif
                @if(!empty($viol['suggestion']))
                <div class="v-suggestion">💡 {{ $viol['suggestion'] }}</div>
                @endif
                <div class="v-card-actions">
                    @if($autoFixed)
                    <button class="cv-btn ghost sm"
                            :disabled="fixingId === '{{ $violId }}'"
                            @click="autoFix('{{ $violId }}')">
                        <span x-text="fixingId === '{{ $violId }}' ? '수정 중...' : '✨ 웍스 자동 수정'"></span>
                    </button>
                    @endif
                    <button class="cv-btn secondary sm"
                            :disabled="ignoringId === '{{ $violId }}'"
                            @click="ignoreViolation('{{ $violId }}', $el)">
                        <span x-text="ignoringId === '{{ $violId }}' ? '처리 중...' : '무시'"></span>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Fixed & Ignored --}}
        @if(count($fixedViol) > 0 || count($ignoredViol) > 0)
        <details style="margin-top:16px;">
            <summary style="font-size:12.5px;font-weight:600;color:#94a3b8;cursor:pointer;">
                처리된 항목 (수정 {{ count($fixedViol) }}건, 무시 {{ count($ignoredViol) }}건)
            </summary>
            <div class="v-list" style="margin-top:10px;opacity:.6;">
                @foreach($fixedViol as $viol)
                <div class="v-card fixed" style="padding:10px 14px;">
                    <div style="font-size:12.5px;font-weight:600;color:#15803d;">✅ 수정됨: {{ $viol['title'] ?? '' }}</div>
                </div>
                @endforeach
                @foreach($ignoredViol as $viol)
                <div class="v-card ignored" style="padding:10px 14px;">
                    <div style="font-size:12.5px;font-weight:600;color:#94a3b8;">🚫 무시됨: {{ $viol['title'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
        </details>
        @endif
    </div>

    {{-- 잘된 점 --}}
    @if(!empty($decoded['strengths']))
    <div class="cvs-section">
        <div class="cvs-section-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            잘된 점
        </div>
        @foreach($decoded['strengths'] as $strength)
        <div class="strength-item">✅ {{ $strength }}</div>
        @endforeach
    </div>
    @endif

    @elseif(!$hasCode)
    {{-- 코드 없음 --}}
    <div class="cvs-section">
        <div class="cvs-empty">
            <h3>Frontend 코드가 없습니다</h3>
            <p>T40에서 먼저 이 화면의 Frontend 코드를 생성해주세요.</p>
        </div>
    </div>
    @else
    {{-- 미검증 --}}
    <div class="cvs-section">
        <div class="cvs-empty">
            <h3>아직 검증되지 않았습니다</h3>
            <p>웍스와 정적 분석 도구가 이 화면 코드를 자동으로 검수합니다.<br>
            명세 부합도, 보안, 베스트 프랙티스 등 5가지 영역을 평가합니다.</p>
            <button class="cv-btn primary" style="margin:0 auto;" :disabled="isValidating" @click="validate()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isValidating ? 'animation:spin 1s linear infinite':''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-text="isValidating ? '검증 중...' : '검증 시작'"></span>
            </button>
        </div>
    </div>
    @endif

    {{-- 삭제 확인 모달 --}}
    <template x-if="showDeleteConfirm">
        <div class="modal-overlay" @click.self="showDeleteConfirm = false">
            <div class="modal-box">
                <h3>검증 결과 삭제</h3>
                <p>[{{ $screen->screen_id }}] {{ $screen->title }}의 검증 결과를 삭제합니다. 되돌릴 수 없습니다.</p>
                <div class="modal-actions">
                    <button class="cv-btn secondary" @click="showDeleteConfirm = false">취소</button>
                    <button class="cv-btn danger" :disabled="isDeleting" @click="confirmDelete()">
                        <span x-text="isDeleting ? '삭제 중...' : '삭제 확인'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function cvsShow() {
    return {
        cfg: {},
        isValidating: false,
        fixingId:     null,
        ignoringId:   null,
        showDeleteConfirm: false,
        isDeleting:   false,
        statusMessage: null,
        statusOk:     false,
        activeFilter: 'all',

        init() {
            const raw = document.getElementById('cvs-data')?.textContent;
            if (raw) this.cfg = JSON.parse(raw);
        },

        async validate() {
            if (this.isValidating) return;
            this.isValidating  = true;
            this.statusMessage = null;

            try {
                const res  = await fetch(this.cfg.validateUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.success) {
                    this.showMsg(
                        `검증 완료! 점수: ${data.compliance_score}점, 위반 ${data.violations_count}건 (v${data.version})`,
                        true
                    );
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showMsg('검증 실패: ' + (data.message || '알 수 없는 오류'), false);
                }
            } catch (e) {
                this.showMsg('오류: ' + e.message, false);
            } finally {
                this.isValidating = false;
            }
        },

        async autoFix(violationId) {
            this.fixingId     = violationId;
            this.statusMessage = null;

            try {
                const res  = await fetch(this.cfg.autoFixUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ violation_id: violationId }),
                });
                const data = await res.json();

                if (data.success) {
                    this.showMsg('자동 수정 완료: ' + data.explanation + ` (코드 v${data.new_version})`, true);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showMsg('수정 실패: ' + (data.message || '알 수 없는 오류'), false);
                }
            } catch (e) {
                this.showMsg('오류: ' + e.message, false);
            } finally {
                this.fixingId = null;
            }
        },

        async ignoreViolation(violationId, btn) {
            this.ignoringId = violationId;
            const url = this.cfg.ignoreUrlTpl.replace('VIOLATION_ID', violationId);

            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    // Hide the card
                    const card = btn?.closest('.v-card');
                    if (card) card.style.display = 'none';
                    this.showMsg('무시 처리되었습니다.', true);
                } else {
                    this.showMsg('처리 실패: ' + (data.message || ''), false);
                }
            } catch (e) {
                this.showMsg('오류: ' + e.message, false);
            } finally {
                this.ignoringId = null;
            }
        },

        async confirmDelete() {
            if (this.isDeleting) return;
            this.isDeleting = true;

            try {
                const res  = await fetch(this.cfg.destroyUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = this.cfg.indexUrl;
                } else {
                    this.showDeleteConfirm = false;
                    this.showMsg('삭제 실패: ' + (data.message || ''), false);
                }
            } catch (e) {
                this.showDeleteConfirm = false;
                this.showMsg('오류: ' + e.message, false);
            } finally {
                this.isDeleting = false;
            }
        },

        showMsg(text, ok) {
            this.statusMessage = text;
            this.statusOk      = ok;
        },
    };
}
</script>
@endpush
