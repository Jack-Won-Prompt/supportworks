@extends('layouts.app')
@section('title', __('ai.ai_agent'))

@push('styles')
<style>
.ai-wrap{display:flex;height:calc(100vh - 52px);margin:-20px -24px -24px;overflow:hidden;background:var(--tBg);}

/* LEFT */
.ai-left{width:240px;min-width:240px;background:#fff;border-right:1px solid var(--t100);display:flex;flex-direction:column;overflow:hidden;transition:width .22s,min-width .22s;}
.ai-left.collapsed{width:40px;min-width:40px;}
.ai-left-header{padding:10px 10px;border-bottom:1px solid var(--t50);display:flex;align-items:center;gap:6px;flex-shrink:0;}
.ai-left-header h2{font-size:13px;font-weight:700;color:#1e1b2e;display:flex;align-items:center;gap:7px;margin:0;flex:1;overflow:hidden;white-space:nowrap;}
.ai-left-toggle{display:flex!important;align-items:center;justify-content:center;width:28px;height:28px;min-width:28px;border-radius:7px;border:1.5px solid var(--t200);background:var(--t50);cursor:pointer;color:var(--t600);flex-shrink:0;padding:0;transition:background .12s;}
.ai-left-toggle:hover{background:var(--t100);}
.ai-left.collapsed .ai-left-header h2,.ai-left.collapsed .ai-left-header .ai-icon-btn{display:none!important;}
.ai-left-scroll{flex:1;overflow-y:auto;padding:8px 10px 12px;}
.ai-left.collapsed .ai-left-body{display:none!important;}
.ai-left-body{display:flex;flex-direction:column;flex:1;overflow:hidden;}
.ai-section-label{font-size:10px;font-weight:700;color:#a1a1aa;letter-spacing:.07em;text-transform:uppercase;padding:6px 4px 3px;}
.ai-session-item{display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:8px;cursor:pointer;transition:background .12s;font-size:12.5px;color:#52525b;}
.ai-session-item:hover{background:var(--tBg);}
.ai-session-item.active{background:var(--t100);color:var(--tText);font-weight:600;}
.ai-session-title{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ai-session-del{display:none;width:20px;height:20px;align-items:center;justify-content:center;border-radius:4px;border:none;background:transparent;color:#d1d5db;cursor:pointer;flex-shrink:0;}
.ai-session-item:hover .ai-session-del{display:flex;}
.ai-session-del:hover{background:#fee2e2;color:#ef4444;}
.sess-proj-group{margin-bottom:2px;}
.sess-proj-hdr{display:flex;align-items:center;gap:5px;padding:5px 4px;font-size:10.5px;font-weight:700;color:#52525b;cursor:pointer;border-radius:6px;transition:background .12s;user-select:none;}
.sess-proj-hdr:hover{background:var(--tBg);}
.sess-proj-hdr-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.sess-proj-count{font-size:9.5px;padding:1px 5px;background:var(--t100);color:var(--t600);border-radius:8px;flex-shrink:0;}
.sess-proj-arrow{transition:transform .18s;flex-shrink:0;color:#94a3b8;}
.sess-proj-hdr.collapsed .sess-proj-arrow{transform:rotate(-90deg);}
.sess-proj-items{padding-left:6px;}
.sess-proj-hdr.collapsed + .sess-proj-items{display:none;}

/* RIGHT STATES */
.ai-right{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.ai-state{flex:1;display:none;flex-direction:column;overflow:hidden;}
.ai-state.active{display:flex;}

/* LANDING */
.lnd-wrap{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px 24px;background:var(--tBg);gap:28px;overflow-y:auto;}
.lnd-header{text-align:center;max-width:560px;}
.lnd-header h1{font-size:21px;font-weight:800;color:#1e1b2e;margin:0 0 10px;display:flex;align-items:center;justify-content:center;gap:8px;}
.lnd-header p{font-size:13px;color:#64748b;line-height:1.75;margin:0;}
.lnd-cards{display:grid;grid-template-columns:1fr 1fr;gap:14px;width:100%;max-width:660px;}
.lnd-card{background:#fff;border:2px solid #e8e3ff;border-radius:16px;padding:26px 22px;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;gap:10px;}
.lnd-card:hover{border-color:var(--t400);box-shadow:0 8px 24px rgba(124,58,237,.1);transform:translateY(-2px);}
.lnd-card-ico{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;}
.lnd-card h3{font-size:14px;font-weight:700;color:#1e1b2e;margin:0;}
.lnd-card p{font-size:12px;color:#64748b;line-height:1.6;margin:0;flex:1;}
.lnd-card-tags{display:flex;flex-wrap:wrap;gap:4px;}
.lnd-card-tag{font-size:10.5px;padding:2px 7px;border-radius:5px;font-weight:500;}
.lnd-card-btn{margin-top:4px;padding:9px 0;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;width:100%;transition:opacity .15s;}
.lnd-card-btn:hover{opacity:.88;}

/* PROJECT SELECT */
.pj-wrap{flex:1;display:flex;flex-direction:column;background:var(--tBg);overflow:hidden;}
.pj-hdr{padding:18px 26px 0;flex-shrink:0;}
.pj-search-wrap{position:relative;margin-bottom:14px;}
.pj-search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;}
.pj-search{width:100%;padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;transition:border-color .15s;}
.pj-search:focus{border-color:var(--t400);}
.pj-list{flex:1;overflow-y:auto;padding:0 26px 24px;display:flex;flex-direction:column;gap:8px;}
.pj-item{background:#fff;border:1.5px solid #e8e3ff;border-radius:12px;padding:14px 16px;cursor:pointer;display:flex;align-items:center;gap:12px;transition:all .15s;}
.pj-item:hover{border-color:var(--t400);background:#faf9ff;}
.pj-item-ico{width:38px;height:38px;border-radius:9px;background:linear-gradient(135deg,var(--t100),var(--t200));display:flex;align-items:center;justify-content:center;flex-shrink:0;}

/* CATEGORY */
.cat-wrap{flex:1;display:flex;flex-direction:column;background:var(--tBg);overflow:hidden;}
.cat-hdr{padding:18px 26px 14px;flex-shrink:0;display:flex;align-items:center;gap:10px;}
.cat-grid{flex:1;overflow-y:auto;padding:0 26px 26px;display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.cat-card{background:#fff;border:2px solid #e8e3ff;border-radius:14px;padding:20px 18px;cursor:pointer;transition:all .18s;}
.cat-card:hover{border-color:var(--t400);box-shadow:0 6px 18px rgba(0,0,0,.07);transform:translateY(-1px);}
.cat-card-ico{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;}
.cat-card h3{font-size:13.5px;font-weight:700;color:#1e1b2e;margin:0 0 5px;}
.cat-card p{font-size:11.5px;color:#64748b;line-height:1.5;margin:0 0 10px;}
.cat-card-sub{display:flex;flex-direction:column;gap:3px;}
.cat-card-sub span{font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:5px;}

/* CHAT */
.chat-hdr{padding:10px 18px;border-bottom:1px solid var(--t100);background:#fff;display:flex;align-items:center;gap:8px;flex-shrink:0;min-height:46px;}
.chat-hdr-title{font-size:13.5px;font-weight:600;color:#1e1b2e;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ai-badge{display:flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.ai-badge-proj{background:#dbeafe;color:#1d4ed8;}
.ai-badge-cat{background:var(--t100);color:var(--tText);}
.ai-badge-fig{background:#f5f3ff;color:var(--t600);}
.ai-chat-area{flex:1;overflow-y:auto;padding:18px 22px;display:flex;flex-direction:column;gap:14px;}

/* Messages */
.ai-msg{display:flex;gap:10px;max-width:100%;}
.ai-msg.user{flex-direction:row-reverse;}
.ai-msg-av{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}
.ai-msg.user .ai-msg-av{background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;}
.ai-msg.assistant .ai-msg-av{background:linear-gradient(135deg,#1e1b2e,#4c1d95);color:var(--t300);}
.ai-msg-body{max-width:calc(100% - 76px);display:flex;flex-direction:column;gap:6px;}
.ai-msg.user .ai-msg-body{align-items:flex-end;}
.ai-msg-time{font-size:10.5px;color:#b0aac8;margin-top:2px;}
.ai-bubble{padding:10px 14px;border-radius:12px;font-size:13.5px;line-height:1.6;white-space:pre-wrap;word-break:break-word;}
.ai-msg.user .ai-bubble{background:linear-gradient(135deg,var(--t600),var(--t500));color:#fff;border-bottom-right-radius:4px;}
.ai-msg.assistant .ai-bubble{background:#fff;color:#1e1b2e;border:1px solid var(--t100);border-bottom-left-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,.04);}

/* Code panel */
.ai-code-panel{background:#fff;border:1px solid var(--t100);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);}
.ai-code-tabs{display:flex;border-bottom:1px solid var(--t100);background:var(--tBg);padding:0 4px;}
.ai-code-tab{padding:8px 13px;font-size:12px;font-weight:600;color:#9e97c0;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .12s,border-color .12s;display:flex;align-items:center;gap:4px;}
.ai-code-tab.active{color:var(--tText);border-bottom-color:var(--t600);}
.ai-tab-copy{margin-left:auto;padding:4px 10px;font-size:11px;font-weight:600;border:1px solid var(--t200);border-radius:5px;background:#fff;color:var(--tText);cursor:pointer;transition:background .12s;margin:5px 6px;}
.ai-tab-copy:hover{background:var(--t100);}
.ai-code-content{display:none;font-family:'JetBrains Mono','Fira Code','Consolas',monospace;font-size:12.5px;line-height:1.7;padding:14px 16px;background:#1e1b2e;color:var(--t300);overflow-x:auto;max-height:360px;overflow-y:auto;white-space:pre;}
.ai-code-content.active{display:block;}
.ai-preview-frame{display:none;height:360px;min-height:200px;border:none;width:100%;background:#fff;}
.ai-preview-frame.active{display:block;}
/* Preview fullscreen overlay */
.preview-fs-overlay{position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;display:none;flex-direction:column;}
.preview-fs-overlay.show{display:flex;animation:fsIn .18s ease;}
@keyframes fsIn{from{opacity:0;transform:scale(.98)}to{opacity:1;transform:scale(1)}}
.preview-fs-header{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;background:#1e1b2e;flex-shrink:0;border-bottom:1px solid rgba(255,255,255,.08);}
.preview-fs-header-left{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#c4b5fd;}
.preview-fs-close{padding:5px 14px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.07);color:#e5e7eb;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:background .12s;font-family:inherit;}
.preview-fs-close:hover{background:rgba(255,255,255,.15);}
.preview-fs-iframe{flex:1;border:none;width:100%;background:#fff;}

/* Input */
.ai-input-area{padding:10px 18px 14px;border-top:1px solid var(--t100);background:#fff;flex-shrink:0;}
.ai-input-row{display:flex;align-items:flex-end;gap:8px;background:var(--tBg);border:1.5px solid var(--t200);border-radius:12px;padding:9px 9px 9px 13px;transition:border-color .15s;}
.ai-input-row:focus-within{border-color:var(--t500);}
#ai-message-input{flex:1;border:none;background:transparent;resize:none;outline:none;font-size:13.5px;color:#1e1b2e;line-height:1.5;max-height:140px;min-height:22px;font-family:inherit;}
#ai-message-input::placeholder{color:#a1a1aa;}
.ai-send-btn{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--t600),var(--t500));border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .15s;}
.ai-send-btn:hover{opacity:.88;}
.ai-send-btn:disabled{opacity:.4;cursor:not-allowed;}

/* Shared */
.ai-icon-btn{width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:5px;border:none;background:transparent;color:#a1a1aa;cursor:pointer;transition:background .12s,color .12s;}
.ai-icon-btn:hover{background:var(--t50);color:var(--tText);}
.ai-back-btn{display:flex;align-items:center;gap:5px;padding:5px 10px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;font-size:11.5px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;flex-shrink:0;}
.ai-back-btn:hover{border-color:var(--t300);color:var(--tText);}

/* Modals */
.ai-modal-backdrop{position:fixed;inset:0;background:rgba(15,14,26,.5);backdrop-filter:blur(4px);z-index:1000;display:none;align-items:center;justify-content:center;}
.ai-modal-backdrop.show{display:flex;}
.ai-modal{background:#fff;border-radius:16px;padding:24px;width:420px;max-width:90vw;box-shadow:0 24px 64px rgba(15,14,26,.3);}
.ai-modal h3{font-size:15px;font-weight:700;color:#1e1b2e;margin:0 0 16px;}
.ai-field{margin-bottom:14px;}
.ai-field label{display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:5px;}
.ai-field input,.ai-field textarea,.ai-field select{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:var(--tBg);box-sizing:border-box;transition:border-color .15s;}
.ai-field input:focus,.ai-field textarea:focus{border-color:var(--t500);}
.ai-modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;}
.ai-btn-cancel{padding:8px 16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#6b7280;font-size:13px;cursor:pointer;font-weight:500;}
.ai-btn-cancel:hover{background:#f9fafb;}
.ai-btn-primary{padding:8px 18px;border:none;border-radius:8px;background:linear-gradient(135deg,var(--t600),var(--t500));color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;}
.ai-btn-primary:hover{opacity:.88;}

/* Typing */
.ai-typing{display:flex;gap:4px;align-items:center;padding:10px 14px;}
.ai-typing span{width:7px;height:7px;border-radius:50%;background:var(--t300);animation:tdot 1.2s infinite ease-in-out;}
.ai-typing span:nth-child(2){animation-delay:.2s;}
.ai-typing span:nth-child(3){animation-delay:.4s;}
@keyframes tdot{0%,60%,100%{transform:translateY(0);opacity:.6}30%{transform:translateY(-6px);opacity:1}}

.ai-left-scroll::-webkit-scrollbar,.ai-chat-area::-webkit-scrollbar,.pj-list::-webkit-scrollbar,.cat-grid::-webkit-scrollbar{width:5px;}
.ai-left-scroll::-webkit-scrollbar-thumb,.ai-chat-area::-webkit-scrollbar-thumb,.pj-list::-webkit-scrollbar-thumb,.cat-grid::-webkit-scrollbar-thumb{background:var(--t200);border-radius:10px;}

/* Attach */
.attach-toolbar{display:flex;align-items:center;gap:4px;padding:4px 0 2px;flex-wrap:wrap;}
.attach-btn{display:flex;align-items:center;gap:4px;padding:4px 9px;border:1.5px solid var(--t200);border-radius:7px;background:transparent;font-size:11.5px;color:var(--t400);cursor:pointer;transition:all .12s;font-family:inherit;}
.attach-btn:hover{background:var(--t50);color:var(--t600);border-color:var(--t300);}
.attach-btn.active{background:var(--t50);color:var(--t600);border-color:var(--t400);}
#attach-url-row{display:none;flex:1;align-items:center;gap:6px;animation:slideUp .15s ease;}
#ctx-mode-wrap{display:none;align-items:center;gap:3px;margin-left:auto;}
#attach-url-input{flex:1;padding:5px 10px;border:1.5px solid var(--t300);border-radius:7px;font-size:12.5px;outline:none;color:#1e1b2e;background:#fff;font-family:inherit;}
#attach-url-input:focus{border-color:var(--t500);}
#attach-chips{display:none;flex-wrap:wrap;gap:5px;padding:5px 2px 2px;}
.attach-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 7px 3px 8px;background:var(--t50);border:1px solid var(--t200);border-radius:20px;font-size:11.5px;color:var(--t600);max-width:240px;}
.attach-chip span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.attach-chip-del{display:flex;align-items:center;background:none;border:none;cursor:pointer;color:var(--t300);padding:0;flex-shrink:0;transition:color .1s;}
.attach-chip-del:hover{color:var(--t600);}

/* Toast */
@keyframes slideUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}


/* Share */
.ai-share-btn{display:flex;align-items:center;gap:4px;padding:4px 9px;border:1.5px solid var(--t200);border-radius:7px;background:transparent;font-size:11.5px;font-weight:600;color:var(--t400);cursor:pointer;transition:all .12s;font-family:inherit;flex-shrink:0;}
.ai-share-btn:hover{background:var(--t50);color:var(--t600);border-color:var(--t300);}
.ai-share-btn.shared{background:var(--t50);color:var(--t600);border-color:var(--t400);}
.ai-session-shared{display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:5px;font-size:10px;font-weight:600;background:#f0fdf4;color:#16a34a;flex-shrink:0;}
.ai-team-session-item{display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:8px;cursor:pointer;transition:background .12s;font-size:12.5px;color:#52525b;text-decoration:none;}
.ai-team-session-item:hover{background:var(--tBg);}
.ai-team-avatar{width:18px;height:18px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}

/* Document Card */
.ai-doc-card{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-top:10px;min-width:280px;max-width:420px;}
.ai-doc-card-icon{font-size:28px;flex-shrink:0;line-height:1;}
.ai-doc-card-info{flex:1;min-width:0;}
.ai-doc-card-name{font-size:13px;font-weight:600;color:#1e1b2e;word-break:break-all;}
.ai-doc-card-meta{font-size:11px;color:#94a3b8;margin-top:3px;}
.ai-doc-download-btn{display:flex;align-items:center;gap:5px;padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;transition:opacity .15s;border:none;cursor:pointer;font-family:inherit;}
.ai-doc-download-btn:hover{opacity:.88;}
.ai-doc-add-proj-btn{display:flex;align-items:center;justify-content:center;gap:4px;padding:6px 10px;background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;border-radius:8px;font-size:11.5px;font-weight:600;white-space:nowrap;cursor:pointer;font-family:inherit;transition:all .12s;}
.ai-doc-add-proj-btn:hover{background:#dcfce7;border-color:#86efac;}
.ai-doc-add-proj-btn.added{background:#f0fdf4;color:#15803d;border-color:#86efac;cursor:default;}
/* Add-to-project modal */
.atp-proj-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e8e3ff;border-radius:10px;background:#fff;cursor:pointer;transition:all .15s;margin-bottom:6px;}
.atp-proj-item:hover{border-color:#7c3aed;background:#faf9ff;}
.atp-proj-item:last-child{margin-bottom:0;}
.atp-proj-ico{width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#ede9fe,#dbeafe);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#6d28d9;flex-shrink:0;}

/* Agent Badges */
.agent-badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0;}
.agent-badge-general{background:#ede9fe;color:#7c3aed;}
.agent-badge-dev{background:#dbeafe;color:#1d4ed8;}
.agent-badge-document{background:#d1fae5;color:#059669;}
.agent-badge-figma{background:#fce7f3;color:#be185d;}
.agent-badge-builder{background:#fef3c7;color:#92400e;}
.builder-step-btn{padding:7px 14px;border-radius:8px;border:2px solid #e5e7eb;background:#fff;font-size:12.5px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;}
.builder-step-btn:hover{border-color:#f59e0b;color:#92400e;}
.builder-step-btn.selected{border-color:#f59e0b;background:#fef3c7;color:#92400e;}
@keyframes builderPulse{0%,100%{opacity:1;}50%{opacity:.5;}}
.builder-pulse{display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:builderPulse 1.4s ease-in-out infinite;flex-shrink:0;}

/* DEV Settings State */
.dev-settings-wrap{flex:1;overflow-y:auto;padding:28px 32px;background:var(--tBg);}
.dev-settings-card{background:#fff;border:1.5px solid #e8e3ff;border-radius:16px;padding:24px;max-width:560px;margin:0 auto;}
.dev-settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* Doc Type State */
.doc-type-outer{flex:1;display:flex;flex-direction:column;background:var(--tBg);overflow:hidden;}
.doc-type-scroll{flex:1;overflow-y:auto;padding:0 24px 16px;}
.doc-type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;}
.doc-type-card{background:#fff;border:2px solid #e8e3ff;border-radius:12px;padding:16px 14px;cursor:pointer;transition:all .18s;display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center;}
.doc-type-card:hover{border-color:#059669;box-shadow:0 4px 12px rgba(5,150,105,.1);transform:translateY(-1px);}
.doc-type-card.selected{border-color:#059669;background:#f0fdf4;}
.doc-type-icon{font-size:26px;line-height:1;}

</style>
@endpush

@section('content')
<div class="ai-wrap">

{{-- ── LEFT ── --}}
<div class="ai-left">
    <div class="ai-left-header">
        <button class="ai-left-toggle" id="sidebar-toggle-btn" onclick="toggleSidebar()" title="{{ __('ai.collapse_expand') }}">
            <svg id="sidebar-toggle-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .22s;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <h2>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--t600)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
            웍스 Agent
        </h2>
        <button class="ai-icon-btn" onclick="openSettings()" title="{{ __('ai.settings') }}" style="display:none;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </button>
    </div>

    <div class="ai-left-body">
    <div style="padding:10px 10px 0;flex-shrink:0;display:flex;flex-direction:column;gap:5px;">
        <button style="width:100%;padding:7px 0;background:linear-gradient(135deg,var(--t600),var(--t500));color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;" onclick="goToLanding()">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('ai.new_chat') }}
        </button>
        <div style="display:flex;gap:5px;">
            <a href="{{ route('ai.prompts.index') }}" style="flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:5px 0;background:var(--tBg);color:var(--t600);border:1px solid var(--t200);border-radius:7px;font-size:11px;font-weight:600;text-decoration:none;">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('ai.library') }}
            </a>
            <a href="{{ route('ai.executions.index') }}" style="flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:5px 0;background:var(--tBg);color:var(--t600);border:1px solid var(--t200);border-radius:7px;font-size:11px;font-weight:600;text-decoration:none;">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('ai.execution_history') }}
            </a>
        </div>
    </div>

    <div class="ai-left-scroll">
        <div id="left-proj-badge" style="display:none;padding:8px 4px 4px;">
            <div style="display:flex;align-items:center;gap:6px;padding:6px 10px;background:#dbeafe;border-radius:8px;">
                <svg width="10" height="10" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                <span id="left-proj-name" style="font-size:11px;font-weight:600;color:#1d4ed8;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            </div>
        </div>
        <div style="height:1px;background:linear-gradient(to right,transparent,#e8e3ff,transparent);margin:10px 0;"></div>
        <div class="ai-section-label">{{ __('ai.chat_history') }}</div>
        <div id="session-list">
            @php
                $sessionGroups = $sessions->groupBy(fn($s) => $s->project_id ?? 0);
                // 프로젝트 있는 그룹 먼저(프로젝트명 오름차순), 그 뒤 프로젝트 없는 그룹
                $projGroups   = $sessionGroups->filter(fn($_, $k) => $k != 0)->sortBy(fn($g) => $g->first()?->project?->name ?? '');
                $noProjGroup  = $sessionGroups->get(0, collect());
            @endphp

            @if($sessions->isEmpty())
            <div style="padding:12px 4px;font-size:12px;color:#b8b0d8;text-align:center;">{{ __('ai.no_chat_history') }}</div>
            @else
                {{-- 프로젝트별 그룹 --}}
                @foreach($projGroups as $pid => $group)
                @php $projName = $group->first()?->project?->name ?? __('ai.project_no_name').$pid; @endphp
                <div class="sess-proj-group" data-project-id="{{ $pid }}">
                    <div class="sess-proj-hdr" onclick="toggleProjGroup(this)">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;color:#6366f1;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        <span class="sess-proj-hdr-name" title="{{ $projName }}">{{ $projName }}</span>
                        <span class="sess-proj-count">{{ $group->count() }}</span>
                        <svg class="sess-proj-arrow" width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div class="sess-proj-items">
                        @foreach($group as $s)
                        @php $at = $s->agent_type ?? 'general'; $atCls = ['general'=>'agent-badge-general','dev'=>'agent-badge-dev','document'=>'agent-badge-document','figma'=>'agent-badge-figma','builder'=>'agent-badge-builder'][$at] ?? 'agent-badge-general'; $atLbl = ['general'=>'G','dev'=>'D','document'=>'DOC','figma'=>'FIG','builder'=>'BUILD'][$at] ?? 'G'; @endphp
                        <div class="ai-session-item {{ $session && $session->id === $s->id ? 'active' : '' }}" data-id="{{ $s->id }}" data-agent="{{ $at }}" data-project-id="{{ $pid }}" onclick="loadSession({{ $s->id }})">
                            <span class="agent-badge {{ $atCls }}" style="padding:1px 5px;font-size:9px;">{{ $atLbl }}</span>
                            <span class="ai-session-title">{{ $s->title }}</span>
                            @if($s->is_shared)<span style="flex-shrink:0;width:7px;height:7px;border-radius:50%;background:#16a34a;" title="{{ __('ai.team_sharing_badge') }}"></span>@endif
                            <button class="ai-session-del" onclick="event.stopPropagation();deleteSession({{ $s->id }},this)"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                {{-- 프로젝트 없는 세션 --}}
                @if($noProjGroup->isNotEmpty())
                <div class="sess-proj-group" data-project-id="0">
                    @if($projGroups->isNotEmpty())
                    <div class="sess-proj-hdr" onclick="toggleProjGroup(this)">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;color:#94a3b8;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span class="sess-proj-hdr-name">{{ __('ai.other') }}</span>
                        <span class="sess-proj-count">{{ $noProjGroup->count() }}</span>
                        <svg class="sess-proj-arrow" width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    @endif
                    <div class="sess-proj-items">
                        @foreach($noProjGroup as $s)
                        @php $at = $s->agent_type ?? 'general'; $atCls = ['general'=>'agent-badge-general','dev'=>'agent-badge-dev','document'=>'agent-badge-document','figma'=>'agent-badge-figma','builder'=>'agent-badge-builder'][$at] ?? 'agent-badge-general'; $atLbl = ['general'=>'G','dev'=>'D','document'=>'DOC','figma'=>'FIG','builder'=>'BUILD'][$at] ?? 'G'; @endphp
                        <div class="ai-session-item {{ $session && $session->id === $s->id ? 'active' : '' }}" data-id="{{ $s->id }}" data-agent="{{ $at }}" data-project-id="0" onclick="loadSession({{ $s->id }})">
                            <span class="agent-badge {{ $atCls }}" style="padding:1px 5px;font-size:9px;">{{ $atLbl }}</span>
                            <span class="ai-session-title">{{ $s->title }}</span>
                            @if($s->is_shared)<span style="flex-shrink:0;width:7px;height:7px;border-radius:50%;background:#16a34a;" title="{{ __('ai.team_sharing_badge') }}"></span>@endif
                            <button class="ai-session-del" onclick="event.stopPropagation();deleteSession({{ $s->id }},this)"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif
        </div>

        {{-- 팀 공유 섹션 --}}
        @if($teamSharedSessions->isNotEmpty())
        <div style="height:1px;background:linear-gradient(to right,transparent,#e8e3ff,transparent);margin:10px 0;"></div>
        <div class="ai-section-label" style="display:flex;align-items:center;gap:4px;">
            <svg width="10" height="10" fill="none" stroke="#16a34a" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('ai.team_share') }}
        </div>
        @foreach($teamSharedSessions as $ts)
        <div class="ai-team-session-item" data-shared-id="{{ $ts->id }}" onclick="loadSession({{ $ts->id }})">
            <div class="ai-team-avatar">{{ mb_substr($ts->user->name, 0, 1) }}</div>
            <div style="flex:1;min-width:0;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;color:#374151;">{{ $ts->title }}</div>
                <div style="font-size:10.5px;color:#94a3b8;">{{ $ts->user->name }}</div>
            </div>
        </div>
        @endforeach
        @endif
    </div>
    </div>{{-- ai-left-body --}}

</div>

{{-- ── RIGHT ── --}}
<div class="ai-right">

    {{-- STATE: LANDING --}}
    <div class="ai-state active" id="state-landing">
        <div class="lnd-wrap">
            <div class="lnd-header">
                <h1>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--t600)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                    웍스 Agent
                </h1>
                <p>{!! __('ai.landing_desc') !!}</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;width:100%;max-width:1320px;">
                {{-- GENERAL --}}
                <div class="lnd-card" onclick="startAgent('general')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="lnd-card-ico" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);width:38px;height:38px;border-radius:10px;">
                            <svg width="19" height="19" fill="none" stroke="var(--t600)" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:#ede9fe;color:var(--t600);">GENERAL</span>
                    </div>
                    <h3>{{ __('ai.agent_general_title') }}</h3>
                    <p>{{ __('ai.agent_general_desc') }}</p>
                    <div class="lnd-card-tags">
                        <span class="lnd-card-tag" style="background:#f1f5f9;color:#475569;">{{ __('ai.agent_general_tag1') }}</span>
                        <span class="lnd-card-tag" style="background:#f1f5f9;color:#475569;">{{ __('ai.agent_general_tag2') }}</span>
                        <span class="lnd-card-tag" style="background:#f1f5f9;color:#475569;">{{ __('ai.agent_general_tag3') }}</span>
                    </div>
                    <button class="lnd-card-btn" style="background:linear-gradient(135deg,var(--t600),var(--t500));color:#fff;margin-top:10px;">{{ __('ai.start') }}</button>
                </div>

                {{-- DEV --}}
                <div class="lnd-card" onclick="startAgent('dev')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="lnd-card-ico" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);width:38px;height:38px;border-radius:10px;">
                            <svg width="19" height="19" fill="none" stroke="#2563eb" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:#dbeafe;color:#1d4ed8;">DEV</span>
                    </div>
                    <h3>{{ __('ai.agent_dev_title') }}</h3>
                    <p>{{ __('ai.agent_dev_desc') }}</p>
                    <div class="lnd-card-tags">
                        <span class="lnd-card-tag" style="background:#eff6ff;color:#1d4ed8;">Frontend</span>
                        <span class="lnd-card-tag" style="background:#eff6ff;color:#1d4ed8;">Backend</span>
                        <span class="lnd-card-tag" style="background:#eff6ff;color:#1d4ed8;">DB/API</span>
                    </div>
                    <button class="lnd-card-btn" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;margin-top:10px;">{{ __('ai.start') }}</button>
                </div>

                {{-- DOCUMENT --}}
                <div class="lnd-card" onclick="startAgent('document')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="lnd-card-ico" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);width:38px;height:38px;border-radius:10px;">
                            <svg width="19" height="19" fill="none" stroke="#059669" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:#d1fae5;color:#059669;">DOCUMENT</span>
                    </div>
                    <h3>{{ __('ai.agent_document_title') }}</h3>
                    <p>{{ __('ai.agent_document_desc') }}</p>
                    <div class="lnd-card-tags">
                        <span class="lnd-card-tag" style="background:#ecfdf5;color:#059669;">{{ __('ai.agent_document_tag1') }}</span>
                        <span class="lnd-card-tag" style="background:#ecfdf5;color:#059669;">{{ __('ai.agent_document_tag2') }}</span>
                        <span class="lnd-card-tag" style="background:#ecfdf5;color:#059669;">{{ __('ai.agent_document_tag3') }}</span>
                    </div>
                    <button class="lnd-card-btn" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;margin-top:10px;">{{ __('ai.start') }}</button>
                </div>

                {{-- FIGMA --}}
                <div class="lnd-card" onclick="startAgent('figma')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="lnd-card-ico" style="background:linear-gradient(135deg,#fce7f3,#fbcfe8);width:38px;height:38px;border-radius:10px;">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#be185d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 2v20M5 9h14M5 15h14"/></svg>
                        </div>
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;background:#fce7f3;color:#be185d;">FIGMA</span>
                    </div>
                    <h3>{{ __('ai.agent_figma_title') }}</h3>
                    <p>{{ __('ai.agent_figma_desc') }}</p>
                    <div class="lnd-card-tags">
                        <span class="lnd-card-tag" style="background:#fdf2f8;color:#be185d;">Figma</span>
                        <span class="lnd-card-tag" style="background:#fdf2f8;color:#be185d;">HTML/CSS</span>
                        <span class="lnd-card-tag" style="background:#fdf2f8;color:#be185d;">{{ __('ai.agent_figma_tag3') }}</span>
                    </div>
                    <button class="lnd-card-btn" style="background:linear-gradient(135deg,#be185d,#ec4899);color:#fff;margin-top:10px;">{{ __('ai.start') }}</button>
                </div>

                {{-- 웍스 BUILDER --}}
                <div class="lnd-card" onclick="startAgent('builder')">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <div class="lnd-card-ico" style="background:linear-gradient(135deg,#fef3c7,#fde68a);width:38px;height:38px;border-radius:10px;">
                            <svg width="19" height="19" fill="none" stroke="#92400e" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <span class="agent-badge agent-badge-builder" style="font-size:9px;padding:2px 6px;">NEW</span>
                    </div>
                    <h3>{{ __('ai.agent_builder_title') }}</h3>
                    <p>{{ __('ai.agent_builder_desc') }}</p>
                    <div class="lnd-card-tags">
                        <span class="lnd-card-tag" style="background:#fef3c7;color:#92400e;">{{ __('ai.agent_builder_tag1') }}</span>
                        <span class="lnd-card-tag" style="background:#fef3c7;color:#92400e;">{{ __('ai.agent_builder_tag2') }}</span>
                        <span class="lnd-card-tag" style="background:#fef3c7;color:#92400e;">{{ __('ai.agent_builder_tag3') }}</span>
                    </div>
                    <button class="lnd-card-btn" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;margin-top:10px;">{{ __('ai.start') }}</button>
                </div>
            </div>

            @if($sessions->count())
            <div style="width:100%;max-width:660px;">
                <div style="font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:.07em;text-transform:uppercase;margin-bottom:8px;">{{ __('ai.recent_chats') }}</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($sessions->take(3) as $s)
                    <div onclick="loadSession({{ $s->id }})" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#fff;border:1.5px solid #e8e3ff;border-radius:10px;cursor:pointer;transition:border-color .15s;" onmouseenter="this.style.borderColor='var(--t300)'" onmouseleave="this.style.borderColor='#e8e3ff'">
                        <svg width="13" height="13" fill="none" stroke="var(--t400)" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span style="flex:1;font-size:13px;font-weight:500;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $s->title }}</span>
                        <span style="font-size:11px;color:#94a3b8;flex-shrink:0;">{{ $s->created_at->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- STATE: PROJECT SELECT --}}
    <div class="ai-state" id="state-project-select">
        <div class="pj-wrap">
            <div class="pj-hdr">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <button class="ai-back-btn" onclick="goToLanding()">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('ai.back') }}
                    </button>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('ai.project_select_title') }}</div>
                        <div style="font-size:12px;color:#94a3b8;">{{ __('ai.project_select_desc') }}</div>
                    </div>
                </div>
                <div class="pj-search-wrap">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
                    <input class="pj-search" id="pj-search" placeholder="{{ __('ai.project_search') }}" oninput="filterProjects(this.value)">
                </div>
            </div>
            <div class="pj-list" id="pj-list">
                @forelse($projects as $proj)
                <div class="pj-item" data-name="{{ strtolower($proj->name) }}" onclick="selectProject({{ $proj->id }}, '{{ addslashes($proj->name) }}')">
                    <div class="pj-item-ico">
                        <svg width="17" height="17" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ __('ai.project_select_hint') }}</div>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                @empty
                <div style="padding:48px 20px;text-align:center;color:#94a3b8;">
                    <div style="font-size:36px;opacity:.3;margin-bottom:10px;">📁</div>
                    <div style="font-size:13px;font-weight:600;color:#64748b;margin-bottom:4px;">{{ __('ai.no_projects') }}</div>
                    <div style="font-size:12px;">{{ __('ai.no_projects_hint') }}</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- STATE: CATEGORY SELECT --}}
    <div class="ai-state" id="state-category-select">
        <div class="cat-wrap">
            <div class="cat-hdr">
                <button class="ai-back-btn" onclick="showState('project-select')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('ai.project') }}
                </button>
                <div style="flex:1;">
                    <div style="font-size:14px;font-weight:700;color:#1e1b2e;">{{ __('ai.category_select_title') }}</div>
                    <div style="font-size:11.5px;color:#94a3b8;display:flex;align-items:center;gap:4px;margin-top:1px;">
                        <svg width="10" height="10" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        <span id="cat-proj-name" style="color:#1d4ed8;font-weight:600;"></span>
                    </div>
                </div>
            </div>
            <div class="cat-grid">
                <div class="cat-card" onclick="selectCategory('dev','{{ __('ai.cat_dev_title') }}')">
                    <div class="cat-card-ico" style="background:#eff6ff;">
                        <svg width="20" height="20" fill="none" stroke="#2563eb" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                    <h3>{{ __('ai.cat_dev_title') }}</h3>
                    <p>{{ __('ai.cat_dev_desc') }}</p>
                    <div class="cat-card-sub">
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_dev_sub1') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_dev_sub2') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_dev_sub3') }}</span>
                    </div>
                </div>

                <div class="cat-card" onclick="selectCategory('figma','{{ __('ai.cat_figma_title') }}')">
                    <div class="cat-card-ico" style="background:#f5f3ff;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--t600)"><path d="M15.5 12a3.5 3.5 0 11-7 0 3.5 3.5 0 017 0zM8.5 3A3.5 3.5 0 005 6.5v.5c0 1.38.798 2.573 1.968 3.153A3.5 3.5 0 008.5 17H9v.5a3.5 3.5 0 007 0V17h.5a3.5 3.5 0 001.532-6.647A3.5 3.5 0 0015.5 3h-7zM7 6.5A1.5 1.5 0 018.5 5h3V8H8.5A1.5 1.5 0 017 6.5zM8.5 10H11v3H8.5a1.5 1.5 0 010-3zM13 8V5h2.5a1.5 1.5 0 010 3H13zm0 2h2.5a1.5 1.5 0 010 3H13v-3zm-2 5h2v2.5a1.5 1.5 0 01-3 0V15h1z"/></svg>
                    </div>
                    <h3>{{ __('ai.cat_figma_title') }}</h3>
                    <p>{{ __('ai.cat_figma_desc') }}</p>
                    <div class="cat-card-sub">
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_figma_sub1') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_figma_sub2') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_figma_sub3') }}</span>
                    </div>
                </div>

                <div class="cat-card" onclick="selectCategory('doc','{{ __('ai.cat_doc_title') }}')">
                    <div class="cat-card-ico" style="background:#ecfdf5;">
                        <svg width="20" height="20" fill="none" stroke="#059669" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3>{{ __('ai.cat_doc_title') }}</h3>
                    <p>{{ __('ai.cat_doc_desc') }}</p>
                    <div class="cat-card-sub">
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_doc_sub1') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_doc_sub2') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_doc_sub3') }}</span>
                    </div>
                </div>

                <div class="cat-card" onclick="selectCategory('auto','{{ __('ai.cat_auto_title') }}')">
                    <div class="cat-card-ico" style="background:#fffbeb;">
                        <svg width="20" height="20" fill="none" stroke="#d97706" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115.35-4.65M20 15a9 9 0 01-15.35 4.65"/></svg>
                    </div>
                    <h3>{{ __('ai.cat_auto_title') }}</h3>
                    <p>{{ __('ai.cat_auto_desc') }}</p>
                    <div class="cat-card-sub">
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_auto_sub1') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_auto_sub2') }}</span>
                        <span><svg width="5" height="5" viewBox="0 0 6 6" fill="#94a3b8"><circle cx="3" cy="3" r="3"/></svg>{{ __('ai.cat_auto_sub3') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATE: CHAT --}}
    <div class="ai-state" id="state-chat">
        <div class="chat-hdr">
            <button class="ai-back-btn" onclick="goToLanding()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </button>
            <div class="chat-hdr-title" id="chat-title">{{ $session?->title ?? __('ai.new_chat') }}</div>
            <span id="chat-badge-agent" style="display:none;" class="agent-badge agent-badge-general"></span>
            <div id="chat-badge-proj" style="display:none;" class="ai-badge ai-badge-proj"></div>
            <div id="chat-badge-cat" style="display:none;" class="ai-badge ai-badge-cat"></div>
            <button id="figma-connect-btn" style="display:none;" class="ai-back-btn" onclick="openFigmaModal()">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 12a3.5 3.5 0 11-7 0 3.5 3.5 0 017 0z"/></svg>
                {{ __('ai.figma_connect') }}
            </button>
            <div id="chat-badge-figma" style="display:none;" class="ai-badge ai-badge-fig"></div>
            <button class="ai-share-btn" id="minutes-btn" onclick="exportMinutes()" style="display:none;" title="{{ __('ai.chat_summary') }}">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span id="minutes-btn-label">{{ __('ai.chat_summary') }}</span>
            </button>
            <button class="ai-share-btn" id="share-session-btn" onclick="openShareModal()" style="display:none;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                <span id="share-btn-label">{{ __('ai.team_share_btn') }}</span>
            </button>
            <button class="ai-share-btn" id="proj-files-btn" onclick="openProjectFilesModal()" style="display:none;" title="{{ __('ai.proj_source_files') }}">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                {{ __('ai.source_files') }} <span id="proj-files-count" style="background:var(--t600);color:#fff;border-radius:9px;padding:0 5px;font-size:10px;margin-left:2px;">0</span>
            </button>
        </div>

        {{-- 인라인 Agent 설정 패널 --}}
        <div id="agent-config-panel" style="display:none;background:#fff;border-bottom:2px solid var(--t100);flex-shrink:0;">
            <div style="padding:10px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--t50);">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div id="acp-heading" style="font-size:13px;font-weight:700;color:#1e1b2e;display:flex;align-items:center;gap:7px;flex-shrink:0;"></div>
                    <div id="acp-sub" style="font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                </div>
                <button id="acp-toggle-btn" onclick="toggleConfigPanel()" style="display:flex;align-items:center;gap:4px;padding:4px 10px;border:1.5px solid var(--t200);border-radius:7px;background:transparent;font-size:11.5px;color:#64748b;cursor:pointer;font-family:inherit;flex-shrink:0;margin-left:10px;">
                    <svg id="acp-toggle-icon" width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="transition:transform .18s;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    <span id="acp-toggle-label">{{ __('ai.fold') }}</span>
                </button>
            </div>
            <div id="acp-content" style="padding:16px 28px 16px;overflow-y:auto;max-height:52vh;">

                {{-- DEV 설정 --}}
                <div id="acp-dev" style="display:none;">
                    <div class="dev-settings-grid">
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.backend_framework') }}</label>
                            <select id="dev-framework" onchange="devSelectToggleCustom('dev-framework','dev-framework-custom')">
                                <option value="">{{ __('ai.select_none') }}</option>
                                <option value="Laravel">Laravel</option>
                                <option value="Django">Django</option>
                                <option value="NestJS">NestJS</option>
                                <option value="Spring Boot">Spring Boot</option>
                                <option value="Express">Express</option>
                                <option value="FastAPI">FastAPI</option>
                                <option value="Ruby on Rails">Ruby on Rails</option>
                                <option value="ASP.NET Core">ASP.NET Core</option>
                                <option value="Flask">Flask</option>
                                <option value="Gin">Gin (Go)</option>
                                <option value="__custom__">{{ __('ai.custom_input') }}</option>
                            </select>
                            <input type="text" id="dev-framework-custom" placeholder="{{ __('ai.framework_custom_ph') }}" style="display:none;width:100%;margin-top:5px;">
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.framework_version') }}</label>
                            <input type="text" id="dev-framework-version" placeholder="{{ __('ai.ph_framework_version') }}">
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.runtime_version') }}</label>
                            <input type="text" id="dev-runtime-version" placeholder="{{ __('ai.ph_runtime_version') }}">
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.frontend_stack') }}</label>
                            <select id="dev-frontend" onchange="devSelectToggleCustom('dev-frontend','dev-frontend-custom')">
                                <option value="">{{ __('ai.select_none') }}</option>
                                <option value="HTML / Vanilla JS">HTML / Vanilla JS</option>
                                <option value="React">React</option>
                                <option value="Vue">Vue</option>
                                <option value="Blade">Blade (Laravel)</option>
                                <option value="Angular">Angular</option>
                                <option value="Svelte">Svelte</option>
                                <option value="Next.js">Next.js</option>
                                <option value="Nuxt.js">Nuxt.js</option>
                                <option value="Tailwind CSS">Tailwind CSS</option>
                                <option value="Bootstrap">Bootstrap</option>
                                <option value="__custom__">{{ __('ai.custom_input') }}</option>
                            </select>
                            <input type="text" id="dev-frontend-custom" placeholder="{{ __('ai.frontend_custom_ph') }}" style="display:none;width:100%;margin-top:5px;">
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.database') }}</label>
                            <select id="dev-db-type">
                                <option value="">{{ __('ai.select_none') }}</option>
                                <option value="MySQL">MySQL</option>
                                <option value="PostgreSQL">PostgreSQL</option>
                                <option value="SQLite">SQLite</option>
                                <option value="MongoDB">MongoDB</option>
                                <option value="Redis">Redis</option>
                                <option value="MariaDB">MariaDB</option>
                                <option value="Oracle">Oracle</option>
                                <option value="MSSQL">MSSQL</option>
                            </select>
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.db_version') }}</label>
                            <input type="text" id="dev-db-version" placeholder="{{ __('ai.ph_db_version') }}">
                        </div>
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin:10px 0;"></div>
                    <div class="dev-settings-grid">
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.output_filename') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                            <input type="text" id="dev-output-filename" placeholder="{{ __('ai.ph_output_filename') }}">
                        </div>
                        <div class="ai-field" style="margin-bottom:10px;">
                            <label>{{ __('ai.extension') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                            <input type="text" id="dev-output-extension" placeholder="{{ __('ai.ph_extension') }}">
                        </div>
                    </div>
                    <div class="ai-field" style="margin-bottom:0;">
                        <label>{{ __('ai.project') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                        <select id="dev-project-id">
                            <option value="">{{ __('ai.select_none') }}</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- DOCUMENT 설정 --}}
                <div id="acp-document" style="display:none;">
                    <div style="font-size:10.5px;font-weight:700;color:#94a3b8;letter-spacing:.07em;text-transform:uppercase;margin-bottom:10px;">{{ __('ai.doc_type_label') }}</div>
                    <div class="doc-type-grid" style="margin-bottom:14px;">
                        <div class="doc-type-card" data-type="report" onclick="selectDocType('report',this)">
                            <div class="doc-type-icon">📊</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_report') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_report_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="proposal" onclick="selectDocType('proposal',this)">
                            <div class="doc-type-icon">💼</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_proposal') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_proposal_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="plan" onclick="selectDocType('plan',this)">
                            <div class="doc-type-icon">🗺️</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_plan') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_plan_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="manual" onclick="selectDocType('manual',this)">
                            <div class="doc-type-icon">📖</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_manual') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_manual_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="minutes" onclick="selectDocType('minutes',this)">
                            <div class="doc-type-icon">📝</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_minutes') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_minutes_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="email" onclick="selectDocType('email',this)">
                            <div class="doc-type-icon">✉️</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_email') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_email_desc') }}</div>
                        </div>
                        <div class="doc-type-card" data-type="other" onclick="selectDocType('other',this)">
                            <div class="doc-type-icon">📄</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('ai.doc_other') }}</div>
                            <div style="font-size:11px;color:#64748b;">{{ __('ai.doc_other_desc') }}</div>
                        </div>
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin-bottom:12px;"></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.output_filename') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                            <input type="text" id="doc-output-filename" placeholder="{{ __('ai.ph_doc_filename') }}">
                        </div>
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.file_format') }}</label>
                            <select id="doc-output-extension">
                                <option value="">{{ __('ai.format_auto') }}</option>
                                <option value=".docx">.docx (Word)</option>
                                <option value=".pdf">.pdf (PDF)</option>
                                <option value=".txt">.txt ({{ __('ai.format_text') }})</option>
                                <option value=".md">.md ({{ __('ai.format_markdown') }})</option>
                            </select>
                        </div>
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.project') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                            <select id="doc-project-id">
                                <option value="">{{ __('ai.select_none') }}</option>
                                @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- FIGMA 설정 --}}
                <div id="acp-figma" style="display:none;">
                    <div style="font-size:10.5px;font-weight:700;color:#be185d;letter-spacing:.07em;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 2v20M5 9h14M5 15h14"/></svg>{{ __('ai.figma_source') }}
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.figma_url_label') }} <span style="color:#be185d;">*</span></label>
                            <input type="url" id="figma-url" placeholder="https://www.figma.com/file/XXXXX/..." style="border-color:#e5e7eb;" onfocus="this.style.borderColor='#be185d'" onblur="this.style.borderColor='#e5e7eb'">
                            <div style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ __('ai.figma_url_hint') }}</div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div class="ai-field" style="margin:0;">
                                <label>{{ __('ai.figma_node_id') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                                <input type="text" id="figma-node-id" placeholder="{{ __('ai.ph_figma_node_id') }}">
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">{{ __('ai.figma_node_id_hint') }}</div>
                            </div>
                            <div class="ai-field" style="margin:0;">
                                <label>{{ __('ai.figma_target_path') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                                <input type="text" id="figma-target-path" placeholder="{{ __('ai.ph_figma_target_path') }}">
                            </div>
                        </div>
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin-bottom:12px;"></div>
                    <div style="font-size:10.5px;font-weight:700;color:#be185d;letter-spacing:.07em;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>{{ __('ai.code_gen_options') }}
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.css_framework') }}</label>
                            <select id="figma-css-framework">
                                <option value="vanilla">{{ __('ai.vanilla_css_label') }}</option>
                                <option value="tailwind">Tailwind CSS</option>
                                <option value="bootstrap">Bootstrap 5</option>
                            </select>
                        </div>
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.interaction_level') }}</label>
                            <select id="figma-interaction-level">
                                <option value="hover">{{ __('ai.hover_focus') }}</option>
                                <option value="interactive">{{ __('ai.full_interactive') }}</option>
                                <option value="static">{{ __('ai.static_html') }}</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                        <div class="ai-field" style="margin:0;">
                            <label>Mobile BP <span style="font-weight:400;color:#94a3b8;">(px)</span></label>
                            <input type="number" id="figma-mobile-bp" value="375" min="320" max="600">
                        </div>
                        <div class="ai-field" style="margin:0;">
                            <label>Tablet BP <span style="font-weight:400;color:#94a3b8;">(px)</span></label>
                            <input type="number" id="figma-tablet-bp" value="768" min="600" max="1200">
                        </div>
                        <div class="ai-field" style="margin:0;">
                            <label>{{ __('ai.font_label') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                            <input type="text" id="figma-font-source" placeholder="Google Fonts URL">
                        </div>
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin-bottom:12px;"></div>
                    <div style="font-size:10.5px;font-weight:700;color:#be185d;letter-spacing:.07em;text-transform:uppercase;margin-bottom:10px;">{{ __('ai.integration_method') }}</div>
                    <div style="display:flex;gap:8px;margin-bottom:14px;">
                        <label style="flex:1;display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:1.5px solid #be185d;background:#fdf2f8;border-radius:8px;cursor:pointer;transition:all .15s;" id="il-new-lbl">
                            <input type="radio" name="integration_level" value="new" checked style="margin-top:2px;accent-color:#be185d;" onchange="highlightIntLevel()">
                            <div><div style="font-size:12.5px;font-weight:600;color:#1e1b2e;">{{ __('ai.integration_new') }}</div><div style="font-size:10.5px;color:#64748b;margin-top:1px;">{{ __('ai.integration_new_desc') }}</div></div>
                        </label>
                        <label style="flex:1;display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:all .15s;" id="il-extend-lbl">
                            <input type="radio" name="integration_level" value="extend" style="margin-top:2px;accent-color:#be185d;" onchange="highlightIntLevel()">
                            <div><div style="font-size:12.5px;font-weight:600;color:#1e1b2e;">{{ __('ai.integration_extend') }}</div><div style="font-size:10.5px;color:#64748b;margin-top:1px;">{{ __('ai.integration_extend_desc') }}</div></div>
                        </label>
                        <label style="flex:1;display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:all .15s;" id="il-replace-lbl">
                            <input type="radio" name="integration_level" value="replace" style="margin-top:2px;accent-color:#be185d;" onchange="highlightIntLevel()">
                            <div><div style="font-size:12.5px;font-weight:600;color:#1e1b2e;">{{ __('ai.integration_replace') }}</div><div style="font-size:10.5px;color:#64748b;margin-top:1px;">{{ __('ai.integration_replace_desc') }}</div></div>
                        </label>
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin-bottom:12px;"></div>
                    <div style="font-size:10.5px;font-weight:700;color:#be185d;letter-spacing:.07em;text-transform:uppercase;margin-bottom:6px;">{{ __('ai.existing_assets') }} <span style="font-weight:400;color:#94a3b8;font-size:9.5px;text-transform:none;">{{ __('ai.existing_assets_hint') }}</span></div>
                    <textarea id="figma-existing-assets" rows="3" placeholder="{{ __('ai.existing_assets_ph') }}" style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;color:#1e1b2e;outline:none;background:#f9fafb;resize:vertical;font-family:monospace;line-height:1.5;box-sizing:border-box;margin-bottom:14px;transition:border-color .15s;" onfocus="this.style.borderColor='#be185d'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    <div class="ai-field" style="margin-bottom:0;">
                        <label>{{ __('ai.project') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                        <select id="figma-project-id">
                            <option value="">{{ __('ai.select_none') }}</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- BUILDER 설정 --}}
                <div id="acp-builder" style="display:none;">
                    <div style="font-size:10.5px;font-weight:700;color:#92400e;letter-spacing:.07em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        {{ __('ai.builder_step_select') }}
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                        <button class="builder-step-btn selected" data-step="STEP_1" onclick="selectBuilderStep('STEP_1',this)">
                            <div style="font-size:10px;opacity:.7;margin-bottom:1px;">STEP 1</div>{{ __('ai.builder_step1_label') }}
                        </button>
                        <button class="builder-step-btn" data-step="STEP_2" onclick="selectBuilderStep('STEP_2',this)">
                            <div style="font-size:10px;opacity:.7;margin-bottom:1px;">STEP 2</div>{{ __('ai.builder_step2_label') }}
                        </button>
                        <button class="builder-step-btn" data-step="STEP_3" onclick="selectBuilderStep('STEP_3',this)">
                            <div style="font-size:10px;opacity:.7;margin-bottom:1px;">STEP 3</div>{{ __('ai.builder_step3_label') }}
                        </button>
                        <button class="builder-step-btn" data-step="STEP_4" onclick="selectBuilderStep('STEP_4',this)">
                            <div style="font-size:10px;opacity:.7;margin-bottom:1px;">STEP 4</div>{{ __('ai.builder_step4_label') }}
                        </button>
                        <button class="builder-step-btn" data-step="STEP_FULL" onclick="selectBuilderStep('STEP_FULL',this)" style="border-color:#f59e0b;background:linear-gradient(135deg,#fef3c7,#fff);">
                            <div style="font-size:10px;opacity:.7;margin-bottom:1px;">FULL</div>{{ __('ai.builder_full_label') }}
                        </button>
                    </div>
                    <div id="builder-step-desc" style="font-size:12px;color:#64748b;background:#fef3c7;border-radius:8px;padding:8px 12px;margin-bottom:14px;line-height:1.5;">
                        {{ __('ai.builder_step1_desc') }}
                    </div>
                    <div style="height:1px;background:#f1f5f9;margin-bottom:12px;"></div>
                    <div class="ai-field" style="margin-bottom:0;">
                        <label>{{ __('ai.project') }} <span style="font-weight:400;color:#94a3b8;">{{ __('ai.output_filename_hint') }}</span></label>
                        <select id="builder-project-id">
                            <option value="">{{ __('ai.select_none') }}</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- Builder 작업 중 표시 --}}
        <div id="builder-working-bar" style="display:none;padding:8px 18px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-bottom:1px solid #fde68a;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#92400e;font-weight:600;">
                <span class="builder-pulse"></span>
                <span id="builder-working-step-label">{{ __('ai.builder_working') }}</span>
                <span style="font-size:11px;font-weight:400;color:#b45309;margin-left:4px;">{{ __('ai.builder_working_warn') }}</span>
            </div>
        </div>

        {{-- 팀원 공유 대화 배너 --}}
        <div id="shared-view-banner" style="display:none;padding:10px 18px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;flex-shrink:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;flex-shrink:0;"></span>
                    <span style="font-size:12.5px;color:#166534;" id="shared-owner-label">{{ __('ai.shared_view_label') }}</span>
                </div>
                <button onclick="forkSession()" id="fork-btn" style="display:flex;align-items:center;gap:5px;padding:6px 13px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;flex-shrink:0;transition:opacity .15s;" onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    {{ __('ai.fork_session') }}
                </button>
            </div>
        </div>

        <div class="ai-chat-area" id="chat-area">
            @if($session && $messages->count())
                @foreach($messages as $msg)
                    @include('ai._message', ['msg' => $msg])
                @endforeach
            @endif
        </div>

        {{-- 문맥/파일 수정 제안 패널 --}}
        <div id="suggestion-panel" style="display:none;background:#faf5ff;border-top:1px solid #e9d5ff;padding:10px 18px;flex-shrink:0;font-size:13px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;color:#6d28d9;font-weight:600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                    <span id="suggestion-text"></span>
                </div>
                <div style="display:flex;gap:8px;">
                    <button id="suggestion-yes" onclick="handleSuggestionYes()" style="padding:4px 14px;border-radius:8px;border:1.5px solid #7c3aed;background:#7c3aed;color:#fff;font-size:12px;font-weight:600;cursor:pointer;">{{ __('ai.yes') }}</button>
                    <button id="suggestion-no"  onclick="handleSuggestionNo()"  style="padding:4px 14px;border-radius:8px;border:1.5px solid #a78bfa;background:transparent;color:#7c3aed;font-size:12px;font-weight:600;cursor:pointer;">{{ __('ai.no') }}</button>
                    <button onclick="hideSuggestion()" style="padding:4px 10px;border-radius:8px;border:none;background:transparent;color:#9ca3af;font-size:12px;cursor:pointer;">✕</button>
                </div>
            </div>
        </div>

        <div class="ai-input-area" id="chat-input-area">
            <div class="ai-input-row">
                <div id="figma-selector" onclick="openFigmaPicker()" style="display:none;font-size:11.5px;color:var(--t500);padding:4px 8px;border-radius:6px;background:var(--t100);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;flex-shrink:0;cursor:pointer;">
                    @if($session?->figmaFile) {{ Str::limit($session->figmaFile->name, 16) }} @endif
                </div>
                <textarea id="ai-message-input" rows="1" placeholder="{{ __('ai.message_placeholder') }}" onkeydown="handleInputKeydown(event)" oninput="autoResize(this)"></textarea>
                <button class="ai-send-btn" id="send-btn" onclick="sendMessage()">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
            {{-- 첨부 파일/URL 칩 --}}
            <div id="attach-chips"></div>
            {{-- 첨부 툴바 --}}
            <div class="attach-toolbar">
                <button class="attach-btn" id="file-attach-btn" onclick="document.getElementById('file-input').click()" title="{{ __('ai.file_attach') }}">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    {{ __('ai.file_attach') }}
                </button>
                <button class="attach-btn" id="url-attach-btn" onclick="toggleUrlInput()" title="URL">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    URL
                </button>
                <input type="file" id="file-input" multiple accept=".txt,.md,.html,.htm,.css,.js,.ts,.jsx,.tsx,.vue,.json,.csv,.php,.py,.java,.xml,.yaml,.yml,.sql,.sh,.log,.png,.jpg,.jpeg,.gif,.webp,.pdf,.zip,.docx,.doc,.xlsx,.xls,.pptx,.ppt" style="display:none" onchange="onFilesSelected(this)">
                <div id="attach-url-row">
                    <input type="text" id="attach-url-input" placeholder="https://..." onkeydown="if(event.key==='Enter'){event.preventDefault();addAttachUrl();}if(event.key==='Escape')toggleUrlInput();">
                    <button class="attach-btn" onclick="addAttachUrl()" style="white-space:nowrap;">{{ __('ai.url_add') }}</button>
                </div>
                <div id="ctx-mode-wrap">
                    <span style="font-size:11px;color:#94a3b8;white-space:nowrap;">{{ __('ai.source_label') }}</span>
                    <button class="attach-btn active" id="ctx-btn-all"     onclick="setContextMode('all')">{{ __('ai.source_all') }}</button>
                    <button class="attach-btn"        id="ctx-btn-current" onclick="setContextMode('current')">{{ __('ai.source_current') }}</button>
                    <button class="attach-btn"        id="ctx-btn-none"    onclick="setContextMode('none')">{{ __('ai.source_none') }}</button>
                </div>
            </div>
            <div style="font-size:11px;color:var(--t300);margin-top:4px;padding:0 2px;display:flex;align-items:center;justify-content:space-between;">
                <span>{{ __('ai.input_hint') }}</span>
                <label id="email-toggle-label" style="display:flex;align-items:center;gap:5px;cursor:pointer;padding:3px 8px;border-radius:6px;transition:background .12s;" onmouseover="this.style.background='var(--t50)'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" id="email-toggle" style="width:13px;height:13px;accent-color:var(--t600);cursor:pointer;flex-shrink:0;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="color:var(--t400);flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span style="color:var(--t500);white-space:nowrap;">{{ __('ai.email_result') }}</span>
                </label>
            </div>
        </div>
    </div>

</div>{{-- .ai-right --}}
</div>{{-- .ai-wrap --}}


{{-- (Dev / Document / Figma 설정은 채팅창 상단 인라인 패널 #agent-config-panel 로 통합됨) --}}
{{-- 아래는 더 이상 사용하지 않는 모달 (폼 요소 중복 방지를 위해 내용 제거) --}}
<div id="modal-dev" style="display:none!important;"></div>
<div id="modal-document" style="display:none!important;"></div>
<div id="modal-figma" style="display:none!important;"></div>

{{-- Settings Modal --}}
<div class="ai-modal-backdrop" id="settings-modal">
    <div class="ai-modal" style="width:480px;">
        <h3>{{ __('ai.api_settings') }}</h3>
        <div style="font-size:11.5px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:9px 12px;margin-bottom:14px;line-height:1.6;">
            {!! __('ai.api_priority_note') !!}
        </div>
        <div class="ai-field">
            <label style="display:flex;align-items:center;gap:5px;">
                <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 6px;background:#ede9fe;color:#7c3aed;border-radius:4px;font-size:10px;font-weight:700;">{{ __('ai.priority_1') }}</span>
                Anthropic API Key (Claude)
            </label>
            <input type="password" id="s-anthropic-key" placeholder="{{ $settings->anthropicKey() ? '••••••••••' : 'sk-ant-...' }}" value="{{ $settings->anthropicKey() ? '••••••••••' : '' }}">
        </div>
        <div class="ai-field">
            <label style="display:flex;align-items:center;gap:5px;">
                <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 6px;background:#dcfce7;color:#16a34a;border-radius:4px;font-size:10px;font-weight:700;">{{ __('ai.priority_2') }}</span>
                OpenAI API Key (GPT-4.1) — {{ __('ai.openai_fallback') }}
            </label>
            <input type="password" id="s-openai-key" placeholder="{{ $settings->openaiKey() ? '••••••••••' : 'sk-...' }}" value="{{ $settings->openaiKey() ? '••••••••••' : '' }}">
        </div>
        <div class="ai-field"><label>Figma Personal Access Token</label><input type="password" id="s-figma-token" placeholder="{{ $settings->figmaToken() ? '••••••••••' : 'figd_...' }}" value="{{ $settings->figmaToken() ? '••••••••••' : '' }}"></div>
        <div style="height:1px;background:#f1f5f9;margin:12px 0;"></div>
        <div style="font-size:11.5px;color:#64748b;background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:9px 12px;margin-bottom:12px;line-height:1.6;">
            <strong style="color:#92400e;">Manus API</strong> — {!! __('ai.manus_note') !!}
        </div>
        <div class="ai-field">
            <label>Manus API Key</label>
            <input type="password" id="s-manus-key" placeholder="{{ $settings->manusKey() ? '••••••••••' : 'Manus API Key...' }}" value="{{ $settings->manusKey() ? '••••••••••' : '' }}">
        </div>
        <div class="ai-field">
            <label>Manus API Endpoint <span style="font-weight:400;color:#94a3b8;">{{ __('ai.manus_endpoint_hint') }}</span></label>
            <input type="text" id="s-manus-endpoint" placeholder="https://api.manus.im/v1" value="{{ $settings->manusEndpoint() !== 'https://api.manus.im/v1' ? $settings->manusEndpoint() : '' }}">
        </div>
        <div id="settings-status" style="font-size:12px;min-height:18px;"></div>
        <div class="ai-modal-actions">
            <button class="ai-btn-cancel" onclick="closeModal('settings-modal')">{{ __('ai.cancel') }}</button>
            <button class="ai-btn-primary" onclick="saveSettings()">{{ __('ai.save') }}</button>
        </div>
    </div>
</div>

{{-- Figma URL Modal --}}
<div class="ai-modal-backdrop" id="figma-modal">
    <div class="ai-modal">
        <h3>{{ __('ai.figma_add_file') }}</h3>
        <div class="ai-field"><label>{{ __('ai.figma_file_url') }}</label><input type="url" id="figma-url-input" placeholder="https://www.figma.com/file/..." onkeydown="if(event.key==='Enter')addFigmaFile()"></div>
        <div id="figma-add-status" style="font-size:12px;min-height:18px;"></div>
        <div class="ai-modal-actions">
            <button class="ai-btn-cancel" onclick="closeModal('figma-modal')">{{ __('ai.cancel') }}</button>
            <button class="ai-btn-primary" onclick="addFigmaFile()">{{ __('ai.add') }}</button>
        </div>
    </div>
</div>

{{-- Figma Picker Modal --}}
<div class="ai-modal-backdrop" id="figma-picker-modal">
    <div class="ai-modal" style="width:340px;">
        <h3>{{ __('ai.figma_connect_modal') }}</h3>
        <div id="figma-picker-list" style="max-height:260px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;"></div>
        <div class="ai-modal-actions"><button class="ai-btn-cancel" onclick="closeModal('figma-picker-modal')">{{ __('ai.close') }}</button></div>
    </div>
</div>

{{-- Share Modal --}}
<div class="ai-modal-backdrop" id="share-modal">
    <div class="ai-modal" style="width:400px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="margin:0;">{{ __('ai.share_modal_title') }}</h3>
            <button onclick="closeModal('share-modal')" style="border:none;background:transparent;cursor:pointer;color:#94a3b8;padding:2px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- 비공유 상태 --}}
        <div id="share-off-view">
            <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#f8fafc;border-radius:10px;margin-bottom:16px;">
                <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#e0e7ff,#ddd6fe);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#4f46e5" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ __('ai.share_with_team') }}</div>
                    <div style="font-size:11.5px;color:#94a3b8;margin-top:3px;line-height:1.5;">{!! __('ai.share_hint') !!}</div>
                </div>
            </div>
            <div class="ai-modal-actions">
                <button class="ai-btn-cancel" onclick="closeModal('share-modal')">{{ __('ai.cancel') }}</button>
                <button class="ai-btn-primary" onclick="toggleShare()">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    {{ __('ai.share_do') }}
                </button>
            </div>
        </div>

        {{-- 공유 중 상태 --}}
        <div id="share-on-view" style="display:none;">
            <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;margin-bottom:14px;">
                <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;flex-shrink:0;"></span>
                <div>
                    <div style="font-size:12.5px;font-weight:600;color:#16a34a;">{{ __('ai.sharing_active') }}</div>
                    <div style="font-size:11.5px;color:#4ade80;margin-top:1px;">{{ __('ai.sharing_active_hint') }}</div>
                </div>
            </div>
            <div class="ai-modal-actions">
                <button class="ai-btn-cancel" style="color:#ef4444;border-color:#fecaca;" onclick="toggleShare()">{{ __('ai.unshare') }}</button>
                <button class="ai-btn-primary" onclick="closeModal('share-modal')">{{ __('ai.close') }}</button>
            </div>
        </div>

        <div id="share-status" style="font-size:12px;min-height:16px;margin-top:8px;color:#64748b;"></div>
    </div>
</div>

{{-- Project Files Modal --}}
<div class="ai-modal-backdrop" id="modal-proj-files">
    <div class="ai-modal" style="width:560px;max-height:85vh;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-shrink:0;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e1b2e;">{{ __('ai.proj_source_files') }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:2px;" id="proj-files-modal-proj"></div>
            </div>
            <button onclick="closeModal('modal-proj-files')" style="border:none;background:transparent;cursor:pointer;color:#94a3b8;padding:4px;"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div id="proj-files-modal-list" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
            <div style="text-align:center;color:#94a3b8;font-size:13px;padding:24px;">{{ __('ai.no_saved_files') }}</div>
        </div>
        <div style="margin-top:12px;flex-shrink:0;border-top:1px solid var(--t100);padding-top:12px;">
            <div style="font-size:11.5px;color:#94a3b8;">{{ __('ai.proj_files_note') }}</div>
        </div>
    </div>
</div>

{{-- Add to Project Modal --}}
<div class="ai-modal-backdrop" id="modal-add-to-project">
    <div class="ai-modal" style="width:420px;max-height:80vh;display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-shrink:0;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e1b2e;">{{ __('ai.add_to_project_title') }}</div>
                <div id="atp-file-info" style="font-size:12px;color:#7c3aed;margin-top:3px;font-weight:500;"></div>
            </div>
            <button onclick="closeModal('modal-add-to-project')" style="border:none;background:transparent;cursor:pointer;color:#94a3b8;padding:4px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="font-size:12px;color:#94a3b8;margin-bottom:14px;flex-shrink:0;">{{ __('ai.select_project_hint') }}</div>
        <div id="atp-proj-list" style="flex:1;overflow-y:auto;min-height:80px;"></div>
    </div>
</div>

{{-- Preview Fullscreen Overlay --}}
<div class="preview-fs-overlay" id="preview-fs-overlay">
    <div class="preview-fs-header">
        <div class="preview-fs-header-left">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            {{ __('ai.preview_fullscreen') }}
        </div>
        <button class="preview-fs-close" onclick="closePreviewFs()">{{ __('ai.close_esc') }}</button>
    </div>
    <iframe class="preview-fs-iframe" id="preview-fs-iframe" sandbox="allow-scripts allow-same-origin allow-popups"></iframe>
</div>
@endsection

@section('scripts')
<script>
const AI_STR = {
    newChat:             '{{ __('ai.new_chat') }}',
    fold:                '{{ __('ai.fold') }}',
    expand:              '{{ __('ai.expand') }}',
    devSettings:         '{{ __('ai.dev_settings') }}',
    devSettingsHint:     '{{ __('ai.dev_settings_hint') }}',
    documentSettings:    '{{ __('ai.document_settings') }}',
    documentSettingsHint:'{{ __('ai.document_settings_hint') }}',
    figmaSettings:       '{{ __('ai.figma_settings') }}',
    figmaSettingsHint:   '{{ __('ai.figma_settings_hint') }}',
    builderSettings:     '{{ __('ai.builder_settings') }}',
    builderSettingsHint: '{{ __('ai.builder_settings_hint') }}',
    builderStep1:        '{{ __('ai.builder_step1_label') }}',
    builderStep2:        '{{ __('ai.builder_step2_label') }}',
    builderStep3:        '{{ __('ai.builder_step3_label') }}',
    builderStep4:        '{{ __('ai.builder_step4_label') }}',
    builderFull:         '{{ __('ai.builder_full_label') }}',
    builderWorking:      '{{ __('ai.builder_working') }}',
    builderWorkingWarn:  '{{ __('ai.builder_working_warn') }}',
    catDev:              '{{ __('ai.cat_dev_title') }}',
    catFigma:            '{{ __('ai.cat_figma_title') }}',
    catDoc:              '{{ __('ai.cat_doc_title') }}',
    catAuto:             '{{ __('ai.cat_auto_title') }}',
    saving:              '{{ __('ai.saving') }}',
    saved:               '{{ __('ai.saved') }}',
    saveFailed:          '{{ __('ai.save_failed') }}',
    adding:              '{{ __('ai.adding') }}',
    added:               '{{ __('ai.added_to_project') }}',
    addFailed:           '{{ __('ai.add_failed') }}',
    noFigmaFiles:        '{{ __('ai.figma_no_files') }}',
    figmaFileLabel:      '{{ __('ai.figma_file_label') }}',
    figmaAddFile:        '{{ __('ai.figma_add_file') }}',
    projectLabel:        '{{ __('ai.project') }}',
    otherLabel:          '{{ __('ai.other') }}',
    suggestModifyFile:   '{{ __('ai.suggest_modify_file') }}',
    suggestNewContext:   '{{ __('ai.suggest_new_context') }}',
    fileTypePpt:         '{{ __('ai.file_type_ppt') }}',
    fileTypeExcel:       '{{ __('ai.file_type_excel') }}',
    fileTypeWord:        '{{ __('ai.file_type_word') }}',
    fileTypeMinutes:     '{{ __('ai.file_type_minutes') }}',
    fileTypeDefault:     '{{ __('ai.file_type_default') }}',
    copy:                '{{ __('ai.copy') }}',
    copied:              '{{ __('ai.copied') }}',
    processing:          '{{ __('ai.processing') }}',
    docCompleted:        '{{ __('ai.doc_completed') }}',
    download:            '{{ __('ai.download') }}',
    addToProject:        '{{ __('ai.add_to_project_file') }}',
    addComplete:         '{{ __('ai.add_complete') }}',
    fullscreen:          '{{ __('ai.fullscreen') }}',
    me:                  '{{ __('ai.me') }}',
    sessionCreateFailed: '{{ __('ai.session_create_failed') }}',
    msgSendFailed:       '{{ __('ai.msg_send_failed') }}',
    errorOccurred:       '{{ __('ai.error_occurred') }}',
    retry:               '{{ __('ai.retry') }}',
    emailSent:           '{{ __('ai.email_sent') }}',
    viewFiles:           '{{ __('ai.view_files') }}',
    loadingProjects:     '{{ __('ai.loading_projects') }}',
    loadFailed:          '{{ __('ai.load_failed') }}',
    noJoinedProjects:    '{{ __('ai.no_joined_projects') }}',
    noSavedFiles:        '{{ __('ai.no_saved_files') }}',
    chatSummaryErr:      '{{ __('ai.chat_summary') }}',
    processing2:         '{{ __('ai.processing2') }}',
    sharingProcessing:   '{{ __('ai.sharing_processing') }}',
    shareSuccess:        '{{ __('ai.share_success') }}',
    shareRemoved:        '{{ __('ai.share_removed') }}',
    shareError:          '{{ __('ai.share_error') }}',
    forkingLabel:        '{{ __('ai.forking_label') }}',
    forkError:           '{{ __('ai.fork_error') }}',
    forkDone:            '{{ __('ai.fork_done') }}',
    sharedByOwner:       '{{ __('ai.shared_read_only') }}',
    shareBtnSharing:     '{{ __('ai.sharing_active_short') }}',
    shareBtnTeam:        '{{ __('ai.team_share_btn') }}',
    summaryLabel:        '{{ __('ai.chat_summary') }}',
    summaryFail:         '{{ __('ai.summary_fail') }}',
    pptxFail:            '{{ __('ai.pptx_fail') }}',
    excelFail:           '{{ __('ai.excel_fail') }}',
    excelGenerated:      '{{ __('ai.excel_generated') }}',
    docFallbackName:     '{{ __('ai.doc_fallback_name') }}',
    sharedByLabel:       '{{ __('ai.shared_by_label') }}',
    dateFormatTpl:       '{{ __('ai.date_format_tpl') }}',
    removeBtn:           '{{ __('ai.remove_btn') }}',
};
const CSRF    = '{{ csrf_token() }}';
const AI_BASE = '{{ url("/ai") }}';
const AI_PATH = '{{ request()->getBasePath() }}/ai';
const ROUTES  = {
    settings:     '{{ route("ai.settings") }}',
    figmaAdd:     '{{ route("ai.figma.add") }}',
    figmaSync:    id => `${AI_BASE}/figma/${id}/sync`,
    figmaDelete:  id => `${AI_BASE}/figma/${id}`,
    sessCreate:   '{{ route("ai.sessions.create") }}',
    sessGet:      id => `${AI_BASE}/sessions/${id}`,
    sessDelete:   id => `${AI_BASE}/sessions/${id}`,
    sessMsg:      id => `${AI_BASE}/sessions/${id}/messages`,
    sessShare:    id => `${AI_BASE}/sessions/${id}/share`,
    sessFork:     id => `${AI_BASE}/sessions/${id}/fork`,
    sessGenPrompt:id => `${AI_BASE}/sessions/${id}/generate-prompt`,
    promptRefine: '{{ route("ai.prompts.refine") }}',
    promptStore:  '{{ route("ai.prompts.store") }}',
    projFiles:      id => `${AI_BASE}/projects/${id}/files`,
    sessCtxCheck:   id => `${AI_BASE}/sessions/${id}/context-check`,
};

let currentSessionId = @json($session?->id);
let currentFigmaId   = @json($session?->figma_file_id);
let currentFigmaName = @json($session?->figmaFile?->name);
let currentProjectId   = @json($session?->project_id);
let currentProjectName = @json($session?->project?->name);
let currentCategory    = @json($session?->prompt_category);
let currentContextMode = 'all';
let projectFiles = [];
let figmaFiles       = @json($figmaFiles);
let sending          = false;
let pendingPromptId  = null;
let attachedFiles    = [];
let attachedUrls     = [];
let pendingFigmaFileId = null;
let currentSessionShared = @json($session?->is_shared ?? false);
let isSharedView         = false;
let sharedSessionOwner   = null;
// Agent system
let currentAgentType     = @json($session?->agent_type ?? 'general');
let currentDevSettings   = @json($session?->dev_settings ?? []);
let currentDocType       = @json($session?->doc_type);
let currentOutputFilename= @json($session?->output_filename);
let currentOutputExtension=@json($session?->output_extension);
let currentBuilderStep   = @json($session?->dev_settings['builder_step'] ?? 'STEP_1');
// Context / file tracking
let lastGeneratedFileType = null; // 'pptx' | 'excel' | 'word' | 'minutes' | null

// URL param auto-apply
(function() {
    const p = new URLSearchParams(location.search);
    const pt = p.get('prompt_text'), pi = p.get('prompt_id');
    if (pt) {
        pendingPromptId = pi ? parseInt(pi) : null;
        window.addEventListener('sessionReady', () => {
            const el = document.getElementById('ai-message-input');
            if (el) { el.value = pt; autoResize(el); el.focus(); }
        }, { once: true });
    }
})();

// Builder 작업 중 페이지 이탈 방지
window.addEventListener('beforeunload', (e) => {
    if (sending && currentAgentType === 'builder') {
        e.preventDefault();
        return (e.returnValue = `${AI_STR.builderWorking} ${AI_STR.builderWorkingWarn}`);
    }
});

// ── Helpers ────────────────────────────────────────────────────
async function post(url, data={}) {
    const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify(data) });
    return r.json();
}
async function postForm(url, fd) {
    const r = await fetch(url, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:fd });
    return r.json();
}
async function del(url) {
    const r = await fetch(url, { method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
    return r.json();
}
async function get(url) {
    const r = await fetch(url, { headers:{'Accept':'application/json'} });
    return r.json();
}
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function openModal(id)  { document.getElementById(id).classList.add('show'); }
function autoResize(el) { el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,140)+'px'; }
function scrollToBottom() { const a=document.getElementById('chat-area'); if(a) a.scrollTop=a.scrollHeight; }
function escHtml(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── State Machine ──────────────────────────────────────────────
function showState(name) {
    document.querySelectorAll('.ai-state').forEach(el => el.classList.remove('active'));
    document.getElementById('state-' + name)?.classList.add('active');
}

function goToLanding() {
    currentSessionId       = null; currentProjectId = null; currentProjectName = null; currentCategory = null;
    currentSessionShared   = false; isSharedView = false; sharedSessionOwner = null;
    currentAgentType       = 'general'; currentDevSettings = {}; currentDocType = null;
    currentOutputFilename  = null; currentOutputExtension = null;
    document.querySelectorAll('.ai-session-item,.ai-team-session-item').forEach(el => el.classList.remove('active'));
    document.getElementById('share-session-btn').style.display = 'none';
    setSharedMode(false);
    updateLeftProjectBadge();
    showState('landing');
    history.replaceState(null, '', AI_PATH);
}

function newSessionKeepSettings() {
    // Preserve current agent type, settings and project — just reset session state
    const savedType    = currentAgentType;
    const savedProject = currentProjectId;
    const savedProjName= currentProjectName;

    currentSessionId     = null;
    currentSessionShared = false;
    isSharedView         = false;
    sharedSessionOwner   = null;
    document.querySelectorAll('.ai-session-item,.ai-team-session-item').forEach(el => el.classList.remove('active'));
    setSharedMode(false);

    createSession(); // shows chat UI with blank chat area

    // Re-show config panel (collapsed) if non-general, preserving form values
    if (savedType && savedType !== 'general') {
        setupConfigPanel(savedType, false);
    }
}

function openProjectSelect() {
    document.getElementById('pj-search').value = '';
    filterProjects('');
    showState('project-select');
}

function filterProjects(q) {
    const lq = q.toLowerCase();
    document.querySelectorAll('#pj-list .pj-item').forEach(el => {
        el.style.display = el.dataset.name.includes(lq) ? '' : 'none';
    });
}

function selectProject(id, name) {
    currentProjectId = id; currentProjectName = name;
    document.getElementById('cat-proj-name').textContent = name;
    updateLeftProjectBadge();
    showState('category-select');
}

async function selectCategory(catId, catName) {
    currentCategory = catId;
    await createSession();
}

async function startGeneral() {
    currentProjectId = null; currentProjectName = null; currentCategory = null;
    await createSession();
}

function startAgent(type) {
    currentAgentType       = type;
    currentDevSettings     = {};
    currentDocType         = null;
    currentProjectId       = null;
    currentProjectName     = null;
    currentCategory        = null;
    currentOutputFilename  = null;
    currentOutputExtension = null;
    if (type === 'builder') currentBuilderStep = 'STEP_1';
    createSession();
    if (type !== 'general') {
        setupConfigPanel(type, type === 'builder' ? true : false);
    }
}

function setupConfigPanel(type, expanded) {
    const cfgMap = {
        dev:      { html: `${getAgentBadgeHtml('dev')} ${AI_STR.devSettings}`,          sub: AI_STR.devSettingsHint },
        document: { html: `${getAgentBadgeHtml('document')} ${AI_STR.documentSettings}`, sub: AI_STR.documentSettingsHint },
        figma:    { html: `${getAgentBadgeHtml('figma')} ${AI_STR.figmaSettings}`,        sub: AI_STR.figmaSettingsHint },
        builder:  { html: `${getAgentBadgeHtml('builder')} ${AI_STR.builderSettings}`,   sub: AI_STR.builderSettingsHint },
    };
    const cfg = cfgMap[type];
    if (!cfg) return;

    document.getElementById('acp-heading').innerHTML = cfg.html;
    document.getElementById('acp-sub').textContent = cfg.sub;

    ['dev', 'document', 'figma', 'builder'].forEach(t =>
        document.getElementById(`acp-${t}`).style.display = t === type ? '' : 'none'
    );

    const content = document.getElementById('acp-content');
    const icon    = document.getElementById('acp-toggle-icon');
    const label   = document.getElementById('acp-toggle-label');
    if (expanded) {
        content.style.display = '';
        if (icon)  icon.style.transform  = '';
        if (label) label.textContent     = AI_STR.fold;
    } else {
        content.style.display = 'none';
        if (icon)  icon.style.transform  = 'rotate(180deg)';
        if (label) label.textContent     = AI_STR.expand;
    }

    document.getElementById('agent-config-panel').style.display = '';
    if (type === 'figma') highlightIntLevel();
}

function toggleConfigPanel() {
    const content = document.getElementById('acp-content');
    const icon    = document.getElementById('acp-toggle-icon');
    const label   = document.getElementById('acp-toggle-label');
    const collapsed = content.style.display === 'none';
    content.style.display = collapsed ? '' : 'none';
    if (icon)  icon.style.transform  = collapsed ? '' : 'rotate(180deg)';
    if (label) label.textContent     = collapsed ? AI_STR.fold : AI_STR.expand;
}

function devSelectToggleCustom(selId, inputId) {
    const sel = document.getElementById(selId);
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const show = sel.value === '__custom__';
    inp.style.display = show ? 'block' : 'none';
    if (show) inp.focus();
}

function devSelectValue(selId, inputId) {
    const sel = document.getElementById(selId);
    if (!sel) return '';
    if (sel.value === '__custom__') return document.getElementById(inputId)?.value.trim() || '';
    return sel.value;
}

function collectDevSettingsFromForm() {
    const fw    = devSelectValue('dev-framework', 'dev-framework-custom');
    const fwVer = document.getElementById('dev-framework-version')?.value.trim();
    const rtVer = document.getElementById('dev-runtime-version')?.value.trim();
    const fe    = devSelectValue('dev-frontend', 'dev-frontend-custom');
    const db    = document.getElementById('dev-db-type')?.value;
    const dbVer = document.getElementById('dev-db-version')?.value.trim();
    const s = {};
    if (fw)    s.framework          = fw;
    if (fwVer) s.framework_version  = fwVer;
    if (rtVer) s.runtime_version    = rtVer;
    if (fe)    s.frontend_stack     = fe;
    if (db)    s.db_type            = db;
    if (dbVer) s.db_version         = dbVer;
    return Object.keys(s).length ? s : null;
}

function selectDocType(type, el) {
    currentDocType = type;
    document.querySelectorAll('.doc-type-card').forEach(c => c.classList.remove('selected'));
    if (el) el.classList.add('selected');
}

function syncProjectFromConfigForm() {
    const selId = currentAgentType === 'dev'      ? 'dev-project-id'
                : currentAgentType === 'document' ? 'doc-project-id'
                : currentAgentType === 'figma'    ? 'figma-project-id'
                : currentAgentType === 'builder'  ? 'builder-project-id' : null;
    if (!selId) return;
    const sel = document.getElementById(selId);
    if (!sel) return;
    if (sel.value) {
        currentProjectId   = parseInt(sel.value);
        currentProjectName = sel.selectedOptions[0]?.text || null;
    } else {
        currentProjectId   = null;
        currentProjectName = null;
    }
}

function highlightIntLevel() {
    const radios = document.querySelectorAll('input[name="integration_level"]');
    radios.forEach(r => {
        const lbl = r.closest('label');
        if (!lbl) return;
        if (r.checked) {
            lbl.style.borderColor = '#be185d';
            lbl.style.background  = '#fdf2f8';
        } else {
            lbl.style.borderColor = '#e5e7eb';
            lbl.style.background  = '#fff';
        }
    });
}

const BUILDER_STEP_DESCS = {
    STEP_1:    '{{ __('ai.builder_step1_desc') }}',
    STEP_2:    '{{ __('ai.builder_step2_desc') }}',
    STEP_3:    '{{ __('ai.builder_step3_desc') }}',
    STEP_4:    '{{ __('ai.builder_step4_desc') }}',
    STEP_FULL: '{{ __('ai.builder_full_desc') }}',
};
function selectBuilderStep(step, el) {
    currentBuilderStep = step;
    document.querySelectorAll('.builder-step-btn').forEach(b => b.classList.remove('selected'));
    if (el) el.classList.add('selected');
    const desc = document.getElementById('builder-step-desc');
    if (desc) desc.textContent = BUILDER_STEP_DESCS[step] || '';
}
function collectBuilderSettingsFromForm() {
    return { builder_step: currentBuilderStep || 'STEP_1' };
}

function collectFigmaSettingsFromForm() {
    const url = document.getElementById('figma-url')?.value.trim();
    if (!url) return null;
    const nodeId       = document.getElementById('figma-node-id')?.value.trim();
    const targetPath   = document.getElementById('figma-target-path')?.value.trim();
    const intLevel     = document.querySelector('input[name="integration_level"]:checked')?.value || 'new';
    const cssFramework = document.getElementById('figma-css-framework')?.value || 'vanilla';
    const interLevel   = document.getElementById('figma-interaction-level')?.value || 'hover';
    const mobileBp     = parseInt(document.getElementById('figma-mobile-bp')?.value) || 375;
    const tabletBp     = parseInt(document.getElementById('figma-tablet-bp')?.value) || 768;
    const fontSource   = document.getElementById('figma-font-source')?.value.trim();
    const existing     = document.getElementById('figma-existing-assets')?.value.trim();
    const s = { figma_url: url, integration_level: intLevel, css_framework: cssFramework, interaction_level: interLevel, mobile_bp: mobileBp, tablet_bp: tabletBp };
    if (nodeId)   s.figma_node_id    = nodeId;
    if (targetPath) s.target_path    = targetPath;
    if (fontSource) s.font_source    = fontSource;
    if (existing)   s.existing_assets= existing;
    return s;
}

function prefillConfigForm(s) {
    if (!s.agent_type || s.agent_type === 'general') return;
    setupConfigPanel(s.agent_type, false); // collapsed for existing sessions

    if (s.agent_type === 'dev' && s.dev_settings) {
        const ds = s.dev_settings;
        const setVal = (id, v) => { const el = document.getElementById(id); if (el && v) el.value = v; };
        setVal('dev-framework-version', ds.framework_version);
        setVal('dev-runtime-version',   ds.runtime_version);
        setVal('dev-db-version',        ds.db_version);
        setVal('dev-output-filename',   s.output_filename);
        setVal('dev-output-extension',  s.output_extension);
        if (ds.framework) {
            const sel = document.getElementById('dev-framework');
            if (sel) {
                const opt = [...sel.options].find(o => o.value === ds.framework);
                if (opt) sel.value = ds.framework;
                else { sel.value = '__custom__'; devSelectToggleCustom('dev-framework','dev-framework-custom'); const ci = document.getElementById('dev-framework-custom'); if(ci) ci.value = ds.framework; }
            }
        }
        if (ds.frontend_stack) {
            const sel = document.getElementById('dev-frontend');
            if (sel) {
                const opt = [...sel.options].find(o => o.value === ds.frontend_stack);
                if (opt) sel.value = ds.frontend_stack;
                else { sel.value = '__custom__'; devSelectToggleCustom('dev-frontend','dev-frontend-custom'); const ci = document.getElementById('dev-frontend-custom'); if(ci) ci.value = ds.frontend_stack; }
            }
        }
        if (ds.db_type) { const el = document.getElementById('dev-db-type'); if (el) el.value = ds.db_type; }
        if (s.project_id) { const el = document.getElementById('dev-project-id'); if (el) el.value = s.project_id; }
    }

    if (s.agent_type === 'document') {
        if (s.doc_type) selectDocType(s.doc_type, document.querySelector(`.doc-type-card[data-type="${s.doc_type}"]`));
        const setVal = (id, v) => { const el = document.getElementById(id); if (el && v) el.value = v; };
        setVal('doc-output-filename',  s.output_filename);
        setVal('doc-output-extension', s.output_extension);
        if (s.project_id) { const el = document.getElementById('doc-project-id'); if (el) el.value = s.project_id; }
    }

    if (s.agent_type === 'figma' && s.dev_settings) {
        const fs = s.dev_settings;
        const setVal = (id, v) => { const el = document.getElementById(id); if (el && v != null) el.value = v; };
        setVal('figma-url',          fs.figma_url);
        setVal('figma-node-id',      fs.figma_node_id);
        setVal('figma-target-path',  fs.target_path);
        setVal('figma-css-framework',fs.css_framework);
        setVal('figma-interaction-level', fs.interaction_level);
        setVal('figma-mobile-bp',    fs.mobile_bp);
        setVal('figma-tablet-bp',    fs.tablet_bp);
        setVal('figma-font-source',  fs.font_source);
        setVal('figma-existing-assets', fs.existing_assets);
        if (fs.integration_level) {
            const r = document.querySelector(`input[name="integration_level"][value="${fs.integration_level}"]`);
            if (r) { r.checked = true; highlightIntLevel(); }
        }
        if (s.project_id) { const el = document.getElementById('figma-project-id'); if (el) el.value = s.project_id; }
    }

    if (s.agent_type === 'builder' && s.dev_settings) {
        const bs = s.dev_settings;
        if (bs.builder_step) {
            currentBuilderStep = bs.builder_step;
            const btn = document.querySelector(`.builder-step-btn[data-step="${bs.builder_step}"]`);
            selectBuilderStep(bs.builder_step, btn);
        }
        if (s.project_id) { const el = document.getElementById('builder-project-id'); if (el) el.value = s.project_id; }
    }
}

function getAgentBadgeHtml(type, small) {
    const map = {
        general:  { label:'GENERAL',  cls:'agent-badge-general' },
        dev:      { label:'DEV',      cls:'agent-badge-dev' },
        document: { label:'DOCUMENT', cls:'agent-badge-document' },
        figma:    { label:'FIGMA',    cls:'agent-badge-figma' },
        builder:  { label:'BUILDER',  cls:'agent-badge-builder' },
    };
    const a = map[type] || map.general;
    const st = small ? 'padding:1px 5px;font-size:9px;' : '';
    return `<span class="agent-badge ${a.cls}" style="${st}">${a.label}</span>`;
}

function updateAgentBadge(type) {
    const el = document.getElementById('chat-badge-agent');
    if (!el) return;
    if (!type || type === 'general') { el.style.display = 'none'; return; }
    const map = {
        dev:      { cls:'agent-badge-dev',      label:'DEV' },
        document: { cls:'agent-badge-document', label:'DOCUMENT' },
        figma:    { cls:'agent-badge-figma',    label:'FIGMA' },
        builder:  { cls:'agent-badge-builder',  label:'BUILDER' },
    };
    const a = map[type];
    if (!a) { el.style.display = 'none'; return; }
    el.className = `agent-badge ${a.cls}`;
    el.textContent = a.label;
    el.style.display = 'inline-flex';
}

function updateLeftProjectBadge() {
    const badge = document.getElementById('left-proj-badge');
    const name  = document.getElementById('left-proj-name');
    if (currentProjectName) { name.textContent = currentProjectName; badge.style.display = 'block'; }
    else badge.style.display = 'none';
}

// ── Sessions ───────────────────────────────────────────────────
async function createSession() {
    currentSessionId     = null;
    currentSessionShared = false;
    isSharedView         = false;
    sharedSessionOwner   = null;
    pendingFigmaFileId   = currentCategory === 'figma' ? (currentFigmaId || null) : null;
    showChatUI(AI_STR.newChat);
    document.getElementById('chat-area').innerHTML = '';
    document.getElementById('ai-message-input').focus();
    window.dispatchEvent(new CustomEvent('sessionReady'));
}

async function ensureSession() {
    if (currentSessionId) return true;

    // Read current form values before creating session
    let devSettings = Object.keys(currentDevSettings||{}).length ? currentDevSettings : null;
    let docType     = currentDocType || null;
    let outFn       = currentOutputFilename  || null;
    let outEx       = currentOutputExtension || null;

    if (currentAgentType === 'dev') {
        syncProjectFromConfigForm();
        devSettings = collectDevSettingsFromForm() || devSettings;
        outFn = document.getElementById('dev-output-filename')?.value.trim() || outFn;
        outEx = document.getElementById('dev-output-extension')?.value.trim() || outEx;
    } else if (currentAgentType === 'document') {
        syncProjectFromConfigForm();
        docType = currentDocType || null;
        outFn = document.getElementById('doc-output-filename')?.value.trim() || outFn;
        outEx = document.getElementById('doc-output-extension')?.value || outEx;
    } else if (currentAgentType === 'figma') {
        syncProjectFromConfigForm();
        devSettings = collectFigmaSettingsFromForm() || devSettings;
    } else if (currentAgentType === 'builder') {
        syncProjectFromConfigForm();
        devSettings = collectBuilderSettingsFromForm();
    }

    const payload = {
        figma_file_id:    pendingFigmaFileId || null,
        project_id:       currentProjectId   || null,
        prompt_category:  currentCategory    || null,
        agent_type:       currentAgentType   || 'general',
        dev_settings:     devSettings,
        doc_type:         docType,
        output_filename:  outFn,
        output_extension: outEx,
    };
    const res = await post(ROUTES.sessCreate, payload);
    if (!res.ok) return false;
    const s = res.session;
    currentSessionId     = s.id;
    currentSessionShared = false;
    isSharedView         = false;
    sharedSessionOwner   = null;
    if (s.figma_file) { currentFigmaId = s.figma_file.id; currentFigmaName = s.figma_file.name; }
    updateShareBtn();
    prependSessionToList(s);
    history.replaceState(null, '', `${AI_PATH}?session=${s.id}`);
    return true;
}

async function loadSession(id) {
    if (currentSessionId === id) { showState('chat'); return; }
    document.querySelectorAll('.ai-session-item,.ai-team-session-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.ai-session-item[data-id="${id}"]`)?.classList.add('active');
    document.querySelector(`.ai-team-session-item[data-shared-id="${id}"]`)?.classList.add('active');
    const res = await get(ROUTES.sessGet(id));
    if (!res.ok) return;
    const s = res.session;
    currentSessionId       = s.id;
    currentProjectId       = s.project_id      ?? null;
    currentProjectName     = s.project?.name   ?? null;
    currentCategory        = s.prompt_category ?? null;
    currentFigmaId         = s.figma_file?.id  ?? null;
    currentFigmaName       = s.figma_file?.name ?? null;
    currentSessionShared   = s.is_shared ?? false;
    isSharedView           = !(res.is_owner ?? true);
    sharedSessionOwner     = isSharedView ? (s.user ?? null) : null;
    currentAgentType       = s.agent_type      ?? 'general';
    currentDevSettings     = s.dev_settings    ?? {};
    currentDocType         = s.doc_type        ?? null;
    currentOutputFilename  = s.output_filename ?? null;
    currentOutputExtension = s.output_extension?? null;
    currentBuilderStep     = s.dev_settings?.builder_step ?? 'STEP_1';
    showChatUI(s.title, currentProjectName, currentCategory ? getCategoryLabel(currentCategory) : null, s.figma_file);
    if (s.agent_type && s.agent_type !== 'general') prefillConfigForm(s);
    const area = document.getElementById('chat-area');
    area.innerHTML = '';
    lastGeneratedFileType = null;
    (s.messages || []).forEach(m => {
        area.insertAdjacentHTML('beforeend', buildMessageHtml(m));
        if (m.role === 'assistant' && m.doc_file_type) {
            lastGeneratedFileType = m.doc_file_type === 'pptx' ? 'pptx'
                : (m.doc_file_type === 'xlsx' ? 'excel'
                : (m.doc_file_type === 'docx' ? (m.doc_file_name?.toLowerCase().includes('회의록') ? 'minutes' : 'word') : null));
        }
    });
    hideSuggestion();
    scrollToBottom();
    history.replaceState(null, '', `${AI_PATH}?session=${id}`);
    window.dispatchEvent(new CustomEvent('sessionReady'));
}

async function deleteSession(id, btn) {
    btn.style.opacity = '.4';
    const res = await del(ROUTES.sessDelete(id));
    if (res.ok) {
        const item  = document.querySelector(`.ai-session-item[data-id="${id}"]`);
        const group = item?.closest('.sess-proj-group');
        item?.remove();
        if (group) {
            const remaining = group.querySelectorAll('.ai-session-item').length;
            if (remaining === 0) {
                group.remove();
            } else {
                const cntEl = group.querySelector('.sess-proj-count');
                if (cntEl) cntEl.textContent = remaining;
            }
        }
        if (currentSessionId === id) goToLanding();
    }
    btn.style.opacity = '1';
}

// ── Share & Fork ───────────────────────────────────────────────
function setSharedMode(on) {
    const banner   = document.getElementById('shared-view-banner');
    const inputArea = document.getElementById('chat-input-area');
    const shareBtn = document.getElementById('share-session-btn');
    banner.style.display   = on ? '' : 'none';
    inputArea.style.display = on ? 'none' : '';
    if (on && sharedSessionOwner) {
        document.getElementById('shared-owner-label').textContent =
            `${sharedSessionOwner.name}${AI_STR.sharedByLabel} · ${AI_STR.sharedByOwner}`;
    }
    if (on) shareBtn.style.display = 'none';
}

function updateShareBtn() {
    const btn     = document.getElementById('share-session-btn');
    const minBtn  = document.getElementById('minutes-btn');
    if (!currentSessionId || isSharedView) {
        btn.style.display    = 'none';
        minBtn.style.display = 'none';
        return;
    }
    btn.style.display    = 'flex';
    minBtn.style.display = 'flex';
    if (currentSessionShared) {
        btn.classList.add('shared');
        document.getElementById('share-btn-label').textContent = AI_STR.shareBtnSharing;
    } else {
        btn.classList.remove('shared');
        document.getElementById('share-btn-label').textContent = AI_STR.shareBtnTeam;
    }
}

async function exportMinutes() {
    if (!currentSessionId) return;
    const btn   = document.getElementById('minutes-btn');
    const label = document.getElementById('minutes-btn-label');
    label.textContent = AI_STR.processing;
    btn.disabled = true;

    try {
        const res  = await fetch(`${AI_BASE}/sessions/${currentSessionId}/minutes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ date: 'today' }),
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || AI_STR.summaryFail);

        // 채팅창에 결과 메시지 추가
        const fakeMsg = { doc_file_name: data.file_name, doc_file_type: 'docx', doc_download_url: data.download_url, doc_status: 'completed' };
        const html = `<div class="ai-msg assistant">
            <div class="ai-msg-av">웍스</div>
            <div class="ai-msg-body" style="flex:1;max-width:100%;">
                <div class="ai-bubble" style="line-height:1.75;">${mdToHtml(data.minutes_text)}</div>
                ${buildDocCard(fakeMsg)}
            </div>
        </div>`;
        const msgs = document.getElementById('chat-area');
        msgs.insertAdjacentHTML('beforeend', html);
        msgs.scrollTop = msgs.scrollHeight;
    } catch (e) {
        alert(AI_STR.summaryFail + e.message);
    } finally {
        label.textContent = AI_STR.summaryLabel;
        btn.disabled = false;
    }
}

async function exportPptx() {
    if (!currentSessionId) return;
    try {
        const res  = await fetch(`${AI_BASE}/sessions/${currentSessionId}/pptx`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'PPTX error');

        const fakeMsg = { doc_file_name: data.file_name, doc_file_type: 'pptx', doc_download_url: data.download_url, doc_status: 'completed' };
        const html = `<div class="ai-msg assistant">
            <div class="ai-msg-av">웍스</div>
            <div class="ai-msg-body" style="flex:1;max-width:100%;">
                <div class="ai-bubble" style="line-height:1.75;">${mdToHtml(data.ppt_text)}</div>
                ${buildDocCard(fakeMsg)}
            </div>
        </div>`;
        const msgs = document.getElementById('chat-area');
        msgs.insertAdjacentHTML('beforeend', html);
        msgs.scrollTop = msgs.scrollHeight;
    } catch (e) {
        alert(AI_STR.pptxFail + e.message);
    }
}

async function exportExcel() {
    if (!currentSessionId) return;
    try {
        const res  = await fetch(`${AI_BASE}/sessions/${currentSessionId}/excel`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Excel error');

        const fakeMsg = { doc_file_name: data.message?.doc_file_name, doc_file_type: 'xlsx', doc_download_url: data.message?.doc_download_url, doc_status: 'completed' };
        const html = `<div class="ai-msg assistant">
            <div class="ai-msg-av">웍스</div>
            <div class="ai-msg-body" style="flex:1;max-width:100%;">
                <div class="ai-bubble" style="line-height:1.75;">${mdToHtml(data.message?.content || AI_STR.excelGenerated)}</div>
                ${buildDocCard(fakeMsg)}
            </div>
        </div>`;
        const msgs = document.getElementById('chat-area');
        msgs.insertAdjacentHTML('beforeend', html);
        msgs.scrollTop = msgs.scrollHeight;
    } catch (e) {
        alert(AI_STR.excelFail + e.message);
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}


function openShareModal() {
    if (!currentSessionId || isSharedView) return;
    document.getElementById('share-status').textContent = '';
    document.getElementById('share-off-view').style.display = currentSessionShared ? 'none' : 'block';
    document.getElementById('share-on-view').style.display  = currentSessionShared ? 'block' : 'none';
    openModal('share-modal');
}

async function toggleShare() {
    if (!currentSessionId) return;
    const statusEl = document.getElementById('share-status');
    statusEl.textContent = AI_STR.sharingProcessing;
    const res = await post(ROUTES.sessShare(currentSessionId));
    if (!res.ok) { statusEl.textContent = AI_STR.shareError; return; }
    currentSessionShared = res.shared;
    updateShareBtn();
    // sidebar dot 업데이트
    const dot = document.querySelector(`.ai-session-item[data-id="${currentSessionId}"] span[title="{{ __('ai.team_sharing_badge') }}"]`);
    if (currentSessionShared) {
        if (!dot) {
            const item = document.querySelector(`.ai-session-item[data-id="${currentSessionId}"]`);
            if (item) {
                const span = document.createElement('span');
                span.style.cssText = 'flex-shrink:0;width:7px;height:7px;border-radius:50%;background:#16a34a;';
                span.title = AI_STR.shareBtnSharing;
                item.querySelector('button.ai-session-del')?.before(span);
            }
        }
    } else {
        dot?.remove();
    }
    // 모달 상태 갱신
    document.getElementById('share-off-view').style.display = currentSessionShared ? 'none' : 'block';
    document.getElementById('share-on-view').style.display  = currentSessionShared ? 'block' : 'none';
    statusEl.textContent = currentSessionShared ? AI_STR.shareSuccess : AI_STR.shareRemoved;
}

async function forkSession() {
    if (!currentSessionId) return;
    const btn = document.getElementById('fork-btn');
    btn.disabled = true;
    btn.textContent = AI_STR.forkingLabel;
    const res = await post(ROUTES.sessFork(currentSessionId));
    if (!res.ok) { btn.disabled = false; btn.textContent = AI_STR.forkError; return; }
    const s = res.session;
    // 내 세션으로 전환
    isSharedView         = false;
    sharedSessionOwner   = null;
    currentSessionShared = false;
    currentSessionId     = s.id;
    setSharedMode(false);
    updateShareBtn();
    document.getElementById('chat-title').textContent = s.title;
    prependSessionToList(s);
    history.replaceState(null, '', `${AI_PATH}?session=${s.id}`);
    // 메시지 새로고침
    const area = document.getElementById('chat-area');
    area.innerHTML = '';
    (s.messages || []).forEach(m => area.insertAdjacentHTML('beforeend', buildMessageHtml(m)));
    scrollToBottom();
    btn.disabled = false;
    btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> ${AI_STR.forkDone}`;
}

function prependSessionToList(s) {
    const list = document.getElementById('session-list');
    list.querySelector('div[style*="text-align:center"]')?.remove();

    const pid   = s.project_id || currentProjectId || 0;
    const pname = s.project?.name || currentProjectName || null;
    const aType = s.agent_type || currentAgentType || 'general';
    const clsMap = { general:'agent-badge-general', dev:'agent-badge-dev', document:'agent-badge-document', figma:'agent-badge-figma', builder:'agent-badge-builder' };
    const lblMap = { general:'G', dev:'D', document:'DOC', figma:'FIG', builder:'BUILD' };

    const itemEl = document.createElement('div');
    itemEl.className = 'ai-session-item active';
    itemEl.dataset.id = s.id;
    itemEl.dataset.agent = aType;
    itemEl.dataset.projectId = pid;
    itemEl.onclick = () => loadSession(s.id);
    itemEl.innerHTML = `<span class="agent-badge ${clsMap[aType]||'agent-badge-general'}" style="padding:1px 5px;font-size:9px;">${lblMap[aType]||'G'}</span>
        <span class="ai-session-title">${escHtml(s.title)}</span>
        <button class="ai-session-del" onclick="event.stopPropagation();deleteSession(${s.id},this)"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>`;

    document.querySelectorAll('.ai-session-item').forEach(el => el.classList.remove('active'));

    // 프로젝트 그룹 찾기 또는 생성
    let group = list.querySelector(`.sess-proj-group[data-project-id="${pid}"]`);
    if (!group) {
        group = document.createElement('div');
        group.className = 'sess-proj-group';
        group.dataset.projectId = pid;

        const hasProjGroups = list.querySelectorAll('.sess-proj-group[data-project-id]:not([data-project-id="0"])').length > 0;
        const iconSvg = pid
            ? `<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;color:#6366f1;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>`
            : `<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;color:#94a3b8;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>`;
        const label = pid ? (pname || AI_STR.projectLabel) : AI_STR.otherLabel;
        const hdrHtml = (pid || hasProjGroups)
            ? `<div class="sess-proj-hdr" onclick="toggleProjGroup(this)">${iconSvg}<span class="sess-proj-hdr-name" title="${escHtml(label)}">${escHtml(label)}</span><span class="sess-proj-count">0</span><svg class="sess-proj-arrow" width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></div>`
            : '';
        group.innerHTML = hdrHtml + `<div class="sess-proj-items"></div>`;

        if (pid) {
            // 프로젝트 그룹은 "기타" 그룹 앞에 삽입
            const noProj = list.querySelector('.sess-proj-group[data-project-id="0"]');
            noProj ? list.insertBefore(group, noProj) : list.prepend(group);
        } else {
            list.append(group);
        }
    }

    const items = group.querySelector('.sess-proj-items');
    items.prepend(itemEl);

    // 카운트 업데이트
    const cntEl = group.querySelector('.sess-proj-count');
    if (cntEl) cntEl.textContent = items.querySelectorAll('.ai-session-item').length;
}

function toggleProjGroup(hdr) {
    hdr.classList.toggle('collapsed');
}

function showChatUI(title, projName, catName, figmaFile) {
    document.getElementById('chat-title').textContent = title || AI_STR.newChat;
    updateAgentBadge(currentAgentType);
    document.getElementById('agent-config-panel').style.display = 'none';
    lastGeneratedFileType = null;
    hideSuggestion();

    const pn = projName || currentProjectName;
    const cn = catName  || (currentCategory ? getCategoryLabel(currentCategory) : null);
    const ff = figmaFile || (currentFigmaName ? { name: currentFigmaName } : null);

    const badgeProj = document.getElementById('chat-badge-proj');
    if (pn) { badgeProj.textContent = pn; badgeProj.style.display = 'flex'; }
    else badgeProj.style.display = 'none';

    const badgeCat = document.getElementById('chat-badge-cat');
    if (cn) { badgeCat.textContent = cn; badgeCat.style.display = 'flex'; }
    else badgeCat.style.display = 'none';

    const figmaBtn  = document.getElementById('figma-connect-btn');
    const figmaBadge = document.getElementById('chat-badge-figma');
    const figmaSel  = document.getElementById('figma-selector');
    if (currentCategory === 'figma') {
        if (ff) {
            figmaBtn.style.display = 'none';
            figmaBadge.textContent = ff.name;
            figmaBadge.style.display = 'flex';
            figmaSel.textContent = ff.name.length > 16 ? ff.name.substring(0,16)+'…' : ff.name;
            figmaSel.style.display = 'block';
        } else {
            figmaBtn.style.display = 'flex';
            figmaBadge.style.display = 'none';
            figmaSel.style.display = 'none';
        }
    } else {
        figmaBtn.style.display = 'none';
        figmaBadge.style.display = 'none';
        figmaSel.style.display = 'none';
    }

    updateShareBtn();
    setSharedMode(isSharedView);
    showState('chat');
    scrollToBottom();

    if (currentProjectId) {
        loadProjectFiles(currentProjectId);
    } else {
        document.getElementById('proj-files-btn').style.display = 'none';
        document.getElementById('ctx-mode-wrap').style.display = 'none';
        projectFiles = [];
    }
}

function getCategoryLabel(catId) {
    const map = { dev: AI_STR.catDev, figma: AI_STR.catFigma, doc: AI_STR.catDoc, auto: AI_STR.catAuto };
    return map[catId] || catId;
}

// ── Send Message ───────────────────────────────────────────────
function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

async function sendMessage() {
    if (sending) return;
    const input = document.getElementById('ai-message-input');
    const content = input.value.trim();
    if (!content) return;

    if (!_skipSuggestionOnce) {
        // ── 파일 수정 제안 체크 (세션에 이미 메시지가 있고 파일이 생성된 경우) ──
        if (currentSessionId && lastGeneratedFileType && isFileModifyKeyword(content) && !_pendingModifyFileType) {
            const typeLabel = { pptx: AI_STR.fileTypePpt, excel: AI_STR.fileTypeExcel, word: AI_STR.fileTypeWord, minutes: AI_STR.fileTypeMinutes }[lastGeneratedFileType] || AI_STR.fileTypeDefault;
            showSuggestion('file', AI_STR.suggestModifyFile.replace(':type', typeLabel), content);
            return;
        }

        // ── 문맥 검사 (세션에 이미 메시지가 있는 경우) ─────────────────────────
        if (currentSessionId) {
            try {
                const ctxRes = await post(ROUTES.sessCtxCheck(currentSessionId), { content });
                if (ctxRes.ok && ctxRes.is_new_context) {
                    showSuggestion('context', AI_STR.suggestNewContext, content);
                    return;
                }
            } catch (_) { /* 실패 시 그냥 진행 */ }
        }
    }
    _skipSuggestionOnce = false;

    sending = true;
    const sendEmail = document.getElementById('email-toggle')?.checked ?? false;
    document.getElementById('send-btn').disabled = true;

    const files = attachedFiles.filter(Boolean);
    const urls  = attachedUrls.filter(Boolean);
    const attachLabels = [
        ...files.map(f => '📎 ' + f.name),
        ...urls.map(u => '🔗 ' + u),
    ];

    input.value = ''; input.style.height = 'auto';
    clearAttachments();
    appendUserMessage(content, attachLabels);
    scrollToBottom();

    // 첫 메시지 전송 시 세션 생성
    if (!await ensureSession()) {
        appendErrorMessage(AI_STR.sessionCreateFailed);
        sending = false;
        document.getElementById('send-btn').disabled = false;
        return;
    }

    const typingId = 'typing-' + Date.now();
    appendTypingIndicator(typingId);
    scrollToBottom();

    // Collect current agent settings from form for this message
    const msgDevSettings    = currentAgentType === 'dev'      ? collectDevSettingsFromForm()   : null;
    const msgFigmaSettings  = currentAgentType === 'figma'    ? collectFigmaSettingsFromForm() : null;
    const msgDocType        = currentAgentType === 'document' ? (currentDocType || null)       : null;
    const msgBuilderStep    = currentAgentType === 'builder'  ? (currentBuilderStep || 'STEP_1') : null;

    // Builder 작업 중 표시
    if (currentAgentType === 'builder') {
        const bar = document.getElementById('builder-working-bar');
        const lbl = document.getElementById('builder-working-step-label');
        const stepNames = { STEP_1: AI_STR.builderStep1, STEP_2: AI_STR.builderStep2, STEP_3: AI_STR.builderStep3, STEP_4: AI_STR.builderStep4, STEP_FULL: AI_STR.builderFull };
        if (lbl) lbl.textContent = `웍스 Builder ${stepNames[currentBuilderStep]||''} ${AI_STR.builderWorking.replace('웍스 Builder ','')}` ;
        if (bar) bar.style.display = '';
    }

    // 파일 수정 플래그 consume
    const modifyFileType = _pendingModifyFileType || undefined;
    _pendingModifyFileType = null;

    let res;
    if (files.length || urls.length) {
        const fd = new FormData();
        fd.append('content', content);
        fd.append('send_email', sendEmail ? '1' : '0');
        fd.append('context_mode', currentContextMode);
        if (msgDevSettings)   Object.entries(msgDevSettings).forEach(([k,v]) => fd.append(`dev_settings[${k}]`, v));
        if (msgFigmaSettings) Object.entries(msgFigmaSettings).forEach(([k,v]) => fd.append(`figma_settings[${k}]`, v));
        if (msgDocType)       fd.append('doc_type', msgDocType);
        if (msgBuilderStep)   fd.append('builder_step', msgBuilderStep);
        if (modifyFileType)   fd.append('modify_file_type', modifyFileType);
        files.forEach(f => fd.append('files[]', f));
        urls.forEach(u => fd.append('urls[]', u));
        res = await postForm(ROUTES.sessMsg(currentSessionId), fd);
    } else {
        res = await post(ROUTES.sessMsg(currentSessionId), {
            content, send_email: sendEmail, context_mode: currentContextMode,
            dev_settings:     msgDevSettings   || undefined,
            figma_settings:   msgFigmaSettings || undefined,
            doc_type:         msgDocType       || undefined,
            builder_step:     msgBuilderStep   || undefined,
            modify_file_type: modifyFileType,
        });
    }
    if (res.ok && currentProjectId) loadProjectFiles(currentProjectId);

    document.getElementById(typingId)?.remove();

    // Builder 작업 중 표시 해제
    if (currentAgentType === 'builder') {
        const bar = document.getElementById('builder-working-bar');
        if (bar) bar.style.display = 'none';
    }

    if (res.ok) {
        appendAiMessage(res.message);
        if (res.session_title) {
            const titleEl = document.querySelector(`.ai-session-item[data-id="${currentSessionId}"] .ai-session-title`);
            if (titleEl) titleEl.textContent = res.session_title;
            document.getElementById('chat-title').textContent = res.session_title;
        }
        if (res.email_sent) showEmailToast();
    } else {
        appendErrorMessage(res.error ?? AI_STR.msgSendFailed);
    }
    scrollToBottom();
    sending = false;
    document.getElementById('send-btn').disabled = false;
    input.focus();
}

function showEmailToast() {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:28px;right:28px;z-index:9999;background:#1a1830;border:1px solid rgba(139,122,240,0.3);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:9px;box-shadow:0 8px 32px rgba(0,0,0,0.4);font-size:13px;color:#c4c2e0;animation:slideUp .25s ease;';
    t.innerHTML = `<svg width="15" height="15" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>${AI_STR.emailSent}`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
}

function appendUserMessage(content, attachLabels=[]) {
    const attachHtml = attachLabels.length
        ? `<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;">${attachLabels.map(l=>`<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:rgba(255,255,255,0.15);border-radius:20px;font-size:11px;">${escHtml(l)}</span>`).join('')}</div>`
        : '';
    document.getElementById('chat-area').insertAdjacentHTML('beforeend',
        `<div class="ai-msg user"><div class="ai-msg-av">${AI_STR.me}</div><div class="ai-msg-body"><div class="ai-bubble">${escHtml(content)}${attachHtml}</div></div></div>`);
}
function appendTypingIndicator(id) {
    document.getElementById('chat-area').insertAdjacentHTML('beforeend',
        `<div class="ai-msg assistant" id="${id}"><div class="ai-msg-av">웍스</div><div class="ai-msg-body"><div class="ai-bubble" style="padding:8px 14px;"><div class="ai-typing"><span></span><span></span><span></span></div></div></div></div>`);
}
function appendAiMessage(msg) {
    document.getElementById('chat-area').insertAdjacentHTML('beforeend', buildMessageHtml(msg));
    // 파일 생성 여부 추적
    if (msg.doc_file_type) {
        lastGeneratedFileType = msg.doc_file_type === 'pptx' ? 'pptx'
            : (msg.doc_file_type === 'xlsx' ? 'excel'
            : (msg.doc_file_type === 'docx' ? (msg.doc_file_name?.toLowerCase().includes('회의록') ? 'minutes' : 'word') : null));
    }
}

// ── Suggestion Panel ───────────────────────────────────────────
let _suggestionMode = null; // 'context' | 'file'
let _pendingContent = null; // 전송 대기 중인 메시지

function showSuggestion(mode, text, content) {
    _suggestionMode = mode;
    _pendingContent = content;
    document.getElementById('suggestion-text').textContent = text;
    document.getElementById('suggestion-panel').style.display = '';
}
function hideSuggestion() {
    document.getElementById('suggestion-panel').style.display = 'none';
    _suggestionMode = null;
    _pendingContent = null;
}

async function handleSuggestionYes() {
    const mode    = _suggestionMode;
    const content = _pendingContent;
    hideSuggestion();
    _skipSuggestionOnce = true;
    if (mode === 'context') {
        // 새 대화창으로 이동 후 메시지 전송
        newSessionKeepSettings();
        await new Promise(r => window.addEventListener('sessionReady', r, { once: true }));
        const el = document.getElementById('ai-message-input');
        el.value = content; autoResize(el);
        sendMessage();
    } else if (mode === 'file') {
        // 기존 파일 수정 요청 — modify_file_type 포함해서 전송
        _pendingModifyFileType = lastGeneratedFileType;
        const el = document.getElementById('ai-message-input');
        el.value = content; autoResize(el);
        sendMessage();
    }
}
function handleSuggestionNo() {
    const mode    = _suggestionMode;
    const content = _pendingContent;
    hideSuggestion();
    if (mode === 'context' || mode === 'file') {
        // 그냥 현재 대화창에서 계속 진행 (제안 체크 건너뜀)
        _skipSuggestionOnce = true;
        const el = document.getElementById('ai-message-input');
        el.value = content; autoResize(el);
        sendMessage();
    }
}

// 파일 수정 키워드 감지
function isFileModifyKeyword(content) {
    const lower = content.toLowerCase();
    const keywords = ['수정', '변경', '바꿔', '바꾸어', '고쳐', '수정해', '업데이트', '추가해', '추가하여', '다시 만들어', '다시만들어', '다시 생성', '다시생성'];
    return keywords.some(k => lower.includes(k));
}

let _pendingModifyFileType = null; // sendMessage가 consume
let _skipSuggestionOnce   = false; // 제안 거절 후 재진입 시 체크 건너뜀

function appendErrorMessage(text) {
    document.getElementById('chat-area').insertAdjacentHTML('beforeend',
        `<div class="ai-msg assistant"><div class="ai-msg-av">웍스</div><div class="ai-msg-body"><div class="ai-bubble" style="border-color:#fecaca;color:#dc2626;">${escHtml(text)}</div></div></div>`);
}

// ── Message Builder ────────────────────────────────────────────
function providerBadgeHtml(provider) {
    if (provider === 'claude') return `<span style="font-size:10.5px;font-weight:700;color:#7c3aed;display:flex;align-items:center;gap:3px;">⚡ Claude</span>`;
    if (provider === 'openai') return `<span style="font-size:10.5px;font-weight:700;color:#16a34a;display:flex;align-items:center;gap:3px;">✦ GPT-4.1</span>`;
    return '';
}

function mdToHtml(text) {
    if (!text) return '';
    let h = escHtml(text);
    h = h.replace(/^### (.+)$/gm, '<strong style="font-size:13px;color:#404040;display:block;margin:10px 0 4px;">$1</strong>');
    h = h.replace(/^## (.+)$/gm,  '<strong style="font-size:14px;color:#2E75B6;display:block;margin:12px 0 5px;border-bottom:1px solid #e4e4e7;padding-bottom:3px;">$1</strong>');
    h = h.replace(/^# (.+)$/gm,   '<strong style="font-size:16px;color:#1F3864;display:block;margin:14px 0 6px;">$1</strong>');
    h = h.replace(/^[-*] (.+)$/gm, '<span style="display:block;padding-left:14px;position:relative;margin:2px 0;"><span style="position:absolute;left:4px;color:#7c3aed;">•</span>$1</span>');
    h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    h = h.replace(/\n/g, '<br>');
    return h;
}

function fmtTime(iso) {
    const d = iso ? new Date(iso) : new Date();
    const y = d.getFullYear();
    const mo = (d.getMonth()+1).toString().padStart(2,'0');
    const day = d.getDate().toString().padStart(2,'0');
    const h = d.getHours().toString().padStart(2,'0');
    const mi = d.getMinutes().toString().padStart(2,'0');
    return AI_STR.dateFormatTpl.replace('{y}',y).replace('{mo}',mo).replace('{day}',day).replace('{h}',h).replace('{mi}',mi);
}

function buildMessageHtml(msg) {
    const hasCode = msg.html_output || msg.css_output || msg.js_output;
    const hasDoc  = msg.doc_file_name && msg.doc_status !== 'failed';
    const time    = `<div class="ai-msg-time">${fmtTime(msg.created_at)}</div>`;
    if (msg.role === 'user') {
        return `<div class="ai-msg user"><div class="ai-msg-av">${AI_STR.me}</div><div class="ai-msg-body"><div class="ai-bubble">${escHtml(msg.content)}</div>${time}</div></div>`;
    }
    const contentHtml = msg.content
        ? `<div class="ai-bubble" style="line-height:1.75;">${mdToHtml(msg.content)}</div>`
        : '';
    return `<div class="ai-msg assistant"><div class="ai-msg-av">웍스</div><div class="ai-msg-body" style="flex:1;max-width:100%;">
        ${contentHtml}
        ${hasDoc  ? buildDocCard(msg)   : ''}
        ${hasCode ? buildCodePanel(msg) : ''}
        ${time}
    </div></div>`;
}

const LANG_LABELS = {
    web:'HTML', html:'HTML', css:'CSS', js:'JS', javascript:'JS',
    python:'Python', sql:'SQL', php:'PHP', java:'Java', typescript:'TypeScript',
    bash:'Bash', shell:'Shell', json:'JSON', yaml:'YAML', ruby:'Ruby',
    go:'Go', rust:'Rust', c:'C', cpp:'C++', kotlin:'Kotlin', swift:'Swift',
};
function getLangLabel(lang) { return LANG_LABELS[lang] || (lang ? lang.toUpperCase() : 'Code'); }

function buildCodePanel(msg) {
    const uid  = 'cp-' + (msg.id || Date.now());
    const lang = msg.code_lang || 'web';
    const isWeb = lang === 'web';
    const tabs = [], contents = [];

    if (isWeb) {
        // 웹: 내용 있는 탭만 표시
        if (msg.html_output) {
            tabs.push(`<div class="ai-code-tab active" onclick="switchTab('${uid}','html',this)"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg> HTML</div>`);
            contents.push(`<pre class="ai-code-content active" id="${uid}-html">${escHtml(msg.html_output)}</pre>`);
        }
        if (msg.css_output) {
            tabs.push(`<div class="ai-code-tab${!msg.html_output?' active':''}" onclick="switchTab('${uid}','css',this)">CSS</div>`);
            contents.push(`<pre class="ai-code-content${!msg.html_output?' active':''}" id="${uid}-css">${escHtml(msg.css_output)}</pre>`);
        }
        if (msg.js_output) {
            const fa = !msg.html_output && !msg.css_output;
            tabs.push(`<div class="ai-code-tab${fa?' active':''}" onclick="switchTab('${uid}','js',this)">JS</div>`);
            contents.push(`<pre class="ai-code-content${fa?' active':''}" id="${uid}-js">${escHtml(msg.js_output)}</pre>`);
        }
    } else {
        // 단일 언어: 언어명 탭 하나로 표시
        const label = getLangLabel(lang);
        tabs.push(`<div class="ai-code-tab active" onclick="switchTab('${uid}','html',this)">${label}</div>`);
        contents.push(`<pre class="ai-code-content active" id="${uid}-html">${escHtml(msg.html_output)}</pre>`);
        if (msg.css_output) {
            tabs.push(`<div class="ai-code-tab" onclick="switchTab('${uid}','css',this)">CSS</div>`);
            contents.push(`<pre class="ai-code-content" id="${uid}-css">${escHtml(msg.css_output)}</pre>`);
        }
    }

    // Preview 탭: 웹/html 계열만
    const showPreview = isWeb || lang === 'html';
    if (showPreview) {
        tabs.push(`<div class="ai-code-tab" onclick="switchTab('${uid}','preview',this)"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg> Preview</div>`);
        contents.push(`<iframe class="ai-preview-frame" id="${uid}-preview" sandbox="allow-scripts allow-same-origin allow-popups"></iframe>`);
    }

    const dlBtn      = msg.id ? `<a href="{{ url('ai/messages') }}/${msg.id}/download" class="ai-tab-copy" style="display:flex;align-items:center;gap:4px;text-decoration:none;" title="ZIP ${AI_STR.download}"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>ZIP</a>` : '';
    const addBtn     = msg.id ? `<button id="atp-zip-btn-${msg.id}" class="ai-tab-copy" onclick="showAddToProjectPicker(${msg.id},'source-code.zip','zip','${AI_BASE}/messages/${msg.id}/add-zip-to-project')" style="display:flex;align-items:center;gap:4px;color:#16a34a;border-color:#bbf7d0;" title="${AI_STR.addToProject}"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>${AI_STR.addToProject}</button>` : '';
    const fsBtn      = showPreview ? `<button class="ai-tab-copy" onclick="openPreviewFs('${uid}')" title="${AI_STR.fullscreen}" style="display:flex;align-items:center;gap:4px;"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>${AI_STR.fullscreen}</button>` : '';
    // 완성형 HTML(기획서 뷰어 등)은 index.html 직접 다운로드 버튼 제공
    const isViewer   = msg.html_output && isCompleteHtmlDoc(msg.html_output);
    const htmlDlBtn  = isViewer ? `<button class="ai-tab-copy" onclick="downloadHtmlViewer('${uid}')" title="index.html ${AI_STR.download}" style="display:flex;align-items:center;gap:4px;color:#d97706;border-color:#fcd34d;"><svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>HTML</button>` : '';
    return `<div class="ai-code-panel" id="${uid}"><div class="ai-code-tabs">${tabs.join('')}<button class="ai-tab-copy" onclick="copyCode('${uid}')">${AI_STR.copy}</button>${htmlDlBtn}${dlBtn}${addBtn}${fsBtn}</div>${contents.join('')}</div>`;
}

function buildDocCard(msg) {
    if (!msg.doc_file_name || msg.doc_status === 'failed') return '';
    const icons = { pptx:'📊', xlsx:'📗', docx:'📄', pdf:'📕' };
    const labels = { pptx:'PowerPoint', xlsx:'Excel', docx:'Word', pdf:'PDF' };
    const icon  = icons[msg.doc_file_type]  || '📁';
    const label = labels[msg.doc_file_type] || AI_STR.chatSummaryErr;
    const statusHtml = msg.doc_status === 'processing'
        ? `<span style="color:#d97706;">${AI_STR.processing}</span>`
        : `<span style="color:#16a34a;">${AI_STR.docCompleted}</span>`;
    const isComplete = msg.doc_download_url && msg.doc_status === 'completed';
    const dlBtn = isComplete
        ? `<a href="${escHtml(msg.doc_download_url)}" download="${escHtml(msg.doc_file_name||'document.docx')}" class="ai-doc-download-btn"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>${AI_STR.download}</a>`
        : '';
    const addBtn = isComplete
        ? `<button id="atp-btn-${msg.id}" class="ai-doc-add-proj-btn" onclick="showAddToProjectPicker(${msg.id}, ${JSON.stringify(msg.doc_file_name)}, ${JSON.stringify(msg.doc_file_type)})"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>${AI_STR.addToProject}</button>`
        : '';
    const btnWrap = (dlBtn || addBtn)
        ? `<div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">${dlBtn}${addBtn}</div>`
        : '';
    return `<div class="ai-doc-card">
        <div class="ai-doc-card-icon">${icon}</div>
        <div class="ai-doc-card-info">
            <div class="ai-doc-card-name">${escHtml(msg.doc_file_name)}</div>
            <div class="ai-doc-card-meta">${label} · ${statusHtml}</div>
        </div>
        ${btnWrap}
    </div>`;
}

function switchTab(uid, tab, clickedEl) {
    const panel = document.getElementById(uid);
    panel.querySelectorAll('.ai-code-tab').forEach(t => t.classList.remove('active'));
    panel.querySelectorAll('.ai-code-content,.ai-preview-frame').forEach(c => c.classList.remove('active'));
    clickedEl.classList.add('active');
    const el = document.getElementById(`${uid}-${tab}`);
    if (el) { el.classList.add('active'); if (tab === 'preview') updatePreview(uid, el); }
}

const PREVIEW_BASE_CSS = `
*,*::before,*::after{box-sizing:border-box;}
html{font-size:16px;-webkit-text-size-adjust:100%;}
body{margin:0;padding:24px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;font-size:14px;line-height:1.65;color:#1e1b2e;background:#fff;}
h1{font-size:1.75em;}h2{font-size:1.45em;}h3{font-size:1.2em;}h4{font-size:1.05em;}
h1,h2,h3,h4,h5,h6{margin:0 0 .5em;font-weight:700;line-height:1.3;color:#1e1b2e;}
p{margin:0 0 .85em;}
ul,ol{padding-left:1.5em;margin:0 0 .85em;}
li{margin-bottom:.3em;}
a{color:#7c3aed;text-decoration:underline;}
a:hover{opacity:.8;}
img,video{max-width:100%;height:auto;display:block;}
button{cursor:pointer;font-family:inherit;font-size:inherit;}
input,select,textarea{font-family:inherit;font-size:inherit;}
table{border-collapse:collapse;width:100%;margin:.85em 0;font-size:.925em;}
th,td{padding:.5em .85em;border:1px solid #e5e7eb;text-align:left;}
th{background:#f9fafb;font-weight:700;color:#374151;}
tr:nth-child(even) td{background:#fafafa;}
pre{background:#1e1b2e;color:#c4b5fd;padding:1em 1.2em;border-radius:8px;overflow-x:auto;font-size:.875em;margin:0 0 .85em;line-height:1.6;}
code{background:#f3f0ff;color:#7c3aed;padding:.1em .4em;border-radius:4px;font-size:.9em;font-family:'Consolas','Monaco','Courier New',monospace;}
pre code{background:none;color:inherit;padding:0;font-size:inherit;}
blockquote{border-left:3px solid #7c3aed;padding:.5em 1em;margin:0 0 .85em;color:#6b7280;font-style:italic;}
hr{border:none;border-top:1px solid #e5e7eb;margin:1.5em 0;}
strong{font-weight:700;}em{font-style:italic;}
`;

function isCompleteHtmlDoc(str) {
    return /^\s*<!doctype\s+html/i.test(str) || /^\s*<html[\s>]/i.test(str);
}

function buildPreviewSrcdoc(uid) {
    const html = document.getElementById(`${uid}-html`)?.textContent || '';
    const css  = document.getElementById(`${uid}-css`)?.textContent  || '';
    const js   = document.getElementById(`${uid}-js`)?.textContent   || '';
    // 완성형 HTML 문서는 이중 래핑 없이 그대로 사용
    if (isCompleteHtmlDoc(html)) return html;
    return `<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base target="_blank"><style>${PREVIEW_BASE_CSS}${css}</style></head><body>${html}<script>${js}<\/script></body></html>`;
}

function updatePreview(uid, iframe) {
    const srcdoc = buildPreviewSrcdoc(uid);
    iframe._previewSrcdoc = srcdoc;
    const isFullDoc = isCompleteHtmlDoc(srcdoc);
    iframe.onload = () => {
        try {
            const body = iframe.contentDocument?.body;
            if (body) {
                const sh = body.scrollHeight;
                // 완성형 HTML(뷰어 등)은 최소 560px, 일반 코드는 최대 620px
                const h = isFullDoc
                    ? Math.max(sh + 40, 560)
                    : Math.min(Math.max(sh + 40, 220), 620);
                iframe.style.height = Math.min(h, 620) + 'px';
            }
        } catch(e) {}
    };
    iframe.srcdoc = srcdoc;
}

function openPreviewFs(uid) {
    const iframe = document.getElementById(`${uid}-preview`);
    const fsIframe = document.getElementById('preview-fs-iframe');
    const overlay = document.getElementById('preview-fs-overlay');
    if (!iframe || !fsIframe || !overlay) return;
    const srcdoc = iframe._previewSrcdoc || buildPreviewSrcdoc(uid);
    fsIframe.srcdoc = srcdoc;
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePreviewFs() {
    const overlay = document.getElementById('preview-fs-overlay');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
}

function downloadHtmlViewer(uid) {
    const html = document.getElementById(`${uid}-html`)?.textContent || '';
    if (!html) return;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'index.html';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreviewFs(); });

function copyCode(uid) {
    const el = document.querySelector(`#${uid} .ai-code-content.active`);
    if (el) navigator.clipboard.writeText(el.textContent).then(() => {
        const btn = document.querySelector(`#${uid} .ai-tab-copy`);
        if (btn) { btn.textContent=AI_STR.copied; setTimeout(()=>btn.textContent=AI_STR.copy,1500); }
    });
}

// ── Sidebar Collapse ───────────────────────────────────────────
function toggleSidebar() {
    const left = document.querySelector('.ai-left');
    const icon = document.getElementById('sidebar-toggle-icon');
    const collapsed = left.classList.toggle('collapsed');
    if (icon) icon.style.transform = collapsed ? 'rotate(180deg)' : '';
    localStorage.setItem('ai_sidebar_collapsed', collapsed ? '1' : '0');
}
(function() {
    if (localStorage.getItem('ai_sidebar_collapsed') === '1') {
        const left = document.querySelector('.ai-left');
        const icon = document.getElementById('sidebar-toggle-icon');
        if (left) left.classList.add('collapsed');
        if (icon) icon.style.transform = 'rotate(180deg)';
    }
})();

// ── Settings ───────────────────────────────────────────────────
function openSettings() { openModal('settings-modal'); }
async function saveSettings() {
    const el = document.getElementById('settings-status');
    el.style.color = '#6b7280'; el.textContent = AI_STR.saving;
    const res = await post(ROUTES.settings, {
        anthropic_key:  document.getElementById('s-anthropic-key').value,
        openai_key:     document.getElementById('s-openai-key').value,
        figma_token:    document.getElementById('s-figma-token').value,
        manus_key:      document.getElementById('s-manus-key').value,
        manus_endpoint: document.getElementById('s-manus-endpoint').value,
    });
    el.style.color = res.ok ? '#16a34a' : '#dc2626';
    el.textContent = res.ok ? AI_STR.saved : (res.error ?? AI_STR.saveFailed);
    if (res.ok) setTimeout(() => closeModal('settings-modal'), 1000);
}

// ── Figma Management ───────────────────────────────────────────
function openFigmaModal() { document.getElementById('figma-url-input').value=''; document.getElementById('figma-add-status').textContent=''; openModal('figma-modal'); }

async function addFigmaFile() {
    const url = document.getElementById('figma-url-input').value.trim();
    const el  = document.getElementById('figma-add-status');
    el.style.color='#6b7280'; el.textContent=AI_STR.adding;
    const res = await post(ROUTES.figmaAdd, { url });
    if (res.ok) { figmaFiles.push(res.file); el.style.color='#16a34a'; el.textContent=AI_STR.added; setTimeout(()=>closeModal('figma-modal'),800); }
    else { el.style.color='#dc2626'; el.textContent=res.error??AI_STR.addFailed; }
}

function openFigmaPicker() {
    const list = document.getElementById('figma-picker-list');
    if (!figmaFiles.length) {
        list.innerHTML = `<div style="padding:20px;text-align:center;font-size:13px;color:#b8b0d8;">${AI_STR.noFigmaFiles}<br><button class="ai-btn-primary" style="margin-top:10px;font-size:12px;" onclick="closeModal('figma-picker-modal');openFigmaModal()">${AI_STR.figmaAddFile}</button></div>`;
    } else {
        list.innerHTML = figmaFiles.map(ff => `
            <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;cursor:pointer;border:1.5px solid ${currentFigmaId===ff.id?'var(--t400)':'#e8e3ff'};background:${currentFigmaId===ff.id?'var(--t50)':'#fff'};" onclick="chooseFigma(${ff.id},'${(ff.name||'').replace(/'/g,"\\'")}')">
                <div style="width:30px;height:30px;border-radius:6px;background:linear-gradient(135deg,var(--t100),var(--t200));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="var(--t600)"><path d="M15.5 12a3.5 3.5 0 11-7 0 3.5 3.5 0 017 0z"/></svg>
                </div>
                <span style="font-size:12.5px;font-weight:600;color:#1e1b2e;">${escHtml(ff.name||AI_STR.figmaFileLabel)}</span>
            </div>`).join('');
    }
    openModal('figma-picker-modal');
}

function chooseFigma(id, name) {
    currentFigmaId = id; currentFigmaName = name;
    const sel = document.getElementById('figma-selector');
    sel.textContent = name.length > 16 ? name.substring(0,16)+'…' : name;
    sel.style.display = 'block';
    document.getElementById('figma-connect-btn').style.display = 'none';
    document.getElementById('chat-badge-figma').textContent = name;
    document.getElementById('chat-badge-figma').style.display = 'flex';
    closeModal('figma-picker-modal');
}

// ── Attach ─────────────────────────────────────────────────────
function onFilesSelected(input) {
    Array.from(input.files).forEach(f => {
        attachedFiles.push(f);
        renderAttachChip('file', f.name, attachedFiles.length - 1);
    });
    input.value = '';
    syncAttachChips();
}

function toggleUrlInput() {
    const row = document.getElementById('attach-url-row');
    const btn = document.getElementById('url-attach-btn');
    const open = row.style.display === 'flex';
    row.style.display = open ? 'none' : 'flex';
    btn.classList.toggle('active', !open);
    if (!open) document.getElementById('attach-url-input').focus();
}

function addAttachUrl() {
    const val = document.getElementById('attach-url-input').value.trim();
    if (!val) return;
    try { new URL(val); } catch { return; }
    attachedUrls.push(val);
    renderAttachChip('url', val, attachedUrls.length - 1);
    document.getElementById('attach-url-input').value = '';
    document.getElementById('attach-url-row').style.display = 'none';
    document.getElementById('url-attach-btn').classList.remove('active');
    syncAttachChips();
}

function renderAttachChip(type, label, idx) {
    const short = label.length > 35 ? label.substring(0, 33) + '…' : label;
    const icon = type === 'file'
        ? `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>`
        : `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>`;
    const chip = document.createElement('div');
    chip.className = 'attach-chip';
    chip.id = `chip-${type}-${idx}`;
    chip.innerHTML = `${icon}<span title="${escHtml(label)}">${escHtml(short)}</span><button class="attach-chip-del" onclick="removeAttach('${type}',${idx})" title="${escHtml(AI_STR.removeBtn)}"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
    document.getElementById('attach-chips').appendChild(chip);
}

function removeAttach(type, idx) {
    if (type === 'file') attachedFiles[idx] = null;
    else attachedUrls[idx] = null;
    document.getElementById(`chip-${type}-${idx}`)?.remove();
    syncAttachChips();
}

function syncAttachChips() {
    const hasAny = attachedFiles.some(Boolean) || attachedUrls.some(Boolean);
    document.getElementById('attach-chips').style.display = hasAny ? 'flex' : 'none';
}

function clearAttachments() {
    attachedFiles = [];
    attachedUrls  = [];
    document.getElementById('attach-chips').innerHTML = '';
    document.getElementById('attach-chips').style.display = 'none';
    document.getElementById('attach-url-row').style.display = 'none';
    document.getElementById('url-attach-btn').classList.remove('active');
}

// ── Project Files ──────────────────────────────────────────────
async function loadProjectFiles(projectId) {
    const res = await get(ROUTES.projFiles(projectId));
    projectFiles = res.ok ? (res.files || []) : [];
    const btn   = document.getElementById('proj-files-btn');
    const wrap  = document.getElementById('ctx-mode-wrap');
    document.getElementById('proj-files-count').textContent = projectFiles.length;
    if (projectFiles.length > 0) {
        btn.style.display  = 'flex';
        wrap.style.display = 'flex';
    } else {
        btn.style.display  = 'none';
        wrap.style.display = 'none';
    }
}

function setContextMode(mode) {
    currentContextMode = mode;
    ['all','current','none'].forEach(m => {
        document.getElementById(`ctx-btn-${m}`)?.classList.toggle('active', m === mode);
    });
}

function openProjectFilesModal() {
    document.getElementById('proj-files-modal-proj').textContent = currentProjectName || '';
    const list = document.getElementById('proj-files-modal-list');
    if (!projectFiles.length) {
        list.innerHTML = `<div style="text-align:center;color:#94a3b8;font-size:13px;padding:24px;">${AI_STR.noSavedFiles}</div>`;
    } else {
        list.innerHTML = projectFiles.map(f => {
            const date = f.updated_at ? f.updated_at.substring(0, 10) : '';
            return `<div style="display:flex;align-items:center;gap:10px;padding:9px 12px;border:1.5px solid var(--t100);border-radius:9px;background:#fff;">
                <svg width="14" height="14" fill="none" stroke="var(--t400)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span style="flex:1;font-size:13px;font-weight:600;color:#1e1b2e;">${escHtml(f.file_name)}</span>
                <span style="font-size:11px;color:#94a3b8;">${escHtml(f.lang)}</span>
                <span style="font-size:11px;color:#94a3b8;background:var(--t50);padding:2px 7px;border-radius:5px;">v${f.version}</span>
                ${date ? `<span style="font-size:11px;color:#94a3b8;">${date}</span>` : ''}
            </div>`;
        }).join('');
    }
    openModal('modal-proj-files');
}

// ── 프로젝트 파일 추가 ─────────────────────────────────────────
let atpMsgId      = null;
let atpEndpoint   = null;
let atpProjects   = null;  // 캐시
const ATP_ADD_URL  = id => `${AI_BASE}/messages/${id}/add-to-project`;
const ATP_PROJ_URL = `${AI_BASE}/user-projects`;

function showAddToProjectPicker(msgId, fileName, fileType, endpoint) {
    atpMsgId    = msgId;
    atpEndpoint = endpoint || ATP_ADD_URL(msgId);
    const icons = { pptx:'📊', xlsx:'📗', docx:'📄', pdf:'📕', zip:'🗜️' };
    document.getElementById('atp-file-info').textContent = (icons[fileType] || '📁') + ' ' + (fileName || AI_STR.docFallbackName);
    const list = document.getElementById('atp-proj-list');
    if (atpProjects) {
        renderAtpProjects(atpProjects);
    } else {
        list.innerHTML = `<div style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">${AI_STR.loadingProjects}</div>`;
        fetch(ATP_PROJ_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
            .then(r => r.json())
            .then(d => { atpProjects = d.projects || []; renderAtpProjects(atpProjects); })
            .catch(() => { list.innerHTML = `<div style="text-align:center;padding:24px;color:#ef4444;font-size:13px;">${AI_STR.loadFailed}</div>`; });
    }
    openModal('modal-add-to-project');
}

function renderAtpProjects(projects) {
    const list = document.getElementById('atp-proj-list');
    if (!projects.length) {
        list.innerHTML = `<div style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">${AI_STR.noJoinedProjects}</div>`;
        return;
    }
    list.innerHTML = projects.map(p => `
        <div class="atp-proj-item" data-pid="${p.id}" data-pname="${escHtml(p.name)}">
            <div class="atp-proj-ico">${escHtml(p.name.charAt(0).toUpperCase())}</div>
            <div style="flex:1;font-size:13px;font-weight:600;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(p.name)}</div>
            <svg width="14" height="14" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </div>
    `).join('');
    list.querySelectorAll('.atp-proj-item').forEach(el => {
        el.addEventListener('click', () => doAddToProject(el.dataset.pid, el.dataset.pname, el));
    });
}

async function doAddToProject(projectId, projectName, el) {
    el.style.opacity = '.5';
    el.style.pointerEvents = 'none';

    try {
        const r = await fetch(atpEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ project_id: projectId }),
        });
        const d = await r.json();
        closeModal('modal-add-to-project');
        if (d.ok) {
            // 버튼을 "추가 완료" 상태로 변경 (doc 버튼 또는 zip 버튼)
            const docBtn = document.getElementById('atp-btn-' + atpMsgId);
            if (docBtn) {
                docBtn.className = 'ai-doc-add-proj-btn added';
                docBtn.disabled  = true;
                docBtn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>${AI_STR.addComplete}`;
            }
            const zipBtn = document.getElementById('atp-zip-btn-' + atpMsgId);
            if (zipBtn) {
                zipBtn.disabled  = true;
                zipBtn.style.color = '#15803d';
                zipBtn.style.borderColor = '#86efac';
                zipBtn.innerHTML = `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>${AI_STR.addComplete}`;
            }
            showAddedToast(`"${projectName}" ${AI_STR.added}`, d.files_url);
        } else {
            alert(d.error || AI_STR.addFailed);
            el.style.opacity = '';
            el.style.pointerEvents = '';
        }
    } catch {
        alert(AI_STR.errorOccurred + ' ' + AI_STR.retry);
        el.style.opacity = '';
        el.style.pointerEvents = '';
    }
}

function showAddedToast(msg, url) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:28px;right:28px;z-index:9999;background:#1a1830;border:1px solid rgba(134,239,172,0.4);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:9px;box-shadow:0 8px 32px rgba(0,0,0,.4);font-size:13px;color:#c4c2e0;animation:slideUp .25s ease;cursor:pointer;';
    t.innerHTML = `<svg width="15" height="15" fill="none" stroke="#4ade80" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>${escHtml(msg)} <span style="color:#86efac;text-decoration:underline;margin-left:4px;">${AI_STR.viewFiles}</span></span>`;
    t.onclick = () => { if (url) window.open(url, '_blank'); t.remove(); };
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 4000);
}

// ── Init ───────────────────────────────────────────────────────
document.querySelectorAll('.ai-modal-backdrop').forEach(el => el.addEventListener('click', e => { if(e.target===el) el.classList.remove('show'); }));

if (currentSessionId) {
    showState('chat');
    updateShareBtn();
    updateAgentBadge(currentAgentType);
    scrollToBottom();
    if (currentProjectId) loadProjectFiles(currentProjectId);
    if (currentAgentType && currentAgentType !== 'general') {
        prefillConfigForm({
            agent_type:      currentAgentType,
            dev_settings:    currentDevSettings,
            doc_type:        currentDocType,
            output_filename: currentOutputFilename,
            output_extension:currentOutputExtension,
            project_id:      currentProjectId,
        });
    }
}
</script>
@endsection
