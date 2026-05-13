@extends('layouts.ai-agent')
@section('title', 'Figma Dev Mode URL — 웍스 Agent')

@push('styles')
<style>
.dh-wrap     { max-width: 900px; }
.dh-card     { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:14px; }
.dh-card-ttl { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:12px; display:flex; align-items:center; gap:7px; }
.dh-card-ttl::after { content:''; flex:1; height:1px; background:#f1f5f9; }

/* Stat chips */
.dh-stat-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:4px; }
.dh-stat { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:12px 20px; border-radius:10px; border:1.5px solid #e2e8f0; background:#fff; }
.dh-stat-num { font-size:28px; font-weight:800; font-family:monospace; line-height:1; }
.dh-stat-lbl { font-size:11px; color:#94a3b8; margin-top:3px; }

/* Screen table */
.dh-table { width:100%; border-collapse:collapse; }
.dh-table th { font-size:11.5px; font-weight:700; color:#64748b; text-align:left; padding:8px 10px; border-bottom:1.5px solid #e2e8f0; white-space:nowrap; }
.dh-table td { padding:10px 10px; border-bottom:1px solid #f1f5f9; font-size:12.5px; vertical-align:middle; }
.dh-table tr:hover td { background:#fafafa; }
.dh-screen-id { font-family:monospace; font-size:11.5px; font-weight:700; background:#f5f3ff; color:#7c3aed; padding:2px 7px; border-radius:4px; white-space:nowrap; }
.dh-screen-name { font-weight:600; color:#1e1b2e; font-size:13px; }
.dh-screen-desc { font-size:11.5px; color:#94a3b8; margin-top:2px; }

/* Links */
.dh-link { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:5px; font-size:11.5px; font-weight:600; text-decoration:none; border:1px solid; }
.dh-link-design { background:#f5f3ff; color:#7c3aed; border-color:#ddd6fe; }
.dh-link-design:hover { background:#ede9fe; }
.dh-link-dev { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
.dh-link-dev:hover { background:#dbeafe; }
.dh-link-map { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.dh-link-map:hover { background:#dcfce7; }

/* Validation badges */
.dh-vbadge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:2px 7px; border-radius:5px; }
.dh-vbadge.ok       { background:#dcfce7; color:#16a34a; }
.dh-vbadge.missing  { background:#fef2f2; color:#dc2626; }
.dh-vbadge.warning  { background:#fffbeb; color:#d97706; }
.dh-vbadge.checking { background:#f1f5f9; color:#64748b; }
.dh-vbadge.denied   { background:#fef2f2; color:#dc2626; }

/* Phase3 checklist */
.dh-phase3-row { display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid #f8fafc; }
.dh-phase3-row:last-child { border-bottom:none; }
.dh-phase3-icon { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:10px; font-weight:700; }
.dh-phase3-icon.ok      { background:#dcfce7; color:#16a34a; }
.dh-phase3-icon.missing { background:#fee2e2; color:#dc2626; }

/* Buttons */
.dh-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.dh-btn-primary { background:#7c3aed; color:#fff; }
.dh-btn-primary:hover { background:#6d28d9; color:#fff; }
.dh-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.dh-btn-outline:hover { border-color:#a78bfa; color:#7c3aed; }
.dh-btn-danger { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.dh-btn-danger:hover { background:#fee2e2; }
.dh-btn:disabled { opacity:.5; cursor:not-allowed; }

/* Download dropdown */
.dh-dd { position:relative; display:inline-block; }
.dh-dd-menu { display:none; position:absolute; right:0; top:calc(100% + 4px); background:#fff; border:1.5px solid #e2e8f0; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.08); z-index:50; min-width:180px; }
.dh-dd.open .dh-dd-menu { display:block; }
.dh-dd-menu a { display:flex; align-items:center; gap:8px; padding:9px 14px; font-size:13px; color:#374151; text-decoration:none; }
.dh-dd-menu a:hover { background:#f8fafc; }

/* Validation result panel */
.dh-val-panel { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-top:12px; }
.dh-val-row { display:flex; align-items:center; gap:10px; padding:5px 0; font-size:12.5px; }

/* Toast */
.dh-toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:500; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.dh-toast.show { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('page-actions')
<div x-data="dhActions(@json([
    'validateUrl' => route('ai-agent.projects.design.figma-dev.validate', $project),
    'generateUrl' => route('ai-agent.projects.design.figma-dev.generate', $project),
    'csrfToken'   => csrf_token(),
]))" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

    <div class="dh-toast" :class="{ show: toast.show }" x-text="toast.msg"></div>

    {{-- Validate --}}
    <button class="dh-btn dh-btn-outline" @click="doValidate()" :disabled="validating">
        <span x-text="validating ? '검증 중...' : '🔍 URL 유효성 검증'"></span>
    </button>

    {{-- Generate artifact --}}
    <button class="dh-btn dh-btn-primary" @click="doGenerate()" :disabled="generating">
        <span x-text="generating ? '생성 중...' : '📄 핸드오프 산출물 생성'"></span>
    </button>

    {{-- Export dropdown --}}
    <div class="dh-dd" @click.outside="open = false" x-data="{ open: false }">
        <button class="dh-btn dh-btn-outline" @click="open = !open">내보내기 ▾</button>
        <div class="dh-dd-menu">
            <a href="{{ route('ai-agent.projects.design.figma-dev.export', $project) }}?format=md" target="_blank">📝 Markdown</a>
            <a href="{{ route('ai-agent.projects.design.figma-dev.export', $project) }}?format=csv" target="_blank">📊 CSV</a>
            <hr style="border:none;border-top:1px solid #f1f5f9;margin:4px 0;">
            <a href="{{ route('ai-agent.projects.design.figma-dev.package', $project) }}">📦 통합 패키지 (zip)</a>
        </div>
    </div>

    <script>
    function dhActions(cfg) {
        return {
            cfg,
            validating: false,
            generating: false,
            toast: { show: false, msg: '' },
            showToast(msg) { this.toast = { show: true, msg }; setTimeout(() => this.toast.show = false, 3500); },
            async doValidate() {
                this.validating = true;
                try {
                    const res = await axios.post(this.cfg.validateUrl, { _token: this.cfg.csrfToken });
                    if (res.data.success) {
                        const s = res.data.summary;
                        this.showToast(`검증 완료: 정상 ${s.valid}건 / 오류 ${s.invalid}건`);
                        // Refresh to show results in table
                        window._dhValidationResults = res.data.results;
                        this.$dispatch('validation-done', res.data);
                    } else {
                        this.showToast(res.data.message);
                    }
                } catch (e) { this.showToast(e.response?.data?.message ?? '검증 실패'); }
                this.validating = false;
            },
            async doGenerate() {
                this.generating = true;
                try {
                    const res = await axios.post(this.cfg.generateUrl, { _token: this.cfg.csrfToken });
                    if (res.data.success) {
                        this.showToast(res.data.message + ' v' + res.data.version);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        this.showToast(res.data.message);
                    }
                } catch (e) { this.showToast(e.response?.data?.message ?? '생성 실패'); }
                this.generating = false;
            },
        };
    }
    </script>
</div>
@endsection

@section('ai-agent-content')
<div class="dh-wrap" x-data="dhPage()" @validation-done.window="onValidationDone($event.detail)">

    {{-- Stats --}}
    <div class="dh-card">
        <div class="dh-card-ttl">매핑 현황</div>
        <div class="dh-stat-row">
            <div class="dh-stat">
                <div class="dh-stat-num" style="color:#7c3aed;">{{ $stats['total'] }}</div>
                <div class="dh-stat-lbl">전체 화면</div>
            </div>
            <div class="dh-stat">
                <div class="dh-stat-num" style="color:#16a34a;">{{ $stats['mapped'] }}</div>
                <div class="dh-stat-lbl">매핑됨</div>
            </div>
            <div class="dh-stat">
                <div class="dh-stat-num" style="color:{{ $stats['unmapped'] > 0 ? '#dc2626' : '#94a3b8' }};">{{ $stats['unmapped'] }}</div>
                <div class="dh-stat-lbl">미매핑</div>
            </div>
            <div class="dh-stat">
                <div class="dh-stat-num" style="color:#2563eb;">{{ $stats['percent'] }}%</div>
                <div class="dh-stat-lbl">매핑률</div>
            </div>
        </div>
        @if($stats['unmapped'] > 0)
        <div style="margin-top:10px;padding:8px 12px;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:8px;font-size:12.5px;color:#92400e;">
            ⚠️ 미매핑 화면 {{ $stats['unmapped'] }}건이 있습니다.
            <a href="{{ route('ai-agent.projects.design.screens', $project) }}" style="color:#d97706;font-weight:600;text-decoration:none;">화면 매핑으로 이동 →</a>
        </div>
        @endif
    </div>

    {{-- Current artifact --}}
    @if($artifact)
    <div class="dh-card">
        <div class="dh-card-ttl">핸드오프 산출물</div>
        <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:14px;">
            <div style="font-size:22px;font-weight:800;font-family:monospace;color:#16a34a;">v{{ $artifact->version }}</div>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ $artifact->title }}</div>
                <div style="font-size:11.5px;color:#64748b;margin-top:2px;">
                    @if($artifact->meta['generated_at'] ?? null)
                    {{ \Carbon\Carbon::parse($artifact->meta['generated_at'])->format('Y-m-d H:i') }} 생성
                    @endif
                    · 매핑 {{ $artifact->meta['mapped_screens'] ?? 0 }}건
                    · 미매핑 {{ $artifact->meta['unmapped_screens'] ?? 0 }}건
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Validation result panel --}}
    <div x-show="validationDone" x-cloak class="dh-card">
        <div class="dh-card-ttl">
            URL 유효성 검증 결과
            <span style="font-size:11.5px;font-weight:600;color:#16a34a;" x-text="'✅ 정상 ' + validSummary.valid + '건'"></span>
            <span style="font-size:11.5px;font-weight:600;color:#dc2626;margin-left:8px;" x-text="validSummary.invalid > 0 ? '⚠️ 오류 ' + validSummary.invalid + '건' : ''"></span>
        </div>
        <div class="dh-val-panel">
            <template x-for="r in validationResults" :key="r.screen_id">
                <div class="dh-val-row">
                    <span class="dh-screen-id" x-text="r.screen_id"></span>
                    <span style="flex:1;font-size:12.5px;color:#374151;" x-text="r.name"></span>
                    <span class="dh-vbadge" :class="r.status === 'ok' ? 'ok' : (r.status === 'access_denied' ? 'denied' : 'missing')"
                          x-text="r.status === 'ok' ? '✅ 정상' : (r.status === 'access_denied' ? '🔒 권한없음' : '⚠️ 오류')"></span>
                    <span style="font-size:11px;color:#dc2626;" x-text="r.error ?? ''"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Screen table --}}
    @if(!empty($devData))
    <div class="dh-card">
        <div class="dh-card-ttl">화면별 Dev Mode 링크 ({{ count($devData) }}개)</div>
        <div style="overflow-x:auto;">
        <table class="dh-table">
            <thead>
                <tr>
                    <th>화면 ID</th>
                    <th>화면명</th>
                    <th>Figma 프레임</th>
                    <th>링크</th>
                    <th>적용 레이아웃</th>
                </tr>
            </thead>
            <tbody>
                @foreach($devData as $screen)
                <tr>
                    <td><span class="dh-screen-id">{{ $screen['screen_id'] }}</span></td>
                    <td>
                        <div class="dh-screen-name">{{ $screen['name'] }}</div>
                        @if($screen['description'])
                        <div class="dh-screen-desc">{{ Str::limit($screen['description'], 50) }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#64748b;">{{ $screen['figma']['frame_name'] ?? '-' }}</td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap;">
                            @if($screen['figma']['view_url'])
                            <a href="{{ $screen['figma']['view_url'] }}" target="_blank" class="dh-link dh-link-design">
                                Design
                            </a>
                            @endif
                            @if($screen['figma']['dev_url'])
                            <a href="{{ $screen['figma']['dev_url'] }}" target="_blank" class="dh-link dh-link-dev">
                                Dev 🔧
                            </a>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if(!empty($screen['standards']['applied_layouts']))
                        @foreach($screen['standards']['applied_layouts'] as $layout)
                        <span style="display:inline-block;background:#f5f3ff;color:#7c3aed;border-radius:4px;font-size:10.5px;padding:1px 6px;margin:1px;">{{ $layout['name'] }}</span>
                        @endforeach
                        @else
                        <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    {{-- Unmapped screens --}}
    @if(!empty($unmapped))
    <div class="dh-card">
        <div class="dh-card-ttl" style="color:#d97706;">미매핑 화면 ({{ count($unmapped) }}건)</div>
        <table class="dh-table">
            <thead>
                <tr>
                    <th>화면 ID</th>
                    <th>화면명</th>
                    <th>상태</th>
                    <th>조치</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unmapped as $screen)
                <tr>
                    <td><span class="dh-screen-id">{{ $screen['screen_id'] }}</span></td>
                    <td style="font-size:13px;color:#374151;font-weight:500;">{{ $screen['name'] }}</td>
                    <td><span style="color:#dc2626;font-size:12px;">❌ {{ $screen['reason'] }}</span></td>
                    <td>
                        <a href="{{ route('ai-agent.projects.design.screens', $project) }}" class="dh-link dh-link-map" style="font-size:11px;">
                            매핑하러 가기
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Phase 3 handoff package summary --}}
    <div class="dh-card">
        <div class="dh-card-ttl">Phase 3 → Phase 4 핸드오프 패키지</div>
        <p style="font-size:12.5px;color:#64748b;margin-bottom:12px;">
            아래 산출물들이 Phase 4 (개발) 입력 자료로 사용됩니다.
        </p>

        {{-- T28-T33 status --}}
        @foreach($designArtifacts as $da)
        <div class="dh-phase3-row">
            <div class="dh-phase3-icon {{ $da['ready'] ? 'ok' : 'missing' }}">
                {{ $da['ready'] ? '✓' : '✗' }}
            </div>
            <div style="flex:1;font-size:13px;color:#374151;font-weight:500;">{{ $da['label'] }}</div>
            <div style="font-size:11.5px;color:#94a3b8;">
                @if($da['ready']) v{{ $da['version'] }} @else 아직 없음 @endif
            </div>
        </div>
        @endforeach

        {{-- This page (T34) --}}
        <div class="dh-phase3-row">
            <div class="dh-phase3-icon {{ $artifact ? 'ok' : 'missing' }}">
                {{ $artifact ? '✓' : '✗' }}
            </div>
            <div style="flex:1;font-size:13px;color:#374151;font-weight:500;">Dev Handoff 목록 (T34)</div>
            <div style="font-size:11.5px;color:#94a3b8;">
                @if($artifact) v{{ $artifact->version }} @else 아직 없음 @endif
            </div>
        </div>

        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('ai-agent.projects.design.figma-dev.package', $project) }}"
               class="dh-btn dh-btn-primary">
                📦 통합 패키지 다운로드 (zip)
            </a>
            <span style="font-size:11.5px;color:#94a3b8;">T28–T34 모든 산출물이 포함됩니다</span>
        </div>
    </div>

    <script>
    function dhPage() {
        return {
            validationDone: false,
            validationResults: [],
            validSummary: { valid: 0, invalid: 0 },
            onValidationDone(data) {
                this.validationDone   = true;
                this.validationResults= data.results;
                this.validSummary     = data.summary;
            },
        };
    }
    </script>

</div>
@endsection
