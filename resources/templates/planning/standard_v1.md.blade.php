<?php
/**
 * Standard Planning Document Template v1.0
 *
 * Variables injected by PlanningDocumentDataContext::build():
 *   $project          — Project model
 *   $asis             — array|null  (as-is artifact content)
 *   $tobe             — array|null  (to-be artifact content)
 *   $requirements     — Collection<AiAgentRequirement>
 *   $gap              — array|null  (gap artifact content)
 *   $gaps             — Collection<AiAgentGap>
 *   $screens          — Collection<AiAgentScreen>
 *   $milestones       — Collection<Schedule>
 *   $attached_files   — Collection<AiAgentArtifactFile>
 *   $document_version — string
 *   $ai_sections      — array  (key => rendered markdown text, filled by T22)
 */
?>
# {{ $project->name }} 기획서

> 작성일: {{ now()->format('Y년 m월 d일') }}
> 버전: {{ $document_version }}
> 생성 도구: Supportworks AI Agent

---

## 1. 프로젝트 개요

### 1.1 프로젝트명
{{ $project->name }}

### 1.2 프로젝트 기간
@php
    $start = $project->start_date?->format('Y년 m월 d일') ?? '미정';
    $end   = $project->end_date?->format('Y년 m월 d일') ?? '미정';
    $weeks = ($project->start_date && $project->end_date)
        ? (int) round($project->start_date->diffInDays($project->end_date) / 7)
        : null;
@endphp
{{ $start }} ~ {{ $end }}{{ $weeks ? " ({$weeks}주)" : '' }}

### 1.3 프로젝트 목표

{!! $ai_sections['section_1_3_objectives'] ?? '_프로젝트 목표는 기획서 작성 시 AI가 생성합니다._' !!}

### 1.4 핵심 이해관계자

@if($project->members && $project->members->count() > 0)
| 이름 | 역할 |
|------|------|
@foreach($project->members as $member)
| {{ $member->name }} | {{ $member->pivot->role ?? '-' }} |
@endforeach
@else
_이해관계자 정보가 등록되지 않았습니다._
@endif

---

## 2. 현황 분석 (AS-IS)

### 2.1 현황 요약

@if($asis && !empty($asis['summary']))
{{ $asis['summary'] }}
@else
_AS-IS 분석이 완료되지 않았습니다._
@endif

### 2.2 주요 문제점

@if($asis && !empty($asis['issues']))
@foreach($asis['issues'] as $i => $issue)
@php
    $severity = strtoupper($issue['severity'] ?? 'MEDIUM');
    $icons = ['HIGH' => '🔴', 'MEDIUM' => '🟡', 'LOW' => '🟢'];
    $icon = $icons[$severity] ?? '⚪';
@endphp
#### {{ $icon }} [{{ $issue['category'] ?? '기타' }}] {{ $issue['title'] ?? "이슈 {$i}" }}
- **심각도**: {{ $severity }}
- **설명**: {{ $issue['description'] ?? '-' }}
@if(!empty($issue['source_files']))
- **출처**: {{ implode(', ', (array) $issue['source_files']) }}
@endif

@endforeach
@else
_주요 문제점 데이터가 없습니다._
@endif

### 2.3 카테고리별 분석

@if($asis && !empty($asis['categories']))
| 카테고리 | 건수 |
|----------|------|
@foreach($asis['categories'] as $cat => $info)
| {{ $cat }} | {{ $info['count'] ?? 0 }} |
@endforeach
@else
_카테고리 데이터가 없습니다._
@endif

---

## 3. 요구사항 분석 (TO-BE)

### 3.1 요구사항 개요

@if($tobe && !empty($tobe['overview']))
{{ $tobe['overview'] }}
@else
_TO-BE 분석이 완료되지 않았습니다._
@endif

### 3.2 우선순위별 요구사항

@php
$grouped = $requirements->groupBy(fn($r) => strtoupper($r->priority->value ?? $r->priority ?? 'SHOULD'));
$priorityOrder = ['MUST', 'SHOULD', 'COULD', 'WONT'];
@endphp

@foreach($priorityOrder as $priority)
@if($grouped->has($priority))
#### {{ $priority }} ({{ $grouped[$priority]->count() }}건)

| ID | 카테고리 | 제목 |
|----|---------|------|
@foreach($grouped[$priority] as $req)
| {{ $req->req_id }} | {{ $req->category ?? '-' }} | {{ $req->title }} |
@endforeach

@endif
@endforeach

@if($requirements->isEmpty())
_요구사항이 등록되지 않았습니다._
@endif

### 3.3 카테고리별 요구사항

@php
$byCategory = $requirements->groupBy('category');
@endphp

@foreach($byCategory as $cat => $reqs)
#### {{ $cat ?: '미분류' }} ({{ $reqs->count() }}건)

| ID | 우선순위 | 제목 |
|----|---------|------|
@foreach($reqs as $req)
| {{ $req->req_id }} | {{ strtoupper($req->priority->value ?? $req->priority ?? '-') }} | {{ $req->title }} |
@endforeach

@endforeach

---

## 4. Gap 분석

### 4.1 분석 요약

@if($gap && !empty($gap['executive_summary']))
{{ $gap['executive_summary'] }}
@else
_Gap 분석이 완료되지 않았습니다._
@endif

### 4.2 주요 Gap

@if($gaps->isNotEmpty())
@foreach($gaps as $g)
#### {{ $g->gap_id }} — {{ $g->title }}

- **카테고리**: {{ $g->category }}
- **심각도**: {{ strtoupper($g->severity) }}
- **현재 상태**: {{ $g->current_state ?? '-' }}
- **목표 상태**: {{ $g->target_state ?? '-' }}
@if(!empty($g->related_requirement_ids))
- **관련 요구사항**: {{ implode(', ', $g->related_requirement_ids) }}
@endif
@if(!empty($g->recommended_actions))
- **권고 조치**:
@foreach($g->recommended_actions as $action)
  - {{ $action }}
@endforeach
@endif

@endforeach
@else
_Gap 데이터가 없습니다._
@endif

### 4.3 개선 기회

@if($gap && !empty($gap['improvement_opportunities']))
@foreach($gap['improvement_opportunities'] as $opp)
#### {{ $opp['title'] ?? '-' }}
{{ $opp['description'] ?? '' }}

**기대 효과**: {{ $opp['expected_benefit'] ?? '-' }}

@endforeach
@else
_개선 기회 데이터가 없습니다._
@endif

### 4.4 리스크 평가

@if($gap && !empty($gap['risks']))
| 리스크 | 발생 가능성 | 영향도 | 완화 방안 |
|--------|------------|--------|-----------|
@foreach($gap['risks'] as $risk)
| {{ $risk['title'] ?? '-' }} | {{ $risk['likelihood'] ?? '-' }} | {{ $risk['impact'] ?? '-' }} | {{ $risk['mitigation'] ?? '-' }} |
@endforeach
@else
_리스크 데이터가 없습니다._
@endif

---

## 5. 추진 전략

### 5.1 우선순위 액션

{!! $ai_sections['section_5_1_priority_actions'] ?? '_추진 전략은 기획서 작성 시 AI가 생성합니다._' !!}

### 5.2 단계적 접근 방안

@if(!empty($ai_sections['section_5_2_phasing_strategy']))
{!! $ai_sections['section_5_2_phasing_strategy'] !!}
@elseif($gap && !empty($gap['recommendations']['phasing_strategy']))
{{ $gap['recommendations']['phasing_strategy'] }}
@else
_단계적 접근 방안은 기획서 작성 시 AI가 생성합니다._
@endif

### 5.3 핵심 성공 요인

{!! $ai_sections['section_5_3_csf'] ?? '_핵심 성공 요인은 기획서 작성 시 AI가 생성합니다._' !!}

### 5.4 리스크 대응 전략

{!! $ai_sections['section_5_4_risk_strategy'] ?? '_리스크 대응 전략은 기획서 작성 시 AI가 생성합니다._' !!}

---

## 6. 화면 설계

### 6.1 화면 목록 (총 {{ $screens->count() }}건)

@if($screens->isNotEmpty())
| ID | 화면명 | 담당자 | 시작일 | 종료일 |
|----|--------|--------|--------|--------|
@foreach($screens as $screen)
| {{ $screen->screen_id }} | {{ $screen->title }} | {{ $screen->assignee?->name ?? '-' }} | {{ $screen->scheduled_start?->format('Y-m-d') ?? '-' }} | {{ $screen->scheduled_end?->format('Y-m-d') ?? '-' }} |
@endforeach
@else
_등록된 화면이 없습니다. T16(화면 목록 관리)을 먼저 완료해주세요._
@endif

### 6.2 화면 흐름도

_T23(IA/화면 흐름도 자동 생성)에서 자동 생성 예정입니다._

### 6.3 화면별 상세

@if($screens->isNotEmpty())
@foreach($screens as $screen)
#### {{ $screen->screen_id }} — {{ $screen->title }}

{!! $ai_sections['screen_' . $screen->screen_id] ?? '_화면 상세 설명은 기획서 작성 시 AI가 생성합니다._' !!}

@endforeach
@else
_화면이 등록되지 않아 화면별 상세를 생성할 수 없습니다._
@endif

---

## 7. 일정 및 리소스

### 7.1 전체 일정

@php
    $start = $project->start_date?->format('Y년 m월 d일') ?? '미정';
    $end   = $project->end_date?->format('Y년 m월 d일') ?? '미정';
@endphp
- **시작일**: {{ $start }}
- **종료일**: {{ $end }}

### 7.2 단계별 마일스톤

@if($milestones->isNotEmpty())
| 업무 | 시작일 | 종료일 | 담당자 | 상태 |
|------|--------|--------|--------|------|
@foreach($milestones as $ms)
| {{ $ms->title }} | {{ $ms->start_date?->format('Y-m-d') ?? '-' }} | {{ $ms->end_date?->format('Y-m-d') ?? '-' }} | {{ $ms->assignee?->name ?? '-' }} | {{ $ms->status ?? '-' }} |
@endforeach
@else
_일정 데이터가 없습니다._
@endif

### 7.3 리소스 계획

@if($project->members && $project->members->count() > 0)
| 구성원 | 역할 |
|--------|------|
@foreach($project->members as $member)
| {{ $member->name }} | {{ $member->pivot->role ?? '-' }} |
@endforeach
@else
_프로젝트 구성원 정보가 없습니다._
@endif

---

## 8. 부록

### 8.1 용어 정의

{!! $ai_sections['section_8_1_glossary'] ?? '_용어 정의는 기획서 작성 시 AI가 생성합니다._' !!}

### 8.2 참고 자료

@if($attached_files->isNotEmpty())
@foreach($attached_files as $file)
- {{ $file->original_name ?? $file->file_name }} ({{ strtoupper($file->file_type ?? '') }})
@endforeach
@else
_첨부된 참고 자료가 없습니다._
@endif

---

_본 문서는 Supportworks AI Agent에 의해 자동 작성되었으며, 담당자 검토 후 확정됩니다._
