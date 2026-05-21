# TAP 테마 v1 — HTML 생성 규약

## 절대 규칙
이 테마는 **이미 정의된 컴포넌트 CSS · JS 라이브러리** 를 사용합니다. 새로운 컴포넌트 스타일을 정의하지 말고, 아래 자산을 **상대 경로로 참조**한 뒤 정의된 클래스명을 그대로 사용하세요.

## HTML 헤더에 반드시 포함

```html
<link rel="stylesheet" href="./assets/theme/css/components.css">
<script defer src="./assets/theme/js/screens.views.js"></script>
<script defer src="./assets/theme/js/spis-datatable.js"></script>
```

- 절대 경로 / CDN 사용 금지. 상대 경로(`./assets/theme/...`)만 허용.
- 추가 외부 CSS/JS 가져오기 금지 (테마 자산만 사용).

## 디자인 토큰 (CSS 변수)
`components.css` 상단에서 정의된 CSS 변수만 사용:
- 색상: `--color-bg-base`, `--color-bg-brand`, `--color-bg-brand-hover`, `--color-bg-brand-subtle`, `--color-text-primary`, `--color-text-inverse`, `--color-text-brand`, `--color-text-accent`, `--color-border-default`, `--color-border-focus`
- 간격: `--space-1` ~ `--space-12`
- 폰트: `--font-family-base`, `--font-size-caption`, `--font-size-body-13`, `--font-size-body-14`, `--font-weight-bold`, `--line-height-body`
- 라운드: `--radius-md`, `--radius-lg`
- 전환: `--transition-fast`

**메인 색상** (옵션의 `main_color`) 은 `--color-bg-brand` 를 inline `<style>` 로 override 하세요:
```html
<style>:root { --color-bg-brand: #...; --color-bg-brand-hover: #...; }</style>
```

## 컴포넌트 클래스 (BEM)

### 버튼 (`.btn`)
- 크기 modifier: `.btn--xs`, `.btn--s`, `.btn--m`, `.btn--l`
- 색상 modifier: `.btn--primary`, `.btn--brand`, `.btn--brand-text`, `.btn--accent`, `.btn--danger`, `.btn--inverse`
- 상태: `.btn--loading`
- 특수: `.btn--register`, `.btn--save-all`
- 내부 아이콘: `.btn__icon`

### 배지 (`.badge`)
- `.badge--success`, `.badge--danger`, `.badge--info`, `.badge--accent`, `.badge--neutral`

### 체크박스 (`.checkbox`)
- 변형: `.checkbox--01`, `.checkbox--02`, `.checkbox--xs`
- 그룹: `.checkbox-group`, `.checkbox-group--horizontal`
- 내부: `.checkbox__box`, `.checkbox__control`

### 데이터테이블 (`.datatable`)
- 래퍼: `.datatable-wrap`
- variant: `.datatable--compact`
- 헤더 셀: `.datatable__head-cell`, `.datatable__head-cell--sortable`, `.datatable__head-cell--asc`, `.datatable__head-cell--desc`, `.datatable__head-cell--editable`, `.datatable__head-cell--check`, `.datatable__head-cell--left`, `.datatable__head-cell--right`, `.datatable__head-inner`, `.datatable__head-label`, `.datatable__head-edit-icon`
- 본문 셀: `.datatable__cell`, `.datatable__cell--check`, `.datatable__cell--edit`, `.datatable__cell--left`, `.datatable__cell--right`
- 행: `.datatable__row`, `.datatable__row--total`

### 아코디언 (`.accordion`)
- `.accordion-wrap`, `.accordion-wrap--detail`, `.accordion--detail`
- 내부: `.accordion__header`, `.accordion__body`

### 브레드크럼 (`.breadcrumb`)
- `.breadcrumb__list`, `.breadcrumb__item`, `.breadcrumb__item--active`, `.breadcrumb__separator`

### 탭 (`.app-tabs`)

### 대시보드 (`.dashboard-grid`, `.dashboard-panel`)
- `.dashboard-panel__head`, `.dashboard-panel__toolbar`, `.dashboard-chart-placeholder`

### 데이트피커 (`.datepicker`, `.datepicker--range`)

### 커스텀 (`.cus-*`)
- `.cus-note`, `.cus-note__warn` — 노트/경고 박스
- `.cus-sms-layout`, `.cus-sms-layout__composer`, `.cus-sms-layout__list` — SMS 레이아웃
- `.cus-toolbar`, `.cus-toolbar__right` — 툴바
- `.cus-check-cell` — 셀 안 체크박스

### 컴포넌트 보조
- `.comp__field-row` — 폼 row
- `.comp__icon-ph` — 아이콘 placeholder
- `.button-area` — 버튼 그룹

## 생성 가이드라인
1. **컴포넌트 우선**: 위 클래스 목록에 있는 컴포넌트는 그 클래스로 마크업합니다. Tailwind 유틸리티는 보조적으로만 사용.
2. **새 클래스 정의 금지**: 인라인 `<style>` 은 메인 색상 override 외에는 사용하지 않습니다. 추가 스타일이 필요하면 Tailwind 유틸리티 또는 인라인 `style="..."` 사용.
3. **JS 호출**: `screens.views.js` 가 일반 화면 동작을, `spis-datatable.js` 가 데이터테이블 동작을 담당합니다. 이미 정의된 hook 이 있다면 그에 맞춰 마크업합니다. (예: `data-*` 속성은 라이브러리가 요구하는 형식 그대로.)
4. **시맨틱**: `<main>`, `<header>`, `<nav>`, `<section>` 등 시맨틱 태그를 적극 사용합니다.
5. **검수 속성 금지**: `data-highlight`, `id="hover-target"` 등 검수용 속성 절대 주입 금지 (BasePromptBuilder system prompt 와 동일).
