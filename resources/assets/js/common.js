(function () {
  const root = document.documentElement;
  const themeWrap =
    document.querySelector(".app-tabs [data-theme-btn]") ||
    document.querySelector(".tab-theme[data-theme-btn]") ||
    document.querySelector("[data-theme-btn]");
  const themeToggle =
    document.getElementById("theme-toggle") ||
    themeWrap?.querySelector(".tab-theme__trigger, .theme-btn__trigger");
  const themePanel = themeWrap?.querySelector(".tab-theme__panel");

  const savedTheme = localStorage.getItem("spis-theme");
  const savedAccent = localStorage.getItem("spis-accent");
  const savedFontScale = localStorage.getItem("spis-font-scale");

  function getActiveAccent() {
    return root.getAttribute("data-accent") || "blue";
  }

  function syncThemeModeButtons() {
    const mode = root.getAttribute("data-theme") || "light";
    themeWrap?.querySelectorAll("[data-theme-mode]").forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.themeMode === mode);
    });
  }

  function syncFontScaleButtons() {
    const scale = root.getAttribute("data-font-scale") || "14";
    themeWrap?.querySelectorAll("[data-font-scale]").forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.fontScale === scale);
    });
  }

  function syncAccentSwatches() {
    const active = getActiveAccent();
    themeWrap?.querySelectorAll(".theme-btn__swatch").forEach((swatch) => {
      swatch.classList.toggle("is-active", swatch.dataset.accent === active);
      swatch.setAttribute("aria-pressed", swatch.dataset.accent === active ? "true" : "false");
    });
  }

  if (savedTheme) {
    root.setAttribute("data-theme", savedTheme);
  }
  syncThemeModeButtons();

  if (savedAccent) {
    root.setAttribute("data-accent", savedAccent);
  }
  syncAccentSwatches();

  if (savedFontScale) {
    root.setAttribute("data-font-scale", savedFontScale);
  }
  syncFontScaleButtons();

  function setPanelOpen(open) {
    if (!themeWrap || !themeToggle) return;
    themeWrap.classList.toggle("is-open", open);
    themeToggle.setAttribute("aria-expanded", String(open));
    if (themePanel) {
      themePanel.setAttribute("aria-hidden", String(!open));
    }
  }

  if (themeToggle && themeWrap) {
    themeToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      if (e.target.closest(".theme-btn__swatch")) return;
      if (e.target.closest(".tab-theme__mode-btn")) return;
      if (e.target.closest(".theme-btn__mode-btn")) return;
      setPanelOpen(!themeWrap.classList.contains("is-open"));
    });

    themeWrap.querySelectorAll(".theme-btn__swatch").forEach((swatch) => {
      swatch.addEventListener("click", (e) => {
        e.stopPropagation();
        const accent = swatch.dataset.accent;
        if (!accent) return;
        root.setAttribute("data-accent", accent);
        localStorage.setItem("spis-accent", accent);
        syncAccentSwatches();
      });
    });

    themeWrap.querySelectorAll("[data-theme-mode]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const mode = btn.dataset.themeMode;
        root.setAttribute("data-theme", mode);
        localStorage.setItem("spis-theme", mode);
        syncThemeModeButtons();
      });
    });

    themeWrap.querySelectorAll("[data-font-scale]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const scale = btn.dataset.fontScale;
        root.setAttribute("data-font-scale", scale);
        localStorage.setItem("spis-font-scale", scale);
        syncFontScaleButtons();
      });
    });

    themeWrap.querySelectorAll(".theme-btn__mode-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const mode = btn.dataset.themeMode;
        root.setAttribute("data-theme", mode);
        localStorage.setItem("spis-theme", mode);
        themeWrap.querySelectorAll(".theme-btn__mode-btn").forEach((b) => {
          b.classList.toggle("is-active", b === btn);
        });
        syncThemeModeButtons();
      });
    });

    document.addEventListener("click", (e) => {
      if (!themeWrap.contains(e.target)) {
        setPanelOpen(false);
      }
    });
  }

  document.querySelectorAll(".sidebar-group__title").forEach((btn) => {
    btn.addEventListener("click", () => {
      const group = btn.closest(".sidebar-group");
      if (!group) return;
      const expanded = group.classList.toggle("is-expanded");
      btn.setAttribute("aria-expanded", String(expanded));
    });
  });

  document.querySelectorAll(".sidebar-tab__item").forEach((tab) => {
    tab.addEventListener("click", () => {
      document.querySelectorAll(".sidebar-tab__item").forEach((t) => {
        t.setAttribute("aria-selected", "false");
      });
      tab.setAttribute("aria-selected", "true");
    });
  });

  function closeAllSelects(except) {
    document.querySelectorAll("[data-select].is-open").forEach((sel) => {
      if (sel === except) return;
      sel.classList.remove("is-open");
      sel.querySelector(".select__trigger")?.setAttribute("aria-expanded", "false");
    });
  }

  /* SPA — Figma select-single (data-select) */
  document.addEventListener("click", (e) => {
    const option = e.target.closest("[data-select] .select__option");
    if (option && !option.disabled) {
      const sel = option.closest("[data-select]");
      const trigger = sel?.querySelector(".select__trigger");
      const valueEl = trigger?.querySelector(".select__value");
      if (valueEl) valueEl.textContent = option.textContent?.trim() ?? "";
      sel.querySelectorAll(".select__option").forEach((o) => {
        o.removeAttribute("aria-selected");
      });
      option.setAttribute("aria-selected", "true");
      sel.classList.remove("is-open");
      trigger?.setAttribute("aria-expanded", "false");
      return;
    }

    const trigger = e.target.closest("[data-select] .select__trigger");
    if (trigger) {
      e.stopPropagation();
      const sel = trigger.closest("[data-select]");
      const open = !sel.classList.contains("is-open");
      closeAllSelects(open ? sel : null);
      sel.classList.toggle("is-open", open);
      trigger.setAttribute("aria-expanded", String(open));
      return;
    }

    if (!e.target.closest("[data-select]")) {
      closeAllSelects();
    }
  });

  /* SPA 탭 패널은 동적 삽입 — document 위임으로 아코디언 토글 */
  document.addEventListener("click", (e) => {
    const header = e.target.closest(".accordion__header");
    if (!header) return;
    const accordion = header.closest(".accordion");
    if (!accordion) return;
    accordion.classList.toggle("is-open");
    header.setAttribute(
      "aria-expanded",
      accordion.classList.contains("is-open") ? "true" : "false"
    );
  });

  const appShell = document.querySelector(".app-shell");
  const sidebarFoldBtn = document.getElementById("sidebar-fold-toggle");
  const savedSidebarFold = localStorage.getItem("spis-sidebar-folded") === "true";

  if (appShell && savedSidebarFold) {
    appShell.classList.add("is-sidebar-folded");
    sidebarFoldBtn?.setAttribute("aria-expanded", "false");
    sidebarFoldBtn?.setAttribute("aria-label", "사이드바 펼치기");
  }

  sidebarFoldBtn?.addEventListener("click", () => {
    if (!appShell) return;
    const folded = appShell.classList.toggle("is-sidebar-folded");
    localStorage.setItem("spis-sidebar-folded", String(folded));
    sidebarFoldBtn.setAttribute("aria-expanded", String(!folded));
    sidebarFoldBtn.setAttribute("aria-label", folded ? "사이드바 펼치기" : "사이드바 접기");
  });

  const menuSearch = document.querySelector("[data-menu-search]");
  if (menuSearch) {
    menuSearch.addEventListener("input", () => {
      const q = menuSearch.value.trim().toLowerCase();
      document.querySelectorAll(".sidebar-group[data-menu-group]").forEach((group) => {
        const text = group.textContent?.toLowerCase() ?? "";
        group.hidden = Boolean(q) && !text.includes(q);
      });
    });
  }
})();
