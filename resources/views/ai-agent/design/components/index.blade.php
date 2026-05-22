@extends('layouts.ai-agent')
@section('title', 'Component 명세서 — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.cs-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.cs-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cs-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.cs-header-right { display:flex; gap:8px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.cs-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.cs-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.cs-btn.primary:hover   { background:var(--t700,#6d28d9); }
.cs-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cs-btn.secondary:hover { background:#e2e8f0; }
.cs-btn.ghost { background:transparent; color:var(--t600); border:1.5px solid var(--t300,#c4b5fd); }
.cs-btn.ghost:hover { background:#f5f3ff; }
.cs-btn.sm { padding:4px 10px; font-size:12px; }
.cs-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── Empty state ─────────────────────────────────────────────────── */
.cs-empty { background:#fff; border:2px dashed #ddd6fe; border-radius:16px; padding:48px 24px; text-align:center; }
.cs-empty-icon { font-size:40px; margin-bottom:12px; }
.cs-empty h3 { font-size:16px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.cs-empty p  { font-size:13px; color:#64748b; margin:0 0 20px; }
.cs-url-input { width:100%; max-width:480px; border:1.5px solid #ddd6fe; border-radius:10px; padding:10px 14px; font-size:13.5px; color:#1e1b2e; outline:none; box-sizing:border-box; }
.cs-url-input:focus { border-color:var(--t500,#8b5cf6); }
.cs-pat-warn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#fffbeb; border:1.5px solid #fde68a; border-radius:8px; font-size:12.5px; color:#92400e; margin-top:12px; }
.cs-token-hint { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:8px; font-size:12.5px; color:#15803d; margin-top:10px; }

/* ── Meta bar ────────────────────────────────────────────────────── */
.cs-meta-bar { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 18px; margin-bottom:18px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.cs-meta-item { font-size:12.5px; color:#64748b; display:flex; align-items:center; gap:5px; }
.cs-meta-item strong { color:#1e1b2e; }
.cs-meta-sep { color:#e2e8f0; }

/* ── Stats ───────────────────────────────────────────────────────── */
.cs-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:10px; margin-bottom:18px; }
.cs-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; text-align:center; }
.cs-stat-num   { font-size:22px; font-weight:800; color:var(--t600); }
.cs-stat-label { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }

/* ── Component grid ──────────────────────────────────────────────── */
.cs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
.cs-card { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; cursor:pointer; transition:box-shadow .15s,border-color .15s; }
.cs-card:hover { box-shadow:0 4px 16px rgba(124,58,237,.12); border-color:var(--t300,#c4b5fd); }
.cs-card.selected { border-color:var(--t500,#8b5cf6); box-shadow:0 0 0 3px rgba(124,58,237,.15); }
.cs-card-preview { width:100%; height:120px; background:#f8f8fc; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.cs-card-preview img { max-width:100%; max-height:100%; object-fit:contain; }
.cs-card-preview-empty { font-size:28px; opacity:.3; }
.cs-card-body { padding:12px 14px; }
.cs-card-name { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cs-card-type { font-size:11px; color:#8b5cf6; font-weight:600; }
.cs-card-meta { font-size:11.5px; color:#94a3b8; margin-top:6px; }
.cs-type-badge { display:inline-block; padding:2px 7px; border-radius:5px; font-size:10.5px; font-weight:700; }
.cs-type-badge.set  { background:#ede8ff; color:#6d28d9; }
.cs-type-badge.solo { background:#e0f2fe; color:#0369a1; }

/* ── Search / filter ─────────────────────────────────────────────── */
.cs-toolbar { display:flex; gap:8px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.cs-search { flex:1; min-width:180px; border:1.5px solid #e2e8f0; border-radius:9px; padding:7px 12px; font-size:13px; color:#1e1b2e; outline:none; }
.cs-search:focus { border-color:var(--t500); }
.cs-filter-btn { padding:5px 11px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:12px; font-weight:600; color:#64748b; background:#fff; cursor:pointer; transition:all .12s; }
.cs-filter-btn.active { border-color:var(--t500); color:var(--t600); background:#f5f3ff; }

/* ── Detail panel ────────────────────────────────────────────────── */
.cs-layout { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
@media (max-width: 900px) { .cs-layout { grid-template-columns:1fr; } }
.cs-detail { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px; position:sticky; top:20px; }
.cs-detail-empty { text-align:center; padding:30px 20px; color:#94a3b8; font-size:13px; }
.cs-detail-preview { width:100%; border-radius:10px; overflow:hidden; background:#f8f8fc; height:160px; display:flex; align-items:center; justify-content:center; margin-bottom:14px; }
.cs-detail-preview img { max-width:100%; max-height:160px; object-fit:contain; }
.cs-detail-name { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cs-detail-desc { font-size:13px; color:#64748b; margin:0 0 14px; }
.cs-props-table { width:100%; border-collapse:collapse; font-size:12.5px; margin-bottom:14px; }
.cs-props-table th { background:#f8f6ff; color:#64748b; font-weight:700; padding:6px 10px; text-align:left; border-bottom:1.5px solid #ede8ff; }
.cs-props-table td { padding:6px 10px; border-bottom:1px solid #f1f5f9; color:#374151; vertical-align:top; }
.cs-props-table td:first-child { font-weight:600; color:#6d28d9; white-space:nowrap; }
.cs-tokens-list { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:14px; }
.cs-token-chip { display:inline-flex; align-items:center; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:500; color:#6d28d9; font-family:monospace; }
.cs-doc-area { width:100%; border:1.5px solid #e2e8f0; border-radius:8px; padding:8px 10px; font-size:12.5px; color:#374151; resize:vertical; min-height:80px; outline:none; box-sizing:border-box; }
.cs-doc-area:focus { border-color:var(--t500); }
.cs-section-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin:14px 0 6px; }

/* ── Spinner overlay ─────────────────────────────────────────────── */
.cs-spinner-overlay { position:fixed; inset:0; background:rgba(255,255,255,.8); display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:9999; gap:14px; }
.cs-spinner { width:40px; height:40px; border:4px solid #ede8ff; border-top-color:var(--t500); border-radius:50%; animation:cs-spin .8s linear infinite; }
@keyframes cs-spin { to { transform:rotate(360deg); } }
.cs-spinner-text { font-size:14px; font-weight:600; color:#6d28d9; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="csPage({
    extractUrl:  '{{ route('ai-agent.projects.design.components.extract', $project) }}',
    exportUrl:   '{{ route('ai-agent.projects.design.components.export', $project) }}',
    showUrl:     '{{ route('ai-agent.projects.design.components.show', [$project, '__KEY__']) }}',
    updateUrl:   '{{ route('ai-agent.projects.design.components.update', [$project, '__KEY__']) }}',
    hasArtifact: {{ $artifact ? 'true' : 'false' }},
    hasTokens:   {{ $hasTokens ? 'true' : 'false' }},
})" x-cloak>

    {{-- Spinner --}}
    <div x-show="extracting" class="cs-spinner-overlay" style="display:none;">
        <div class="cs-spinner"></div>
        <div class="cs-spinner-text">컴포넌트 명세 추출 중...</div>
    </div>

    <div class="cs-header">
        <div class="cs-header-left">
            <h1>Component 명세서</h1>
            <p>Figma 컴포넌트 라이브러리에서 명세를 자동으로 추출하고 관리합니다.</p>
        </div>
        <div class="cs-header-right" x-show="hasArtifact">
            <button @click="showExtractModal = true" class="cs-btn secondary">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                재추출
            </button>
            <div style="position:relative;" x-data="{ open: false }">
                <button @click="open = !open" class="cs-btn secondary">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    다운로드
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open=false"
                     style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1.5px solid #ede8ff;border-radius:10px;padding:4px;min-width:140px;z-index:50;box-shadow:0 4px 16px rgba(0,0,0,.1);">
                    <a :href="exportUrl + '?format=json'" class="cs-btn ghost sm" style="display:block;width:100%;margin-bottom:2px;">JSON 다운로드</a>
                    <a :href="exportUrl + '?format=markdown'" class="cs-btn ghost sm" style="display:block;width:100%;">Markdown 다운로드</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Empty State ──────────────────────────────────────── --}}
    @if(!$artifact)
    <div class="cs-empty">
        <div class="cs-empty-icon">🧩</div>
        <h3>컴포넌트 명세가 없습니다</h3>
        <p>Figma 컴포넌트 라이브러리에서 자동으로 명세를 추출합니다.</p>

        <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">
            <input type="url" x-model="figmaUrl" class="cs-url-input"
                   placeholder="https://www.figma.com/file/ABC123/My-Design-System">

            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;cursor:pointer;">
                <input type="checkbox" x-model="linkTokens" style="accent-color:var(--t500);">
                Design Tokens와 연결 (자동 매핑)
                <span x-show="hasTokens" style="color:#15803d;font-size:11px;">(토큰 추출 산출물 있음)</span>
                <span x-show="!hasTokens" style="color:#94a3b8;font-size:11px;">(토큰 미추출 — 추출 후 이용 가능)</span>
            </label>

            <button @click="doExtract()" :disabled="!figmaUrl || extracting" class="cs-btn primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                Figma에서 추출
            </button>

            @if(!$hasPat)
            <div class="cs-pat-warn">
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
    <div class="cs-meta-bar">
        <div class="cs-meta-item">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
            <strong>{{ $artifact->meta['figma_file_name'] ?? 'Figma 파일' }}</strong>
        </div>
        <span class="cs-meta-sep">|</span>
        <div class="cs-meta-item">버전 <strong>v{{ $artifact->version }}</strong></div>
        <span class="cs-meta-sep">|</span>
        <div class="cs-meta-item">
            추출일: <strong>{{ isset($artifact->meta['extracted_at']) ? \Carbon\Carbon::parse($artifact->meta['extracted_at'])->diffForHumans() : '알 수 없음' }}</strong>
        </div>
        @if(isset($artifact->meta['token_artifact_id']) && $artifact->meta['token_artifact_id'])
        <span class="cs-meta-sep">|</span>
        <div class="cs-meta-item" style="color:#15803d;">
            <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            토큰 매핑 연결됨
        </div>
        @endif
    </div>

    {{-- Stats --}}
    <div class="cs-stats">
        @php $stats = $artifact->meta ?? []; @endphp
        <div class="cs-stat">
            <div class="cs-stat-num">{{ $stats['total_components'] ?? 0 }}</div>
            <div class="cs-stat-label">총 컴포넌트</div>
        </div>
        <div class="cs-stat">
            <div class="cs-stat-num">{{ $stats['component_sets'] ?? 0 }}</div>
            <div class="cs-stat-label">ComponentSet</div>
        </div>
        <div class="cs-stat">
            <div class="cs-stat-num">{{ $stats['single_components'] ?? 0 }}</div>
            <div class="cs-stat-label">단일 컴포넌트</div>
        </div>
        <div class="cs-stat">
            <div class="cs-stat-num">{{ $stats['total_variants'] ?? 0 }}</div>
            <div class="cs-stat-label">총 Variants</div>
        </div>
    </div>

    {{-- Grid + Detail panel --}}
    <div class="cs-layout">
        {{-- Left: component grid --}}
        <div>
            {{-- Toolbar --}}
            <div class="cs-toolbar">
                <input type="search" x-model="search" placeholder="컴포넌트 검색..." class="cs-search">
                <button @click="typeFilter='all'"         :class="typeFilter==='all'         ? 'active':''" class="cs-filter-btn">전체</button>
                <button @click="typeFilter='ComponentSet'" :class="typeFilter==='ComponentSet' ? 'active':''" class="cs-filter-btn">ComponentSet</button>
                <button @click="typeFilter='Component'"   :class="typeFilter==='Component'   ? 'active':''" class="cs-filter-btn">단일</button>
            </div>

            {{-- Grid --}}
            <div class="cs-grid">
                @foreach(($specData['components'] ?? []) as $key => $component)
                <div class="cs-card"
                     :class="selectedKey === '{{ $key }}' ? 'selected' : ''"
                     x-show="matchesFilter('{{ $component['type'] ?? 'Component' }}', '{{ $component['name'] ?? $key }}')"
                     @click="selectComponent('{{ $key }}')">
                    <div class="cs-card-preview">
                        @if(!empty($component['preview_url']))
                            <img src="{{ $component['preview_url'] }}" alt="{{ $component['name'] ?? $key }}" loading="lazy">
                        @else
                            <span class="cs-card-preview-empty">🧩</span>
                        @endif
                    </div>
                    <div class="cs-card-body">
                        <div class="cs-card-name" title="{{ $component['name'] ?? $key }}">{{ $component['name'] ?? $key }}</div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                            <span class="cs-type-badge {{ ($component['type'] ?? '') === 'ComponentSet' ? 'set' : 'solo' }}">
                                {{ ($component['type'] ?? 'Component') === 'ComponentSet' ? 'Set' : 'Solo' }}
                            </span>
                            @if(($component['type'] ?? '') === 'ComponentSet')
                            <span class="cs-card-meta">{{ $component['variants_count'] ?? 0 }}개 variants</span>
                            @endif
                        </div>
                        @if(!empty($component['description']))
                        <div class="cs-card-meta" style="margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $component['description'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Right: detail panel --}}
        <div class="cs-detail">
            <div x-show="!selectedKey" class="cs-detail-empty">
                <div style="font-size:32px;margin-bottom:10px;">👈</div>
                <div>컴포넌트를 클릭하면<br>상세 정보를 볼 수 있습니다.</div>
            </div>

            <div x-show="selectedKey" x-cloak>
                {{-- Preview --}}
                <div class="cs-detail-preview">
                    <img x-show="detail.preview_url" :src="detail.preview_url" :alt="detail.name" style="max-width:100%;max-height:160px;object-fit:contain;">
                    <span x-show="!detail.preview_url" style="font-size:36px;opacity:.2;">🧩</span>
                </div>

                {{-- Header --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <div class="cs-detail-name" x-text="detail.name"></div>
                    <span class="cs-type-badge" :class="detail.type === 'ComponentSet' ? 'set' : 'solo'" x-text="detail.type === 'ComponentSet' ? 'Set' : 'Solo'"></span>
                </div>
                <div class="cs-detail-desc" x-text="detail.description || '설명 없음'"></div>

                {{-- Variants count --}}
                <div x-show="detail.variants_count > 1" style="font-size:12.5px;color:#64748b;margin-bottom:12px;">
                    <strong x-text="detail.variants_count"></strong> 개 Variants
                </div>

                {{-- Props --}}
                <div x-show="Object.keys(detail.props || {}).length > 0">
                    <div class="cs-section-label">Props</div>
                    <table class="cs-props-table">
                        <thead>
                            <tr><th>이름</th><th>값 목록</th><th>기본값</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="[propName, prop] in Object.entries(detail.props || {})" :key="propName">
                                <tr>
                                    <td x-text="propName"></td>
                                    <td x-text="(prop.values || []).join(', ')"></td>
                                    <td x-text="prop.default || ''"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Tokens used --}}
                <div x-show="(detail.tokens_used || []).length > 0">
                    <div class="cs-section-label">사용된 토큰</div>
                    <div class="cs-tokens-list">
                        <template x-for="token in (detail.tokens_used || [])" :key="token">
                            <span class="cs-token-chip" x-text="token"></span>
                        </template>
                    </div>
                </div>

                {{-- Figma link --}}
                <div x-show="detail.figma_node_id" style="margin-bottom:14px;">
                    <a :href="'https://www.figma.com/file/{{ $artifact->meta['figma_file_key'] ?? '' }}?node-id=' + encodeURIComponent(detail.figma_node_id)"
                       target="_blank" rel="noopener" class="cs-btn ghost sm">
                        <svg width="10" height="10" viewBox="0 0 38 57" fill="none"><path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/><path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/><path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/><path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" fill="#F24E1E"/><path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" fill="#A259FF"/></svg>
                        Figma에서 보기 →
                    </a>
                </div>

                {{-- Documentation (editable) --}}
                <div class="cs-section-label">사용 가이드</div>
                <textarea x-model="detail.documentation" class="cs-doc-area"
                          placeholder="이 컴포넌트의 사용 가이드를 작성하세요..."></textarea>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button @click="saveDoc()" :disabled="savingDoc" class="cs-btn primary sm">
                        <span x-text="savingDoc ? '저장 중...' : '저장'"></span>
                    </button>
                    <span x-show="saveMsg" x-text="saveMsg" style="font-size:12px;color:#15803d;align-self:center;"></span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Re-extract Modal ──────────────────────────────────── --}}
    @if($artifact)
    <div x-show="showExtractModal" style="position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px;" x-cloak>
        <div style="background:#fff;border-radius:16px;padding:28px;max-width:480px;width:100%;">
            <h3 style="font-size:16px;font-weight:800;color:#1e1b2e;margin:0 0 6px;">컴포넌트 명세 재추출</h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 18px;">기존 명세는 새 버전으로 보존됩니다.</p>
            <input type="url" x-model="figmaUrl" class="cs-url-input"
                   placeholder="https://www.figma.com/file/ABC123/..."
                   value="{{ $artifact->meta['figma_file_key'] ?? '' }}">
            <div style="display:flex;gap:8px;margin-top:14px;justify-content:flex-end;">
                <button @click="showExtractModal=false" class="cs-btn secondary">취소</button>
                <button @click="doExtract()" :disabled="!figmaUrl || extracting" class="cs-btn primary">재추출</button>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function csPage(cfg) {
    return {
        // State
        figmaUrl:        '',
        linkTokens:      cfg.hasTokens,
        search:          '',
        typeFilter:      'all',
        extracting:      false,
        showExtractModal: false,
        selectedKey:     null,
        detail:          {},
        savingDoc:       false,
        saveMsg:         '',
        hasArtifact:     cfg.hasArtifact,
        hasTokens:       cfg.hasTokens,

        matchesFilter(type, name) {
            const q = this.search.trim().toLowerCase();
            if (q && !name.toLowerCase().includes(q)) return false;
            if (this.typeFilter === 'all') return true;
            return type === this.typeFilter;
        },

        async selectComponent(key) {
            this.selectedKey = key;
            this.saveMsg     = '';

            const url = cfg.showUrl.replace('__KEY__', encodeURIComponent(key));
            try {
                const res = await axios.get(url);
                this.detail = res.data.component || {};
            } catch {
                this.detail = {};
            }
        },

        async doExtract() {
            if (!this.figmaUrl) return;
            this.extracting      = true;
            this.showExtractModal = false;

            try {
                await axios.post(cfg.extractUrl, {
                    figma_url:   this.figmaUrl,
                    link_tokens: this.linkTokens,
                    _token:      document.querySelector('meta[name="csrf-token"]').content,
                });
                window.location.reload();
            } catch (err) {
                const msg = err.response?.data?.message || '추출 중 오류가 발생했습니다.';
                alert(msg);
            } finally {
                this.extracting = false;
            }
        },

        async saveDoc() {
            if (!this.selectedKey) return;
            this.savingDoc = true;
            this.saveMsg   = '';

            const url = cfg.updateUrl.replace('__KEY__', encodeURIComponent(this.selectedKey));
            try {
                await axios.patch(url, {
                    documentation: this.detail.documentation || '',
                    description:   this.detail.description   || '',
                    _token:        document.querySelector('meta[name="csrf-token"]').content,
                });
                this.saveMsg = '저장됨 ✓';
                setTimeout(() => { this.saveMsg = ''; }, 2000);
            } catch {
                this.saveMsg = '저장 실패';
            } finally {
                this.savingDoc = false;
            }
        },
    };
}
</script>
@endpush
