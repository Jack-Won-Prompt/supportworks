@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.mk-show-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.mk-show-header-left { flex:1; min-width:0; }
.mk-show-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.mk-show-header-left p  { font-size:13px; color:#64748b; margin:0; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.mk-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.mk-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.mk-btn.primary:hover { background:var(--t700,#6d28d9); }
.mk-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.mk-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.mk-btn.secondary:hover { background:#e2e8f0; }
.mk-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.mk-btn.ghost:hover { background:#f5f3ff; }
.mk-btn.sm { padding:4px 10px; font-size:12px; }
.mk-btn.danger { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.mk-btn.danger:hover { background:#fee2e2; }

/* ── Section card ────────────────────────────────────────────────── */
.mk-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:14px; }
.mk-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 12px; display:flex; align-items:center; gap:7px; }

/* ── Split layout ────────────────────────────────────────────────── */
.mk-split { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:900px) { .mk-split { grid-template-columns:1fr; } }

/* ── Preview panel ───────────────────────────────────────────────── */
.mk-preview-wrap { border:1.5px solid #ede8ff; border-radius:12px; overflow:hidden; background:#f8f9fa; }
.mk-preview-toolbar { display:flex; align-items:center; gap:8px; padding:8px 12px; background:#fafafe; border-bottom:1px solid #ede8ff; }
.mk-preview-toolbar-title { font-size:12.5px; font-weight:700; color:#475569; flex:1; }
.mk-preview-iframe { width:100%; height:480px; border:none; background:#fff; display:block; }

/* ── Code editor ─────────────────────────────────────────────────── */
.mk-editor-wrap { border:1.5px solid #ede8ff; border-radius:10px; overflow:hidden; }
.mk-editor-toolbar { display:flex; align-items:center; gap:8px; padding:8px 12px; border-bottom:1px solid #ede8ff; background:#fafafe; flex-wrap:wrap; }
.mk-editor-toolbar-title { font-size:12.5px; font-weight:700; color:#475569; flex:1; }
.mk-textarea { width:100%; height:480px; padding:14px 16px; font-size:12.5px; line-height:1.75; color:#374151; font-family:'Courier New',monospace; border:none; resize:vertical; background:#fafafa; box-sizing:border-box; }
.mk-textarea:focus { outline:2px solid var(--t300,#c4b5fd); }

/* ── Info grid ───────────────────────────────────────────────────── */
.mk-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
.mk-info-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
.mk-info-value { font-size:13px; color:#1e1b2e; font-weight:600; }

/* ── Feature chips ───────────────────────────────────────────────── */
.mk-feature-chip { display:inline-flex; align-items:center; gap:3px; padding:3px 9px; background:#dcfce7; border:1.5px solid #bbf7d0; border-radius:99px; font-size:12px; font-weight:600; color:#15803d; margin:2px 3px 2px 0; }

/* ── Nav arrows ──────────────────────────────────────────────────── */
.mk-nav-arrow { padding:6px 11px; border-radius:8px; border:1.5px solid #e2e8f0; background:#fff; color:#475569; font-size:12.5px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.mk-nav-arrow:hover { background:#f1f5f9; }
.mk-nav-arrow.disabled { opacity:.3; pointer-events:none; }

/* ── Empty state ─────────────────────────────────────────────────── */
.mk-empty { background:#f8f5ff; border:1.5px dashed #c4b5fd; border-radius:12px; padding:36px; text-align:center; }
.mk-empty-title { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.mk-empty-text  { font-size:13px; color:#64748b; margin:0 0 16px; }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="mk-show-data">
{
    "generateUrl": "{{ $generateUrl }}",
    "updateUrl":   "{{ $updateUrl }}",
    "destroyUrl":  "{{ $destroyUrl }}",
    "previewUrl":  {{ json_encode($previewUrl) }},
    "csrfToken":   "{{ $csrfToken }}",
    "hasMockup":   {{ $artifact ? 'true' : 'false' }},
    "initialCode": {{ json_encode($data['main_file']['content'] ?? '') }}
}
</script>

<div x-data="mkShow()" x-init="init()">

    {{-- ── 헤더 ──────────────────────────────────────────────────────────── --}}
    <div class="mk-show-header">
        <div class="mk-show-header-left">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <a href="{{ $indexUrl }}" class="mk-btn secondary sm">← 목록</a>
                <span style="font-family:monospace;font-size:12.5px;font-weight:700;color:#7c3aed;background:#f8f5ff;padding:3px 9px;border-radius:6px;">{{ $screen->screen_id }}</span>
            </div>
            <h1>{{ $screen->title }} 목업</h1>
            <p>웍스가 생성한 목업 코드를 미리보기하고 편집합니다.</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            @if($prevScreen)
            <a href="{{ route('ai-agent.projects.planning.mockups.show', [$project, $prevScreen]) }}" class="mk-nav-arrow">← {{ $prevScreen->screen_id }}</a>
            @else
            <span class="mk-nav-arrow disabled">←</span>
            @endif
            @if($nextScreen)
            <a href="{{ route('ai-agent.projects.planning.mockups.show', [$project, $nextScreen]) }}" class="mk-nav-arrow">{{ $nextScreen->screen_id }} →</a>
            @else
            <span class="mk-nav-arrow disabled">→</span>
            @endif
        </div>
    </div>

    {{-- ── 목업 없을 때 ───────────────────────────────────────────────────── --}}
    <template x-if="!hasMockup && !generated">
        <div class="mk-empty">
            <div class="mk-empty-title">목업이 아직 없습니다</div>
            <div class="mk-empty-text">웍스가 화면 프롬프트를 분석하여 {{ $config?->frontend_stack?->label() ?? 'HTML' }} 코드를 자동 생성합니다.</div>
            <button class="mk-btn primary" @click="generate()" :disabled="generating">
                <template x-if="!generating">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </template>
                <span x-text="generating ? '웍스 생성 중...' : '웍스 목업 생성'"></span>
            </button>
        </div>
    </template>

    {{-- ── 목업 에디터 ────────────────────────────────────────────────────── --}}
    <template x-if="hasMockup || generated">
        <div>
            {{-- 액션 툴바 --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                <button class="mk-btn secondary" @click="generate()" :disabled="generating">
                    <span x-text="generating ? '웍스 재생성 중...' : '웍스 재생성'"></span>
                </button>
                <button class="mk-btn primary" @click="save()" :disabled="saving">
                    <span x-text="saving ? '저장 중...' : '저장'"></span>
                </button>
                <span x-show="saveStatus" x-text="saveStatus" style="font-size:12px;color:#64748b;"></span>
                <div style="margin-left:auto;display:flex;gap:8px;">
                    @if($standaloneUrl)
                    <a href="{{ $standaloneUrl }}" target="_blank" class="mk-btn ghost sm">전체화면 미리보기</a>
                    @endif
                    @if($downloadUrl)
                    <a href="{{ $downloadUrl }}" class="mk-btn secondary sm">다운로드</a>
                    @endif
                    @if($historyUrl)
                    <a href="{{ $historyUrl }}" class="mk-btn secondary sm">버전 이력</a>
                    @endif
                    <button class="mk-btn danger sm" @click="deleteMockup()" :disabled="deleting">
                        <span x-text="deleting ? '삭제 중...' : '삭제'"></span>
                    </button>
                </div>
            </div>

            {{-- 분할 화면: 미리보기 | 코드 에디터 --}}
            <div class="mk-split" style="margin-bottom:14px;">
                {{-- 미리보기 --}}
                <div class="mk-preview-wrap">
                    <div class="mk-preview-toolbar">
                        <span class="mk-preview-toolbar-title">미리보기</span>
                        <button onclick="reloadPreview()" class="mk-btn secondary sm">새로고침</button>
                    </div>
                    <template x-if="previewUrl">
                        <iframe
                            id="preview-frame"
                            :src="previewUrl"
                            class="mk-preview-iframe"
                            sandbox="allow-scripts"
                            title="{{ $screen->title }} 미리보기">
                        </iframe>
                    </template>
                    <template x-if="!previewUrl">
                        <div class="mk-preview-iframe" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;">
                            저장 후 미리보기가 표시됩니다.
                        </div>
                    </template>
                </div>

                {{-- 코드 에디터 --}}
                <div>
                    <div class="mk-editor-wrap">
                        <div class="mk-editor-toolbar">
                            <span class="mk-editor-toolbar-title">
                                코드 편집
                                @if($artifact)
                                    <span style="font-size:11px;font-weight:400;color:#94a3b8;">— {{ $data['main_file']['name'] ?? '' }}</span>
                                @endif
                            </span>
                            <span style="font-size:11px;color:#94a3b8;">(Ctrl+S 저장)</span>
                        </div>
                        <textarea
                            class="mk-textarea"
                            x-model="code"
                            @keydown.ctrl.s.prevent="save()"
                            spellcheck="false"
                            autocomplete="off"
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- 화면 정보 카드 --}}
            <div class="mk-section">
                <div class="mk-section-title">화면 / 목업 정보</div>
                <div class="mk-info-grid">
                    <div>
                        <div class="mk-info-label">화면 ID</div>
                        <div class="mk-info-value" style="font-family:monospace;color:#7c3aed;">{{ $screen->screen_id }}</div>
                    </div>
                    <div>
                        <div class="mk-info-label">스택</div>
                        <div class="mk-info-value">{{ $config?->frontend_stack?->label() ?? '미설정' }}</div>
                    </div>
                    @if($artifact)
                    <div>
                        <div class="mk-info-label">버전</div>
                        <div class="mk-info-value">
                            v{{ $artifact->version }}
                            @php $ct = $artifact->meta['change_type'] ?? ''; @endphp
                            @if($ct === 'user_edited')
                                <span style="font-size:11px;color:#1d4ed8;font-weight:400;">(사용자 편집)</span>
                            @else
                                <span style="font-size:11px;color:#15803d;font-weight:400;">(웍스 생성)</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="mk-info-label">토큰</div>
                        <div class="mk-info-value">{{ number_format(($artifact->meta['tokens_in'] ?? 0) + ($artifact->meta['tokens_out'] ?? 0)) }}</div>
                    </div>
                    <div>
                        <div class="mk-info-label">비용</div>
                        <div class="mk-info-value">${{ number_format($artifact->meta['cost_usd'] ?? 0, 4) }}</div>
                    </div>
                    @endif
                </div>

                @if($data['description'] ?? '')
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <div class="mk-info-label" style="margin-bottom:4px;">설명</div>
                    <div style="font-size:13px;color:#475569;line-height:1.6;">{{ $data['description'] }}</div>
                </div>
                @endif

                @if(!empty($data['features']))
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <div class="mk-info-label" style="margin-bottom:6px;">구현된 기능</div>
                    @foreach($data['features'] as $feat)
                        <span class="mk-feature-chip">✓ {{ $feat }}</span>
                    @endforeach
                </div>
                @endif

                @if(!empty($data['dependencies']))
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <div class="mk-info-label" style="margin-bottom:4px;">의존성</div>
                    <div style="font-size:12.5px;color:#64748b;">{{ implode(', ', $data['dependencies']) }}</div>
                </div>
                @endif

                @if($data['preview_notes'] ?? '')
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <div class="mk-info-label" style="margin-bottom:4px;">미리보기 주의사항</div>
                    <div style="font-size:12.5px;color:#92400e;background:#fef3c7;border-radius:7px;padding:7px 10px;">{{ $data['preview_notes'] }}</div>
                </div>
                @endif
            </div>

            {{-- T24 연결 배너 --}}
            <div style="background:linear-gradient(135deg,#1d4ed8,#1e40af);border-radius:12px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;color:#fff;">
                <div>
                    <div style="font-size:14px;font-weight:800;margin-bottom:2px;">화면 생성 프롬프트 (T24)</div>
                    <div style="font-size:12.5px;opacity:.85;">이 목업을 생성하는 데 사용된 프롬프트를 확인하고 편집할 수 있습니다.</div>
                </div>
                <a href="{{ route('ai-agent.projects.planning.prompts.show', [$project, $screen]) }}"
                   style="background:#fff;color:#1d4ed8;border:none;border-radius:9px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                    프롬프트 편집 →
                </a>
            </div>
        </div>
    </template>

</div>

@push('scripts')
<script>
async function mkShow() {
    const cfg = JSON.parse(document.getElementById('mk-show-data').textContent);

    return {
        cfg,
        hasMockup:  cfg.hasMockup,
        generated:  false,
        code:       cfg.initialCode || '',
        previewUrl: cfg.previewUrl,
        generating: false,
        saving:     false,
        deleting:   false,
        saveStatus: '',

        init() {},

        async generate() {
            if (this.generating) return;
            this.generating = true;
            this.saveStatus = '';
            try {
                const res  = await fetch(this.cfg.generateUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                });
                const json = await res.json();
                if (json.success) {
                    this.code       = json.code;
                    this.hasMockup  = true;
                    this.generated  = true;
                    this.previewUrl = this.cfg.previewUrl + '?v=' + Date.now();
                    this.saveStatus = `웍스 생성 완료 (v${json.version}) — 비용 $${(json.cost_usd || 0).toFixed(4)}`;
                    setTimeout(() => this.saveStatus = '', 5000);
                } else {
                    alert(json.message || '생성 실패');
                }
            } catch(e) {
                alert('오류: ' + e.message);
            }
            this.generating = false;
        },

        async save() {
            if (this.saving || !this.code.trim()) return;
            this.saving = true;
            this.saveStatus = '';
            try {
                const res  = await fetch(this.cfg.updateUrl, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body:    JSON.stringify({ code: this.code }),
                });
                const json = await res.json();
                if (json.success) {
                    this.saveStatus = `저장됨 (v${json.version})`;
                    this.previewUrl = this.cfg.previewUrl + '?v=' + Date.now();
                } else {
                    this.saveStatus = '저장 실패';
                }
            } catch(e) {
                this.saveStatus = '오류: ' + e.message;
            }
            this.saving = false;
            setTimeout(() => this.saveStatus = '', 3000);
        },

        async deleteMockup() {
            if (!await __confirm('이 화면의 목업을 삭제하시겠습니까?')) return;
            this.deleting = true;
            try {
                const res  = await fetch(this.cfg.destroyUrl, {
                    method:  'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                });
                const json = await res.json();
                if (json.success) {
                    this.hasMockup = false;
                    this.generated = false;
                    this.code      = '';
                } else {
                    alert(json.message || '삭제 실패');
                }
            } catch(e) {
                alert('오류: ' + e.message);
            }
            this.deleting = false;
        },
    };
}

async function reloadPreview() {
    const frame = document.getElementById('preview-frame');
    if (frame) frame.src = frame.src;
}
</script>
@endpush

@endsection
