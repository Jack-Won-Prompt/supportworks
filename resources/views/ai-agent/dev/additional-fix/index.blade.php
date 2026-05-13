@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.af-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.af-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.af-header p  { font-size:13.5px; color:#64748b; margin:0; }

.af-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.af-btn.primary    { background:#7c3aed; color:#fff; }
.af-btn.primary:hover { background:#6d28d9; }
.af-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.af-btn.danger     { background:#dc2626; color:#fff; }
.af-btn.danger:hover { background:#b91c1c; }
.af-btn.secondary  { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.af-btn.secondary:hover { background:#e2e8f0; }
.af-btn.ghost      { background:transparent; color:#7c3aed; border:1.5px solid #c4b5fd; }
.af-btn.ghost:hover { background:#f5f3ff; }
.af-btn.sm         { padding:4px 10px; font-size:12px; }

.af-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:10px; margin-bottom:18px; }
.af-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:12px 16px; }
.af-stat-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.af-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; margin-top:2px; }

.af-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:16px; }
.af-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

.af-quick-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }

.af-progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:10px; }
.af-progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.af-progress-log { background:#0f0f1a; border-radius:10px; padding:12px 14px; max-height:180px; overflow-y:auto; font-family:monospace; font-size:12px; color:#94a3b8; }
.af-progress-log-line.ok     { color:#4ade80; }
.af-progress-log-line.fail   { color:#f87171; }
.af-progress-log-line.active { color:#c4b5fd; }

/* Group card */
.af-group-card { border:1.5px solid #ede8ff; border-radius:12px; padding:16px 18px; margin-bottom:12px; background:#fff; }
.af-group-card.critical { border-left:4px solid #f87171; background:#fff9f9; }
.af-group-card.warning  { border-left:4px solid #fbbf24; background:#fffef9; }
.af-group-card.info     { border-left:4px solid #60a5fa; background:#f8fbff; }
.af-group-card.fixed    { opacity:.65; border-color:#bbf7d0; background:#f0fdf4; }
.af-group-card.ignored  { opacity:.45; border-color:#e2e8f0; }

.af-group-header { display:flex; align-items:flex-start; gap:10px; margin-bottom:8px; flex-wrap:wrap; }
.af-group-title  { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; min-width:180px; }
.af-sev-badge { font-size:10.5px; font-weight:700; text-transform:uppercase; border-radius:4px; padding:2px 8px; display:inline-block; flex-shrink:0; }
.af-sev-badge.critical { background:#fee2e2; color:#b91c1c; }
.af-sev-badge.warning  { background:#fef9c3; color:#a16207; }
.af-sev-badge.info     { background:#dbeafe; color:#1d4ed8; }
.af-cat-badge { font-size:10.5px; font-weight:600; background:#ede8ff; color:#7c3aed; border-radius:4px; padding:2px 8px; }
.af-status-badge { font-size:10.5px; font-weight:700; border-radius:4px; padding:2px 8px; }
.af-status-badge.fixed   { background:#dcfce7; color:#15803d; }
.af-status-badge.ignored { background:#f1f5f9; color:#94a3b8; }
.af-status-badge.pending { background:#f5f3ff; color:#7c3aed; }

.af-group-meta { display:flex; gap:12px; flex-wrap:wrap; font-size:12px; color:#64748b; margin-bottom:8px; }
.af-group-desc  { font-size:13px; color:#475569; margin-bottom:8px; line-height:1.6; }
.af-group-suggestion { font-size:12.5px; color:#334155; background:#f8fafc; border-radius:7px; padding:8px 12px; margin-bottom:8px; border-left:2px solid #c4b5fd; }
.af-group-affected { font-size:11.5px; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:2px 8px; display:inline-block; margin-right:4px; margin-bottom:2px; }
.af-auto-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; background:#dcfce7; color:#15803d; }
.af-manual-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:4px; background:#fef9c3; color:#a16207; }

.af-group-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
.af-act-btn { font-size:12px; padding:4px 12px; border-radius:7px; border:1.5px solid; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
.af-act-btn.fix     { border-color:#4ade80; color:#15803d; background:#f0fdf4; }
.af-act-btn.fix:hover { background:#dcfce7; }
.af-act-btn.manual  { border-color:#fbbf24; color:#a16207; background:#fffbeb; }
.af-act-btn.manual:hover { background:#fef9c3; }
.af-act-btn.ignore  { border-color:#cbd5e1; color:#64748b; background:#f8fafc; }
.af-act-btn.ignore:hover { background:#e2e8f0; }

/* Manual guide modal */
.af-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:60; display:flex; align-items:center; justify-content:center; padding:20px; }
.af-modal { background:#fff; border-radius:16px; padding:28px; max-width:560px; width:100%; max-height:80vh; overflow-y:auto; }
.af-modal h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 16px; }
.af-modal-section { margin-bottom:14px; }
.af-modal-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.af-modal-files { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px; }
.af-modal-file { font-size:11.5px; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:2px 8px; }
.af-modal-suggestion { font-size:13px; color:#334155; background:#f5f3ff; border-radius:8px; padding:12px 14px; line-height:1.65; }
.af-modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:16px; }

/* Ignore modal */
.af-ignore-modal { background:#fff; border-radius:16px; padding:24px; max-width:400px; width:100%; }
.af-ignore-modal h3 { font-size:15px; font-weight:800; margin:0 0 12px; }
.af-ignore-modal textarea { width:100%; border:1.5px solid #e2e8f0; border-radius:8px; padding:10px 12px; font-size:13px; resize:vertical; min-height:80px; box-sizing:border-box; }
.af-ignore-modal .actions { display:flex; gap:8px; justify-content:flex-end; margin-top:12px; }

.confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.confirm-box { background:#fff; border-radius:16px; padding:28px; max-width:420px; width:100%; }
.confirm-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 8px; }
.confirm-box p { font-size:13px; color:#64748b; margin:0 0 10px; }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="additionalFix()" x-init="init()">

    {{-- Header --}}
    <div class="af-header">
        <div>
            <h1>웍스 추가 수정 (T46)</h1>
            <p>T41 Output 검증 + T45 웍스 코드 리뷰에서 발견된 모든 이슈를 통합 관리합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($stats['total'] > 0)
            <a href="{{ $exportUrl }}" class="af-btn secondary">Markdown 내보내기</a>
            @endif
        </div>
    </div>

    @if($stats['total'] === 0)
    <div class="af-section" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:36px;margin-bottom:10px;">✅</div>
        <div style="font-weight:700;color:#475569;font-size:15px;">T41 · T45 이슈가 없습니다</div>
        <div style="font-size:13px;margin-top:6px;">T41 Output 검증과 T45 웍스 코드 리뷰를 먼저 실행하세요.</div>
        <div style="margin-top:18px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('ai-agent.projects.dev.code-validation', $project) }}" class="af-btn ghost">T41 Output 검증 →</a>
            <a href="{{ route('ai-agent.projects.dev.code-review', $project) }}" class="af-btn ghost">T45 웍스 코드 리뷰 →</a>
        </div>
    </div>
    @else

    {{-- Stats --}}
    <div class="af-stats">
        <div class="af-stat">
            <div class="af-stat-label">전체 그룹</div>
            <div class="af-stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="af-stat">
            <div class="af-stat-label">미해결</div>
            <div class="af-stat-value" style="color:#7c3aed;">{{ $stats['pending'] }}</div>
        </div>
        <div class="af-stat">
            <div class="af-stat-label">Critical</div>
            <div class="af-stat-value" style="color:{{ $stats['critical'] > 0 ? '#b91c1c' : '#15803d' }};">{{ $stats['critical'] }}</div>
        </div>
        <div class="af-stat">
            <div class="af-stat-label">Warning</div>
            <div class="af-stat-value" style="color:{{ $stats['warning'] > 0 ? '#a16207' : '#15803d' }};">{{ $stats['warning'] }}</div>
        </div>
        <div class="af-stat">
            <div class="af-stat-label">해결 완료</div>
            <div class="af-stat-value" style="color:#15803d;">{{ $stats['fixed'] }}</div>
        </div>
        <div class="af-stat">
            <div class="af-stat-label">무시됨</div>
            <div class="af-stat-value" style="color:#94a3b8;">{{ $stats['ignored'] }}</div>
        </div>
    </div>

    {{-- Quick actions --}}
    @if($stats['pending'] > 0)
    <div class="af-section">
        <div class="af-section-title">빠른 작업</div>
        <div class="af-quick-actions">
            @if($stats['critAuto'] > 0)
            <button class="af-btn danger" @click="startBatch('critical')">
                🔴 Critical만 자동 수정 ({{ $stats['critAuto'] }}건)
            </button>
            @endif
            @if($stats['autoFix'] > 0)
            <button class="af-btn primary" @click="startBatch('all')">
                ✨ 모두 자동 수정 ({{ $stats['autoFix'] }}건)
            </button>
            @endif
            <button class="af-btn secondary" @click="requestReverify()">
                🔍 수정 후 재검증 안내
            </button>
        </div>

        {{-- Progress area --}}
        <div x-show="fixing || progressLog.length > 0" x-cloak>
            <div class="af-progress-bar-wrap">
                <div class="af-progress-bar-fill" :style="`width:${progress}%`"></div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-bottom:8px;" x-text="progressMsg"></div>
            <div class="af-progress-log" x-ref="logBox">
                <template x-for="(line, i) in progressLog" :key="i">
                    <div class="af-progress-log-line" :class="line.cls" x-text="line.text"></div>
                </template>
            </div>
            <div style="margin-top:8px;display:flex;gap:8px;">
                <button x-show="fixing" @click="cancelFix()" class="af-btn secondary" style="font-size:12px;padding:4px 12px;">취소</button>
                <button x-show="!fixing && progressLog.length > 0" @click="progressLog=[]" class="af-btn secondary" style="font-size:12px;padding:4px 12px;">로그 지우기</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Groups --}}
    <div class="af-section">
        <div class="af-section-title">
            이슈 그룹 (우선순위 정렬)
            <span style="font-size:11px;font-weight:400;color:#94a3b8;">{{ $stats['total'] }}건 · 미해결 {{ $stats['pending'] }}건</span>
        </div>

        @forelse($groups as $group)
        @php
            $gkey    = $group['group_key'];
            $sev     = $group['severity'];
            $status  = $group['status'];
            $auto    = $group['auto_fixable'];
            $cardCls = $status !== 'pending' ? $status : $sev;
            $icon    = match($sev) { 'critical' => '🔴', 'warning' => '🟡', default => '🔵' };
        @endphp

        <div class="af-group-card {{ $cardCls }}">
            <div class="af-group-header">
                <div class="af-group-title">{{ $icon }} {{ $group['title'] }}</div>
                <span class="af-sev-badge {{ $sev }}">{{ strtoupper($sev) }}</span>
                <span class="af-cat-badge">{{ $group['category'] }}</span>
                <span class="af-status-badge {{ $status }}">{{ match($status) { 'fixed' => '✅ 해결', 'ignored' => '🚫 무시', default => '⏳ 미해결' } }}</span>
            </div>

            <div class="af-group-meta">
                <span>발생 {{ count($group['occurrences']) }}건</span>
                <span>영향 파일 {{ count($group['affected_files']) }}개</span>
                <span>출처: {{ strtoupper(implode(' + ', $group['sources'])) }}</span>
                @if($auto)
                <span class="af-auto-badge">✨ 자동 수정 가능</span>
                @else
                <span class="af-manual-badge">⚠️ 수동 필요</span>
                @endif
            </div>

            @if(!empty($group['description']))
            <div class="af-group-desc">{{ $group['description'] }}</div>
            @endif

            @if(!empty($group['suggestion']))
            <div class="af-group-suggestion">💡 {{ $group['suggestion'] }}</div>
            @endif

            @if(!empty($group['affected_files']))
            <div style="margin-bottom:8px;">
                @foreach(array_slice($group['affected_files'], 0, 5) as $file)
                <span class="af-group-affected">{{ $file }}</span>
                @endforeach
                @if(count($group['affected_files']) > 5)
                <span style="font-size:11.5px;color:#94a3b8;">+{{ count($group['affected_files']) - 5 }}개</span>
                @endif
            </div>
            @endif

            @if($status === 'pending')
            <div class="af-group-actions">
                @if($auto)
                <button class="af-act-btn fix"
                        @click="fixSingleGroup(@json($gkey), @json($group['title']))"
                        :disabled="fixingGroup === @json($gkey)">
                    <span x-show="fixingGroup !== @json($gkey)">✨ 자동 수정</span>
                    <span x-show="fixingGroup === @json($gkey)" x-cloak>수정 중...</span>
                </button>
                @endif
                <button class="af-act-btn manual"
                        @click="openManualGuide(@json($group))">
                    ✏️ 수동 가이드
                </button>
                <button class="af-act-btn ignore"
                        @click="openIgnoreModal(@json($gkey), @json($group['title']))">
                    🚫 무시
                </button>
            </div>
            @endif

            @if($status === 'fixed')
            <div style="font-size:12px;color:#15803d;margin-top:6px;">✅ 수정 완료</div>
            @endif
            @if($status === 'ignored')
            <div style="font-size:12px;color:#94a3b8;margin-top:6px;">🚫 무시됨</div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">이슈 그룹이 없습니다.</div>
        @endforelse
    </div>

    {{-- Error message --}}
    <div x-show="errorMsg" x-cloak style="background:#fff1f2;border:1.5px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:13px;color:#b91c1c;" x-text="errorMsg"></div>

    @endif

    {{-- Batch confirm modal --}}
    <div x-show="showBatchConfirm" x-cloak class="confirm-overlay" @click.self="showBatchConfirm=false">
        <div class="confirm-box">
            <h3>일괄 자동 수정 확인</h3>
            <p><span x-text="confirmGroupCount"></span>개 그룹 · <span x-text="confirmOccCount"></span>건 자동 수정</p>
            <p style="font-size:12px;color:#94a3b8;">수정된 파일은 T40/T43 새 버전으로 저장됩니다.</p>
            <div class="confirm-actions">
                <button @click="showBatchConfirm=false" class="af-btn secondary">취소</button>
                <button @click="confirmBatch()" class="af-btn primary">수정 시작</button>
            </div>
        </div>
    </div>

    {{-- Manual guide modal --}}
    <div x-show="showManual" x-cloak class="af-modal-overlay" @click.self="showManual=false">
        <div class="af-modal">
            <h3>✏️ 수동 수정 가이드</h3>
            <div class="af-modal-section">
                <div class="af-modal-label">그룹</div>
                <div style="font-size:14px;font-weight:700;color:#1e1b2e;" x-text="manualGroup.title"></div>
            </div>
            <div class="af-modal-section" x-show="manualGroup.affected_files && manualGroup.affected_files.length > 0">
                <div class="af-modal-label">영향 파일</div>
                <div class="af-modal-files">
                    <template x-for="f in (manualGroup.affected_files || []).slice(0, 8)" :key="f">
                        <span class="af-modal-file" x-text="f"></span>
                    </template>
                </div>
            </div>
            <div class="af-modal-section" x-show="manualGroup.description">
                <div class="af-modal-label">설명</div>
                <div style="font-size:13px;color:#334155;" x-text="manualGroup.description"></div>
            </div>
            <div class="af-modal-section" x-show="manualGroup.suggestion">
                <div class="af-modal-label">권장 수정 방법</div>
                <div class="af-modal-suggestion" x-text="manualGroup.suggestion"></div>
            </div>
            <div class="af-modal-section">
                <div class="af-modal-label">참고: 출처</div>
                <div style="font-size:12px;color:#64748b;" x-text="(manualGroup.sources || []).map(s => s.toUpperCase()).join(' + ')"></div>
            </div>
            <div class="af-modal-actions">
                <button @click="showManual=false" class="af-btn secondary">닫기</button>
                <button @click="markManual()" class="af-btn primary">수정 완료 표시</button>
            </div>
        </div>
    </div>

    {{-- Ignore reason modal --}}
    <div x-show="showIgnore" x-cloak class="af-modal-overlay" @click.self="showIgnore=false">
        <div class="af-ignore-modal">
            <h3>🚫 무시 처리</h3>
            <div style="font-size:13px;color:#475569;margin-bottom:10px;" x-text="'그룹: ' + ignoreTitle"></div>
            <textarea x-model="ignoreReason" placeholder="무시 사유 (선택)"></textarea>
            <div class="actions">
                <button @click="showIgnore=false" class="af-btn secondary">취소</button>
                <button @click="confirmIgnore()" class="af-btn primary">무시</button>
            </div>
        </div>
    </div>

    {{-- Reverify modal --}}
    <div x-show="showReverify" x-cloak class="af-modal-overlay" @click.self="showReverify=false">
        <div class="confirm-box">
            <h3>🔍 재검증 안내</h3>
            <p>자동 수정 후 T41 · T45를 순서대로 재실행하여 점수를 확인하세요.</p>
            <div class="confirm-actions" style="flex-direction:column;align-items:stretch;gap:8px;">
                <a href="{{ route('ai-agent.projects.dev.code-validation', $project) }}" class="af-btn primary" style="justify-content:center;">→ T41 Output 검증으로</a>
                <a href="{{ route('ai-agent.projects.dev.code-review', $project) }}" class="af-btn ghost" style="justify-content:center;">→ T45 웍스 코드 리뷰로</a>
                <button @click="showReverify=false" class="af-btn secondary" style="justify-content:center;">닫기</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const _GROUPS_URL       = @json(route('ai-agent.projects.dev.additional-fix.groups', $project));
const _FIX_URL_TPL      = @json(route('ai-agent.projects.dev.additional-fix.group.fix', [$project, 'GROUP_KEY']));
const _IGNORE_URL_TPL   = @json(route('ai-agent.projects.dev.additional-fix.group.ignore', [$project, 'GROUP_KEY']));
const _MANUAL_URL_TPL   = @json(route('ai-agent.projects.dev.additional-fix.group.manual', [$project, 'GROUP_KEY']));
const _BATCH_START_URL  = @json($batchStartUrl);
const _BATCH_SSE_TPLURL = @json($batchSseUrlTpl);
const _CANCEL_URL_TPL   = @json($cancelUrlTpl);

async function additionalFix() {
    return {
        fixing: false,
        fixingGroup: null,
        progress: 0,
        progressMsg: '',
        progressLog: [],
        errorMsg: '',
        sessionId: null,
        sse: null,
        pendingSeverity: 'all',

        showBatchConfirm: false,
        confirmGroupCount: 0,
        confirmOccCount: 0,

        showManual: false,
        manualGroup: {},

        showIgnore: false,
        ignoreKey: '',
        ignoreTitle: '',
        ignoreReason: '',

        showReverify: false,

        init() {},

        fixSingleGroup(groupKey, title) {
            if (!await __confirm(`"${title}" 그룹을 자동 수정하시겠습니까?`)) return;
            this.fixingGroup = groupKey;
            this.errorMsg = '';

            fetch(_FIX_URL_TPL.replace('GROUP_KEY', groupKey), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(data => {
                this.fixingGroup = null;
                if (data.success) {
                    this.addLog(`✓ "${title}" — ${data.occurrences_fixed}/${data.occurrences_total}건 수정`, 'ok');
                    setTimeout(() => location.reload(), 800);
                } else {
                    this.errorMsg = data.message || '수정 실패';
                }
            })
            .catch(e => { this.fixingGroup = null; this.errorMsg = e.message; });
        },

        startBatch(severity) {
            this.pendingSeverity = severity;

            fetch(_BATCH_START_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ severity_filter: severity, confirmed: false }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.requiresConfirmation) {
                    this.confirmGroupCount = data.groupCount;
                    this.confirmOccCount   = data.occurrencesCount;
                    this.showBatchConfirm  = true;
                }
            })
            .catch(e => this.errorMsg = e.message);
        },

        confirmBatch() {
            this.showBatchConfirm = false;
            this.fixing = true;
            this.progress = 0;
            this.progressLog = [];
            this.progressMsg = '준비 중...';

            fetch(_BATCH_START_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ severity_filter: this.pendingSeverity, confirmed: true }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || '세션 생성 실패');
                this.sessionId = data.sessionId;
                this.connectSse(data.sessionId);
            })
            .catch(e => { this.fixing = false; this.errorMsg = e.message; });
        },

        connectSse(sessionId) {
            const url = _BATCH_SSE_TPLURL.replace('SESSION_ID', sessionId);
            this.sse = new EventSource(url);

            const handle = (ev, data) => {
                if (ev === 'group_start') {
                    this.progress = data.progress || 0;
                    this.progressMsg = `[${data.severity?.toUpperCase()}] "${data.title}" 수정 중...`;
                    this.addLog(`▶ "${data.title}" (${data.occurrences_count}건)`, 'active');
                } else if (ev === 'group_done') {
                    this.progress = data.progress || 0;
                    this.addLog(`✓ "${data.title}" — ${data.occurrences_fixed}/${data.occurrences_total}건`, 'ok');
                } else if (ev === 'group_error') {
                    this.addLog(`✗ "${data.title}" — ${data.error}`, 'fail');
                } else if (ev === 'complete') {
                    this.progress = 100;
                    this.progressMsg = `완료 — ${data.total}그룹, ${data.total_fixed}건 수정, ${data.failed_groups}그룹 실패`;
                    this.addLog(`✅ 완료: ${data.total}그룹 처리`, 'ok');
                    this.fixing = false;
                    this.sse?.close();
                    setTimeout(() => location.reload(), 1500);
                } else if (ev === 'error') {
                    this.progressMsg = '오류: ' + data.message;
                    this.addLog('오류: ' + data.message, 'fail');
                    this.fixing = false;
                    this.sse?.close();
                }
            };

            ['status','start','group_start','group_done','group_error','complete','error'].forEach(ev => {
                this.sse.addEventListener(ev, e => handle(ev, JSON.parse(e.data)));
            });
            this.sse.onerror = () => { this.fixing = false; this.sse?.close(); };
        },

        cancelFix() {
            if (this.sessionId) {
                fetch(_CANCEL_URL_TPL.replace('SESSION_ID', this.sessionId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
            }
            this.sse?.close();
            this.fixing = false;
            this.progressMsg = '취소됨';
        },

        openManualGuide(group) {
            this.manualGroup = group;
            this.showManual  = true;
        },

        markManual() {
            const gkey = this.manualGroup.group_key;
            fetch(_MANUAL_URL_TPL.replace('GROUP_KEY', gkey), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(data => {
                this.showManual = false;
                if (data.success) {
                    setTimeout(() => location.reload(), 400);
                } else {
                    this.errorMsg = data.message || '수정 완료 표시 실패';
                }
            })
            .catch(e => { this.errorMsg = e.message; });
        },

        openIgnoreModal(key, title) {
            this.ignoreKey    = key;
            this.ignoreTitle  = title;
            this.ignoreReason = '';
            this.showIgnore   = true;
        },

        confirmIgnore() {
            fetch(_IGNORE_URL_TPL.replace('GROUP_KEY', this.ignoreKey), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ reason: this.ignoreReason }),
            })
            .then(r => r.json())
            .then(data => {
                this.showIgnore = false;
                if (data.success) {
                    setTimeout(() => location.reload(), 400);
                } else {
                    this.errorMsg = data.message || '무시 처리 실패';
                }
            })
            .catch(e => { this.errorMsg = e.message; });
        },

        requestReverify() {
            this.showReverify = true;
        },

        addLog(text, cls) {
            this.progressLog.push({ text, cls });
            this.$nextTick(() => {
                const box = this.$refs.logBox;
                if (box) box.scrollTop = box.scrollHeight;
            });
        },
    };
}
</script>
@endpush
