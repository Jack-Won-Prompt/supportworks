# AI Agent 스펙 명세

---

## 3.2.2 AS-IS 분석

> 마지막 업데이트: 2026-05-03 (T16-보완)

### 3.2.2.1 분석 단위 (Scope)

AS-IS 분석은 두 가지 단위를 동시에 지원합니다.

| 단위 | 설명 | 산출물 수 |
|---|---|---|
| **프로젝트 단위** | 프로젝트 전체 현황 종합 분석 | 1개 |
| **화면 단위** | 각 화면(SCR-XXX)별 정밀 분석 | N개 (화면 수만큼) |

두 방식은 독립적으로 공존 가능합니다 (한 프로젝트에 프로젝트 단위 1개 + 화면 단위 N개).

### 3.2.2.2 진입점

| 단위 | URL |
|---|---|
| 프로젝트 단위 | `GET /ai-agent/projects/{projectId}/planning/as-is` |
| 화면 단위 | `GET /ai-agent/projects/{projectId}/planning/screens/{screenId}` (상세 패널) |

### 3.2.2.3 입력 자료

멀티 파일 업로드 지원:
- 텍스트 (.txt, .md)
- 이미지 (.png, .jpg, .gif, .webp)
- Excel (.xlsx, .xls)
- PowerPoint (.pptx, .ppt)
- PDF (.pdf)

### 3.2.2.4 AI 처리 흐름

```
파일 업로드 (N개)
  ↓
멀티모달 AI (Claude claude-sonnet-4-5 또는 최신 sonnet) 분석
  ↓
핵심 이슈 / 문제점 / 개선 필요 사항 추출
  ↓
카테고리별 자동 분류
  ↓
AS-IS 분석 리포트 생성 (구조화된 JSON + Markdown)
```

### 3.2.2.5 출력 형태 (리포트 구조)

```json
{
  "summary":       "현황 요약 (1~3 문단)",
  "issues": [
    { "category": "UX", "severity": "high", "description": "...", "source_file": "screen1.png" }
  ],
  "categories": {
    "UX":           { "count": 3, "items": [...] },
    "Performance":  { "count": 1, "items": [...] },
    "Accessibility":{ "count": 2, "items": [...] }
  },
  "source_mapping": [
    { "file": "screen1.png", "issues": [0, 2] }
  ]
}
```

### 3.2.2.6 산출물 저장 규칙

`ai_agent_artifacts` 테이블에 저장:

| 컬럼 | 값 |
|---|---|
| `type` | `as_is_analysis` |
| `scope_type` | `'project'` 또는 `'screen'` |
| `scope_id` | `project.id` 또는 `screen.id` |
| `title` | `"AS-IS 분석 — {프로젝트명}"` 또는 `"AS-IS 분석 — {SCR-XXX}"` |
| `content` | JSON 리포트 (3.2.2.5 구조) |
| `meta` | `{ "file_count": N, "analyzed_at": "...", "model": "..." }` |

**중복 방지 규칙**:
- 같은 `(project_id, type, scope_type, scope_id)` 조합은 1개만 존재 (새 버전으로 갱신)
- 갱신 시 `updateWithVersion()` 호출로 이전 버전 보존

---

# AI Agent 승인 게이트 명세

## 4.x 승인 상태 매핑

### 4.x.1 개요

승인 게이트는 HITL(Human-In-The-Loop) 검증 지점으로, 스펙에서 정의한 논리적 상태(Logical Status)와 DB에 저장되는 물리적 상태(DB Status)가 분리되어 있습니다.

- **논리적 상태** — 스펙/UI 레이어에서 사용하는 상태 식별자
- **DB 상태** — `ai_agent_approval_gates.status` 컬럼 값 (`ApprovalStatus` enum)

### 4.x.2 상태 매핑 테이블

| 논리적 상태 (Spec) | DB 상태 (`ApprovalStatus`) | 조건 | UI 레이블 | 뱃지 CSS 클래스 |
|---|---|---|---|---|
| `NOT_REQUESTED` | (레코드 없음 / `null`) | `gate === null` | 요청 전 | `.apg-badge.none` |
| `IN_REVIEW` | `pending` | `gate->status === PENDING` | 승인 대기 | `.apg-badge.pending` |
| `APPROVED` | `approved` | `gate->status === APPROVED` | 승인됨 | `.apg-badge.approved` |
| `REJECTED` | `rejected` | `gate->status === REJECTED` | 반려됨 | `.apg-badge.rejected` |

### 4.x.3 주요 규칙

1. **NOT_REQUESTED**: `AiAgentApprovalGate` 레코드가 존재하지 않는 상태. DB에는 `pending` 값이 없고 row 자체가 없음.
2. **IN_REVIEW**: 스펙에서 "검토 중" 으로 표현되나 DB enum 값은 `pending`. `ApprovalGateHelper::getLogicalStatus()` 를 통해 `IN_REVIEW` 로 변환.
3. **isPassable**: `APPROVED` 상태일 때만 `true`. 다음 단계 활성화 조건으로 사용.
4. 스펙 코드에서 `gate->status->value === 'pending'` 직접 비교 금지 — 반드시 `ApprovalGateHelper` 경유.

### 4.x.4 단계 상태(StageStatus)와의 관계

`AiAgentProjectStage.status` (StageStatus enum) 는 승인 게이트의 생명주기를 반영합니다.

| StageStatus | 의미 | 사이드바 아이콘 색상 |
|---|---|---|
| `LOCKED` | 이전 단계 미완료, 접근 불가 | 회색 |
| `IN_PROGRESS` | 작업 중 (편집 가능) | 황색(amber) |
| `PENDING_APPROVAL` | 승인 게이트 `IN_REVIEW` 중 | 파랑(blue) |
| `APPROVED` | 승인 완료, 다음 단계 활성화 | 초록(green) |

### 4.x.5 헬퍼 클래스

`App\Services\Agent\ApprovalGateHelper` — 모든 상태 매핑 로직의 단일 진실 공급원(SSOT).

```php
// 논리적 상태 조회
ApprovalGateHelper::getLogicalStatus(?AiAgentApprovalGate $gate): string
// → 'NOT_REQUESTED' | 'IN_REVIEW' | 'APPROVED' | 'REJECTED'

// UI 레이블
ApprovalGateHelper::getUiLabel(?AiAgentApprovalGate $gate): string
// → '요청 전' | '승인 대기' | '승인됨' | '반려됨'

// 뱃지 CSS 수정자 클래스 (.apg-badge.{class})
ApprovalGateHelper::getUiBadgeClass(?AiAgentApprovalGate $gate): string
// → 'none' | 'pending' | 'approved' | 'rejected'

// 인라인 SVG 아이콘 (!! 언이스케이프 출력)
ApprovalGateHelper::getUiIcon(?AiAgentApprovalGate $gate): string

// 다음 단계 진행 가능 여부
ApprovalGateHelper::isPassable(?AiAgentApprovalGate $gate): bool

// 사이드바용 StageStatus 아이콘 SVG
ApprovalGateHelper::getStageStatusIcon(?StageStatus $status): string
```
