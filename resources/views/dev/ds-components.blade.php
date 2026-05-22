@extends('layouts.app')

@section('title', 'Design System — Components')

@section('header-actions')@endsection

@section('content')
@php $hex = '#0f86ef'; @endphp
<style>
    /* 가이드 페이지 전용 — 디자인 시스템 클래스(.ds-*) 데모용 보조 스타일 */
    .ds-doc-section { background:#fff; border:1px solid var(--color-border-default); border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; }
    .ds-doc-h2 { margin:0 0 4px; font-size:18px; font-weight:700; color:var(--color-text-primary); }
    .ds-doc-sub { margin:0 0 18px; font-size:13px; color:var(--color-text-tertiary); }
    .ds-doc-row { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:12px; }
    .ds-doc-code { display:inline-block; padding:2px 6px; background:#f3f4f6; border-radius:4px; font-family:ui-monospace,'SF Mono',Consolas,monospace; font-size:12px; color:#374151; }
    .ds-doc-label { font-size:12px; font-weight:600; color:var(--color-text-tertiary); width:120px; flex-shrink:0; }
</style>

<div style="margin-bottom:16px;display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="min-width:0;">
        <div style="font-size:19px;font-weight:700;color:#1e1b2e;">Design System Components</div>
        <div style="font-size:13px;color:#94a3b8;margin-top:3px;">resources/assets/css/components.css — Phase 2 표준 컴포넌트 클래스(<span class="ds-doc-code">.ds-*</span>)</div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;font-size:12px;color:var(--color-text-tertiary);">
        현재 액센트: <code class="ds-doc-code" id="ds-current-accent">{{ $hex }}</code>
    </div>
</div>

{{-- Buttons ─────────────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Button</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-btn</span> + size <span class="ds-doc-code">--xs / --s / --m / --l</span> + variant <span class="ds-doc-code">--primary / --brand / --brand-text / --accent / --danger / --inverse</span></p>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Sizes</span>
        <button class="ds-btn ds-btn--xs">XS Button</button>
        <button class="ds-btn ds-btn--s">Small Button</button>
        <button class="ds-btn ds-btn--m">Medium Button</button>
        <button class="ds-btn ds-btn--l">Large Button</button>
    </div>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Variants</span>
        <button class="ds-btn ds-btn--m">Default</button>
        <button class="ds-btn ds-btn--primary ds-btn--m">Primary</button>
        <button class="ds-btn ds-btn--brand ds-btn--m">Brand</button>
        <button class="ds-btn ds-btn--brand-text ds-btn--m">Brand Text</button>
        <button class="ds-btn ds-btn--accent ds-btn--m">Accent</button>
        <button class="ds-btn ds-btn--danger ds-btn--m">Danger</button>
        <button class="ds-btn ds-btn--inverse ds-btn--m">Inverse</button>
    </div>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Disabled</span>
        <button class="ds-btn ds-btn--m" disabled>Default</button>
        <button class="ds-btn ds-btn--primary ds-btn--m" disabled>Primary</button>
        <button class="ds-btn ds-btn--danger ds-btn--m" disabled>Danger</button>
    </div>
</section>

{{-- Input & Textarea ─────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Input · Textarea</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-input</span> sizes <span class="ds-doc-code">--xs / --s</span>, <span class="ds-doc-code">.ds-input--num</span> 으로 우측 정렬</p>

    <div class="ds-doc-row" style="align-items:flex-start;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <label class="ds-label ds-label--xs">Small</label>
            <input type="text" class="ds-input ds-input--s" placeholder="이름 입력" style="width:180px;">
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <label class="ds-label ds-label--xs">XS</label>
            <input type="text" class="ds-input ds-input--xs" placeholder="검색어" style="width:180px;">
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <label class="ds-label ds-label--xs">금액</label>
            <input type="text" class="ds-input ds-input--s ds-input--num" value="1,234,567" style="width:180px;">
        </div>
    </div>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Textarea</span>
        <textarea class="ds-textarea" rows="3" placeholder="여러 줄 입력 가능" style="width:320px;"></textarea>
    </div>
</section>

{{-- Select · Checkbox · Radio ─────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Checkbox · Radio</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-checkbox</span>, <span class="ds-doc-code">.ds-radio</span></p>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Checkbox</span>
        <label class="ds-checkbox"><input type="checkbox" class="ds-checkbox__control"><span class="ds-checkbox__box"></span> 동의합니다</label>
        <label class="ds-checkbox"><input type="checkbox" class="ds-checkbox__control" checked><span class="ds-checkbox__box"></span> 약관 확인</label>
        <label class="ds-checkbox"><input type="checkbox" class="ds-checkbox__control" disabled><span class="ds-checkbox__box"></span> 비활성</label>
    </div>

    <div class="ds-doc-row">
        <span class="ds-doc-label">Radio</span>
        <label class="ds-radio"><input type="radio" name="ex-radio" class="ds-radio__control" checked><span class="ds-radio__box"></span> 옵션 A</label>
        <label class="ds-radio"><input type="radio" name="ex-radio" class="ds-radio__control"><span class="ds-radio__box"></span> 옵션 B</label>
        <label class="ds-radio"><input type="radio" name="ex-radio" class="ds-radio__control" disabled><span class="ds-radio__box"></span> 비활성</label>
    </div>
</section>

{{-- Badge ────────────────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Badge</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-badge</span> + <span class="ds-doc-code">--accent / --danger / --info / --neutral / --success</span></p>
    <div class="ds-doc-row">
        <span class="ds-badge">Default</span>
        <span class="ds-badge ds-badge--accent">Accent</span>
        <span class="ds-badge ds-badge--danger">Danger</span>
        <span class="ds-badge ds-badge--info">Info</span>
        <span class="ds-badge ds-badge--neutral">Neutral</span>
        <span class="ds-badge ds-badge--success">Success</span>
    </div>
</section>

{{-- Breadcrumb ───────────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Breadcrumb</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-breadcrumb</span>, <span class="ds-doc-code">.ds-breadcrumb__list</span>, <span class="ds-doc-code">.ds-breadcrumb__item</span></p>
    <nav class="ds-breadcrumb">
        <ol class="ds-breadcrumb__list">
            <li class="ds-breadcrumb__item"><a href="#">홈</a></li>
            <li class="ds-breadcrumb__separator">›</li>
            <li class="ds-breadcrumb__item"><a href="#">프로젝트</a></li>
            <li class="ds-breadcrumb__separator">›</li>
            <li class="ds-breadcrumb__item ds-breadcrumb__item--active">SK Hynix</li>
        </ol>
    </nav>
</section>

{{-- Toast / Alert ────────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Toast</h2>
    <p class="ds-doc-sub"><span class="ds-doc-code">.ds-toast</span> + <span class="ds-doc-code">--info / --success / --warning / --error</span></p>
    <div style="display:flex;flex-direction:column;gap:8px;max-width:480px;">
        <div class="ds-toast ds-toast--info">정보 메시지입니다.</div>
        <div class="ds-toast ds-toast--success">성공적으로 저장되었습니다.</div>
        <div class="ds-toast ds-toast--warning">주의가 필요한 항목이 있습니다.</div>
        <div class="ds-toast ds-toast--error">오류가 발생했습니다.</div>
    </div>
</section>

{{-- Spacing 표준 ───────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">Spacing — 카드 간격 표준</h2>
    <p class="ds-doc-sub">모든 페이지의 카드·요소 간격은 아래 4단계만 사용합니다. 토큰: <span class="ds-doc-code">var(--space-1)</span> / <span class="ds-doc-code">--space-2</span> / <span class="ds-doc-code">--space-3</span></p>

    <div style="display:flex;flex-direction:column;gap:16px;">
        {{-- 시각화: 섹션 간 12px --}}
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--color-text-tertiary);margin-bottom:6px;">① 섹션 간 — <code class="ds-doc-code">12px</code> <span class="ds-doc-code">var(--space-3)</span></div>
            <div style="display:flex;flex-direction:column;gap:var(--space-3);">
                <div style="background:var(--color-bg-muted);padding:14px;border-radius:var(--radius-md);font-size:13px;">큰 카드 섹션 — Tasks</div>
                <div style="background:var(--color-bg-muted);padding:14px;border-radius:var(--radius-md);font-size:13px;">큰 카드 섹션 — KPI</div>
                <div style="background:var(--color-bg-muted);padding:14px;border-radius:var(--radius-md);font-size:13px;">큰 카드 섹션 — 회의록</div>
            </div>
        </div>

        {{-- 시각화: 카드 그리드 8px --}}
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--color-text-tertiary);margin-bottom:6px;">② 카드 그리드 내 — <code class="ds-doc-code">8px</code> <span class="ds-doc-code">var(--space-2)</span></div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-2);">
                <div style="background:var(--t50);padding:14px;border-radius:var(--radius-md);font-size:12px;text-align:center;">KPI 1</div>
                <div style="background:var(--t50);padding:14px;border-radius:var(--radius-md);font-size:12px;text-align:center;">KPI 2</div>
                <div style="background:var(--t50);padding:14px;border-radius:var(--radius-md);font-size:12px;text-align:center;">KPI 3</div>
                <div style="background:var(--t50);padding:14px;border-radius:var(--radius-md);font-size:12px;text-align:center;">KPI 4</div>
            </div>
        </div>

        {{-- 시각화: 카드 내부 4px --}}
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--color-text-tertiary);margin-bottom:6px;">③ 카드 내부 요소 간 — <code class="ds-doc-code">4px</code> <span class="ds-doc-code">var(--space-1)</span></div>
            <div style="background:var(--color-bg-base);border:1px solid var(--color-border-default);padding:14px;border-radius:var(--radius-md);max-width:280px;display:flex;flex-direction:column;gap:var(--space-1);">
                <div style="font-size:12px;color:var(--color-text-tertiary);">제목 라벨</div>
                <div style="font-size:18px;font-weight:700;color:var(--color-text-primary);">42 건</div>
                <div style="font-size:11px;color:var(--color-text-tertiary);">전월 대비 +12%</div>
            </div>
        </div>

        {{-- 시각화: 배지/칩 4px --}}
        <div>
            <div style="font-size:12px;font-weight:600;color:var(--color-text-tertiary);margin-bottom:6px;">④ 인접 작은 요소 (배지·칩·아이콘+텍스트) — <code class="ds-doc-code">4px</code> <span class="ds-doc-code">var(--space-1)</span></div>
            <div style="display:flex;align-items:center;gap:var(--space-1);">
                <span class="ds-badge ds-badge--success">완료</span>
                <span class="ds-badge ds-badge--info">검토</span>
                <span class="ds-badge ds-badge--danger">긴급</span>
                <span class="ds-badge ds-badge--neutral">대기</span>
            </div>
        </div>
    </div>
</section>

{{-- Usage Note ───────────────────────────────────────────── --}}
<section class="ds-doc-section">
    <h2 class="ds-doc-h2">사용 방식</h2>
    <p class="ds-doc-sub">신규 페이지 작성 시 위 클래스를 직접 사용하세요. 색은 현재 액센트(<span class="ds-doc-code" id="ds-current-accent-2">{{ $hex }}</span>)에 자동 추종됩니다.</p>
    <pre style="background:#f8fafc;border:1px solid var(--color-border-default);border-radius:8px;padding:12px;font-size:12.5px;font-family:ui-monospace,'SF Mono',Consolas,monospace;color:#374151;overflow-x:auto;margin:0;line-height:1.7;"><code>&lt;button class="ds-btn ds-btn--primary ds-btn--m"&gt;저장&lt;/button&gt;
&lt;input type="text" class="ds-input ds-input--s" placeholder="검색"&gt;
&lt;span class="ds-badge ds-badge--success"&gt;완료&lt;/span&gt;
&lt;div class="ds-toast ds-toast--info"&gt;알림 메시지&lt;/div&gt;</code></pre>
</section>

<script>
(function() {
    function paintHex() {
        const hex = getComputedStyle(document.documentElement).getPropertyValue('--color-theme-active').trim().toUpperCase();
        document.querySelectorAll('#ds-current-accent, #ds-current-accent-2').forEach(el => el.textContent = hex);
    }
    paintHex();
    // accent 변경 시 즉시 반영
    new MutationObserver(paintHex).observe(document.documentElement, { attributes: true, attributeFilter: ['data-accent', 'style'] });
})();
</script>
@endsection
