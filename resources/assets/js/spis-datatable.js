/* SPIS 표 동작 — 헤더 정렬 / 행 선택 카운트 / 목록 정렬·필터·페이지네이션 */

/* 그리드 자동 표준화 — 마크업 변경 없이 액션 라벨/속성 통일 + 체크박스 컬럼 주입
   대상: section 안에 (.detail-tool__actions 또는 .grid-info__actions) + <table> 이 있는 영역.
   동작:
   1) 액션 버튼 텍스트("신규" / "행 추가" / "삭제" / "행 삭제" / "저장") → 표준 라벨 + data-grid-action 부여
   2) "삭제" 버튼이 존재하면 자동으로 체크박스 선두 컬럼 주입 (thead/tbody/colgroup 동시 처리)
   3) data-grid-section 자동 부여 → 선택 카운트 / 자동 갱신 동작 연결 */
(function () {
  if (window.__spisGridAutoStandard) return;
  window.__spisGridAutoStandard = true;

  var ACTION_MAP = {
    "신규": "new", "행 추가": "new",
    "삭제": "delete", "행 삭제": "delete",
    "저장": "save",
  };
  var STD_LABEL = { new: "신규", delete: "삭제", save: "저장" };
  var CHECKBOX_HTML = '<label class="checkbox checkbox--01"><input type="checkbox" class="checkbox__control" /><span class="checkbox__box"></span></label>';

  function standardizeButtons(actionsEl) {
    var hasDelete = false;
    actionsEl.querySelectorAll(".btn").forEach(function (btn) {
      if (btn.dataset.gridAction) {
        if (btn.dataset.gridAction === "delete") hasDelete = true;
        return;
      }
      var text = btn.textContent.trim();
      var act = ACTION_MAP[text];
      if (!act) return;
      btn.dataset.gridAction = act;
      btn.textContent = STD_LABEL[act];
      if (act === "delete") hasDelete = true;
    });
    return hasDelete;
  }

  function injectCheckboxColumn(table) {
    var firstHeadTh = table.querySelector("thead tr th");
    if (firstHeadTh && firstHeadTh.querySelector(".checkbox")) return;

    /* colgroup 처리 — 없으면 헤더 컬럼 수만큼 빈 col 생성 */
    var colgroup = table.querySelector("colgroup");
    if (!colgroup) {
      colgroup = document.createElement("colgroup");
      var firstHeadRow = table.querySelector("thead tr");
      var colCount = 0;
      if (firstHeadRow) {
        Array.prototype.forEach.call(firstHeadRow.children, function (th) {
          colCount += (parseInt(th.getAttribute("colspan"), 10) || 1);
        });
      }
      for (var i = 0; i < colCount; i++) colgroup.appendChild(document.createElement("col"));
      table.insertBefore(colgroup, table.firstChild);
    }
    var checkCol = document.createElement("col");
    checkCol.style.width = "44px";
    colgroup.insertBefore(checkCol, colgroup.firstChild);

    /* thead 첫 행에 체크박스 th 삽입 (다중 행 헤더면 rowspan 처리) */
    var headRows = table.querySelectorAll("thead tr");
    if (headRows.length) {
      var th = document.createElement("th");
      th.className = "datatable__head-cell--check";
      if (headRows.length > 1) th.rowSpan = headRows.length;
      th.innerHTML = CHECKBOX_HTML;
      headRows[0].insertBefore(th, headRows[0].firstChild);
    }

    /* tbody 각 행 첫 칸에 체크박스 td 삽입 */
    table.querySelectorAll("tbody tr").forEach(function (tr) {
      var td = document.createElement("td");
      td.className = "datatable__cell--check";
      td.innerHTML = CHECKBOX_HTML;
      tr.insertBefore(td, tr.firstChild);
    });
  }

  function standardizeSection(section) {
    if (section.dataset.gridAutoStd === "true") return;
    var actions = section.querySelector(".detail-tool__actions, .grid-info__actions");
    if (!actions) return;
    var table = section.querySelector("table");
    if (!table) return;

    var hasDelete = standardizeButtons(actions);
    if (hasDelete) {
      injectCheckboxColumn(table);
      if (!section.hasAttribute("data-grid-section")) section.setAttribute("data-grid-section", "");
    }
    section.dataset.gridAutoStd = "true";
  }

  function scan(root) {
    /* Pass 1: section 컨테이너 (lon020/lon040 등) */
    (root || document).querySelectorAll("section").forEach(standardizeSection);
    /* Pass 2: section 밖의 .detail-tool/.grid-info — 부모 컨테이너 기준 표준화
       (예: sit020form 의 accordion__body 안 .detail-split__main 컨테이너) */
    (root || document).querySelectorAll(".detail-tool, .grid-info").forEach(function (head) {
      if (head.closest("section")) return;  /* Pass 1 에서 처리됨 */
      var parent = head.parentElement;
      if (!parent) return;
      standardizeSection(parent);
    });
  }

  if (document.readyState !== "loading") scan();
  else document.addEventListener("DOMContentLoaded", function () { scan(); });

  /* 동적 탭 패널 삽입 대응 — 새 노드만 스캔 */
  new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      var added = mutations[i].addedNodes;
      for (var j = 0; j < added.length; j++) {
        if (added[j].nodeType === 1) scan(added[j]);
      }
    }
  }).observe(document.body, { childList: true, subtree: true });
})();

/* 그리드 액션 — 신규 / 삭제 / 저장 표준 동작
   [data-grid-section] 안 .grid-info__actions 의 [data-grid-action] 버튼 클릭 처리.
   - "new":    tbody 마지막 행 복제 → 입력 값 비우고 체크박스 해제 후 추가
   - "delete": 선택(체크) 된 행 삭제. 체크된 행 없으면 알림
   - "save":   spis:grid-save 커스텀 이벤트 디스패치 (도메인 핸들러가 잡아 처리) */
(function () {
  if (window.__spisGridActions) return;
  window.__spisGridActions = true;

  function getTbody(section) {
    return section.querySelector("table tbody");
  }
  function getBodyRows(section) {
    var tbody = getTbody(section);
    return tbody ? Array.prototype.slice.call(tbody.children) : [];
  }
  function renumberSequence(section) {
    /* 첫 번째 td 가 순번(체크박스 다음 칸 또는 첫 칸) 일 경우만 숫자 갱신 */
    var rows = getBodyRows(section);
    rows.forEach(function (tr, i) {
      var seqIdx = tr.firstElementChild && tr.firstElementChild.querySelector(".checkbox") ? 1 : 0;
      var seqCell = tr.children[seqIdx];
      if (!seqCell) return;
      var raw = seqCell.textContent.trim();
      if (/^\d+$/.test(raw)) seqCell.textContent = String(i + 1);
    });
  }
  function addRow(section) {
    var tbody = getTbody(section);
    if (!tbody) return;
    var last = tbody.lastElementChild;
    if (!last) return;
    var clone = last.cloneNode(true);
    /* 입력값 비우기 */
    clone.querySelectorAll("input").forEach(function (i) {
      if (i.type === "checkbox") i.checked = false;
      else i.value = "";
    });
    clone.querySelectorAll("select option[selected]").forEach(function (o) {
      o.removeAttribute("selected");
    });
    clone.querySelectorAll("select").forEach(function (s) {
      if (s.options.length) s.selectedIndex = 0;
    });
    /* 순번 셀(숫자) 비우기 — renumberSequence 가 채움 */
    var seqIdx = clone.firstElementChild && clone.firstElementChild.querySelector(".checkbox") ? 1 : 0;
    var seqCell = clone.children[seqIdx];
    if (seqCell && /^\d+$/.test(seqCell.textContent.trim())) seqCell.textContent = "";
    tbody.appendChild(clone);
    renumberSequence(section);
  }
  function deleteCheckedRows(section) {
    var rows = getBodyRows(section);
    var checked = rows.filter(function (tr) {
      var cb = tr.querySelector('input[type="checkbox"]');
      return cb && cb.checked;
    });
    if (!checked.length) {
      window.alert("삭제할 행을 선택하세요.");
      return;
    }
    checked.forEach(function (tr) { tr.remove(); });
    /* 헤더 체크박스 해제 */
    var head = section.querySelector('table thead input[type="checkbox"]');
    if (head) { head.checked = false; head.indeterminate = false; }
    renumberSequence(section);
  }
  function saveGrid(section) {
    var ev = new CustomEvent("spis:grid-save", { bubbles: true, detail: { section: section } });
    section.dispatchEvent(ev);
    /* 도메인 핸들러가 없으면 기본 알림 */
    if (!ev.defaultPrevented) {
      var title = section.querySelector(".grid-info__title");
      window.alert((title ? title.textContent.trim() + " — " : "") + "저장되었습니다.");
    }
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-grid-action]");
    if (!btn) return;
    var section = btn.closest("[data-grid-section]");
    if (!section) return;
    var action = btn.getAttribute("data-grid-action");
    if (action === "new") addRow(section);
    else if (action === "delete") deleteCheckedRows(section);
    else if (action === "save") saveGrid(section);
    /* 행 변동 후 카운트 자동 갱신 — MutationObserver 가 따라옴 */
  });
})();

/* 그리드 행 선택 카운트 — [data-grid-section] 내 표의 체크박스 선택 수를 집계
   타이틀 옆 [data-count-selected] 갱신, [data-count-selected-wrap] 토글
   thead 의 체크박스는 전체 선택/해제로 동작
   추가: tbody 행 변동 시 [data-count-total] 도 자동 갱신 */
(function () {
  if (window.__spisGridSelectionCount) return;
  window.__spisGridSelectionCount = true;

  function bodyCheckboxes(section) {
    return section.querySelectorAll('table tbody input[type="checkbox"]');
  }
  function headerCheckbox(section) {
    return section.querySelector('table thead input[type="checkbox"]');
  }
  function refresh(section) {
    var checked = 0, total = 0;
    bodyCheckboxes(section).forEach(function (cb) {
      total++;
      if (cb.checked) checked++;
    });
    var selWrap = section.querySelector("[data-count-selected-wrap]");
    var selNum  = section.querySelector("[data-count-selected]");
    var totalNum = section.querySelector("[data-count-total]");
    var newSel = String(checked);
    var newTotal = String(total);
    /* MutationObserver 자기-호출 무한루프 방지 — 동일값이면 쓰지 않음 */
    if (selNum && selNum.textContent !== newSel) selNum.textContent = newSel;
    if (totalNum && totalNum.textContent !== newTotal) totalNum.textContent = newTotal;
    var newHidden = checked === 0;
    if (selWrap && selWrap.hidden !== newHidden) selWrap.hidden = newHidden;
    var head = headerCheckbox(section);
    if (head) {
      head.checked = checked > 0 && checked === total;
      head.indeterminate = checked > 0 && checked < total;
    }
  }

  document.addEventListener("change", function (e) {
    var cb = e.target;
    if (!cb || cb.type !== "checkbox") return;
    var section = cb.closest("[data-grid-section]");
    if (!section) return;
    var isHead = !!cb.closest("table thead");
    if (isHead) {
      bodyCheckboxes(section).forEach(function (b) { b.checked = cb.checked; });
    }
    refresh(section);
  });

  /* 화면 로드 직후 한 번 갱신 (탭 패널 동적 삽입 포함) */
  function refreshAll() {
    document.querySelectorAll("[data-grid-section]").forEach(refresh);
  }
  if (document.readyState !== "loading") refreshAll();
  else document.addEventListener("DOMContentLoaded", refreshAll);
  new MutationObserver(refreshAll).observe(document.body, { childList: true, subtree: true });
})();


/* 데이터테이블 헤더 정렬 — 표준 동작 (document 위임)
   .datatable__head-cell--sortable 클릭 시 해당 열 기준 정렬 + ▴/▾ 표시.
   표준 9개 화면(data-key 보유 헤더)은 전용 스크립트가 처리하므로 제외. */
(function () {
  if (window.__spisTableSort) return;
  window.__spisTableSort = true;

  function cellText(row, idx) {
    var c = row.children[idx];
    return c ? c.textContent.trim() : "";
  }
  function isNumeric(v) {
    var s = v.replace(/\s/g, "");
    return s !== "" && /^-?[\d,]*\.?\d+%?$/.test(s);
  }
  function numVal(v) {
    var n = parseFloat(v.replace(/[^0-9.\-]/g, ""));
    return isNaN(n) ? 0 : n;
  }

  document.addEventListener("click", function (e) {
    var th = e.target.closest(".datatable__head-cell--sortable");
    if (!th) return;
    if (th.hasAttribute("data-key")) return; // 표준 전용 스크립트 담당
    var table = th.closest("table.datatable");
    if (!table || !table.tBodies.length) return;

    var heads = Array.prototype.slice.call(th.parentElement.children);
    var idx = heads.indexOf(th);
    if (idx < 0) return;
    var tbody = table.tBodies[0];

    var asc = !th.classList.contains("datatable__head-cell--asc");
    heads.forEach(function (h) {
      h.classList.remove("datatable__head-cell--asc", "datatable__head-cell--desc");
    });
    th.classList.add(asc ? "datatable__head-cell--asc" : "datatable__head-cell--desc");

    var rows = Array.prototype.slice.call(tbody.querySelectorAll(".datatable__row"));
    var totals = rows.filter(function (r) {
      return r.classList.contains("datatable__row--total");
    });
    var body = rows.filter(function (r) {
      return !r.classList.contains("datatable__row--total");
    });

    var allNum = body.length > 0 && body.every(function (r) {
      return isNumeric(cellText(r, idx));
    });
    body.sort(function (a, b) {
      var av = cellText(a, idx), bv = cellText(b, idx), cmp;
      if (allNum) cmp = numVal(av) - numVal(bv);
      else cmp = av.localeCompare(bv, "ko");
      return asc ? cmp : -cmp;
    });
    body.concat(totals).forEach(function (r) {
      tbody.appendChild(r);
    });
  });
})();

/* 계약 목록 — 정렬 / 필터 / 페이지네이션 (표준 공용)
   #contracts-tbody 가 있는 화면에서만 1회 초기화(data-cl-bound 가드).
   SPA 탭 패널이 동적으로 들어와도 MutationObserver 로 초기화. */
(function () {
  if (window.__spisContractsList) return;
  window.__spisContractsList = true;

  function init() {
    var tbody = document.getElementById("contracts-tbody");
    if (!tbody || tbody.dataset.clBound === "true") return;
    tbody.dataset.clBound = "true";

    var statusFilter = document.getElementById("status-filter");
    var dongInput = document.getElementById("dong");
    var hoInput = document.getElementById("ho");
    var totalCount = document.getElementById("total-count");
    var paginationList = document.getElementById("pagination-list");
    var paginationNav = document.getElementById("contracts-pagination");
    var pageSizeSelect = document.getElementById("page-size");

    var currentPage = 1;
    var pageSize = 10;
    var sortKey = null;
    var sortDir = 1;

    function getRows() {
      return Array.from(tbody.querySelectorAll(".datatable__row"));
    }

    function formatAmount(n) {
      return Number(n).toLocaleString("ko-KR");
    }

    getRows().forEach(function (row) {
      var cell = row.querySelector("[data-amount]");
      if (cell) {
        var raw = cell.dataset.amount || cell.textContent.replace(/,/g, "");
        cell.textContent = formatAmount(raw);
      }
      row.addEventListener("click", function (e) {
        if (e.target.closest("a, button")) return;
        var screen = row.dataset.screen;
        if (screen && window.SPIS) {
          window.SPIS.openScreen(screen, { activate: true });
        }
      });
    });

    function matchesFilter(row) {
      var status = statusFilter ? statusFilter.value : "";
      var badge = row.querySelector(".badge");
      var statusText = badge ? badge.textContent.trim() : "";
      if (status && statusText !== status) return false;

      var unit = (row.cells[1] && row.cells[1].textContent.trim()) || "";
      var dong = (dongInput && dongInput.value.trim()) || "";
      var ho = (hoInput && hoInput.value.trim()) || "";
      if (dong && unit.indexOf(dong) === -1) return false;
      if (ho && unit.indexOf(ho) === -1) return false;
      return true;
    }

    function applyFilter() {
      getRows().forEach(function (row) {
        row.dataset.filtered = matchesFilter(row) ? "true" : "false";
      });
      currentPage = 1;
      updateCount();
      renderPagination();
      showPage();
    }

    function visibleRows() {
      return getRows().filter(function (r) {
        return r.dataset.filtered !== "false";
      });
    }

    function updateCount() {
      if (totalCount) totalCount.textContent = String(visibleRows().length);
    }

    function sortRows(key) {
      var rows = visibleRows();
      rows.sort(function (a, b) {
        var map = { site: 0, unit: 1, name: 2, date: 3, amount: 4, status: 5 };
        var idx = map[key];
        var av = a.cells[idx].textContent.trim();
        var bv = b.cells[idx].textContent.trim();
        if (key === "amount") {
          av = Number(a.cells[idx].dataset.amount || av.replace(/,/g, ""));
          bv = Number(b.cells[idx].dataset.amount || bv.replace(/,/g, ""));
        }
        if (av < bv) return -1 * sortDir;
        if (av > bv) return 1 * sortDir;
        return 0;
      });
      rows.forEach(function (r) {
        tbody.appendChild(r);
      });
    }

    document.querySelectorAll("[data-sortable]").forEach(function (th) {
      th.addEventListener("click", function () {
        var key = th.dataset.key;
        if (sortKey === key) sortDir *= -1;
        else {
          sortKey = key;
          sortDir = 1;
        }
        document.querySelectorAll("[data-sortable]").forEach(function (h) {
          h.classList.remove("datatable__head-cell--asc", "datatable__head-cell--desc");
        });
        th.classList.add(
          sortDir > 0 ? "datatable__head-cell--asc" : "datatable__head-cell--desc"
        );
        sortRows(key);
        showPage();
      });
    });

    function totalPages() {
      return Math.max(1, Math.ceil(visibleRows().length / pageSize));
    }

    function showPage() {
      var rows = visibleRows();
      var tp = totalPages();
      currentPage = Math.min(currentPage, tp);
      rows.forEach(function (row, i) {
        var page = Math.floor(i / pageSize) + 1;
        row.hidden = page !== currentPage;
      });
    }

    function renderPagination() {
      if (!paginationList) return;
      var tp = totalPages();
      paginationList.innerHTML = "";
      var maxButtons = Math.min(tp, 5);
      var start = Math.max(1, currentPage - 2);
      var end = Math.min(tp, start + maxButtons - 1);
      start = Math.max(1, end - maxButtons + 1);

      for (var i = start; i <= end; i++) {
        (function (page) {
          var li = document.createElement("li");
          var btn = document.createElement("button");
          btn.type = "button";
          btn.className = "pagination__item";
          btn.textContent = String(page);
          btn.dataset.page = String(page);
          if (page === currentPage) btn.setAttribute("aria-current", "page");
          btn.addEventListener("click", function () {
            currentPage = page;
            showPage();
            renderPagination();
          });
          li.appendChild(btn);
          paginationList.appendChild(li);
        })(i);
      }

      if (paginationNav) {
        var prev = paginationNav.querySelector('[data-page="prev"]');
        var next = paginationNav.querySelector('[data-page="next"]');
        if (prev) prev.disabled = currentPage <= 1;
        if (next) next.disabled = currentPage >= tp;
      }
      showPage();
    }

    if (paginationNav) {
      paginationNav.addEventListener("click", function (e) {
        var arrow = e.target.closest(".pagination__arrow");
        if (!arrow || arrow.disabled) return;
        var tp = totalPages();
        if (arrow.dataset.page === "prev") currentPage = Math.max(1, currentPage - 1);
        else if (arrow.dataset.page === "next") currentPage = Math.min(tp, currentPage + 1);
        showPage();
        renderPagination();
      });
    }

    if (pageSizeSelect) {
      pageSizeSelect.addEventListener("change", function () {
        pageSize = Number(pageSizeSelect.value) || 10;
        currentPage = 1;
        renderPagination();
      });
    }

    var btnSearch = document.getElementById("btn-search");
    if (btnSearch) btnSearch.addEventListener("click", applyFilter);
    var btnReset = document.getElementById("btn-reset");
    if (btnReset) {
      btnReset.addEventListener("click", function () {
        if (statusFilter) statusFilter.value = "";
        if (dongInput) dongInput.value = "";
        if (hoInput) hoInput.value = "";
        getRows().forEach(function (r) {
          r.dataset.filtered = "true";
        });
        currentPage = 1;
        updateCount();
        renderPagination();
        showPage();
      });
    }

    getRows().forEach(function (r) {
      r.dataset.filtered = "true";
    });
    updateCount();
    renderPagination();
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
  new MutationObserver(init).observe(document.body, { childList: true, subtree: true });
})();
