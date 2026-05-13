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
.doc-btn.primary:hover   { background:var(--t700,#6d28d9); }
.doc-btn.primary:disabled { opacity:.45; cursor:not-allowed; pointer-events:none; }
.doc-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.doc-btn.secondary:hover { background:#e2e8f0; }

/* ── Cards ───────────────────────────────────────────────────────── */
.doc-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.doc-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Template Info ───────────────────────────────────────────────── */
.tpl-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:18px; }
.tpl-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 16px; }
.tpl-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.tpl-info-value { font-size:16px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Data status grid ────────────────────────────────────────────── */
.data-status-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:4px; }
.data-card { border-radius:12px; border:1.5px solid; padding:14px 16px; display:flex; align-items:flex-start; gap:12px; transition:box-shadow .15s; }
.data-card:hover { box-shadow:0 2px 10px rgba(124,58,237,.1); }
.data-card.ready   { background:#f0fdf4; border-color:#86efac; }
.data-card.missing { background:#fef2f2; border-color:#fca5a5; }
.data-card.optional-missing { background:#fffbeb; border-color:#fde68a; }
.data-card-icon { font-size:22px; flex-shrink:0; margin-top:1px; }
.data-card-body {}
.data-card-title { font-size:13.5px; font-weight:700; color:#1e1b2e; margin-bottom:2px; }
.data-card-count { font-size:11.5px; font-weight:600; color:#64748b; }
.data-card-link  { font-size:11.5px; font-weight:600; color:var(--t600,#7c3aed); text-decoration:none; margin-top:4px; display:inline-block; }
.data-card-link:hover { text-decoration:underline; }

/* ── Missing warning ─────────────────────────────────────────────── */
.missing-alert { background:#fef3c7; border:1.5px solid #f59e0b; border-radius:10px; padding:14px 16px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px; }
.missing-alert-icon { font-size:18px; flex-shrink:0; margin-top:1px; }
.missing-alert-body { font-size:13px; color:#92400e; line-height:1.6; }
.missing-alert-list { margin:6px 0 0; padding-left:18px; }

/* ── Section tree ────────────────────────────────────────────────── */
.section-tree { list-style:none; margin:0; padding:0; }
.section-tree li { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; font-size:13px; margin-bottom:2px; }
.section-tree li:hover { background:#f9f8ff; }
.section-tree .section-id   { font-family:monospace; font-size:11.5px; color:#a78bfa; font-weight:700; min-width:26px; }
.section-tree .section-name { flex:1; color:#374151; font-weight:600; }
.section-tree .section-badge { padding:2px 8px; border-radius:20px; font-size:10.5px; font-weight:700; white-space:nowrap; }
.section-tree .badge-ready    { background:#dcfce7; color:#166534; }
.section-tree .badge-ai       { background:#ede9fe; color:var(--t700,#6d28d9); }
.section-tree .badge-missing  { background:#fee2e2; color:#991b1b; }
.section-tree .badge-optional { background:#fef9c3; color:#854d0e; }
.section-tree .badge-placeholder { background:#f1f5f9; color:#475569; }
.section-tree .section-divider { height:1px; background:#f3eeff; margin:4px 10px; border:none; }

/* ── Proceed banner ──────────────────────────────────────────────── */
.proceed-banner { background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%); border-radius:14px; padding:22px 26px; color:#fff; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.proceed-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.proceed-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.proceed-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 20px; font-size:13.5px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.proceed-btn:hover { background:#f5f3ff; }
.proceed-btn:disabled { opacity:.5; cursor:not-allowed; }

.blocked-banner { background:#f1f5f9; border:1.5px solid #e2e8f0; border-radius:14px; padding:20px 24px; color:#475569; text-align:center; }
.blocked-banner h3 { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.blocked-banner p  { font-size:13px; margin:0; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="docPreview()" x-init="init()">

{{-- ── 헤더 ────────────────────────────────────────────────────────── --}}
<div class="doc-header">
    <div class="doc-header-left">
        <h1>기획서 작성</h1>
        <p>AS-IS · TO-BE · Gap 분석 결과를 종합하여 프로젝트 기획서를 자동 작성합니다.</p>
    </div>
    <div class="doc-header-right">
        @if($template)
        <span style="font-size:12px;color:#64748b;">템플릿: <strong>{{ $template->name }}</strong> v{{ $template->version }}</span>
        @endif
    </div>
</div>

{{-- ── 누락 데이터 경고 ──────────────────────────────────────────────── --}}
@if(!empty($missingRequired))
<div class="missing-alert">
    <span class="missing-alert-icon">⚠️</span>
    <div class="missing-alert-body">
        <strong>기획서 작성에 필요한 데이터가 준비되지 않았습니다.</strong>
        아래 항목을 먼저 완료해주세요.
        <ul class="missing-alert-list">
            @foreach($missingRequired as $item)
            <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- ── 데이터 준비 현황 ─────────────────────────────────────────────── --}}
<div class="doc-section">
    <div class="doc-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        입력 데이터 현황
    </div>
    <div class="data-status-grid">
        @foreach($dataStatus as $key => $status)
        @php
            $isReady = $status['ready'];
            $isOptional = $status['optional'] ?? false;
            $cardClass = $isReady ? 'ready' : ($isOptional ? 'optional-missing' : 'missing');
            $icon = $isReady ? '✅' : ($isOptional ? '⚠️' : '❌');
        @endphp
        <div class="data-card {{ $cardClass }}">
            <span class="data-card-icon">{{ $icon }}</span>
            <div class="data-card-body">
                <div class="data-card-title">{{ $status['label'] }}</div>
                @if($isReady && $status['count'] !== null)
                    <div class="data-card-count">{{ $status['count'] }}{{ $status['count_label'] ? ' ' . $status['count_label'] : '' }} 준비됨</div>
                @elseif(!$isReady)
                    <div class="data-card-count">{{ $isOptional ? '선택 사항' : '필수 — 미완료' }}</div>
                @endif
                @php
                    $routeMap = [
                        'asis'    => $asIsUrl,
                        'tobe'    => $toBeUrl,
                        'gap'     => $gapUrl,
                        'screens' => $screensUrl,
                    ];
                    $url = $routeMap[$key] ?? '#';
                @endphp
                @if(!$isReady)
                <a href="{{ $url }}" class="data-card-link">→ 지금 완료하기</a>
                @else
                <a href="{{ $url }}" class="data-card-link">→ 결과 보기</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── 템플릿 정보 ──────────────────────────────────────────────────── --}}
@if($template)
<div class="doc-section">
    <div class="doc-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        템플릿 개요
    </div>
    <div class="tpl-info-grid">
        <div class="tpl-info-card">
            <div class="tpl-info-label">전체 섹션</div>
            <div class="tpl-info-value">{{ count($sectionStatuses) }}</div>
        </div>
        <div class="tpl-info-card">
            <div class="tpl-info-label">웍스 생성 섹션</div>
            <div class="tpl-info-value">{{ $template->getAiSectionCount() }}</div>
        </div>
        <div class="tpl-info-card">
            <div class="tpl-info-label">출력 형식</div>
            <div class="tpl-info-value">Markdown</div>
        </div>
        <div class="tpl-info-card">
            <div class="tpl-info-label">버전</div>
            <div class="tpl-info-value">{{ $template->version }}</div>
        </div>
    </div>
    <p style="font-size:13px;color:#64748b;margin:0;">{{ $template->description }}</p>
</div>

{{-- ── 섹션 구조 미리보기 ───────────────────────────────────────────── --}}
<div class="doc-section">
    <div class="doc-section-title">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#7c3aed"><path d="M4 6h16M4 10h16M4 14h8M4 18h8"/></svg>
        섹션 구조
        <span style="font-size:11px;color:#64748b;font-weight:400;margin-left:4px;">클릭하면 세부 항목 확인</span>
    </div>

    <ul class="section-tree">
        @foreach($sectionStatuses as $sec)
        @php
            $template_section = collect($template->getSections())->firstWhere('id', $sec['section_id']);
            $subsections = $template_section['subsections'] ?? [];

            $badgeClass = match($sec['status']) {
                'ready'      => 'badge-ready',
                'ai_pending' => 'badge-ai',
                'missing'    => $sec['optional'] ? 'badge-optional' : 'badge-missing',
                default      => 'badge-placeholder',
            };
            $badgeLabel = match($sec['status']) {
                'ready'      => '✅ 데이터 준비됨',
                'ai_pending' => '🤖 웍스 생성 예정',
                'missing'    => $sec['optional'] ? '⚠️ 선택 사항' : '❌ 필수 데이터 누락',
                default      => '기타',
            };
        @endphp
        <li x-data="{ open: false }" @click="open = !open" style="cursor:pointer; flex-direction:column; align-items:stretch;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="section-id">{{ $sec['section_id'] }}</span>
                <span class="section-name">{{ $sec['section_title'] }}</span>
                <span class="section-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                <svg width="12" height="12" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"
                     :style="open ? 'transform:rotate(180deg)' : ''" style="transition:.15s;flex-shrink:0;margin-left:auto">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            @if(count($subsections) > 0)
            <ul x-show="open" x-transition style="list-style:none; margin:6px 0 2px 28px; padding:0;">
                @foreach($subsections as $sub)
                @php
                    $subType = $sub['type'] ?? 'data_injection';
                    $subBadge = match($subType) {
                        'ai_generated' => ['badge-ai',          '🤖 웍스 생성'],
                        'placeholder'  => ['badge-placeholder', '📍 Placeholder'],
                        default        => ['badge-ready',       '📋 데이터 주입'],
                    };
                @endphp
                <li style="display:flex; align-items:center; gap:8px; padding:5px 8px; border-radius:6px; font-size:12px; cursor:default;">
                    <span style="color:#c4b5fd; font-family:monospace; min-width:28px; font-size:11px;">{{ $sub['id'] }}</span>
                    <span style="flex:1; color:#475569;">{{ $sub['title'] }}</span>
                    <span class="section-badge {{ $subBadge[0] }}" style="font-size:10px;">{{ $subBadge[1] }}</span>
                </li>
                @endforeach
            </ul>
            @endif
        </li>
        @if(!$loop->last)
        <hr class="section-divider">
        @endif
        @endforeach
    </ul>
</div>
@else
<div class="doc-section" style="text-align:center; padding:40px 20px; color:#64748b;">
    <svg width="40" height="40" fill="none" stroke="#c4b5fd" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <p style="font-size:14px; font-weight:600; color:#1e1b2e; margin:0 0 6px;">활성 템플릿이 없습니다</p>
    <p style="font-size:13px; margin:0;">마이그레이션을 실행하여 표준 템플릿을 등록해주세요.</p>
</div>
@endif

{{-- ── 기획서 작성 시작 배너 ────────────────────────────────────────── --}}
<div style="margin-top:4px;">
    @if($canProceed)
    <div class="proceed-banner">
        <div class="proceed-banner-text">
            <h3>기획서 작성 준비 완료</h3>
            <p>모든 필수 데이터가 준비되었습니다. 웍스가 기획서를 자동 작성합니다.</p>
        </div>
        {{-- T22에서 이 버튼의 href를 기획서 작성 URL로 교체 --}}
        <button class="proceed-btn" disabled title="T22에서 구현 예정">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            기획서 작성 시작 (T22)
        </button>
    </div>
    @else
    <div class="blocked-banner">
        <h3>필수 데이터 준비 후 기획서를 작성할 수 있습니다</h3>
        <p>위 '입력 데이터 현황'에서 누락된 항목을 먼저 완료해주세요.</p>
    </div>
    @endif
</div>

</div>
@endsection

@push('scripts')
<script>
function docPreview() {
    return {
        init() {
            // T22에서 실시간 데이터 상태 폴링 등 확장 가능
        },
    };
}
</script>
@endpush
