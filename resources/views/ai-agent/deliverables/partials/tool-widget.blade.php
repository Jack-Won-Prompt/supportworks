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
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11.5px;padding:5px 10px;" onclick="aiAction('draft')">
                {{ __('deliverables.tool_ai_draft') }}
            </button>
        </div>

    @elseif($toolId === 'APPROVE')
        {{-- 승인 워크플로 --}}
        @once
        <style>
        .dlv-apr-chk { display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:11.5px;cursor:pointer;color:#374151;background:#fff; }
        .dlv-apr-chk:has(input:checked) { border-color:#7c3aed;background:#f5f3ff;color:#5b21b6;font-weight:600; }
        .dlv-apr-chk input { margin:0; }
        </style>
        @endonce
        <div id="approve-widget-{{ $stepNo }}" class="dlv-approve-widget">
            @php
                $myId        = auth()->id();
                $approvals   = $stepApprovals ?? collect();
                $total       = $approvals->count();
                $approvedCnt = $approvals->where('status', 'approved')->count();
                $rejectedCnt = $approvals->where('status', 'rejected')->count();
                $allApproved = $total > 0 && $approvedCnt === $total;
                $anyRejected = $rejectedCnt > 0;
                $isPending   = $total > 0 && !$allApproved && !$anyRejected;
                $iAmRequester= $total > 0 && $approvals->first()->requester_id === $myId;
                $myPending   = $approvals->first(fn($a) => $a->approver_id === $myId && $a->status === 'pending');
            @endphp

            {{-- 승인 현황 (멀티 승인자) --}}
            @if($total > 0)
            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;margin-bottom:8px;
                        background:{{ $allApproved ? '#f0fdf4' : ($anyRejected ? '#fef2f2' : '#faf5ff') }};
                        border:1px solid {{ $allApproved ? '#bbf7d0' : ($anyRejected ? '#fecaca' : '#ddd6fe') }};">
                <span style="font-size:18px;">{{ $allApproved ? '✅' : ($anyRejected ? '❌' : '⏳') }}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:11.5px;font-weight:700;color:{{ $allApproved ? '#15803d' : ($anyRejected ? '#b91c1c' : '#5b21b6') }};">
                        {{ $allApproved ? __('deliverables.approve_done') : ($anyRejected ? __('deliverables.approve_rejected_lbl') : __('deliverables.approve_pending')) }}
                        <span style="color:#94a3b8;font-weight:600;">({{ $approvedCnt }}/{{ $total }})</span>
                    </div>
                    <div style="font-size:10.5px;color:#64748b;margin-top:2px;">
                        {{ __('deliverables.approve_req_label') }}: {{ $approvals->first()->requester?->name ?? '-' }}
                    </div>
                </div>
            </div>
            {{-- 승인자별 상태 --}}
            <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:10px;">
                @foreach($approvals as $a)
                <div style="display:flex;align-items:center;gap:8px;font-size:11px;padding:5px 9px;border-radius:6px;background:#f8fafc;">
                    <span>{{ $a->status === 'approved' ? '✅' : ($a->status === 'rejected' ? '❌' : '⏳') }}</span>
                    <span style="font-weight:600;color:#374151;">{{ $a->approver?->name ?? '-' }}</span>
                    <span style="color:#94a3b8;">{{ $a->status === 'approved' ? __('deliverables.approve_done') : ($a->status === 'rejected' ? __('deliverables.approve_rejected_lbl') : __('deliverables.approve_pending')) }}</span>
                    @if($a->responded_at)<span style="color:#cbd5e1;margin-left:auto;">{{ $a->responded_at->format('m.d H:i') }}</span>@endif
                </div>
                @if($a->note)
                <div style="font-size:10.5px;color:#374151;padding:4px 8px;background:rgba(0,0,0,.04);border-radius:4px;margin-left:22px;">{{ $a->note }}</div>
                @endif
                @endforeach
            </div>
            @endif

            {{-- 내 승인 차례: 승인/반려 버튼 --}}
            @if($myPending)
            <div style="margin-bottom:10px;">
                <div style="font-size:11px;color:#64748b;margin-bottom:6px;">{{ __('deliverables.approve_my_turn') }}</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <button type="button" class="dlv-btn"
                            style="font-size:11.5px;padding:5px 14px;background:#16a34a;color:#fff;border:none;"
                            onclick="dlvApprovalRespond({{ $myPending->id }}, 'approved')">
                        {{ __('deliverables.approve_do') }}
                    </button>
                    <button type="button" class="dlv-btn dlv-btn-outline"
                            style="font-size:11.5px;padding:5px 14px;color:#b91c1c;border-color:#fecaca;"
                            onclick="dlvApprovalRejectPrompt({{ $myPending->id }})">
                        {{ __('deliverables.approve_reject_do') }}
                    </button>
                </div>
            </div>
            @endif

            {{-- 새 승인 요청 폼 (진행 중이 아니거나 내가 요청자, 전원 승인 전) — 승인자 멀티 선택 --}}
            @if((!$isPending || $iAmRequester) && !$allApproved)
            <div id="approve-form-{{ $stepNo }}">
                <div style="font-size:11px;color:#64748b;margin-bottom:7px;">
                    @if($anyRejected) {{ __('deliverables.approve_hint_rejected') }}
                    @else {{ __('deliverables.approve_hint_select') }}
                    @endif
                </div>
                <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex:1;min-width:180px;display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($projectMembers as $member)
                            @if($member->id !== auth()->id())
                            <label class="dlv-apr-chk">
                                <input type="checkbox" name="approver-{{ $stepNo }}" value="{{ $member->id }}">
                                {{ $member->name }}
                            </label>
                            @endif
                        @endforeach
                    </div>
                    <button type="button"
                            class="dlv-btn dlv-btn-outline"
                            style="font-size:11.5px;padding:5px 12px;white-space:nowrap;flex-shrink:0;"
                            onclick="dlvApprovalRequest({{ $stepNo }})">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('deliverables.tool_approve') }}
                    </button>
                </div>
            </div>
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
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">
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
            <div id="share-url-box" style="display:{{ $deliverable->share_token ? 'flex' : 'none' }};align-items:center;gap:8px;width:100%;margin-top:4px;">
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
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
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
        {{-- 탭 (편집 / 변경내용) --}}
        <div class="md-tabs">
            <button type="button" class="md-tab is-active" onclick="tblSwitchTab(this,'edit')">{{ __('deliverables.tab_edit') }}</button>
            <button type="button" class="md-tab" onclick="tblSwitchTab(this,'changes')">{{ __('deliverables.tab_changes') }}</button>
        </div>
        <div class="dlv-tbl-edit-pane">
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
        {{-- 변경내용 패널 --}}
        <div class="md-changes-pane" style="display:none;">
            <div class="md-changes-bar">
                <span class="md-changes-info"></span>
                <button type="button" class="md-changes-btn md-changes-accept" onclick="tblAcceptChanges(this)">{{ __('deliverables.changes_accept') }}</button>
                <button type="button" class="md-changes-btn md-changes-reject" onclick="tblRejectChanges(this)">{{ __('deliverables.changes_reject') }}</button>
            </div>
            <div class="md-changes-body" style="min-height:200px;"></div>
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

@elseif($toolId === 'MATRIX-RISK')
    {{-- 위험 평가 매트릭스 — 위험 등록부 + 5×5 히트맵 --}}
    @once
    <style>
    .dlv-risk-builder { font-size:11.5px; }
    .dlv-risk-table { width:100%; border-collapse:collapse; }
    .dlv-risk-table th { background:#f8fafc; color:#64748b; font-size:10.5px; font-weight:700; padding:6px 7px; border:1px solid #e2e8f0; text-align:center; white-space:nowrap; }
    .dlv-risk-table td { border:1px solid #e2e8f0; padding:4px 5px; vertical-align:middle; }
    .dlv-risk-table textarea { width:100%; border:none; resize:none; font:inherit; background:transparent; outline:none; overflow:hidden; padding:2px 3px; }
    .dlv-risk-table select { border:1px solid #e2e8f0; border-radius:5px; font:inherit; padding:3px 4px; background:#fff; cursor:pointer; }
    .dlv-risk-score { display:inline-block; min-width:30px; text-align:center; font-weight:800; border-radius:5px; padding:3px 6px; font-size:11px; }
    .dlv-risk-grade { display:inline-block; font-weight:700; border-radius:11px; padding:2px 9px; font-size:10px; white-space:nowrap; }
    .dlv-risk-g1 { background:#dcfce7; color:#15803d; }
    .dlv-risk-g2 { background:#fef9c3; color:#a16207; }
    .dlv-risk-g3 { background:#ffedd5; color:#c2410c; }
    .dlv-risk-g4 { background:#fee2e2; color:#b91c1c; }
    .dlv-risk-del { background:none; border:none; cursor:pointer; color:#cbd5e1; padding:2px; line-height:0; }
    .dlv-risk-del:hover { color:#ef4444; }
    .dlv-risk-heat { border-collapse:collapse; margin:0 auto; }
    .dlv-risk-heat td { width:34px; height:34px; text-align:center; border:1px solid #fff; font-weight:800; font-size:11px; color:#fff; }
    .dlv-risk-heat .dlv-heat-axis { background:transparent; color:#94a3b8; font-size:9.5px; font-weight:700; width:auto; height:auto; padding:2px 5px; border:none; }
    .dlv-risk-empty { text-align:center; color:#94a3b8; font-size:11px; padding:14px; }
    </style>
    @endonce
    @php
        $riskInit = json_encode($toolResult['risks'] ?? [], JSON_UNESCAPED_UNICODE);
    @endphp
    <div class="dlv-risk-builder" data-tool-id="{{ $toolId }}" data-step="{{ $stepNo }}" data-init='{{ $riskInit }}'>
        <div class="dlv-tbl-toolbar">
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="riskAddRow(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> {{ __('deliverables.risk_add') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-outline dlv-tbl-btn" onclick="riskAiGen(this)" style="color:var(--t600);border-color:var(--t300);">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> {{ __('deliverables.tw_ai_gen') }}
            </button>
            <span class="dlv-tbl-sep"></span>
            <button type="button" class="dlv-btn dlv-btn-primary dlv-tbl-btn" style="font-size:11px;padding:4px 12px;" onclick="riskSave(this)">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> {{ __('deliverables.tw_save') }}
            </button>
        </div>
        <div class="dlv-tbl-scroll" style="margin-top:6px;">
            <table class="dlv-risk-table">
                <thead><tr>
                    <th style="min-width:150px;">{{ __('deliverables.risk_col_name') }}</th>
                    <th>{{ __('deliverables.risk_col_like') }}</th>
                    <th>{{ __('deliverables.risk_col_impact') }}</th>
                    <th>{{ __('deliverables.risk_col_score') }}</th>
                    <th>{{ __('deliverables.risk_col_grade') }}</th>
                    <th style="min-width:160px;">{{ __('deliverables.risk_col_action') }}</th>
                    <th></th>
                </tr></thead>
                <tbody class="dlv-risk-rows"></tbody>
            </table>
        </div>
        <div class="dlv-risk-empty" style="display:none;">{{ __('deliverables.risk_empty') }}</div>
        <div style="margin-top:14px;display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;justify-content:center;">
            <div>
                <div style="font-size:10.5px;font-weight:700;color:#64748b;text-align:center;margin-bottom:4px;">{{ __('deliverables.risk_heatmap') }}</div>
                <table class="dlv-risk-heat"><tbody class="dlv-risk-heat-body"></tbody></table>
            </div>
        </div>
    </div>
    @php
        $riskGradesJs = [
            ['max'=>4,  'cls'=>'dlv-risk-g1', 'label'=>__('deliverables.risk_grade_low')],
            ['max'=>9,  'cls'=>'dlv-risk-g2', 'label'=>__('deliverables.risk_grade_mid')],
            ['max'=>14, 'cls'=>'dlv-risk-g3', 'label'=>__('deliverables.risk_grade_high')],
            ['max'=>25, 'cls'=>'dlv-risk-g4', 'label'=>__('deliverables.risk_grade_crit')],
        ];
        $riskStrJs = [
            'name_ph'   => __('deliverables.risk_name_ph'),
            'action_ph' => __('deliverables.risk_col_action'),
            'axis_like' => __('deliverables.risk_col_like'),
            'axis_imp'  => __('deliverables.risk_col_impact'),
        ];
    @endphp
    @once
    <script>
    (function () {
        const RISK_GRADES = @json($riskGradesJs);
        const RISK_HEAT = { 1:'#22c55e', 2:'#84cc16', 3:'#eab308', 4:'#f97316', 5:'#ef4444' };
        const RISK_STR = @json($riskStrJs);
        window.riskGrade = function (score) {
            for (const g of RISK_GRADES) if (score <= g.max) return g;
            return RISK_GRADES[RISK_GRADES.length - 1];
        };
        function heatColor(score) {
            if (score >= 15) return RISK_HEAT[5];
            if (score >= 10) return RISK_HEAT[4];
            if (score >= 5)  return RISK_HEAT[3];
            if (score >= 3)  return RISK_HEAT[2];
            return RISK_HEAT[1];
        }
        function autoH(t) { t.style.height = 'auto'; t.style.height = t.scrollHeight + 'px'; }
        function selOptions(sel, val) {
            for (let i = 1; i <= 5; i++) {
                const o = document.createElement('option');
                o.value = i; o.textContent = i;
                if (i === val) o.selected = true;
                sel.appendChild(o);
            }
        }
        function makeRow(b, data) {
            data = data || { name:'', likelihood:3, impact:3, mitigation:'' };
            const tr = document.createElement('tr');

            const tdName = document.createElement('td');
            const taName = document.createElement('textarea');
            taName.rows = 1; taName.placeholder = RISK_STR.name_ph; taName.value = data.name || '';
            taName.dataset.role = 'name';
            taName.addEventListener('input', () => autoH(taName));
            tdName.appendChild(taName); tr.appendChild(tdName);

            const tdL = document.createElement('td'); tdL.style.textAlign = 'center';
            const selL = document.createElement('select'); selL.dataset.role = 'like';
            selOptions(selL, parseInt(data.likelihood) || 3);
            tdL.appendChild(selL); tr.appendChild(tdL);

            const tdI = document.createElement('td'); tdI.style.textAlign = 'center';
            const selI = document.createElement('select'); selI.dataset.role = 'impact';
            selOptions(selI, parseInt(data.impact) || 3);
            tdI.appendChild(selI); tr.appendChild(tdI);

            const tdScore = document.createElement('td'); tdScore.style.textAlign = 'center';
            const score = document.createElement('span'); score.className = 'dlv-risk-score';
            tdScore.appendChild(score); tr.appendChild(tdScore);

            const tdGrade = document.createElement('td'); tdGrade.style.textAlign = 'center';
            const grade = document.createElement('span'); grade.className = 'dlv-risk-grade';
            tdGrade.appendChild(grade); tr.appendChild(tdGrade);

            const tdAct = document.createElement('td');
            const taAct = document.createElement('textarea');
            taAct.rows = 1; taAct.placeholder = RISK_STR.action_ph; taAct.value = data.mitigation || '';
            taAct.dataset.role = 'mitigation';
            taAct.addEventListener('input', () => autoH(taAct));
            tdAct.appendChild(taAct); tr.appendChild(tdAct);

            const tdDel = document.createElement('td'); tdDel.style.textAlign = 'center';
            const del = document.createElement('button');
            del.type = 'button'; del.className = 'dlv-risk-del';
            del.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            del.onclick = () => { tr.remove(); riskRefresh(b); };
            tdDel.appendChild(del); tr.appendChild(tdDel);

            function update() {
                const s = (parseInt(selL.value) || 0) * (parseInt(selI.value) || 0);
                const g = window.riskGrade(s);
                score.textContent = s;
                score.style.background = heatColor(s);
                score.style.color = '#fff';
                grade.textContent = g.label;
                grade.className = 'dlv-risk-grade ' + g.cls;
            }
            selL.addEventListener('change', () => { update(); riskHeat(b); });
            selI.addEventListener('change', () => { update(); riskHeat(b); });
            update();

            b.querySelector('.dlv-risk-rows').appendChild(tr);
            requestAnimationFrame(() => { autoH(taName); autoH(taAct); });
        }
        window.riskAddRow = function (btn) {
            const b = btn.closest('.dlv-risk-builder');
            makeRow(b);
            riskRefresh(b);
        };
        function riskRows(b) {
            return Array.from(b.querySelectorAll('.dlv-risk-rows tr')).map(tr => ({
                name:       tr.querySelector('[data-role=name]').value.trim(),
                likelihood: parseInt(tr.querySelector('[data-role=like]').value) || 0,
                impact:     parseInt(tr.querySelector('[data-role=impact]').value) || 0,
                mitigation: tr.querySelector('[data-role=mitigation]').value.trim(),
            }));
        }
        window.riskRows = riskRows;
        function riskRefresh(b) {
            const has = b.querySelectorAll('.dlv-risk-rows tr').length > 0;
            b.querySelector('.dlv-risk-empty').style.display = has ? 'none' : 'block';
            riskHeat(b);
        }
        window.riskRefresh = riskRefresh;
        function riskHeat(b) {
            const body = b.querySelector('.dlv-risk-heat-body');
            body.innerHTML = '';
            // 셀별 위험 개수 집계
            const counts = {};
            riskRows(b).forEach(r => {
                if (r.likelihood && r.impact) {
                    const k = r.likelihood + 'x' + r.impact;
                    counts[k] = (counts[k] || 0) + 1;
                }
            });
            // 행: 영향도 5→1 (위→아래), 열: 발생가능성 1→5
            for (let imp = 5; imp >= 1; imp--) {
                const tr = document.createElement('tr');
                const axis = document.createElement('td');
                axis.className = 'dlv-heat-axis'; axis.textContent = imp;
                tr.appendChild(axis);
                for (let like = 1; like <= 5; like++) {
                    const td = document.createElement('td');
                    const s = like * imp;
                    td.style.background = heatColor(s);
                    const c = counts[like + 'x' + imp] || 0;
                    td.textContent = c ? c : '';
                    td.title = RISK_STR.axis_like + ' ' + like + ' × ' + RISK_STR.axis_imp + ' ' + imp + ' = ' + s;
                    tr.appendChild(td);
                }
                body.appendChild(tr);
            }
            // X축 라벨
            const trX = document.createElement('tr');
            trX.appendChild(document.createElement('td'));
            for (let like = 1; like <= 5; like++) {
                const td = document.createElement('td');
                td.className = 'dlv-heat-axis'; td.textContent = like;
                trX.appendChild(td);
            }
            body.appendChild(trX);
            const trLbl = document.createElement('tr');
            const tdY = document.createElement('td');
            tdY.className = 'dlv-heat-axis'; tdY.textContent = RISK_STR.axis_imp;
            trLbl.appendChild(tdY);
            const tdX = document.createElement('td');
            tdX.className = 'dlv-heat-axis'; tdX.colSpan = 5;
            tdX.textContent = RISK_STR.axis_like;
            trLbl.appendChild(tdX);
            body.appendChild(trLbl);
        }
        window.riskHeat = riskHeat;
        window.riskSave = async function (btn) {
            const b = btn.closest('.dlv-risk-builder');
            const rows = riskRows(b).filter(r => r.name);
            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = LANG.saving;
            try {
                const res = await fetch(SAVE_TOOL_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, result: { risks: rows } }),
                });
                const json = await res.json();
                if (json.ok) {
                    btn.innerHTML = LANG.saved;
                    setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 1500);
                } else {
                    alert(json.message || LANG.save_failed);
                    btn.innerHTML = orig; btn.disabled = false;
                }
            } catch (e) {
                alert(LANG.save_error.replace(':message', e.message));
                btn.innerHTML = orig; btn.disabled = false;
            }
        };
        window.riskAiGen = async function (btn) {
            const b = btn.closest('.dlv-risk-builder');
            btn.disabled = true;
            const orig = btn.innerHTML;
            btn.innerHTML = LANG.ai_generating;
            try {
                const res = await fetch(ANALYZE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ action: 'risk-matrix', step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, fields: getFormData().fields }),
                });
                const json = await res.json();
                let risks = json.risks || json.result?.risks || null;
                if (!risks && typeof json.text === 'string') {
                    try { risks = JSON.parse(json.text).risks; } catch (e) {}
                }
                if (Array.isArray(risks) && risks.length) {
                    b.querySelector('.dlv-risk-rows').innerHTML = '';
                    risks.forEach(r => makeRow(b, r));
                    riskRefresh(b);
                    await fetch(SAVE_TOOL_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, result: { risks: riskRows(b).filter(r => r.name) } }),
                    });
                    btn.innerHTML = LANG.ai_gen_done;
                    setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 1800);
                    return;
                } else if (json.message) {
                    alert(json.message);
                }
            } catch (e) {
                alert(LANG.ai_gen_error.replace(':message', e.message));
            }
            btn.innerHTML = orig; btn.disabled = false;
        };
        function initRiskBuilder(b) {
            let data = [];
            try { data = JSON.parse(b.dataset.init || '[]'); } catch (e) {}
            if (Array.isArray(data)) data.forEach(r => makeRow(b, r));
            riskRefresh(b);
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.dlv-risk-builder').forEach(initRiskBuilder);
        });
        if (document.readyState !== 'loading') {
            document.querySelectorAll('.dlv-risk-builder').forEach(b => {
                if (!b.dataset.riskInit) { b.dataset.riskInit = '1'; initRiskBuilder(b); }
            });
        }
    })();
    </script>
    @endonce

@elseif($cat === 'matrix')
    {{-- 매트릭스 도구 --}}
    <div class="dlv-tool-placeholder">
        <div class="dlv-tool-placeholder-icon">
            <svg width="20" height="20" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M3 6h18M3 14h4m-4 4h4m5-4h9m-9 4h9"/></svg>
        </div>
        <div class="tool-id">{{ $toolId }}</div>
        <p>{{ $toolDef['name'] ?? $toolId }}</p>
        <p style="margin-top:6px;font-size:11px;color:#b0b8c9;">{{ __('deliverables.tool_matrix_desc') }}</p>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;">
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="aiAction('tool-generate')">{{ __('deliverables.tool_ai_generate') }}</button>
            <button type="button" class="dlv-btn dlv-btn-outline" style="font-size:11px;padding:4px 10px;" onclick="alert('{{ __('deliverables.tool_matrix_coming') }}')">{{ __('deliverables.tool_matrix_open') }}</button>
        </div>
    </div>

@elseif($toolId === 'FORM-CHECKLIST')
    {{-- 체크리스트 빌더: 컴팩트 가로형 --}}
    <div style="display:flex;align-items:center;gap:12px;padding:8px 10px;background:#faf5ff;border-radius:7px;">
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
        <div style="display:flex;gap:8px;margin-top:6px;">
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
