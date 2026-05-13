<?php
// This template renders as Markdown. $project, $tokens, $components, $layouts,
// $mappings, $review, $metadata, $ai_sections, $flat_colors, $flat_typography, $flat_shadows
// are injected by DesignSystemTemplateService::renderMarkdown()
?>
@php
use App\Services\Agent\DesignSystemTemplateService;
$colorGroups = [];
foreach($flat_colors ?? [] as $c) {
    $parts = explode('.', $c['path'], 2);
    $group = $parts[0] ?? 'misc';
    $colorGroups[$group][] = $c;
}
@endphp
# {{ $project->name ?? 'Unknown' }} 디자인 시스템

> 생성일: {{ ($metadata['generated_at'] ?? now())->format('Y년 m월 d일') }}
> 버전: 1.0
> 생성: AI Agent

---

## 1. 개요

### 1.1 프로젝트 정보

- 프로젝트: **{{ $project->name ?? '-' }}**
- 문서 생성: AI Agent v1.0
- 업데이트: {{ ($metadata['generated_at'] ?? now())->format('Y-m-d H:i') }}

### 1.2 디자인 철학

@if(!empty($ai_sections['philosophy']))
{{ $ai_sections['philosophy'] }}
@else
_디자인 철학이 작성되지 않았습니다. AI 보강 버튼을 눌러 자동 생성하세요._
@endif

---

## 2. Foundation

### 2.1 색상 팔레트

@if(!empty($flat_colors))
@foreach($colorGroups as $group => $colorList)
#### {{ ucfirst($group) }}

| 토큰 경로 | 값 |
|-----------|-----|
@foreach($colorList as $c)
| `{{ $c['path'] }}` | `{{ $c['value'] }}` |
@endforeach

@endforeach
@else
_디자인 토큰이 없습니다._
@endif

### 2.2 타이포그래피

@if(!empty($flat_typography))
| 토큰 경로 | 값 |
|-----------|-----|
@foreach($flat_typography as $t)
| `{{ $t['path'] }}` | `{{ is_array($t['value']) ? json_encode($t['value'], JSON_UNESCAPED_UNICODE) : $t['value'] }}` |
@endforeach
@else
_타이포그래피 토큰이 없습니다._
@endif

### 2.3 그림자

@if(!empty($flat_shadows))
| 토큰 경로 | 값 |
|-----------|-----|
@foreach($flat_shadows as $s)
| `{{ $s['path'] }}` | `{{ is_array($s['value']) ? json_encode($s['value']) : $s['value'] }}` |
@endforeach
@else
_그림자 토큰이 없습니다._
@endif

---

## 3. 컴포넌트

@php $comps = ($components['components'] ?? []); @endphp
총 **{{ count($comps) }}개** 컴포넌트

@if(!empty($comps))
@foreach($comps as $key => $component)
### 3.{{ $loop->iteration }} {{ $component['name'] ?? $key }}

- **타입**: {{ $component['type'] ?? '-' }}
- **Variants**: {{ $component['variants_count'] ?? 0 }}개
- **설명**: {{ $component['description'] ?: '_(설명 없음)_' }}

@if(!empty($component['props']))
#### Props

| 이름 | 값 목록 | 기본값 |
|------|---------|--------|
@foreach($component['props'] as $propName => $propConfig)
| `{{ $propName }}` | {{ implode(', ', $propConfig['values'] ?? []) }} | `{{ $propConfig['default'] ?? '-' }}` |
@endforeach
@endif

@if(!empty($component['tokens_used']))
#### 사용된 토큰

@foreach($component['tokens_used'] as $token)
- `{{ $token }}`
@endforeach
@endif

---

@endforeach
@else
_컴포넌트 명세가 없습니다._
@endif

## 4. 레이아웃

@if(!empty($layouts['standard_layouts']))
### 4.1 표준 레이아웃

@foreach($layouts['standard_layouts'] as $key => $layout)
#### {{ $layout['name'] ?? $key }}

- **사용률**: {{ $layout['usage_percent'] ?? 0 }}%
- **사용 화면 수**: {{ count($layout['used_in_frames'] ?? []) }}개
@if(isset($layout['spec']['type']))
- **타입**: {{ $layout['spec']['type'] }}
@if($layout['spec']['type'] === 'grid')
- **Columns**: {{ $layout['spec']['columns'] ?? '-' }}
- **Gutter**: {{ $layout['spec']['gutter'] ?? '-' }}
- **Margin**: {{ $layout['spec']['margin'] ?? '-' }}
@endif
@endif

@endforeach
@else
_레이아웃 명세가 없습니다._
@endif

@if(!empty($layouts['non_standard_frames']))
### 4.2 비표준 프레임 ⚠️

@foreach($layouts['non_standard_frames'] as $frame)
- **{{ $frame['frame_name'] ?? '' }}**: {{ $frame['deviation'] ?? '' }}
@endforeach
@endif

---

## 5. 화면 매핑

@php
$mappedCount = count(array_filter($mappings ?? [], fn($m) => $m['is_mapped']));
$totalCount  = count($mappings ?? []);
@endphp
총 **{{ $totalCount }}개** 화면 중 **{{ $mappedCount }}개** Figma 매핑 완료

@if(!empty($mappings))
| 화면 ID | 화면명 | Figma 매핑 | 적용 레이아웃 |
|---------|--------|-----------|------------|
@foreach($mappings as $m)
| {{ $m['screen_id'] }} | {{ $m['name'] }} | @if($m['is_mapped']) ✅ {{ $m['figma_frame_name'] }} @else ❌ 미매핑 @endif | @if(!empty($m['applied_layouts'])) {{ collect($m['applied_layouts'])->pluck('name')->join(', ') }} @else - @endif |
@endforeach
@else
_화면 매핑 데이터가 없습니다._
@endif

---

## 6. 일관성 검수 결과

@if($review)
@php
$stats     = $review['$metadata']['stats'] ?? [];
$breakdown = $review['summary']['compliance_breakdown'] ?? [];
$recs      = $review['recommendations'] ?? [];
@endphp

### 6.1 종합 점수

**{{ $stats['compliance_score'] ?? 0 }}점 / 100**

- Critical: {{ $stats['critical'] ?? 0 }}건
- Warning: {{ $stats['warning'] ?? 0 }}건
- Info: {{ $stats['info'] ?? 0 }}건

### 6.2 카테고리별 점수

| 카테고리 | 점수 |
|---------|------|
@foreach($breakdown as $category => $score)
| {{ ucfirst($category) }} | {{ $score }}점 |
@endforeach

### 6.3 권장사항

@foreach($recs as $rec)
- {{ $rec }}
@endforeach
@else
_일관성 검수가 실행되지 않았습니다._
@endif

---

## 7. 부록

### 7.1 토큰 JSON

```json
{!! json_encode($tokens ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
```

### 7.2 코드 통합 가이드

@if(!empty($ai_sections['integration_guide']))
{{ $ai_sections['integration_guide'] }}
@else
프로젝트의 프론트엔드 스택에 맞게 토큰 JSON을 임포트하여 사용하세요.
@endif

---

_본 문서는 AI Agent에 의해 자동 생성된 디자인 시스템의 단일 진실 원천(Single Source of Truth)입니다._
