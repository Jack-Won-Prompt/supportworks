@extends('layouts.ai-agent')
@section('title', 'Design Tokens — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.dt-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.dt-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.dt-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.dt-header-right { display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.dt-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.dt-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.dt-btn.primary:hover   { background:var(--t700,#6d28d9); }
.dt-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.dt-btn.secondary:hover { background:#e2e8f0; }
.dt-btn.ghost { background:transparent; color:var(--t600); border:1.5px solid var(--t300,#c4b5fd); }
.dt-btn.ghost:hover { background:#f5f3ff; }
.dt-btn.sm { padding:4px 10px; font-size:12px; }
.dt-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── Empty state ─────────────────────────────────────────────────── */
.dt-empty { background:#fff; border:2px dashed #ddd6fe; border-radius:16px; padding:48px 24px; text-align:center; }
.dt-empty-icon { font-size:40px; margin-bottom:12px; }
.dt-empty h3 { font-size:16px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.dt-empty p  { font-size:13px; color:#64748b; margin:0 0 20px; }
.dt-url-input { width:100%; max-width:480px; border:1.5px solid #ddd6fe; border-radius:10px; padding:10px 14px; font-size:13.5px; color:#1e1b2e; outline:none; box-sizing:border-box; }
.dt-url-input:focus { border-color:var(--t500,#8b5cf6); }
.dt-pat-warn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#fffbeb; border:1.5px solid #fde68a; border-radius:8px; font-size:12.5px; color:#92400e; margin-top:12px; }

/* ── Meta bar ────────────────────────────────────────────────────── */
.dt-meta-bar { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 18px; margin-bottom:18px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.dt-meta-item { font-size:12.5px; color:#64748b; display:flex; align-items:center; gap:5px; }
.dt-meta-item strong { color:#1e1b2e; }
.dt-meta-sep { color:#e2e8f0; }

/* ── Stats ───────────────────────────────────────────────────────── */
.dt-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(100px,1fr)); gap:10px; margin-bottom:18px; }
.dt-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; text-align:center; }
.dt-stat-num   { font-size:22px; font-weight:800; color:var(--t600); }
.dt-stat-label { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

/* ── Tabs ────────────────────────────────────────────────────────── */
.dt-tabs { display:flex; gap:4px; border-bottom:2px solid #ede8ff; margin-bottom:20px; flex-wrap:wrap; }
.dt-tab { padding:8px 14px; font-size:13px; font-weight:600; color:#64748b; border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .15s; border-radius:8px 8px 0 0; }
.dt-tab.active { color:var(--t600); border-bottom-color:var(--t500); background:#f5f3ff; }
.dt-tab:hover:not(.active) { color:#475569; background:#f8fafc; }

/* ── Section ─────────────────────────────────────────────────────── */
.dt-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.dt-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:7px; }

/* ── Color palette ───────────────────────────────────────────────── */
.dt-color-group { margin-bottom:18px; }
.dt-color-group-name { font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.06em; margin:0 0 8px; }
.dt-color-swatches { display:flex; flex-wrap:wrap; gap:8px; }
.dt-swatch { display:flex; flex-direction:column; align-items:center; gap:4px; cursor:pointer; }
.dt-swatch-box { width:52px; height:52px; border-radius:10px; border:1.5px solid rgba(0,0,0,.08); transition:transform .12s; flex-shrink:0; }
.dt-swatch:hover .dt-swatch-box { transform:scale(1.08); }
.dt-swatch-name  { font-size:10px; font-weight:600; color:#475569; text-align:center; max-width:60px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dt-swatch-hex   { font-size:9.5px; color:#94a3b8; font-family:monospace; }

/* ── Typography ──────────────────────────────────────────────────── */
.dt-typo-list { display:flex; flex-direction:column; gap:12px; }
.dt-typo-item { border:1.5px solid #f3eeff; border-radius:10px; padding:14px 16px; }
.dt-typo-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; flex-wrap:wrap; }
.dt-typo-name { font-size:12px; font-weight:700; color:#7c3aed; font-family:monospace; }
.dt-typo-meta { font-size:11px; color:#94a3b8; }
.dt-typo-preview { color:#1e1b2e; line-height:1.5; }

/* ── Shadow ──────────────────────────────────────────────────────── */
.dt-shadow-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; }
.dt-shadow-card { border:1.5px solid #ede8ff; border-radius:10px; padding:16px; display:flex; flex-direction:column; align-items:center; gap:10px; }
.dt-shadow-box  { width:72px; height:72px; background:#fff; border-radius:8px; }
.dt-shadow-name { font-size:11px; font-weight:700; color:#475569; font-family:monospace; }
.dt-shadow-val  { font-size:10px; color:#94a3b8; text-align:center; line-height:1.5; }

/* ── Layout grid ─────────────────────────────────────────────────── */
.dt-grid-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; }
.dt-grid-card  { border:1.5px solid #ede8ff; border-radius:10px; padding:16px; }
.dt-grid-name  { font-size:12px; font-weight:700; color:#7c3aed; font-family:monospace; margin-bottom:8px; }
.dt-grid-row   { display:flex; justify-content:space-between; font-size:12px; color:#475569; padding:3px 0; border-bottom:1px solid #f3eeff; }
.dt-grid-row:last-child { border-bottom:none; }
.dt-grid-row span:last-child { font-weight:700; color:#1e1b2e; }

/* ── Edit mode ───────────────────────────────────────────────────── */
.dt-editor { display:none; }
.dt-editor.active { display:block; }
.dt-editor-textarea { width:100%; height:400px; border:1.5px solid #ddd6fe; border-radius:10px; padding:14px; font-size:12.5px; font-family:'Courier New',monospace; color:#1e1b2e; resize:vertical; outline:none; box-sizing:border-box; }
.dt-editor-textarea:focus { border-color:var(--t500); }

/* ── Progress ────────────────────────────────────────────────────── */
.dt-progress-overlay { position:fixed; inset:0; background:rgba(30,27,46,.5); z-index:9990; display:flex; align-items:center; justify-content:center; }
.dt-progress-box { background:#fff; border-radius:16px; padding:28px 32px; width:100%; max-width:400px; text-align:center; }
.dt-progress-title { font-size:15px; font-weight:800; color:#1e1b2e; margin:0 0 16px; }
.dt-progress-spinner { width:36px; height:36px; border:3px solid #ede8ff; border-top-color:var(--t500); border-radius:50%; animation:dt-spin .8s linear infinite; margin:0 auto 16px; }
@keyframes dt-spin { to { transform:rotate(360deg); } }
.dt-progress-msg { font-size:13px; color:#64748b; margin:0; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="dtPage()" x-init="init()">

    <div class="dt-header">
        <div class="dt-header-left">
            <h1>Design Tokens</h1>
            <p>Figma 파일에서 색상·타이포그래피·그림자·레이아웃 토큰을 추출하고 관리합니다.</p>
        </div>
        @if($artifact)
        <div class="dt-header-right">
            <div x-show="!editMode">
                <button class="dt-btn secondary sm" @click="editMode = true">편집</button>
                <div style="position:relative;display:inline-block;" x-data="{open:false}">
                    <button class="dt-btn secondary sm" @click="open=!open">
                        다운로드 ▾
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false"
                         style="position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1.5px solid #ede8ff;border-radius:10px;padding:6px;z-index:100;min-width:160px;box-shadow:0 8px 24px rgba(30,27,46,.12);">
                        <a href="{{ route('ai-agent.projects.design.tokens.export', $project) }}?format=json"
                           style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:7px;font-size:12.5px;color:#1e1b2e;text-decoration:none;font-weight:600;"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                            JSON (W3C)
                        </a>
                        <a href="{{ route('ai-agent.projects.design.tokens.export', $project) }}?format=css"
                           style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:7px;font-size:12.5px;color:#1e1b2e;text-decoration:none;font-weight:600;"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                            CSS 변수
                        </a>
                        <a href="{{ route('ai-agent.projects.design.tokens.export', $project) }}?format=tailwind"
                           style="display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:7px;font-size:12.5px;color:#1e1b2e;text-decoration:none;font-weight:600;"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                            Tailwind Config
                        </a>
                    </div>
                </div>
                <button class="dt-btn ghost sm" @click="showExtractModal = true">재추출</button>
            </div>
            <div x-show="editMode" style="display:flex;gap:6px;">
                <button class="dt-btn primary sm" @click="saveEdit" :disabled="saving">저장</button>
                <button class="dt-btn secondary sm" @click="editMode = false">취소</button>
            </div>
        </div>
        @endif
    </div>

    {{-- ─── 빈 상태 ──────────────────────────────────────────────────── --}}
    @if(!$artifact)
    <div class="dt-empty">
        <div class="dt-empty-icon">🎨</div>
        <h3>Design Tokens가 없습니다</h3>
        <p>Figma 파일 URL을 입력하면 색상, 타이포그래피, 그림자 토큰을 자동 추출합니다.</p>

        @if(!$hasPat)
        <div class="dt-pat-warn">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            Figma PAT이 설정되지 않았습니다.
            <a href="{{ route('ai-agent.settings.figma') }}" style="color:#7c3aed;font-weight:700;text-decoration:underline;">설정하러 가기</a>
        </div>
        @else
        <div style="display:flex;gap:8px;justify-content:center;align-items:center;flex-wrap:wrap;margin-top:8px;">
            <input type="text" class="dt-url-input" x-model="figmaUrl"
                   placeholder="https://www.figma.com/file/ABC123/..." style="max-width:380px;">
            <button class="dt-btn primary" @click="extract" :disabled="!figmaUrl.trim() || extracting">
                <svg x-show="!extracting" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                <svg x-show="extracting" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:dt-spin .8s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Figma에서 추출
            </button>
        </div>
        <template x-if="extractError">
            <p style="color:#dc2626;font-size:13px;margin-top:10px;" x-text="extractError"></p>
        </template>
        @endif
    </div>
    @endif

    {{-- ─── 토큰 존재 시 ─────────────────────────────────────────────── --}}
    @if($artifact && $tokenData)
    @php
        $meta       = $artifact->meta ?? [];
        $colorCount = $meta['color_count']      ?? 0;
        $typoCount  = $meta['typography_count'] ?? 0;
        $shadowCount= $meta['shadow_count']     ?? 0;
        $layoutCount= $meta['layout_count']     ?? 0;
        $tokenCount = $meta['token_count']      ?? 0;
        $fileName   = $meta['figma_file_name']  ?? '—';
        $extractedAt= $meta['extracted_at']     ?? null;
        $colors     = $tokenData['color']       ?? [];
        $typography = $tokenData['typography']  ?? [];
        $shadows    = $tokenData['shadow']      ?? [];
        $layouts    = $tokenData['layout']      ?? [];
    @endphp

    {{-- Meta bar --}}
    <div class="dt-meta-bar">
        <span class="dt-meta-item">
            <svg width="12" height="12" viewBox="0 0 38 57" fill="none" style="flex-shrink:0;">
                <path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/>
                <path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/>
                <path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/>
            </svg>
            <strong>{{ $fileName }}</strong>
        </span>
        <span class="dt-meta-sep">|</span>
        <span class="dt-meta-item">v{{ $artifact->version }}</span>
        @if($extractedAt)
        <span class="dt-meta-sep">|</span>
        <span class="dt-meta-item">추출: {{ \Carbon\Carbon::parse($extractedAt)->diffForHumans() }}</span>
        @endif
    </div>

    {{-- Stats --}}
    <div class="dt-stats">
        <div class="dt-stat">
            <div class="dt-stat-num">{{ $colorCount }}</div>
            <div class="dt-stat-label">색상</div>
        </div>
        <div class="dt-stat">
            <div class="dt-stat-num">{{ $typoCount }}</div>
            <div class="dt-stat-label">타이포</div>
        </div>
        <div class="dt-stat">
            <div class="dt-stat-num">{{ $shadowCount }}</div>
            <div class="dt-stat-label">그림자</div>
        </div>
        <div class="dt-stat">
            <div class="dt-stat-num">{{ $layoutCount }}</div>
            <div class="dt-stat-label">레이아웃</div>
        </div>
        <div class="dt-stat">
            <div class="dt-stat-num" style="color:#1e1b2e;">{{ $tokenCount }}</div>
            <div class="dt-stat-label">합계</div>
        </div>
    </div>

    {{-- Edit mode textarea --}}
    <div class="dt-editor" :class="editMode ? 'active' : ''">
        <div class="dt-section">
            <div class="dt-section-title">JSON 직접 편집</div>
            <textarea class="dt-editor-textarea" x-model="editContent"></textarea>
            <template x-if="saveError">
                <p style="color:#dc2626;font-size:13px;margin-top:8px;" x-text="saveError"></p>
            </template>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-show="!editMode">
        <div class="dt-tabs">
            @if(!empty($colors))
            <button class="dt-tab" :class="activeTab === 'color' ? 'active' : ''" @click="activeTab = 'color'">
                색상 {{ $colorCount > 0 ? "({$colorCount})" : '' }}
            </button>
            @endif
            @if(!empty($typography))
            <button class="dt-tab" :class="activeTab === 'typography' ? 'active' : ''" @click="activeTab = 'typography'">
                타이포그래피 {{ $typoCount > 0 ? "({$typoCount})" : '' }}
            </button>
            @endif
            @if(!empty($shadows))
            <button class="dt-tab" :class="activeTab === 'shadow' ? 'active' : ''" @click="activeTab = 'shadow'">
                그림자 {{ $shadowCount > 0 ? "({$shadowCount})" : '' }}
            </button>
            @endif
            @if(!empty($layouts))
            <button class="dt-tab" :class="activeTab === 'layout' ? 'active' : ''" @click="activeTab = 'layout'">
                레이아웃 {{ $layoutCount > 0 ? "({$layoutCount})" : '' }}
            </button>
            @endif
        </div>

        {{-- ─── 색상 탭 ────────────────────────────────────────────── --}}
        @if(!empty($colors))
        <div x-show="activeTab === 'color'">
            <div class="dt-section">
                @foreach($colors as $groupName => $group)
                @if(str_starts_with($groupName, '$')) @continue @endif
                <div class="dt-color-group">
                    <div class="dt-color-group-name">{{ $groupName }}</div>
                    <div class="dt-color-swatches">
                        @foreach($group as $tokenKey => $token)
                        @if(str_starts_with($tokenKey, '$')) @continue @endif
                        @php
                            $hex = is_array($token) && isset($token['$value']) ? $token['$value'] : null;
                            if (is_array($token) && !isset($token['$value'])) {
                                // nested group — render sub-swatches
                            }
                        @endphp
                        @if($hex)
                            <div class="dt-swatch" title="{{ $hex }}" onclick="navigator.clipboard.writeText('{{ $hex }}')">
                                <div class="dt-swatch-box" style="background:{{ $hex }};"></div>
                                <span class="dt-swatch-name">{{ $tokenKey }}</span>
                                <span class="dt-swatch-hex">{{ $hex }}</span>
                            </div>
                        @else
                            @foreach($token as $subKey => $subToken)
                            @if(str_starts_with($subKey, '$')) @continue @endif
                            @php $hex2 = is_array($subToken) && isset($subToken['$value']) ? $subToken['$value'] : null; @endphp
                            @if($hex2)
                                <div class="dt-swatch" title="{{ $hex2 }}" onclick="navigator.clipboard.writeText('{{ $hex2 }}')">
                                    <div class="dt-swatch-box" style="background:{{ $hex2 }};"></div>
                                    <span class="dt-swatch-name">{{ $groupName }}.{{ $tokenKey }}.{{ $subKey }}</span>
                                    <span class="dt-swatch-hex">{{ $hex2 }}</span>
                                </div>
                            @endif
                            @endforeach
                        @endif
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ─── 타이포그래피 탭 ────────────────────────────────────── --}}
        @if(!empty($typography))
        <div x-show="activeTab === 'typography'">
            <div class="dt-section">
                <div class="dt-typo-list">
                    @php
                    function flatTypo(array $node, string $path = ''): array {
                        $result = [];
                        if (isset($node['$value'])) {
                            $result[] = ['path' => $path, 'value' => $node['$value']];
                            return $result;
                        }
                        foreach ($node as $key => $child) {
                            if (str_starts_with($key, '$')) continue;
                            if (is_array($child)) {
                                foreach (flatTypo($child, $path ? "{$path}.{$key}" : $key) as $item) {
                                    $result[] = $item;
                                }
                            }
                        }
                        return $result;
                    }
                    $typoFlat = flatTypo($typography);
                    @endphp
                    @foreach($typoFlat as $t)
                    @php $v = $t['value']; @endphp
                    <div class="dt-typo-item">
                        <div class="dt-typo-header">
                            <span class="dt-typo-name">{{ $t['path'] }}</span>
                            <span class="dt-typo-meta">{{ $v['fontFamily'] ?? '' }}, {{ $v['fontSize'] ?? '' }}, {{ $v['fontWeight'] ?? '' }}</span>
                        </div>
                        <div class="dt-typo-preview" style="
                            font-family: {{ $v['fontFamily'] ?? 'inherit' }};
                            font-size: min({{ $v['fontSize'] ?? '16px' }}, 28px);
                            font-weight: {{ $v['fontWeight'] ?? 400 }};
                            line-height: {{ $v['lineHeight'] ?? 'normal' }};
                            letter-spacing: {{ $v['letterSpacing'] ?? 'normal' }};
                        ">
                            빠른 갈색 여우가 게으른 개를 뛰어넘습니다
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ─── 그림자 탭 ──────────────────────────────────────────── --}}
        @if(!empty($shadows))
        <div x-show="activeTab === 'shadow'">
            <div class="dt-section">
                <div class="dt-shadow-grid">
                    @php
                    function flatShadow(array $node, string $path = ''): array {
                        $result = [];
                        if (isset($node['$value'])) {
                            $result[] = ['path' => $path, 'value' => $node['$value']];
                            return $result;
                        }
                        foreach ($node as $key => $child) {
                            if (str_starts_with($key, '$')) continue;
                            if (is_array($child)) {
                                foreach (flatShadow($child, $path ? "{$path}.{$key}" : $key) as $item) {
                                    $result[] = $item;
                                }
                            }
                        }
                        return $result;
                    }
                    $shadowFlat = flatShadow($shadows);
                    @endphp
                    @foreach($shadowFlat as $s)
                    @php
                        $v   = $s['value'];
                        $css = is_array($v)
                            ? "{$v['x']} {$v['y']} {$v['blur']} {$v['spread']} {$v['color']}"
                            : '';
                    @endphp
                    <div class="dt-shadow-card">
                        <div class="dt-shadow-box" style="box-shadow: {{ $css }};"></div>
                        <div class="dt-shadow-name">{{ $s['path'] }}</div>
                        <div class="dt-shadow-val">{{ $css }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ─── 레이아웃 탭 ─────────────────────────────────────────── --}}
        @if(!empty($layouts))
        <div x-show="activeTab === 'layout'">
            <div class="dt-section">
                <div class="dt-grid-cards">
                    @php
                    function flatLayout(array $node, string $path = ''): array {
                        $result = [];
                        if (isset($node['$value'])) {
                            $result[] = ['path' => $path, 'value' => $node['$value']];
                            return $result;
                        }
                        foreach ($node as $key => $child) {
                            if (str_starts_with($key, '$')) continue;
                            if (is_array($child)) {
                                foreach (flatLayout($child, $path ? "{$path}.{$key}" : $key) as $item) {
                                    $result[] = $item;
                                }
                            }
                        }
                        return $result;
                    }
                    $layoutFlat = flatLayout($layouts);
                    @endphp
                    @foreach($layoutFlat as $l)
                    @php $v = $l['value']; @endphp
                    <div class="dt-grid-card">
                        <div class="dt-grid-name">{{ $l['path'] }}</div>
                        @if(is_array($v))
                            @foreach($v as $prop => $val)
                            <div class="dt-grid-row"><span>{{ $prop }}</span><span>{{ $val }}</span></div>
                            @endforeach
                        @else
                            <div class="dt-grid-row"><span>값</span><span>{{ $v }}</span></div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ─── 재추출 모달 ──────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="showExtractModal" x-cloak
             style="position:fixed;inset:0;background:rgba(30,27,46,.5);z-index:9990;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(30,27,46,.2);">
                <h3 style="font-size:15px;font-weight:800;color:#1e1b2e;margin:0 0 16px;">Figma에서 토큰 재추출</h3>
                <p style="font-size:12.5px;color:#64748b;margin:0 0 14px;line-height:1.6;">
                    새 Figma URL을 입력하거나 현재 파일을 재추출합니다. 기존 토큰은 버전 이력으로 보존됩니다.
                </p>
                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">Figma 파일 URL</label>
                <input type="text" class="dt-url-input" x-model="figmaUrl"
                       placeholder="{{ isset($meta) && isset($meta['figma_file_key']) ? 'https://www.figma.com/file/'.$meta['figma_file_key'].'/' : 'https://www.figma.com/file/ABC123/...' }}"
                       style="margin-bottom:14px;">
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button class="dt-btn secondary" @click="showExtractModal = false">취소</button>
                    <button class="dt-btn primary" @click="extract" :disabled="!figmaUrl.trim() || extracting">
                        <span x-show="!extracting">추출 시작</span>
                        <span x-show="extracting">추출 중...</span>
                    </button>
                </div>
                <template x-if="extractError">
                    <p style="color:#dc2626;font-size:13px;margin-top:10px;" x-text="extractError"></p>
                </template>
            </div>
        </div>
    </template>

    {{-- ─── 추출 중 오버레이 ──────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="extracting" x-cloak class="dt-progress-overlay">
            <div class="dt-progress-box">
                <div class="dt-progress-spinner"></div>
                <h3 class="dt-progress-title">토큰 추출 중...</h3>
                <p class="dt-progress-msg">Figma 파일에서 스타일을 읽고 있습니다.<br>잠시 기다려 주세요.</p>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function dtPage() {
    return {
        activeTab:       '{{ !empty($colors) ? 'color' : (!empty($typography) ? 'typography' : 'shadow') }}',
        figmaUrl:        '',
        extracting:      false,
        extractError:    null,
        showExtractModal:false,
        editMode:        false,
        editContent:     @json($artifact ? $artifact->content : '{}'),
        saving:          false,
        saveError:       null,

        init() {
            // 초기 탭 결정
        },

        async extract() {
            if (!this.figmaUrl.trim()) return;
            this.extracting   = true;
            this.extractError = null;

            try {
                const res  = await fetch('{{ route('ai-agent.projects.design.tokens.extract', $project) }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
                    body: JSON.stringify({ figma_url: this.figmaUrl }),
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    this.extractError = data.message;
                    this.extracting   = false;
                }
            } catch {
                this.extractError = '추출 중 오류가 발생했습니다.';
                this.extracting   = false;
            }
        },

        async saveEdit() {
            this.saving    = true;
            this.saveError = null;

            try {
                const res  = await fetch('{{ route('ai-agent.projects.design.tokens.update', $project) }}', {
                    method: 'PATCH',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
                    body: JSON.stringify({ content: this.editContent }),
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    this.saveError = data.message;
                    this.saving    = false;
                }
            } catch {
                this.saveError = '저장 중 오류가 발생했습니다.';
                this.saving    = false;
            }
        },
    };
}
</script>
@endpush
