@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.sp-show-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.sp-show-header-left { flex:1; min-width:0; }
.sp-show-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sp-show-header-left p  { font-size:13px; color:#64748b; margin:0; }
.sp-show-nav { display:flex; align-items:center; gap:6px; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.sp-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.sp-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.sp-btn.primary:hover { background:var(--t700,#6d28d9); }
.sp-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.sp-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.sp-btn.secondary:hover { background:#e2e8f0; }
.sp-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.sp-btn.ghost:hover { background:#f5f3ff; }
.sp-btn.sm { padding:4px 10px; font-size:12px; }
.sp-btn.danger { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.sp-btn.danger:hover { background:#fee2e2; }

/* ── Section card ────────────────────────────────────────────────── */
.sp-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.sp-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 12px; display:flex; align-items:center; gap:7px; }

/* ── Screen info ─────────────────────────────────────────────────── */
.sp-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; }
.sp-info-item-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
.sp-info-item-value { font-size:13.5px; color:#1e1b2e; font-weight:600; }

/* ── Prompt editor ───────────────────────────────────────────────── */
.sp-editor-wrap { border:1.5px solid #ede8ff; border-radius:10px; overflow:hidden; }
.sp-editor-toolbar { display:flex; align-items:center; gap:8px; padding:9px 14px; border-bottom:1px solid #ede8ff; background:#fafafe; flex-wrap:wrap; }
.sp-editor-toolbar-title { font-size:12.5px; font-weight:700; color:#475569; flex:1; }
.sp-textarea { width:100%; min-height:520px; padding:16px 18px; font-size:13px; line-height:1.85; color:#374151; font-family:'Courier New',monospace; border:none; resize:vertical; background:#fafafa; box-sizing:border-box; }
.sp-textarea:focus { outline:2px solid var(--t300,#c4b5fd); }

/* ── Requirements ────────────────────────────────────────────────── */
.req-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:99px; font-size:12px; font-weight:600; color:#7c3aed; margin:3px 3px 3px 0; }

/* ── Empty state ─────────────────────────────────────────────────── */
.sp-empty { background:#f8f5ff; border:1.5px dashed #c4b5fd; border-radius:12px; padding:30px; text-align:center; }
.sp-empty-title { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.sp-empty-text  { font-size:13px; color:#64748b; margin:0 0 16px; }

/* ── Nav arrows ──────────────────────────────────────────────────── */
.sp-nav-arrow { padding:6px 11px; border-radius:8px; border:1.5px solid #e2e8f0; background:#fff; color:#475569; font-size:12.5px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
.sp-nav-arrow:hover { background:#f1f5f9; }
.sp-nav-arrow.disabled { opacity:.3; pointer-events:none; }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="sp-show-data">
{
    "generateUrl": "{{ $generateUrl }}",
    "updateUrl":   "{{ $updateUrl }}",
    "destroyUrl":  "{{ $destroyUrl }}",
    "csrfToken":   "{{ $csrfToken }}",
    "hasPrompt":   {{ $artifact ? 'true' : 'false' }},
    "initialContent": {{ json_encode($artifact?->content ?? '') }}
}
</script>

<div x-data="spShow()" x-init="init()">

    {{-- ── 헤더 ──────────────────────────────────────────────────────────── --}}
    <div class="sp-show-header">
        <div class="sp-show-header-left">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <a href="{{ $indexUrl }}" class="sp-btn secondary sm">← 목록</a>
                <span style="font-family:monospace;font-size:12.5px;font-weight:700;color:#7c3aed;background:#f8f5ff;padding:3px 9px;border-radius:6px;">{{ $screen->screen_id }}</span>
            </div>
            <h1>{{ $screen->title }} 프롬프트</h1>
            <p>화면 생성 프롬프트 — 편집 후 저장하거나 웍스로 재생성할 수 있습니다.</p>
        </div>
        <div class="sp-show-nav">
            @if($prevScreen)
            <a href="{{ route('ai-agent.projects.planning.prompts.show', [$project, $prevScreen]) }}" class="sp-nav-arrow">← {{ $prevScreen->screen_id }}</a>
            @else
            <span class="sp-nav-arrow disabled">←</span>
            @endif
            @if($nextScreen)
            <a href="{{ route('ai-agent.projects.planning.prompts.show', [$project, $nextScreen]) }}" class="sp-nav-arrow">{{ $nextScreen->screen_id }} →</a>
            @else
            <span class="sp-nav-arrow disabled">→</span>
            @endif
        </div>
    </div>

    {{-- ── 화면 정보 카드 ────────────────────────────────────────────────── --}}
    <div class="sp-section">
        <div class="sp-section-title">화면 정보</div>
        <div class="sp-info-grid">
            <div>
                <div class="sp-info-item-label">화면 ID</div>
                <div class="sp-info-item-value" style="font-family:monospace;color:#7c3aed;">{{ $screen->screen_id }}</div>
            </div>
            <div>
                <div class="sp-info-item-label">화면명</div>
                <div class="sp-info-item-value">{{ $screen->title }}</div>
            </div>
            <div>
                <div class="sp-info-item-label">스택</div>
                <div class="sp-info-item-value">{{ $config?->frontend_stack?->label() ?? '미설정' }}</div>
            </div>
            @if($artifact)
            <div>
                <div class="sp-info-item-label">프롬프트 버전</div>
                <div class="sp-info-item-value">v{{ $artifact->version }}
                    @if(($artifact->meta['change_type'] ?? '') === 'user_edited')
                        <span style="font-size:11px;color:#1d4ed8;font-weight:400;">(사용자 편집)</span>
                    @else
                        <span style="font-size:11px;color:#15803d;font-weight:400;">(웍스 생성)</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @if($screen->description)
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
            <div class="sp-info-item-label" style="margin-bottom:4px;">설명</div>
            <div style="font-size:13px;color:#475569;line-height:1.6;">{{ $screen->description }}</div>
        </div>
        @endif
        @if($relatedReqs->isNotEmpty())
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
            <div class="sp-info-item-label" style="margin-bottom:6px;">관련 요구사항</div>
            @foreach($relatedReqs as $req)
                <span class="req-chip" title="{{ $req->description }}">{{ $req->req_id }} {{ $req->title }}</span>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── 프롬프트 없을 때 ─────────────────────────────────────────────── --}}
    <template x-if="!hasPrompt && !generated">
        <div class="sp-empty">
            <div class="sp-empty-title">프롬프트가 아직 없습니다</div>
            <div class="sp-empty-text">웍스가 화면 정보와 기획서를 분석하여 목업 생성용 프롬프트를 자동 작성합니다.</div>
            <button class="sp-btn primary" @click="generate()" :disabled="generating">
                <template x-if="!generating">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </template>
                <span x-text="generating ? '웍스 생성 중...' : '웍스 프롬프트 생성'"></span>
            </button>
        </div>
    </template>

    {{-- ── 프롬프트 에디터 ──────────────────────────────────────────────── --}}
    <template x-if="hasPrompt || generated">
        <div>
            <div class="sp-section">
                <div class="sp-editor-wrap">
                    <div class="sp-editor-toolbar">
                        <span class="sp-editor-toolbar-title">프롬프트 편집</span>
                        <span style="font-size:11px;color:#94a3b8;">(Ctrl+S 저장)</span>
                        <span x-show="saveStatus" x-text="saveStatus" style="font-size:11.5px;color:#64748b;"></span>
                    </div>
                    <textarea class="sp-textarea" x-model="content" @keydown.ctrl.s.prevent="save()"></textarea>
                </div>

                <div style="margin-top:12px;display:flex;gap:8px;justify-content:space-between;flex-wrap:wrap;">
                    <div style="display:flex;gap:6px;">
                        @if($artifact && $historyUrl)
                        <a href="{{ $historyUrl }}" class="sp-btn secondary sm">버전 이력</a>
                        @endif
                        <button class="sp-btn danger sm" @click="deletePrompt()" :disabled="deleting">
                            <span x-text="deleting ? '삭제 중...' : '프롬프트 삭제'"></span>
                        </button>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="sp-btn secondary" @click="generate()" :disabled="generating">
                            <span x-text="generating ? '웍스 재생성 중...' : '웍스 재생성'"></span>
                        </button>
                        <button class="sp-btn primary" @click="save()" :disabled="saving">
                            <span x-text="saving ? '저장 중...' : '저장'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- T25 연결 배너 --}}
            <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);border-radius:12px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;color:#fff;">
                <div>
                    <div style="font-size:14px;font-weight:800;margin-bottom:2px;">이 프롬프트로 웍스 목업 생성</div>
                    <div style="font-size:12.5px;opacity:.85;">작성된 프롬프트를 이용해 HTML/React/Vue 목업을 자동 생성합니다.</div>
                </div>
                <a href="{{ $mockupUrl }}" style="background:#fff;color:#6d28d9;border:none;border-radius:9px;padding:8px 18px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                    목업 생성 → (T25)
                </a>
            </div>
        </div>
    </template>

</div>

@push('scripts')
<script>
async function spShow() {
    const cfg = JSON.parse(document.getElementById('sp-show-data').textContent);

    return {
        cfg,
        hasPrompt:  cfg.hasPrompt,
        generated:  false,
        content:    cfg.initialContent || '',
        generating: false,
        saving:     false,
        deleting:   false,
        saveStatus: '',

        init() {},

        async generate() {
            if (this.generating) return;
            this.generating = true;
            try {
                const res  = await fetch(this.cfg.generateUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                });
                const json = await res.json();
                if (json.success) {
                    this.content   = json.content;
                    this.hasPrompt = true;
                    this.generated = true;
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
            if (this.saving || !this.content.trim()) return;
            this.saving = true;
            this.saveStatus = '';
            try {
                const res  = await fetch(this.cfg.updateUrl, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body:    JSON.stringify({ content: this.content }),
                });
                const json = await res.json();
                this.saveStatus = json.success ? `저장됨 (v${json.version})` : '저장 실패';
            } catch(e) {
                this.saveStatus = '오류: ' + e.message;
            }
            this.saving = false;
            setTimeout(() => this.saveStatus = '', 3000);
        },

        async deletePrompt() {
            if (!await __confirm('이 화면의 프롬프트를 삭제하시겠습니까?')) return;
            this.deleting = true;
            try {
                const res  = await fetch(this.cfg.destroyUrl, {
                    method:  'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                });
                const json = await res.json();
                if (json.success) {
                    this.hasPrompt = false;
                    this.generated = false;
                    this.content   = '';
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
</script>
@endpush

@endsection
