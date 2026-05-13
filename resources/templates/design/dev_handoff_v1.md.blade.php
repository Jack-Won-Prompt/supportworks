# {{ $project->name }} — 개발 핸드오프

> 생성일: {{ now()->format('Y-m-d') }}
> Phase 3 (디자인) → Phase 4 (개발) 전환 자료

---

## 개요

- **매핑된 화면**: {{ count($devData) }}개
- **미매핑 화면**: {{ count($unmapped) }}개{{ count($unmapped) > 0 ? ' ⚠️ (별도 협의 필요)' : '' }}

---

## 화면별 Figma Dev Mode 링크

@forelse($devData as $screen)
### {{ $screen['screen_id'] }} — {{ $screen['name'] }}

@if(!empty($screen['description']))
{{ $screen['description'] }}

@endif
| 항목 | 내용 |
|------|------|
| Figma 프레임 | {{ $screen['figma']['frame_name'] ?? '-' }} |
| 디자인 보기 | [열기]({{ $screen['figma']['view_url'] ?? '#' }}) |
| Dev Mode | [코드/스타일 확인]({{ $screen['figma']['dev_url'] ?? '#' }}) 🔧 |
| 매핑일 | {{ $screen['figma']['mapped_at'] ? \Carbon\Carbon::parse($screen['figma']['mapped_at'])->format('Y-m-d') : '-' }} |

@if(!empty($screen['standards']['applied_layouts']))
**적용 표준 레이아웃**:
@foreach($screen['standards']['applied_layouts'] as $layout)
- {{ $layout['name'] }}@if(!empty($layout['spec']['type'])) ({{ $layout['spec']['type'] }})@endif

@endforeach
@endif

---

@empty
_매핑된 화면이 없습니다. T31 화면 매핑을 먼저 완료하세요._

@endforelse

@if(!empty($unmapped))
## ⚠️ 미매핑 화면 (별도 협의 필요)

| 화면 ID | 화면명 | 비고 |
|---------|--------|------|
@foreach($unmapped as $screen)
| {{ $screen['screen_id'] }} | {{ $screen['name'] }} | {{ $screen['reason'] }} |
@endforeach

> 위 화면들은 Figma와 연결되지 않았습니다. 개발 전 디자이너와 협의하여 디자인을 확인하세요.

@endif

---

## 디자인 시스템 참고

프로젝트의 디자인 표준을 확인하려면 아래 자료를 참고하세요.

- **Design Tokens** (색상, 타이포, 그림자): `design-tokens-*.json`
- **컴포넌트 명세서**: `components-*.json`
- **레이아웃 / 그리드**: `layouts-*.json`
- **디자인 시스템 문서**: `design-system-*.html` (브라우저로 열기)
- **일관성 검수 결과**: `design-review-*.json`

## Figma Dev Mode 사용 가이드

1. 위 표의 **Dev Mode** 링크를 클릭합니다.
2. Figma 우측 패널에서 **Inspect** 탭 확인 (코드, 스타일, 에셋 추출 가능).
3. 프로젝트 스택에 맞게 코드를 활용합니다.
4. 디자인 토큰 값은 `design-tokens-*.json`과 대조하여 일관성을 유지하세요.

---

_본 문서는 Phase 3 디자인 단계의 공식 핸드오프 자료입니다._
_Phase 4 (개발 준비 및 개발) 작업 시 이 문서를 기반으로 진행하세요._
