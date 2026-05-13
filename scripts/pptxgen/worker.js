'use strict';

const PptxGenJS = require('pptxgenjs');
const fs        = require('fs');

// ── CLI ───────────────────────────────────────────────────────────
const [,, inputFile, outputFile] = process.argv;
if (!inputFile || !outputFile) {
    process.stderr.write('Usage: node worker.js <input.json> <output.pptx>\n');
    process.exit(1);
}

let data;
try {
    data = JSON.parse(fs.readFileSync(inputFile, 'utf8'));
} catch (e) {
    process.stderr.write('JSON parse error: ' + e.message + '\n');
    process.exit(1);
}

// ── Theme ─────────────────────────────────────────────────────────
const T = {
    navy:   '0F172A',
    dark:   '1E293B',
    indigo: '312E81',
    ind2:   '4338CA',
    ind3:   '6366F1',
    cyan:   '0891B2',
    cyan2:  '22D3EE',
    teal:   '0D9488',
    white:  'FFFFFF',
    wdim:   'C7D2FE',
    gray:   '64748B',
    lgray:  'F1F5F9',
    card:   'F8F9FF',
    cardB:  'EEF2FF',
    txH:    '0F172A',
    txB:    '1E293B',
    txS:    '64748B',
    grn:    '059669',
    amber:  'D97706',
    red:    'DC2626',
};

const FONT = '맑은 고딕';
const W    = 10;
const H    = 5.625;

const today   = new Date().toLocaleDateString('ko-KR', { year:'numeric', month:'2-digit', day:'2-digit' });
const company = data.company || 'SupportWorks';

// ── Presentation ──────────────────────────────────────────────────
const pres   = new PptxGenJS();
pres.layout  = 'LAYOUT_WIDE';
pres.title   = data.title || '프레젠테이션';
pres.author  = 'SupportWorks AI';
pres.company = company;

// ── Helpers ───────────────────────────────────────────────────────
function rect(slide, opts) {
    slide.addShape(pres.ShapeType.rect, { line: { type: 'none' }, ...opts });
}
function roundRect(slide, opts) {
    slide.addShape(pres.ShapeType.roundRect, { rectRadius: 0.08, line: { type: 'none' }, ...opts });
}
function circle(slide, opts) {
    slide.addShape(pres.ShapeType.ellipse, { line: { type: 'none' }, ...opts });
}
function txt(slide, text, opts) {
    slide.addText(String(text ?? ''), { fontFace: FONT, wrap: true, ...opts });
}

/** 헤더 바 공통 */
function addHeader(slide, title, num) {
    // 배경 그라데이션 시뮬레이션
    rect(slide, { x: 0,    y: 0, w: W,    h: 0.78, fill: { color: T.indigo } });
    rect(slide, { x: 7.2,  y: 0, w: 2.8,  h: 0.78, fill: { color: T.ind2  } });
    // 좌측 시안 포인트 바
    rect(slide, { x: 0,    y: 0, w: 0.1,  h: 0.78, fill: { color: T.cyan2 } });
    // 슬라이드 번호 배지
    roundRect(slide, { x: 9.25, y: 0.16, w: 0.48, h: 0.44, fill: { color: T.ind3 }, line: { type: 'none' } });
    txt(slide, String(num), {
        x: 9.25, y: 0.16, w: 0.48, h: 0.44,
        fontSize: 11, bold: true, color: T.white, align: 'center', valign: 'middle',
    });
    // 슬라이드 제목
    const tlen  = (title || '').length;
    const tsize = tlen > 36 ? 15 : tlen > 28 ? 17 : tlen > 20 ? 19 : 21;
    txt(slide, title, {
        x: 0.22, y: 0.05, w: 8.8, h: 0.68,
        fontSize: tsize, bold: true, color: T.white, valign: 'middle',
    });
}

/** 하단 푸터 */
function addFooter(slide) {
    rect(slide, { x: 0, y: H - 0.24, w: W, h: 0.24, fill: { color: T.dark } });
    // 좌: 회사명
    txt(slide, company, {
        x: 0.2, y: H - 0.24, w: 4, h: 0.24,
        fontSize: 7.5, color: T.wdim, valign: 'middle',
    });
    // 우: 날짜
    txt(slide, today, {
        x: W - 2.5, y: H - 0.24, w: 2.4, h: 0.24,
        fontSize: 7.5, color: T.gray, align: 'right', valign: 'middle',
    });
}

// ─────────────────────────────────────────────────────────────────
//  SLIDE BUILDERS
// ─────────────────────────────────────────────────────────────────

/** 1. 표지 슬라이드 */
function buildTitleSlide(slide) {
    slide.background = { color: T.navy };

    // 우측 사선 블록 (세련된 느낌)
    rect(slide, { x: 5.8, y: 0, w: 4.2, h: H, fill: { color: T.indigo } });
    rect(slide, { x: 7.5, y: 0, w: 2.5, h: H, fill: { color: T.ind2  } });
    // 좌상단 시안 라인
    rect(slide, { x: 0,   y: 0, w: W,   h: 0.07, fill: { color: T.cyan2 } });
    // 좌측 강조 바
    rect(slide, { x: 0,   y: 0, w: 0.1, h: H,    fill: { color: T.ind3  } });

    // 원형 장식
    circle(slide, { x: 6.2,  y: 0.5,  w: 3.2, h: 3.2, fill: { color: '1A1456' } });
    circle(slide, { x: 7.0,  y: 1.2,  w: 1.8, h: 1.8, fill: { color: T.navy   } });
    circle(slide, { x: 7.35, y: 1.52, w: 1.1, h: 1.1, fill: { color: '2D237A'  } });

    // 태그 라벨
    roundRect(slide, { x: 0.28, y: 1.0, w: 1.6, h: 0.28, fill: { color: T.ind3 }, line: { type: 'none' } });
    txt(slide, 'PRESENTATION', {
        x: 0.28, y: 1.0, w: 1.6, h: 0.28,
        fontSize: 7.5, bold: true, color: T.white, align: 'center', valign: 'middle', charSpacing: 2,
    });

    // 메인 타이틀
    const title = data.title || '프레젠테이션';
    const tsize = title.length > 24 ? 26 : title.length > 18 ? 30 : 35;
    txt(slide, title, {
        x: 0.28, y: 1.38, w: 5.3, h: 1.9,
        fontSize: tsize, bold: true, color: T.white, valign: 'top',
    });

    // 구분선
    rect(slide, { x: 0.28, y: 3.34, w: 2.2, h: 0.06, fill: { color: T.cyan2 } });

    // 서브타이틀
    const subtitle = data.subtitle || today;
    txt(slide, subtitle, {
        x: 0.28, y: 3.46, w: 5.3, h: 0.46,
        fontSize: 13, color: T.wdim,
    });

    // 회사명
    txt(slide, company, {
        x: 0.28, y: 4.04, w: 5.3, h: 0.36,
        fontSize: 11, color: '6D63BE',
    });

    // 점 장식
    [0,1,2,3,4].forEach(i => {
        circle(slide, {
            x: 0.3 + i * 0.22, y: 5.1, w: 0.12, h: 0.12,
            fill: { color: i === 0 ? T.cyan2 : '2E2870' },
        });
    });
}

/** 2. 콘텐츠 슬라이드 — 카드 레이아웃 */
function buildContentSlide(slide, d, num) {
    slide.background = { color: T.lgray };
    addHeader(slide, d.title || '', num);
    addFooter(slide);

    const CONTENT_TOP = 0.86;
    const CONTENT_H   = H - CONTENT_TOP - 0.32;
    const bullets = d.bullets || [];
    if (bullets.length === 0) return;

    const count = Math.min(bullets.length, 9);
    const items = bullets.slice(0, count);

    // 카드 배경
    rect(slide, {
        x: 0.18, y: CONTENT_TOP,
        w: W - 0.26, h: CONTENT_H,
        fill: { color: T.white },
        line: { color: 'E2E8F0', pt: 0.8 },
    });

    // 좌측 accent 바
    rect(slide, { x: 0.18, y: CONTENT_TOP, w: 0.07, h: CONTENT_H, fill: { color: T.ind2 } });

    // 각 bullet 아이템 개별 배치
    const itemH = CONTENT_H / count;
    items.forEach((b, i) => {
        const text  = (typeof b === 'string' ? b : (b.text ?? '')).trim();
        const level = typeof b === 'object' ? (b.level ?? 0) : 0;
        const bold  = typeof b === 'object' && !!b.bold;

        const iy = CONTENT_TOP + i * itemH;

        // 구분선 (첫 번째 제외)
        if (i > 0) {
            rect(slide, {
                x: 0.32, y: iy,
                w: W - 0.5, h: 0.006,
                fill: { color: 'E8EEFF' },
            });
        }

        if (level === 0) {
            // 마커 배지
            const markerColor = bold ? T.ind2 : T.ind3;
            roundRect(slide, {
                x: 0.32, y: iy + itemH * 0.22,
                w: 0.2, h: itemH * 0.55,
                fill: { color: markerColor }, line: { type: 'none' },
            });
            txt(slide, bold ? '▶' : '●', {
                x: 0.32, y: iy + itemH * 0.22,
                w: 0.2, h: itemH * 0.55,
                fontSize: 6, color: T.white, align: 'center', valign: 'middle',
            });
        } else {
            // 서브 아이템 들여쓰기 마커
            rect(slide, {
                x: 0.58, y: iy + itemH * 0.42,
                w: 0.1, h: 0.05,
                fill: { color: T.cyan },
            });
        }

        const fsz = count <= 4 ? 14 : count <= 6 ? 12.5 : count <= 8 ? 11.5 : 10.5;
        const textX = level === 0 ? 0.62 : 0.76;

        txt(slide, text, {
            x: textX, y: iy + 0.02,
            w: W - textX - 0.2, h: itemH - 0.04,
            fontSize: fsz,
            bold,
            color: bold ? T.txH : T.txB,
            valign: 'middle',
        });
    });
}

/** 3. 2컬럼 슬라이드 */
function buildTwoColumnSlide(slide, d, num) {
    slide.background = { color: T.lgray };
    addHeader(slide, d.title || '', num);
    addFooter(slide);

    const GUTTER = 0.22;
    const COL_W  = (W - GUTTER * 3) / 2;
    const x1     = GUTTER;
    const x2     = GUTTER * 2 + COL_W;
    const Y0     = 0.88;
    const COL_H  = H - Y0 - 0.32;
    const HDR_H  = 0.42;

    // 카드 배경
    rect(slide, { x: x1, y: Y0, w: COL_W, h: COL_H, fill: { color: T.white }, line: { color: 'DDD6FE', pt: 0.8 } });
    rect(slide, { x: x2, y: Y0, w: COL_W, h: COL_H, fill: { color: T.white }, line: { color: 'A5F3FC', pt: 0.8 } });

    // 컬럼 헤더
    const ltitle = d.left_title  || '';
    const rtitle = d.right_title || '';
    if (ltitle) {
        rect(slide, { x: x1, y: Y0, w: COL_W, h: HDR_H, fill: { color: T.ind2 } });
        txt(slide, ltitle, { x: x1+0.12, y: Y0, w: COL_W-0.18, h: HDR_H,
            fontSize: 12.5, bold: true, color: T.white, valign: 'middle' });
    }
    if (rtitle) {
        rect(slide, { x: x2, y: Y0, w: COL_W, h: HDR_H, fill: { color: T.cyan } });
        txt(slide, rtitle, { x: x2+0.12, y: Y0, w: COL_W-0.18, h: HDR_H,
            fontSize: 12.5, bold: true, color: T.white, valign: 'middle' });
    }

    const leftItems  = (d.left_items  || []).slice(0, 8);
    const rightItems = (d.right_items || []).slice(0, 8);
    const colFsz     = Math.max(10.5, 13 - Math.floor(Math.max(leftItems.length, rightItems.length) / 3));
    const itemH      = (COL_H - HDR_H) / Math.max(leftItems.length, rightItems.length, 1);

    function renderColItems(items, xOff, color) {
        items.forEach((item, i) => {
            const iy = Y0 + HDR_H + i * itemH;
            if (i > 0) rect(slide, { x: xOff, y: iy, w: COL_W, h: 0.005, fill: { color: 'EEF2FF' } });
            circle(slide, { x: xOff+0.1, y: iy+itemH*0.35, w: 0.1, h: 0.1, fill: { color } });
            txt(slide, String(item), {
                x: xOff+0.26, y: iy, w: COL_W-0.32, h: itemH,
                fontSize: colFsz, color: T.txB, valign: 'middle',
            });
        });
    }
    renderColItems(leftItems,  x1, T.ind3);
    renderColItems(rightItems, x2, T.cyan);

    rect(slide, { x: 0, y: 0.78, w: 0.07, h: H - 0.78 - 0.24, fill: { color: T.ind2 } });
}

/** 4. 섹션 구분 슬라이드 */
function buildSectionSlide(slide, d, num) {
    slide.background = { color: T.navy };

    // 우측 블록
    rect(slide, { x: 5.2, y: 0, w: 4.8, h: H, fill: { color: T.indigo } });
    rect(slide, { x: 7.8, y: 0, w: 2.2, h: H, fill: { color: T.ind2  } });

    rect(slide, { x: 0, y: 0, w: W, h: 0.07, fill: { color: T.cyan2 } });
    rect(slide, { x: 0, y: 0, w: 0.1, h: H,  fill: { color: T.ind3  } });

    // 배경 대형 숫자
    txt(slide, String(num).padStart(2, '0'), {
        x: 5.0, y: 0.2, w: 4.8, h: 5.0,
        fontSize: 160, bold: true, color: '1E1968',
        align: 'center', valign: 'middle',
    });

    // SECTION 태그
    roundRect(slide, { x: 0.3, y: 1.4, w: 1.1, h: 0.28, fill: { color: T.ind3 }, line: { type: 'none' } });
    txt(slide, 'SECTION', {
        x: 0.3, y: 1.4, w: 1.1, h: 0.28,
        fontSize: 8, bold: true, color: T.white,
        align: 'center', valign: 'middle', charSpacing: 2,
    });

    rect(slide, { x: 0.3, y: 1.78, w: 2.6, h: 0.06, fill: { color: T.cyan2 } });

    const tlen = (d.title || '').length;
    txt(slide, d.title || '', {
        x: 0.3, y: 1.88, w: 5.0, h: 1.9,
        fontSize: tlen > 22 ? 24 : tlen > 16 ? 28 : 33,
        bold: true, color: T.white, valign: 'top',
    });

    if (d.subtitle) {
        txt(slide, d.subtitle, {
            x: 0.3, y: 3.9, w: 5.0, h: 0.5,
            fontSize: 13, color: T.wdim,
        });
    }
}

/** 5. 마무리 슬라이드 */
function buildClosingSlide(slide, d) {
    slide.background = { color: T.navy };

    rect(slide, { x: 0, y: 0, w: W, h: 0.07, fill: { color: T.cyan2 } });
    rect(slide, { x: 0, y: H - 0.07, w: W, h: 0.07, fill: { color: T.ind3 } });

    // 배경 원형 장식
    circle(slide, { x: 3.0,  y: 0.6,  w: 3.8, h: 3.8, fill: { color: '130F36' } });
    circle(slide, { x: 3.55, y: 1.1,  w: 2.7, h: 2.7, fill: { color: T.indigo } });
    circle(slide, { x: 3.95, y: 1.45, w: 1.9, h: 1.9, fill: { color: T.ind2   } });
    circle(slide, { x: 4.25, y: 1.72, w: 1.3, h: 1.3, fill: { color: T.ind3   } });

    const msg   = d.message || '감사합니다';
    const msize = msg.length > 12 ? 34 : 42;
    txt(slide, msg, {
        x: 0.5, y: 1.35, w: 9.0, h: 1.8,
        fontSize: msize, bold: true, color: T.white,
        align: 'center', valign: 'middle',
    });

    rect(slide, { x: 3.5, y: 3.28, w: 3.0, h: 0.06, fill: { color: T.cyan2 } });

    txt(slide, company, {
        x: 0, y: 3.42, w: W, h: 0.44,
        fontSize: 14, color: T.wdim, align: 'center',
    });
    txt(slide, today, {
        x: 0, y: 3.9, w: W, h: 0.38,
        fontSize: 11, color: '3D3780', align: 'center',
    });

    [0,1,2,3,4,5].forEach(i => {
        circle(slide, {
            x: 4.43 + i * 0.22, y: 5.1, w: 0.13, h: 0.13,
            fill: { color: i === 2 ? T.cyan2 : i === 3 ? T.ind3 : '2E2870' },
        });
    });
}

/** 6. 하이라이트 슬라이드 (KPI / 핵심 지표) */
function buildHighlightSlide(slide, d, num) {
    slide.background = { color: T.lgray };
    addHeader(slide, d.title || '핵심 지표', num);
    addFooter(slide);

    const items = (d.items || []).slice(0, 4);
    if (items.length === 0) return;

    const count  = items.length;
    const cardW  = (W - 0.3 * (count + 1)) / count;
    const Y0     = 0.96;
    const CARD_H = H - Y0 - 0.38;
    const COLORS = [T.ind2, T.cyan, T.teal, T.indigo];

    items.forEach((item, i) => {
        const cx    = 0.3 + i * (cardW + 0.3);
        const color = COLORS[i % COLORS.length];

        // 카드
        rect(slide, { x: cx, y: Y0, w: cardW, h: CARD_H, fill: { color: T.white }, line: { color: 'E2E8F0', pt: 0.8 } });
        // 상단 색상 바
        rect(slide, { x: cx, y: Y0, w: cardW, h: 0.1, fill: { color } });

        // 수치 (큰 폰트)
        const value = String(item.value || item.number || '');
        const vsize = value.length > 8 ? 26 : value.length > 5 ? 32 : 40;
        txt(slide, value, {
            x: cx + 0.1, y: Y0 + 0.22, w: cardW - 0.2, h: 1.3,
            fontSize: vsize, bold: true, color,
            align: 'center', valign: 'middle',
        });

        // 레이블
        const label = item.label || item.title || '';
        txt(slide, label, {
            x: cx + 0.1, y: Y0 + 1.55, w: cardW - 0.2, h: 0.48,
            fontSize: 12.5, bold: true, color: T.txH,
            align: 'center',
        });

        // 설명
        if (item.desc || item.description) {
            txt(slide, item.desc || item.description, {
                x: cx + 0.1, y: Y0 + 2.06, w: cardW - 0.2, h: CARD_H - 2.2,
                fontSize: 10.5, color: T.txS,
                align: 'center', valign: 'top',
            });
        }
    });
}

/** 7. 테이블 슬라이드 */
function buildTableSlide(slide, d, num) {
    slide.background = { color: T.lgray };
    addHeader(slide, d.title || '표', num);
    addFooter(slide);

    const headers = d.headers || [];
    const rows    = d.rows    || [];
    if (headers.length === 0 && rows.length === 0) return;

    const colCount = Math.max(headers.length, (rows[0] || []).length, 1);
    const Y0       = 0.88;
    const AREA_H   = H - Y0 - 0.32;
    const rowCount = rows.length + (headers.length > 0 ? 1 : 0);
    const rowH     = Math.min(AREA_H / Math.max(rowCount, 1), 0.52);
    const colW     = (W - 0.26) / colCount;
    let   curY     = Y0;

    // 헤더 행
    if (headers.length > 0) {
        rect(slide, { x: 0.13, y: curY, w: W - 0.26, h: rowH, fill: { color: T.ind2 } });
        headers.forEach((h, ci) => {
            txt(slide, String(h), {
                x: 0.13 + ci * colW + 0.06, y: curY, w: colW - 0.08, h: rowH,
                fontSize: 11, bold: true, color: T.white, valign: 'middle',
            });
        });
        curY += rowH;
    }

    // 데이터 행
    rows.forEach((row, ri) => {
        const even = ri % 2 === 0;
        rect(slide, {
            x: 0.13, y: curY, w: W - 0.26, h: rowH,
            fill: { color: even ? T.card : T.white },
            line: { color: 'DDD6FE', pt: 0.4 },
        });
        (row || []).forEach((cell, ci) => {
            txt(slide, String(cell ?? ''), {
                x: 0.13 + ci * colW + 0.08, y: curY, w: colW - 0.12, h: rowH,
                fontSize: 10.5, color: T.txB, valign: 'middle',
            });
        });
        curY += rowH;
    });
}

// ─────────────────────────────────────────────────────────────────
//  RENDER
// ─────────────────────────────────────────────────────────────────

const slides = data.slides || [];
let contentNum = 1;

slides.forEach(d => {
    const slide = pres.addSlide();
    switch (d.type) {
        case 'title':       buildTitleSlide(slide);                      break;
        case 'section':     buildSectionSlide(slide, d, contentNum++);   break;
        case 'two_column':  buildTwoColumnSlide(slide, d, contentNum++); break;
        case 'highlight':   buildHighlightSlide(slide, d, contentNum++); break;
        case 'table':       buildTableSlide(slide, d, contentNum++);     break;
        case 'closing':     buildClosingSlide(slide, d);                  break;
        default:            buildContentSlide(slide, d, contentNum++);   break;
    }
});

pres.writeFile({ fileName: outputFile })
    .then(() => { process.stdout.write(outputFile + '\n'); process.exit(0); })
    .catch(e  => { process.stderr.write('error: ' + e.message + '\n'); process.exit(1); });
