{{-- ══════════════════════════════════════════════════════════
     공유 이미지 리뷰 라이트박스
     사용법: openLightbox(src, alt, msgId)
     필요 전역: LB_BASE = rtrim(url("/"), "/")
     ══════════════════════════════════════════════════════════ --}}

@push('styles')
<style>
/* 이미지 리뷰 라이트박스 */
#img-lightbox{display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.82);backdrop-filter:blur(4px);align-items:center;justify-content:center;}
#img-lightbox.open{display:flex;}
@keyframes lb-in{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}
#img-lb-container{display:flex;flex-direction:column;width:min(1100px,96vw);height:min(82vh,820px);background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.45);animation:lb-in .18s ease;}
#img-lb-toolbar{display:flex;align-items:center;gap:4px;padding:0 14px;height:42px;background:rgba(12,9,26,.98);flex-shrink:0;border-bottom:1px solid rgba(255,255,255,.06);border-radius:16px 16px 0 0;}
#img-lb-body{display:flex;flex:1;min-height:0;overflow:hidden;}
#img-lb-ann-svg{position:absolute;z-index:20;pointer-events:none;overflow:visible;}
.lb-ann-tool-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:6px;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s,border-color .15s;padding:0;flex-shrink:0;}
.lb-ann-tool-btn:hover{background:rgba(196,181,253,.15);color:#c4b5fd;}
.lb-ann-tool-btn.active{background:rgba(196,181,253,.28);color:#c4b5fd;border-color:rgba(196,181,253,.45);}
.lb-ann-item{cursor:default;}
.lb-ann-item[data-can-delete="1"]{cursor:pointer;}
#img-lb-image-side{flex:1;background:#111;display:flex;flex-direction:column;position:relative;overflow:hidden;min-width:0;}
#img-lb-scroll-wrap{flex:1;overflow:auto;min-height:0;cursor:grab;user-select:none;scrollbar-width:thin;scrollbar-color:#444 #111;}
#img-lb-inner{display:flex;align-items:flex-start;min-height:100%;min-width:100%;padding:20px;box-sizing:border-box;}
#img-lightbox-img{display:block;margin:auto;max-width:100%;max-height:100%;object-fit:contain;}
#img-lb-zoom-bar{display:flex;align-items:center;justify-content:center;gap:8px;padding:6px 14px;background:#0d0d14;flex-shrink:0;border-top:1px solid rgba(255,255,255,.06);}
.lb-zoom-btn{padding:4px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:13px;cursor:pointer;line-height:1;transition:background .15s;}
.lb-zoom-btn:hover{background:rgba(255,255,255,.13);}
#lb-img-zoom-label{font-size:11px;color:#9ca3af;min-width:38px;text-align:center;}
#img-lightbox-name{position:absolute;bottom:44px;left:0;right:0;text-align:center;color:rgba(255,255,255,.5);font-size:11px;pointer-events:none;}
#img-lb-close{background:rgba(255,255,255,.12);border:none;border-radius:7px;height:28px;padding:0 10px;color:rgba(255,255,255,.8);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background .15s;flex-shrink:0;}
#img-lb-close:hover{background:rgba(255,255,255,.22);color:#fff;}
#img-lb-review{width:300px;min-width:300px;display:flex;flex-direction:column;border-left:1px solid #e4e4e7;transition:width .22s ease,min-width .22s ease,border-left-width .22s ease;overflow:hidden;}
#img-lb-review.lb-collapsed{width:0;min-width:0;border-left-width:0;}
#lb-review-tab{position:absolute;right:0;top:50%;transform:translateY(-50%);z-index:25;width:22px;height:64px;background:linear-gradient(180deg,var(--t400),var(--t600));border:none;border-radius:6px 0 0 6px;color:#fff;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:-2px 0 10px rgba(0,0,0,.25);transition:width .15s,background .15s;line-height:1;}
#lb-review-tab:hover{width:28px;background:linear-gradient(180deg,var(--t300),var(--t500));}
#img-lb-review-hdr{padding:12px 14px 10px;border-bottom:1px solid #f1f5f9;flex-shrink:0;display:flex;align-items:center;gap:6px;}
#lb-review-collapse-btn{margin-left:auto;background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:13px;padding:2px 4px;line-height:1;border-radius:4px;transition:color .12s,background .12s;}
#lb-review-collapse-btn:hover{color:#374151;background:#f1f5f9;}
#img-lb-review-title{font-size:13px;font-weight:700;color:#1e1b2e;}
#img-lb-comment-count{font-size:11px;color:#a1a1aa;font-weight:500;}
#img-lb-comments{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin;scrollbar-color:var(--t100) transparent;}
#img-lb-comments::-webkit-scrollbar{width:4px;}
#img-lb-comments::-webkit-scrollbar-thumb{background:var(--t200);border-radius:4px;}
.lb-comment{background:#f8fafc;border-radius:10px;padding:9px 11px;font-size:12px;border-left:3px solid var(--t200);}
.lb-comment.mine{background:#f5f3ff;border-left-color:var(--t400);}
.lb-comment-name{font-weight:700;color:#1e1b2e;font-size:11.5px;margin-bottom:3px;display:flex;justify-content:space-between;align-items:center;}
.lb-comment-time{font-size:10px;color:#a1a1aa;font-weight:400;}
.lb-comment-body{color:#3f3f46;line-height:1.55;white-space:pre-wrap;}
.lb-del-btn{background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;padding:0 2px;line-height:1;transition:color .1s;}
.lb-del-btn:hover{color:#ef4444;}
#img-lb-review-form{padding:10px 12px;border-top:1px solid #f1f5f9;flex-shrink:0;display:flex;flex-direction:column;gap:6px;}
#img-lb-textarea{resize:none;border:1.5px solid var(--t100);border-radius:10px;padding:8px 11px;font-size:12.5px;outline:none;font-family:inherit;max-height:100px;overflow-y:auto;line-height:1.5;transition:border-color .15s;background:#fff;color:#1e1b2e;}
#img-lb-textarea:focus{border-color:var(--t400);}
#img-lb-submit{align-self:flex-end;padding:6px 16px;background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;}
#img-lb-submit:hover{opacity:.85;}
#img-lb-submit:disabled{opacity:.5;cursor:default;}
</style>
@endpush

{{-- 라이트박스 HTML --}}
<div id="img-lightbox" onclick="closeLightbox()">
    <div id="img-lb-container" onclick="event.stopPropagation()">
        <div id="img-lb-toolbar">
            <button id="img-lb-close" onclick="closeLightbox()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('viewer.close') }}
            </button>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 8px;"></div>
            <span style="font-size:10px;font-weight:600;color:#6b7280;letter-spacing:.4px;margin-right:4px;">{{ __('viewer.ann_shapes') }}</span>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 4px;"></div>
            <button id="lb-ann-btn-number" onclick="lbSetAnnTool('number')" title="{{ __('viewer.ann_number') }}" class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.5" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
            <button id="lb-ann-btn-rect"   onclick="lbSetAnnTool('rect')"   title="{{ __('viewer.ann_rect') }}"   class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
            <button id="lb-ann-btn-circle" onclick="lbSetAnnTool('circle')" title="{{ __('viewer.ann_circle') }}" class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
            <button id="lb-ann-btn-line"   onclick="lbSetAnnTool('line')"   title="{{ __('viewer.ann_line') }}"   class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
            <button id="lb-ann-btn-text"   onclick="lbSetAnnTool('text')"   title="{{ __('viewer.ann_text') }}"   class="lb-ann-tool-btn" style="font-size:13px;font-weight:700;line-height:1;">T</button>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 6px;"></div>
            <span style="font-size:10px;color:#6b7280;margin-right:4px;">{{ __('viewer.ann_color') }}</span>
            <button onclick="lbSetAnnColor('#ef4444')" data-lbcolor="#ef4444" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#ef4444;border:none;cursor:pointer;padding:0;outline:2px solid #fff;outline-offset:2px;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#f97316')" data-lbcolor="#f97316" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#f97316;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#eab308')" data-lbcolor="#eab308" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#eab308;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#22c55e')" data-lbcolor="#22c55e" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#22c55e;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#3b82f6')" data-lbcolor="#3b82f6" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#3b82f6;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#a855f7')" data-lbcolor="#a855f7" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#a855f7;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <div style="flex:1;"></div>
            <span style="font-size:10px;color:#4b5563;">{{ __('viewer.ann_hint') }}</span>
        </div>
        <div id="img-lb-body">
            <div id="img-lb-image-side">
                <button id="lb-review-tab" onclick="toggleLbReview()" title="{{ __('viewer.panel_toggle_title') }}">
                    <svg id="lb-review-tab-icon" width="12" height="12" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div id="img-lb-scroll-wrap">
                    <div id="img-lb-inner">
                        <img id="img-lightbox-img" src="" alt="">
                    </div>
                </div>
                <div id="img-lb-zoom-bar">
                    <button class="lb-zoom-btn" onclick="lbImgZoom(-0.25)">−</button>
                    <span id="lb-img-zoom-label">{{ __('viewer.zoom_fit') }}</span>
                    <button class="lb-zoom-btn" onclick="lbImgZoom(0.25)">+</button>
                    <div style="width:1px;height:14px;background:rgba(255,255,255,.1);margin:0 2px;"></div>
                    <button class="lb-zoom-btn" onclick="lbImgZoomFit()" style="font-size:11px;">{{ __('viewer.zoom_fit') }}</button>
                    <button class="lb-zoom-btn" onclick="lbImgZoomOriginal()" style="font-size:11px;">{{ __('viewer.zoom_original') }}</button>
                </div>
                <svg id="img-lb-ann-svg" xmlns="http://www.w3.org/2000/svg" style="position:absolute;z-index:20;pointer-events:none;overflow:visible;"></svg>
                <span id="img-lightbox-name"></span>
            </div>
            <div id="img-lb-review">
                <div id="img-lb-review-hdr">
                    <span id="img-lb-review-title">{{ __('viewer.review_title') }}</span>
                    <span id="img-lb-comment-count"></span>
                    <button id="lb-review-collapse-btn" onclick="toggleLbReview()" title="{{ __('viewer.panel_collapse_title') }}">◀</button>
                </div>
                <div id="img-lb-comments">
                    <span id="img-lb-empty" style="display:none;">{{ __('viewer.no_opinions') }}</span>
                </div>
                <div id="img-lb-review-form">
                    <textarea id="img-lb-textarea" rows="3" placeholder="{{ __('viewer.opinion_placeholder') }}"
                        onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();submitLbComment();}"></textarea>
                    <button id="img-lb-submit" onclick="submitLbComment()">{{ __('viewer.submit') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 텍스트 주석 입력 팝업 --}}
<div id="lb-ann-text-popup" style="display:none;position:fixed;z-index:10010;background:#fff;border:2px solid #a78bfa;border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);min-width:280px;max-width:360px;">
    <div id="lb-ann-text-popup-title" style="font-size:11px;font-weight:700;color:#6d28d9;margin-bottom:8px;">{{ __('viewer.ann_text_title') }}</div>
    <textarea id="lb-ann-text-input" rows="4" placeholder="{{ __('viewer.ann_text_placeholder') }}"
           style="width:100%;border:1.5px solid #e5e7eb;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;line-height:1.5;font-family:inherit;transition:border-color .15s;"
           onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button onclick="lbConfirmAnnText()" style="flex:1;padding:6px 0;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">{{ __('viewer.ann_confirm') }}</button>
        <button onclick="lbCancelAnnText()" style="flex:1;padding:6px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">{{ __('viewer.ann_cancel') }}</button>
    </div>
</div>

@once
@push('scripts')
<script>
(async function() {
if (typeof window._lbLoaded !== 'undefined') return;
window._lbLoaded = true;

const _LB_BASE = '{{ rtrim(url("/"), "/") }}';
function escA(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

const LB_STR = {
    loading:               '{{ __("viewer.loading") }}',
    no_opinions:           '{{ __("viewer.no_opinions") }}',
    zoom_fit:              '{{ __("viewer.zoom_fit") }}',
    confirm_delete_opinion:'{{ __("viewer.confirm_delete_opinion") }}',
    confirm_delete_ann:    '{{ __("viewer.confirm_delete_ann") }}',
    ann_text_edit:         '{{ __("viewer.ann_text_edit") }}',
    ann_text_title:        '{{ __("viewer.ann_text_title") }}',
    count_suffix:          '{{ __("viewer.count_suffix") }}',
    delete_title:          '{{ __("viewer.delete_title") }}',
};
let lbMsgId=null;
const _lbRenderedCommentIds=new Set();
let _lbImgNatW=0,_lbImgNatH=0,lbImgScale=1.0,_lbImgFitMode=true,_lbSvgPosRaf=null;

async function lbImgZoom(delta){
    const wrap=document.getElementById('img-lb-scroll-wrap');
    if(!wrap||!_lbImgNatW)return;
    if(_lbImgFitMode){
        _lbImgFitMode=false;
        lbImgScale=Math.min((wrap.clientWidth-40)/_lbImgNatW,(wrap.clientHeight-40)/_lbImgNatH,1);
    }
    lbImgScale=Math.min(8,Math.max(0.1,lbImgScale+delta));
    _applyLbImgZoom();
}
async function lbImgZoomFit(){
    const img=document.getElementById('img-lightbox-img');
    if(!img)return;
    _lbImgFitMode=true;lbImgScale=1.0;
    img.style.width='';img.style.height='';img.style.maxWidth='100%';img.style.maxHeight='100%';
    const label=document.getElementById('lb-img-zoom-label');
    if(label)label.textContent=LB_STR.zoom_fit;
    const wrap=document.getElementById('img-lb-scroll-wrap');
    if(wrap){wrap.scrollLeft=0;wrap.scrollTop=0;wrap.style.cursor='grab';}
    requestAnimationFrame(_lbUpdateSvgPosition);
}
async function lbImgZoomOriginal(){
    if(!_lbImgNatW)return;
    _lbImgFitMode=false;lbImgScale=1.0;_applyLbImgZoom();
}
async function _applyLbImgZoom(){
    const img=document.getElementById('img-lightbox-img');
    const wrap=document.getElementById('img-lb-scroll-wrap');
    const label=document.getElementById('lb-img-zoom-label');
    if(!img||!_lbImgNatW)return;
    const w=Math.round(_lbImgNatW*lbImgScale),h=Math.round(_lbImgNatH*lbImgScale);
    img.style.width=w+'px';img.style.height=h+'px';
    img.style.maxWidth='none';img.style.maxHeight='none';
    if(label)label.textContent=Math.round(lbImgScale*100)+'%';
    if(wrap)wrap.style.cursor='grab';
    requestAnimationFrame(_lbUpdateSvgPosition);
}

// Ctrl+휠 줌 + 드래그 팬
(async function(){
    const gw=()=>document.getElementById('img-lb-scroll-wrap');
    document.addEventListener('wheel',e=>{
        const w=gw();if(!w||!w.contains(e.target)||!e.ctrlKey)return;
        e.preventDefault();lbImgZoom(e.deltaY<0?0.15:-0.15);
    },{passive:false});
    document.addEventListener('scroll',e=>{
        const w=gw();if(!w||e.target!==w)return;
        if(_lbSvgPosRaf)cancelAnimationFrame(_lbSvgPosRaf);
        _lbSvgPosRaf=requestAnimationFrame(()=>{_lbUpdateSvgPosition();_lbSvgPosRaf=null;});
    },true);
    let pan=false,pSX,pSY,pSL,pST;
    document.addEventListener('mousedown',e=>{
        const w=gw();if(!w||!w.contains(e.target))return;
        pan=true;pSX=e.clientX;pSY=e.clientY;pSL=w.scrollLeft;pST=w.scrollTop;
        w.style.cursor='grabbing';e.preventDefault();
    });
    document.addEventListener('mousemove',e=>{
        if(!pan)return;const w=gw();if(!w)return;
        w.scrollLeft=pSL-(e.clientX-pSX);w.scrollTop=pST-(e.clientY-pSY);
    });
    document.addEventListener('mouseup',()=>{if(!pan)return;pan=false;const w=gw();if(w)w.style.cursor='grab';});
    document.addEventListener('mouseleave',()=>{if(!pan)return;pan=false;const w=gw();if(w)w.style.cursor='grab';});
})();

// 패널 토글
let _lbReviewCollapsed=false;
async function _setLbReviewTabDir(c){
    const icon=document.getElementById('lb-review-tab-icon');
    if(icon)icon.querySelector('path').setAttribute('d',c?'M9 18l6-6-6-6':'M15 18l-6-6 6-6');
    const btn=document.getElementById('lb-review-collapse-btn');
    if(btn)btn.textContent=c?'▶':'◀';
}
window.toggleLbReview=async function(){
    _lbReviewCollapsed=!_lbReviewCollapsed;
    document.getElementById('img-lb-review').classList.toggle('lb-collapsed',_lbReviewCollapsed);
    _setLbReviewTabDir(_lbReviewCollapsed);
    setTimeout(()=>requestAnimationFrame(_lbUpdateSvgPosition),230);
};

// 주석 상태
let lbAnnTool=null,lbAnnColor='#ef4444',lbAnnNextNum=1,lbAnnList=[],lbAnnSelected=null;
let lbAnnDrawing=false,lbAnnStartX=0,lbAnnStartY=0,lbAnnDragEl=null;
let lbAnnMoveActive=false,lbAnnMoveStartX=0,lbAnnMoveStartY=0,lbAnnMoveStartData=null;
let _lbAnnTextPct=null,_lbAnnEditId=null;

async function _lbCalcNextNum(list){
    const nums=list.filter(a=>a.type==='number').map(a=>a.data?.n??0);
    return nums.length?Math.max(...nums)+1:1;
}
async function _lbResetAnnState(){
    lbAnnTool=null;lbAnnSelected=null;lbAnnList=[];lbAnnDrawing=false;lbAnnMoveActive=false;
    if(lbAnnDragEl){lbAnnDragEl.remove();lbAnnDragEl=null;}
    _lbAnnTextPct=null;_lbAnnEditId=null;
    _lbImgNatW=0;_lbImgNatH=0;lbImgScale=1.0;_lbImgFitMode=true;
    const svg=document.getElementById('img-lb-ann-svg');
    if(svg){
        svg.querySelectorAll('.lb-ann-item,#lb-ann-sel-overlay').forEach(el=>el.remove());
        svg.style.cssText='position:absolute;z-index:20;pointer-events:none;overflow:visible;';
    }
    document.querySelectorAll('.lb-ann-tool-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('lb-ann-text-popup').style.display='none';
    lbImgZoomFit();
}

window.openLightbox=async function(src,alt,msgId){
    lbMsgId=msgId||null;
    _lbResetAnnState();
    document.getElementById('img-lightbox').classList.add('open');
    document.body.style.overflow='hidden';
    const img=document.getElementById('img-lightbox-img');
    img.onload=()=>{_lbImgNatW=img.naturalWidth;_lbImgNatH=img.naturalHeight;requestAnimationFrame(_lbUpdateSvgPosition);};
    img.src=src;img.alt=alt||'';
    if(img.complete&&img.naturalWidth){_lbImgNatW=img.naturalWidth;_lbImgNatH=img.naturalHeight;requestAnimationFrame(_lbUpdateSvgPosition);}
    document.getElementById('img-lightbox-name').textContent=alt||'';
    document.getElementById('img-lb-comments').innerHTML=`<span style="color:#a1a1aa;font-size:12px;text-align:center;margin:auto;">${LB_STR.loading}</span>`;
    document.getElementById('img-lb-comment-count').textContent='';
    document.getElementById('img-lb-textarea').value='';
    if(msgId){loadLbComments(msgId);lbLoadAnnotations(msgId);}
};

window.closeLightbox=async function(){
    document.getElementById('img-lightbox').classList.remove('open');
    document.body.style.overflow='';
    lbMsgId=null;_lbRenderedCommentIds.clear();_lbResetAnnState();
    if(_lbReviewCollapsed){
        _lbReviewCollapsed=false;
        document.getElementById('img-lb-review').classList.remove('lb-collapsed');
        _setLbReviewTabDir(false);
    }
};

async function loadLbComments(msgId){
    fetch(`${_LB_BASE}/messages/${msgId}/image-comments`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(data=>{if(!data.ok)return;renderLbComments(data.comments);});
}
async function renderLbComments(comments){
    _lbRenderedCommentIds.clear();
    const list=document.getElementById('img-lb-comments');
    const count=document.getElementById('img-lb-comment-count');
    list.innerHTML='';count.textContent=comments.length?`${comments.length}${LB_STR.count_suffix}`:'';
    if(!comments.length){list.innerHTML=`<span style="color:#a1a1aa;font-size:12px;text-align:center;margin:auto;">${LB_STR.no_opinions}</span>`;return;}
    comments.forEach(c=>{_lbRenderedCommentIds.add(c.id);list.appendChild(makeLbComment(c));});
    list.scrollTop=list.scrollHeight;
}
async function makeLbComment(c){
    const div=document.createElement('div');
    div.className='lb-comment'+(c.is_mine?' mine':'');div.dataset.commentId=c.id;
    const delBtn=c.is_mine?`<button class="lb-del-btn" onclick="deleteLbComment(${lbMsgId},${c.id},this)" title="${LB_STR.delete_title}">&times;</button>`:'';
    div.innerHTML=`<div class="lb-comment-name"><span>${escA(c.user_name)}</span><span style="display:flex;align-items:center;gap:4px;"><span class="lb-comment-time">${escA(c.created_at)}</span>${delBtn}</span></div><div class="lb-comment-body">${escA(c.content)}</div>`;
    return div;
}
async function appendLbComment(c){
    if(_lbRenderedCommentIds.has(c.id))return;_lbRenderedCommentIds.add(c.id);
    const list=document.getElementById('img-lb-comments');
    const empty=list.querySelector('span');if(empty)empty.remove();
    list.appendChild(makeLbComment(c));list.scrollTop=list.scrollHeight;
    const count=document.getElementById('img-lb-comment-count');
    count.textContent=`${list.querySelectorAll('.lb-comment').length}${LB_STR.count_suffix}`;
}
window.submitLbComment=async function(){
    if(!lbMsgId)return;
    const ta=document.getElementById('img-lb-textarea');
    const btn=document.getElementById('img-lb-submit');
    const content=ta.value.trim();if(!content)return;
    btn.disabled=true;
    fetch(`${_LB_BASE}/messages/${lbMsgId}/image-comments`,{
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify({content})
    }).then(r=>r.json()).then(data=>{
        if(!data.ok)return;
        ta.value='';appendLbComment(data.comment);
        window.dispatchEvent(new CustomEvent('lbCommentAdded',{detail:{msgId:lbMsgId,comment:data.comment}}));
    })
    .finally(()=>{btn.disabled=false;ta.focus();});
};
window.deleteLbComment=async function(msgId,commentId,btn){
    if(!await __confirm(LB_STR.confirm_delete_opinion))return;
    fetch(`${_LB_BASE}/messages/${msgId}/image-comments/${commentId}`,{
        method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'}
    }).then(r=>r.json()).then(data=>{
        if(!data.ok)return;
        const item=btn.closest('.lb-comment');item.remove();
        const list=document.getElementById('img-lb-comments');
        const count=document.getElementById('img-lb-comment-count');
        const n=list.querySelectorAll('.lb-comment').length;
        count.textContent=n?`${n}${LB_STR.count_suffix}`:'';
        if(!n)list.innerHTML=`<span style="color:#a1a1aa;font-size:12px;text-align:center;margin:auto;">${LB_STR.no_opinions}</span>`;
        window.dispatchEvent(new CustomEvent('lbCommentDeleted',{detail:{msgId:msgId,commentId:commentId}}));
    });
};

// SVG 위치 동기화
async function _lbUpdateSvgPosition(){
    const svg=document.getElementById('img-lb-ann-svg');
    const img=document.getElementById('img-lightbox-img');
    const side=document.getElementById('img-lb-image-side');
    if(!svg||!img||!side)return;
    const vr=side.getBoundingClientRect(),ir=img.getBoundingClientRect();
    if(!ir.width||!ir.height)return;
    svg.style.left=`${ir.left-vr.left}px`;svg.style.top=`${ir.top-vr.top}px`;
    svg.style.right='auto';svg.style.bottom='auto';
    svg.style.width=`${ir.width}px`;svg.style.height=`${ir.height}px`;
}

// 주석 도구
window.lbSetAnnTool=async function(tool){
    lbAnnTool=(lbAnnTool===tool)?null:tool;
    if(lbAnnTool)lbSelectAnnotation(null);
    document.querySelectorAll('.lb-ann-tool-btn').forEach(btn=>{
        btn.classList.toggle('active',btn.id.replace('lb-ann-btn-','')===lbAnnTool);
    });
    const svg=document.getElementById('img-lb-ann-svg');
    if(svg){svg.style.pointerEvents=(lbAnnTool||lbAnnSelected)?'all':'none';svg.style.cursor=lbAnnTool?'crosshair':'default';}
};
window.lbSetAnnColor=async function(c){
    lbAnnColor=c;
    document.querySelectorAll('.lb-ann-color-btn').forEach(btn=>{
        btn.style.outline=(btn.dataset.lbcolor===c)?'2px solid #fff':'none';btn.style.outlineOffset='2px';
    });
};
function _getLbSvgPct(svg,e){
    const r=svg.getBoundingClientRect();
    return{x:Math.max(0,Math.min(100,(e.clientX-r.left)/r.width*100)),y:Math.max(0,Math.min(100,(e.clientY-r.top)/r.height*100))};
}
function _makeLbTempEl(type){
    const ns='http://www.w3.org/2000/svg';
    if(type==='rect'){const el=document.createElementNS(ns,'rect');el.setAttribute('fill','none');el.setAttribute('stroke',lbAnnColor);el.setAttribute('stroke-width','2.5');el.setAttribute('stroke-dasharray','5 3');return el;}
    if(type==='circle'){const el=document.createElementNS(ns,'ellipse');el.setAttribute('fill','none');el.setAttribute('stroke',lbAnnColor);el.setAttribute('stroke-width','2.5');el.setAttribute('stroke-dasharray','5 3');return el;}
    if(type==='line'){const el=document.createElementNS(ns,'line');el.setAttribute('stroke',lbAnnColor);el.setAttribute('stroke-width','2.5');el.setAttribute('stroke-linecap','round');return el;}
    return null;
}
function _updateLbTempEl(el,type,x1,y1,x2,y2){
    if(type==='rect'){el.setAttribute('x',`${Math.min(x1,x2)}%`);el.setAttribute('y',`${Math.min(y1,y2)}%`);el.setAttribute('width',`${Math.abs(x2-x1)}%`);el.setAttribute('height',`${Math.abs(y2-y1)}%`);}
    else if(type==='circle'){el.setAttribute('cx',`${(x1+x2)/2}%`);el.setAttribute('cy',`${(y1+y2)/2}%`);el.setAttribute('rx',`${Math.abs(x2-x1)/2}%`);el.setAttribute('ry',`${Math.abs(y2-y1)/2}%`);}
    else if(type==='line'){el.setAttribute('x1',`${x1}%`);el.setAttribute('y1',`${y1}%`);el.setAttribute('x2',`${x2}%`);el.setAttribute('y2',`${y2}%`);}
}

// SVG 마우스 이벤트
(async function(){
    function getSvg(){return document.getElementById('img-lb-ann-svg');}
    document.addEventListener('mousedown',e=>{
        const svg=getSvg();if(!svg)return;
        if(e.target!==svg&&!svg.contains(e.target))return;
        const pct=_getLbSvgPct(svg,e);
        if(lbAnnTool){
            if(e.target.id==='lb-ann-sel-overlay'||(e.target.closest&&e.target.closest('#lb-ann-sel-overlay')))return;
            e.preventDefault();
            if(lbAnnTool==='number'){lbSaveAnnotation('number',{x:pct.x,y:pct.y,n:lbAnnNextNum,color:lbAnnColor});lbSetAnnTool(null);return;}
            if(lbAnnTool==='text'){
                _lbAnnTextPct=pct;
                const popup=document.getElementById('lb-ann-text-popup');
                popup.style.left=`${Math.min(e.clientX,window.innerWidth-380)}px`;
                popup.style.top=`${Math.min(e.clientY,window.innerHeight-210)}px`;
                popup.style.display='block';
                setTimeout(()=>document.getElementById('lb-ann-text-input').focus(),50);return;
            }
            lbAnnDrawing=true;lbAnnStartX=pct.x;lbAnnStartY=pct.y;
            lbAnnDragEl=_makeLbTempEl(lbAnnTool);if(lbAnnDragEl)svg.appendChild(lbAnnDragEl);return;
        }
        if(lbAnnSelected){
            if(e.target.closest&&e.target.closest('#lb-ann-sel-overlay'))return;
            if(_findLbAnnGroup(e.target))return;
            lbSelectAnnotation(null);
        }
    });
    document.addEventListener('mousemove',e=>{
        const svg=getSvg();if(!svg)return;
        if(lbAnnDrawing&&lbAnnDragEl){e.preventDefault();const pct=_getLbSvgPct(svg,e);_updateLbTempEl(lbAnnDragEl,lbAnnTool,lbAnnStartX,lbAnnStartY,pct.x,pct.y);return;}
        if(lbAnnMoveActive&&lbAnnSelected){e.preventDefault();const pct=_getLbSvgPct(svg,e);_directMoveLbAnn(lbAnnSelected.type,lbAnnMoveStartData,pct.x-lbAnnMoveStartX,pct.y-lbAnnMoveStartY);}
    });
    async function finish(e){
        const svg=getSvg();if(!svg)return;
        if(lbAnnDrawing){
            lbAnnDrawing=false;const pct=_getLbSvgPct(svg,e);
            if(lbAnnDragEl){lbAnnDragEl.remove();lbAnnDragEl=null;}
            const dx=pct.x-lbAnnStartX,dy=pct.y-lbAnnStartY;
            if(Math.abs(dx)<0.5&&Math.abs(dy)<0.5)return;
            if(lbAnnTool==='rect')lbSaveAnnotation('rect',{x1:lbAnnStartX,y1:lbAnnStartY,x2:pct.x,y2:pct.y,color:lbAnnColor});
            else if(lbAnnTool==='circle')lbSaveAnnotation('circle',{cx:(lbAnnStartX+pct.x)/2,cy:(lbAnnStartY+pct.y)/2,rx:Math.abs(dx)/2,ry:Math.abs(dy)/2,color:lbAnnColor});
            else if(lbAnnTool==='line')lbSaveAnnotation('line',{x1:lbAnnStartX,y1:lbAnnStartY,x2:pct.x,y2:pct.y,color:lbAnnColor});
            lbSetAnnTool(null);return;
        }
        if(lbAnnMoveActive&&lbAnnSelected){
            lbAnnMoveActive=false;if(svg)svg.style.cursor='default';
            const pct=_getLbSvgPct(svg,e);const dx=pct.x-lbAnnMoveStartX,dy=pct.y-lbAnnMoveStartY;
            if(Math.abs(dx)<0.5&&Math.abs(dy)<0.5){_clearLbSelectionOverlay();_showLbSelectionOverlay(lbAnnSelected.id);return;}
            const newData=lbApplyDelta(lbAnnSelected.type,lbAnnMoveStartData,dx,dy);
            const idx=lbAnnList.findIndex(a=>a.id===lbAnnSelected.id);
            if(idx!==-1){lbAnnList[idx].data=newData;lbAnnSelected=lbAnnList[idx];}
            lbPatchAnnotation(lbAnnSelected.id,newData);lbRenderAnnotations();
        }
    }
    document.addEventListener('mouseup',finish);document.addEventListener('mouseleave',finish);
})();

// 주석 로드/렌더/CRUD
async function lbLoadAnnotations(msgId){
    fetch(`${_LB_BASE}/messages/${msgId}/annotations`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json()).then(data=>{lbAnnList=data.annotations||[];lbAnnNextNum=_lbCalcNextNum(lbAnnList);lbRenderAnnotations();})
    .catch(()=>{});
}
async function lbRenderAnnotations(){
    const svg=document.getElementById('img-lb-ann-svg');if(!svg)return;
    svg.querySelectorAll('.lb-ann-item,#lb-ann-sel-overlay').forEach(el=>el.remove());
    lbAnnList.forEach(a=>_renderLbAnnItem(a,svg));
    if(lbAnnSelected&&lbAnnList.find(a=>a.id===lbAnnSelected.id))_showLbSelectionOverlay(lbAnnSelected.id);
    else if(lbAnnSelected){lbAnnSelected=null;svg.style.pointerEvents=lbAnnTool?'all':'none';}
    requestAnimationFrame(_lbUpdateSvgPosition);
}
async function _renderLbAnnItem(ann,svg){
    const ns='http://www.w3.org/2000/svg',g=document.createElementNS(ns,'g');
    g.classList.add('lb-ann-item');g.dataset.annId=ann.id;
    if(ann.can_delete)g.dataset.canDelete='1';
    const d=ann.data,color=d.color||'#ef4444';
    if(ann.type==='number'){
        const c=document.createElementNS(ns,'circle');c.setAttribute('cx',`${d.x}%`);c.setAttribute('cy',`${d.y}%`);c.setAttribute('r','14');c.setAttribute('fill',color);c.setAttribute('stroke','white');c.setAttribute('stroke-width','1.5');
        const t=document.createElementNS(ns,'text');t.setAttribute('x',`${d.x}%`);t.setAttribute('y',`${d.y}%`);t.setAttribute('text-anchor','middle');t.setAttribute('dominant-baseline','central');t.setAttribute('fill','white');t.setAttribute('font-size','11');t.setAttribute('font-weight','700');t.setAttribute('pointer-events','none');t.textContent=d.n;
        g.appendChild(c);g.appendChild(t);
    }else if(ann.type==='rect'){
        const x=Math.min(d.x1,d.x2),y=Math.min(d.y1,d.y2);
        const r=document.createElementNS(ns,'rect');r.setAttribute('x',`${x}%`);r.setAttribute('y',`${y}%`);r.setAttribute('width',`${Math.abs(d.x2-d.x1)}%`);r.setAttribute('height',`${Math.abs(d.y2-d.y1)}%`);r.setAttribute('fill','rgba(0,0,0,0)');r.setAttribute('stroke',color);r.setAttribute('stroke-width','2.5');g.appendChild(r);
    }else if(ann.type==='circle'){
        const el=document.createElementNS(ns,'ellipse');el.setAttribute('cx',`${d.cx}%`);el.setAttribute('cy',`${d.cy}%`);el.setAttribute('rx',`${d.rx}%`);el.setAttribute('ry',`${d.ry}%`);el.setAttribute('fill','rgba(0,0,0,0)');el.setAttribute('stroke',color);el.setAttribute('stroke-width','2.5');g.appendChild(el);
    }else if(ann.type==='line'){
        const el=document.createElementNS(ns,'line');el.setAttribute('x1',`${d.x1}%`);el.setAttribute('y1',`${d.y1}%`);el.setAttribute('x2',`${d.x2}%`);el.setAttribute('y2',`${d.y2}%`);el.setAttribute('stroke',color);el.setAttribute('stroke-width','2.5');el.setAttribute('stroke-linecap','round');
        const markId=`lb-arr-${ann.id}`;const defs=svg.querySelector('defs')||svg.insertBefore(document.createElementNS(ns,'defs'),svg.firstChild);
        const mk=document.createElementNS(ns,'marker');mk.setAttribute('id',markId);mk.setAttribute('markerWidth','8');mk.setAttribute('markerHeight','6');mk.setAttribute('refX','7');mk.setAttribute('refY','3');mk.setAttribute('orient','auto');
        const poly=document.createElementNS(ns,'polygon');poly.setAttribute('points','0 0, 8 3, 0 6');poly.setAttribute('fill',color);mk.appendChild(poly);defs.appendChild(mk);
        el.setAttribute('marker-end',`url(#${markId})`);g.appendChild(el);
    }else if(ann.type==='text'){
        const el=document.createElementNS(ns,'text');el.setAttribute('x',`${d.x}%`);el.setAttribute('y',`${d.y}%`);el.setAttribute('fill',color);el.setAttribute('font-size','14');el.setAttribute('font-weight','700');el.setAttribute('dominant-baseline','hanging');
        (d.text||'').split('\n').forEach((line,i)=>{const ts=document.createElementNS(ns,'tspan');ts.setAttribute('x',`${d.x}%`);ts.setAttribute('dy',i===0?'0':'1.4em');ts.textContent=line||' ';el.appendChild(ts);});g.appendChild(el);
    }
    if(ann.can_delete){
        g.setAttribute('pointer-events','all');g.style.cursor='grab';
        g.addEventListener('mousedown',e=>{
            if(lbAnnTool)return;e.preventDefault();e.stopPropagation();
            const svgEl=document.getElementById('img-lb-ann-svg'),pct=_getLbSvgPct(svgEl,e);
            lbSelectAnnotation(ann.id);lbAnnMoveActive=true;lbAnnMoveStartX=pct.x;lbAnnMoveStartY=pct.y;
            lbAnnMoveStartData=JSON.parse(JSON.stringify(lbAnnList.find(a=>a.id===ann.id)?.data||ann.data));
            _clearLbSelectionOverlay();svgEl.style.cursor='grabbing';
        });
    }else{g.setAttribute('pointer-events','none');g.style.cursor='default';}
    svg.appendChild(g);
}
async function lbSaveAnnotation(type,data){
    if(!lbMsgId)return;
    fetch(`${_LB_BASE}/messages/${lbMsgId}/annotations`,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({type,data})})
    .then(r=>r.json()).then(resp=>{if(!resp.ok)return;lbAnnList.push(resp.annotation);if(type==='number')lbAnnNextNum=_lbCalcNextNum(lbAnnList);lbRenderAnnotations();}).catch(()=>{});
}
async function lbDeleteAnnotation(id){
    fetch(`${_LB_BASE}/messages/${lbMsgId}/annotations/${id}`,{method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})
    .then(r=>r.json()).then(resp=>{if(!resp.ok)return;lbAnnList=lbAnnList.filter(a=>a.id!==id);lbAnnNextNum=_lbCalcNextNum(lbAnnList);if(lbAnnSelected?.id===id)lbSelectAnnotation(null);lbRenderAnnotations();}).catch(()=>{});
}
async function lbPatchAnnotation(id,data){
    fetch(`${_LB_BASE}/messages/${lbMsgId}/annotations/${id}`,{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({data})}).catch(()=>{});
}
async function lbSelectAnnotation(id){
    lbAnnSelected=id?(lbAnnList.find(a=>a.id===id)||null):null;
    const svg=document.getElementById('img-lb-ann-svg');
    if(svg)svg.style.pointerEvents=(lbAnnSelected||lbAnnTool)?'all':'none';
    _clearLbSelectionOverlay();if(lbAnnSelected)_showLbSelectionOverlay(lbAnnSelected.id);
}
async function _clearLbSelectionOverlay(){document.getElementById('lb-ann-sel-overlay')?.remove();}
async function _showLbSelectionOverlay(annId){
    _clearLbSelectionOverlay();
    const svg=document.getElementById('img-lb-ann-svg');
    const g=svg?.querySelector(`.lb-ann-item[data-ann-id="${annId}"]`);if(!g)return;
    let bbox;try{bbox=g.getBBox();}catch(_){return;}
    const ann=lbAnnList.find(a=>a.id===annId),ns='http://www.w3.org/2000/svg',pad=6;
    const overlay=document.createElementNS(ns,'g');overlay.id='lb-ann-sel-overlay';overlay.style.pointerEvents='none';
    const selRect=document.createElementNS(ns,'rect');
    selRect.setAttribute('x',bbox.x-pad);selRect.setAttribute('y',bbox.y-pad);
    selRect.setAttribute('width',Math.max(bbox.width+pad*2,4));selRect.setAttribute('height',Math.max(bbox.height+pad*2,4));
    selRect.setAttribute('fill','rgba(167,139,250,.08)');selRect.setAttribute('stroke','#a78bfa');selRect.setAttribute('stroke-width','1.5');selRect.setAttribute('stroke-dasharray','5 3');selRect.setAttribute('rx','4');
    overlay.appendChild(selRect);
    if(ann&&ann.type==='text'&&ann.can_delete){
        const editG=document.createElementNS(ns,'g');editG.setAttribute('transform',`translate(${bbox.x+bbox.width+pad+2},${bbox.y-pad-2})`);editG.style.pointerEvents='all';editG.style.cursor='pointer';
        editG.addEventListener('mousedown',e=>e.stopPropagation());
        editG.addEventListener('click',e=>{
            e.stopPropagation();_lbAnnEditId=ann.id;
            document.getElementById('lb-ann-text-popup-title').textContent=LB_STR.ann_text_edit;
            document.getElementById('lb-ann-text-input').value=ann.data.text||'';
            const sr=svg.getBoundingClientRect();const px=sr.left+bbox.x,py=sr.top+bbox.y+bbox.height+4;
            const popup=document.getElementById('lb-ann-text-popup');
            popup.style.left=`${Math.min(px,window.innerWidth-380)}px`;popup.style.top=`${Math.min(py,window.innerHeight-210)}px`;popup.style.display='block';
            setTimeout(()=>{const inp=document.getElementById('lb-ann-text-input');inp.focus();inp.select();},30);
        });
        const editBg=document.createElementNS(ns,'circle');editBg.setAttribute('r','9');editBg.setAttribute('fill','#7c3aed');editBg.setAttribute('stroke','white');editBg.setAttribute('stroke-width','1.5');editG.appendChild(editBg);
        const editTxt=document.createElementNS(ns,'text');editTxt.setAttribute('text-anchor','middle');editTxt.setAttribute('dominant-baseline','central');editTxt.setAttribute('fill','white');editTxt.setAttribute('font-size','11');editTxt.setAttribute('pointer-events','none');editTxt.textContent='✎';editG.appendChild(editTxt);
        overlay.appendChild(editG);
    }
    if(ann&&ann.can_delete){
        const delOffset=(ann.type==='text')?24:0;
        const delG=document.createElementNS(ns,'g');delG.setAttribute('transform',`translate(${bbox.x+bbox.width+pad+2+delOffset},${bbox.y-pad-2})`);delG.style.pointerEvents='all';delG.style.cursor='pointer';
        delG.addEventListener('mousedown',e=>e.stopPropagation());
        delG.addEventListener('click',async e=>{e.stopPropagation();if(!await __confirm(LB_STR.confirm_delete_ann))return;lbDeleteAnnotation(ann.id);});
        const delBg=document.createElementNS(ns,'circle');delBg.setAttribute('r','9');delBg.setAttribute('fill','#ef4444');delBg.setAttribute('stroke','white');delBg.setAttribute('stroke-width','1.5');delG.appendChild(delBg);
        const delTxt=document.createElementNS(ns,'text');delTxt.setAttribute('text-anchor','middle');delTxt.setAttribute('dominant-baseline','central');delTxt.setAttribute('fill','white');delTxt.setAttribute('font-size','13');delTxt.setAttribute('font-weight','700');delTxt.setAttribute('pointer-events','none');delTxt.textContent='×';delG.appendChild(delTxt);
        overlay.appendChild(delG);
    }
    svg.appendChild(overlay);
}
async function _findLbAnnGroup(el){let c=el;const svg=document.getElementById('img-lb-ann-svg');while(c&&c!==svg){if(c.classList&&c.classList.contains('lb-ann-item'))return c;c=c.parentElement;}return null;}
async function lbApplyDelta(type,data,dx,dy){
    const cl=v=>Math.max(0,Math.min(100,v)),d=Object.assign({},data);
    if(type==='number'||type==='text'){d.x=cl(d.x+dx);d.y=cl(d.y+dy);}
    else if(type==='rect'||type==='line'){d.x1=cl(d.x1+dx);d.y1=cl(d.y1+dy);d.x2=cl(d.x2+dx);d.y2=cl(d.y2+dy);}
    else if(type==='circle'){d.cx=cl(d.cx+dx);d.cy=cl(d.cy+dy);}
    return d;
}
async function _directMoveLbAnn(type,base,dx,dy){
    const ann=lbAnnSelected;if(!ann)return;
    const svg=document.getElementById('img-lb-ann-svg');const g=svg?.querySelector(`.lb-ann-item[data-ann-id="${ann.id}"]`);if(!g)return;
    const cl=v=>Math.max(0,Math.min(100,v));
    if(type==='number'){const nx=cl(base.x+dx),ny=cl(base.y+dy);g.querySelector('circle')?.setAttribute('cx',`${nx}%`);g.querySelector('circle')?.setAttribute('cy',`${ny}%`);g.querySelector('text')?.setAttribute('x',`${nx}%`);g.querySelector('text')?.setAttribute('y',`${ny}%`);}
    else if(type==='rect'){const nx1=cl(base.x1+dx),ny1=cl(base.y1+dy),nx2=cl(base.x2+dx),ny2=cl(base.y2+dy);const r=g.querySelector('rect');if(r){r.setAttribute('x',`${Math.min(nx1,nx2)}%`);r.setAttribute('y',`${Math.min(ny1,ny2)}%`);r.setAttribute('width',`${Math.abs(nx2-nx1)}%`);r.setAttribute('height',`${Math.abs(ny2-ny1)}%`);}}
    else if(type==='circle'){const e=g.querySelector('ellipse');if(e){e.setAttribute('cx',`${cl(base.cx+dx)}%`);e.setAttribute('cy',`${cl(base.cy+dy)}%`);}}
    else if(type==='line'){const l=g.querySelector('line');if(l){l.setAttribute('x1',`${cl(base.x1+dx)}%`);l.setAttribute('y1',`${cl(base.y1+dy)}%`);l.setAttribute('x2',`${cl(base.x2+dx)}%`);l.setAttribute('y2',`${cl(base.y2+dy)}%`);}}
    else if(type==='text'){const t=g.querySelector('text');if(t){const nx=`${cl(base.x+dx)}%`,ny=`${cl(base.y+dy)}%`;t.setAttribute('x',nx);t.setAttribute('y',ny);t.querySelectorAll('tspan').forEach(ts=>ts.setAttribute('x',nx));}}
}

// 텍스트 주석 팝업
window.lbConfirmAnnText=async function(){
    const val=document.getElementById('lb-ann-text-input').value.trim();
    document.getElementById('lb-ann-text-popup').style.display='none';
    document.getElementById('lb-ann-text-input').value='';
    document.getElementById('lb-ann-text-popup-title').textContent=LB_STR.ann_text_title;
    if(_lbAnnEditId){
        if(val){const ann=lbAnnList.find(a=>a.id===_lbAnnEditId);if(ann){const newData=Object.assign({},ann.data,{text:val});ann.data=newData;lbPatchAnnotation(_lbAnnEditId,newData);lbRenderAnnotations();}}
        _lbAnnEditId=null;return;
    }
    if(!val||!_lbAnnTextPct){_lbAnnTextPct=null;return;}
    lbSaveAnnotation('text',{x:_lbAnnTextPct.x,y:_lbAnnTextPct.y,text:val,color:lbAnnColor});
    _lbAnnTextPct=null;lbSetAnnTool(null);
};
window.lbCancelAnnText=async function(){
    document.getElementById('lb-ann-text-popup').style.display='none';
    document.getElementById('lb-ann-text-input').value='';
    document.getElementById('lb-ann-text-popup-title').textContent=LB_STR.ann_text_title;
    _lbAnnTextPct=null;_lbAnnEditId=null;
};
document.getElementById('lb-ann-text-input').addEventListener('keydown',e=>{
    if((e.ctrlKey||e.metaKey)&&e.key==='Enter'){e.preventDefault();lbConfirmAnnText();}
    if(e.key==='Escape'){e.preventDefault();lbCancelAnnText();}
});
lbSetAnnColor('#ef4444');
window.lbImgZoom=lbImgZoom;window.lbImgZoomFit=lbImgZoomFit;window.lbImgZoomOriginal=lbImgZoomOriginal;

// ESC 키
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&document.getElementById('img-lightbox')?.classList.contains('open')){closeLightbox();}
});

})();
</script>
@endpush
@endonce
