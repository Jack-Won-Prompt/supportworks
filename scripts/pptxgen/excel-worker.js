'use strict';

const XLSX = require('xlsx');
const fs   = require('fs');

const [,, inputFile, outputFile] = process.argv;
if (!inputFile || !outputFile) {
    process.stderr.write('Usage: node excel-worker.js <input.json> <output.xlsx>\n');
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
const INDIGO = '312E81';
const IND2   = '4F46E5';

function hdrStyle() {
    return {
        font:      { name: '맑은 고딕', sz: 11, bold: true, color: { rgb: 'FFFFFF' } },
        fill:      { patternType: 'solid', fgColor: { rgb: IND2 } },
        alignment: { vertical: 'center', horizontal: 'center', wrapText: false },
        border: {
            top:    { style: 'thin', color: { rgb: INDIGO } },
            bottom: { style: 'thin', color: { rgb: INDIGO } },
            left:   { style: 'thin', color: { rgb: INDIGO } },
            right:  { style: 'thin', color: { rgb: INDIGO } },
        },
    };
}

function dataStyle(even, isNum) {
    return {
        font:      { name: '맑은 고딕', sz: 10, color: { rgb: '374151' } },
        fill:      { patternType: 'solid', fgColor: { rgb: even ? 'F8F7FF' : 'FFFFFF' } },
        alignment: {
            vertical: 'center',
            horizontal: isNum ? 'right' : 'left',
            indent: isNum ? 0 : 1,
            wrapText: !isNum,
        },
        border: {
            bottom: { style: 'hair', color: { rgb: 'DDD6FE' } },
            right:  { style: 'hair', color: { rgb: 'DDD6FE' } },
        },
    };
}

function titleStyle() {
    return {
        font:      { name: '맑은 고딕', sz: 15, bold: true, color: { rgb: 'FFFFFF' } },
        fill:      { patternType: 'solid', fgColor: { rgb: INDIGO } },
        alignment: { vertical: 'middle', horizontal: 'left', indent: 1 },
    };
}

function subStyle() {
    return {
        font:      { name: '맑은 고딕', sz: 9, color: { rgb: '8880D0' } },
        fill:      { patternType: 'solid', fgColor: { rgb: '16123A' } },
        alignment: { vertical: 'middle', horizontal: 'left', indent: 1 },
    };
}

function sumStyle(isNum) {
    return {
        font:      { name: '맑은 고딕', sz: 10, bold: true, color: { rgb: 'FFFFFF' } },
        fill:      { patternType: 'solid', fgColor: { rgb: INDIGO } },
        alignment: { vertical: 'middle', horizontal: isNum ? 'right' : 'center' },
    };
}

// ── Build workbook ────────────────────────────────────────────────

const wb = XLSX.utils.book_new();
wb.Props = { Title: data.title || '문서', Author: 'SupportWorks AI' };

const sheets = data.sheets || [];

for (const sheetDef of sheets) {
    const ws  = {};
    const ref = { minC: 0, maxC: 0, minR: 0, maxR: 0 };
    let row          = 0;
    let headerRowIdx = -1;

    const headers   = sheetDef.headers    || [];
    const colWidths = sheetDef.col_widths || [];
    const rows      = sheetDef.rows       || [];
    const colCount  = headers.length || (rows[0] || []).length || 1;

    function setCell(r, c, val, style) {
        const addr  = XLSX.utils.encode_cell({ r, c });
        const isNum = typeof val === 'number';
        const cell  = { v: val, t: isNum ? 'n' : 's', s: style };
        if (isNum) cell.z = '#,##0.##';
        ws[addr] = cell;
        if (c > ref.maxC) ref.maxC = c;
        if (r > ref.maxR) ref.maxR = r;
    }

    function mergeCols(r, val, style) {
        setCell(r, 0, val, style);
        for (let c = 1; c < colCount; c++) setCell(r, c, '', style);
        if (!ws['!merges']) ws['!merges'] = [];
        ws['!merges'].push({ s: { r, c: 0 }, e: { r, c: colCount - 1 } });
    }

    // 제목 행
    if (sheetDef.title) {
        mergeCols(row, sheetDef.title, titleStyle());
        row++;
    }
    // 부제목
    if (sheetDef.subtitle) {
        mergeCols(row, sheetDef.subtitle, subStyle());
        row++;
    }
    // 빈 행 (간격)
    row++;

    // 헤더
    if (headers.length > 0) {
        headerRowIdx = row;
        headers.forEach((h, c) => setCell(row, c, h, hdrStyle()));
        row++;
    }

    // 데이터 행
    rows.forEach((dataRow, ri) => {
        const even = ri % 2 === 0;
        dataRow.forEach((val, c) => {
            setCell(row, c, val, dataStyle(even, typeof val === 'number'));
        });
        for (let c = dataRow.length; c < colCount; c++) setCell(row, c, '', dataStyle(even, false));
        row++;
    });

    // 집계(summary) 행
    if (sheetDef.summary) {
        sheetDef.summary.forEach((val, c) => {
            setCell(row, c, val, sumStyle(typeof val === 'number'));
        });
        row++;
    }

    // 컬럼 폭: col_widths 우선, 없으면 내용 기반 자동 산정
    ws['!cols'] = Array.from({ length: colCount }, (_, i) => {
        if (colWidths[i]) return { wch: colWidths[i] };
        const headerLen = (headers[i] || '').length;
        const dataMax   = rows.reduce((mx, r) => Math.max(mx, String(r[i] ?? '').length), 0);
        return { wch: Math.min(45, Math.max(10, Math.max(headerLen, dataMax) * 1.2)) };
    });

    // 행 높이
    ws['!rows'] = [];
    if (sheetDef.title)    ws['!rows'][0] = { hpx: 36 };
    if (sheetDef.subtitle) ws['!rows'][1] = { hpx: 22 };
    if (headerRowIdx >= 0) ws['!rows'][headerRowIdx] = { hpx: 26 };

    // 틀 고정 + 자동 필터 (헤더 행 기준)
    if (headerRowIdx >= 0) {
        ws['!views'] = [{ state: 'frozen', xSplit: 0, ySplit: headerRowIdx + 1 }];
        ws['!autofilter'] = {
            ref: XLSX.utils.encode_range({
                s: { r: headerRowIdx, c: 0 },
                e: { r: headerRowIdx, c: colCount - 1 },
            }),
        };
    }

    // ref 범위 설정
    ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: ref.maxR, c: ref.maxC } });

    const sheetName = (sheetDef.name || 'Sheet').substring(0, 31);
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
}

// 시트가 없으면 기본 시트
if (wb.SheetNames.length === 0) {
    const ws = XLSX.utils.aoa_to_sheet([[data.title || '데이터 없음']]);
    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
}

// ── Save ──────────────────────────────────────────────────────────
try {
    XLSX.writeFile(wb, outputFile, { bookType: 'xlsx', cellStyles: true });
    process.stdout.write(outputFile + '\n');
    process.exit(0);
} catch (e) {
    process.stderr.write('xlsx error: ' + e.message + '\n');
    process.exit(1);
}
