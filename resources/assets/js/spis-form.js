/* SPIS 폼 입력 UX — SMS 바이트 카운터 */

/* SMS 작성 — 바이트 카운터 (표준 공용, document 위임)
   #cus-sms-text 입력 시 #cus-sms-byte 갱신, #cus-sms-clear 로 초기화.
   해당 요소가 있는 화면에서만 동작. */
(function () {
  if (window.__spisSmsCompose) return;
  window.__spisSmsCompose = true;

  function byteLen(str) {
    var n = 0;
    for (var i = 0; i < str.length; i++) {
      n += str.charCodeAt(i) <= 0x7f ? 1 : 2;
    }
    return n;
  }

  function refreshByte() {
    var ta = document.getElementById("cus-sms-text");
    var out = document.getElementById("cus-sms-byte");
    if (!ta || !out) return;
    var txt = byteLen(ta.value) + " Bytes";
    /* 동일 값이면 쓰지 않음 — MutationObserver 자기-호출 무한루프 방지 */
    if (out.textContent !== txt) out.textContent = txt;
  }

  document.addEventListener("input", function (e) {
    if (e.target && e.target.id === "cus-sms-text") refreshByte();
  });

  document.addEventListener("click", function (e) {
    if (e.target.closest("#cus-sms-clear")) {
      var ta = document.getElementById("cus-sms-text");
      if (ta) {
        ta.value = "";
        refreshByte();
      }
    }
  });

  /* 화면이 동적으로 들어와도 바이트 표시가 맞도록 */
  new MutationObserver(refreshByte).observe(document.body, {
    childList: true,
    subtree: true,
  });
})();
