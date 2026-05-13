{{-- Maintenance Panel: FAB + ?쒕줈??step1쨌2) + ?꾩껜?붾㈃ 由щ럭 ?ㅻ쾭?덉씠(step3) --}}
<style>
/* ?? FAB / backdrop / drawer ?????????????????????????????? */
#mn-fab{position:fixed;top:64px;right:0;z-index:900;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px 0 0 8px;padding:8px 12px 8px 10px;cursor:pointer;display:none;align-items:center;gap:6px;font-size:12px;font-weight:700;box-shadow:-3px 4px 16px rgba(109,40,217,.35);transition:transform .15s;font-family:inherit;}
#mn-fab:hover{transform:translateX(-3px);}
#mn-backdrop{position:fixed;inset:0;background:rgba(15,14,26,.4);backdrop-filter:blur(2px);z-index:1000;display:none;}
#mn-backdrop.show{display:block;}
#mn-drawer{position:fixed;top:0;right:-500px;width:480px;height:100vh;background:#fff;z-index:1001;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.18);transition:right .26s cubic-bezier(.4,0,.2,1);overflow:hidden;}
#mn-drawer.open{right:0;}

/* ?? Drawer header / tabs ????????????????????????????????? */
.mn-hdr{display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1.5px solid #ede9ff;flex-shrink:0;background:linear-gradient(135deg,#faf7ff,#f3f0ff);}
.mn-hdr-info{flex:1;min-width:0;}
.mn-hdr-key{font-size:10px;font-weight:700;color:#7c3aed;font-family:monospace;line-height:1.2;}
.mn-hdr-name{font-size:13.5px;font-weight:700;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mn-tabs{display:flex;gap:2px;background:#ede9ff;border-radius:7px;padding:3px;flex-shrink:0;}
.mn-tab-btn{padding:4px 11px;border-radius:5px;font-size:12px;font-weight:600;border:none;background:transparent;color:#6b7280;cursor:pointer;transition:all .12s;white-space:nowrap;}
.mn-tab-btn.active{background:#fff;color:#7c3aed;box-shadow:0 1px 4px rgba(0,0,0,.1);}
.mn-close{width:28px;height:28px;border:none;background:transparent;border-radius:6px;cursor:pointer;color:#6b7280;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.mn-close:hover{background:#f3f4f6;color:#374151;}

/* ?? Drawer body ?????????????????????????????????????????? */
.mn-body{flex:1;overflow:hidden;display:flex;flex-direction:column;}
.mn-tab-body{display:none;flex:1;flex-direction:column;overflow:hidden;}
.mn-tab-body.active{display:flex;}

/* ?? Step indicator ??????????????????????????????????????? */
.mn-steps{display:flex;align-items:center;padding:12px 16px 10px;flex-shrink:0;border-bottom:1px solid #f3f0ff;}
.mn-step-item{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#94a3b8;flex:1;}
.mn-step-item:last-child{flex:none;}
.mn-step-dot{width:22px;height:22px;border-radius:50%;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;background:#fff;flex-shrink:0;transition:all .2s;}
.mn-step-item.active .mn-step-dot{border-color:#7c3aed;background:#7c3aed;color:#fff;}
.mn-step-item.active{color:#7c3aed;}
.mn-step-item.done .mn-step-dot{border-color:#16a34a;background:#16a34a;color:#fff;}
.mn-step-item.done{color:#16a34a;}
.mn-step-line{height:1.5px;flex:1;background:#e5e7eb;margin:0 4px;}

/* ?? Step content ????????????????????????????????????????? */
.mn-step-wrap{flex:1;overflow:hidden;display:flex;flex-direction:column;}
.mn-step-content{display:none;flex:1;flex-direction:column;overflow-y:auto;padding:14px 16px;}
.mn-step-content.active{display:flex;}
.mn-step-content::-webkit-scrollbar{width:4px;}
.mn-step-content::-webkit-scrollbar-thumb{background:#d8b4fe;border-radius:2px;}

/* ?? Form ????????????????????????????????????????????????? */
.mn-label{font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;}
.mn-file-info{background:#f8f7ff;border:1px solid #e8e3ff;border-radius:7px;padding:7px 12px;margin-bottom:12px;font-size:11px;color:#5b21b6;font-family:monospace;display:flex;align-items:center;justify-content:space-between;gap:8px;word-break:break-all;}
.mn-file-info button{background:none;border:none;cursor:pointer;color:#7c3aed;font-size:11px;font-weight:600;white-space:nowrap;flex-shrink:0;}
.mn-textarea{width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1e1b2e;resize:vertical;outline:none;font-family:inherit;transition:border-color .15s;box-sizing:border-box;}
.mn-textarea:focus{border-color:#7c3aed;}
.mn-input{width:100%;padding:7px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12.5px;color:#1e1b2e;outline:none;font-family:inherit;transition:border-color .15s;box-sizing:border-box;}
.mn-input:focus{border-color:#7c3aed;}
.mn-prompt-block{margin-bottom:9px;}
.mn-prompt-key{font-size:10.5px;font-weight:700;color:#7c3aed;font-family:monospace;margin-bottom:3px;}
.mn-reg-box{background:#faf5ff;border:1.5px solid #d8b4fe;border-radius:10px;padding:14px;margin-bottom:14px;}

/* ?? Drawer buttons ??????????????????????????????????????? */
.mn-btn-row{display:flex;gap:8px;padding:10px 16px;border-top:1px solid #f3f0ff;flex-shrink:0;background:#fff;}
.mn-btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .12s;font-family:inherit;}
.mn-btn-primary{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;flex:1;}
.mn-btn-primary:hover{opacity:.88;}
.mn-btn-primary:disabled{opacity:.5;cursor:not-allowed;}
.mn-btn-outline{background:#fff;border:1.5px solid #d8b4fe;color:#7c3aed;}
.mn-btn-outline:hover{background:#faf5ff;}
.mn-btn-danger{background:#fff;border:1.5px solid #fecaca;color:#dc2626;}
.mn-btn-danger:hover{background:#fee2e2;}
.mn-btn-danger:disabled{opacity:.5;cursor:not-allowed;}
.mn-status{font-size:11.5px;color:#6b7280;min-height:14px;text-align:center;padding:0 16px 8px;}
.mn-status.err{color:#dc2626;}
.mn-status.ok{color:#16a34a;}
.mn-summary-box{background:#f8f7ff;border:1px solid #e8e3ff;border-radius:8px;padding:10px 12px;font-size:12.5px;color:#374151;line-height:1.6;}

/* ?? Version list ????????????????????????????????????????? */
.mn-ver-scroll{flex:1;overflow-y:auto;padding:12px 16px;}
.mn-ver-scroll::-webkit-scrollbar{width:4px;}
.mn-ver-scroll::-webkit-scrollbar-thumb{background:#d8b4fe;border-radius:2px;}
.mn-version-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #f0edff;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:all .12s;}
.mn-version-item:hover{border-color:#c4b5fd;background:#faf5ff;}
.mn-version-badge{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border-radius:6px;padding:3px 8px;font-size:10px;font-weight:700;flex-shrink:0;}
.mn-version-info{flex:1;min-width:0;}
.mn-version-summary{font-size:12.5px;font-weight:600;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mn-version-meta{font-size:11px;color:#94a3b8;margin-top:2px;}
.mn-empty{text-align:center;padding:48px 20px;color:#94a3b8;font-size:13px;}

/* ?? Spinner ?????????????????????????????????????????????? */
.mn-spinner{display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:mn-spin .7s linear infinite;vertical-align:middle;}
@keyframes mn-spin{to{transform:rotate(360deg);}}

/* ?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧
   由щ럭 紐⑤떖 ?앹뾽
   ?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧 */
#mn-review{position:fixed;inset:0;z-index:1500;display:none;align-items:center;justify-content:center;background:rgba(15,14,26,.6);backdrop-filter:blur(4px);font-family:inherit;}
#mn-review.show{display:flex;}
#mn-rv-inner{background:#fff;border-radius:16px;width:92vw;max-width:1280px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.35);}

/* 由щ럭 ?ㅻ뜑 */
#mn-rv-hdr{display:flex;align-items:center;gap:12px;padding:0 20px;height:56px;background:#fff;border-bottom:1.5px solid #ede9ff;flex-shrink:0;box-shadow:0 2px 8px rgba(109,40,217,.06);}
#mn-rv-hdr .rv-icon{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rv-title{font-size:15px;font-weight:800;color:#1e1b2e;}
.rv-key{font-size:11px;font-weight:700;color:#7c3aed;font-family:monospace;background:#f3f0ff;padding:2px 7px;border-radius:5px;}
.rv-spacer{flex:1;}
#mn-rv-status{font-size:12px;color:#6b7280;}
#mn-rv-status.err{color:#dc2626;}
#mn-rv-status.ok{color:#16a34a;}
.rv-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:all .12s;font-family:inherit;}
.rv-btn-back{background:#fff;border:1.5px solid #d8b4fe;color:#7c3aed;}
.rv-btn-back:hover{background:#faf5ff;}
.rv-btn-apply{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;padding:7px 22px;}
.rv-btn-apply:hover{opacity:.88;}
.rv-btn-apply:disabled{opacity:.5;cursor:not-allowed;}
.rv-btn-preview{background:#fff;border:1.5px solid #d8b4fe;color:#7c3aed;}
.rv-btn-preview:hover{background:#faf5ff;}
.rv-btn-preview:disabled{opacity:.5;cursor:not-allowed;}

/* 由щ럭 諛붾뵒 */
#mn-rv-body{display:flex;flex:1;overflow:hidden;}

/* ?쇱そ ?ъ씠?쒕컮 */
#mn-rv-side{width:260px;background:#fff;border-right:1.5px solid #ede9ff;display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto;}
#mn-rv-side::-webkit-scrollbar{width:4px;}
#mn-rv-side::-webkit-scrollbar-thumb{background:#d8b4fe;border-radius:2px;}
.rv-side-section{padding:14px 16px;border-bottom:1px solid #f3f0ff;}
.rv-side-label{font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.rv-summary-text{font-size:12.5px;color:#374151;line-height:1.65;}
.rv-file-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;cursor:pointer;transition:all .12s;margin-bottom:3px;}
.rv-file-item:hover{background:#f5f3ff;}
.rv-file-item.active{background:#f0ebff;color:#6d28d9;}
.rv-file-badge{font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:4px;background:#ede9ff;color:#7c3aed;flex-shrink:0;}
.rv-file-name{font-size:12px;font-weight:600;color:#374151;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.rv-file-item.active .rv-file-name{color:#6d28d9;}
.rv-stats{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;}
.rv-stat{font-size:11px;font-weight:600;padding:2px 8px;border-radius:5px;}
.rv-stat-add{background:rgba(34,197,94,.12);color:#16a34a;}
.rv-stat-del{background:rgba(239,68,68,.12);color:#dc2626;}

/* ?ㅻⅨ履?肄붾뱶 ?곸뿭 */
#mn-rv-main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#1a1625;}

/* 酉??꾪솚 ??*/
#mn-rv-tabs{display:flex;gap:2px;background:#0f0e1a;padding:8px 12px;flex-shrink:0;border-bottom:1px solid #2d2a42;}
.rv-view-tab{padding:5px 14px;border-radius:6px;font-size:12px;font-weight:600;border:none;background:transparent;color:#64748b;cursor:pointer;transition:all .12s;font-family:inherit;}
.rv-view-tab.active{background:#2d2a42;color:#e2e8f0;}
.rv-view-tab:hover:not(.active){color:#94a3b8;}

/* 肄붾뱶 酉곗뼱 */
#mn-rv-code-wrap{flex:1;overflow:auto;position:relative;}
#mn-rv-code-wrap::-webkit-scrollbar{width:8px;height:8px;}
#mn-rv-code-wrap::-webkit-scrollbar-track{background:#0f0e1a;}
#mn-rv-code-wrap::-webkit-scrollbar-thumb{background:#3d3761;border-radius:4px;}
.rv-code-table{width:100%;border-collapse:collapse;font-family:'Consolas','Monaco','Courier New',monospace;font-size:12.5px;line-height:1.65;}
.rv-ln{width:1%;white-space:nowrap;padding:0 12px 0 16px;text-align:right;color:#3d3761;user-select:none;border-right:1px solid #2d2a42;}
.rv-lc{padding:0 16px;white-space:pre;color:#c9d1d9;width:100%;}
.rv-row-add{background:rgba(34,197,94,.1);}
.rv-row-add .rv-lc{color:#7ee787;}
.rv-row-add .rv-ln{background:rgba(34,197,94,.06);color:#4ade80;}
.rv-row-del{background:rgba(239,68,68,.1);}
.rv-row-del .rv-lc{color:#ffa198;}
.rv-row-del .rv-ln{background:rgba(239,68,68,.06);color:#f87171;}
.rv-row-ctx .rv-lc{color:#6e7681;}
.rv-row-ctx .rv-ln{color:#2d2a42;}
</style>

{{-- FAB --}}
<button id="mn-fab" title="{{ __('maintenance.maintenance_panel') }}">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
    {{ __('maintenance.maintenance_panel') }}
</button>

{{-- Backdrop --}}
<div id="mn-backdrop"></div>

{{-- ?먥븧 Drawer ?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧 --}}
<div id="mn-drawer">
    <div class="mn-hdr">
        <div class="mn-hdr-info">
            <div class="mn-hdr-key" id="mn-hdr-key"></div>
            <div class="mn-hdr-name" id="mn-hdr-name">{{ __('maintenance.maintenance_panel') }}</div>
        </div>
        <div class="mn-tabs">
            <button class="mn-tab-btn active" onclick="mnSetTab('workflow')">{{ __('maintenance.tab_edit') }}</button>
            <button class="mn-tab-btn" onclick="mnSetTab('versions')">{{ __('maintenance.tab_versions') }}<span id="mn-ver-badge" style="margin-left:4px;background:#ede9ff;color:#7c3aed;border-radius:10px;padding:0 5px;font-size:10px;display:none;"></span></button>
        </div>
        <button class="mn-close" onclick="mnClose()">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="mn-body">
        {{-- ?섏젙 ??--}}
        <div class="mn-tab-body active" id="mn-tab-workflow">
            <div class="mn-steps">
                <div class="mn-step-item active" id="mn-sind-1"><div class="mn-step-dot">1</div><span>{{ __('maintenance.step_request') }}</span></div>
                <div class="mn-step-line"></div>
                <div class="mn-step-item" id="mn-sind-2"><div class="mn-step-dot">2</div><span>{{ __('maintenance.step_ai_analysis') }}</span></div>
                <div class="mn-step-line"></div>
                <div class="mn-step-item" id="mn-sind-3"><div class="mn-step-dot">3</div><span>{{ __('maintenance.step_review_apply') }}</span></div>
            </div>

            <div class="mn-step-wrap">
                {{-- Step 1: ?붿껌 --}}
                <div class="mn-step-content active" id="mn-sc-1">
                    <div id="mn-reg-box" class="mn-reg-box" style="display:none;">
                        <div style="font-size:12.5px;font-weight:700;color:#6d28d9;margin-bottom:10px;">{{ __('maintenance.register_screen') }}</div>
                        <div class="mn-label">{{ __('maintenance.screen_name') }}</div>
                        <input type="text" class="mn-input" id="mn-reg-name" style="margin-bottom:8px;">
                        <input type="hidden" id="mn-reg-blade">
                        <button class="mn-btn mn-btn-primary" onclick="mnRegister()" style="width:100%;margin-top:4px;">{{ __('maintenance.register_btn') }}</button>
                        <div class="mn-status" id="mn-reg-status" style="padding:6px 0 0;text-align:left;"></div>
                    </div>
                    <div id="mn-req-box">
                        <span id="mn-file-path-txt" style="display:none;"></span>
                        <div class="mn-label">{{ __('maintenance.request_label') }} <span style="color:#ef4444;">*</span></div>
                        <textarea class="mn-textarea" id="mn-request" rows="7" placeholder="{{ __('maintenance.request_placeholder') }}"></textarea>
                    </div>
                </div>

                {{-- Step 2: 웍스 遺꾩꽍 --}}
                <div class="mn-step-content" id="mn-sc-2">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;flex-shrink:0;">{{ __('maintenance.step_ai_analysis') }}</div>
                    <div style="background:#f8f7ff;border:1px solid #e8e3ff;border-radius:8px;padding:9px 12px;margin-bottom:12px;flex-shrink:0;">
                        <div style="font-size:10px;font-weight:700;color:#94a3b8;margin-bottom:4px;">{{ __('maintenance.original_request') }}</div>
                        <div id="mn-p-req-display" style="font-size:12.5px;color:#374151;line-height:1.6;white-space:pre-wrap;"></div>
                    </div>
                    <div class="mn-prompt-block"><div class="mn-prompt-key">goal</div><input type="text" class="mn-input" id="mn-p-goal"></div>
                    <div class="mn-prompt-block" style="display:none;"><div class="mn-prompt-key">role</div><input type="text" class="mn-input" id="mn-p-role"></div>
                    <div class="mn-prompt-block" style="display:none;"><div class="mn-prompt-key">input</div><textarea class="mn-textarea" id="mn-p-input" rows="2"></textarea></div>
                    <div class="mn-prompt-block" style="display:none;"><div class="mn-prompt-key">constraints</div><textarea class="mn-textarea" id="mn-p-constraints" rows="2"></textarea></div>
                    <div class="mn-prompt-block" style="display:none;"><div class="mn-prompt-key">output_format</div><input type="text" class="mn-input" id="mn-p-output-format"></div>
                    <div class="mn-prompt-block"><div class="mn-prompt-key">refined_prompt <span style="color:#94a3b8;font-size:9px;">{{ __('maintenance.refined_prompt_hint') }}</span></div><textarea class="mn-textarea" id="mn-p-refined" rows="6"></textarea></div>
                </div>
            </div>

            <div class="mn-status" id="mn-wf-status"></div>
            <div class="mn-btn-row" id="mn-btn-row-1">
                <button class="mn-btn mn-btn-primary" id="mn-btn-gen-prompt" onclick="mnGeneratePrompt()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    {{ __('maintenance.ai_gen_prompt') }}
                </button>
            </div>
            <div class="mn-btn-row" id="mn-btn-row-2" style="display:none;">
                <button class="mn-btn mn-btn-outline" onclick="mnGoStep(1)">{{ __('maintenance.go_back') }}</button>
                <button class="mn-btn mn-btn-primary" id="mn-btn-gen-patch" onclick="mnGeneratePatch()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                    {{ __('maintenance.ai_gen_patch') }}
                </button>
            </div>
        </div>

        {{-- 踰꾩쟾 ??--}}
        <div class="mn-tab-body" id="mn-tab-versions">
            <div id="mn-ver-list-view" style="flex:1;overflow:hidden;display:flex;flex-direction:column;">
                <div class="mn-ver-scroll" id="mn-ver-list"></div>
            </div>
            <div id="mn-ver-detail-view" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
                <div style="padding:12px 16px 0;flex-shrink:0;">
                    <button onclick="mnVerBack()" style="display:flex;align-items:center;gap:4px;background:none;border:none;color:#7c3aed;font-size:12px;font-weight:600;cursor:pointer;padding:0;margin-bottom:10px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        {{ __('maintenance.version_list') }}
                    </button>
                    <div class="mn-summary-box" id="mn-ver-detail-summary" style="margin-bottom:10px;"></div>
                    <div style="display:flex;gap:2px;background:#f3f0ff;border-radius:6px;padding:2px;margin-bottom:8px;">
                        <button class="rv-view-tab active" style="flex:1;background:#fff;color:#7c3aed;box-shadow:0 1px 3px rgba(0,0,0,.1);" onclick="mnVerSetView('diff')">{{ __('maintenance.view_diff') }}</button>
                        <button class="rv-view-tab" style="flex:1;" onclick="mnVerSetView('before')">{{ __('maintenance.view_before') }}</button>
                        <button class="rv-view-tab" style="flex:1;" onclick="mnVerSetView('after')">{{ __('maintenance.view_after') }}</button>
                    </div>
                </div>
                <div style="flex:1;overflow:hidden;background:#1a1625;margin:0 16px;">
                    <div id="mn-ver-code" style="width:100%;height:100%;overflow:auto;font-family:'Consolas','Monaco',monospace;font-size:11.5px;line-height:1.65;"></div>
                </div>
                <div class="mn-btn-row" style="flex-shrink:0;">
                    <button class="mn-btn mn-btn-danger" id="mn-btn-rollback" onclick="mnRollback()">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                        {{ __('maintenance.rollback') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ?먥븧 由щ럭 紐⑤떖 ?앹뾽 ?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧?먥븧 --}}
<div id="mn-review">
  <div id="mn-rv-inner">
    {{-- ?ㅻ뜑 --}}
    <div id="mn-rv-hdr">
        <div class="rv-icon">
            <svg width="15" height="15" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
        </div>
        <div>
            <div class="rv-title">{{ __('maintenance.review_title') }}</div>
        </div>
        <span class="rv-key" id="mn-rv-key"></span>
        <div class="rv-spacer"></div>
        <span id="mn-rv-status"></span>
        <button class="rv-btn rv-btn-back" onclick="mnCloseReview()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            {{ __('maintenance.back_to_prompt') }}
        </button>
        <button class="rv-btn rv-btn-preview" id="mn-rv-btn-preview" onclick="mnPreview()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('maintenance.preview_btn') }}
        </button>
        <button class="rv-btn rv-btn-apply" id="mn-rv-btn-apply" onclick="mnApply()">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            {{ __('maintenance.approve_apply') }}
        </button>
    </div>

    {{-- 諛붾뵒 --}}
    <div id="mn-rv-body">
        {{-- ?쇱そ ?ъ씠?쒕컮 --}}
        <div id="mn-rv-side">
            <div class="rv-side-section">
                <div class="rv-side-label">{{ __('maintenance.change_summary') }}</div>
                <div class="rv-summary-text" id="mn-rv-summary"></div>
                <div class="rv-stats" id="mn-rv-stats"></div>
            </div>
            <div class="rv-side-section">
                <div class="rv-side-label">{{ __('maintenance.changed_files') }}</div>
                <div id="mn-rv-file-list"></div>
            </div>
        </div>

        {{-- ?ㅻⅨ履?肄붾뱶 ?곸뿭 --}}
        <div id="mn-rv-main">
            <div id="mn-rv-tabs">
                <button class="rv-view-tab active" onclick="mnRvSetView('diff')">{{ __('maintenance.view_diff') }}</button>
                <button class="rv-view-tab" onclick="mnRvSetView('before')">{{ __('maintenance.view_before') }}</button>
                <button class="rv-view-tab" onclick="mnRvSetView('after')">{{ __('maintenance.view_after') }}</button>
            </div>
            <div id="mn-rv-code-wrap">
                <table class="rv-code-table" id="mn-rv-code-table"></table>
            </div>
        </div>
    </div>
  </div>
</div>

{{-- ?꾩옱 肄붾뱶 紐⑤떖 --}}
<div id="mn-code-modal" style="position:fixed;inset:0;background:rgba(15,14,26,.65);backdrop-filter:blur(4px);z-index:2000;display:none;align-items:center;justify-content:center;" onclick="if(event.target===this)mnCloseCode()">
    <div style="background:#1a1625;border-radius:12px;width:820px;max-width:95vw;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.4);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid #2d2a42;flex-shrink:0;">
            <span style="color:#e2e8f0;font-size:13px;font-weight:600;" id="mn-code-modal-title">{{ __('maintenance.current_code') }}</span>
            <button onclick="mnCloseCode()" style="background:none;border:none;color:#94a3b8;cursor:pointer;display:flex;align-items:center;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <pre id="mn-code-modal-body" style="flex:1;overflow:auto;padding:16px;margin:0;font-family:'Consolas','Monaco',monospace;font-size:12px;color:#e2e8f0;line-height:1.65;white-space:pre;"></pre>
    </div>
</div>

<script>
(async function(){
'use strict';
const MN_BASE = '{{ url("/maintenance") }}';
const MN_CSRF = '{{ csrf_token() }}';
const $ = id => document.getElementById(id);

/* ?? i18n strings ??????????????????????????????????????? */
const STR = {
    registering:       '{{ __("maintenance.js_registering") }}',
    regFail:           '{{ __("maintenance.js_reg_fail") }}',
    promptGenerating:  '{{ __("maintenance.js_prompt_generating") }}',
    promptAnalyzing:   '{{ __("maintenance.js_prompt_analyzing") }}',
    promptFail:        '{{ __("maintenance.js_prompt_fail") }}',
    patchGenerating:   '{{ __("maintenance.js_patch_generating") }}',
    patchAnalyzing:    '{{ __("maintenance.js_patch_analyzing") }}',
    patchFail:         '{{ __("maintenance.js_patch_fail") }}',
    previewPreparing:  '{{ __("maintenance.js_preview_preparing") }}',
    previewGenerating: '{{ __("maintenance.js_preview_generating") }}',
    previewFail:       '{{ __("maintenance.js_preview_fail") }}',
    previewBlocked:    '{{ __("maintenance.js_preview_blocked") }}',
    previewOpened:     '{{ __("maintenance.js_preview_opened") }}',
    applying:          '{{ __("maintenance.js_applying") }}',
    applyFiles:        '{{ __("maintenance.js_apply_files") }}',
    applyFail:         '{{ __("maintenance.js_apply_fail") }}',
    loading:           '{{ __("maintenance.js_loading") }}',
    loadFail:          '{{ __("maintenance.js_load_fail") }}',
    noFile:            '{{ __("maintenance.js_no_file") }}',
    rollingBack:       '{{ __("maintenance.js_rolling_back") }}',
    rollbackFail:      '{{ __("maintenance.js_rollback_fail") }}',
    confirmApply:      '{{ __("maintenance.js_confirm_apply") }}',
    enterRequest:      '{{ __("maintenance.js_enter_request") }}',
    system:            '{{ __("maintenance.js_system") }}',
    noVersions:        '{{ __("maintenance.no_versions") }}',
    currentCode:       '{{ __("maintenance.current_code") }}',
    aiGenPrompt:       '{{ __("maintenance.ai_gen_prompt") }}',
    aiGenPatch:        '{{ __("maintenance.ai_gen_patch") }}',
    approveApply:      '{{ __("maintenance.approve_apply") }}',
    previewBtn:        '{{ __("maintenance.preview_btn") }}',
    rollback:          '{{ __("maintenance.rollback") }}',
};

let mnKey   = window.MAINTENANCE_KEY   || '';
let mnBlade = window.MAINTENANCE_BLADE || '';

function mnResolveName() {
    if (window.MAINTENANCE_NAME && window.MAINTENANCE_NAME !== mnKey) return window.MAINTENANCE_NAME;
    const seg = (document.title||'').split(/\s*[|??-]\s*/)[0].trim();
    return seg || mnKey;
}
let mnName = mnResolveName();

function mnResolveBlade() {
    if (mnBlade) return mnBlade;
    return 'resources/views/' + mnKey.replace(/\./g,'/') + '.blade.php';
}

let mnScreen  = null;
let mnPatch   = null;
let mnFiles   = [];
let mnCurFile = 0;
let mnRvView  = 'diff';
let mnSelVer  = null;
let mnVerView = 'diff';

// ?? Init ??????????????????????????????????????????????????
document.addEventListener('DOMContentLoaded', () => {
    if (!mnKey) return;
    $('mn-fab').style.display = 'flex';
    $('mn-fab').onclick = mnOpen;
    $('mn-backdrop').onclick = mnClose;
    $('mn-hdr-key').textContent  = mnKey;
    $('mn-hdr-name').textContent = mnName;
    $('mn-rv-key').textContent   = mnKey;
    $('mn-reg-name').value  = mnName;
    $('mn-reg-blade').value = mnResolveBlade();
    mnInitScreen();
});

async function mnInitScreen() {
    try {
        const r = await fetch(`${MN_BASE}/${mnKey}/info`,{headers:{Accept:'application/json'}});
        if (r.ok) {
            const d = await r.json();
            mnScreen = d.screen;
            mnShowReqBox();
            if ((d.screen.versions_count||0) > 0) {
                const b=$('mn-ver-badge'); b.textContent=d.screen.versions_count; b.style.display='inline';
            }
        } else mnShowRegBox();
    } catch(e){ mnShowRegBox(); }
}
async function mnShowRegBox(){ $('mn-reg-box').style.display='block'; $('mn-req-box').style.display='none'; }
async function mnShowReqBox(){
    $('mn-reg-box').style.display='none'; $('mn-req-box').style.display='block';
    $('mn-file-path-txt').textContent = mnScreen?.blade_path || mnResolveBlade();
}

async function mnRegister() {
    const blade = $('mn-reg-blade').value.trim() || mnResolveBlade();
    const name  = $('mn-reg-name').value.trim()  || mnName;
    const st    = $('mn-reg-status');
    mnSt(st, STR.registering);
    const r = await fetch(MN_BASE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'},
        body:JSON.stringify({screen_key:mnKey,name,blade_path:blade,url_pattern:window.location.pathname})});
    const d = await r.json();
    if (d.ok){ mnScreen=d.screen; st.textContent=''; mnShowReqBox(); }
    else mnSt(st, d.message||d.errors?.screen_key?.[0]||STR.regFail,'err');
}

// ?? Drawer open/close ?????????????????????????????????????
async function mnOpen(){ $('mn-drawer').classList.add('open'); $('mn-backdrop').classList.add('show'); }
async function mnClose(){ $('mn-drawer').classList.remove('open'); $('mn-backdrop').classList.remove('show'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ mnCloseReview(); mnClose(); } });

// ?? Tabs ??????????????????????????????????????????????????
async function mnSetTab(tab){
    document.querySelectorAll('.mn-tab-btn').forEach((b,i)=>b.classList.toggle('active',['workflow','versions'][i]===tab));
    document.querySelectorAll('.mn-tab-body').forEach(b=>b.classList.remove('active'));
    $(`mn-tab-${tab}`).classList.add('active');
    if(tab==='versions') mnLoadVersions();
}

// ?? Step navigation ???????????????????????????????????????
async function mnGoStep(n){
    document.querySelectorAll('.mn-step-content').forEach(el=>el.classList.remove('active'));
    if($(`mn-sc-${n}`)) $(`mn-sc-${n}`).classList.add('active');
    [$('mn-btn-row-1'),$('mn-btn-row-2')].forEach((el,i)=>{ if(el) el.style.display=i+1===n?'flex':'none'; });
    for(let i=1;i<=3;i++){
        const el=$(`mn-sind-${i}`); if(!el) continue;
        el.classList.remove('active','done');
        if(i<n) el.classList.add('done');
        else if(i===n) el.classList.add('active');
    }
    $('mn-wf-status').textContent='';
    $('mn-wf-status').className='mn-status';
}

// ?? Step 1: ?꾨＼?꾪듃 ?앹꽦 ?????????????????????????????????
async function mnGeneratePrompt(){
    const req=$('mn-request').value.trim();
    const st=$('mn-wf-status');
    if(!req){ mnSt(st, STR.enterRequest,'err'); return; }
    const btn=$('mn-btn-gen-prompt');
    mnBusy(btn,'<span class="mn-spinner"></span> '+STR.promptGenerating);
    mnSt(st, STR.promptAnalyzing);
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/generate-prompt`,{method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'},
            body:JSON.stringify({user_request:req})});
        const d=await r.json();
        if(!d.ok) throw new Error(d.error||STR.promptFail);
        $('mn-p-goal').value         = d.draft.goal||'';
        $('mn-p-role').value         = d.draft.role||'';
        $('mn-p-input').value        = d.draft.input||'';
        $('mn-p-constraints').value  = d.draft.constraints||'';
        $('mn-p-output-format').value= d.draft.output_format||'';
        $('mn-p-refined').value      = d.draft.refined_prompt||'';
        $('mn-p-req-display').textContent = req;
        mnGoStep(2);
    } catch(e){ mnSt(st,e.message,'err'); }
    finally{ mnReady(btn,'<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg> '+STR.aiGenPrompt); }
}

// ?? Step 2: ?섏젙???앹꽦 ??由щ럭 ?ㅻ쾭?덉씠 ?닿린 ?????????????
async function mnGeneratePatch(){
    const prompt={goal:$('mn-p-goal').value,role:$('mn-p-role').value,input:$('mn-p-input').value,
        constraints:$('mn-p-constraints').value,output_format:$('mn-p-output-format').value,refined_prompt:$('mn-p-refined').value};
    const st=$('mn-wf-status');
    const btn=$('mn-btn-gen-patch');
    mnBusy(btn,'<span class="mn-spinner"></span> '+STR.patchGenerating);
    mnSt(st, STR.patchAnalyzing);
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/generate-patch`,{method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'},
            body:JSON.stringify({user_request:$('mn-request').value,prompt})});
        const d=await r.json();
        if(!d.ok) throw new Error(d.error||STR.patchFail);
        mnPatch=d.patch; mnFiles=d.patch.files||[];
        mnCurFile=0; mnRvView='diff';
        // ?쒕줈???ㅽ뀦 3?쇰줈 ?쒖떆 ??由щ럭 ?ㅻ쾭?덉씠 ?닿린
        for(let i=1;i<=3;i++){
            const el=$(`mn-sind-${i}`); el.classList.remove('active','done');
            if(i<3) el.classList.add('done'); else el.classList.add('active');
        }
        $('mn-wf-status').textContent='';
        mnOpenReview();
    } catch(e){ mnSt(st,e.message,'err'); }
    finally{ mnReady(btn,'<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg> '+STR.aiGenPatch); }
}

// ?? 由щ럭 ?ㅻ쾭?덉씠 ?????????????????????????????????????????
async function mnOpenReview(){
    // ?쒕줈???リ린
    $('mn-drawer').classList.remove('open');
    $('mn-backdrop').classList.remove('show');
    // ?ㅻ쾭?덉씠 以鍮?    $('mn-rv-summary').textContent = mnPatch.change_summary||'';
    $('mn-rv-status').textContent='';
    $('mn-rv-status').className='';
    mnRvView='diff';
    document.querySelectorAll('#mn-rv-tabs .rv-view-tab').forEach((t,i)=>t.classList.toggle('active',i===0));
    mnRenderRvFileList();
    mnRenderRvCode();
    $('mn-review').classList.add('show');
}
async function mnCloseReview(){
    $('mn-review').classList.remove('show');
    // ?쒕줈???ㅼ떆 ?닿린 (step 2濡?
    $('mn-drawer').classList.add('open');
    $('mn-backdrop').classList.add('show');
    [$('mn-btn-row-1'),$('mn-btn-row-2')].forEach((el,i)=>{ if(el) el.style.display=i===1?'flex':'none'; });
    document.querySelectorAll('.mn-step-content').forEach(el=>el.classList.remove('active'));
    if($('mn-sc-2')) $('mn-sc-2').classList.add('active');
}

// ?? 由щ럭: ?뚯씪 紐⑸줉 ?ъ씠?쒕컮 ?????????????????????????????
async function mnRenderRvFileList(){
    let adds=0, dels=0;
    mnFiles.forEach(f=>{
        (f.diff_content||'').split('\n').forEach(l=>{
            if(l.startsWith('+ ')) adds++; else if(l.startsWith('- ')) dels++;
        });
    });
    $('mn-rv-stats').innerHTML=
        (adds?`<span class="rv-stat rv-stat-add">+${adds}</span>`:'') +
        (dels?`<span class="rv-stat rv-stat-del">-${dels}</span>`:'');

    $('mn-rv-file-list').innerHTML = mnFiles.map((f,i)=>{
        const name = f.file_path?.split('/').pop() || f.file_type || 'file';
        const type = (f.file_type||'').toUpperCase().slice(0,5)||'FILE';
        return `<div class="rv-file-item${i===mnCurFile?' active':''}" onclick="mnRvSetFile(${i})">
            <span class="rv-file-badge">${mnEsc(type)}</span>
            <span class="rv-file-name" title="${mnEsc(f.file_path||'')}">${mnEsc(name)}</span>
        </div>`;
    }).join('');
}
async function mnRvSetFile(i){
    mnCurFile=i;
    document.querySelectorAll('.rv-file-item').forEach((el,idx)=>el.classList.toggle('active',idx===i));
    mnRenderRvCode();
}
async function mnRvSetView(v){
    mnRvView=v;
    document.querySelectorAll('#mn-rv-tabs .rv-view-tab').forEach((t,i)=>t.classList.toggle('active',['diff','before','after'][i]===v));
    mnRenderRvCode();
}
async function mnRenderRvCode(){
    const f=mnFiles[mnCurFile]; if(!f) return;
    const tbl=$('mn-rv-code-table');
    if(mnRvView==='diff'){
        tbl.innerHTML=mnRvDiffRows(f.diff_content||'');
    } else {
        const txt=mnRvView==='before'?(f.original_content||''):(f.modified_content||'');
        tbl.innerHTML=mnRvPlainRows(txt);
    }
}
function mnRvDiffRows(diff){
    let ln1=1, ln2=1;
    return diff.split('\n').map(line=>{
        let cls='rv-row-ctx', lnTxt='', lnTxt2='';
        if(line.startsWith('+ ')){ cls='rv-row-add'; lnTxt=''; lnTxt2=ln2++; }
        else if(line.startsWith('- ')){ cls='rv-row-del'; lnTxt=ln1++; lnTxt2=''; }
        else { lnTxt=ln1++; lnTxt2=ln2++; }
        return `<tr class="${cls}"><td class="rv-ln">${lnTxt}</td><td class="rv-ln">${lnTxt2}</td><td class="rv-lc">${mnEsc(line)}</td></tr>`;
    }).join('');
}
function mnRvPlainRows(txt){
    return txt.split('\n').map((line,i)=>
        `<tr><td class="rv-ln">${i+1}</td><td class="rv-lc">${mnEsc(line)}</td></tr>`
    ).join('');
}

// ?? 誘몃━蹂닿린 ??????????????????????????????????????????????
async function mnPreview(){
    if (!mnFiles.length) return;
    const btn=$('mn-rv-btn-preview');
    const st=$('mn-rv-status');
    mnBusy(btn,'<span class="mn-spinner" style="border-top-color:#7c3aed;border-color:rgba(124,58,237,.25);"></span> '+STR.previewPreparing);
    st.textContent=STR.previewGenerating; st.className='';
    try {
        // ?꾩옱 ?좏깮???뚯씪??modified_content ?ъ슜
        const content = mnFiles[mnCurFile]?.modified_content || '';
        const r = await fetch(`${MN_BASE}/${mnKey}/preview`, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'},
            body: JSON.stringify({ content })
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || STR.previewFail);
        // ????쑝濡??닿린
        const win = window.open(d.url, '_blank');
        if (!win) {
            // ?앹뾽 李⑤떒??寃쎌슦 留곹겕 ?쒖떆
            st.textContent = '';
            const link = document.createElement('a');
            link.href = d.url; link.target = '_blank';
            link.style.cssText = 'font-size:12px;color:#7c3aed;text-decoration:underline;';
            link.textContent = STR.previewBlocked;
            $('mn-rv-status').replaceWith(link);
        } else {
            st.textContent = STR.previewOpened; st.className='ok';
            setTimeout(()=>{ if(st.className==='ok') { st.textContent=''; st.className=''; } }, 3000);
        }
    } catch(e){ st.textContent=e.message; st.className='err'; }
    finally{ mnReady(btn,'<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg> '+STR.previewBtn); }
}

// ?? ?곸슜 ?????????????????????????????????????????????????
async function mnApply(){
    if(!await __confirm(STR.confirmApply)) return;
    const st=$('mn-rv-status');
    const btn=$('mn-rv-btn-apply');
    mnBusy(btn,'<span class="mn-spinner"></span> '+STR.applying);
    st.textContent=STR.applyFiles; st.className='';
    const prompt={goal:$('mn-p-goal').value,role:$('mn-p-role').value,input:$('mn-p-input').value,
        constraints:$('mn-p-constraints').value,output_format:$('mn-p-output-format').value,refined_prompt:$('mn-p-refined').value};
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/apply`,{method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'},
            body:JSON.stringify({change_summary:mnPatch.change_summary,files:mnFiles,prompt,user_request:$('mn-request').value})});
        const d=await r.json();
        if(!d.ok) throw new Error(d.error||STR.applyFail);
        st.textContent=`??v${d.version.version_no} {{ __("maintenance.js_rollback_done") }}`; st.className='ok';
        const b=$('mn-ver-badge'); b.textContent=parseInt(b.textContent||0)+1; b.style.display='inline';
        setTimeout(()=>{
            $('mn-review').classList.remove('show');
            $('mn-request').value='';
            mnGoStep(1);
        }, 2000);
    } catch(e){ st.textContent=e.message; st.className='err'; }
    finally{ mnReady(btn,'<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> '+STR.approveApply); }
}

// ?? 踰꾩쟾 紐⑸줉 ?????????????????????????????????????????????
async function mnLoadVersions(){
    const list=$('mn-ver-list');
    list.innerHTML='<div class="mn-status">'+STR.loading+'</div>';
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/versions`,{headers:{Accept:'application/json'}});
        const d=await r.json(); if(!d.ok) throw new Error();
        const vers=d.versions;
        const b=$('mn-ver-badge');
        if(vers.length){b.textContent=vers.length;b.style.display='inline';}else b.style.display='none';
        if(!vers.length){list.innerHTML='<div class="mn-empty">'+STR.noVersions+'</div>';return;}
        list.innerHTML=vers.map(v=>`
            <div class="mn-version-item" onclick="mnShowVerDetail(${v.id},${v.version_no})">
                <div class="mn-version-badge">v${v.version_no}</div>
                <div class="mn-version-info">
                    <div class="mn-version-summary">${mnEsc(v.change_summary||'')}</div>
                    <div class="mn-version-meta">${mnEsc(v.applied_by?.name||STR.system)} 쨌 ${mnFmt(v.applied_at)}</div>
                </div>
                <svg width="13" height="13" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </div>`).join('');
    } catch(e){ list.innerHTML='<div class="mn-status err">'+STR.loadFail+'</div>'; }
}

async function mnShowVerDetail(verId, verNo){
    $('mn-ver-list-view').style.display='none';
    $('mn-ver-detail-view').style.display='flex';
    $('mn-ver-detail-summary').textContent=STR.loading;
    $('mn-ver-code').innerHTML='';
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/versions/${verId}`,{headers:{Accept:'application/json'}});
        const d=await r.json(); if(!d.ok) throw new Error();
        const v=d.version;
        $('mn-ver-detail-summary').textContent=`v${v.version_no} ??${v.change_summary||''}`;
        mnSelVer={id:verId,verNo:v.version_no,files:v.files||[]};
        mnVerView='diff';
        document.querySelectorAll('#mn-ver-detail-view .rv-view-tab').forEach((t,i)=>t.classList.toggle('active',i===0));
        mnVerRenderCode();
    } catch(e){ $('mn-ver-detail-summary').textContent=STR.loadFail; }
}
async function mnVerBack(){ $('mn-ver-list-view').style.display='flex'; $('mn-ver-detail-view').style.display='none'; mnSelVer=null; }
async function mnVerSetView(v){
    mnVerView=v;
    document.querySelectorAll('#mn-ver-detail-view .rv-view-tab').forEach((t,i)=>t.classList.toggle('active',['diff','before','after'][i]===v));
    mnVerRenderCode();
}
async function mnVerRenderCode(){
    if(!mnSelVer?.files?.length) return;
    const f=mnSelVer.files[0];
    const area=$('mn-ver-code');
    if(mnVerView==='diff'){
        area.innerHTML=mnSimpleDiff(f.diff_content||'');
    } else {
        const txt=mnVerView==='before'?(f.original_content||''):(f.modified_content||'');
        area.innerHTML=`<span style="color:#94a3b8;white-space:pre;">${mnEsc(txt)}</span>`;
    }
}
function mnSimpleDiff(diff){
    return diff.split('\n').map(line=>{
        if(line.startsWith('+ ')) return `<span style="display:block;background:rgba(34,197,94,.18);color:#7ee787;">${mnEsc(line)}</span>`;
        if(line.startsWith('- ')) return `<span style="display:block;background:rgba(239,68,68,.18);color:#ffa198;">${mnEsc(line)}</span>`;
        return `<span style="display:block;color:#6e7681;">${mnEsc(line)}</span>`;
    }).join('');
}

async function mnRollback(){
    if(!mnSelVer) return;
    if(!await __confirm(`{{ __("maintenance.js_confirm_rollback") }}`.replace(':ver', mnSelVer.verNo))) return;
    const btn=$('mn-btn-rollback');
    mnBusy(btn,'<span class="mn-spinner" style="border-top-color:#dc2626;border-color:rgba(220,38,38,.3)"></span> '+STR.rollingBack);
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/versions/${mnSelVer.id}/rollback`,{method:'POST',headers:{'X-CSRF-TOKEN':MN_CSRF,'Accept':'application/json'}});
        const d=await r.json(); if(!d.ok) throw new Error(d.error);
        alert(`{{ __("maintenance.js_rollback_done") }}`.replace(':ver', d.version.version_no));
        mnVerBack(); mnLoadVersions();
    } catch(e){ alert(STR.rollbackFail+e.message); }
    finally{ mnReady(btn,'<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg> '+STR.rollback); }
}

// ?? ?꾩옱 肄붾뱶 蹂닿린 ????????????????????????????????????????
async function mnViewCode(){
    $('mn-code-modal').style.display='flex';
    $('mn-code-modal-title').textContent=STR.currentCode+' ??'+(mnScreen?.blade_path||mnResolveBlade());
    $('mn-code-modal-body').textContent=STR.loading;
    try {
        const r=await fetch(`${MN_BASE}/${mnKey}/files`,{headers:{Accept:'application/json'}});
        const d=await r.json();
        $('mn-code-modal-body').textContent=d.files?.blade?.content||STR.noFile;
    } catch(e){ $('mn-code-modal-body').textContent=STR.loadFail; }
}
async function mnCloseCode(){ $('mn-code-modal').style.display='none'; }

// ?? Helpers ???????????????????????????????????????????????
function mnEsc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function mnSt(el,msg,cls){ el.textContent=msg; el.className='mn-status'+(cls?' '+cls:''); }
function mnBusy(btn,html){ btn.disabled=true; btn.innerHTML=html; }
function mnReady(btn,html){ btn.disabled=false; btn.innerHTML=html; }
function mnFmt(s){ if(!s) return ''; const d=new Date(s); return d.getFullYear()+'.'+String(d.getMonth()+1).padStart(2,'0')+'.'+String(d.getDate()).padStart(2,'0'); }

// expose for inline onclick
Object.assign(window,{mnSetTab,mnGoStep,mnRvSetFile,mnRvSetView,mnVerSetView,mnVerBack,
    mnShowVerDetail,mnRollback,mnGeneratePrompt,mnGeneratePatch,mnApply,mnPreview,mnRegister,
    mnOpen,mnClose,mnOpenReview,mnCloseReview,mnViewCode,mnCloseCode});
})();
</script>

