import flatpickr from 'flatpickr';
import { Korean } from 'flatpickr/dist/l10n/ko.js';
import 'flatpickr/dist/flatpickr.min.css';

flatpickr.localize(Korean);

function fmtDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0');
}

function onDayCreate(dObj, dStr, fp, dayElem) {
    const holidays = window.KOREAN_HOLIDAYS || {};
    const key = fmtDate(dayElem.dateObj);
    if (holidays[key]) {
        dayElem.classList.add('sw-holiday');
        dayElem.setAttribute('title', holidays[key]);
    }
}

function dispatchInput(fp) {
    fp.input.dispatchEvent(new Event('input', { bubbles: true }));
    fp.input.dispatchEvent(new Event('change', { bubbles: true }));
}

function repositionCalendar(fp) {
    const input = fp.altInput || fp.input;
    const cal   = fp.calendarContainer;

    // Flatpickr가 위쪽으로 열 때 bottom을 설정하는데, position:fixed와 충돌해 달력이 늘어남
    cal.style.bottom = '';
    cal.style.right  = '';
    cal.style.height = '';

    const rect  = input.getBoundingClientRect();
    const calH  = cal.offsetHeight || 320;
    const calW  = cal.offsetWidth  || 308;
    const vH    = window.innerHeight;
    const vW    = window.innerWidth;
    const top   = (vH - rect.bottom >= calH)
        ? rect.bottom + 2
        : Math.max(4, rect.top - calH - 2);
    const left  = Math.max(4, Math.min(rect.left, vW - calW - 8));
    cal.style.top  = top  + 'px';
    cal.style.left = left + 'px';
}

const COMMON_OPTS = {
    allowInput: true,
    disableMobile: true,
    onDayCreate: onDayCreate,
    onReady: function (_, __, fp) {
        // Flatpickr의 내부 positionCalendar 교체 — scroll/resize 재호출 포함
        // position:fixed 기준 뷰포트 좌표로 계산하기 위함
        fp.positionCalendar = function () { repositionCalendar(fp); };
    },
    onOpen: function (_, __, fp) {
        // rAF: 레이아웃 패스 후 offsetHeight 실측해서 한 번 더 보정
        requestAnimationFrame(function () { repositionCalendar(fp); });
    },
    onChange: function (_, __, fp) { dispatchInput(fp); },
};

function initDatePickers(root) {
    root = root || document;
    // appendTo:body → 달력 컨테이너를 flex/grid 내부가 아닌 body에 붙여
    // 부모 컨테이너의 overflow 계산에서 제외시켜 가로 스크롤 방지
    var body = document.body;

    root.querySelectorAll('input[type="date"]:not(.flatpickr-input)').forEach(function (el) {
        flatpickr(el, Object.assign({}, COMMON_OPTS, { dateFormat: 'Y-m-d', appendTo: body }));
    });

    root.querySelectorAll('input[type="datetime-local"]:not(.flatpickr-input)').forEach(function (el) {
        flatpickr(el, Object.assign({}, COMMON_OPTS, {
            dateFormat: 'Y-m-dTH:i',
            enableTime: true,
            time_24hr: true,
            appendTo: body,
        }));
    });
}

document.addEventListener('DOMContentLoaded', function () { initDatePickers(); });

window.initDatePickers = initDatePickers;
