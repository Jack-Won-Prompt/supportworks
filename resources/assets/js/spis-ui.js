/* SPIS UI 동작 — 모달 / 아코디언 / 고급검색 / 안내문 / 마스터-디테일 */

/* 모달 — open/close (표준 공용, document 위임)
   [data-open-modal="id"] 클릭 시 해당 모달과 backdrop 표시,
   [data-close-modal] 또는 backdrop 클릭 시 닫기.
   SPA 탭 패널 동적 삽입에도 동작. */
(function () {
  if (window.__spisModalBound) return;
  window.__spisModalBound = true;

  document.addEventListener("click", function (e) {
    var opener = e.target.closest("[data-open-modal]");
    if (opener) {
      var m = document.getElementById(opener.getAttribute("data-open-modal"));
      var bd = document.querySelector("[data-modal-backdrop]");
      if (m) {
        if (bd) bd.classList.add("is-open");
        m.classList.add("is-open");
      }
      return;
    }
    if (e.target.closest("[data-close-modal]") || e.target.matches("[data-modal-backdrop]")) {
      var bd2 = document.querySelector("[data-modal-backdrop]");
      if (bd2) bd2.classList.remove("is-open");
      document.querySelectorAll(".modal.is-open").forEach(function (m) {
        m.classList.remove("is-open");
      });
    }
  });
})();

/* 아코디언 — 모두 펼치기/접기 (표준 공용, document 위임)
   [id^="expand-all"] / [id^="collapse-all"] 클릭 시 가장 가까운
   .accordion-wrap(#accordion-wrap) 내 .accordion 일괄 토글.
   기존 contract-detail.js + customer-360.js 통합. */
(function () {
  if (window.__spisAccordion) return;
  window.__spisAccordion = true;

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[id]");
    if (!btn) return;
    var isExpand = btn.id.indexOf("expand-all") === 0;
    var isCollapse = btn.id.indexOf("collapse-all") === 0;
    if (!isExpand && !isCollapse) return;

    var wrap =
      btn.closest(".accordion-wrap") ||
      document.querySelector("#accordion-wrap") ||
      document.querySelector(".accordion-wrap");
    if (!wrap) return;

    wrap.querySelectorAll(".accordion").forEach(function (el) {
      el.classList.toggle("is-open", isExpand);
      var head = el.querySelector(".accordion__header");
      if (head) head.setAttribute("aria-expanded", isExpand ? "true" : "false");
    });
  });
})();

/* 검색조건 — 접이식 고급검색 (표준 공용, document 위임)
   [data-search-toggle] 클릭 시 같은 .search-box 내
   [data-search-advanced] 영역 표시 토글. */
(function () {
  if (window.__spisSearchAdvancedBound) return;
  window.__spisSearchAdvancedBound = true;

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-search-toggle]");
    if (!btn) return;
    var box = btn.closest(".search-box");
    if (!box) return;
    var adv = box.querySelector("[data-search-advanced]");
    if (!adv) return;

    var expanded = btn.getAttribute("aria-expanded") === "true";
    btn.setAttribute("aria-expanded", String(!expanded));
    adv.hidden = expanded;

    var label = btn.querySelector(".search-box__toggle-label");
    if (label) label.textContent = expanded ? "상세조건" : "상세조건 닫기";
  });
})();

/* 안내문 — 접기/펼치기 (표준 공용, document 위임)
   .notice 의 .notice__head 클릭 시 .is-collapsed 토글.
   (입금 일괄 수정 등 주의사항 패널 공통 동작) */
(function () {
  if (window.__spisNoticeToggleBound) return;
  window.__spisNoticeToggleBound = true;

  document.addEventListener("click", function (e) {
    var head = e.target.closest(".notice__head");
    if (!head) return;
    var notice = head.closest(".notice");
    if (!notice) return;
    var collapsed = notice.classList.toggle("is-collapsed");
    head.setAttribute("aria-expanded", collapsed ? "false" : "true");
  });
})();

/* 마스터-디테일 — 분할뷰 / 목록 행 선택 강조 (표준 공용, document 위임)
   .md-split__list 또는 [data-md-selectable] 내 행 클릭 시 선택 강조,
   분할뷰(.md-split)면 [data-md-title] 상세 헤더를 선택 행 값으로 갱신. */
(function () {
  if (window.__spisMdBound) return;
  window.__spisMdBound = true;

  document.addEventListener("click", function (e) {
    var row = e.target.closest(
      ".md-split__list .datatable__row, [data-md-selectable] .datatable__row"
    );
    if (!row) return;
    if (e.target.closest("input, button, a, label")) return;

    var scope = row.closest(".md-split__list") || row.closest("[data-md-selectable]");
    if (!scope) return;
    scope.querySelectorAll(".datatable__row").forEach(function (r) {
      r.removeAttribute("data-row-selected");
    });
    row.setAttribute("data-row-selected", "true");

    /* 분할뷰: 상세 헤더 갱신 */
    var split = row.closest(".md-split");
    if (!split) return;
    var titleEl = split.querySelector("[data-md-title]");
    if (!titleEl) return;
    var cells = row.querySelectorAll("td");
    var base = titleEl.getAttribute("data-md-base") || "";
    var idx = (titleEl.getAttribute("data-md-cells") || "0,1")
      .split(",")
      .map(function (n) { return parseInt(n, 10); });
    var parts = idx
      .map(function (i) { return cells[i] ? cells[i].textContent.trim() : ""; })
      .filter(Boolean);
    titleEl.textContent = base + (parts.length ? " — " + parts.join(" ") : "");
  });
})();

/* 조건부 표시 — [data-show-when-select="<id>"] [data-show-when-value="<val>"]
   대상 select 의 값이 일치할 때만 요소 표시. 그 외에는 hidden 속성으로 숨김.
   여러 값 매칭은 콤마 구분 (예: "8,0").
   초기 + select change + MutationObserver(동적 탭 패널 삽입) 대응. */
(function () {
  if (window.__spisConditionalShow) return;
  window.__spisConditionalShow = true;

  function syncOne(el) {
    var selId = el.getAttribute("data-show-when-select");
    var expectedRaw = el.getAttribute("data-show-when-value") || "";
    if (!selId) return;
    var sel = document.getElementById(selId);
    if (!sel) return;
    var expected = expectedRaw.split(",").map(function (v) { return v.trim(); });
    var match = expected.indexOf(sel.value) !== -1;
    el.hidden = !match;
  }

  function syncAll(root) {
    var ctx = (root && typeof root.querySelectorAll === "function") ? root : document;
    ctx.querySelectorAll("[data-show-when-select]").forEach(syncOne);
  }

  document.addEventListener("change", function (e) {
    if (e.target && e.target.tagName === "SELECT") syncAll();
  });

  if (document.readyState !== "loading") syncAll();
  else document.addEventListener("DOMContentLoaded", syncAll);

  new MutationObserver(function (m) {
    for (var i = 0; i < m.length; i++) {
      var added = m[i].addedNodes;
      for (var j = 0; j < added.length; j++) {
        if (added[j].nodeType === 1) { syncAll(added[j]); }
      }
    }
  }).observe(document.body, { childList: true, subtree: true });
})();

