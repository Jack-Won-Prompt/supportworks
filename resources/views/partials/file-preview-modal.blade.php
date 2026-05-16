{{-- ============================  의견 전용 팝업  ============================ --}}
<div id="comments-modal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
<div style="width:100%;max-width:560px;max-height:88vh;background:#fff;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 70px rgba(0,0,0,.28);">

    {{-- 헤더 --}}
    <div style="padding:16px 18px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:10px;flex-shrink:0;">
        <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span id="cm-filename" style="flex:1;font-size:13px;font-weight:700;color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
        <span id="cm-count" style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:2px 8px;border-radius:10px;font-weight:700;flex-shrink:0;"></span>
        <button onclick="closeComments()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:2px 4px;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">×</button>
    </div>

    {{-- 의견 목록 --}}
    <div id="cm-list" style="flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:10px;min-height:0;"></div>

    {{-- 새 의견 작성 폼 --}}
    <div style="padding:12px 16px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9;">
        <textarea id="cm-new-input" rows="2" placeholder="{{ __('viewer.new_opinion_placeholder') }}"
                  style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;resize:none;outline:none;transition:border-color .15s;box-sizing:border-box;background:#fff;"
                  onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"
                  onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();cmSubmitComment();}"></textarea>
        <div style="display:flex;align-items:center;gap:8px;margin-top:7px;">
            <button id="cm-open-preview" onclick="" style="flex:1;padding:8px;background:#f5f3ff;color:#7c3aed;border:1.5px solid #ede9fe;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;display:none;transition:background .15s;" onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                {{ __('viewer.open_for_review') }}
            </button>
            <button onclick="cmSubmitComment()" id="cm-submit-btn"
                    style="padding:8px 20px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0;">
                {{ __('viewer.submit_opinion') }}
            </button>
        </div>
    </div>
</div>
</div>

{{-- ============================  미리보기 팝업 모달  ============================ --}}
<div id="preview-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,8,20,.75);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:32px;">
<div style="width:100%;max-width:1200px;height:calc(100vh - 64px);max-height:820px;background:#1a1730;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6);border:1px solid rgba(196,181,253,.15);">

    {{-- 상단바 --}}
    <div style="height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:12px;padding:0 16px;flex-shrink:0;border-radius:16px 16px 0 0;">
        <button id="modal-close-btn" onclick="closePreview()" style="display:inline-flex;align-items:center;gap:6px;color:#c4b5fd;font-size:13px;font-weight:600;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;transition:background .15s;" onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ __('viewer.close') }}
        </button>
        <span id="modal-filename" style="flex:1;overflow:hidden;font-size:13px;font-weight:600;color:#e5e7eb;white-space:nowrap;text-overflow:ellipsis;"></span>
        <span id="modal-version-bar" style="display:inline-flex;align-items:center;gap:4px;flex-shrink:0;"></span>
        <button id="modal-compare-btn" type="button" onclick="openVersionCompareMenu(event)"
                title="{{ __('viewer.compare_versions_title') }}"
                style="display:none;align-items:center;gap:5px;color:#7dd3fc;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(125,211,252,.3);border-radius:7px;flex-shrink:0;background:none;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='rgba(125,211,252,.1)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/></svg>
            {{ __('viewer.compare_versions') }}
        </button>
        <span id="modal-badge" style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;flex-shrink:0;"></span>
        <button id="modal-upload-version" type="button" onclick="openUploadVersionModal()" style="display:inline-flex;align-items:center;gap:5px;color:#fde68a;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(253,230,138,.3);border-radius:7px;flex-shrink:0;background:none;cursor:pointer;transition:background .15s;" onmouseover="this.style.background='rgba(253,230,138,.1)'" onmouseout="this.style.background='none'" title="{{ __('viewer.upload_revision_title') }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            {{ __('viewer.upload_revision') }}
        </button>
        <a id="modal-download" href="#" style="display:inline-flex;align-items:center;gap:5px;color:#a5b4fc;font-size:12px;font-weight:600;text-decoration:none;padding:5px 10px;border:1px solid rgba(165,180,252,.25);border-radius:7px;flex-shrink:0;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('viewer.download') }}
        </a>
        <button type="button" id="preview-fs-btn" onclick="togglePreviewFullscreen()" class="preview-fs-btn" title="{{ __('viewer.fullwindow_title') }}">
            <svg class="preview-fs-icon-off" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            <svg class="preview-fs-icon-on" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V5m0 0H5m4 4L4 4m11 5h4m0 0V5m0 4l5-5M9 15v4m0 0H5m4 0l-5 5m11-5h4m0 0v4m0-4l5 5"/></svg>
            {{ __('viewer.fullwindow') }}
        </button>
        <button id="modal-download-comments" type="button" onclick="downloadCommentsReport()"
                title="{{ __('viewer.download_with_comments_title') }}"
                style="display:inline-flex;align-items:center;gap:5px;color:#86efac;font-size:12px;font-weight:600;background:none;border:1px solid rgba(134,239,172,.3);border-radius:7px;padding:5px 10px;flex-shrink:0;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='rgba(134,239,172,.1)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('viewer.download_with_comments') }}
        </button>
        <button id="ann-dl-btn" onclick="downloadAnnotatedPdf()" style="display:none;align-items:center;gap:5px;color:#c4b5fd;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(196,181,253,.25);border-radius:7px;flex-shrink:0;background:none;cursor:pointer;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('viewer.download_with_review') }}
        </button>
    </div>

    {{-- 주석 툴바 --}}
    <div id="ann-toolbar" style="display:none;height:42px;background:rgba(12,9,26,.98);border-bottom:1px solid rgba(196,181,253,.08);align-items:center;gap:4px;padding:0 14px;flex-shrink:0;">
        <span style="font-size:10px;font-weight:600;color:#6b7280;letter-spacing:.4px;margin-right:4px;">{{ __('viewer.ann_shapes') }}</span>
        <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 4px;"></div>
        <button id="ann-btn-number" onclick="setAnnTool('number')" title="{{ __('viewer.ann_number') }}" class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.5" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
        <button id="ann-btn-rect"   onclick="setAnnTool('rect')"   title="{{ __('viewer.ann_rect') }}"   class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
        <button id="ann-btn-circle" onclick="setAnnTool('circle')" title="{{ __('viewer.ann_circle') }}" class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
        <button id="ann-btn-line"   onclick="setAnnTool('line')"   title="{{ __('viewer.ann_line') }}"   class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
        <button id="ann-btn-text"   onclick="setAnnTool('text')"   title="{{ __('viewer.ann_text') }}"   class="ann-tool-btn" style="font-size:13px;font-weight:700;line-height:1;">T</button>
        <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 6px;"></div>
        <span style="font-size:10px;color:#6b7280;margin-right:4px;">{{ __('viewer.ann_color') }}</span>
        <button onclick="setAnnColor('#ef4444')" data-color="#ef4444" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#ef4444;border:none;cursor:pointer;padding:0;outline:2px solid #fff;outline-offset:2px;flex-shrink:0;"></button>
        <button onclick="setAnnColor('#f97316')" data-color="#f97316" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#f97316;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="setAnnColor('#eab308')" data-color="#eab308" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#eab308;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="setAnnColor('#22c55e')" data-color="#22c55e" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#22c55e;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="setAnnColor('#3b82f6')" data-color="#3b82f6" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#3b82f6;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="setAnnColor('#a855f7')" data-color="#a855f7" class="ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#a855f7;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <div style="flex:1;"></div>
        <span style="font-size:10px;color:#4b5563;">{{ __('viewer.ann_hint') }}</span>
    </div>

    {{-- 본문 (뷰어 + 의견) --}}
    <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

        {{-- 뷰어 영역 --}}
        <div style="flex:1;min-width:0;position:relative;background:#1f2937;overflow:hidden;display:flex;flex-direction:column;">

            {{-- 공통 로딩 --}}
            <div id="viewer-loading" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:14px;gap:12px;z-index:2;background:#1f2937;">
                <div style="width:36px;height:36px;border:3px solid rgba(196,181,253,.2);border-top-color:#9b8afb;border-radius:50%;animation:spin .8s linear infinite;"></div>
                <span id="loading-label">{{ __('viewer.loading') }}</span>
            </div>

            {{-- Office/iframe 뷰어 --}}
            <iframe id="viewer-frame" src="" style="width:100%;height:100%;position:absolute;inset:0;border:none;display:none;z-index:1;"
                    onload="document.getElementById('viewer-loading').style.display='none';this.style.display='block';"></iframe>

            {{-- 이미지 뷰어 --}}
            <div id="viewer-img-wrap" style="display:none;position:absolute;inset:0;z-index:1;">
                <div id="img-scroll-wrap" style="position:absolute;top:0;left:0;right:0;bottom:44px;overflow:auto;background:#1f2937;cursor:grab;user-select:none;">
                    <div id="img-inner" style="display:flex;align-items:flex-start;min-height:100%;min-width:100%;padding:20px;box-sizing:border-box;">
                        <img id="viewer-img" src="" alt="" style="display:block;margin:auto;">
                    </div>
                </div>
                <div style="position:absolute;bottom:0;left:0;right:0;height:44px;display:flex;align-items:center;justify-content:center;gap:10px;padding:0 16px;background:#111827;border-top:1px solid rgba(255,255,255,.07);">
                    <button onclick="imgZoom(-0.25)"
                            style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">−</button>
                    <span id="img-zoom-label" style="font-size:12px;color:#9ca3af;min-width:40px;text-align:center;">{{ __('viewer.zoom_fit') }}</span>
                    <button onclick="imgZoom(0.25)"
                            style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">+</button>
                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                    <button onclick="imgZoomFit()"
                            style="padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">{{ __('viewer.zoom_fit') }}</button>
                    <button onclick="imgZoomOriginal()"
                            style="padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">{{ __('viewer.zoom_original') }}</button>
                </div>
            </div>

            {{-- 주석 SVG 오버레이 --}}
            <svg id="ann-svg" xmlns="http://www.w3.org/2000/svg"
                 style="position:absolute;inset:0;width:100%;height:100%;z-index:20;pointer-events:none;overflow:visible;"></svg>

            {{-- 동영상 뷰어 --}}
            <div id="viewer-video" style="display:none;position:absolute;inset:0;z-index:1;flex-direction:column;background:#000;">
                <div id="vid-wrap" style="flex:1;min-height:0;position:relative;display:flex;align-items:center;justify-content:center;background:#000;">
                    <video id="vid-el" preload="metadata" playsinline style="max-width:100%;max-height:100%;display:block;"></video>
                    {{-- 현재 진행 중인 의견 토스트 --}}
                    <div id="vid-comment-toast" style="display:none;position:absolute;top:18px;left:18px;right:18px;max-width:560px;margin:0 auto;background:rgba(15,12,30,.94);border:1px solid rgba(196,181,253,.35);color:#fff;border-radius:10px;padding:10px 14px;font-size:13px;line-height:1.5;box-shadow:0 8px 28px rgba(0,0,0,.4);backdrop-filter:blur(6px);z-index:5;"></div>
                </div>
                {{-- 컨트롤 바 --}}
                <div style="height:auto;background:#111827;border-top:1px solid rgba(255,255,255,.07);padding:8px 14px 10px;flex-shrink:0;">
                    {{-- 타임라인 + 마커 (클릭/드래그 시킹) --}}
                    <div id="vid-track-wrap" style="position:relative;height:18px;margin-bottom:8px;cursor:pointer;user-select:none;">
                        <div id="vid-track" style="position:absolute;top:7px;left:0;right:0;height:5px;background:rgba(255,255,255,.14);border-radius:3px;transition:height .15s, top .15s;pointer-events:none;"></div>
                        <div id="vid-progress" style="position:absolute;top:7px;left:0;width:0;height:5px;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:3px;pointer-events:none;transition:height .15s, top .15s;"></div>
                        <div id="vid-thumb" style="display:none;position:absolute;top:50%;transform:translate(-50%,-50%);width:13px;height:13px;background:#fff;border:2px solid #7c3aed;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.4);pointer-events:none;"></div>
                        <div id="vid-hover-time" style="display:none;position:absolute;bottom:22px;transform:translateX(-50%);background:rgba(15,12,30,.95);color:#fff;font-size:11px;font-weight:600;padding:3px 8px;border-radius:5px;pointer-events:none;font-variant-numeric:tabular-nums;white-space:nowrap;"></div>
                        <div id="vid-markers" style="position:absolute;inset:0;pointer-events:none;"></div>
                    </div>
                    {{-- 컨트롤 버튼 --}}
                    <div style="display:flex;align-items:center;justify-content:center;gap:10px;">
                        <button type="button" onclick="vidSeekRelative(-10)" title="{{ __('viewer.vid_back_10') }}"
                                style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5 5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 17l-5-5 5-5"/></svg>
                            {{ __('viewer.vid_back_10') }}
                        </button>
                        <button type="button" id="vid-play-btn" onclick="vidTogglePlay()" title="{{ __('viewer.vid_play_pause') }}"
                                style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;background:linear-gradient(135deg,#7c3aed,#9b8afb);border:none;color:#fff;border-radius:50%;cursor:pointer;flex-shrink:0;box-shadow:0 4px 14px rgba(124,58,237,.45);"
                                onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                            <svg id="vid-play-icon" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <button type="button" onclick="vidSeekRelative(10)" title="{{ __('viewer.vid_forward_10') }}"
                                style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                            {{ __('viewer.vid_forward_10') }}
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17l5-5-5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 17l5-5-5-5"/></svg>
                        </button>
                        <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                        <span id="vid-time-label" style="font-size:12px;color:#9ca3af;font-variant-numeric:tabular-nums;min-width:90px;text-align:center;">0:00 / 0:00</span>
                        <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                        <button type="button" onclick="vidPauseAndAddComment()" title="{{ __('viewer.vid_add_comment_title') }}"
                                style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:rgba(196,181,253,.15);border:1px solid rgba(196,181,253,.3);color:#c4b5fd;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(196,181,253,.25)'" onmouseout="this.style.background='rgba(196,181,253,.15)'">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            {{ __('viewer.vid_add_comment') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- PDF 뷰어 (PDF.js) --}}
            <div id="viewer-pdf" style="display:none;position:absolute;inset:0;overflow:hidden;z-index:1;">
                {{-- PDF 캔버스 영역 --}}
                <div id="pdf-canvas-wrap" style="position:absolute;top:0;left:0;right:0;bottom:44px;overflow:auto;padding:20px;background:#374151;cursor:grab;user-select:none;">
                    <canvas id="pdf-canvas" style="box-shadow:0 4px 24px rgba(0,0,0,.5);display:block;margin:0 auto;"></canvas>
                </div>
                {{-- PDF 네비게이션 바 (하단) --}}
                <div style="position:absolute;bottom:0;left:0;right:0;height:44px;display:flex;align-items:center;justify-content:center;gap:10px;padding:0 16px;background:#111827;border-top:1px solid rgba(255,255,255,.07);">
                    <button id="pdf-first-btn" onclick="pdfFirstPage()" title="{{ __('viewer.pdf_first_page') }}"
                            style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:28px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 19l-7-7 7-7M11 19l-7-7 7-7"/></svg>
                    </button>
                    <button id="pdf-prev-btn" onclick="pdfPrevPage()"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('common.prev') }}
                    </button>

                    <span id="pdf-page-info" style="font-size:13px;font-weight:600;color:#e5e7eb;min-width:100px;text-align:center;">— / —</span>

                    {{-- 페이지 번호 입력 + 이동 --}}
                    <div style="display:inline-flex;align-items:center;gap:4px;margin-left:4px;">
                        <input id="pdf-page-input" type="number" min="1" value="" placeholder="#"
                               title="{{ __('viewer.pdf_page_input_title') }}"
                               style="width:54px;padding:4px 6px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#e5e7eb;border-radius:6px;font-size:12px;text-align:center;outline:none;"
                               onfocus="this.select();this.style.background='rgba(255,255,255,.13)';this.style.borderColor='rgba(196,181,253,.4)'"
                               onblur="this.style.background='rgba(255,255,255,.07)';this.style.borderColor='rgba(255,255,255,.12)'"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();pdfGoToInput();}">
                        <button onclick="pdfGoToInput()" type="button"
                                title="{{ __('viewer.pdf_goto_input_title') }}"
                                style="padding:5px 9px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;transition:background .15s;"
                                onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                            {{ __('viewer.pdf_goto') }}
                        </button>
                    </div>

                    <button id="pdf-next-btn" onclick="pdfNextPage()"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        {{ __('common.next') }}
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>

                    <button onclick="pdfZoom(-0.2)"
                            style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">−</button>
                    <span id="pdf-zoom-label" style="font-size:12px;color:#9ca3af;min-width:40px;text-align:center;">100%</span>
                    <button onclick="pdfZoom(0.2)"
                            style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">+</button>
                </div>
            </div>
        </div>

        {{-- 의견 패널 토글 (접힘 시 보이는 가장자리 핸들) --}}
        <button id="comment-panel-handle" onclick="toggleCommentPanel()" title="{{ __('viewer.open_comment_panel') }}"
                style="display:none;width:28px;flex-shrink:0;background:#ede9fe;border:none;border-left:1px solid #c4b5fd;cursor:pointer;align-items:center;justify-content:center;color:#6d28d9;padding:0;writing-mode:vertical-rl;font-size:11px;font-weight:700;letter-spacing:.05em;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform:rotate(-90deg);margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            {{ __('viewer.show_comments') }}
        </button>

        {{-- 의견 패널 좌측 리사이즈 핸들 --}}
        <div class="cmt-resize-handle" data-target="comment-panel" title="{{ __('viewer.resize_comment_panel') }}"></div>

        {{-- 의견 패널 --}}
        <div id="comment-panel" style="width:340px;flex-shrink:0;background:#fff;border-left:1px solid #e5e7eb;display:flex;flex-direction:column;">

            {{-- 패널 헤더 --}}
            <div style="padding:12px 16px 10px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
                <div style="font-size:14px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" fill="none" stroke="#6d28d9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    {{ __('viewer.opinions') }}
                    <span id="comment-count" style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700;"></span>
                    <button onclick="toggleCommentPanel()" title="{{ __('viewer.collapse_comment_panel') }}"
                            style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px 4px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;"
                            onmouseover="this.style.color='#6d28d9';this.style.background='#f5f3ff'" onmouseout="this.style.color='#9ca3af';this.style.background='none'">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                <div style="display:flex;gap:6px;margin-top:7px;">
                    <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:#ede9fe;color:#6d28d9;font-weight:600;">{{ __('viewer.badge_screen_ann') }}</span>
                    <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:#f0fdf4;color:#065f46;font-weight:600;">{{ __('viewer.badge_general') }}</span>
                </div>
                {{-- PDF 페이지 필터 바 (JS로 제어) --}}
                <div id="cmt-filter-bar" style="display:none;margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <span id="cmt-filter-label" style="font-size:11px;color:#6b7280;"></span>
                    <button onclick="toggleCommentPageFilter()" id="cmt-filter-btn"
                            style="font-size:11px;color:#7c3aed;background:none;border:1px solid #ede9fe;border-radius:5px;cursor:pointer;padding:2px 8px;font-weight:600;white-space:nowrap;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='none'"></button>
                </div>
            </div>

            {{-- 의견 목록 --}}
            <div id="comment-list" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:10px;">
                <div id="comment-empty" style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">{{ __('viewer.no_opinions') }}</div>
            </div>

            {{-- 의견 작성 폼 --}}
            <div style="padding:12px 14px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9;">
                <div id="page-input-wrap" style="margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <label style="font-size:11px;font-weight:600;color:#6b7280;white-space:nowrap;" id="page-label">{{ __('viewer.page_label_default') }}</label>
                        <div style="display:flex;align-items:center;border:1.5px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff;">
                            <button type="button" onclick="adjustPage(-1)" style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">−</button>
                            <input type="number" id="comment-page" min="1" max="9999" placeholder="—"
                                   style="width:48px;text-align:center;border:none;outline:none;font-size:13px;font-weight:600;color:#1f2937;padding:4px 0;">
                            <button type="button" onclick="adjustPage(1)" style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">+</button>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span id="page-auto-badge" style="display:none;font-size:10px;background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-weight:700;">{{ __('viewer.page_auto') }}</span>
                        <span style="font-size:10px;color:#9ca3af;">{{ __('viewer.page_blank_hint') }}</span>
                    </div>
                </div>
                <textarea id="comment-input" rows="3" placeholder="{{ __('viewer.comment_placeholder') }}"
                          style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;resize:none;outline:none;transition:border-color .15s;box-sizing:border-box;"
                          onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"
                          onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter')submitComment()"></textarea>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:7px;">
                    <span style="font-size:11px;color:#9ca3af;">{{ __('viewer.ctrl_enter_hint') }}</span>
                    <button onclick="submitComment()" id="submit-btn"
                            style="padding:7px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                        {{ __('viewer.submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- ============================  버전 비교 모달  ============================ --}}
<div id="compare-modal" style="display:none;position:fixed;inset:0;z-index:10006;background:#0d0a1c;flex-direction:column;">
    {{-- 상단바 --}}
    <div style="height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:14px;padding:0 16px;flex-shrink:0;">
        <span style="font-size:13px;font-weight:700;color:#e5e7eb;display:inline-flex;align-items:center;gap:7px;flex-shrink:0;">
            <svg width="15" height="15" fill="none" stroke="#7dd3fc" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/></svg>
            {{ __('viewer.compare_versions') }}
        </span>
        <span id="compare-title" style="flex:1;overflow:hidden;font-size:12px;color:#9ca3af;white-space:nowrap;text-overflow:ellipsis;"></span>
        <label id="compare-sync-wrap" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#c4b5fd;cursor:pointer;user-select:none;flex-shrink:0;opacity:.5;">
            <input type="checkbox" id="compare-sync-pages" onchange="onCompareSyncToggle()" disabled
                   style="width:15px;height:15px;accent-color:#7c3aed;cursor:pointer;">
            <span id="compare-sync-label">{{ __('viewer.sync_same_page') }}</span>
        </label>
        <span id="compare-sync-note" style="display:none;font-size:11px;color:#6b7280;flex-shrink:0;"></span>
        <button onclick="closeCompare()"
                style="display:inline-flex;align-items:center;gap:6px;color:#c4b5fd;font-size:13px;font-weight:600;background:none;border:1px solid rgba(196,181,253,.25);cursor:pointer;padding:6px 12px;border-radius:8px;flex-shrink:0;transition:background .15s;"
                onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ __('common.close') }}
        </button>
    </div>
    {{-- 본문: 두 뷰어 나란히 --}}
    <div style="display:flex;flex:1;min-height:0;">
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;border-right:2px solid #2d2748;">
            <div id="compare-label-a" class="compare-pane-label"></div>
            <iframe id="compare-frame-a" title="{{ __('viewer.compare_frame_left') }}" style="flex:1;width:100%;border:none;background:#1a1730;"></iframe>
        </div>
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;">
            <div id="compare-label-b" class="compare-pane-label"></div>
            <iframe id="compare-frame-b" title="{{ __('viewer.compare_frame_right') }}" style="flex:1;width:100%;border:none;background:#1a1730;"></iframe>
        </div>
    </div>
</div>

{{-- ============================  URL 뷰어 팝업  ============================ --}}
<div id="url-viewer-modal" onclick="if(event.target===this)closeUrlViewer()" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,8,20,.75);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:32px;">
<div style="width:100%;max-width:1400px;height:calc(100vh - 64px);max-height:880px;background:#1a1730;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6);border:1px solid rgba(196,181,253,.15);">

    {{-- 상단바 --}}
    <div style="height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:12px;padding:0 16px;flex-shrink:0;border-radius:16px 16px 0 0;">
        <button onclick="closeUrlViewer()" style="display:inline-flex;align-items:center;gap:6px;color:#c4b5fd;font-size:13px;font-weight:600;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;transition:background .15s;" onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ __('viewer.close') }}
        </button>
        <span id="url-modal-fname" style="flex:1;overflow:hidden;font-size:13px;font-weight:600;color:#e5e7eb;white-space:nowrap;text-overflow:ellipsis;"></span>
        <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;background:#312e81;color:#a5b4fc;flex-shrink:0;">URL</span>
        <button onclick="urlNewTab()" style="display:inline-flex;align-items:center;gap:5px;color:#a5b4fc;font-size:12px;font-weight:600;background:none;border:1px solid rgba(165,180,252,.25);padding:5px 10px;border-radius:7px;cursor:pointer;transition:background .15s;flex-shrink:0;" onmouseover="this.style.background='rgba(165,180,252,.1)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            {{ __('viewer.open_new_tab') }}
        </button>
        <button onclick="urlPrintPdf()" style="display:inline-flex;align-items:center;gap:5px;color:#fff;font-size:12px;font-weight:600;background:linear-gradient(135deg,#7c3aed,#6366f1);border:none;padding:5px 12px;border-radius:7px;cursor:pointer;transition:opacity .15s;flex-shrink:0;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('viewer.save_pdf') }}
        </button>
        <button type="button" onclick="downloadUrlCommentsReport()"
                title="{{ __('viewer.url_download_comments_title') }}"
                style="display:inline-flex;align-items:center;gap:5px;color:#86efac;font-size:12px;font-weight:600;background:none;border:1px solid rgba(134,239,172,.3);border-radius:7px;padding:5px 10px;flex-shrink:0;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='rgba(134,239,172,.1)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('viewer.download_with_review') }}
        </button>
        <button type="button" id="url-preview-fs-btn" onclick="toggleUrlPreviewFullscreen()" class="preview-fs-btn" title="{{ __('viewer.fullwindow') }}">
            <svg class="preview-fs-icon-off" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            <svg class="preview-fs-icon-on" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V5m0 0H5m4 4L4 4m11 5h4m0 0V5m0 4l5-5M9 15v4m0 0H5m4 0l-5 5m11-5h4m0 0v4m0-4l5 5"/></svg>
            {{ __('viewer.fullwindow') }}
        </button>
    </div>

    {{-- 본문 (iframe + 의견 패널) --}}
    <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

        {{-- iframe 영역 --}}
        <div style="flex:1;min-width:0;position:relative;background:#1e1b2e;overflow:hidden;">
            <div id="url-modal-loading" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:14px;gap:12px;z-index:2;background:#1e1b2e;">
                <div style="width:36px;height:36px;border:3px solid rgba(196,181,253,.2);border-top-color:#9b8afb;border-radius:50%;animation:spin .8s linear infinite;"></div>
                <span>{{ __('viewer.loading') }}</span>
            </div>
            <iframe id="url-modal-frame" src="" style="width:100%;height:100%;position:absolute;inset:0;border:none;display:none;z-index:1;background:#fff;"
                    allowfullscreen allow="fullscreen"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation"
                    onload="window.onUrlModalLoad && onUrlModalLoad()">
            </iframe>
            <div id="url-modal-block" style="display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;background:#1e1b2e;gap:18px;padding:48px;text-align:center;z-index:3;">
                <div style="font-size:52px;">🔒</div>
                <div style="font-size:16px;font-weight:700;color:#e9d5ff;">{{ __('viewer.embed_blocked_title') }}</div>
                <div style="font-size:13px;color:#a78bfa;max-width:420px;line-height:1.8;">{!! __('viewer.embed_blocked_line1') !!}<br>{{ __('viewer.embed_blocked_line2') }}</div>
                <a id="url-modal-block-link" href="#" target="_blank" style="display:inline-flex;align-items:center;gap:7px;padding:11px 28px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border-radius:10px;font-size:14px;font-weight:700;text-decoration:none;transition:opacity .15s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    {{ __('viewer.open_new_tab_btn') }}
                </a>
            </div>
        </div>

        {{-- 의견 패널 좌측 리사이즈 핸들 --}}
        <div class="cmt-resize-handle" data-target="url-comment-panel" title="{{ __('viewer.resize_comment_panel') }}"></div>

        {{-- 의견 패널 --}}
        <div id="url-comment-panel" style="width:340px;flex-shrink:0;background:#fff;border-left:1px solid #e5e7eb;display:flex;flex-direction:column;">
            <div style="padding:12px 16px 10px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
                <div style="font-size:14px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" fill="none" stroke="#6d28d9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    {{ __('viewer.opinions') }}
                    <span id="url-cmt-count" style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700;"></span>
                </div>
            </div>
            <div id="url-cmt-list" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:10px;">
                <div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">{{ __('viewer.no_opinions') }}</div>
            </div>
            <div style="padding:12px 14px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9;">
                <textarea id="url-cmt-input" rows="3" placeholder="{{ __('viewer.opinion_placeholder') }}"
                          style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;resize:none;outline:none;transition:border-color .15s;box-sizing:border-box;"
                          onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"
                          onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter')urlSubmitCmt()"></textarea>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:7px;">
                    <span style="font-size:11px;color:#9ca3af;">{{ __('viewer.ctrl_enter_hint') }}</span>
                    <button onclick="urlSubmitCmt()" id="url-cmt-btn"
                            style="padding:7px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                        {{ __('viewer.submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- 텍스트 주석 입력 팝업 --}}
<div id="ann-text-popup" style="display:none;position:fixed;z-index:10010;background:#fff;border:2px solid #a78bfa;border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);min-width:280px;max-width:360px;">
    <div id="ann-text-popup-title" style="font-size:11px;font-weight:700;color:#6d28d9;margin-bottom:8px;">{{ __('viewer.ann_text_title') }}</div>
    <textarea id="ann-text-input" rows="4" placeholder="{{ __('viewer.ann_text_placeholder') }}"
           style="width:100%;border:1.5px solid #e5e7eb;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;line-height:1.5;font-family:inherit;transition:border-color .15s;"
           onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button onclick="confirmAnnText()" style="flex:1;padding:6px 0;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">{{ __('viewer.ann_confirm') }}</button>
        <button onclick="cancelAnnText()" style="flex:1;padding:6px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">{{ __('viewer.ann_cancel') }}</button>
    </div>
</div>

{{-- ============================  의견 → 논의사항 변환 다이얼로그  ============================ --}}
<div id="cnv-confirm-modal" onclick="if(event.target===this)cnvCloseConfirm()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(0,0,0,.5);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:480px;max-width:calc(100vw - 48px);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('viewer.register_as_discussion') }}
            </h3>
            <button onclick="cnvCloseConfirm()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>
        <div style="padding:18px 22px;">
            <p style="margin:0 0 12px;font-size:13px;color:#374151;">{{ __('viewer.confirm_register_discussion') }}</p>
            <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:10px 12px;max-height:160px;overflow-y:auto;">
                <div id="cnv-confirm-meta" style="font-size:11px;color:#9ca3af;margin-bottom:4px;font-weight:600;"></div>
                <div id="cnv-confirm-content" style="font-size:13px;color:#1f2937;white-space:pre-wrap;word-break:break-word;line-height:1.5;"></div>
            </div>
        </div>
        <div style="padding:12px 22px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button onclick="cnvCloseConfirm()" style="padding:7px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.cancel') }}</button>
            <button id="cnv-confirm-ok" onclick="cnvDoConvert()" style="padding:7px 18px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">{{ __('common.register') }}</button>
        </div>
    </div>
</div>

<div id="cnv-detail-modal" onclick="if(event.target===this)cnvCloseDetail()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(0,0,0,.5);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:760px;max-width:calc(100vw - 48px);max-height:85vh;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:10px;">
            <h3 id="cnv-detail-title" style="margin:0;font-size:15px;font-weight:700;color:#1f2937;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ __('viewer.discussion') }}</h3>
            <span id="cnv-detail-status" style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0;"></span>
            <button onclick="cnvCloseDetail()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;flex-shrink:0;">×</button>
        </div>
        <div id="cnv-detail-meta" style="padding:10px 22px;border-bottom:1px solid #f3f4f6;font-size:12px;color:#6b7280;background:#fafafa;"></div>
        <div style="padding:18px 22px;overflow-y:auto;flex:1;min-height:0;">
            <div id="cnv-detail-content" style="font-size:14px;color:#1f2937;line-height:1.7;word-break:break-word;"></div>
            <div id="cnv-detail-conclusion" style="margin-top:18px;padding:14px;background:#fef3c7;border-left:3px solid #f59e0b;border-radius:6px;display:none;">
                <div style="font-size:11px;font-weight:700;color:#b45309;margin-bottom:6px;letter-spacing:.05em;">{{ __('viewer.conclusion') }}</div>
                <div id="cnv-detail-conclusion-body" style="font-size:13px;color:#92400e;line-height:1.6;"></div>
            </div>
        </div>
        <div style="padding:12px 22px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button onclick="cnvCloseDetail()" style="padding:7px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.close') }}</button>
            <a id="cnv-detail-link" style="padding:7px 18px;background:#7c3aed;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                {{ __('viewer.open_in_discussions') }}
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>
</div>

{{-- ====== 수정본 업로드 다이얼로그 ====== --}}
<div id="upv-modal" onclick="if(event.target===this)closeUploadVersionModal()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:500px;max-width:calc(100vw - 48px);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('viewer.upload_revision_heading') }}
            </h3>
            <button type="button" onclick="closeUploadVersionModal()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>
        <div style="padding:18px 22px;">
            <p style="margin:0 0 14px;font-size:13px;color:#374151;line-height:1.55;">
                {{ __('viewer.upload_revision_desc') }}<br>
                <span style="font-size:12px;color:#6b7280;">{!! __('viewer.upload_revision_note', ['done' => '<strong style="color:#15803d;">'.__('viewer.applied_done').'</strong>']) !!}</span>
            </p>
            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">{{ __('viewer.new_file_label') }}</label>
            <input type="file" id="upv-file" class="upv-file-native">
            <label for="upv-file" id="upv-file-drop" class="upv-file-drop">
                <svg width="22" height="22" fill="none" stroke="#a78bfa" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1A5.5 5.5 0 0118 16h-1m-6-1l3-3m0 0l3 3m-3-3v8"/></svg>
                <div class="upv-file-drop-main"><span id="upv-file-drop-cta">{{ __('viewer.choose_file') }}</span><span class="upv-file-drop-hint">{{ __('viewer.drop_file_here') }}</span></div>
            </label>
            <div id="upv-filename" style="margin-top:6px;font-size:12px;color:#7c3aed;font-weight:600;"></div>

            <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin:14px 0 6px;">{{ __('viewer.change_note_label') }}</label>
            <textarea id="upv-note" rows="3" placeholder="{{ __('viewer.change_note_placeholder') }}"
                      style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;line-height:1.55;resize:vertical;box-sizing:border-box;outline:none;"
                      onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>
        <div style="padding:12px 22px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeUploadVersionModal()" style="padding:7px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.cancel') }}</button>
            <button type="button" id="upv-submit-btn" onclick="submitUploadVersion()" style="padding:7px 18px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">{{ __('common.upload') }}</button>
        </div>
    </div>
</div>

{{-- ====== 버전 이력 다이얼로그 ====== --}}
<div id="vh-modal" onclick="if(event.target===this)closeVersionHistory()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:560px;max-width:calc(100vw - 48px);max-height:80vh;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;">{{ __('viewer.version_history') }}</h3>
            <button type="button" onclick="closeVersionHistory()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>
        <div id="vh-list" style="padding:14px 22px;overflow-y:auto;flex:1;min-height:0;font-size:13px;color:#374151;"></div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* PDF 페이지 입력 + 의견 페이지 입력 — 기본 브라우저 number 스피너 화살표 숨김
   각 입력은 자체 prev/next/+/- 버튼이 별도로 있어 네이티브 스피너는 중복·다크테마 부조화 유발 */
#pdf-page-input::-webkit-outer-spin-button,
#pdf-page-input::-webkit-inner-spin-button,
#comment-page::-webkit-outer-spin-button,
#comment-page::-webkit-inner-spin-button,
#url-comment-page::-webkit-outer-spin-button,
#url-comment-page::-webkit-inner-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
}
#pdf-page-input,
#comment-page,
#url-comment-page { -moz-appearance: textfield; appearance: textfield; }

/* 수정본 업로드 — 네이티브 file input 숨기고 드래그/클릭 영역 */
.upv-file-native { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; overflow:hidden; }
.upv-file-drop {
    display:flex; align-items:center; gap:12px;
    padding:14px 16px;
    border:1.5px dashed #d8d4e8; border-radius:10px;
    background:#fafaff;
    cursor:pointer;
    transition:border-color .15s, background .15s;
    user-select:none;
}
.upv-file-drop:hover { border-color:#a78bfa; background:#f5f3ff; }
.upv-file-drop.is-dragover { border-color:#7c3aed; background:#ede9fe; }
.upv-file-drop.has-file    { border-color:#16a34a; background:#f0fdf4; }
.upv-file-drop.has-file svg { stroke:#16a34a; }
.upv-file-drop-main { display:flex; flex-direction:column; gap:2px; min-width:0; flex:1; }
#upv-file-drop-cta  { font-size:13px; font-weight:700; color:#374151; }
.upv-file-drop-hint { font-size:11.5px; color:#9ca3af; }
.upv-file-drop:focus-within { outline:2px solid #c4b5fd; outline-offset:2px; }

/* 전체창 보기 모드 — viewport 가득 채움 */
#preview-modal.is-fullscreen, #url-viewer-modal.is-fullscreen { padding: 0 !important; }
#preview-modal.is-fullscreen > div, #url-viewer-modal.is-fullscreen > div {
    max-width: none !important;
    max-height: none !important;
    width: 100vw !important;
    height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
}
.preview-fs-btn { display:inline-flex; align-items:center; gap:5px; color:#c4b5fd; font-size:12px; font-weight:600; padding:5px 10px; border:1px solid rgba(196,181,253,.25); border-radius:7px; flex-shrink:0; background:none; cursor:pointer; transition:background .15s; }
.preview-fs-btn:hover { background:rgba(196,181,253,.1); }
.preview-fs-icon-on  { display:none; }
.is-fullscreen .preview-fs-btn .preview-fs-icon-off { display:none; }
.is-fullscreen .preview-fs-btn .preview-fs-icon-on  { display:inline; }

/* 의견 패널 좌측 리사이즈 핸들 — 항상 보이는 그립 아이콘 */
.cmt-resize-handle {
    width: 6px;
    flex-shrink: 0;
    cursor: col-resize;
    background: rgba(255,255,255,.06);
    transition: background .15s;
    position: relative;
}
.cmt-resize-handle::before {
    /* 6개의 그립 점 (2열 × 3행) */
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 2px; height: 2px;
    background: transparent;
    border-radius: 50%;
    box-shadow:
        -3px -9px 0 1px #c4b5fd,
         3px -9px 0 1px #c4b5fd,
        -3px  0   0 1px #c4b5fd,
         3px  0   0 1px #c4b5fd,
        -3px  9px 0 1px #c4b5fd,
         3px  9px 0 1px #c4b5fd;
    opacity: .7;
    transition: opacity .15s;
    pointer-events: none;
}
.cmt-resize-handle:hover, .cmt-resize-handle.is-dragging { background: rgba(196,181,253,.45); }
.cmt-resize-handle:hover::before, .cmt-resize-handle.is-dragging::before {
    opacity: 1;
    box-shadow:
        -3px -9px 0 1px #fff,  3px -9px 0 1px #fff,
        -3px  0   0 1px #fff,  3px  0   0 1px #fff,
        -3px  9px 0 1px #fff,  3px  9px 0 1px #fff;
}
body.cmt-resizing { cursor: col-resize !important; user-select: none !important; }
body.cmt-resizing iframe { pointer-events: none; }
.comment-card { background:#f9fafb; border:1px solid #f3f4f6; border-radius:10px; padding:10px 12px; transition:background .15s; }
.comment-card:hover { background:#f3f4f6; }
.page-badge { display:inline-block; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; }
.reply-thread { margin-top:8px; padding-left:12px; border-left:2px solid #e9d5ff; display:flex; flex-direction:column; gap:6px; }
.reply-card { background:#fff; border:1px solid #f3e8ff; border-radius:8px; padding:8px 10px; }
.reply-btn { background:none; border:none; cursor:pointer; font-size:11px; font-weight:600; color:#9ca3af; padding:2px 6px; border-radius:4px; transition:color .12s, background .12s; }
.reply-btn:hover { color:#7c3aed; background:#f5f3ff; }
.cmt-reply-cta { display:inline-flex; align-items:center; gap:5px; margin-top:8px; padding:5px 11px; background:#fff; border:1.5px solid #e5e7eb; color:#6b7280; font-size:12px; font-weight:600; border-radius:7px; cursor:pointer; transition:all .12s; }
.cmt-reply-cta:hover { background:#f5f3ff; border-color:#c4b5fd; color:#7c3aed; }
.cmt-reply-cta svg { flex-shrink:0; }
.reply-form { margin-top:8px; padding:8px 10px; background:#faf5ff; border:1.5px solid #e9d5ff; border-radius:8px; }
.reply-textarea { width:100%; padding:7px 10px; border:1.5px solid #e5e7eb; border-radius:6px; font-size:12px; color:#1f2937; resize:none; outline:none; box-sizing:border-box; font-family:inherit; transition:border-color .15s; }
.reply-textarea:focus { border-color:#a78bfa; }
.ann-tool-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px;
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1);
    border-radius:6px; color:#9ca3af; cursor:pointer;
    transition:background .15s, color .15s, border-color .15s;
    padding:0; flex-shrink:0;
}
.ann-tool-btn:hover { background:rgba(196,181,253,.15); color:#c4b5fd; }
.ann-tool-btn.active { background:rgba(196,181,253,.28); color:#c4b5fd; border-color:rgba(196,181,253,.45); }
.ann-item { cursor:default; }
.ann-item[data-can-delete="1"] { cursor:pointer; }

/* ── 버전 비교 ── */
.compare-pane-label {
    flex-shrink:0; height:32px;
    display:flex; align-items:center; gap:8px;
    padding:0 14px;
    background:rgba(124,58,237,.13);
    border-bottom:1px solid rgba(196,181,253,.14);
    font-size:12px; font-weight:700;
    white-space:nowrap; overflow:hidden;
}
.vcmp-row {
    display:flex; align-items:center; gap:8px;
    padding:6px 8px; border-radius:7px;
    cursor:pointer; transition:background .12s;
}
.vcmp-row:hover { background:rgba(196,181,253,.1); }
.vcmp-row.is-checked { background:rgba(125,211,252,.14); }
.vcmp-row.is-disabled { opacity:.4; cursor:not-allowed; }
.vcmp-row input { width:15px; height:15px; accent-color:#0ea5e9; cursor:pointer; flex-shrink:0; }
.vcmp-vtag { font-size:11px; font-weight:700; color:#c4b5fd; background:rgba(196,181,253,.15); padding:2px 7px; border-radius:4px; flex-shrink:0; }
.vcmp-meta { display:flex; flex-direction:column; min-width:0; }
.vcmp-name { font-size:12px; color:#e5e7eb; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.vcmp-sub  { font-size:10px; color:#8b85a8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>

{{-- PDF.js --}}
<script src="https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
{{-- jsPDF --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const BASE_URL   = '{{ rtrim(url("/"), "/") }}';

const FM_STR = {
    loading:               '{{ __("viewer.loading") }}',
    no_opinions:           '{{ __("viewer.no_opinions") }}',
    zoom_fit:              '{{ __("viewer.zoom_fit") }}',
    confirm_delete_opinion:'{{ __("viewer.confirm_delete_opinion") }}',
    confirm_delete_ann:    '{{ __("viewer.confirm_delete_ann") }}',
    ann_text_edit:         '{{ __("viewer.ann_text_edit") }}',
    ann_text_title:        '{{ __("viewer.ann_text_title") }}',
    submit:                '{{ __("viewer.submit") }}',
    submit_opinion:        '{{ __("viewer.submit_opinion") }}',
    opinions:              '{{ __("viewer.opinions") }}',
    badge_screen_ann:      '{{ __("viewer.badge_screen_ann") }}',
    badge_general:         '{{ __("viewer.badge_general") }}',
    page_label_pdf:        '{{ __("viewer.page_label_pdf") }}',
    page_label_sheet:      '{{ __("viewer.page_label_sheet") }}',
    page_label_slide:      '{{ __("viewer.page_label_slide") }}',
    page_label_default:    '{{ __("viewer.page_label_default") }}',
    converting_doc:        '{{ __("viewer.converting_doc") }}',
    pdf_loading:           '{{ __("viewer.pdf_loading") }}',
    page_of:               '{{ __("viewer.page_of") }}',
    filter_all_pages:      '{{ __("viewer.filter_all_pages") }}',
    filter_current_page:   '{{ __("viewer.filter_current_page") }}',
    filter_showing_page:   '{{ __("viewer.filter_showing_page") }}',
    filter_view_all:       '{{ __("viewer.filter_view_all") }}',
    no_opinions_page:      '{{ __("viewer.no_opinions_page") }}',
    be_first_to_comment:   '{{ __("viewer.be_first_to_comment") }}',
    page_badge:            '{{ __("viewer.page_badge") }}',
    opinions_count:        '{{ __("viewer.opinions_count") }}',
    load_failed:           '{{ __("viewer.load_failed") }}',
    save_failed:           '{{ __("viewer.save_failed") }}',
    delete_failed:         '{{ __("viewer.delete_failed") }}',
    ann_save_failed:       '{{ __("viewer.ann_save_failed") }}',
    ann_save_failed_detail:'{{ __("viewer.ann_save_failed_detail") }}',
    ann_by:                '{{ __("viewer.ann_by") }}',
    pdf_build_failed:      '{{ __("viewer.pdf_build_failed") }}',
    pdf_info_missing:      '{{ __("viewer.pdf_info_missing") }}',
    generating:            '{{ __("viewer.generating") }}',
    review_suffix:         '{{ __("viewer.review_filename_suffix") }}',
    reply:                 '{{ __("viewer.reply") }}',
    reply_placeholder:     '{{ __("viewer.reply_placeholder") }}',
    reply_url_placeholder: '{{ __("viewer.reply_url_placeholder") }}',
    reply_submitting:      '{{ __("viewer.reply_submitting") }}',
    cancel:                '{{ __("viewer.cancel") }}',
    register:              '{{ __("viewer.register") }}',
    popup_blocked:         '{{ __("viewer.popup_blocked_msg") }}',
    pdfjs_not_loaded:      '{{ __("viewer.pdfjs_not_loaded") }}',
    jspdf_not_loaded:      '{{ __("viewer.jspdf_not_loaded") }}',
    comment_placeholder:   '{{ __("viewer.comment_placeholder") }}',
    discussion:            '{{ __("viewer.discussion") }}',
    view_discussion:       '{{ __("viewer.view_discussion") }}',
    register_discussion:   '{{ __("viewer.register_discussion") }}',
    register_doing:        '{{ __("viewer.register_doing") }}',
    file_info_missing:     '{{ __("viewer.file_info_missing") }}',
    network_error:         '{{ __("common.network_error") }}',
    already_discussion:    '{{ __("viewer.already_discussion") }}',
    discussion_reg_failed: '{{ __("viewer.discussion_reg_failed") }}',
    loading_text:          '{{ __("common.loading") }}',
    discussion_load_failed:'{{ __("viewer.discussion_load_failed") }}',
    meta_author:           '{{ __("viewer.meta_author") }}',
    meta_discussion_date:  '{{ __("viewer.meta_discussion_date") }}',
    meta_registered:       '{{ __("viewer.meta_registered") }}',
    no_content:            '{{ __("viewer.no_content") }}',
    upload:                '{{ __("common.upload") }}',
    uploading:             '{{ __("viewer.uploading") }}',
    upload_failed:         '{{ __("viewer.upload_failed") }}',
    upload_done:           '{{ __("viewer.upload_done") }}',
    upload_confirm:        '{{ __("viewer.upload_confirm") }}',
    loading_ellipsis:      '{{ __("viewer.loading_ellipsis") }}',
    no_version_history:    '{{ __("viewer.no_version_history") }}',
    load_failed_short:     '{{ __("viewer.load_failed_short") }}',
    select_file_alert:     '{{ __("viewer.select_file_alert") }}',
    file_selected:         '{{ __("viewer.file_selected") }}',
    choose_file:           '{{ __("viewer.choose_file") }}',
    register:              '{{ __("viewer.register") }}',
    cmp_pick_versions:     '{{ __("viewer.cmp_pick_versions") }}',
    cmp_pick_hint:         '{{ __("viewer.cmp_pick_hint") }}',
    cmp_select_prompt:     '{{ __("viewer.cmp_select_prompt") }}',
    cmp_view_one:          '{{ __("viewer.cmp_view_one") }}',
    cmp_compare_two:       '{{ __("viewer.cmp_compare_two") }}',
    cmp_current:           '{{ __("viewer.cmp_current") }}',
    cmp_current_version:   '{{ __("viewer.cmp_current_version") }}',
    sync_same_video:       '{{ __("viewer.sync_same_video") }}',
    sync_same_page:        '{{ __("viewer.sync_same_page") }}',
    sync_same_format_only: '{{ __("viewer.sync_same_format_only") }}',
    confirm_delete_short:  '{{ __("viewer.confirm_delete_short") }}',
    del_cancel:            '{{ __("common.cancel") }}',
    del_confirm:           '{{ __("common.delete") }}',
    vid_err_aborted:       '{{ __("viewer.vid_err_aborted") }}',
    vid_err_network:       '{{ __("viewer.vid_err_network") }}',
    vid_err_decode:        '{{ __("viewer.vid_err_decode") }}',
    vid_err_src:           '{{ __("viewer.vid_err_src") }}',
    vid_err_generic:       '{{ __("viewer.vid_err_generic") }}',
    vid_err_cannot_play:   '{{ __("viewer.vid_err_cannot_play") }}',
    vid_cannot_play_title: '{{ __("viewer.vid_cannot_play_title") }}',
    goto_page_suffix:      '{{ __("viewer.goto_page_suffix") }}',
    vid_seek_to:           '{{ __("viewer.vid_seek_to") }}',
    vid_comment_at_time:   '{{ __("viewer.vid_comment_at_time") }}',
};
let currentFileId    = null;
let currentProjectId = null;

// 버전 비교 모달의 iframe 안에서 로드된 임베드 뷰어인지 여부
const IS_EMBED = new URLSearchParams(location.search).get('embed') === '1';

// ── PDF.js 상태 ──────────────────────────────────
let pdfDoc        = null;
let pdfPage       = 1;
let pdfTotal      = 0;
let pdfScale      = 1.0;
let pdfRendering  = false;
let pdfPending    = null;
let pdfSheetNames = [];

// ── 도형 주석 상태 ────────────────────────────────
let annTool           = null;    // 'number'|'rect'|'circle'|'line'|'text'|null
let annColor          = '#ef4444';
let annNextNum        = 1;

function calcNextNum(list) {
    const nums = list.filter(a => a.type === 'number').map(a => a.data?.n ?? 0);
    return nums.length ? Math.max(...nums) + 1 : 1;
}
let annList           = [];
let annSelected       = null;   // currently selected annotation object
let annDrawing        = false;
let annStartX         = 0;
let annStartY         = 0;
let annDragEl         = null;
let annMoveActive     = false;  // dragging annotation to reposition
let annMoveStartX     = 0;
let annMoveStartY     = 0;
let annMoveStartData  = null;
let _annPreviewType   = null;
let _annTextPct       = null;
let _annEditId        = null;   // annotation ID being edited (text type)

// ── 의견 패널 통합 상태 ────────────────────────────
let _commentsList   = [];    // 일반 의견 캐시
let _commentShowAll = false; // false=현재 페이지, true=전체

// ── 동적 API 베이스 URL (SR 유지보수 파일용) ────────
let _commentApiBase    = null; // null → 기본 프로젝트 파일 URL 사용
let _annotationApiBase = null;
let _currentServeUrl   = null;
let _currentFileName   = null;

function _cBase() {
    return _commentApiBase || `${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}`;
}
function _aBase() {
    return _annotationApiBase || `${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}`;
}

// ── 실시간 의견 구독 (Pusher/Echo) ────────────────
let _echoFileId = null;

function _subscribeFileComments(fileId) {
    if (!window.Echo) return;
    if (_echoFileId === fileId) return;
    if (_echoFileId !== null) window.Echo.leave('file.' + _echoFileId);
    _echoFileId = fileId;
    window.Echo.channel('file.' + fileId)
        .listen('.FileCommentPosted', _onEchoFileComment)
        .listen('.FileCommentDeleted', _onEchoFileCommentDeleted);
}

function _maybeUnsubscribeFileComments() {
    if (currentFileId !== null || _cmFileId !== null) return;
    if (_echoFileId !== null && window.Echo) window.Echo.leave('file.' + _echoFileId);
    _echoFileId = null;
}

function _onEchoFileComment(data) {
    // 프리뷰 패널 (인라인 의견 목록)
    if (data.parent_id) {
        // 답글: 부모 의견의 replies 배열에 추가 (중복 방지)
        const parent = _commentsList.find(c => c.id === data.parent_id);
        if (parent && !(parent.replies || []).some(r => r.id === data.id)) {
            parent.replies = parent.replies || [];
            parent.replies.push(data);
            renderAllComments();
        }
    } else if (!_commentsList.some(c => c.id === data.id)) {
        _commentsList.unshift({ ...data, replies: [] });
        renderAllComments();
        if (_annPreviewType === 'video') vidRenderMarkers();
    }
    // 의견 전용 모달 — 미리보기 패널이 같은 파일을 이미 표시 중이면 건너뜀 (중복 방지)
    const cmModal = document.getElementById('comments-modal');
    if (cmModal && cmModal.style.display !== 'none' && _cmFileId !== currentFileId) {
        if (data.parent_id) {
            const parent = _cmComments.find(c => c.id === data.parent_id);
            if (parent && !(parent.replies || []).some(r => r.id === data.id)) {
                parent.replies = parent.replies || [];
                parent.replies.push(data);
                renderCmComments();
            }
        } else if (!_cmComments.some(c => c.id === data.id)) {
            _cmComments.unshift({ ...data, replies: [] });
            renderCmComments();
        }
    }
}

function _onEchoFileCommentDeleted(data) {
    const id = data.id;
    _commentsList = _commentsList.filter(c => c.id !== id);
    renderAllComments();
    const cmModal = document.getElementById('comments-modal');
    if (cmModal && cmModal.style.display !== 'none') {
        _cmComments = _cmComments.filter(c => c.id !== id);
        renderCmComments();
    }
}

// ── 팝업 열기 ─────────────────────────────────────
function openPreview(fileId, projectId, customPreviewDataUrl, customDownloadUrl) {
    currentFileId    = fileId;
    currentProjectId = projectId;
    _subscribeFileComments(fileId);
    document.getElementById('preview-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    resetViewers();
    applyCommentPanelState();

    const _convTimer = setTimeout(() => {
        document.getElementById('loading-label').textContent = FM_STR.converting_doc;
    }, 2500);

    const previewDataUrl = customPreviewDataUrl || `${BASE_URL}/projects/${currentProjectId}/files/${fileId}/preview-data`;

    fetch(previewDataUrl, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => {
        if (!r.ok) return r.text().then(t => { throw new Error(`HTTP ${r.status}: ${t.substring(0,300)}`); });
        return r.json();
    })
    .then(data => {
        clearTimeout(_convTimer);
        if (data.error) { alert(data.error); closePreview(); return; }
        _commentApiBase    = data.commentApiBase    || null;
        _annotationApiBase = data.annotationApiBase || null;
        _annPreviewType    = data.previewType;
        _currentServeUrl   = data.serveUrl || null;
        _currentFileName   = data.fileName || null;

        document.getElementById('modal-filename').textContent = data.fileName;
        renderVersionBar(data.versions || [], data.version || 1);
        refreshCompareButton(data.versions || []);
        window._currentVersion = data.version || 1;
        const _annDlBtn = document.getElementById('ann-dl-btn');
        if (_annDlBtn) _annDlBtn.style.display = (data.previewType === 'pdf' || data.previewType === 'image') ? 'inline-flex' : 'none';
        const downloadHref = customDownloadUrl || `${BASE_URL}/projects/${currentProjectId}/files/${fileId}/download`;
        document.getElementById('modal-download').href = downloadHref;

        const extMap = { docx:'Word', doc:'Word', xlsx:'Excel', xls:'Excel', pptx:'PowerPoint', ppt:'PowerPoint', pdf:'PDF' };
        const colMap = { office:'background:#1e3a5f;color:#60a5fa', pdf:'background:#3f1515;color:#f87171', image:'background:#1a3321;color:#4ade80' };
        const badge  = document.getElementById('modal-badge');
        badge.textContent  = extMap[data.ext] || data.ext.toUpperCase();
        badge.style.cssText = (colMap[data.previewType] || 'background:#374151;color:#d1d5db')
                            + ';font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;flex-shrink:0;';

        const pageLabel = { pdf: FM_STR.page_label_pdf, xlsx: FM_STR.page_label_sheet, xls: FM_STR.page_label_sheet };
        document.getElementById('page-label').textContent = pageLabel[data.ext] || FM_STR.page_label_slide;
        document.getElementById('page-input-wrap').style.display = data.hasPages ? 'block' : 'none';

        if (data.previewType === 'pdf') {
            document.getElementById('page-auto-badge').style.display = 'inline';
            pdfSheetNames = data.sheetNames || [];
            loadPdfJs(data.serveUrl);
        } else if (data.previewType === 'image') {
            const img = document.getElementById('viewer-img');
            imgZoomFit(true); // reset zoom state
            document.getElementById('viewer-img-wrap').style.display = 'block';
            img.onload = () => {
                _imgNatW = img.naturalWidth;
                _imgNatH = img.naturalHeight;
                document.getElementById('viewer-loading').style.display = 'none';
                imgZoomOriginal();
                requestAnimationFrame(_updateSvgPosition);
            };
            img.src = data.serveUrl;
        } else if (data.previewType === 'video') {
            document.getElementById('page-auto-badge').style.display = 'none';
            // 동영상은 페이지 입력 불필요 → 입력 영역 숨김
            document.getElementById('page-input-wrap').style.display = 'none';
            vidInit(data.serveUrl);
        } else {
            document.getElementById('page-auto-badge').style.display = 'none';
            document.getElementById('viewer-frame').src = data.viewerUrl;
        }

        renderComments(data.comments);
        loadAnnotations();
        document.getElementById('ann-toolbar').style.display = 'flex';

        // 임베드 모드 → 부모(비교 모달)에 준비 완료 통보
        if (IS_EMBED) {
            _postEmbedToParent({ type: 'sw-cmp-ready', ptype: _annPreviewType, page: 1 });
        }
    })
    .catch(() => { clearTimeout(_convTimer); alert(FM_STR.load_failed); closePreview(); });
}

function resetViewers() {
    vidTeardown();
    document.getElementById('viewer-frame').style.display = 'none';
    document.getElementById('viewer-frame').src = '';
    document.getElementById('viewer-img-wrap').style.display = 'none';
    document.getElementById('viewer-img').src = '';
    document.getElementById('viewer-img').style.width     = '';
    document.getElementById('viewer-img').style.height    = '';
    document.getElementById('viewer-img').style.maxWidth  = '';
    document.getElementById('viewer-img').style.maxHeight = '';
    document.getElementById('viewer-pdf').style.display = 'none';
    _imgNatW = 0; _imgNatH = 0; imgScale = 1.0;
    const _imgLabel = document.getElementById('img-zoom-label');
    if (_imgLabel) _imgLabel.textContent = FM_STR.zoom_fit;
    document.getElementById('viewer-loading').style.display = 'flex';
    document.getElementById('comment-list').innerHTML =
        `<div id="comment-empty" style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${FM_STR.loading}</div>`;
    document.getElementById('comment-count').textContent = '';
    document.getElementById('comment-input').value = '';
    document.getElementById('comment-page').value  = '';
    document.getElementById('modal-filename').textContent = '';
    document.getElementById('page-auto-badge').style.display = 'none';
    pdfDoc = null; pdfPage = 1; pdfTotal = 0; pdfSheetNames = [];

    // ann / comment reset
    _commentsList      = [];
    _commentShowAll    = false;
    _commentApiBase    = null;
    _annotationApiBase = null;
    _currentServeUrl   = null;
    _currentFileName   = null;
    const _annDlBtn = document.getElementById('ann-dl-btn');
    if (_annDlBtn) _annDlBtn.style.display = 'none';
    const _cmpBtn = document.getElementById('modal-compare-btn');
    if (_cmpBtn) _cmpBtn.style.display = 'none';
    annTool = null; annSelected = null; annList = [];
    annDrawing = false; annMoveActive = false;
    if (annDragEl) { annDragEl.remove(); annDragEl = null; }
    _annPreviewType = null; _annTextPct = null; _annEditId = null;
    const _annSvg = document.getElementById('ann-svg');
    _annSvg.querySelectorAll('.ann-item, #ann-sel-overlay').forEach(el => el.remove());
    // SVG 위치 초기화 (PDF 모드에서 변경된 경우)
    _annSvg.style.left = '0'; _annSvg.style.top = '0';
    _annSvg.style.right = '0'; _annSvg.style.bottom = '0';
    _annSvg.style.width = '100%'; _annSvg.style.height = '100%';
    _annSvg.style.pointerEvents = 'none';
    _annSvg.style.cursor = 'default';
    document.querySelectorAll('.ann-tool-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('ann-toolbar').style.display = 'none';
    document.getElementById('ann-text-popup').style.display = 'none';
}

function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('viewer-frame').src = '';
    clearInterval(_pdfPollTimer);
    pdfDoc = null;
    currentFileId = null;
    _maybeUnsubscribeFileComments();
}

// 파일 리뷰 팝업: 전체창 보기 토글
function togglePreviewFullscreen() {
    const m = document.getElementById('preview-modal');
    if (!m) return;
    m.classList.toggle('is-fullscreen');
}
function toggleUrlPreviewFullscreen() {
    const m = document.getElementById('url-viewer-modal');
    if (!m) return;
    m.classList.toggle('is-fullscreen');
}

// ── 의견 패널 가로 사이즈 조절 (드래그 + localStorage 기억) ──
(function() {
    const MIN_W = 240, MAX_W_RATIO = 0.75;
    const STORE_KEY = 'cmt-panel-width';
    const saved = parseInt(localStorage.getItem(STORE_KEY) || '0', 10);

    // 초기 폭 복원
    function applyStoredWidth() {
        if (!saved || saved < MIN_W) return;
        ['comment-panel', 'url-comment-panel'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.width = saved + 'px';
        });
    }
    applyStoredWidth();

    function startDrag(handle, e) {
        const targetId = handle.dataset.target;
        const panel    = document.getElementById(targetId);
        if (!panel) return;
        e.preventDefault();
        handle.classList.add('is-dragging');
        document.body.classList.add('cmt-resizing');

        const startX     = e.clientX;
        const startWidth = panel.getBoundingClientRect().width;

        function onMove(ev) {
            // 의견 패널은 우측 — 마우스가 왼쪽으로 갈수록 폭이 넓어짐
            const delta = startX - ev.clientX;
            const maxW  = Math.floor(window.innerWidth * MAX_W_RATIO);
            let next    = Math.max(MIN_W, Math.min(maxW, startWidth + delta));
            panel.style.width = next + 'px';
        }
        function onUp() {
            handle.classList.remove('is-dragging');
            document.body.classList.remove('cmt-resizing');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup',   onUp);
            // 저장
            const w = Math.round(panel.getBoundingClientRect().width);
            localStorage.setItem(STORE_KEY, String(w));
            // 두 패널 모두 동일 폭으로 동기화
            ['comment-panel', 'url-comment-panel'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el !== panel) el.style.width = w + 'px';
            });
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup',   onUp);
    }

    document.querySelectorAll('.cmt-resize-handle').forEach(h => {
        h.addEventListener('mousedown', e => startDrag(h, e));
        // 더블 클릭 → 기본 폭(340)으로 리셋
        h.addEventListener('dblclick', () => {
            ['comment-panel', 'url-comment-panel'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.width = '340px';
            });
            localStorage.removeItem(STORE_KEY);
        });
    });
})();
// ESC 로 전체창 해제 (모달 닫기는 별도 처리)
// 임베드 모드는 항상 전체창이어야 하므로 해제하지 않는다.
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !IS_EMBED) {
        document.getElementById('preview-modal')?.classList.remove('is-fullscreen');
        document.getElementById('url-viewer-modal')?.classList.remove('is-fullscreen');
    }
});

// ── PDF.js 렌더링 ─────────────────────────────────
const PDF_WORKER_URL = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

async function loadPdfJs(url) {
    if (!window.pdfjsLib) { loadPdfFallback(url); return; }

    if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
        try {
            const blob = new Blob([`importScripts('${PDF_WORKER_URL}');`], { type: 'application/javascript' });
            pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(blob);
        } catch (e) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_WORKER_URL;
        }
    }

    try {
        document.getElementById('loading-label').textContent = FM_STR.pdf_loading;

        const resp = await fetch(url, { credentials: 'same-origin' });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const buffer = await resp.arrayBuffer();

        pdfDoc   = await pdfjsLib.getDocument({ data: buffer }).promise;
        pdfTotal = pdfDoc.numPages;
        pdfPage  = 1;

        pdfScale = 1.0;

        document.getElementById('viewer-loading').style.display = 'none';
        document.getElementById('viewer-pdf').style.display = 'block';
        await renderPdfPage(1);

    } catch (e) {
        console.warn('[PDF.js 실패]', e);
        loadPdfFallback(url);
    }
}

// ── 폴백: 브라우저 내장 PDF 뷰어 + 해시 폴링 ────────
let _pdfPollTimer = null;

function loadPdfFallback(url) {
    document.getElementById('page-auto-badge').style.display = 'none';
    document.getElementById('viewer-pdf').style.display = 'none';

    const frame = document.getElementById('viewer-frame');
    frame.onload = () => {
        document.getElementById('viewer-loading').style.display = 'none';
        frame.style.display = 'block';
        _startHashPoll(frame);
    };
    frame.src = url;
}

function _startHashPoll(frame) {
    clearInterval(_pdfPollTimer);
    _pdfPollTimer = setInterval(() => {
        try {
            const hash  = frame.contentWindow.location.hash;
            const match = hash.match(/[#&]page=(\d+)/i);
            if (match) {
                const p = parseInt(match[1]);
                const input = document.getElementById('comment-page');
                if (input.value != p) {
                    input.value = p;
                    document.getElementById('page-auto-badge').style.display = 'inline';
                }
            }
        } catch (_) {}
    }, 600);
}

async function renderPdfPage(num) {
    if (!pdfDoc) return;
    if (pdfRendering) { pdfPending = num; return; }

    pdfRendering = true;
    try {
        const page     = await pdfDoc.getPage(num);
        const ratio    = window.devicePixelRatio || 1;
        const viewport = page.getViewport({ scale: pdfScale * ratio });
        const canvas   = document.getElementById('pdf-canvas');
        const ctx      = canvas.getContext('2d');

        canvas.width        = viewport.width;
        canvas.height       = viewport.height;
        canvas.style.width  = (viewport.width  / ratio) + 'px';
        canvas.style.height = (viewport.height / ratio) + 'px';

        await page.render({ canvasContext: ctx, viewport }).promise;

        pdfPage = num;
        if (IS_EMBED) _postEmbedToParent({ type: 'sw-cmp-page', page: num, total: pdfTotal });
        _updateSvgPosition();  // SVG를 새 캔버스 크기에 맞게 재배치 후 도형 렌더
        renderAnnotations();
        renderAllComments();   // 의견 패널을 현재 페이지 기준으로 갱신
        document.getElementById('comment-page').value = num;
        const sheetLabel = (pdfSheetNames.length >= num && pdfSheetNames[num - 1])
            ? pdfSheetNames[num - 1] + ' (' + num + '/' + pdfTotal + ')'
            : FM_STR.page_of.replace(':page', num).replace(':total', pdfTotal);
        document.getElementById('pdf-page-info').textContent = sheetLabel;
        document.getElementById('pdf-zoom-label').textContent = Math.round(pdfScale * 100) + '%';
        document.getElementById('pdf-prev-btn').disabled = (num <= 1);
        document.getElementById('pdf-next-btn').disabled = (num >= pdfTotal);
        // 페이지 번호 입력란 동기화 (포커스 상태에선 사용자가 입력 중이므로 갱신 X)
        const pdfPgInp = document.getElementById('pdf-page-input');
        if (pdfPgInp && document.activeElement !== pdfPgInp) {
            pdfPgInp.value = num;
            pdfPgInp.max   = pdfTotal;
        }
    } finally {
        pdfRendering = false;
        if (pdfPending !== null) {
            const p = pdfPending; pdfPending = null;
            renderPdfPage(p);
        }
    }
}

function pdfPrevPage()  { if (pdfPage > 1) renderPdfPage(pdfPage - 1); }
function pdfNextPage()  { if (pdfPage < pdfTotal) renderPdfPage(pdfPage + 1); }
function pdfFirstPage() { if (pdfPage !== 1) renderPdfPage(1); }
function gotoPage(n) {
    n = parseInt(n, 10);
    if (!n || n < 1) return;
    if (typeof pdfDoc !== 'undefined' && pdfDoc) {
        if (n > pdfTotal) n = pdfTotal;
        if (n !== pdfPage) renderPdfPage(n);
    }
}

// 페이지 번호 입력란 → 이동
function pdfGoToInput() {
    const inp = document.getElementById('pdf-page-input');
    if (!inp) return;
    let n = parseInt(inp.value, 10);
    if (!n || n < 1) { inp.value = pdfPage; return; }
    if (typeof pdfTotal !== 'undefined' && n > pdfTotal) n = pdfTotal;
    inp.value = n;
    gotoPage(n);
}

function pdfZoom(delta) {
    pdfScale = Math.min(3, Math.max(0.5, pdfScale + delta));
    renderPdfPage(pdfPage);
}

// ── 마우스 휠 확대/축소 (Ctrl+휠) ────────────────
document.getElementById('pdf-canvas-wrap').addEventListener('wheel', e => {
    if (!pdfDoc || !e.ctrlKey) return;
    e.preventDefault();
    pdfZoom(e.deltaY < 0 ? 0.15 : -0.15);
}, { passive: false });

// ── 이미지 확대/축소 ──────────────────────────────
let imgScale = 1.0;
let _imgNatW = 0;
let _imgNatH = 0;
let _imgFitMode = true; // true = 화면 맞춤 모드

function imgZoom(delta) {
    const img  = document.getElementById('viewer-img');
    const wrap = document.getElementById('img-scroll-wrap');
    if (!img) return;

    if (_imgFitMode && _imgNatW) {
        // 맞춤 모드에서 첫 줌: 현재 표시 크기를 기준으로 scale 계산
        const wW = wrap.clientWidth  - 40;
        const wH = wrap.clientHeight - 40;
        imgScale = Math.min(wW / _imgNatW, wH / _imgNatH, 1);
        _imgFitMode = false;
    }
    imgScale = Math.min(8, Math.max(0.1, imgScale + delta));
    _applyImgZoom();
}

function imgZoomFit(silent) {
    const img  = document.getElementById('viewer-img');
    const wrap = document.getElementById('img-scroll-wrap');
    if (!img) return;
    _imgFitMode = true;
    if (_imgNatW && _imgNatH && wrap) {
        const avW = Math.max(1, wrap.clientWidth  - 40);
        const avH = Math.max(1, wrap.clientHeight - 40);
        const s   = Math.min(avW / _imgNatW, avH / _imgNatH, 1);
        imgScale = s;
        img.style.width     = Math.round(_imgNatW * s) + 'px';
        img.style.height    = Math.round(_imgNatH * s) + 'px';
        img.style.maxWidth  = 'none';
        img.style.maxHeight = 'none';
        const label = document.getElementById('img-zoom-label');
        if (label) label.textContent = Math.round(s * 100) + '%';
    } else {
        imgScale = 1.0;
        img.style.width = img.style.height = img.style.maxWidth = img.style.maxHeight = '';
        const label = document.getElementById('img-zoom-label');
        if (label) label.textContent = FM_STR.zoom_fit;
    }
    if (wrap) { wrap.scrollLeft = 0; wrap.scrollTop = 0; wrap.style.cursor = 'grab'; }
    if (!silent) requestAnimationFrame(_updateSvgPosition);
}

function imgZoomOriginal() {
    if (!_imgNatW) return;
    _imgFitMode = false;
    imgScale    = 1.0;
    _applyImgZoom();
}

function _applyImgZoom() {
    const img   = document.getElementById('viewer-img');
    const wrap  = document.getElementById('img-scroll-wrap');
    const label = document.getElementById('img-zoom-label');
    if (!img || !_imgNatW) return;

    const w = Math.round(_imgNatW * imgScale);
    const h = Math.round(_imgNatH * imgScale);
    img.style.width     = w + 'px';
    img.style.height    = h + 'px';
    img.style.maxWidth  = 'none';
    img.style.maxHeight = 'none';

    if (label) label.textContent = Math.round(imgScale * 100) + '%';
    if (wrap)  wrap.style.cursor = 'grab';
    requestAnimationFrame(_updateSvgPosition);
}

// 이미지 Ctrl+휠 확대/축소
document.getElementById('img-scroll-wrap').addEventListener('wheel', e => {
    if (_annPreviewType !== 'image' || !e.ctrlKey) return;
    e.preventDefault();
    imgZoom(e.deltaY < 0 ? 0.15 : -0.15);
}, { passive: false });

// ── 이미지 드래그 팬 ──────────────────────────────
(function() {
    let dragging = false, startX, startY, scrollLeft, scrollTop;
    const wrap = () => document.getElementById('img-scroll-wrap');

    document.addEventListener('mousedown', e => {
        const w = wrap();
        if (_annPreviewType !== 'image' || !w || !w.contains(e.target)) return;
        dragging = true;
        startX     = e.pageX - w.offsetLeft;
        startY     = e.pageY - w.offsetTop;
        scrollLeft = w.scrollLeft;
        scrollTop  = w.scrollTop;
        w.style.cursor = 'grabbing';
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const w = wrap();
        if (!w) return;
        w.scrollLeft = scrollLeft - (e.pageX - w.offsetLeft - startX);
        w.scrollTop  = scrollTop  - (e.pageY - w.offsetTop  - startY);
    });

    document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        const w = wrap();
        if (w) w.style.cursor = 'grab';
    });
})();

// ── PDF 드래그 팬 ─────────────────────────────────
(function() {
    let dragging = false, startX, startY, scrollLeft, scrollTop;
    const wrap = () => document.getElementById('pdf-canvas-wrap');

    document.addEventListener('mousedown', e => {
        const w = wrap();
        if (!pdfDoc || !w || !w.contains(e.target)) return;
        dragging = true;
        startX = e.pageX - w.offsetLeft;
        startY = e.pageY - w.offsetTop;
        scrollLeft = w.scrollLeft;
        scrollTop  = w.scrollTop;
        w.style.cursor = 'grabbing';
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const w = wrap();
        if (!w) return;
        w.scrollLeft = scrollLeft - (e.pageX - w.offsetLeft - startX);
        w.scrollTop  = scrollTop  - (e.pageY - w.offsetTop  - startY);
    });

    document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        const w = wrap();
        if (w) w.style.cursor = 'grab';
    });
})();

// ── 키보드 단축키 ─────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const cmpM = document.getElementById('compare-modal');
        if (cmpM && cmpM.style.display === 'flex') { closeCompare(); return; }
        if (annSelected) { selectAnnotation(null); return; }
        if (annTool)     { setAnnTool(annTool); return; } // toggle off
        if (!IS_EMBED) { closePreview(); closeComments(); }
    }
    if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;
    if (!pdfDoc) return;
    if (e.key === 'ArrowLeft')  pdfPrevPage();
    if (e.key === 'ArrowRight') pdfNextPage();
});

// ── 페이지 번호 수동 조절 ─────────────────────────
function adjustPage(delta) {
    const el = document.getElementById('comment-page');
    el.value = Math.max(1, (parseInt(el.value) || 0) + delta);
}

// ── 의견 렌더링 ───────────────────────────────────
function renderComments(comments) {
    _commentsList = [...comments].reverse();
    renderAllComments();
}

// 일반의견 + 화면내용의견 통합 렌더링 (페이지 필터 포함)
function renderAllComments() {
    const list  = document.getElementById('comment-list');
    const label = document.getElementById('page-label').textContent;
    const isPdf = _annPreviewType === 'pdf';

    // ── 필터 바 업데이트 ───────────────────────────
    const filterBar = document.getElementById('cmt-filter-bar');
    const filterLbl = document.getElementById('cmt-filter-label');
    const filterBtn = document.getElementById('cmt-filter-btn');
    if (isPdf && filterBar) {
        filterBar.style.display = 'flex';
        if (_commentShowAll) {
            filterLbl.textContent = FM_STR.filter_all_pages;
            filterBtn.textContent = FM_STR.filter_current_page;
        } else {
            filterLbl.textContent = FM_STR.filter_showing_page.replace(':page', pdfPage);
            filterBtn.textContent = FM_STR.filter_view_all;
        }
    } else if (filterBar) {
        filterBar.style.display = 'none';
    }

    // ── 필터 함수 ──────────────────────────────────
    // page=null 항목은 어느 페이지에서나 표시
    const visible = item => !isPdf || _commentShowAll || item.page == null || item.page === pdfPage;

    const textAnns       = annList.filter(a => a.type === 'text' && a.data?.text);
    const totalCount     = _commentsList.length + textAnns.length;
    const filteredCmts   = _commentsList.filter(visible);
    const filteredAnns   = textAnns.filter(visible);
    const shownCount     = filteredCmts.length + filteredAnns.length;

    // 헤더 카운트: 전체 수 표시 (필터 중이면 "필터/전체" 형식)
    const countEl = document.getElementById('comment-count');
    if (isPdf && !_commentShowAll && totalCount !== shownCount) {
        countEl.textContent = `${shownCount} / ${totalCount}`;
    } else {
        countEl.textContent = totalCount || '';
    }

    if (shownCount === 0) {
        const msg = isPdf && !_commentShowAll
            ? FM_STR.no_opinions_page.replace(':page', pdfPage)
            : FM_STR.no_opinions;
        list.innerHTML = `<div id="comment-empty" style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${msg}</div>`;
        return;
    }

    // 페이지 오름차순 정렬 (page=null은 맨 앞)
    const cmtItems = filteredCmts.map(c => ({ ...c, _kind: 'general', _sortPage: c.page ?? -1 }));
    const annItems = filteredAnns.map(a => ({ ...a, _kind: 'screen',  _sortPage: a.page ?? -1 }));
    const all = [...cmtItems, ...annItems].sort((a, b) =>
        a._sortPage !== b._sortPage ? a._sortPage - b._sortPage : (b.id || 0) - (a.id || 0)
    );

    list.innerHTML = all.map(item =>
        item._kind === 'screen' ? annCommentHtml(item, label) : commentHtml(item, label)
    ).join('');
}

window.toggleCommentPageFilter = function() {
    _commentShowAll = !_commentShowAll;
    renderAllComments();
};

// ── 파일 버전 바 렌더링 + 버전 전환 ──────────────────────────
let _previewDataUrlBase = null;

function renderVersionBar(versions, activeVersion) {
    const bar = document.getElementById('modal-version-bar');
    if (!bar) return;
    if (!versions || versions.length <= 1) {
        if (versions && versions.length === 1 && versions[0].version === 1) {
            // 단일 버전이면 v 표시 생략
            bar.innerHTML = '';
            return;
        }
        bar.innerHTML = '';
        return;
    }
    bar.innerHTML = versions.map(v => {
        const active = (v.version === activeVersion);
        const tooltip = `${v.name || ''}${v.uploader?.name ? ' · ' + v.uploader.name : ''}${v.created_at ? ' · ' + v.created_at : ''}`;
        const baseStyle = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:24px;padding:0 8px;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer;transition:all .12s;border:none;';
        const style = active
            ? baseStyle + 'background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;box-shadow:0 1px 6px rgba(124,58,237,.4);'
            : baseStyle + 'background:rgba(255,255,255,.07);color:#c4b5fd;border:1px solid rgba(196,181,253,.25);';
        return `<button type="button" onclick="switchToVersion(${v.version})" title="${escHtml(tooltip)}" style="${style}">v${v.version}</button>`;
    }).join('');
}

function switchToVersion(version) {
    if (!currentProjectId || !currentFileId) return;
    const baseUrl = _previewDataUrlBase || `${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}/preview-data`;
    const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'version=' + version;
    openPreview(currentFileId, currentProjectId, url);
}

// ══════════════════════════════════════════════════════════
//  버전 비교 (Version Comparison)
// ══════════════════════════════════════════════════════════
let _cmpVersionList = [];   // previewData.versions (현재 파일의 전체 버전 목록)
function _cmpFresh() {
    return { readyA:false, readyB:false, ptypeA:null, ptypeB:null, pageA:1, pageB:1, vidA:null, vidB:null };
}
let _cmpState    = _cmpFresh();
let _cmpSyncMode = null;   // 'page' | 'video' | null
let _cmpVidApplying = false;

// 부모(비교 모달)로 메시지 전송 — 임베드 뷰어 전용
function _postEmbedToParent(msg) {
    if (!IS_EMBED || window.parent === window) return;
    try { window.parent.postMessage(msg, location.origin); } catch (_) {}
}

// "버전 비교" 버튼 표시 여부 갱신 (openPreview 에서 호출)
function refreshCompareButton(versions) {
    _cmpVersionList = Array.isArray(versions) ? versions : [];
    const btn = document.getElementById('modal-compare-btn');
    if (!btn) return;
    // 숨김 조건: 임베드 모드 · SR(유지보수) 파일 · 버전 2개 미만
    const show = !IS_EMBED
              && !_commentApiBase
              && _cmpVersionList.length >= 2;
    btn.style.display = show ? 'inline-flex' : 'none';
}

// ── 버전 선택 팝오버 ─────────────────────────────
function openVersionCompareMenu(ev) {
    if (ev) ev.stopPropagation();
    closeVersionCompareMenu();
    const btn = document.getElementById('modal-compare-btn');
    if (!btn || !_cmpVersionList.length) return;

    const activeVersion = window._currentVersion || null;
    const rows = [..._cmpVersionList].reverse().map(v => {
        const sub = (v.uploader?.name || '—') + (v.created_at ? ' · ' + v.created_at : '') + (v.is_current ? ' · ' + FM_STR.cmp_current : '');
        const pre = (v.version === activeVersion) ? ' checked' : '';
        return `<label class="vcmp-row${pre ? ' is-checked' : ''}" data-version="${v.version}">
            <input type="checkbox" value="${v.version}" onchange="onVersionCompareCheck()"${pre}>
            <span class="vcmp-vtag">v${v.version}</span>
            <span class="vcmp-meta">
                <span class="vcmp-name">${escHtml(v.name || '')}</span>
                <span class="vcmp-sub">${escHtml(sub)}</span>
            </span>
        </label>`;
    }).join('');

    const pop = document.createElement('div');
    pop.id = 'version-compare-pop';
    pop.style.cssText = 'position:fixed;z-index:10010;background:#241f3d;border:1px solid rgba(196,181,253,.25);border-radius:10px;box-shadow:0 16px 48px rgba(0,0,0,.55);padding:10px;width:288px;';
    pop.innerHTML = `
        <div style="font-size:12px;font-weight:700;color:#e9d5ff;margin-bottom:6px;">${FM_STR.cmp_pick_versions} <span style="font-weight:500;color:#8b85a8;">${FM_STR.cmp_pick_hint}</span></div>
        <div style="display:flex;flex-direction:column;gap:3px;max-height:280px;overflow-y:auto;">${rows}</div>
        <button id="vcmp-go" type="button" onclick="confirmVersionCompare()"
            style="margin-top:9px;width:100%;padding:8px;border:none;border-radius:7px;font-size:12px;font-weight:700;"></button>`;
    document.body.appendChild(pop);

    const r = btn.getBoundingClientRect();
    let left = r.right - 288;
    if (left < 8) left = 8;
    pop.style.top  = (r.bottom + 6) + 'px';
    pop.style.left = left + 'px';

    onVersionCompareCheck();  // 버튼 상태 초기화
    setTimeout(() => document.addEventListener('mousedown', _vcmpOutside), 0);
}

function _vcmpOutside(e) {
    const pop = document.getElementById('version-compare-pop');
    if (pop && !pop.contains(e.target) && !e.target.closest('#modal-compare-btn')) {
        closeVersionCompareMenu();
    }
}

function closeVersionCompareMenu() {
    const pop = document.getElementById('version-compare-pop');
    if (pop) pop.remove();
    document.removeEventListener('mousedown', _vcmpOutside);
}

// 체크박스 변경 — 2개 초과 선택 방지 + 확인 버튼 갱신
function onVersionCompareCheck() {
    const pop = document.getElementById('version-compare-pop');
    if (!pop) return;
    const boxes   = [...pop.querySelectorAll('input[type=checkbox]')];
    const checked = boxes.filter(b => b.checked);

    boxes.forEach(b => { b.disabled = !b.checked && checked.length >= 2; });
    pop.querySelectorAll('.vcmp-row').forEach(row => {
        const cb = row.querySelector('input');
        row.classList.toggle('is-checked', cb.checked);
        row.classList.toggle('is-disabled', cb.disabled);
    });

    const go = document.getElementById('vcmp-go');
    if (checked.length === 0) {
        go.disabled = true;
        go.textContent = FM_STR.cmp_select_prompt;
        go.style.background = '#3a3357'; go.style.color = '#8b85a8'; go.style.cursor = 'not-allowed';
    } else if (checked.length === 1) {
        go.disabled = false;
        go.textContent = FM_STR.cmp_view_one.replace(':version', checked[0].value);
        go.style.background = 'linear-gradient(135deg,#7c3aed,#a78bfa)'; go.style.color = '#fff'; go.style.cursor = 'pointer';
    } else {
        const sorted = checked.map(b => parseInt(b.value, 10)).sort((a, b) => a - b);
        go.disabled = false;
        go.textContent = FM_STR.cmp_compare_two.replace(':a', sorted[0]).replace(':b', sorted[1]);
        go.style.background = 'linear-gradient(135deg,#0ea5e9,#7dd3fc)'; go.style.color = '#08233a'; go.style.cursor = 'pointer';
    }
}

function confirmVersionCompare() {
    const pop = document.getElementById('version-compare-pop');
    if (!pop) return;
    const checked = [...pop.querySelectorAll('input[type=checkbox]:checked')]
        .map(b => parseInt(b.value, 10)).sort((a, b) => a - b);
    closeVersionCompareMenu();
    if (checked.length === 1) {
        switchToVersion(checked[0]);          // 1개 선택 → 단일 버전 보기 (기존 동작과 동일)
    } else if (checked.length === 2) {
        openCompare(checked[0], checked[1]);  // 2개 선택 → 나란히 비교
    }
}

// ── 비교 모달 ────────────────────────────────────
function _compareLabelHtml(v) {
    if (!v) return '';
    const sub = (v.uploader?.name || '—')
              + (v.created_at ? ' · ' + v.created_at : '')
              + (v.is_current ? ' · ' + FM_STR.cmp_current_version : '');
    return `<span style="background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;padding:2px 9px;border-radius:5px;font-size:11px;flex-shrink:0;">v${v.version}</span>
            <span style="color:#e5e7eb;overflow:hidden;text-overflow:ellipsis;">${escHtml(v.name || '')}</span>
            <span style="color:#8b85a8;font-weight:500;flex-shrink:0;">${escHtml(sub)}</span>`;
}

function openCompare(vA, vB) {
    if (!currentProjectId || !currentFileId) return;
    const embedBase = `${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}/viewer-embed`;
    const va = _cmpVersionList.find(v => v.version === vA);
    const vb = _cmpVersionList.find(v => v.version === vB);

    _cmpState    = _cmpFresh();
    _cmpSyncMode = null;

    document.getElementById('compare-label-a').innerHTML = _compareLabelHtml(va);
    document.getElementById('compare-label-b').innerHTML = _compareLabelHtml(vb);
    document.getElementById('compare-title').textContent = _currentFileName || '';

    const sync = document.getElementById('compare-sync-pages');
    sync.checked  = false;
    sync.disabled = true;
    document.getElementById('compare-sync-wrap').style.opacity = '.5';
    document.getElementById('compare-sync-note').style.display = 'none';

    document.getElementById('compare-frame-a').src = `${embedBase}?embed=1&version=${vA}`;
    document.getElementById('compare-frame-b').src = `${embedBase}?embed=1&version=${vB}`;

    document.getElementById('compare-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCompare() {
    document.getElementById('compare-modal').style.display = 'none';
    document.getElementById('compare-frame-a').src = 'about:blank';
    document.getElementById('compare-frame-b').src = 'about:blank';
    _cmpState    = _cmpFresh();
    _cmpSyncMode = null;
    // 비교 모달 아래의 미리보기 모달은 그대로 유지된다.
}

// 동기화 사용 가능 여부 갱신
//  · 양쪽 PDF   → 'page'  모드 (페이지 동일함)
//  · 양쪽 동영상 → 'video' 모드 (재생 위치 동일함)
//  · 그 외(혼합·이미지) → 비활성
function _compareUpdateSyncAvailability() {
    if (!_cmpState.readyA || !_cmpState.readyB) return;
    const sync  = document.getElementById('compare-sync-pages');
    const wrap  = document.getElementById('compare-sync-wrap');
    const note  = document.getElementById('compare-sync-note');
    const label = document.getElementById('compare-sync-label');

    const bothPdf   = _cmpState.ptypeA === 'pdf'   && _cmpState.ptypeB === 'pdf';
    const bothVideo = _cmpState.ptypeA === 'video' && _cmpState.ptypeB === 'video';
    _cmpSyncMode = bothPdf ? 'page' : (bothVideo ? 'video' : null);

    if (label) label.textContent = bothVideo ? FM_STR.sync_same_video : FM_STR.sync_same_page;

    const enabled = !!_cmpSyncMode;
    sync.disabled = !enabled;
    wrap.style.opacity = enabled ? '1' : '.5';
    if (!enabled) {
        sync.checked = false;
        note.textContent = FM_STR.sync_same_format_only;
        note.style.display = 'inline';
    } else {
        note.style.display = 'none';
    }
}

// 동기화 토글 — 켜는 즉시 좌측 상태로 우측을 맞춘다.
function onCompareSyncToggle() {
    const sync = document.getElementById('compare-sync-pages');
    if (!sync.checked || sync.disabled) return;
    if (_cmpSyncMode === 'page') {
        _compareSendGoto('b', _cmpState.pageA);
        _cmpState.pageB = _cmpState.pageA;
    } else if (_cmpSyncMode === 'video' && _cmpState.vidA) {
        _compareSendVid('b', _cmpState.vidA.action, _cmpState.vidA.time);
    }
}

// 동영상 재생 명령을 반대편 임베드로 전달
function _compareSendVid(which, action, time) {
    const fr = document.getElementById(which === 'a' ? 'compare-frame-a' : 'compare-frame-b');
    if (fr && fr.contentWindow) {
        fr.contentWindow.postMessage({ type: 'sw-cmp-vid-apply', action: action, time: time }, location.origin);
    }
}

function _compareSendGoto(which, page) {
    const fr = document.getElementById(which === 'a' ? 'compare-frame-a' : 'compare-frame-b');
    if (fr && fr.contentWindow) {
        fr.contentWindow.postMessage({ type: 'sw-cmp-goto', page: page }, location.origin);
    }
}

// ── 임베드 동영상 동기화 ──────────────────────────
// 임베드 뷰어에서 재생/일시정지/탐색이 일어나면 부모로 통보
function _cmpBindEmbedVideo(vid) {
    if (!IS_EMBED || !vid || vid._cmpBound) return;
    vid._cmpBound = true;
    const post = (action) => {
        if (_cmpVidApplying) return;
        _postEmbedToParent({ type: 'sw-cmp-vid', action: action, time: vid.currentTime });
    };
    vid.addEventListener('play',   () => post('play'));
    vid.addEventListener('pause',  () => post('pause'));
    vid.addEventListener('seeked', () => post('seek'));
}
// 부모로부터 받은 재생 명령 적용 (역전파 방지 플래그 사용)
function _cmpApplyVideo(action, time) {
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    _cmpVidApplying = true;
    if (typeof time === 'number' && isFinite(time) && Math.abs(vid.currentTime - time) > 0.3) {
        vid.currentTime = time;
    }
    if (action === 'play')  vid.play().catch(() => {});
    if (action === 'pause') vid.pause();
    clearTimeout(_cmpApplyVideo._t);
    _cmpApplyVideo._t = setTimeout(() => { _cmpVidApplying = false; }, 500);
}

// 의견·답글을 원본 파일 형식 또는 PDF 보고서로 다운로드 (현재 선택된 버전 기준)
function downloadCommentsReport() {
    if (!currentProjectId || !currentFileId) return;
    const v = window._currentVersion || 1;
    const url = `${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}/comments/download?version=${v}`;
    // 새 탭으로 열어 진행 토스트가 사라지지 않게
    const a = document.createElement('a');
    a.href = url;
    a.rel  = 'noopener';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
window.downloadCommentsReport = downloadCommentsReport;

// ── 파일 수정본 업로드 (버전 관리) ──────────────────────────
function openUploadVersionModal() {
    if (!currentProjectId || !currentFileId) { alert(FM_STR.file_info_missing); return; }
    document.getElementById('upv-file').value = '';
    document.getElementById('upv-note').value = '';
    if (typeof _updateUpvFileUi === 'function') _updateUpvFileUi(null);
    const btn = document.getElementById('upv-submit-btn');
    btn.disabled = false; btn.textContent = FM_STR.upload;
    document.getElementById('upv-modal').style.display = 'flex';
}
function closeUploadVersionModal() {
    document.getElementById('upv-modal').style.display = 'none';
}
document.addEventListener('change', function(e) {
    if (e.target?.id === 'upv-file') {
        _updateUpvFileUi(e.target.files?.[0] || null);
    }
});

function _updateUpvFileUi(f) {
    const drop = document.getElementById('upv-file-drop');
    const cta  = document.getElementById('upv-file-drop-cta');
    const fn   = document.getElementById('upv-filename');
    if (!drop || !cta || !fn) return;
    if (f) {
        drop.classList.add('has-file');
        cta.textContent = '✓ ' + FM_STR.file_selected;
        fn.textContent  = `${f.name} (${(f.size/1024).toFixed(0)} KB)`;
    } else {
        drop.classList.remove('has-file');
        cta.textContent = FM_STR.choose_file;
        fn.textContent  = '';
    }
}

/* 드래그앤드롭 — 한 번만 등록 */
(function setupUpvDnd() {
    const drop = document.getElementById('upv-file-drop');
    if (!drop || drop._dndBound) return;
    drop._dndBound = true;

    ['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, e => {
        e.preventDefault(); e.stopPropagation();
        drop.classList.add('is-dragover');
    }));
    ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => {
        e.preventDefault(); e.stopPropagation();
        drop.classList.remove('is-dragover');
    }));
    drop.addEventListener('drop', e => {
        const f = e.dataTransfer?.files?.[0];
        if (!f) return;
        const inp = document.getElementById('upv-file');
        if (!inp) return;
        try {
            const dt = new DataTransfer();
            dt.items.add(f);
            inp.files = dt.files;
            _updateUpvFileUi(f);
        } catch (_) {
            // DataTransfer 미지원 브라우저: 파일 표시만 갱신 (제출 시엔 필요 시 사용자 다시 선택 안내)
            _updateUpvFileUi(f);
        }
    });
})();
async function submitUploadVersion() {
    const fileInput = document.getElementById('upv-file');
    const note      = document.getElementById('upv-note').value.trim();
    if (!fileInput.files?.[0]) { alert(FM_STR.select_file_alert); return; }
    const ok = (typeof window.__confirm === 'function')
        ? await window.__confirm(FM_STR.upload_confirm)
        : confirm(FM_STR.upload_confirm);
    if (!ok) return;
    const btn = document.getElementById('upv-submit-btn');
    btn.disabled = true; btn.textContent = FM_STR.uploading;

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    if (note) fd.append('change_note', note);

    fetch(`${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}/upload-version`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
        body: fd,
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { alert(d.message || FM_STR.upload_failed); btn.disabled = false; btn.textContent = FM_STR.upload; return; }
        closeUploadVersionModal();
        alert(d.message || FM_STR.upload_done);
        // 미리보기/의견 갱신을 위해 페이지 reload (간단)
        location.reload();
    })
    .catch(() => { alert(FM_STR.network_error); btn.disabled = false; btn.textContent = FM_STR.upload; });
}

function openVersionHistory() {
    if (!currentProjectId || !currentFileId) return;
    document.getElementById('vh-list').innerHTML = `<div style="padding:24px;text-align:center;color:#9ca3af;">${FM_STR.loading_ellipsis}</div>`;
    document.getElementById('vh-modal').style.display = 'flex';
    fetch(`${BASE_URL}/projects/${currentProjectId}/files/${currentFileId}/versions`, {
        headers:{'Accept':'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok || !d.versions?.length) {
            document.getElementById('vh-list').innerHTML = `<div style="padding:24px;text-align:center;color:#9ca3af;">${FM_STR.no_version_history}</div>`;
            return;
        }
        document.getElementById('vh-list').innerHTML = d.versions.map(v => `
            <div style="padding:12px 0;border-bottom:1px solid #f3f4f6;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="font-size:12px;font-weight:700;padding:3px 9px;border-radius:5px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;">v${v.version}</span>
                    <span style="font-size:12px;color:#374151;font-weight:600;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(v.original_name)}</span>
                    <span style="font-size:11px;color:#9ca3af;">${v.size_human || ''}</span>
                </div>
                <div style="font-size:11px;color:#6b7280;margin-bottom:2px;">${v.uploader?.name || '—'} · ${v.created_at || ''}</div>
                ${v.change_note ? `<div style="font-size:12px;color:#374151;background:#fafafa;border-left:3px solid #c4b5fd;padding:6px 10px;margin-top:4px;white-space:pre-wrap;">${escHtml(v.change_note)}</div>` : ''}
            </div>
        `).join('');
    })
    .catch(() => {
        document.getElementById('vh-list').innerHTML = `<div style="padding:24px;text-align:center;color:#ef4444;">${FM_STR.load_failed_short}</div>`;
    });
}
function closeVersionHistory() {
    document.getElementById('vh-modal').style.display = 'none';
}

// ── 의견 → 논의사항 변환 ──────────────────────────
let _cnvPendingCommentId = null;

function convertBtnHtml(c) {
    if (!c || c.parent_id) return '';
    if (c.discussion_id) {
        return `<button onclick="cnvOpenDetail(${c.discussion_id})" title="${FM_STR.view_discussion}"
                    style="display:inline-flex;align-items:center;gap:3px;font-size:11px;color:#15803d;background:#dcfce7;border:1px solid #bbf7d0;border-radius:5px;padding:2px 7px;cursor:pointer;font-weight:600;"
                    onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/></svg>
                    ${FM_STR.discussion}
                </button>`;
    }
    return `<button onclick="convertCommentToDiscussion(${c.id})" title="${FM_STR.register_discussion}"
                style="display:inline-flex;align-items:center;gap:3px;font-size:11px;color:#5b21b6;background:#ede9fe;border:1px solid #ddd6fe;border-radius:5px;padding:2px 7px;cursor:pointer;font-weight:600;"
                onmouseover="this.style.background='#ddd6fe'" onmouseout="this.style.background='#ede9fe'">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ${FM_STR.discussion}
            </button>`;
}

function _cnvFindComment(commentId) {
    const lists = [
        (typeof _commentsList !== 'undefined') ? _commentsList : null,
        (typeof _cmComments !== 'undefined') ? _cmComments : null,
    ];
    for (const list of lists) {
        if (!list) continue;
        const found = list.find(x => x.id === commentId);
        if (found) return found;
    }
    return null;
}

function convertCommentToDiscussion(commentId) {
    const c = _cnvFindComment(commentId) || { id: commentId, user_name: '', content: '', created_at: '' };
    _cnvPendingCommentId = commentId;
    document.getElementById('cnv-confirm-meta').textContent =
        [c.user_name, c.created_at].filter(Boolean).join(' · ');
    document.getElementById('cnv-confirm-content').textContent = c.content || '';
    const okBtn = document.getElementById('cnv-confirm-ok');
    okBtn.disabled = false; okBtn.textContent = FM_STR.register;
    document.getElementById('cnv-confirm-modal').style.display = 'flex';
}

function cnvCloseConfirm() {
    document.getElementById('cnv-confirm-modal').style.display = 'none';
    _cnvPendingCommentId = null;
}

function cnvDoConvert() {
    const commentId = _cnvPendingCommentId;
    if (!commentId) return;
    const fileId = (typeof _cmFileId !== 'undefined' && _cmFileId) ? _cmFileId : currentFileId;
    if (!fileId || !currentProjectId) { alert(FM_STR.file_info_missing); cnvCloseConfirm(); return; }

    const okBtn = document.getElementById('cnv-confirm-ok');
    okBtn.disabled = true; okBtn.textContent = FM_STR.register_doing;

    fetch(`${BASE_URL}/projects/${currentProjectId}/files/${fileId}/comments/${commentId}/convert-to-discussion`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            if (res.status === 409 && data.discussion_id) {
                _cnvUpdateCardButton(commentId, data.discussion_id);
                alert(data.message || FM_STR.already_discussion);
            } else {
                alert(data.message || FM_STR.discussion_reg_failed);
            }
            cnvCloseConfirm();
            return;
        }
        _cnvUpdateCardButton(commentId, data.discussion_id);
        cnvCloseConfirm();
    })
    .catch(() => {
        alert(FM_STR.network_error);
        okBtn.disabled = false; okBtn.textContent = FM_STR.register;
    });
}

function _cnvUpdateCardButton(commentId, discussionId) {
    // 캐시 갱신
    [(typeof _commentsList !== 'undefined') ? _commentsList : null,
     (typeof _cmComments !== 'undefined') ? _cmComments : null].forEach(list => {
        if (!list) return;
        const item = list.find(x => x.id === commentId);
        if (item) item.discussion_id = discussionId;
    });
    // DOM 버튼 교체
    ['cmt-','cm-cmt-'].forEach(prefix => {
        const card = document.getElementById(prefix + commentId);
        if (!card) return;
        const newC = { id: commentId, parent_id: null, discussion_id: discussionId };
        card.querySelectorAll('button').forEach(el => {
            const onc = el.getAttribute('onclick') || '';
            if (onc.includes(`convertCommentToDiscussion(${commentId}`)) {
                el.outerHTML = convertBtnHtml(newC);
            }
        });
    });
}

function cnvOpenDetail(discussionId) {
    if (!discussionId || !currentProjectId) return;
    const modal = document.getElementById('cnv-detail-modal');
    document.getElementById('cnv-detail-title').textContent = FM_STR.loading_text;
    document.getElementById('cnv-detail-meta').textContent = '';
    document.getElementById('cnv-detail-content').innerHTML = '';
    document.getElementById('cnv-detail-conclusion').style.display = 'none';
    const stEl = document.getElementById('cnv-detail-status');
    stEl.textContent = ''; stEl.style.background = ''; stEl.style.color = '';
    document.getElementById('cnv-detail-link').href = `${BASE_URL}/projects/${currentProjectId}/discussions?open=${discussionId}`;
    modal.style.display = 'flex';

    fetch(`${BASE_URL}/projects/${currentProjectId}/discussions/${discussionId}`, {
        headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(d => {
        document.getElementById('cnv-detail-title').textContent = d.title || FM_STR.discussion;
        const meta = [];
        if (d.author?.name) meta.push(FM_STR.meta_author + ': ' + d.author.name);
        if (d.discussion_date) meta.push(FM_STR.meta_discussion_date + ': ' + d.discussion_date);
        if (d.created_at) meta.push(FM_STR.meta_registered + ': ' + d.created_at);
        document.getElementById('cnv-detail-meta').textContent = meta.join(' · ');
        document.getElementById('cnv-detail-content').innerHTML = d.content || `<p style="color:#9ca3af;">${FM_STR.no_content}</p>`;
        if (d.status_label) {
            stEl.textContent = d.status_label;
            stEl.style.background = d.status_color?.bg || '#f3f4f6';
            stEl.style.color = d.status_color?.fg || '#6b7280';
        }
        if (d.conclusion) {
            document.getElementById('cnv-detail-conclusion').style.display = 'block';
            document.getElementById('cnv-detail-conclusion-body').innerHTML = d.conclusion;
        }
    })
    .catch(() => { document.getElementById('cnv-detail-title').textContent = FM_STR.discussion_load_failed; });
}

function cnvCloseDetail() {
    document.getElementById('cnv-detail-modal').style.display = 'none';
}

// ESC로 다이얼로그 닫기
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    const dt = document.getElementById('cnv-detail-modal');
    const cf = document.getElementById('cnv-confirm-modal');
    if (dt && dt.style.display === 'flex') { cnvCloseDetail(); e.stopPropagation(); return; }
    if (cf && cf.style.display === 'flex') { cnvCloseConfirm(); e.stopPropagation(); }
}, true);

function commentHtml(c, label) {
    label = label || document.getElementById('page-label').textContent;

    const repliesHtml = (c.replies || []).map(r => `
        <div class="reply-card" id="panel-reply-${r.id}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;">
                <div style="flex:1;min-width:0;">
                    <span style="font-size:11px;font-weight:700;color:#6d28d9;">↳ ${escHtml(r.user_name)}</span>
                    <span style="font-size:10px;color:#9ca3af;margin-left:5px;">${r.created_at}</span>
                    <p style="font-size:12px;color:#374151;margin-top:3px;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;">${escHtml(r.content)}</p>
                </div>
                ${r.can_delete ? `<button onclick="deletePanelReply(${r.id}, ${c.id}, this)" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </div>
        </div>
    `).join('');

    const vidBadge = (c.video_time != null)
        ? `<button onclick="vidSeekTo(${c.video_time})" class="page-badge" style="background:#ddd6fe;color:#5b21b6;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:3px;" title="${FM_STR.vid_seek_to.replace(':time', vidFmtTime(c.video_time))}">
                <svg width="9" height="9" viewBox="0 0 16 16" fill="currentColor"><path d="M4 3v10l8-5z"/></svg>
                ${vidFmtTime(c.video_time)}
            </button>`
        : '';

    return `<div class="comment-card" id="cmt-${c.id}">
        <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:5px;">
            <span class="page-badge" style="background:#f0fdf4;color:#065f46;">${FM_STR.badge_general}</span>
            ${c.page ? `<button type="button" onclick="event.stopPropagation();gotoPage(${c.page})" class="page-badge" title="${escHtml(label)} ${c.page}${FM_STR.goto_page_suffix}" style="background:#ede9fe;color:#6d28d9;border:none;cursor:pointer;">${escHtml(label)} ${c.page}</button>` : ''}
            ${vidBadge}
        </div>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            <span style="font-size:12px;font-weight:700;color:#1f2937;">${escHtml(c.user_name)}</span>
            <span style="font-size:11px;color:#9ca3af;">${c.created_at}</span>
            <span style="margin-left:auto;display:inline-flex;align-items:center;gap:3px;flex-shrink:0;">
                ${convertBtnHtml(c)}
                ${c.can_delete ? `<button onclick="deleteComment(${c.id}, this)" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:16px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </span>
        </div>
        <p style="font-size:13px;color:#374151;margin:0;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;line-height:1.55;">${escHtml(c.content)}</p>
        <button type="button" class="cmt-reply-cta" onclick="togglePanelReplyForm(${c.id})">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            ${FM_STR.reply}${(c.replies || []).length ? ` (${(c.replies || []).length})` : ''}
        </button>
        ${repliesHtml ? `<div class="reply-thread">${repliesHtml}</div>` : `<div class="reply-thread" id="panel-replies-${c.id}" style="display:none;"></div>`}
        <div class="reply-form" id="panel-reply-form-${c.id}" style="display:none;">
            <textarea class="reply-textarea" id="panel-reply-ta-${c.id}" rows="2" placeholder="${FM_STR.reply_placeholder}"
                      onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();submitPanelReply(${c.id});}"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:6px;">
                <button onclick="togglePanelReplyForm(${c.id})" style="padding:5px 12px;background:#f3f4f6;color:#374151;border:none;border-radius:5px;font-size:11px;cursor:pointer;">${FM_STR.cancel}</button>
                <button onclick="submitPanelReply(${c.id})" style="padding:5px 14px;background:#7c3aed;color:#fff;border:none;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer;">${FM_STR.register}</button>
            </div>
        </div>
    </div>`;
}

function annCommentHtml(ann, label) {
    label = label || document.getElementById('page-label').textContent;
    const text = ann.data?.text || '';
    return `<div class="comment-card" id="ann-cmt-${ann.id}" onclick="selectAnnotation(${ann.id})"
            style="cursor:pointer;border-left:3px solid #8b5cf6;">
        <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:5px;">
            <span class="page-badge" style="background:#ede9fe;color:#6d28d9;display:inline-flex;align-items:center;gap:3px;">
                <svg width="9" height="9" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="12" height="12" rx="1"/><path d="M5 8h6M8 5v6" stroke-linecap="round"/></svg>
                ${FM_STR.badge_screen_ann}
            </span>
            ${ann.page ? `<button type="button" onclick="event.stopPropagation();gotoPage(${ann.page})" class="page-badge" title="${escHtml(label)} ${ann.page}${FM_STR.goto_page_suffix}" style="background:#ede9fe;color:#6d28d9;border:none;cursor:pointer;">${escHtml(label)} ${ann.page}</button>` : ''}
        </div>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
            <div style="flex:1;min-width:0;">
                <span style="font-size:12px;font-weight:700;color:#1f2937;">${escHtml(ann.user_name || '')}</span>
                ${ann.created_at ? `<span style="font-size:11px;color:#9ca3af;margin-left:6px;">${ann.created_at}</span>` : ''}
                <p style="font-size:13px;color:#374151;margin-top:4px;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;">${escHtml(text)}</p>
            </div>
            ${ann.can_delete ? `<button onclick="event.stopPropagation();deleteAnnotation(${ann.id})" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:16px;line-height:1;padding:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
        </div>
    </div>`;
}

// ── 의견 등록 ─────────────────────────────────────
function submitComment() {
    const content = document.getElementById('comment-input').value.trim();
    if (!content) { document.getElementById('comment-input').focus(); return; }

    const page = document.getElementById('comment-page').value || null;
    const btn  = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = FM_STR.loading;

    // 동영상 모드일 때 현재 재생 시간 동봉 (페이지는 비움)
    let payload = { content, page: page ? parseInt(page) : null };
    if (_annPreviewType === 'video' && _vidPendingTime !== null) {
        payload = { content, video_time: _vidPendingTime };
    }

    fetch(`${_cBase()}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(payload)
    })
    .then(r => {
        if (!r.ok) return r.text().then(t => { throw new Error(`HTTP ${r.status}: ${t.substring(0,200)}`); });
        return r.json();
    })
    .then(c => {
        document.getElementById('comment-input').value = '';
        const existing = _commentsList.find(x => x.id === c.id);
        if (existing) {
            // 실시간 브로드캐스트가 먼저 도착한 경우, 누락된 필드(video_time 등) 보강
            Object.assign(existing, c);
        } else {
            _commentsList.unshift({ ...c, replies: [] });
        }
        renderAllComments();
        if (_annPreviewType === 'video') {
            vidRenderMarkers();
            _vidPendingTime = null;
            document.getElementById('comment-input').placeholder = FM_STR.comment_placeholder;
        }
        const list = document.getElementById('comment-list');
        list.scrollTop = 0;
        const total = _commentsList.reduce((s, x) => s + 1 + (x.replies?.length || 0), 0);
        updateFileRowBadge(currentFileId, total);
    })
    .catch(() => alert(FM_STR.save_failed))
    .finally(() => { btn.disabled = false; btn.textContent = FM_STR.submit; });
}

// ── 패널 답글 토글/등록/삭제 ──────────────────────
function togglePanelReplyForm(parentId) {
    const form = document.getElementById(`panel-reply-form-${parentId}`);
    if (!form) return;
    const isHidden = form.style.display === 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) document.getElementById(`panel-reply-ta-${parentId}`)?.focus();
}

function submitPanelReply(parentId) {
    const ta = document.getElementById(`panel-reply-ta-${parentId}`);
    const content = ta?.value.trim();
    if (!content) { ta?.focus(); return; }

    fetch(`${_cBase()}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ content, parent_id: parentId })
    })
    .then(r => r.json())
    .then(reply => {
        const parent = _commentsList.find(c => c.id === parentId);
        if (parent) {
            parent.replies = parent.replies || [];
            if (!parent.replies.some(r => r.id === reply.id)) parent.replies.push(reply);
        }
        if (ta) ta.value = '';
        const form = document.getElementById(`panel-reply-form-${parentId}`);
        if (form) form.style.display = 'none';
        renderAllComments();
    })
    .catch(() => alert(FM_STR.save_failed));
}

function deletePanelReply(replyId, parentId, btn) {
    showDeleteConfirmPopover(btn || document.body, FM_STR.confirm_delete_opinion, () => {
        fetch(`${_cBase()}/comments/${replyId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(r => r.json())
        .then(() => {
            const parent = _commentsList.find(c => c.id === parentId);
            if (parent) parent.replies = (parent.replies || []).filter(r => r.id !== replyId);
            renderAllComments();
        })
        .catch(() => alert(FM_STR.delete_failed));
    });
}

// ── 의견 삭제 ─────────────────────────────────────
function deleteComment(id, btn) {
    showDeleteConfirmPopover(btn || document.body, FM_STR.confirm_delete_opinion, () => {
        fetch(`${_cBase()}/comments/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(r => r.json())
        .then(() => {
            _commentsList = _commentsList.filter(c => c.id !== id);
            renderAllComments();
            if (_annPreviewType === 'video') vidRenderMarkers();
            const total = _commentsList.reduce((s, x) => s + 1 + (x.replies?.length || 0), 0);
            updateFileRowBadge(currentFileId, total);
        })
        .catch(() => alert(FM_STR.delete_failed));
    });
}

// ── 파일 목록 의견 배지 실시간 업데이트 ──────────────
function updateFileRowBadge(fileId, count) {
    const badge = document.getElementById(`file-comment-badge-${fileId}`);
    if (!badge) return;
    if (count > 0) {
        badge.innerHTML = `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> ${FM_STR.opinions_count.replace(':count', count)}`;
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

// ── 의견 전용 팝업 ────────────────────────────────
let _cmFileId      = null;
let _cmComments    = [];
let _cmCanPreview  = false;

function openComments(fileId, fileName, projectId, canPreview) {
    _cmFileId        = fileId;
    _subscribeFileComments(fileId);
    _cmCanPreview    = !!canPreview;
    currentProjectId = projectId;
    document.getElementById('cm-filename').textContent = fileName;
    document.getElementById('cm-count').textContent = '';
    document.getElementById('cm-list').innerHTML = `<div style="color:#9ca3af;font-size:13px;text-align:center;padding:20px;">${FM_STR.loading}</div>`;
    document.getElementById('cm-new-input').value = '';

    const prevBtn = document.getElementById('cm-open-preview');
    if (_cmCanPreview) {
        prevBtn.style.display = 'block';
        prevBtn.onclick = () => { closeComments(); openPreview(fileId, projectId); };
    } else {
        prevBtn.style.display = 'none';
    }

    const modal = document.getElementById('comments-modal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch(`${BASE_URL}/projects/${projectId}/files/${fileId}/comments`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => r.json())
    .then(data => {
        _cmComments = (data.comments || []).reverse();
        _cmCanPreview = data.can_preview ?? _cmCanPreview;
        if (_cmCanPreview) {
            prevBtn.style.display = 'block';
            prevBtn.onclick = () => { closeComments(); openPreview(fileId, projectId); };
        }
        renderCmComments();
    })
    .catch(() => {
        document.getElementById('cm-list').innerHTML = `<div style="color:#ef4444;font-size:13px;text-align:center;padding:20px;">${FM_STR.load_failed}</div>`;
    });
}

function renderCmComments() {
    const list = document.getElementById('cm-list');
    const total = _cmComments.reduce((s, c) => s + 1 + (c.replies?.length || 0), 0);
    const cnt = document.getElementById('cm-count');
    cnt.textContent = total || '';

    if (!_cmComments.length) {
        list.innerHTML = `<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${FM_STR.no_opinions}<br><span style="font-size:12px;">${FM_STR.be_first_to_comment}</span></div>`;
        return;
    }

    list.innerHTML = _cmComments.map(c => cmCommentHtml(c)).join('');

    list.querySelectorAll('.cm-reply-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const pid = btn.dataset.pid;
            const form = document.getElementById(`cm-reply-form-${pid}`);
            if (!form) return;
            const isHidden = form.style.display === 'none';
            form.style.display = isHidden ? 'block' : 'none';
            if (isHidden) form.querySelector('textarea')?.focus();
        });
    });

    list.querySelectorAll('.cm-reply-submit').forEach(btn => {
        btn.addEventListener('click', () => cmSubmitReply(btn.dataset.pid));
    });

    list.querySelectorAll('.cm-del-btn').forEach(btn => {
        btn.addEventListener('click', (e) => cmDeleteComment(parseInt(btn.dataset.cid), btn.dataset.pid ? parseInt(btn.dataset.pid) : null, e.currentTarget));
    });

    list.querySelectorAll('.cm-reply-textarea').forEach(ta => {
        ta.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                cmSubmitReply(ta.dataset.pid);
            }
        });
    });
}

function cmCommentHtml(c) {
    const repliesHtml = (c.replies || []).map(r => `
        <div class="reply-card" id="cm-reply-${r.id}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <div style="flex:1;min-width:0;">
                    <span style="font-size:11px;font-weight:700;color:#6d28d9;">↳ ${escHtml(r.user_name)}</span>
                    <span style="font-size:10px;color:#9ca3af;margin-left:5px;">${r.created_at}</span>
                    <p style="font-size:12px;color:#374151;margin-top:3px;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;">${escHtml(r.content)}</p>
                </div>
                ${r.can_delete ? `<button class="cm-del-btn" data-cid="${r.id}" data-pid="${c.id}" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </div>
        </div>
    `).join('');

    return `<div class="comment-card" id="cm-cmt-${c.id}">
        ${c.page ? `<div class="page-badge" style="background:#ede9fe;color:#6d28d9;margin-bottom:5px;">${FM_STR.page_badge.replace(':page', c.page)}</div>` : ''}
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            <span style="font-size:12px;font-weight:700;color:#1f2937;">${escHtml(c.user_name)}</span>
            <span style="font-size:11px;color:#9ca3af;">${c.created_at}</span>
            <span style="margin-left:auto;display:inline-flex;align-items:center;gap:3px;flex-shrink:0;">
                ${convertBtnHtml(c)}
                ${c.can_delete ? `<button class="cm-del-btn" data-cid="${c.id}" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:16px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </span>
        </div>
        <p style="font-size:13px;color:#374151;margin:0;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;line-height:1.55;">${escHtml(c.content)}</p>
        <button type="button" class="cmt-reply-cta cm-reply-btn" data-pid="${c.id}">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            ${FM_STR.reply}${(c.replies || []).length ? ` (${(c.replies || []).length})` : ''}
        </button>
        ${repliesHtml ? `<div class="reply-thread">${repliesHtml}</div>` : '<div class="reply-thread" id="cm-replies-' + c.id + '" style="display:none;"></div>'}
        <div class="reply-form" id="cm-reply-form-${c.id}" style="display:none;">
            <textarea class="reply-textarea cm-reply-textarea" data-pid="${c.id}" rows="2" placeholder="${FM_STR.reply_placeholder}"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:6px;">
                <button onclick="document.getElementById('cm-reply-form-${c.id}').style.display='none'" style="padding:5px 12px;background:#f3f4f6;color:#374151;border:none;border-radius:5px;font-size:11px;cursor:pointer;">${FM_STR.cancel}</button>
                <button class="cm-reply-submit" data-pid="${c.id}" style="padding:5px 14px;background:#7c3aed;color:#fff;border:none;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer;">${FM_STR.register}</button>
            </div>
        </div>
    </div>`;
}

function cmSubmitComment() {
    const input = document.getElementById('cm-new-input');
    const content = input.value.trim();
    if (!content) { input.focus(); return; }

    const btn = document.getElementById('cm-submit-btn');
    btn.disabled = true; btn.textContent = FM_STR.loading;

    fetch(`${BASE_URL}/projects/${currentProjectId}/files/${_cmFileId}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ content })
    })
    .then(r => r.json())
    .then(c => {
        input.value = '';
        if (!_cmComments.some(x => x.id === c.id)) _cmComments.unshift({ ...c, replies: [] });
        renderCmComments();
        const list = document.getElementById('cm-list');
        list.scrollTop = 0;
        const total = _cmComments.reduce((s, x) => s + 1 + (x.replies?.length || 0), 0);
        updateFileRowBadge(_cmFileId, total);
    })
    .catch(() => alert(FM_STR.save_failed))
    .finally(() => { btn.disabled = false; btn.textContent = FM_STR.submit_opinion; });
}

function cmSubmitReply(parentId) {
    parentId = parseInt(parentId);
    const form = document.getElementById(`cm-reply-form-${parentId}`);
    const ta   = form?.querySelector('textarea');
    const content = ta?.value.trim();
    if (!content) { ta?.focus(); return; }

    const submitBtn = form.querySelector('.cm-reply-submit');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = FM_STR.reply_submitting; }

    fetch(`${BASE_URL}/projects/${currentProjectId}/files/${_cmFileId}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ content, parent_id: parentId })
    })
    .then(r => r.json())
    .then(reply => {
        const parent = _cmComments.find(c => c.id === parentId);
        if (parent) {
            parent.replies = parent.replies || [];
            if (!parent.replies.some(r => r.id === reply.id)) parent.replies.push(reply);
        }
        renderCmComments();
        const total = _cmComments.reduce((s, x) => s + 1 + (x.replies?.length || 0), 0);
        updateFileRowBadge(_cmFileId, total);
    })
    .catch(() => alert(FM_STR.save_failed))
    .finally(() => { if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = FM_STR.submit; } });
}

function closeComments() {
    document.getElementById('comments-modal').style.display = 'none';
    document.body.style.overflow = '';
    _cmFileId = null;
    _cmComments = [];
    _maybeUnsubscribeFileComments();
}

function cmDeleteComment(id, parentId, btn) {
    showDeleteConfirmPopover(btn || document.body, FM_STR.confirm_delete_opinion, () => {
        fetch(`${BASE_URL}/projects/${currentProjectId}/files/${_cmFileId}/comments/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(r => r.json())
        .then(() => {
            if (parentId) {
                const parent = _cmComments.find(c => c.id === parentId);
                if (parent) parent.replies = parent.replies.filter(r => r.id !== id);
            } else {
                _cmComments = _cmComments.filter(c => c.id !== id);
            }
            renderCmComments();
            const total = _cmComments.reduce((s, x) => s + 1 + (x.replies?.length || 0), 0);
            updateFileRowBadge(_cmFileId, total);
        })
        .catch(() => alert(FM_STR.delete_failed));
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── SVG 위치 동기화 ─────────────────────────────────
// PDF 모드: SVG를 캔버스 영역에만 덮음 → 도형 좌표 = 캔버스 % → 확대/축소 자동 동기화
// 그 외 모드: SVG를 전체 뷰어에 덮음
function _updateSvgPosition() {
    const svg = document.getElementById('ann-svg');
    if (_annPreviewType === 'pdf' && pdfDoc) {
        const canvas = document.getElementById('pdf-canvas');
        const vr = svg.parentElement.getBoundingClientRect();
        const cr = canvas.getBoundingClientRect();
        svg.style.left   = `${cr.left - vr.left}px`;
        svg.style.top    = `${cr.top  - vr.top}px`;
        svg.style.right  = 'auto';
        svg.style.bottom = 'auto';
        svg.style.width  = `${cr.width}px`;
        svg.style.height = `${cr.height}px`;
    } else if (_annPreviewType === 'image') {
        const img = document.getElementById('viewer-img');
        const vr  = svg.parentElement.getBoundingClientRect();
        const ir  = img.getBoundingClientRect();
        svg.style.left   = `${ir.left - vr.left}px`;
        svg.style.top    = `${ir.top  - vr.top}px`;
        svg.style.right  = 'auto';
        svg.style.bottom = 'auto';
        svg.style.width  = `${ir.width}px`;
        svg.style.height = `${ir.height}px`;
    } else {
        svg.style.left = '0'; svg.style.top = '0';
        svg.style.right = '0'; svg.style.bottom = '0';
        svg.style.width = '100%'; svg.style.height = '100%';
    }
}

// PDF/이미지 스크롤 시 SVG 위치 갱신 (requestAnimationFrame으로 성능 최적화)
let _svgPosRaf = null;
document.getElementById('pdf-canvas-wrap').addEventListener('scroll', () => {
    if (!pdfDoc || _annPreviewType !== 'pdf') return;
    if (_svgPosRaf) cancelAnimationFrame(_svgPosRaf);
    _svgPosRaf = requestAnimationFrame(() => { _updateSvgPosition(); _svgPosRaf = null; });
});
document.getElementById('img-scroll-wrap').addEventListener('scroll', () => {
    if (_annPreviewType !== 'image') return;
    if (_svgPosRaf) cancelAnimationFrame(_svgPosRaf);
    _svgPosRaf = requestAnimationFrame(() => { _updateSvgPosition(); _svgPosRaf = null; });
});

// ── 도형 주석 ─────────────────────────────────────

function setAnnTool(tool) {
    annTool = (annTool === tool) ? null : tool;
    if (annTool) { selectAnnotation(null); } // deselect when switching to draw mode
    document.querySelectorAll('.ann-tool-btn').forEach(btn => {
        const t = btn.id.replace('ann-btn-', '');
        btn.classList.toggle('active', t === annTool);
    });
    const svg = document.getElementById('ann-svg');
    svg.style.pointerEvents = (annTool || annSelected) ? 'all' : 'none';
    svg.style.cursor = annTool ? 'crosshair' : 'default';
}

function setAnnColor(c) {
    annColor = c;
    document.querySelectorAll('.ann-color-btn').forEach(btn => {
        btn.style.outline = (btn.dataset.color === c) ? '2px solid #fff' : 'none';
        btn.style.outlineOffset = '2px';
    });
}

function _getSvgPct(svg, e) {
    const r = svg.getBoundingClientRect();
    return {
        x: Math.max(0, Math.min(100, (e.clientX - r.left)  / r.width  * 100)),
        y: Math.max(0, Math.min(100, (e.clientY - r.top)   / r.height * 100))
    };
}

function _makeTempEl(type) {
    const ns = 'http://www.w3.org/2000/svg';
    if (type === 'rect') {
        const el = document.createElementNS(ns, 'rect');
        el.setAttribute('fill', 'none'); el.setAttribute('stroke', annColor);
        el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-dasharray', '5 3');
        return el;
    }
    if (type === 'circle') {
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('fill', 'none'); el.setAttribute('stroke', annColor);
        el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-dasharray', '5 3');
        return el;
    }
    if (type === 'line') {
        const el = document.createElementNS(ns, 'line');
        el.setAttribute('stroke', annColor); el.setAttribute('stroke-width', '2.5');
        el.setAttribute('stroke-linecap', 'round');
        return el;
    }
    return null;
}

function _updateTempEl(el, type, x1, y1, x2, y2) {
    if (type === 'rect') {
        const x = Math.min(x1, x2), y = Math.min(y1, y2);
        el.setAttribute('x', `${x}%`); el.setAttribute('y', `${y}%`);
        el.setAttribute('width',  `${Math.abs(x2 - x1)}%`);
        el.setAttribute('height', `${Math.abs(y2 - y1)}%`);
    } else if (type === 'circle') {
        const cx = (x1 + x2) / 2, cy = (y1 + y2) / 2;
        el.setAttribute('cx', `${cx}%`); el.setAttribute('cy', `${cy}%`);
        el.setAttribute('rx', `${Math.abs(x2 - x1) / 2}%`);
        el.setAttribute('ry', `${Math.abs(y2 - y1) / 2}%`);
    } else if (type === 'line') {
        el.setAttribute('x1', `${x1}%`); el.setAttribute('y1', `${y1}%`);
        el.setAttribute('x2', `${x2}%`); el.setAttribute('y2', `${y2}%`);
    }
}

// SVG 마우스 이벤트 초기화
(function() {
    const svg = document.getElementById('ann-svg');

    svg.addEventListener('mousedown', e => {
        const pct = _getSvgPct(svg, e);

        if (annTool) {
            // ── 그리기 모드 ──
            if (e.target.closest && e.target.closest('#ann-sel-overlay')) return;
            e.preventDefault();
            if (annTool === 'number') {
                saveAnnotation('number', { x: pct.x, y: pct.y, n: annNextNum, color: annColor });
                setAnnTool(null);
                return;
            }
            if (annTool === 'text') {
                _annTextPct = pct;
                const popup = document.getElementById('ann-text-popup');
                popup.style.left = `${Math.min(e.clientX, window.innerWidth - 380)}px`;
                popup.style.top  = `${Math.min(e.clientY, window.innerHeight - 210)}px`;
                popup.style.display = 'block';
                setTimeout(() => document.getElementById('ann-text-input').focus(), 50);
                return;
            }
            annDrawing = true;
            annStartX  = pct.x; annStartY = pct.y;
            annDragEl  = _makeTempEl(annTool);
            if (annDragEl) svg.appendChild(annDragEl);
            return;
        }

        // ── 선택 모드: SVG 빈 영역 클릭 → 선택 해제 ──
        if (annSelected) {
            if (e.target.closest && e.target.closest('#ann-sel-overlay')) return;
            if (_findAnnGroup(e.target)) return; // <g> 에서 처리
            selectAnnotation(null);
        }
    });

    svg.addEventListener('mousemove', e => {
        if (annDrawing && annDragEl) {
            e.preventDefault();
            const pct = _getSvgPct(svg, e);
            _updateTempEl(annDragEl, annTool, annStartX, annStartY, pct.x, pct.y);
            return;
        }
        if (annMoveActive && annSelected) {
            e.preventDefault();
            const pct = _getSvgPct(svg, e);
            _directMoveAnn(annSelected.type, annMoveStartData, pct.x - annMoveStartX, pct.y - annMoveStartY);
        }
    });

    function finishInteraction(e) {
        if (annDrawing) {
            annDrawing = false;
            const pct = _getSvgPct(svg, e);
            if (annDragEl) { annDragEl.remove(); annDragEl = null; }
            const dx = pct.x - annStartX, dy = pct.y - annStartY;
            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) return;
            if (annTool === 'rect') {
                saveAnnotation('rect', { x1: annStartX, y1: annStartY, x2: pct.x, y2: pct.y, color: annColor });
            } else if (annTool === 'circle') {
                const cx = (annStartX + pct.x) / 2, cy = (annStartY + pct.y) / 2;
                saveAnnotation('circle', { cx, cy, rx: Math.abs(dx) / 2, ry: Math.abs(dy) / 2, color: annColor });
            } else if (annTool === 'line') {
                saveAnnotation('line', { x1: annStartX, y1: annStartY, x2: pct.x, y2: pct.y, color: annColor });
            }
            setAnnTool(null);
            return;
        }
        if (annMoveActive && annSelected) {
            annMoveActive = false;
            svg.style.cursor = 'default';
            const pct = _getSvgPct(svg, e);
            const dx = pct.x - annMoveStartX, dy = pct.y - annMoveStartY;
            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
                // Just a click – re-show selection overlay
                _clearSelectionOverlay();
                _showSelectionOverlay(annSelected.id);
                return;
            }
            const newData = applyDelta(annSelected.type, annMoveStartData, dx, dy);
            const idx = annList.findIndex(a => a.id === annSelected.id);
            if (idx !== -1) { annList[idx].data = newData; annSelected = annList[idx]; }
            patchAnnotation(annSelected.id, newData);
            renderAnnotations();
        }
    }

    svg.addEventListener('mouseup',    finishInteraction);
    svg.addEventListener('mouseleave', finishInteraction);
})();

function loadAnnotations() {
    if (!_annotationApiBase && (!currentFileId || !currentProjectId)) return;
    fetch(`${_aBase()}/annotations`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => r.json())
    .then(data => {
        annList    = data.annotations || [];
        annNextNum = calcNextNum(annList);
        renderAnnotations();
        renderAllComments();
    })
    .catch(err => console.error('[Annotations load]', err));
}

function renderAnnotations() {
    const svg = document.getElementById('ann-svg');
    svg.querySelectorAll('.ann-item, #ann-sel-overlay').forEach(el => el.remove());

    const visible = annList.filter(a =>
        _annPreviewType === 'pdf' ? a.page === pdfPage : true
    );
    visible.forEach(a => _renderAnnItem(a, svg));

    // Re-apply selection overlay if selected annotation is still visible
    if (annSelected && visible.find(a => a.id === annSelected.id)) {
        _showSelectionOverlay(annSelected.id);
    } else if (annSelected) {
        annSelected = null;
        const s = document.getElementById('ann-svg');
        s.style.pointerEvents = annTool ? 'all' : 'none';
    }
}

function _renderAnnItem(ann, svg) {
    const ns = 'http://www.w3.org/2000/svg';
    const g = document.createElementNS(ns, 'g');
    g.classList.add('ann-item');
    g.dataset.annId = ann.id;
    if (ann.can_delete) g.dataset.canDelete = '1';
    g.setAttribute('title', FM_STR.ann_by.replace(':name', escHtml(ann.user_name)));

    const d = ann.data, color = d.color || '#ef4444';

    if (ann.type === 'number') {
        const c = document.createElementNS(ns, 'circle');
        c.setAttribute('cx', `${d.x}%`); c.setAttribute('cy', `${d.y}%`); c.setAttribute('r', '14');
        c.setAttribute('fill', color); c.setAttribute('stroke', 'white'); c.setAttribute('stroke-width', '1.5');
        const t = document.createElementNS(ns, 'text');
        t.setAttribute('x', `${d.x}%`); t.setAttribute('y', `${d.y}%`);
        t.setAttribute('text-anchor', 'middle'); t.setAttribute('dominant-baseline', 'central');
        t.setAttribute('fill', 'white'); t.setAttribute('font-size', '11'); t.setAttribute('font-weight', '700');
        t.setAttribute('pointer-events', 'none');
        t.textContent = d.n;
        g.appendChild(c); g.appendChild(t);

    } else if (ann.type === 'rect') {
        const x = Math.min(d.x1, d.x2), y = Math.min(d.y1, d.y2);
        const rect = document.createElementNS(ns, 'rect');
        rect.setAttribute('x', `${x}%`); rect.setAttribute('y', `${y}%`);
        rect.setAttribute('width',  `${Math.abs(d.x2 - d.x1)}%`);
        rect.setAttribute('height', `${Math.abs(d.y2 - d.y1)}%`);
        rect.setAttribute('fill', 'rgba(0,0,0,0)'); rect.setAttribute('stroke', color); rect.setAttribute('stroke-width', '2.5');
        g.appendChild(rect);

    } else if (ann.type === 'circle') {
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('cx', `${d.cx}%`); el.setAttribute('cy', `${d.cy}%`);
        el.setAttribute('rx', `${d.rx}%`); el.setAttribute('ry', `${d.ry}%`);
        el.setAttribute('fill', 'rgba(0,0,0,0)'); el.setAttribute('stroke', color); el.setAttribute('stroke-width', '2.5');
        g.appendChild(el);

    } else if (ann.type === 'line') {
        const el = document.createElementNS(ns, 'line');
        el.setAttribute('x1', `${d.x1}%`); el.setAttribute('y1', `${d.y1}%`);
        el.setAttribute('x2', `${d.x2}%`); el.setAttribute('y2', `${d.y2}%`);
        el.setAttribute('stroke', color); el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-linecap', 'round');
        // arrowhead
        const markId = `arr-${ann.id}`;
        const defs = svg.querySelector('defs') || svg.insertBefore(document.createElementNS(ns, 'defs'), svg.firstChild);
        const mk = document.createElementNS(ns, 'marker');
        mk.setAttribute('id', markId); mk.setAttribute('markerWidth', '8'); mk.setAttribute('markerHeight', '6');
        mk.setAttribute('refX', '7'); mk.setAttribute('refY', '3'); mk.setAttribute('orient', 'auto');
        const poly = document.createElementNS(ns, 'polygon');
        poly.setAttribute('points', '0 0, 8 3, 0 6'); poly.setAttribute('fill', color);
        mk.appendChild(poly); defs.appendChild(mk);
        el.setAttribute('marker-end', `url(#${markId})`);
        g.appendChild(el);

    } else if (ann.type === 'text') {
        const el = document.createElementNS(ns, 'text');
        el.setAttribute('x', `${d.x}%`); el.setAttribute('y', `${d.y}%`);
        el.setAttribute('fill', color); el.setAttribute('font-size', '14'); el.setAttribute('font-weight', '700');
        el.setAttribute('dominant-baseline', 'hanging');
        const lines = (d.text || '').split('\n');
        lines.forEach((line, i) => {
            const tspan = document.createElementNS(ns, 'tspan');
            tspan.setAttribute('x', `${d.x}%`);
            tspan.setAttribute('dy', i === 0 ? '0' : '1.4em');
            tspan.textContent = line || ' ';
            el.appendChild(tspan);
        });
        g.appendChild(el);
    }

    if (ann.can_delete) {
        // 본인 도형: 선택·이동 가능
        g.setAttribute('pointer-events', 'all');
        g.style.cursor = 'grab';
        g.addEventListener('mousedown', e => {
            if (annTool) return;
            e.preventDefault();
            e.stopPropagation();
            const svgEl = document.getElementById('ann-svg');
            const pct   = _getSvgPct(svgEl, e);
            selectAnnotation(ann.id);
            annMoveActive    = true;
            annMoveStartX    = pct.x;
            annMoveStartY    = pct.y;
            annMoveStartData = JSON.parse(JSON.stringify(annList.find(a => a.id === ann.id)?.data || ann.data));
            _clearSelectionOverlay();
            svgEl.style.cursor = 'grabbing';
        });
    } else {
        // 타인 도형: 보기 전용
        g.setAttribute('pointer-events', 'none');
        g.style.cursor = 'default';
    }

    svg.appendChild(g);
}

function saveAnnotation(type, data) {
    const page = (_annPreviewType === 'pdf') ? pdfPage : null;
    fetch(`${_aBase()}/annotations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ type, data, page })
    })
    .then(r => {
        if (!r.ok) return r.text().then(t => { throw new Error(`HTTP ${r.status}: ${t.substring(0,200)}`); });
        return r.json();
    })
    .then(resp => {
        if (!resp.ok) { alert(FM_STR.ann_save_failed); return; }
        annList.push(resp.annotation);
        if (type === 'number') annNextNum = calcNextNum(annList);
        renderAnnotations();
        if (type === 'text') renderAllComments();
    })
    .catch(err => { console.error('[saveAnnotation]', err); alert(FM_STR.ann_save_failed_detail.replace(':message', err.message)); });
}

function deleteAnnotation(id) {
    fetch(`${_aBase()}/annotations/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) return;
        annList = annList.filter(a => a.id !== id);
        annNextNum = calcNextNum(annList);
        if (annSelected?.id === id) selectAnnotation(null);
        renderAnnotations();
        renderAllComments();
    })
    .catch(err => console.error('[deleteAnnotation]', err));
}

function patchAnnotation(id, data) {
    fetch(`${_aBase()}/annotations/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ data })
    })
    .catch(err => console.error('[patchAnnotation]', err));
}

function selectAnnotation(id) {
    annSelected = id ? (annList.find(a => a.id === id) || null) : null;
    const svg = document.getElementById('ann-svg');
    svg.style.pointerEvents = (annSelected || annTool) ? 'all' : 'none';
    _clearSelectionOverlay();
    if (annSelected) _showSelectionOverlay(annSelected.id);
}

function _clearSelectionOverlay() {
    document.getElementById('ann-sel-overlay')?.remove();
}

function _showSelectionOverlay(annId) {
    _clearSelectionOverlay();
    const svg = document.getElementById('ann-svg');
    const g   = svg.querySelector(`.ann-item[data-ann-id="${annId}"]`);
    if (!g) return;

    let bbox;
    try { bbox = g.getBBox(); } catch (_) { return; }

    const ann = annList.find(a => a.id === annId);
    const ns  = 'http://www.w3.org/2000/svg';
    const pad = 6;

    const overlay = document.createElementNS(ns, 'g');
    overlay.id = 'ann-sel-overlay';
    overlay.style.pointerEvents = 'none';

    // 선택 테두리
    const selRect = document.createElementNS(ns, 'rect');
    selRect.setAttribute('x',      bbox.x - pad);
    selRect.setAttribute('y',      bbox.y - pad);
    selRect.setAttribute('width',  Math.max(bbox.width  + pad * 2, 4));
    selRect.setAttribute('height', Math.max(bbox.height + pad * 2, 4));
    selRect.setAttribute('fill',   'rgba(167,139,250,.08)');
    selRect.setAttribute('stroke', '#a78bfa');
    selRect.setAttribute('stroke-width',   '1.5');
    selRect.setAttribute('stroke-dasharray', '5 3');
    selRect.setAttribute('rx', '4');
    overlay.appendChild(selRect);

    // 텍스트 수정 버튼 (우상단 삭제 버튼 왼쪽)
    if (ann && ann.type === 'text' && ann.can_delete) {
        const editG = document.createElementNS(ns, 'g');
        const editX = bbox.x + bbox.width + pad + 2;
        editG.setAttribute('transform', `translate(${editX}, ${bbox.y - pad - 2})`);
        editG.style.pointerEvents = 'all';
        editG.style.cursor        = 'pointer';
        editG.addEventListener('mousedown', e => e.stopPropagation());
        editG.addEventListener('click', e => {
            e.stopPropagation();
            _annEditId = ann.id;
            document.getElementById('ann-text-popup-title').textContent = FM_STR.ann_text_edit;
            document.getElementById('ann-text-input').value = ann.data.text || '';
            const svgEl = document.getElementById('ann-svg');
            const sr    = svgEl.getBoundingClientRect();
            const px    = sr.left + bbox.x;
            const py    = sr.top  + bbox.y + bbox.height + 4;
            const popup = document.getElementById('ann-text-popup');
            popup.style.left = `${Math.min(px, window.innerWidth  - 380)}px`;
            popup.style.top  = `${Math.min(py, window.innerHeight - 210)}px`;
            popup.style.display = 'block';
            setTimeout(() => {
                const inp = document.getElementById('ann-text-input');
                inp.focus(); inp.select();
            }, 30);
        });
        const editBg = document.createElementNS(ns, 'circle');
        editBg.setAttribute('r', '9'); editBg.setAttribute('fill', '#7c3aed');
        editBg.setAttribute('stroke', 'white'); editBg.setAttribute('stroke-width', '1.5');
        editG.appendChild(editBg);
        // 펜 아이콘 (간단한 path)
        const editIco = document.createElementNS(ns, 'path');
        editIco.setAttribute('d', 'M-3,-2 L2,-2 L2,2 L-3,2 Z M-4,3 L3,3');
        editIco.setAttribute('fill', 'none'); editIco.setAttribute('stroke', 'white');
        editIco.setAttribute('stroke-width', '1.5'); editIco.setAttribute('stroke-linecap', 'round');
        editIco.setAttribute('pointer-events', 'none');
        editG.appendChild(editIco);
        // "수정" 텍스트 대신 작은 T 글자
        const editTxt = document.createElementNS(ns, 'text');
        editTxt.setAttribute('text-anchor', 'middle'); editTxt.setAttribute('dominant-baseline', 'central');
        editTxt.setAttribute('fill', 'white'); editTxt.setAttribute('font-size', '11');
        editTxt.setAttribute('font-weight', '700'); editTxt.setAttribute('pointer-events', 'none');
        editTxt.textContent = '✎';
        editG.appendChild(editTxt);
        overlay.appendChild(editG);
    }

    // 삭제 버튼 (우상단)
    if (ann && ann.can_delete) {
        const delOffset = (ann.type === 'text') ? 24 : 0; // text 타입이면 수정 버튼 공간 확보
        const delG = document.createElementNS(ns, 'g');
        delG.setAttribute('transform', `translate(${bbox.x + bbox.width + pad + 2 + delOffset}, ${bbox.y - pad - 2})`);
        delG.style.pointerEvents = 'all';
        delG.style.cursor        = 'pointer';
        delG.addEventListener('mousedown', e => e.stopPropagation());
        delG.addEventListener('click', e => {
            e.stopPropagation();
            if (!confirm(FM_STR.confirm_delete_ann)) return;
            deleteAnnotation(ann.id);
        });
        const delBg = document.createElementNS(ns, 'circle');
        delBg.setAttribute('r', '9'); delBg.setAttribute('fill', '#ef4444');
        delBg.setAttribute('stroke', 'white'); delBg.setAttribute('stroke-width', '1.5');
        delG.appendChild(delBg);
        const delTxt = document.createElementNS(ns, 'text');
        delTxt.setAttribute('text-anchor', 'middle'); delTxt.setAttribute('dominant-baseline', 'central');
        delTxt.setAttribute('fill', 'white'); delTxt.setAttribute('font-size', '13');
        delTxt.setAttribute('font-weight', '700'); delTxt.setAttribute('pointer-events', 'none');
        delTxt.textContent = '×';
        delG.appendChild(delTxt);
        overlay.appendChild(delG);
    }

    svg.appendChild(overlay);
}

function _findAnnGroup(el) {
    let curr = el;
    const svg = document.getElementById('ann-svg');
    while (curr && curr !== svg) {
        if (curr.classList && curr.classList.contains('ann-item')) return curr;
        curr = curr.parentElement;
    }
    return null;
}

function applyDelta(type, data, dx, dy) {
    const cl = v => Math.max(0, Math.min(100, v));
    const d  = Object.assign({}, data);
    if (type === 'number' || type === 'text') {
        d.x = cl(d.x + dx); d.y = cl(d.y + dy);
    } else if (type === 'rect' || type === 'line') {
        d.x1 = cl(d.x1 + dx); d.y1 = cl(d.y1 + dy);
        d.x2 = cl(d.x2 + dx); d.y2 = cl(d.y2 + dy);
    } else if (type === 'circle') {
        d.cx = cl(d.cx + dx); d.cy = cl(d.cy + dy);
    }
    return d;
}

function _directMoveAnn(type, base, dx, dy) {
    const ann = annSelected;
    if (!ann) return;
    const g = document.getElementById('ann-svg').querySelector(`.ann-item[data-ann-id="${ann.id}"]`);
    if (!g) return;
    const cl = v => Math.max(0, Math.min(100, v));

    if (type === 'number') {
        const nx = cl(base.x + dx), ny = cl(base.y + dy);
        g.querySelector('circle')?.setAttribute('cx', `${nx}%`);
        g.querySelector('circle')?.setAttribute('cy', `${ny}%`);
        g.querySelector('text')?.setAttribute('x', `${nx}%`);
        g.querySelector('text')?.setAttribute('y', `${ny}%`);
    } else if (type === 'rect') {
        const nx1 = cl(base.x1 + dx), ny1 = cl(base.y1 + dy);
        const nx2 = cl(base.x2 + dx), ny2 = cl(base.y2 + dy);
        const r = g.querySelector('rect');
        if (r) {
            r.setAttribute('x', `${Math.min(nx1, nx2)}%`);
            r.setAttribute('y', `${Math.min(ny1, ny2)}%`);
            r.setAttribute('width',  `${Math.abs(nx2 - nx1)}%`);
            r.setAttribute('height', `${Math.abs(ny2 - ny1)}%`);
        }
    } else if (type === 'circle') {
        const e = g.querySelector('ellipse');
        if (e) {
            e.setAttribute('cx', `${cl(base.cx + dx)}%`);
            e.setAttribute('cy', `${cl(base.cy + dy)}%`);
        }
    } else if (type === 'line') {
        const l = g.querySelector('line');
        if (l) {
            l.setAttribute('x1', `${cl(base.x1 + dx)}%`); l.setAttribute('y1', `${cl(base.y1 + dy)}%`);
            l.setAttribute('x2', `${cl(base.x2 + dx)}%`); l.setAttribute('y2', `${cl(base.y2 + dy)}%`);
        }
    } else if (type === 'text') {
        const t = g.querySelector('text');
        if (t) {
            const nx = `${cl(base.x + dx)}%`;
            const ny = `${cl(base.y + dy)}%`;
            t.setAttribute('x', nx);
            t.setAttribute('y', ny);
            t.querySelectorAll('tspan').forEach(ts => ts.setAttribute('x', nx));
        }
    }
}

function confirmAnnText() {
    const val = document.getElementById('ann-text-input').value.trim();
    document.getElementById('ann-text-popup').style.display = 'none';
    document.getElementById('ann-text-input').value = '';
    document.getElementById('ann-text-popup-title').textContent = FM_STR.ann_text_title;

    if (_annEditId) {
        // 기존 텍스트 주석 수정
        if (val) {
            const ann = annList.find(a => a.id === _annEditId);
            if (ann) {
                const newData = Object.assign({}, ann.data, { text: val });
                ann.data = newData;
                patchAnnotation(_annEditId, newData);
                renderAnnotations();
                renderAllComments();
            }
        }
        _annEditId = null;
        return;
    }

    if (!val || !_annTextPct) { _annTextPct = null; return; }
    saveAnnotation('text', { x: _annTextPct.x, y: _annTextPct.y, text: val, color: annColor });
    _annTextPct = null;
    setAnnTool(null);
}

function cancelAnnText() {
    document.getElementById('ann-text-popup').style.display = 'none';
    document.getElementById('ann-text-input').value = '';
    document.getElementById('ann-text-popup-title').textContent = FM_STR.ann_text_title;
    _annTextPct = null;
    _annEditId  = null;
}

document.getElementById('ann-text-input').addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); confirmAnnText(); }
    if (e.key === 'Escape') { e.preventDefault(); cancelAnnText(); }
});

// 기본 색상 초기화
setAnnColor('#ef4444');

// ── URL 뷰어 팝업 ─────────────────────────────────
let urlFileId    = null;
let urlProjectId = null;
let urlSrcUrl    = '';
let _urlCmts     = [];

function openUrlViewer(fileId, projectId, fileName, embedUrl, sourceUrl) {
    urlFileId    = fileId;
    urlProjectId = projectId;
    urlSrcUrl    = sourceUrl;

    document.getElementById('url-viewer-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    document.getElementById('url-modal-fname').textContent   = fileName;
    document.getElementById('url-modal-block-link').href     = sourceUrl;
    document.getElementById('url-modal-loading').style.display = 'flex';
    document.getElementById('url-modal-frame').style.display  = 'none';
    document.getElementById('url-modal-frame').src            = '';
    document.getElementById('url-modal-block').style.display  = 'none';
    document.getElementById('url-cmt-input').value = '';
    document.getElementById('url-cmt-count').textContent = '';
    document.getElementById('url-cmt-list').innerHTML =
        `<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${FM_STR.loading}</div>`;
    _urlCmts = [];

    setTimeout(() => { document.getElementById('url-modal-frame').src = embedUrl; }, 80);

    fetch(`${BASE_URL}/projects/${projectId}/files/${fileId}/comments`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => r.json())
    .then(d => urlRenderCmts(d.comments || []))
    .catch(() => {
        document.getElementById('url-cmt-list').innerHTML =
            `<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${FM_STR.no_opinions}</div>`;
    });
}

function closeUrlViewer() {
    document.getElementById('url-viewer-modal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('url-modal-frame').src = '';
    urlFileId = null;
}

function onUrlModalLoad() {
    setTimeout(() => {
        const f = document.getElementById('url-modal-frame');
        try {
            if (f.contentDocument && f.contentDocument.body && f.contentDocument.body.innerHTML === '') {
                showUrlBlock();
            } else {
                document.getElementById('url-modal-loading').style.display = 'none';
                f.style.display = 'block';
            }
        } catch(e) {
            document.getElementById('url-modal-loading').style.display = 'none';
            f.style.display = 'block';
        }
    }, 2000);
}

function showUrlBlock() {
    document.getElementById('url-modal-loading').style.display = 'none';
    document.getElementById('url-modal-frame').style.display   = 'none';
    document.getElementById('url-modal-block').style.display   = 'flex';
}

function urlNewTab()  { if (urlSrcUrl) window.open(urlSrcUrl, '_blank'); }
function urlPrintPdf() {
    if (!urlSrcUrl) return;
    const win = window.open(urlSrcUrl, '_blank', 'width=1280,height=900');
    if (!win) { alert(FM_STR.popup_blocked); return; }
    win.addEventListener('load', () => setTimeout(() => { try { win.print(); } catch(e) {} }, 800));
}

// URL 뷰어: 의견·답글을 PDF 보고서로 다운로드 (원본 파일 없이 의견만)
function downloadUrlCommentsReport() {
    if (!urlProjectId || !urlFileId) return;
    const url = `${BASE_URL}/projects/${urlProjectId}/files/${urlFileId}/comments/download`;
    const a = document.createElement('a');
    a.href = url;
    a.target = '_blank';
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
window.downloadUrlCommentsReport = downloadUrlCommentsReport;

function urlRenderCmts(list) {
    _urlCmts = list;
    const el  = document.getElementById('url-cmt-list');
    const cnt = document.getElementById('url-cmt-count');
    const total = list.reduce((s, c) => s + 1 + (c.replies?.length || 0), 0);
    cnt.textContent = total || '';
    if (!list.length) {
        el.innerHTML = `<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">${FM_STR.no_opinions}</div>`;
        return;
    }
    el.innerHTML = list.map(c => urlCmtHtml(c)).join('');
}

function urlCmtHtml(c) {
    const rHtml = (c.replies || []).map(r => `
        <div class="reply-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;">
                <div style="flex:1;min-width:0;">
                    <span style="font-size:11px;font-weight:700;color:#6d28d9;">↳ ${escHtml(r.user_name)}</span>
                    <span style="font-size:10px;color:#9ca3af;margin-left:5px;">${r.created_at}</span>
                    <p style="font-size:12px;color:#374151;margin-top:3px;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;">${escHtml(r.content)}</p>
                </div>
                ${r.can_delete ? `<button onclick="urlDelCmt(${r.id},${c.id},this)" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;font-size:15px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </div>
        </div>`).join('');

    return `<div class="comment-card">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            <span style="font-size:12px;font-weight:700;color:#1f2937;">${escHtml(c.user_name)}</span>
            <span style="font-size:11px;color:#9ca3af;">${c.created_at}</span>
            <span style="margin-left:auto;display:inline-flex;align-items:center;gap:3px;flex-shrink:0;">
                ${convertBtnHtml(c)}
                ${c.can_delete ? `<button onclick="urlDelCmt(${c.id},null,this)" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:16px;line-height:1;padding:0 2px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>` : ''}
            </span>
        </div>
        <p style="font-size:13px;color:#374151;margin:0;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;line-height:1.55;">${escHtml(c.content)}</p>
        <button type="button" class="cmt-reply-cta" onclick="urlToggleReply(${c.id})">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
            ${FM_STR.reply}${(c.replies || []).length ? ` (${(c.replies || []).length})` : ''}
        </button>
        ${rHtml ? `<div class="reply-thread">${rHtml}</div>` : `<div class="reply-thread" id="url-replies-${c.id}" style="display:none;"></div>`}
        <div class="reply-form" id="url-reply-form-${c.id}" style="display:none;">
            <textarea class="reply-textarea" id="url-reply-ta-${c.id}" rows="2" placeholder="${FM_STR.reply_url_placeholder}"
                      onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();urlSubmitReply(${c.id});}"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:6px;">
                <button onclick="urlToggleReply(${c.id})" style="padding:5px 12px;background:#f3f4f6;color:#374151;border:none;border-radius:5px;font-size:11px;cursor:pointer;">${FM_STR.cancel}</button>
                <button onclick="urlSubmitReply(${c.id})" style="padding:5px 14px;background:#7c3aed;color:#fff;border:none;border-radius:5px;font-size:11px;font-weight:700;cursor:pointer;">${FM_STR.register}</button>
            </div>
        </div>
    </div>`;
}

function urlToggleReply(parentId) {
    const form = document.getElementById(`url-reply-form-${parentId}`);
    if (!form) return;
    const hidden = form.style.display === 'none';
    form.style.display = hidden ? 'block' : 'none';
    if (hidden) document.getElementById(`url-reply-ta-${parentId}`)?.focus();
}

function urlSubmitCmt() {
    const input   = document.getElementById('url-cmt-input');
    const content = input.value.trim();
    if (!content || !urlFileId) { input.focus(); return; }
    const btn = document.getElementById('url-cmt-btn');
    btn.disabled = true; btn.textContent = FM_STR.loading;
    fetch(`${BASE_URL}/projects/${urlProjectId}/files/${urlFileId}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ content, page: null })
    })
    .then(r => r.json())
    .then(c => {
        input.value = '';
        _urlCmts.push({ ...c, replies: [] });
        urlRenderCmts(_urlCmts);
        const list = document.getElementById('url-cmt-list');
        list.scrollTop = list.scrollHeight;
        updateFileRowBadge(urlFileId, _urlCmts.reduce((s,x)=>s+1+(x.replies?.length||0),0));
    })
    .catch(() => alert(FM_STR.save_failed))
    .finally(() => { btn.disabled = false; btn.textContent = FM_STR.submit; });
}

function urlSubmitReply(parentId) {
    const ta = document.getElementById(`url-reply-ta-${parentId}`);
    const content = ta?.value.trim();
    if (!content) { ta?.focus(); return; }
    fetch(`${BASE_URL}/projects/${urlProjectId}/files/${urlFileId}/comments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ content, parent_id: parentId })
    })
    .then(r => r.json())
    .then(reply => {
        const parent = _urlCmts.find(c => c.id === parentId);
        if (parent) { parent.replies = parent.replies || []; parent.replies.push(reply); }
        if (ta) ta.value = '';
        urlRenderCmts(_urlCmts);
    })
    .catch(() => alert(FM_STR.save_failed));
}

function urlDelCmt(id, parentId, btn) {
    showDeleteConfirmPopover(btn || document.body, FM_STR.confirm_delete_opinion, () => {
        _urlDelCmtPerform(id, parentId);
    });
}

function _urlDelCmtPerform(id, parentId) {
    fetch(`${BASE_URL}/projects/${urlProjectId}/files/${urlFileId}/comments/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(r => r.json())
    .then(() => {
        if (parentId) {
            const parent = _urlCmts.find(c => c.id === parentId);
            if (parent) parent.replies = parent.replies.filter(r => r.id !== id);
        } else {
            _urlCmts = _urlCmts.filter(c => c.id !== id);
        }
        urlRenderCmts(_urlCmts);
        updateFileRowBadge(urlFileId, _urlCmts.reduce((s,x)=>s+1+(x.replies?.length||0),0));
    })
    .catch(() => alert(FM_STR.delete_failed));
}

// ── 리뷰 포함 PDF 다운로드 ──────────────────────────
function _drawAnnsOnCanvas(ctx, anns, canvasW, canvasH) {
    // data는 0-100 퍼센트 값으로 저장됨
    const px = v => v / 100 * canvasW;
    const py = v => v / 100 * canvasH;
    anns.forEach(a => {
        const d   = a.data || {};
        const col = d.color || '#ef4444';
        ctx.strokeStyle = col;
        ctx.fillStyle   = col;
        ctx.lineWidth   = Math.max(2, canvasW * 0.0025);
        if (a.type === 'rect') {
            const x1 = px(d.x1 ?? 0), y1 = py(d.y1 ?? 0);
            const x2 = px(d.x2 ?? 0), y2 = py(d.y2 ?? 0);
            ctx.strokeRect(Math.min(x1,x2), Math.min(y1,y2), Math.abs(x2-x1), Math.abs(y2-y1));
        } else if (a.type === 'circle') {
            // circle: d.cx, d.cy (center %) + d.rx, d.ry (radius %)
            const cx = px(d.cx ?? 50), cy = py(d.cy ?? 50);
            const rx = px(d.rx ?? 10), ry = py(d.ry ?? 10);
            ctx.beginPath();
            ctx.ellipse(cx, cy, Math.max(1, rx), Math.max(1, ry), 0, 0, Math.PI * 2);
            ctx.stroke();
        } else if (a.type === 'line') {
            const x1 = px(d.x1 ?? 0), y1 = py(d.y1 ?? 0);
            const x2 = px(d.x2 ?? 0), y2 = py(d.y2 ?? 0);
            ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
            const angle = Math.atan2(y2 - y1, x2 - x1);
            const hs = Math.max(10, canvasW * 0.014);
            ctx.beginPath();
            ctx.moveTo(x2, y2);
            ctx.lineTo(x2 - hs*Math.cos(angle-Math.PI/6), y2 - hs*Math.sin(angle-Math.PI/6));
            ctx.lineTo(x2 - hs*Math.cos(angle+Math.PI/6), y2 - hs*Math.sin(angle+Math.PI/6));
            ctx.closePath(); ctx.fill();
        } else if (a.type === 'number') {
            // number: d.x, d.y (center %)
            const cx = px(d.x ?? 50), cy = py(d.y ?? 50);
            const r  = Math.max(12, canvasW * 0.018);
            ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.fillStyle = col; ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.font = `bold ${Math.round(r * 1.2)}px sans-serif`;
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(String(d.n ?? ''), cx, cy);
        } else if (a.type === 'text') {
            const x = px(d.x ?? 0), y = py(d.y ?? 0);
            const fs = Math.max(13, canvasW * 0.018);
            ctx.font = `bold ${fs}px sans-serif`;
            ctx.fillStyle = col;
            const lines = (d.text || '').split('\n');
            lines.forEach((ln, i) => ctx.fillText(ln, x, y + i * (fs + 4)));
        }
    });
}

async function _buildAnnotatedPdf(serveUrl, allAnns, fileName) {
    const { jsPDF } = window.jspdf;
    if (!window.pdfjsLib) { alert(FM_STR.pdfjs_not_loaded); return; }

    const SCALE = 1.5;
    const loadingTask = pdfjsLib.getDocument({ url: serveUrl, withCredentials: false });
    const pdf = await loadingTask.promise;
    const total = pdf.numPages;

    let doc = null;
    for (let p = 1; p <= total; p++) {
        const page = await pdf.getPage(p);
        const vp   = page.getViewport({ scale: SCALE });
        const pw   = Math.round(vp.width);
        const ph   = Math.round(vp.height);

        const orient = pw >= ph ? 'l' : 'p';
        if (!doc) {
            doc = new jsPDF({ unit: 'px', format: [pw, ph], compress: true, orientation: orient });
        } else {
            doc.addPage([pw, ph], orient);
        }

        const canvas = document.createElement('canvas');
        canvas.width  = pw;
        canvas.height = ph;
        const ctx = canvas.getContext('2d');
        await page.render({ canvasContext: ctx, viewport: vp }).promise;

        const pageAnns = allAnns.filter(a => (a.page ?? 1) === p);
        _drawAnnsOnCanvas(ctx, pageAnns, pw, ph);

        const imgData = canvas.toDataURL('image/jpeg', 0.92);
        doc.addImage(imgData, 'JPEG', 0, 0, pw, ph);
    }

    const base = fileName.replace(/\.[^.]+$/, '');
    doc.save(base + FM_STR.review_suffix);
}

function _trimContentBounds(canvas) {
    const w = canvas.width, h = canvas.height;
    const d = canvas.getContext('2d').getImageData(0, 0, w, h).data;
    const blank = (x, y) => { const i = (y * w + x) * 4; return d[i+3] < 8 || (d[i] > 242 && d[i+1] > 242 && d[i+2] > 242); };
    let bottom = h;
    for (let y = h - 1; y > 0; y--) {
        let hit = false;
        for (let x = 0; x < w; x += 3) { if (!blank(x, y)) { hit = true; break; } }
        if (hit) { bottom = y + 1; break; }
    }
    let right = w;
    for (let x = w - 1; x > 0; x--) {
        let hit = false;
        for (let y = 0; y < bottom; y += 3) { if (!blank(x, y)) { hit = true; break; } }
        if (hit) { right = x + 1; break; }
    }
    return { w: Math.min(right + 20, w), h: Math.min(bottom + 20, h) };
}

async function _buildAnnotatedImagePdf(serveUrl, anns, fileName) {
    const { jsPDF } = window.jspdf;

    const img = await new Promise((res, rej) => {
        const i = new Image(); i.crossOrigin = 'anonymous';
        i.onload = () => res(i); i.onerror = rej;
        i.src = serveUrl;
    });

    const canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    _drawAnnsOnCanvas(ctx, anns, canvas.width, canvas.height);

    const b = _trimContentBounds(canvas);
    const trimmed = document.createElement('canvas');
    trimmed.width = b.w; trimmed.height = b.h;
    trimmed.getContext('2d').drawImage(canvas, 0, 0, b.w, b.h, 0, 0, b.w, b.h);

    const imgData = trimmed.toDataURL('image/jpeg', 0.92);
    const doc = new jsPDF({ unit: 'px', format: [b.w, b.h], compress: true, orientation: b.w >= b.h ? 'l' : 'p' });
    doc.addImage(imgData, 'JPEG', 0, 0, b.w, b.h);

    const base = fileName.replace(/\.[^.]+$/, '');
    doc.save(base + FM_STR.review_suffix);
}

async function downloadAnnotatedPdf() {
    if (!_currentServeUrl || !_currentFileName) { alert(FM_STR.pdf_info_missing); return; }
    if (!window.jspdf) { alert(FM_STR.jspdf_not_loaded); return; }

    const btn = document.getElementById('ann-dl-btn');
    const origText = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> ${FM_STR.generating}`; }
    if (btn) {
        const r = btn.getBoundingClientRect();
        let dp = document.getElementById('sw-dlp-ann-fm');
        if (dp) dp.remove();
        dp = document.createElement('div'); dp.className = 'sw-dlp'; dp.id = 'sw-dlp-ann-fm';
        dp.style.top = (r.bottom + 3) + 'px'; dp.style.left = r.left + 'px'; dp.style.width = Math.max(r.width, 72) + 'px';
        dp.innerHTML = '<div class="sw-dlp-track"><div class="sw-dlp-fill sw-dlp-indet"></div></div><span class="sw-dlp-pct">···</span>';
        document.body.appendChild(dp);
    }

    try {
        if (_annPreviewType === 'pdf') {
            await _buildAnnotatedPdf(_currentServeUrl, annList, _currentFileName);
        } else {
            await _buildAnnotatedImagePdf(_currentServeUrl, annList, _currentFileName);
        }
        const dp = document.getElementById('sw-dlp-ann-fm');
        if (dp) { const f = dp.querySelector('.sw-dlp-fill'), p = dp.querySelector('.sw-dlp-pct'); if (f){f.classList.remove('sw-dlp-indet');f.style.width='100%';}if(p)p.textContent='✓'; setTimeout(function(){dp.remove();},1200); }
    } catch (e) {
        const dp = document.getElementById('sw-dlp-ann-fm'); if (dp) dp.remove();
        alert(FM_STR.pdf_build_failed.replace(':message', e.message));
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    }
}

/* ══════════════════════════════════════════════════
   삭제 확인 팝오버 (confirm() 대체)
══════════════════════════════════════════════════ */
function showDeleteConfirmPopover(targetBtn, message, onConfirm) {
    // 기존 팝오버 제거
    document.querySelectorAll('.sw-del-pop').forEach(p => p.remove());

    const pop = document.createElement('div');
    pop.className = 'sw-del-pop';
    pop.style.cssText = 'position:fixed;z-index:10050;background:#fff;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;box-shadow:0 10px 32px rgba(0,0,0,.18);min-width:220px;max-width:260px;font-family:inherit;';
    pop.innerHTML = `
        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;">
            <svg width="16" height="16" fill="none" stroke="#dc2626" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div style="font-size:13px;color:#374151;line-height:1.5;">${message || FM_STR.confirm_delete_short}</div>
        </div>
        <div style="display:flex;gap:6px;justify-content:flex-end;">
            <button type="button" class="sw-del-cancel" style="padding:5px 12px;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">${FM_STR.del_cancel}</button>
            <button type="button" class="sw-del-confirm" style="padding:5px 14px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">${FM_STR.del_confirm}</button>
        </div>`;

    document.body.appendChild(pop);

    // 위치 계산: 트리거 버튼 아래로 위치
    const r = targetBtn.getBoundingClientRect();
    requestAnimationFrame(() => {
        const ph = pop.offsetHeight;
        const pw = pop.offsetWidth;
        let top  = r.bottom + 6;
        if (top + ph > window.innerHeight - 8) top = Math.max(8, r.top - ph - 6);
        let left = r.right - pw;
        if (left < 8) left = 8;
        if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
        pop.style.top  = top  + 'px';
        pop.style.left = left + 'px';
    });

    const close = () => { pop.remove(); document.removeEventListener('mousedown', outside, true); };
    const outside = (e) => { if (!pop.contains(e.target) && e.target !== targetBtn) close(); };
    pop.querySelector('.sw-del-cancel').onclick  = close;
    pop.querySelector('.sw-del-confirm').onclick = () => { close(); onConfirm && onConfirm(); };
    setTimeout(() => document.addEventListener('mousedown', outside, true), 0);
}

/* ══════════════════════════════════════════════════
   동영상 뷰어 + 시간 기반 의견
══════════════════════════════════════════════════ */
let _vidPendingTime = null;
let _vidLastToastId = null;

function vidFmtTime(t) {
    if (!isFinite(t) || t < 0) t = 0;
    const m = Math.floor(t / 60);
    const s = Math.floor(t % 60);
    return `${m}:${String(s).padStart(2, '0')}`;
}

function vidInit(src) {
    const wrap = document.getElementById('viewer-video');
    const vid  = document.getElementById('vid-el');
    wrap.style.display = 'flex';
    document.getElementById('viewer-loading').style.display = 'none';
    _vidPendingTime = null;

    vid.onloadedmetadata = () => {
        vidUpdateTime();
        vidRenderMarkers();
    };
    vid.ontimeupdate = () => {
        vidUpdateTime();
        vidShowCommentToast();
    };
    vid.onplay  = () => { document.getElementById('vid-play-icon').innerHTML = '<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>'; };
    vid.onpause = () => { document.getElementById('vid-play-icon').innerHTML = '<path d="M8 5v14l11-7z"/>'; };

    // 에러 핸들러 — 미지원 코덱·잘못된 MIME·404 등 모든 케이스
    vid.onerror = () => {
        const err = vid.error;
        const codeMap = {1:FM_STR.vid_err_aborted, 2:FM_STR.vid_err_network, 3:FM_STR.vid_err_decode, 4:FM_STR.vid_err_src};
        const msg = err ? (codeMap[err.code] || FM_STR.vid_err_generic) : FM_STR.vid_err_cannot_play;
        const wrapInner = document.getElementById('vid-wrap');
        if (wrapInner && !document.getElementById('vid-err-msg')) {
            const e = document.createElement('div');
            e.id = 'vid-err-msg';
            e.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(20,17,35,.92);color:#f9a8a8;font-size:13px;gap:8px;padding:24px;text-align:center;';
            e.innerHTML = `<div style="font-size:32px;">🎬</div><div style="font-weight:700;">${FM_STR.vid_cannot_play_title}</div><div style="color:#fca5a5;font-size:12px;">${msg}</div>`;
            wrapInner.appendChild(e);
        }
    };

    // 타임라인 클릭/드래그/호버 → 탐색
    vidSetupTrack();

    // 버전 비교 임베드 모드 — 재생/탐색 동기화
    _cmpBindEmbedVideo(vid);

    // 이전 에러 토스트 제거 + src 재설정
    document.getElementById('vid-err-msg')?.remove();
    vid.src = src;
    try { vid.load(); } catch (_) {}
}

let _vidScrubbing = false;

function vidSetupTrack() {
    const wrap   = document.getElementById('vid-track-wrap');
    const thumb  = document.getElementById('vid-thumb');
    const hover  = document.getElementById('vid-hover-time');
    const track  = document.getElementById('vid-track');
    const prog   = document.getElementById('vid-progress');
    if (!wrap || wrap._vidBound) return;
    wrap._vidBound = true;

    const seekFromEvent = (e) => {
        const vid = document.getElementById('vid-el');
        if (!vid) return;
        const dur = vid.duration;
        if (!isFinite(dur) || dur <= 0) return;
        const r = wrap.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
        const pct = Math.max(0, Math.min(1, x / r.width));
        vid.currentTime = pct * dur;
    };

    const updateHover = (e) => {
        const vid = document.getElementById('vid-el');
        const dur = vid?.duration;
        if (!isFinite(dur) || dur <= 0) return;
        const r = wrap.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
        const pct = Math.max(0, Math.min(1, x / r.width));
        hover.style.left = (pct * 100) + '%';
        hover.textContent = vidFmtTime(pct * dur);
        hover.style.display = 'block';
    };

    // 마우스 클릭 (탭) → 즉시 이동
    wrap.addEventListener('click', (e) => {
        // 마커 클릭은 stopPropagation으로 여기 도달하지 않음
        seekFromEvent(e);
    });

    // 드래그 스크럽
    wrap.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        _vidScrubbing = true;
        track.style.height = '7px'; track.style.top = '6px';
        prog.style.height  = '7px'; prog.style.top  = '6px';
        seekFromEvent(e);
    });
    window.addEventListener('mousemove', (e) => {
        if (!_vidScrubbing) return;
        seekFromEvent(e);
        updateHover(e);
    });
    window.addEventListener('mouseup', () => {
        if (!_vidScrubbing) return;
        _vidScrubbing = false;
        track.style.height = '5px'; track.style.top = '7px';
        prog.style.height  = '5px'; prog.style.top  = '7px';
        hover.style.display = 'none';
    });

    // 호버 시 미리보기 시간 + thumb 위치
    wrap.addEventListener('mousemove', updateHover);
    wrap.addEventListener('mouseleave', () => {
        if (_vidScrubbing) return;
        hover.style.display = 'none';
    });
    wrap.addEventListener('mouseenter', () => {
        const vid = document.getElementById('vid-el');
        if (vid && isFinite(vid.duration) && vid.duration > 0) {
            thumb.style.display = 'block';
        }
    });
    // wrap leave → thumb 숨김
    wrap.addEventListener('mouseleave', () => {
        if (!_vidScrubbing) thumb.style.display = 'none';
    });

    // 터치 지원
    wrap.addEventListener('touchstart', (e) => { seekFromEvent(e); updateHover(e); }, { passive: true });
    wrap.addEventListener('touchmove',  (e) => { seekFromEvent(e); updateHover(e); }, { passive: true });
    wrap.addEventListener('touchend',   () => { hover.style.display = 'none'; });
}

function vidTeardown() {
    const wrap = document.getElementById('viewer-video');
    const vid  = document.getElementById('vid-el');
    if (wrap) wrap.style.display = 'none';
    if (vid) {
        try { vid.pause(); } catch (_) {}
        vid.onloadedmetadata = null;
        vid.ontimeupdate     = null;
        vid.onplay           = null;
        vid.onpause          = null;
        vid.onerror          = null;
        vid.removeAttribute('src');
        try { vid.load(); } catch (_) {}
    }
    const toast = document.getElementById('vid-comment-toast');
    if (toast) toast.style.display = 'none';
    document.getElementById('vid-err-msg')?.remove();
    _vidPendingTime = null;
    _vidLastToastId = null;
}

function vidTogglePlay() {
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    if (vid.paused) {
        const p = vid.play();
        if (p && p.catch) p.catch(() => {}); // NotSupported/Aborted 등 무시
    } else {
        vid.pause();
    }
}

function vidSeekRelative(delta) {
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    const dur = vid.duration;
    if (!isFinite(dur) || dur <= 0) return;
    const target = (vid.currentTime || 0) + delta;
    vid.currentTime = Math.max(0, Math.min(dur, target));
}

function vidSeekTo(time) {
    const vid = document.getElementById('vid-el');
    if (!vid || !vid.duration) return;
    vid.currentTime = Math.max(0, Math.min(vid.duration, time));
    if (vid.paused) vid.play().catch(() => {});
}

function vidUpdateTime() {
    const vid = document.getElementById('vid-el');
    const dur = vid.duration || 0;
    const cur = vid.currentTime || 0;
    document.getElementById('vid-time-label').textContent = `${vidFmtTime(cur)} / ${vidFmtTime(dur)}`;
    const pct = dur ? (cur / dur * 100) : 0;
    document.getElementById('vid-progress').style.width = pct + '%';
}

function vidRenderMarkers() {
    const vid = document.getElementById('vid-el');
    const markersEl = document.getElementById('vid-markers');
    if (!markersEl || !vid) return;
    const dur = vid.duration || 0;
    markersEl.innerHTML = '';
    if (!dur) return;
    _commentsList.filter(c => c.video_time != null).forEach(c => {
        const pct = Math.max(0, Math.min(100, (c.video_time / dur) * 100));
        const m = document.createElement('div');
        m.title = `${vidFmtTime(c.video_time)} — ${c.user_name}: ${c.content}`;
        m.dataset.cid = c.id;
        // hit area는 22px(전체 높이), 시각적 점은 가운데 14x14
        m.style.cssText = `position:absolute;left:${pct}%;top:-2px;width:22px;height:22px;margin-left:-11px;cursor:pointer;pointer-events:auto;display:flex;align-items:center;justify-content:center;`;
        m.innerHTML = `<span style="display:block;width:14px;height:14px;background:#a78bfa;border:2px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.4);transition:transform .12s, background .12s;pointer-events:none;"></span>`;
        // 호버 시 강조 + 의견 미리보기
        m.addEventListener('mouseenter', () => {
            m.firstElementChild.style.background = '#7c3aed';
            m.firstElementChild.style.transform  = 'scale(1.25)';
            const hover = document.getElementById('vid-hover-time');
            if (hover) {
                hover.style.left = pct + '%';
                hover.textContent = `${vidFmtTime(c.video_time)} — ${c.user_name}`;
                hover.style.display = 'block';
            }
        });
        m.addEventListener('mouseleave', () => {
            m.firstElementChild.style.background = '#a78bfa';
            m.firstElementChild.style.transform  = 'scale(1)';
        });
        // 부모(track-wrap)의 mousedown/move/click이 발동하지 않도록 모두 격리
        const stop = (e) => e.stopPropagation();
        m.addEventListener('mousedown',  stop);
        m.addEventListener('mouseup',    stop);
        m.addEventListener('mousemove',  stop);
        m.addEventListener('touchstart', stop, { passive: true });
        m.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            vid.currentTime = c.video_time;
            if (vid.paused) vid.play().catch(() => {});
        });
        markersEl.appendChild(m);
    });
}

function vidShowCommentToast() {
    const vid   = document.getElementById('vid-el');
    const toast = document.getElementById('vid-comment-toast');
    if (!toast) return;
    const cur = vid.currentTime || 0;

    // 현재 시점에 가까운(±0.6초 이내) 미표시 의견 찾기
    const hit = _commentsList.find(c => c.video_time != null
        && Math.abs(c.video_time - cur) <= 0.6
        && c.id !== _vidLastToastId);
    if (!hit) return;

    _vidLastToastId = hit.id;
    toast.innerHTML = `<div style="display:flex;gap:8px;align-items:flex-start;">
        <span style="flex-shrink:0;font-size:11px;font-weight:700;color:#c4b5fd;background:rgba(196,181,253,.15);padding:2px 8px;border-radius:10px;">${vidFmtTime(hit.video_time)}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:11px;font-weight:700;color:#a78bfa;margin-bottom:2px;">${escHtml(hit.user_name)}</div>
            <div style="color:#e5e7eb;word-break:keep-all;overflow-wrap:break-word;white-space:pre-wrap;">${escHtml(hit.content)}</div>
        </div>
        <button onclick="document.getElementById('vid-comment-toast').style.display='none'" style="flex-shrink:0;background:none;border:none;color:#9ca3af;font-size:16px;line-height:1;cursor:pointer;padding:0 2px;">×</button>
    </div>`;
    toast.style.display = 'block';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => { toast.style.display = 'none'; _vidLastToastId = null; }, 4000);
}

function vidPauseAndAddComment() {
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    vid.pause();
    _vidPendingTime = +(vid.currentTime || 0).toFixed(2);

    // 의견 패널 자동 열기
    const panel = document.getElementById('comment-panel');
    if (panel && panel.style.display === 'none') toggleCommentPanel();

    const ta = document.getElementById('comment-input');
    if (ta) {
        ta.placeholder = FM_STR.vid_comment_at_time.replace(':time', vidFmtTime(_vidPendingTime));
        ta.focus();
    }
}

/* ══════════════════════════════════════════════════
   의견 패널 펼침/접힘 토글
══════════════════════════════════════════════════ */
function toggleCommentPanel() {
    const panel  = document.getElementById('comment-panel');
    const handle = document.getElementById('comment-panel-handle');
    if (!panel || !handle) return;
    const collapsed = panel.style.display === 'none';
    if (collapsed) {
        panel.style.display = 'flex';
        handle.style.display = 'none';
        try { localStorage.setItem('sw-comment-panel-collapsed', '0'); } catch (_) {}
    } else {
        panel.style.display = 'none';
        handle.style.display = 'flex';
        try { localStorage.setItem('sw-comment-panel-collapsed', '1'); } catch (_) {}
    }
}

// 모달 열릴 때 저장된 접힘 상태 복원
function applyCommentPanelState() {
    let collapsed = '0';
    try { collapsed = localStorage.getItem('sw-comment-panel-collapsed') || '0'; } catch (_) {}
    const panel  = document.getElementById('comment-panel');
    const handle = document.getElementById('comment-panel-handle');
    if (!panel || !handle) return;
    if (collapsed === '1') {
        panel.style.display = 'none';
        handle.style.display = 'flex';
    } else {
        panel.style.display = 'flex';
        handle.style.display = 'none';
    }
}

// ── 버전 비교: 부모 ↔ 임베드 뷰어 메시지 통신 ──────────
window.addEventListener('message', function (e) {
    if (e.origin !== location.origin) return;
    const d = e.data;
    if (!d || typeof d.type !== 'string') return;

    if (IS_EMBED) {
        // 임베드(자식) 뷰어 — 부모로부터 동기화 지시 수신
        if (d.type === 'sw-cmp-goto' && typeof d.page === 'number') {
            if (typeof pdfDoc !== 'undefined' && pdfDoc) gotoPage(d.page);
        } else if (d.type === 'sw-cmp-vid-apply') {
            _cmpApplyVideo(d.action, d.time);
        }
        return;
    }

    // 부모(비교 모달) — 어느 iframe 에서 온 메시지인지 판별
    const fA = document.getElementById('compare-frame-a');
    const fB = document.getElementById('compare-frame-b');
    let which = null;
    if (fA && e.source === fA.contentWindow)      which = 'a';
    else if (fB && e.source === fB.contentWindow) which = 'b';
    if (!which) return;

    if (d.type === 'sw-cmp-ready') {
        if (which === 'a') { _cmpState.readyA = true; _cmpState.ptypeA = d.ptype; _cmpState.pageA = d.page || 1; }
        else               { _cmpState.readyB = true; _cmpState.ptypeB = d.ptype; _cmpState.pageB = d.page || 1; }
        _compareUpdateSyncAvailability();
    } else if (d.type === 'sw-cmp-page' && typeof d.page === 'number') {
        if (which === 'a') _cmpState.pageA = d.page;
        else               _cmpState.pageB = d.page;
        // 페이지 동일함 → 반대편 뷰어로 같은 페이지 전파
        const sync = document.getElementById('compare-sync-pages');
        if (sync && sync.checked && !sync.disabled && _cmpSyncMode === 'page') {
            _compareSendGoto(which === 'a' ? 'b' : 'a', d.page);
        }
    } else if (d.type === 'sw-cmp-vid') {
        const st = { action: d.action, time: d.time };
        if (which === 'a') _cmpState.vidA = st;
        else               _cmpState.vidB = st;
        // 재생 위치 동일함 → 반대편 뷰어로 재생/탐색 전파
        const sync = document.getElementById('compare-sync-pages');
        if (sync && sync.checked && !sync.disabled && _cmpSyncMode === 'video') {
            _compareSendVid(which === 'a' ? 'b' : 'a', d.action, d.time);
        }
    }
});

// ── 임베드 모드 초기화 — 비교 모달 iframe 안에서 로드된 경우 ──
if (IS_EMBED) {
    document.getElementById('preview-modal')?.classList.add('is-fullscreen');
    ['modal-close-btn', 'modal-compare-btn', 'modal-upload-version', 'preview-fs-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}
</script>
