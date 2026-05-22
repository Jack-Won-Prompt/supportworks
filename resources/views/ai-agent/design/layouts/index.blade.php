@extends('layouts.ai-agent')
@section('title', '표준 Layout / Grid — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.ls-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.ls-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.ls-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.ls-header-right { display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.ls-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.ls-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.ls-btn.primary:hover   { background:var(--t700,#6d28d9); }
.ls-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.ls-btn.secondary:hover { background:#e2e8f0; }
.ls-btn.ghost { background:transparent; color:var(--t600); border:1.5px solid var(--t300,#c4b5fd); }
.ls-btn.ghost:hover { background:#f5f3ff; }
.ls-btn.sm { padding:4px 10px; font-size:12px; }
.ls-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── Empty state ─────────────────────────────────────────────────── */
.ls-empty { background:#fff; border:2px dashed #ddd6fe; border-radius:16px; padding:48px 24px; text-align:center; }
.ls-empty-icon { font-size:40px; margin-bottom:12px; }
.ls-empty h3 { font-size:16px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.ls-empty p  { font-size:13px; color:#64748b; margin:0 0 20px; }
.ls-url-input { width:100%; max-width:480px; border:1.5px solid #ddd6fe; border-radius:10px; padding:10px 14px; font-size:13.5px; color:#1e1b2e; outline:none; box-sizing:border-box; }
.ls-url-input:focus { border-color:var(--t500,#8b5cf6); }
.ls-pat-warn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#fffbeb; border:1.5px solid #fde68a; border-radius:8px; font-size:12.5px; color:#92400e; margin-top:12px; }

/* ── Meta bar ────────────────────────────────────────────────────── */
.ls-meta-bar { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 18px; margin-bottom:18px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.ls-meta-item { font-size:12.5px; color:#64748b; display:flex; align-items:center; gap:5px; }
.ls-meta-item strong { color:#1e1b2e; }
.ls-meta-sep { color:#e2e8f0; }

/* ── Stats ───────────────────────────────────────────────────────── */
.ls-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:10px; margin-bottom:18px; }
.ls-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; text-align:center; }
.ls-stat-num   { font-size:22px; font-weight:800; color:var(--t600); }
.ls-stat-label { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

/* ── Section ─────────────────────────────────────────────────────── */
.ls-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.ls-section-title { font-size:14px; font-weight:800; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
.ls-section-title small { font-size:12px; font-weight:500; color:#94a3b8; }

/* ── Standard layout card ────────────────────────────────────────── */
.ls-std-card { border:1.5px solid #ede8ff; border-radius:12px; padding:18px 20px; margin-bottom:14px; }
.ls-std-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:12px; }
.ls-std-name { font-size:14px; font-weight:700; color:#1e1b2e; margin:0; }
.ls-std-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; background:#ede8ff; color:#6d28d9; white-space:nowrap; }
.ls-std-desc { font-size:12.5px; color:#64748b; margin:0 0 12px; }
.ls-std-desc-input { width:100%; border:1.5px solid #e2e8f0; border-radius:8px; padding:7px 10px; font-size:12.5px; color:#374151; outline:none; box-sizing:border-box; }
.ls-std-desc-input:focus { border-color:var(--t500); }

/* Grid visualizer */
.ls-grid-vis { display:flex; gap:3px; height:36px; align-items:stretch; margin-bottom:12px; border-radius:6px; overflow:hidden; }
.ls-grid-col { flex:1; border-radius:3px; min-width:4px; }

/* Spec table */
.ls-spec-row { display:flex; flex-wrap:wrap; gap:10px 20px; font-size:12px; color:#374151; margin-bottom:10px; }
.ls-spec-item { display:flex; gap:4px; }
.ls-spec-item span:first-child { color:#94a3b8; font-weight:600; }
.ls-spec-item strong { color:#1e1b2e; font-weight:700; }

/* Frames toggle */
.ls-frames-toggle { font-size:11.5px; color:var(--t600); cursor:pointer; background:none; border:none; padding:0; font-weight:600; text-decoration:underline; }

/* ── Spacing scale ────────────────────────────────────────────────── */
.ls-spacing-scale { display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; padding:8px 0; }
.ls-spacing-item  { display:flex; flex-direction:column; align-items:center; gap:4px; min-width:36px; }
.ls-spacing-bar   { width:28px; background:var(--t400,#a78bfa); border-radius:4px 4px 0 0; min-height:4px; transition:height .2s; }
.ls-spacing-val   { font-size:10.5px; font-weight:700; color:#64748b; }
.ls-spacing-cnt   { font-size:9.5px; color:#94a3b8; }

/* ── Non-standard list ───────────────────────────────────────────── */
.ls-nonst-item { display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
.ls-nonst-item:last-child { border-bottom:none; }
.ls-nonst-icon { flex-shrink:0; width:22px; height:22px; border-radius:50%; background:#fff7ed; display:flex; align-items:center; justify-content:center; font-size:12px; margin-top:1px; }
.ls-nonst-name { font-size:12.5px; font-weight:700; color:#1e1b2e; margin:0 0 2px; }
.ls-nonst-dev  { font-size:11.5px; color:#64748b; margin:0; }

/* ── Spinner overlay ─────────────────────────────────────────────── */
.ls-spinner-overlay { position:fixed; inset:0; background:rgba(255,255,255,.8); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:9999; gap:14px; }
.ls-spinner { width:40px; height:40px; border:4px solid #ede8ff; border-top-color:var(--t500); border-radius:50%; animation:ls-spin .8s linear infinite; }
@keyframes ls-spin { to { transform:rotate(360deg); } }
.ls-spinner-text { font-size:14px; font-weight:600; color:#6d28d9; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="lsPage({
    analyzeUrl: '{{ route('ai-agent.projects.design.layout.analyze', $project) }}',
    exportUrl:  '{{ route('ai-agent.projects.design.layout.export', $project) }}',
    updateBase: '{{ route('ai-agent.projects.design.layout.update', [$project, '__KEY__']) }}',
    hasArtifact: {{ $artifact ? 'true' : 'false' }},
})" x-cloak>

    {{-- Spinner --}}
    <div x-show="analyzing" class="ls-spinner-overlay" style="display:none;">
        <div class="ls-spinner"></div>
        <div class="ls-spinner-text">레이아웃 패턴 분석 중...</div>
    </div>

    <div class="ls-header">
        <div class="ls-header-left">
            <h1>표준 Layout / Grid</h1>
            <p>Figma 파일의 모든 프레임을 분석하여 사용 패턴을 식별하고 레이아웃 표준을 정의합니다.</p>
        </div>
        <div class="ls-header-right" x-show="hasArtifact">
            <button @click="showAnalyzeModal = true" class="ls-btn secondary">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                재분석
            </button>
            <a :href="exportUrl" class="ls-btn ghost">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                JSON 다운로드
            </a>
        </div>
    </div>

    {{-- ── Empty State ──────────────────────────────────────── --}}
    @if(!$artifact)
    <div class="ls-empty">
        <div class="ls-empty-icon">📐</div>
        <h3>레이아웃 분석이 없습니다</h3>
        <p>Figma 파일의 모든 프레임을 분석하여 표준 레이아웃 패턴을 자동으로 식별합니다.</p>

        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <input type="url" x-model="figmaUrl" class="ls-url-input"
                   placeholder="https://www.figma.com/file/ABC123/My-Design-System">

            <button @click="doAnalyze()" :disabled="!figmaUrl || analyzing" class="ls-btn primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                레이아웃 분석
            </button>

            @if(!$hasPat)
            <div class="ls-pat-warn">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Figma PAT이 설정되지 않았습니다.
                <a href="{{ route('ai-agent.settings.figma') }}" style="color:#92400e;font-weight:700;">설정하기 →</a>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Has Artifact ──────────────────────────────────────── --}}
    @if($artifact)
    {{-- Meta bar --}}
    <div class="ls-meta-bar">
        <div class="ls-meta-item">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
            <strong>{{ $artifact->meta['figma_file_name'] ?? 'Figma 파일' }}</strong>
        </div>
        <span class="ls-meta-sep">|</span>
        <div class="ls-meta-item">버전 <strong>v{{ $artifact->version }}</strong></div>
        <span class="ls-meta-sep">|</span>
        <div class="ls-meta-item">
            분석일: <strong>{{ isset($artifact->meta['extracted_at']) ? \Carbon\Carbon::parse($artifact->meta['extracted_at'])->diffForHumans() : '알 수 없음' }}</strong>
        </div>
    </div>

    {{-- Stats --}}
    <div class="ls-stats">
        <div class="ls-stat">
            <div class="ls-stat-num">{{ $artifact->meta['total_frames_analyzed'] ?? 0 }}</div>
            <div class="ls-stat-label">분석 프레임</div>
        </div>
        <div class="ls-stat">
            <div class="ls-stat-num">{{ $artifact->meta['standard_layouts_identified'] ?? 0 }}</div>
            <div class="ls-stat-label">표준 레이아웃</div>
        </div>
        <div class="ls-stat">
            <div class="ls-stat-num" style="{{ ($artifact->meta['non_standard_frames'] ?? 0) > 0 ? 'color:#f59e0b' : '' }}">{{ $artifact->meta['non_standard_frames'] ?? 0 }}</div>
            <div class="ls-stat-label">비표준 프레임</div>
        </div>
    </div>

    {{-- ── Standard Layouts ────────────────────────────────────── --}}
    @php
        $standards   = $specData['standard_layouts']    ?? [];
        $spacing     = $specData['spacing_scale']       ?? [];
        $nonStandard = $specData['non_standard_frames'] ?? [];
    @endphp

    @if(!empty($standards))
    <div class="ls-section">
        <div class="ls-section-title">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7"/></svg>
            표준 레이아웃
            <small>사용 빈도 {{ count($standards) }}종 식별됨 (10% 이상 사용)</small>
        </div>

        @foreach($standards as $layoutKey => $layout)
        @php
            $spec    = $layout['spec'] ?? [];
            $cols    = $spec['columns'] ?? null;
            $gutter  = $spec['gutter']  ?? '';
            $margin  = $spec['margin']  ?? '';
            $align   = $spec['alignment'] ?? '';
            $isFree  = ($spec['type'] ?? '') === 'freeform';
        @endphp

        <div class="ls-std-card" x-data="{ showFrames: false, editing: false, desc: '{{ addslashes($layout['description'] ?? '') }}', saving: false, saveMsg: '', updateUrl: '{{ route('ai-agent.projects.design.layout.update', [$project, $layoutKey]) }}' }">
            <div class="ls-std-card-header">
                <div>
                    <div class="ls-std-name">{{ $layout['name'] }}</div>
                </div>
                <span class="ls-std-badge">
                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ $layout['usage_count'] }}개 · {{ $layout['usage_percent'] }}%
                </span>
            </div>

            {{-- Grid visualizer --}}
            @if(!$isFree && $cols)
            <div class="ls-grid-vis" title="{{ $cols }} columns, gutter: {{ $gutter }}, margin: {{ $margin }}">
                {{-- Margin left --}}
                @if($margin && $margin !== '0px')
                <div style="width:12px;background:#f1f5f9;border-radius:3px;flex-shrink:0;"></div>
                @endif
                {{-- Columns + gutters --}}
                @for($i = 0; $i < min((int)$cols, 24); $i++)
                    <div class="ls-grid-col" style="background:{{ ['#c4b5fd','#ddd6fe','#ede9fe'][$i%3] }};"></div>
                    @if($i < (int)$cols - 1)
                    <div style="width:2px;background:#f8f6ff;flex-shrink:0;"></div>
                    @endif
                @endfor
                {{-- Margin right --}}
                @if($margin && $margin !== '0px')
                <div style="width:12px;background:#f1f5f9;border-radius:3px;flex-shrink:0;"></div>
                @endif
            </div>
            @else
            <div style="height:36px;background:repeating-linear-gradient(45deg,#f8f6ff,#f8f6ff 4px,#fff 4px,#fff 10px);border-radius:6px;margin-bottom:12px;display:flex;align-items:center;justify-content:center;font-size:11px;color:#94a3b8;font-weight:600;">자유 배치 (No Grid)</div>
            @endif

            {{-- Spec details --}}
            @if(!$isFree)
            <div class="ls-spec-row">
                @if($cols)<div class="ls-spec-item"><span>Columns</span><strong>{{ $cols }}</strong></div>@endif
                @if($gutter)<div class="ls-spec-item"><span>Gutter</span><strong>{{ $gutter }}</strong></div>@endif
                @if($margin)<div class="ls-spec-item"><span>Margin</span><strong>{{ $margin }}</strong></div>@endif
                @if($align)<div class="ls-spec-item"><span>Align</span><strong>{{ $align }}</strong></div>@endif
            </div>
            @endif

            {{-- Description (editable) --}}
            <div x-show="!editing">
                <div class="ls-std-desc" x-text="desc || '설명 없음'"></div>
                <button @click="editing=true" class="ls-btn ghost sm">편집</button>
            </div>
            <div x-show="editing" style="margin-top:8px;">
                <input type="text" x-model="desc" class="ls-std-desc-input" placeholder="이 레이아웃 표준에 대한 설명...">
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button @click="saving=true; saveMsg=''; axios.patch(updateUrl, {description: desc, _token: document.querySelector('meta[name=csrf-token]').content}).then(() => { saveMsg='저장됨 ✓'; editing=false; }).catch(() => { saveMsg='저장 실패'; }).finally(() => { saving=false; setTimeout(() => saveMsg='', 2000); })" :disabled="saving" class="ls-btn primary sm">
                        <span x-text="saving ? '저장 중...' : '저장'"></span>
                    </button>
                    <button @click="editing=false" class="ls-btn secondary sm">취소</button>
                    <span x-show="saveMsg" x-text="saveMsg" style="font-size:12px;color:#15803d;align-self:center;"></span>
                </div>
            </div>

            {{-- Used-in frames toggle --}}
            @if(!empty($layout['frame_names']))
            <div style="margin-top:10px;">
                <button @click="showFrames = !showFrames" class="ls-frames-toggle">
                    <span x-text="showFrames ? '▲ 화면 목록 접기' : '▼ 사용 화면 보기 ({{ count($layout['frame_names']) }}개)'"></span>
                </button>
                <div x-show="showFrames" style="margin-top:8px;background:#f8f6ff;border-radius:8px;padding:10px 12px;">
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($layout['frame_names'] as $fname)
                        <span style="display:inline-block;padding:2px 8px;background:#ede8ff;border-radius:5px;font-size:11.5px;color:#6d28d9;">{{ $fname }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Spacing Scale ────────────────────────────────────────── --}}
    @if(!empty($spacing['values']))
    <div class="ls-section">
        <div class="ls-section-title">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            간격 스케일
            <small>{{ count($spacing['values']) }}개 자주 사용된 간격값</small>
        </div>

        @php
            $usageCnt = $spacing['usage_count'] ?? [];
            $maxCnt   = $usageCnt ? max($usageCnt) : 1;
        @endphp
        <div class="ls-spacing-scale">
            @foreach($spacing['values'] as $val)
            @php
                $cnt    = $usageCnt[$val] ?? 0;
                $barH   = max(6, (int) round(($cnt / $maxCnt) * 60));
            @endphp
            <div class="ls-spacing-item">
                <div class="ls-spacing-bar" style="height:{{ $barH }}px;" title="{{ $cnt }}회 사용"></div>
                <div class="ls-spacing-val">{{ $val }}</div>
                <div class="ls-spacing-cnt">{{ $cnt }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Non-standard Frames ──────────────────────────────────── --}}
    @if(!empty($nonStandard))
    <div class="ls-section">
        <div class="ls-section-title">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            비표준 프레임
            <small style="color:#f59e0b;">{{ count($nonStandard) }}개 — 표준 패턴 미적용</small>
        </div>

        @foreach($nonStandard as $frame)
        <div class="ls-nonst-item">
            <div class="ls-nonst-icon">⚠️</div>
            <div>
                <div class="ls-nonst-name">{{ $frame['frame_name'] }}</div>
                <div class="ls-nonst-dev">{{ $frame['deviation'] }}</div>
                <div style="margin-top:4px;font-size:11px;color:#94a3b8;">Node: {{ $frame['node_id'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Empty result --}}
    @if($artifact && empty($standards) && empty($nonStandard))
    <div class="ls-section" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:32px;margin-bottom:10px;">🔍</div>
        <div style="font-size:14px;font-weight:600;margin-bottom:6px;">분석할 프레임이 없습니다</div>
        <div style="font-size:12.5px;">Figma 파일에 FRAME 타입 노드가 없거나, 레이아웃 그리드가 설정되지 않았습니다.</div>
    </div>
    @endif
    @endif

    {{-- ── Re-analyze Modal ─────────────────────────────────────── --}}
    @if($artifact)
    <div x-show="showAnalyzeModal"
         style="position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px;" x-cloak>
        <div style="background:#fff;border-radius:16px;padding:28px;max-width:480px;width:100%;">
            <h3 style="font-size:16px;font-weight:800;color:#1e1b2e;margin:0 0 6px;">레이아웃 재분석</h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 18px;">기존 분석 결과는 새 버전으로 보존됩니다.</p>
            <input type="url" x-model="figmaUrl" class="ls-url-input"
                   placeholder="https://www.figma.com/file/ABC123/..."
                   value="{{ $artifact->meta['figma_file_key'] ?? '' }}">
            <div style="display:flex;gap:8px;margin-top:14px;justify-content:flex-end;">
                <button @click="showAnalyzeModal=false" class="ls-btn secondary">취소</button>
                <button @click="doAnalyze()" :disabled="!figmaUrl || analyzing" class="ls-btn primary">재분석</button>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function lsPage(cfg) {
    return {
        figmaUrl:        '',
        analyzing:       false,
        showAnalyzeModal: false,
        hasArtifact:     cfg.hasArtifact,

        async doAnalyze() {
            if (!this.figmaUrl) return;
            this.analyzing       = true;
            this.showAnalyzeModal = false;

            try {
                await axios.post(cfg.analyzeUrl, {
                    figma_url: this.figmaUrl,
                    _token:    document.querySelector('meta[name="csrf-token"]').content,
                });
                window.location.reload();
            } catch (err) {
                const msg = err.response?.data?.message || '분석 중 오류가 발생했습니다.';
                alert(msg);
            } finally {
                this.analyzing = false;
            }
        },

    };
}
</script>
@endpush
