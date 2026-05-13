<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $project->name ?? 'Unknown' }} 디자인 시스템</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;font-size:14px;color:#1e293b;background:#f8fafc;line-height:1.6;}
.ds-wrap{max-width:1100px;margin:0 auto;padding:32px 24px;}
h1{font-size:28px;font-weight:800;color:#1e1b2e;margin-bottom:6px;}
h2{font-size:20px;font-weight:700;color:#1e1b2e;margin:32px 0 16px;padding-bottom:8px;border-bottom:2px solid #ede8ff;}
h3{font-size:15px;font-weight:700;color:#374151;margin:20px 0 10px;}
h4{font-size:13px;font-weight:600;color:#475569;margin:14px 0 8px;}
p{margin-bottom:10px;color:#475569;}
.ds-meta{background:#fff;border:1.5px solid #ede8ff;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;gap:24px;flex-wrap:wrap;}
.ds-meta-item{font-size:12px;color:#64748b;}<br>.ds-meta-item strong{color:#374151;}
.ds-section{background:#fff;border:1.5px solid #ede8ff;border-radius:12px;padding:20px 22px;margin-bottom:20px;}
/* Colors */
.ds-color-grid{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;}
.ds-color-chip{text-align:center;width:80px;}
.ds-color-swatch{width:80px;height:60px;border-radius:8px;border:1px solid rgba(0,0,0,.08);margin-bottom:4px;}
.ds-color-name{font-size:10px;color:#64748b;word-break:break-all;}
.ds-color-val{font-size:10px;font-family:monospace;color:#94a3b8;}
/* Typography */
.ds-typo-item{padding:12px 14px;border:1.5px solid #f1f5f9;border-radius:8px;margin-bottom:8px;}
.ds-typo-label{font-size:11px;color:#94a3b8;margin-top:6px;font-family:monospace;}
/* Components */
.ds-comp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;}
.ds-comp-card{border:1.5px solid #f1f5f9;border-radius:10px;padding:14px 16px;}
.ds-comp-name{font-size:14px;font-weight:700;color:#1e1b2e;margin-bottom:4px;}
.ds-comp-meta{font-size:11px;color:#94a3b8;margin-bottom:8px;}
.ds-comp-desc{font-size:12.5px;color:#475569;margin-bottom:8px;}
.ds-badge{display:inline-flex;align-items:center;font-size:10.5px;font-weight:600;padding:2px 7px;border-radius:4px;background:#f1f5f9;color:#475569;margin:2px;}
.ds-token-tag{display:inline-block;font-size:10px;font-family:monospace;background:#f5f3ff;color:#7c3aed;border-radius:4px;padding:1px 5px;margin:1px;}
/* Table */
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{text-align:left;padding:8px 10px;background:#f8fafc;border-bottom:1.5px solid #e2e8f0;font-weight:600;color:#374151;}
td{padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#475569;}
tr:hover td{background:#fafafa;}
code{font-family:monospace;font-size:11.5px;background:#f1f5f9;padding:1px 5px;border-radius:3px;color:#7c3aed;}
/* Mappings */
.ds-map-row{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f8fafc;}
.ds-map-row:last-child{border-bottom:none;}
.ds-map-id{font-family:monospace;font-size:11.5px;font-weight:700;color:#7c3aed;background:#f5f3ff;padding:2px 7px;border-radius:4px;white-space:nowrap;}
.ds-map-name{flex:1;font-size:13px;color:#374151;}
.ds-map-status{font-size:11.5px;}
/* Score */
.ds-score-big{font-size:48px;font-weight:900;font-family:monospace;margin-bottom:4px;}
.ds-score-bar{width:100%;height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;margin-bottom:16px;}
.ds-score-fill{height:100%;border-radius:4px;}
.ds-cat-row{display:flex;align-items:center;gap:10px;margin-bottom:6px;}
.ds-cat-label{width:80px;font-size:12px;color:#64748b;flex-shrink:0;}
.ds-cat-bar{flex:1;height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden;}
.ds-cat-fill{height:100%;border-radius:2px;}
.ds-cat-score{width:30px;text-align:right;font-size:11.5px;font-weight:700;}
/* Stats chips */
.ds-stat-chips{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
.ds-stat-chip{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;}
.ds-stat-chip.critical{background:#fef2f2;color:#dc2626;}
.ds-stat-chip.warning{background:#fffbeb;color:#d97706;}
.ds-stat-chip.info{background:#eff6ff;color:#2563eb;}
.ds-stat-chip.passed{background:#f0fdf4;color:#16a34a;}
/* Philosophy */
.ds-philosophy{background:#f5f3ff;border-left:3px solid #7c3aed;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13.5px;color:#374151;line-height:1.7;margin-bottom:16px;}
/* Code block */
.ds-code{background:#1e1b2e;color:#e2e8f0;padding:14px 16px;border-radius:10px;font-family:monospace;font-size:11.5px;overflow-x:auto;white-space:pre;}
/* Footer */
.ds-footer{text-align:center;font-size:11.5px;color:#94a3b8;margin-top:32px;padding-top:16px;border-top:1px solid #f1f5f9;}
.ds-group-title{font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:16px 0 8px;}
</style>
</head>
<body>
<div class="ds-wrap">

{{-- Header --}}
<h1>{{ $project->name ?? 'Unknown' }} 디자인 시스템</h1>
<div class="ds-meta">
    <div class="ds-meta-item">생성일 <strong>{{ ($metadata['generated_at'] ?? now())->format('Y-m-d H:i') }}</strong></div>
    <div class="ds-meta-item">버전 <strong>1.0</strong></div>
    <div class="ds-meta-item">생성 <strong>AI Agent</strong></div>
    @if(!empty($flat_colors)) <div class="ds-meta-item">색상 토큰 <strong>{{ count($flat_colors) }}개</strong></div> @endif
    @if(!empty(($components['components'] ?? []))) <div class="ds-meta-item">컴포넌트 <strong>{{ count($components['components']) }}개</strong></div> @endif
</div>

{{-- Philosophy --}}
@if(!empty($ai_sections['philosophy']))
<div class="ds-section">
    <h2 style="margin-top:0;">디자인 철학</h2>
    <div class="ds-philosophy">{{ $ai_sections['philosophy'] }}</div>
</div>
@endif

{{-- Foundation: Colors --}}
<div class="ds-section">
    <h2 style="margin-top:0;">색상 팔레트</h2>
    @php
    $colorGroups = [];
    foreach($flat_colors ?? [] as $c) {
        $parts = explode('.', $c['path'], 2);
        $group = $parts[0] ?? 'misc';
        $colorGroups[$group][] = $c;
    }
    @endphp
    @forelse($colorGroups as $group => $colorList)
    <div class="ds-group-title">{{ ucfirst($group) }}</div>
    <div class="ds-color-grid">
        @foreach($colorList as $c)
        @if(is_string($c['value']) && (str_starts_with($c['value'], '#') || str_starts_with($c['value'], 'rgb')))
        <div class="ds-color-chip">
            <div class="ds-color-swatch" style="background:{{ $c['value'] }};"></div>
            <div class="ds-color-name">{{ $c['path'] }}</div>
            <div class="ds-color-val">{{ $c['value'] }}</div>
        </div>
        @endif
        @endforeach
    </div>
    @empty
    <p style="color:#94a3b8;">색상 토큰이 없습니다.</p>
    @endforelse
</div>

{{-- Foundation: Typography --}}
@if(!empty($flat_typography))
<div class="ds-section">
    <h2 style="margin-top:0;">타이포그래피</h2>
    @foreach($flat_typography as $t)
    @php
    $val = $t['value'];
    $ff  = is_array($val) ? ($val['fontFamily'] ?? 'inherit') : 'inherit';
    $fs  = is_array($val) ? ($val['fontSize']   ?? '14px')    : '14px';
    $fw  = is_array($val) ? ($val['fontWeight']  ?? 400)       : 400;
    $lh  = is_array($val) ? ($val['lineHeight']  ?? '1.5')     : '1.5';
    @endphp
    <div class="ds-typo-item">
        <div style="font-family:{{ $ff }};font-size:{{ $fs }};font-weight:{{ $fw }};line-height:{{ $lh }};">
            가나다라마바사 The quick brown fox
        </div>
        <div class="ds-typo-label">
            {{ $t['path'] }} · {{ $ff }} {{ $fs }} fw:{{ $fw }} lh:{{ $lh }}
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Components --}}
@if(!empty($components['components']))
<div class="ds-section">
    <h2 style="margin-top:0;">컴포넌트 ({{ count($components['components']) }}개)</h2>
    <div class="ds-comp-grid">
        @foreach($components['components'] as $key => $component)
        <div class="ds-comp-card">
            <div class="ds-comp-name">{{ $component['name'] ?? $key }}</div>
            <div class="ds-comp-meta">
                {{ $component['type'] ?? '-' }} &nbsp;·&nbsp; Variants {{ $component['variants_count'] ?? 0 }}개
            </div>
            @if(!empty($component['description']))
            <div class="ds-comp-desc">{{ $component['description'] }}</div>
            @endif
            @if(!empty($component['props']))
            <div>
                @foreach($component['props'] as $propName => $propConfig)
                @foreach($propConfig['values'] ?? [] as $v)
                <span class="ds-badge">{{ $propName }}: {{ $v }}</span>
                @endforeach
                @endforeach
            </div>
            @endif
            @if(!empty($component['tokens_used']))
            <div style="margin-top:6px;">
                @foreach(array_slice($component['tokens_used'], 0, 4) as $token)
                <span class="ds-token-tag">{{ $token }}</span>
                @endforeach
                @if(count($component['tokens_used']) > 4)
                <span style="font-size:10px;color:#94a3b8;">+{{ count($component['tokens_used']) - 4 }}개</span>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Layouts --}}
@if(!empty($layouts['standard_layouts']))
<div class="ds-section">
    <h2 style="margin-top:0;">표준 레이아웃</h2>
    <table>
        <thead>
            <tr>
                <th>레이아웃명</th>
                <th>타입</th>
                <th>사용률</th>
                <th>사용 화면 수</th>
                <th>상세</th>
            </tr>
        </thead>
        <tbody>
            @foreach($layouts['standard_layouts'] as $key => $layout)
            <tr>
                <td><strong>{{ $layout['name'] ?? $key }}</strong></td>
                <td>{{ $layout['spec']['type'] ?? '-' }}</td>
                <td>{{ $layout['usage_percent'] ?? 0 }}%</td>
                <td>{{ count($layout['used_in_frames'] ?? []) }}개</td>
                <td style="font-size:11px;color:#64748b;">
                    @if(($layout['spec']['type'] ?? '') === 'grid')
                    {{ $layout['spec']['columns'] ?? '' }}col / {{ $layout['spec']['gutter'] ?? '' }}gutter
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if(!empty($layouts['non_standard_frames']))
    <div style="margin-top:14px;padding:10px 14px;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:8px;font-size:12.5px;color:#92400e;">
        <strong>⚠️ 비표준 프레임 {{ count($layouts['non_standard_frames']) }}개</strong>:
        {{ collect($layouts['non_standard_frames'])->pluck('frame_name')->join(', ') }}
    </div>
    @endif
</div>
@endif

{{-- Screen Mappings --}}
@if(!empty($mappings))
@php
$mappedCount = count(array_filter($mappings, fn($m) => $m['is_mapped']));
$totalCount  = count($mappings);
@endphp
<div class="ds-section">
    <h2 style="margin-top:0;">화면 매핑 ({{ $mappedCount }}/{{ $totalCount }})</h2>
    @foreach($mappings as $m)
    <div class="ds-map-row">
        <span class="ds-map-id">{{ $m['screen_id'] }}</span>
        <span class="ds-map-name">{{ $m['name'] }}</span>
        @if($m['is_mapped'])
        <span class="ds-map-status" style="color:#16a34a;">✅ {{ $m['figma_frame_name'] }}</span>
        @if($m['figma_url'])
        <a href="{{ $m['figma_url'] }}" target="_blank" style="font-size:11px;color:#7c3aed;">Figma ↗</a>
        @endif
        @if($m['figma_dev_url'])
        <a href="{{ $m['figma_dev_url'] }}" target="_blank" style="font-size:11px;color:#2563eb;">Dev ↗</a>
        @endif
        @else
        <span class="ds-map-status" style="color:#dc2626;">❌ 미매핑</span>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- Review --}}
@if($review)
@php
$stats     = $review['$metadata']['stats'] ?? [];
$breakdown = $review['summary']['compliance_breakdown'] ?? [];
$score     = $stats['compliance_score'] ?? 0;
$scoreColor= $score >= 80 ? '#16a34a' : ($score >= 60 ? '#d97706' : '#dc2626');
$scoreGrad = $score >= 80 ? 'linear-gradient(90deg,#22c55e,#86efac)' : ($score >= 60 ? 'linear-gradient(90deg,#f59e0b,#fcd34d)' : 'linear-gradient(90deg,#ef4444,#fca5a5)');
@endphp
<div class="ds-section">
    <h2 style="margin-top:0;">일관성 검수 결과</h2>
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;">
        <div class="ds-score-big" style="color:{{ $scoreColor }};">{{ $score }}</div>
        <div style="flex:1;">
            <div class="ds-score-bar"><div class="ds-score-fill" style="width:{{ $score }}%;background:{{ $scoreGrad }};"></div></div>
            <div style="font-size:11.5px;color:#94a3b8;">/ 100점</div>
        </div>
    </div>
    <div class="ds-stat-chips">
        <span class="ds-stat-chip critical">🔴 Critical {{ $stats['critical'] ?? 0 }}건</span>
        <span class="ds-stat-chip warning">🟡 Warning {{ $stats['warning'] ?? 0 }}건</span>
        <span class="ds-stat-chip info">🔵 Info {{ $stats['info'] ?? 0 }}건</span>
        <span class="ds-stat-chip passed">✅ 통과 {{ $stats['passed_screens'] ?? 0 }}화면</span>
    </div>
    @foreach($breakdown as $category => $catScore)
    @php $catColor = $catScore >= 80 ? '#16a34a' : ($catScore >= 60 ? '#d97706' : '#dc2626'); @endphp
    <div class="ds-cat-row">
        <span class="ds-cat-label">{{ ucfirst($category) }}</span>
        <div class="ds-cat-bar"><div class="ds-cat-fill" style="width:{{ $catScore }}%;background:{{ $scoreGrad }};"></div></div>
        <span class="ds-cat-score" style="color:{{ $catColor }};">{{ $catScore }}</span>
    </div>
    @endforeach
    @if(!empty($review['recommendations']))
    <h3 style="margin-top:16px;">권장사항</h3>
    <ul style="padding-left:18px;font-size:12.5px;color:#475569;">
        @foreach($review['recommendations'] as $rec)
        <li style="margin-bottom:6px;">{{ $rec }}</li>
        @endforeach
    </ul>
    @endif
</div>
@endif

{{-- Token JSON (Appendix) --}}
<div class="ds-section">
    <h2 style="margin-top:0;">토큰 JSON (전체)</h2>
    <div class="ds-code">{{ json_encode($tokens ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
</div>

<div class="ds-footer">
    본 문서는 AI Agent에 의해 자동 생성된 디자인 시스템의 단일 진실 원천(Single Source of Truth)입니다.<br>
    {{ ($metadata['generated_at'] ?? now())->format('Y-m-d H:i') }} 생성
</div>

</div>
</body>
</html>
