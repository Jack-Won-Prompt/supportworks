{{--
  도구 위젯 렌더러
  Variables: $toolId, $toolDef, $toolResult, $stepNo,
             $project, $deliverable, $typeId, $projectMembers, $stepApproval
--}}
@php
    $cat = $toolDef['category'] ?? 'system';
@endphp

@if(in_array($toolId, ['웍스-CHAT', 'VERSION', 'APPROVE', 'EXPORT']))
    {{-- 시스템 공통 도구 --}}

    @if($toolId === '웍스-CHAT')
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;" onclick="aiAction('draft')">
                {{ __('deliverables.tool_ai_draft') }}
            </button>
        </div>

    @elseif($toolId === 'APPROVE')
        {{-- 승인 워크플로 --}}
        <div id="approve-widget-{{ $stepNo }}" class="dlv-approve-widget">
            @php
                $myId     = auth()->id();
                $isPending  = $stepApproval && $stepApproval->status === 'pending';
                $isApproved = $stepApproval && $stepApproval->status === 'approved';
                $isRejected = $stepApproval && $stepApproval->status === 'rejected';
                $iAmApprover  = $stepApproval && $stepApproval->approver_id  === $myId;
                $iAmRequester = $stepApproval && $stepApproval->requester_id === $myId;
            @endphp

            {{-- 현재 승인 상태 배지 --}}
            @if($stepApproval)
            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;margin-bottom:10px;
                        background:{{ $isApproved ? '#f0fdf4' : ($isRejected ? '#fef2f2' : '#faf5ff') }};
                        border:1px solid {{ $isApproved ? '#bbf7d0' : ($isRejected ? '#fecaca' : '#ddd6fe') }};">
                <span style="font-size:18px;">{{ $isApproved ? '✅' : ($isRejected ? '❌' : '⏳') }}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:11.5px;font-weight:700;color:{{ $isApproved ? '#15803d' : ($isRejected ? '#b91c1c' : '#5b21b6') }};">
                        {{ $isApproved ? __('deliverables.approve_done') : ($isRejected ? __('deliverables.approve_rejected_lbl') : __('deliverables.approve_pending')) }}
                    </div>
                    <div style="font-size:10.5px;color:#64748b;margin-top:2px;">
                        {{ __('deliverables.approve_req_label') }}: {{ $stepApproval->requester?->name ?? '-' }}
                        · {{ __('deliverables.approve_apr_label') }}: {{ $stepApproval->approver?->name ?? '-' }}
                        @if($stepApproval->responded_at)
                        · {{ $stepApproval->responded_at->format('m.d H:i') }}
                        @endif
                    </div>
                    @if($stepApproval->note)
                    <div style="font-size:10.5px;color:#374151;margin-top:4px;padding:4px 8px;background:rgba(0,0,0,.04);border-radius:4px;">
                        {{ $stepApproval->note }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- 승인자에게: 승인/반려 버튼 --}}
            @if($isPending && $iAmApprover)
            <div style="margin-bottom:10px;">
                <div style="font-size:11px;color:#64748b;margin-bottom:6px;">{{ __('deliverables.approve_my_turn') }}</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <button type="button"
                            class="dlv-btn"
                            style="font-size:11.5px;padding:5px 14px;background:#16a34a;color:#fff;border:none;"
                            onclick="dlvApprovalRespond({{ $stepApproval->id }}, 'approved')">
                        {{ __('deliverables.approve_do') }}
                    </button>
                    <button type="button"
                            class="dlv-btn dlv-btn-outline"
                            style="font-size:11.5px;padding:5px 14px;color:#b91c1c;border-color:#fecaca;"
                            onclick="dlvApprovalRejectPrompt({{ $stepApproval->id }})">
                        {{ __('deliverables.approve_reject_do') }}
                    </button>
                </div>
            </div>
            @endif

            {{-- 새 승인 요청 폼 (pending 아닐 때 또는 내가 요청자가 아닐 때) --}}
            @if(!$isPending || $iAmRequester)
            @if(!$isApproved)
            <div id="approve-form-{{ $stepNo }}">
                <div style="font-size:11px;color:#64748b;margin-bottom:6px;">
                    @if($isRejected) {{ __('deliverables.approve_hint_rejected') }}
                    @elseif($isPending && $iAmRequester) {{ __('deliverables.approve_hint_pending') }}
                    @else {{ __('deliverables.approve_hint_select') }}
                    @endif
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <select id="approver-select-{{ $stepNo }}"
                            class="dlv-textarea"
                            style="flex:1;min-width:140px;height:32px;padding:4px 8px;font-size:11.5px;min-height:unset;">
                        <option value="">{{ __('deliverables.approve_select_ph') }}</option>
                        @foreach($projectMembers as $member)
                            @if($member->id !== auth()->id())
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <button type="button"
                            class="dlv-btn dlv-btn-outline"
                            style="font-size:11.5px;padding:5px 12px;white-space:nowrap;"
                            onclick="dlvApprovalRequest({{ $stepNo }})">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('deliverables.tool_approve') }}
                    </button>
                </div>
            </div>
            @endif
            @endif

        </div>

    @elseif($toolId === 'EXPORT')
        {{-- 다중 포맷 출력 패널 --}}
        @php
            // 현재 단계에 table 타입 필드가 있는지 확인 (Excel 버튼 노출 조건)
            $stepDef      = collect(config('deliverables.deliverables.' . $typeId . '.steps') ?? [])->firstWhere('order', $stepNo);
            $hasTableField = collect($stepDef['fields'] ?? [])->contains('type', 'table');
            $wordUrl       = route('ai-agent.projects.deliverables.export-word', [$project, $typeId]);
        @endphp
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:flex-start;">
            {{-- 뷰어 (팝업 모달) --}}
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;"
                    onclick="dlvOpenViewer()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                {{ __('deliverables.viewer') }}
            </button>
            {{-- 링크 공유 (뷰어와 독립) --}}
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;"
                    onclick="dlvToggleShare()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                {{ __('deliverables.tw_share_link') }}
            </button>
            {{-- Word 실제 다운로드 --}}
            <a href="{{ $wordUrl }}" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;text-decoration:none;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Word
            </a>
            {{-- Excel: 현재 단계에 표 필드가 있을 때만 --}}
            @if($hasTableField)
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;" onclick="alert('{{ __('deliverables.tw_excel_coming') }}')">
                Excel
            </button>
            @endif
            {{-- PDF --}}
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;" onclick="alert('{{ __('deliverables.tw_pdf_coming') }}')">
                PDF
            </button>
            {{-- 공유 URL 표시 (링크 공유 활성 시) --}}
            <div id="share-url-box" style="display:{{ $deliverable->share_token ? 'flex' : 'none' }};align-items:center;gap:6px;width:100%;margin-top:4px;">
                <input type="text" id="share-url-input" readonly
                       value="{{ $deliverable->share_token ? route('deliverables.public-share', $deliverable->share_token) : '' }}"
                       style="flex:1;font-size:10.5px;padding:4px 8px;border:1px solid #ddd6fe;border-radius:5px;background:#faf5ff;color:#5b21b6;outline:none;">
                <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:10.5px;padding:3px 8px;white-space:nowrap;"
                        onclick="dlvCopyShareUrl()">{{ __('deliverables.copy_link') }}</button>
                <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:10.5px;padding:3px 8px;color:#b91c1c;border-color:#fecaca;white-space:nowrap;"
                        onclick="dlvToggleShare()">{{ __('deliverables.tw_unshare') }}</button>
            </div>
        </div>

    @elseif($toolId === 'VERSION')
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;" onclick="alert('{{ __('deliverables.tool_version_coming') }}')">
                {{ __('deliverables.tool_version') }}
            </button>
        </div>
    @endif

@elseif($cat === 'table')
    {{-- 테이블 빌더 --}}
    @php
        // 이 도구와 연결된 table 타입 필드 key 찾기
        $stepDef2   = collect(config('deliverables.deliverables.' . $typeId . '.steps') ?? [])->firstWhere('order', $stepNo);
        $tblField   = collect($stepDef2['fields'] ?? [])->firstWhere('type', 'table');
        $tblFieldKey = $tblField['key'] ?? null;
        // 기존 저장 데이터: tool result JSON 우선, 없으면 textarea Markdown
        $tblInitMd  = '';
        if ($toolResult && !empty($toolResult['markdown'])) {
            $tblInitMd = $toolResult['markdown'];
        } elseif ($tblFieldKey) {
            $tblInitMd = $deliverable->getStepValue($stepNo, $tblFieldKey) ?? '';
        }
    @endphp
    <div class="dlv-tbl-builder"
         data-tool-id="{{ $toolId }}"
         data-step="{{ $stepNo }}"
         data-field-key="{{ $tblFieldKey }}"
         data-init-md="{{ e($tblInitMd) }}">
        {{-- 툴바 --}}
        <div class="dlv-tbl-toolbar">
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblAddRow(this)" title="{{ __('deliverables.tw_tbl_add_row') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> {{ __('deliverables.tw_row') }}
            </button>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblAddCol(this)" title="{{ __('deliverables.tw_tbl_add_col') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> {{ __('deliverables.tw_col') }}
            </button>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblDelRow(this)" title="{{ __('deliverables.tw_tbl_del_row') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg> {{ __('deliverables.tw_row') }}
            </button>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblDelCol(this)" title="{{ __('deliverables.tw_tbl_del_col') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg> {{ __('deliverables.tw_col') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblAiGen(this)" style="color:var(--t600);border-color:var(--t300);">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> {{ __('deliverables.tw_ai_gen') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="tblExcel(this)" style="color:#16a34a;border-color:#86efac;" title="{{ __('deliverables.tw_excel_dl') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> Excel
            </button>
        </div>
        {{-- 테이블 편집 영역 --}}
        <div class="dlv-tbl-scroll">
            <table class="dlv-tbl-edit"></table>
        </div>
        {{-- 저장 버튼 --}}
        <div style="display:flex;justify-content:flex-end;margin-top:6px;">
            <button type="button" class="dlv-btn dlv-btn-primary dlv-tbl-btn" style="font-size:11px;padding:4px 12px;" onclick="tblSave(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> {{ __('deliverables.tw_save') }}
            </button>
        </div>
    </div>

@elseif($cat === 'diagram')
    {{-- 다이어그램 빌더 --}}
    @php
        $dgrInitMd = $toolResult['mermaid'] ?? '';
        $dgrDefaults = [
            'DIAGRAM-FLOW' => "flowchart TD\n    A[시작] --> B{조건}\n    B -->|예| C[처리]\n    B -->|아니오| D[종료]\n    C --> D",
            'DIAGRAM-SEQ'  => "sequenceDiagram\n    participant 사용자\n    participant 시스템\n    사용자->>시스템: 요청\n    시스템-->>사용자: 응답",
            'DIAGRAM-ERD'  => "erDiagram\n    USER ||--o{ ORDER : places\n    ORDER ||--|{ LINE-ITEM : contains",
            'DIAGRAM-ARCH' => "flowchart TB\n    subgraph Frontend\n        UI[UI Layer]\n    end\n    subgraph Backend\n        API[API Server]\n        DB[(Database)]\n    end\n    UI --> API --> DB",
            'DIAGRAM-DFD'  => "flowchart LR\n    A([외부 개체]) --> B[프로세스]\n    B --> C[(데이터 저장소)]\n    C --> B",
            'DIAGRAM-NET'  => "flowchart TB\n    Internet --> Firewall\n    Firewall --> LB[Load Balancer]\n    LB --> App1 & App2\n    App1 & App2 --> DB[(DB)]",
            'DIAGRAM-LIFE' => "stateDiagram-v2\n    [*] --> 생성\n    생성 --> 활성\n    활성 --> 종료\n    종료 --> [*]",
        ];
        $dgrTemplate = $dgrDefaults[$toolId] ?? "flowchart TD\n    A[시작] --> B[끝]";
        if (!$dgrInitMd) $dgrInitMd = $dgrTemplate;
    @endphp
    <div class="dlv-dgr-builder"
         data-tool-id="{{ $toolId }}"
         data-step="{{ $stepNo }}">
        {{-- 상단 툴바 --}}
        <div class="dlv-tbl-toolbar" style="margin-bottom:6px;">
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="dgrAiGen(this)" style="color:var(--t600);border-color:var(--t300);">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> {{ __('deliverables.tw_ai_gen') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="dgrRender(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> {{ __('deliverables.tw_preview') }}
            </button>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="dgrDownloadPng(this)" title="{{ __('deliverables.tw_png_dl') }}">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> PNG
            </button>
            <button type="button" class="dlv-btn dlv-btn-primary dlv-tbl-btn" style="font-size:11px;padding:4px 12px;" onclick="dgrSave(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> {{ __('deliverables.tw_save') }}
            </button>
        </div>
        {{-- 분할 편집 창 --}}
        <div class="dlv-dgr-panes">
            <div class="dlv-dgr-code-pane">
                <div style="font-size:10px;color:#94a3b8;margin-bottom:3px;font-weight:600;">{{ __('deliverables.tw_mermaid_code') }}</div>
                <textarea class="dlv-dgr-textarea" spellcheck="false">{{ $dgrInitMd }}</textarea>
            </div>
            <div class="dlv-dgr-preview-pane">
                <div style="font-size:10px;color:#94a3b8;margin-bottom:3px;font-weight:600;">{{ __('deliverables.tw_preview') }} <span style="font-weight:400;color:#b0b8c9;">· {{ __('deliverables.tw_preview_hint') }}</span></div>
                <div class="dlv-dgr-pv-wrap">
                    <div class="dlv-dgr-preview mermaid"></div>
                    <button type="button" class="dlv-dgr-open-lb-btn"
                            onclick="dgrOpenLb(this.closest('.dlv-dgr-pv-wrap').querySelector('.dlv-dgr-preview'))"
                            title="{{ __('deliverables.tw_diagram_view_title') }}">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 3h6m0 0v6m0-6l-7 7M9 21H3m0 0v-6m0 6l7-7"/></svg>
                        {{ __('deliverables.tw_diagram_view') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

@elseif($cat === 'matrix')
    {{-- 매트릭스 도구 --}}
    <div class="dlv-tool-placeholder">
        <div class="dlv-tool-placeholder-icon">
            <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M3 6h18M3 14h4m-4 4h4m5-4h9m-9 4h9"/></svg>
        </div>
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ $toolDef['name'] ?? $toolId }}</p>
        <p style="margin-top:6px;font-size:11px;color:#b0b8c9;">{{ __('deliverables.tool_matrix_desc') }}</p>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:12px;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="aiAction('tool-generate')">{{ __('deliverables.tool_ai_generate') }}</button>
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="alert('{{ __('deliverables.tool_matrix_coming') }}')">{{ __('deliverables.tool_matrix_open') }}</button>
        </div>
    </div>

@elseif($toolId === 'FORM-CHECKLIST')
    {{-- 체크리스트 빌더: 컴팩트 가로형 --}}
    <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:#faf5ff;border-radius:7px;">
        <svg width="14" height="14" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span style="font-size:11.5px;font-weight:700;color:var(--t700);flex:1;">{{ $toolDef['name'] ?? $toolId }}</span>
        <span style="font-size:10px;color:#94a3b8;">{{ __('deliverables.tool_checklist_desc') }}</span>
        <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:3px 8px;" onclick="alert('{{ __('deliverables.tool_checklist_coming') }}')">{{ __('deliverables.tool_open') }}</button>
    </div>

@elseif($toolId === 'FORM-QA')
    {{-- 질의응답 폼 빌더 (OneTrust 스타일) --}}
    <div class="dlv-qa-builder"
         data-tool-id="FORM-QA"
         data-step="{{ $stepNo }}"
         data-init="{{ json_encode($toolResult ?? ['sections' => []]) }}">

        {{-- 툴바 --}}
        <div class="dlv-tbl-toolbar">
            <button type="button" class="dlv-btn dlv-tbl-btn" onclick="qaAddSection(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                {{ __('deliverables.tw_qa_add_section') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-primary dlv-tbl-btn" onclick="qaSave(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V7l-4-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 21v-8H7v8M7 3v5h8"/></svg>
                {{ __('deliverables.tw_save') }}
            </button>
            <button type="button" class="dlv-btn dlv-tbl-btn" onclick="qaAiGen(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                {{ __('deliverables.tw_qa_ai_draft') }}
            </button>
        </div>

        {{-- 섹션 목록 (JS가 data-init 기반으로 렌더링) --}}
        <div class="dlv-qa-sections"></div>

        {{-- 빈 상태 --}}
        <div class="dlv-qa-empty">
            <svg width="28" height="28" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            {{ __('deliverables.tw_qa_empty') }}
        </div>
    </div>

@elseif($cat === 'form')
    {{-- 폼/계산기 도구 (미구현) --}}
    <div class="dlv-tool-placeholder">
        <div class="dlv-tool-placeholder-icon">
            <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ $toolDef['name'] ?? $toolId }}</p>
        @if($toolId === 'FORM-SLA-CALC')
        <p style="margin-top:6px;font-size:11px;color:#b0b8c9;">{{ __('deliverables.tool_sla_desc') }}</p>
        @elseif($toolId === 'FORM-RPO-RTO')
        <p style="margin-top:6px;font-size:11px;color:#b0b8c9;">{{ __('deliverables.tool_rpo_desc') }}</p>
        @endif
        <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;margin-top:10px;" onclick="alert('{{ __('deliverables.tool_form_coming', ['tool' => $toolId]) }}')">{{ __('deliverables.tool_open') }}</button>
    </div>

@elseif($cat === 'runbook')
    {{-- Runbook 에디터 --}}
    <div>
        <div class="md-editor">
            <div class="md-tabs">
                <button type="button" class="md-tab is-active" onclick="mdSwitchTab(this,'preview')">{{ __('deliverables.tab_preview') }}</button>
                <button type="button" class="md-tab" onclick="mdSwitchTab(this,'edit')">{{ __('deliverables.tab_edit') }}</button>
                <button type="button" class="md-tab-tr" onclick="mdTranslate(this)" title="{{ __('deliverables.tab_translate_title') }}">🌐 {{ __('deliverables.tab_translate') }}</button>
            </div>
            <div class="md-edit-pane" style="display:none;">
                <textarea class="dlv-textarea" name="fields[runbook_{{ $stepNo }}]" style="height:200px;"
                          placeholder="{{ __('deliverables.tool_runbook_ph') }}">{{ $toolResult['content'] ?? '' }}</textarea>
            </div>
            <div class="md-preview-pane" style="height:200px;"></div>
        </div>
        <div style="display:flex;gap:6px;margin-top:6px;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="aiAction('draft')">{{ __('deliverables.tool_ai_draft') }}</button>
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="alert('{{ __('deliverables.tool_pdf_coming') }}')">{{ __('deliverables.tool_pdf_short') }}</button>
        </div>
    </div>

@elseif($cat === 'upload')
    {{-- 업로드 도구 --}}
    <div class="dlv-upload-box" onclick="document.getElementById('upload-{{ $toolId }}-{{ $stepNo }}').click()">
        <svg width="24" height="24" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" style="margin:0 auto 6px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
        <p style="font-size:12px;color:#64748b;margin:0;">{{ $toolDef['name'] ?? $toolId }}</p>
        <p style="font-size:11px;color:#94a3b8;margin:3px 0 0;">{{ __('deliverables.tool_upload_types') }}</p>
        <input type="file" id="upload-{{ $toolId }}-{{ $stepNo }}" style="display:none" multiple>
    </div>

@elseif($cat === 'visualization')
    {{-- 대시보드/타임라인 --}}
    <div class="dlv-tool-placeholder">
        <div class="dlv-tool-placeholder-icon">
            <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ $toolDef['name'] ?? $toolId }}</p>
        <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;margin-top:8px;" onclick="alert('{{ __('deliverables.tool_vis_coming', ['tool' => $toolId]) }}')">{{ __('deliverables.tool_preview_btn') }}</button>
    </div>

@elseif($cat === 'mapping')
    {{-- 매핑 도구 --}}
    <div class="dlv-tool-placeholder">
        <div class="dlv-tool-placeholder-icon">
            <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ __('deliverables.tool_mapping_title') }}</p>
        <p style="margin-top:6px;font-size:11px;color:#b0b8c9;">{{ __('deliverables.tool_mapping_desc') }}</p>
        <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;margin-top:8px;" onclick="aiAction('tool-generate')">{{ __('deliverables.tool_auto_map') }}</button>
    </div>

@else
    {{-- 기타 --}}
    <div class="dlv-tool-placeholder">
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ $toolDef['name'] ?? $toolId }}</p>
        <p style="font-size:11px;color:#b0b8c9;margin-top:4px;">{{ __('deliverables.tool_coming_soon') }}</p>
    </div>
@endif
