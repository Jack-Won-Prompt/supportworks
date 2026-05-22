/**
 * Figma DS - open screen tabs (MDI). Menu/tab opens panels without full page navigation.
 */
(function () {
  const STORAGE_KEY = "spis-open-tabs";
  const ACTIVE_KEY = "spis-active-tab";
  const SCREENS = window.SPIS_SCREENS || {};

  const tabMenu = document.getElementById("screen-tabs");
  const tabPanels = document.getElementById("tab-panels");
  if (!tabMenu || !tabPanels) return;

  const instances = new Map();
  let openTabIds = [];
  let activeTabId = null;

  const loadedCss = new Set();
  const loadedJs = new Set();

  function saveSession() {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(openTabIds));
      if (activeTabId) sessionStorage.setItem(ACTIVE_KEY, activeTabId);
    } catch (_) {
      /* ignore */
    }
  }

  function loadSession() {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) {
          openTabIds = parsed.filter((id) => SCREENS[id]);
        }
      }
      const active = sessionStorage.getItem(ACTIVE_KEY);
      if (active && SCREENS[active] && openTabIds.includes(active)) {
        activeTabId = active;
      }
    } catch (_) {
      /* ignore */
    }
  }

  function loadStylesheet(href) {
    if (loadedCss.has(href)) return;
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = href;
    link.dataset.spisCss = href;
    document.head.appendChild(link);
    loadedCss.add(href);
  }

  function loadScriptOnce(src) {
    if (loadedJs.has(src)) return Promise.resolve();
    return new Promise((resolve, reject) => {
      const el = document.createElement("script");
      el.src = src;
      el.async = false;
      el.dataset.spisJs = src;
      el.onload = () => {
        loadedJs.add(src);
        resolve();
      };
      el.onerror = reject;
      document.body.appendChild(el);
    });
  }

  function renderTabBar() {
    tabMenu.innerHTML = "";
    openTabIds.forEach((id) => {
      const meta = SCREENS[id];
      if (!meta) return;
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "tab__item";
      btn.setAttribute("role", "tab");
      btn.dataset.screenId = id;
      btn.setAttribute("aria-selected", id === activeTabId ? "true" : "false");
      btn.innerHTML = `<span class="tab__item-label">${meta.label}</span>`;
      if (openTabIds.length > 1) {
        const close = document.createElement("span");
        close.className = "tab__item-close";
        close.setAttribute("role", "button");
        close.setAttribute("aria-label", `${meta.label} \ud0ed \ub2eb\uae30`);
        close.innerHTML =
          '<img src="assets/icon/icon-delete-01.svg" alt="" width="12" height="12" />';
        close.dataset.closeTab = id;
        btn.appendChild(close);
      }
      tabMenu.appendChild(btn);
    });
  }

  function updateSidebarActive(screenId) {
    const meta = SCREENS[screenId];
    if (!meta) return;
    document.querySelectorAll(".sidebar-link").forEach((el) => {
      el.classList.toggle("is-active", el.dataset.screen === screenId);
    });
    document.querySelectorAll(".sidebar-group").forEach((group) => {
      const isGroup = group.dataset.menuGroup === meta.group;
      group.classList.toggle("is-expanded", isGroup);
      const title = group.querySelector(".sidebar-group__title");
      if (title) {
        title.classList.toggle("is-active", isGroup);
        title.setAttribute("aria-expanded", isGroup ? "true" : "false");
      }
      const icon = group.querySelector(".sidebar-group__icon");
      if (icon && icon.src) {
        const m = icon.src.match(/icon-menu-([a-z]+)-/);
        if (m) {
          icon.src = `assets/icon/icon-menu-${m[1]}-${isGroup ? "active" : "default"}.svg`;
        }
      }
    });
  }

  function appBasePath() {
    const path = window.location.pathname || "/";
    const idx = path.lastIndexOf("/");
    return idx >= 0 ? path.slice(0, idx + 1) : "/";
  }

  async function loadViewHtml(screenId, meta) {
    if (meta.html) return meta.html;
    // 인라인 번들 우선 — 서버 없이(file://) 브라우저에서 바로 동작
    if (window.SPIS_VIEWS && window.SPIS_VIEWS[screenId] != null) {
      return window.SPIS_VIEWS[screenId];
    }
    // 폴백: 서버 환경에서 메뉴 그룹(대메뉴) 폴더의 화면 직접 로드
    const dir = meta.group ? meta.group + "/" : "";
    const url = `${appBasePath()}pages/views/${dir}${screenId}.html?v=${Date.now()}`;
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error(`${res.status} ${url}`);
    return res.text();
  }

  async function ensurePanel(screenId) {
    let inst = instances.get(screenId);
    if (inst?.loaded) return inst;
    if (inst && !inst.loaded) {
      inst.panel.remove();
      instances.delete(screenId);
    }

    const meta = SCREENS[screenId];
    if (!meta) {
      console.error("Unknown screen:", screenId);
      return null;
    }

    const panel = document.createElement("div");
    panel.className = "tab-panel";
    panel.dataset.screenId = screenId;
    panel.setAttribute("role", "tabpanel");
    panel.hidden = true;
    panel.innerHTML = '<div class="tab-panel__loading">\ubd88\ub7ec\uc624\ub294 \uc911\u2026</div>';
    tabPanels.appendChild(panel);
    inst = { panel, loaded: false, jsLoaded: false };
    instances.set(screenId, inst);

    (meta.css || []).forEach(loadStylesheet);

    try {
      let html = await loadViewHtml(screenId, meta);
      html = html.replace(/\.\.\/assets\//g, "assets/");
      panel.innerHTML = html;
      inst.loaded = true;
      bindPanelLinks(panel);
      initPanelAccordions(panel);
    } catch (err) {
      instances.delete(screenId);
      panel.innerHTML = `<p class="tab-panel__error">\ud654\uba74\uc744 \ubd88\ub7ec\uc624\uc9c0 \ubabb\ud588\uc2b5\ub2c8\ub2e4. (${screenId})<br><small>${err.message}</small></p>`;
      console.error(err);
    }

    return inst;
  }

  function initPanelAccordions(panel) {
    panel.querySelectorAll(".accordion").forEach((accordion) => {
      const header = accordion.querySelector(".accordion__header");
      if (!header) return;
      const open = accordion.classList.contains("is-open");
      header.setAttribute("aria-expanded", open ? "true" : "false");
    });
  }

  function bindPanelLinks(panel) {
    panel.querySelectorAll("[data-screen]").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        openScreen(el.dataset.screen, { activate: true });
      });
    });
    panel.querySelectorAll("a[href$='.html']").forEach((el) => {
      const href = el.getAttribute("href") || "";
      const match = href.match(/([a-z0-9-]+)\.html$/i);
      if (match && SCREENS[match[1]]) {
        el.addEventListener("click", (e) => {
          e.preventDefault();
          openScreen(match[1], { activate: true });
        });
      }
    });
  }

  async function activateTab(screenId) {
    if (!SCREENS[screenId]) return;
    const inst = await ensurePanel(screenId);
    if (!inst || !inst.loaded) return;

    if ((SCREENS[screenId].js || []).length && !inst.jsLoaded) {
      for (const src of SCREENS[screenId].js) {
        await loadScriptOnce(src);
      }
      inst.jsLoaded = true;
    }

    activeTabId = screenId;
    instances.forEach((item, id) => {
      const active = id === screenId;
      if (active) {
        if (!item.panel.isConnected) {
          tabPanels.appendChild(item.panel);
        }
        item.panel.classList.add("is-active");
        item.panel.hidden = false;
      } else {
        item.panel.classList.remove("is-active");
        item.panel.hidden = true;
        if (item.panel.isConnected) {
          item.panel.remove();
        }
      }
    });
    renderTabBar();
    updateSidebarActive(screenId);
    saveSession();
    document.title = `${SCREENS[screenId].label} | SK\uc5d0\ucf54\ud50c\ub79c\ud2b8 \ubd84\uc591\uad00\ub9ac\uc2dc\uc2a4\ud15c`;
  }

  function openScreen(screenId, options) {
    const opts = options || {};
    const activate = opts.activate !== false;
    if (!SCREENS[screenId]) return;
    if (!openTabIds.includes(screenId)) {
      openTabIds.push(screenId);
    }
    if (activate) {
      activateTab(screenId);
    } else {
      renderTabBar();
      saveSession();
    }
  }

  function closeTab(screenId) {
    const idx = openTabIds.indexOf(screenId);
    if (idx === -1 || openTabIds.length <= 1) return;
    openTabIds.splice(idx, 1);
    const inst = instances.get(screenId);
    if (inst) {
      inst.panel.hidden = true;
      inst.panel.classList.remove("is-active");
    }
    if (activeTabId === screenId) {
      const next = openTabIds[Math.min(idx, openTabIds.length - 1)];
      activateTab(next);
    } else {
      renderTabBar();
    }
    saveSession();
  }

  tabMenu.addEventListener("click", (e) => {
    const closeEl = e.target.closest("[data-close-tab]");
    if (closeEl) {
      e.stopPropagation();
      closeTab(closeEl.dataset.closeTab);
      return;
    }
    const tab = e.target.closest(".tab__item[data-screen-id]");
    if (tab) activateTab(tab.dataset.screenId);
  });

  document.querySelector(".app-sidebar")?.addEventListener("click", (e) => {
    const link = e.target.closest("[data-screen]");
    if (!link) return;
    e.preventDefault();
    openScreen(link.dataset.screen, { activate: true });
  });

  window.SPIS = { openScreen, closeTab, activateTab, SCREENS };

  loadSession();
  if (!openTabIds.length) openTabIds = ["contracts-list"];
  if (!activeTabId || !openTabIds.includes(activeTabId)) {
    activeTabId = openTabIds[openTabIds.length - 1];
  }
  activateTab(activeTabId);
})();
