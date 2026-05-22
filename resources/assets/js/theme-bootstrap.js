/** Apply saved theme before first paint (include in <head> after tokens.css). */
(function () {
  var root = document.documentElement;
  var theme = localStorage.getItem("spis-theme");
  var accent = localStorage.getItem("spis-accent");
  var fontScale = localStorage.getItem("spis-font-scale");
  if (theme) root.setAttribute("data-theme", theme);
  if (accent) root.setAttribute("data-accent", accent);
  if (fontScale) root.setAttribute("data-font-scale", fontScale);
})();
