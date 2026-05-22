{{-- ── 커스텀 다이얼로그 (브라우저 alert/confirm/prompt 대체) ─────── --}}
<div id="__dlg" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(17,12,40,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div id="__dlg-box" style="background:#fff;border-radius:16px;box-shadow:0 24px 80px rgba(0,0,0,.28);min-width:320px;max-width:460px;width:90%;">
        <div style="padding:22px 22px 0;display:flex;gap:12px;align-items:flex-start;">
            <div id="__dlg-icon" style="width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;">
                <div id="__dlg-title" style="font-size:15px;font-weight:700;color:#111827;margin-bottom:5px;line-height:1.4;"></div>
                <div id="__dlg-body" style="font-size:13px;color:#6b7280;line-height:1.65;white-space:pre-wrap;word-break:break-word;"></div>
            </div>
        </div>
        <div id="__dlg-input-wrap" style="display:none;padding:12px 22px 0;">
            <input id="__dlg-input" type="text" autocomplete="off"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1f2937;outline:none;transition:border-color .15s;font-family:inherit;"
                   onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div style="padding:18px 22px;display:flex;justify-content:flex-end;gap:8px;">
            <button id="__dlg-no"  style="display:none;padding:8px 20px;background:#f3f4f6;color:#374151;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:background .14s;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">{{ __('common.cancel') }}</button>
            <button id="__dlg-yes" style="padding:8px 22px;background:#7c3aed;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:background .14s;" onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">{{ __('common.confirm') }}</button>
        </div>
    </div>
</div>
<style>
#__dlg[style*="flex"] > div { animation:__dlgIn .18s ease; }
@keyframes __dlgIn { from{opacity:0;transform:scale(.94) translateY(8px)} to{opacity:1;transform:none} }
</style>
<script>
(function(){
    var ov   = document.getElementById('__dlg');
    var icon = document.getElementById('__dlg-icon');
    var ttl  = document.getElementById('__dlg-title');
    var body = document.getElementById('__dlg-body');
    var iw   = document.getElementById('__dlg-input-wrap');
    var inp  = document.getElementById('__dlg-input');
    var no   = document.getElementById('__dlg-no');
    var yes  = document.getElementById('__dlg-yes');
    var _res = null, _type = '';

    var ICONS = {
        alert:   {bg:'#eff6ff',svg:'<svg width="20" height="20" fill="none" stroke="#3b82f6" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>'},
        confirm: {bg:'#fffbeb',svg:'<svg width="20" height="20" fill="none" stroke="#d97706" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'},
        prompt:  {bg:'#f5f3ff',svg:'<svg width="20" height="20" fill="none" stroke="#7c3aed" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'},
        error:   {bg:'#fef2f2',svg:'<svg width="20" height="20" fill="none" stroke="#dc2626" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-width="2" d="M15 9l-6 6M9 9l6 6"/></svg>'},
        success: {bg:'#f0fdf4',svg:'<svg width="20" height="20" fill="none" stroke="#16a34a" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>'},
    };
    var TITLES = {
        alert:   @json(__('common.dialog_alert')),
        confirm: @json(__('common.dialog_confirm')),
        prompt:  @json(__('common.dialog_prompt')),
        error:   @json(__('common.dialog_error')),
        success: @json(__('common.dialog_success')),
    };

    function open(type, msg, ph) {
        return new Promise(function(resolve) {
            _res = resolve; _type = type;
            var ic = ICONS[type] || ICONS.alert;
            icon.style.background = ic.bg;
            icon.innerHTML = ic.svg;
            ttl.textContent  = TITLES[type] || @json(__('common.dialog_alert'));
            body.textContent = msg || '';
            iw.style.display  = type === 'prompt' ? 'block' : 'none';
            no.style.display  = type === 'alert'  ? 'none'  : 'inline-block';
            ov.style.display  = 'flex';
            if (type === 'prompt') { inp.placeholder = ph||''; inp.value=''; setTimeout(function(){inp.focus();},60); }
            else setTimeout(function(){yes.focus();},60);
        });
    }
    function close(val) {
        ov.style.display = 'none';
        if (_res) { _res(val); _res = null; }
    }

    yes.addEventListener('click', function(){ close(_type==='prompt' ? inp.value : true); });
    no.addEventListener('click',  function(){ close(_type==='prompt' ? null : false); });
    ov.addEventListener('click',  function(e){ if(e.target===ov) close(_type==='prompt'?null:(_type==='alert'?undefined:false)); });
    inp.addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();close(inp.value);} });
    document.addEventListener('keydown', function(e){
        if (ov.style.display!=='flex') return;
        if (e.key==='Enter' && _type!=='prompt') { e.preventDefault(); close(true); }
        if (e.key==='Escape') { e.preventDefault(); close(_type==='prompt'?null:(_type==='alert'?undefined:false)); }
    });

    /* 전역 API */
    window.__alert   = function(msg, type){ return open(type||'alert', msg); };
    window.__confirm = function(msg)      { return open('confirm', msg); };
    window.__prompt  = function(msg, ph)  { return open('prompt', msg, ph); };

    /* window.alert 오버라이드 (호출 측 변경 불필요) */
    window.alert = function(msg){ window.__alert(String(msg==null?'':msg)); };

    /* data-confirm 인터셉터 (onclick="return confirm('...')" 대체) */
    document.addEventListener('click', function(e){
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        e.preventDefault(); e.stopImmediatePropagation();
        var msg  = el.dataset.confirm;
        var form = el.form || el.closest('form');
        window.__confirm(msg).then(function(ok){
            if (!ok) return;
            if (el.tagName==='A') { location.href = el.href; return; }
            if (form && (el.type==='submit'||el.type==='button')) { form.submit(); return; }
            /* 일반 버튼: data-confirm 임시 제거 후 재클릭 */
            var saved = el.dataset.confirm;
            delete el.dataset.confirm;
            el.click();
            el.dataset.confirm = saved;
        });
    }, true);
})();
</script>
