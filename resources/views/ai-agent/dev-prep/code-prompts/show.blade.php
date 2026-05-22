@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────── */
.cp-show-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.cp-show-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cp-show-header-left .breadcrumb { font-size:12.5px; color:#94a3b8; margin:0 0 6px; }
.cp-show-header-right { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────── */
.cp-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.cp-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.cp-btn.primary:hover { background:var(--t700,#6d28d9); }
.cp-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.cp-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cp-btn.secondary:hover { background:#e2e8f0; }
.cp-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.cp-btn.ghost:hover { background:#f5f3ff; }
.cp-btn.danger    { background:#fef2f2; color:#b91c1c; border:1.5px solid #fecaca; }
.cp-btn.danger:hover { background:#fee2e2; }
.cp-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Section ────────────────────────────────────────────────── */
.cp-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.cp-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* ── Screen info ─────────────────────────────────────────────── */
.screen-meta { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.screen-meta-item { font-size:12.5px; color:#64748b; display:flex; align-items:center; gap:5px; }
.screen-meta-item strong { color:#1e1b2e; font-weight:700; }
.screen-id-badge { font-size:12px; font-weight:700; font-family:monospace; background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; border-radius:6px; padding:2px 8px; }

/* ── Prompt display ──────────────────────────────────────────── */
.prompt-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px; }
.prompt-toolbar-left { display:flex; align-items:center; gap:8px; }
.prompt-toolbar-right { display:flex; align-items:center; gap:6px; }

.prompt-view-area { background:#0f0f1a; border-radius:12px; padding:20px; font-family:'Fira Code','Cascadia Code','JetBrains Mono',monospace; font-size:13px; line-height:1.65; color:#e2e8f0; max-height:520px; overflow-y:auto; white-space:pre-wrap; word-break:break-word; position:relative; }
.prompt-edit-area { width:100%; min-height:480px; border:1.5px solid #e2e8f0; border-radius:12px; padding:16px; font-family:'Fira Code','Cascadia Code','JetBrains Mono',monospace; font-size:13px; line-height:1.65; color:#1e1b2e; resize:vertical; box-sizing:border-box; }
.prompt-edit-area:focus { outline:none; border-color:#7c3aed; }

/* ── Copy toast ──────────────────────────────────────────────── */
.copy-toast { position:fixed; bottom:24px; right:24px; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:600; z-index:999; opacity:0; transition:opacity .25s; pointer-events:none; }
.copy-toast.show { opacity:1; }

/* ── Empty / loading ─────────────────────────────────────────── */
.cp-empty-block { text-align:center; padding:50px 20px; }
.cp-empty-block p { font-size:14px; color:#94a3b8; margin-bottom:14px; }

/* ── Meta info ───────────────────────────────────────────────── */
.meta-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
.meta-chip { font-size:11.5px; font-weight:600; background:#f8f5ff; color:#7c6fa0; border:1px solid #ede8ff; border-radius:99px; padding:3px 10px; }

/* ── Confirm ─────────────────────────────────────────────────── */
.confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.confirm-box { background:#fff; border-radius:14px; padding:24px; max-width:380px; width:100%; }
.confirm-box h3 { font-size:15px; font-weight:800; color:#1e1b2e; margin:0 0 8px; }
.confirm-box p  { font-size:13px; color:#64748b; margin:0 0 20px; }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; }

@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')
<script type="application/json" id="cp-show-data">
{
    "hasPrompt": {{ $hasPrompt ? 'true' : 'false' }},
    "content": @if($hasPrompt){{ json_encode($artifact->content) }}@else null @endif,
    "artifactId": @if($hasPrompt){{ $artifact->id }}@else null @endif,
    "version": @if($hasPrompt){{ $artifact->version }}@else null @endif,
    "meta": @if($hasPrompt && $artifact->meta){{ json_encode($artifact->meta) }}@else {} @endif,
    "generateUrl": "{{ $generateUrl }}",
    "updateUrl": "{{ $updateUrl }}",
    "destroyUrl": "{{ $destroyUrl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="codePromptShow()" x-init="init()">

    {{-- 헤더 --}}
    <div class="cp-show-header">
        <div class="cp-show-header-left">
            <div class="breadcrumb">
                <a href="{{ $indexUrl }}" style="color:#7c3aed;text-decoration:none;">코드 생성 프롬프트 목록</a>
                <span style="margin:0 6px;">›</span>
                <span>{{ $screen->screen_id }}</span>
            </div>
            <h1>{{ $screen->title }}</h1>
            @if($screen->description)
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">{{ $screen->description }}</p>
            @endif
        </div>
        <div class="cp-show-header-right">
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="cp-btn ghost sm">버전 이력</a>
            @endif
            <button class="cp-btn danger sm" @click="showConfirm=true" x-show="hasPrompt">삭제</button>
        </div>
    </div>

    {{-- 화면 정보 --}}
    <div class="cp-section" style="padding:14px 18px;">
        <div class="screen-meta">
            <span class="screen-id-badge">{{ $screen->screen_id }}</span>
            @if($screen->figma_url)
            <a href="{{ $screen->figma_url }}" target="_blank" class="screen-meta-item" style="color:#7c3aed;text-decoration:none;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Figma 열기
            </a>
            @endif
            <span class="screen-meta-item">
                <span>프롬프트 상태:</span>
                <strong x-text="hasPrompt ? '생성됨' : '미생성'" :style="hasPrompt ? 'color:#16a34a' : 'color:#d97706'"></strong>
            </span>
            <template x-if="version">
                <span class="screen-meta-item">버전 <strong x-text="'v' + version"></strong></span>
            </template>
        </div>
    </div>

    {{-- 프롬프트 콘텐츠 --}}
    <div class="cp-section">
        <div class="cp-section-title">
            코드 생성 프롬프트
            <div style="margin-left:auto;display:flex;gap:8px;">
                <template x-if="hasPrompt && !isEditing">
                    <div style="display:flex;gap:8px;">
                        <button class="cp-btn ghost sm" @click="copyPrompt()">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            복사
                        </button>
                        <button class="cp-btn secondary sm" @click="startEdit()">편집</button>
                    </div>
                </template>
                <template x-if="isEditing">
                    <div style="display:flex;gap:8px;">
                        <button class="cp-btn primary sm" :disabled="isSaving" @click="saveEdit()">
                            <template x-if="isSaving"><svg style="width:12px;height:12px;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></template>
                            저장
                        </button>
                        <button class="cp-btn secondary sm" @click="cancelEdit()">취소</button>
                    </div>
                </template>
                <button class="cp-btn primary sm" :disabled="isGenerating" @click="generate()">
                    <template x-if="isGenerating"><svg style="width:12px;height:12px;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></template>
                    <span x-text="hasPrompt ? '재생성' : '생성'"></span>
                </button>
            </div>
        </div>

        {{-- 읽기 모드 --}}
        <template x-if="hasPrompt && !isEditing">
            <div>
                <div class="prompt-view-area" x-text="content"></div>
                <div class="meta-chips" x-show="Object.keys(meta).length > 0">
                    <template x-if="meta.model">
                        <span class="meta-chip" x-text="'모델: ' + meta.model"></span>
                    </template>
                    <template x-if="meta.tokens_in">
                        <span class="meta-chip" x-text="'입력 토큰: ' + meta.tokens_in"></span>
                    </template>
                    <template x-if="meta.tokens_out">
                        <span class="meta-chip" x-text="'출력 토큰: ' + meta.tokens_out"></span>
                    </template>
                    <template x-if="meta.cost_usd">
                        <span class="meta-chip" x-text="'비용: $' + meta.cost_usd"></span>
                    </template>
                    <template x-if="meta.generated_at">
                        <span class="meta-chip" x-text="'생성: ' + meta.generated_at.slice(0,16)"></span>
                    </template>
                </div>
            </div>
        </template>

        {{-- 편집 모드 --}}
        <template x-if="isEditing">
            <textarea class="prompt-edit-area" x-model="editContent"></textarea>
        </template>

        {{-- 미생성 --}}
        <template x-if="!hasPrompt && !isGenerating">
            <div class="cp-empty-block">
                <p>이 화면에 대한 코드 생성 프롬프트가 아직 없습니다.</p>
                <button class="cp-btn primary" @click="generate()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    웍스로 생성하기
                </button>
            </div>
        </template>

        {{-- 생성 중 --}}
        <template x-if="isGenerating">
            <div class="cp-empty-block">
                <p style="margin-bottom:6px;">웍스가 코드 생성 프롬프트를 작성하고 있습니다...</p>
                <p style="font-size:12px;">ERD, API 명세, RBAC, 디자인 시스템을 통합하여 분석 중입니다.</p>
            </div>
        </template>

        {{-- 에러 --}}
        <template x-if="error">
            <div style="margin-top:12px;padding:12px 14px;background:#fef2f2;border-radius:8px;border:1.5px solid #fecaca;font-size:12.5px;color:#b91c1c;" x-text="error"></div>
        </template>
    </div>

    {{-- 삭제 확인 모달 --}}
    <template x-if="showConfirm">
        <div class="confirm-overlay" @click.self="showConfirm=false">
            <div class="confirm-box">
                <h3>프롬프트 삭제</h3>
                <p>이 화면의 코드 생성 프롬프트를 삭제하시겠습니까? 버전 이력도 함께 삭제됩니다.</p>
                <div class="confirm-actions">
                    <button class="cp-btn secondary" @click="showConfirm=false">취소</button>
                    <button class="cp-btn danger" @click="destroy()">삭제</button>
                </div>
            </div>
        </div>
    </template>

    {{-- 복사 토스트 --}}
    <div class="copy-toast" :class="copyToast ? 'show' : ''">클립보드에 복사되었습니다</div>

</div>
@endsection

@push('scripts')
<script>
function codePromptShow() {
    return {
        cfg: {},
        hasPrompt: false,
        content: '',
        editContent: '',
        meta: {},
        version: null,
        isEditing: false,
        isGenerating: false,
        isSaving: false,
        error: null,
        showConfirm: false,
        copyToast: false,

        init() {
            const raw = document.getElementById('cp-show-data')?.textContent;
            if (raw) {
                const d = JSON.parse(raw);
                this.cfg       = d;
                this.hasPrompt = d.hasPrompt;
                this.content   = d.content ?? '';
                this.meta      = d.meta ?? {};
                this.version   = d.version;
            }
        },

        async generate() {
            if (this.isGenerating) return;
            this.isGenerating = true;
            this.error        = null;

            try {
                const res = await fetch(this.cfg.generateUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '생성 실패');

                this.content   = data.content;
                this.version   = data.version;
                this.hasPrompt = true;
                this.meta      = { model: data.model, tokens_in: data.tokens_in, tokens_out: data.tokens_out, cost_usd: data.cost_usd };
            } catch (e) {
                this.error = '생성 실패: ' + e.message;
            } finally {
                this.isGenerating = false;
            }
        },

        startEdit() {
            this.editContent = this.content;
            this.isEditing   = true;
        },

        cancelEdit() {
            this.isEditing = false;
        },

        async saveEdit() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.error    = null;

            try {
                const res = await fetch(this.cfg.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ content: this.editContent }),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '저장 실패');

                this.content   = this.editContent;
                this.version   = data.version;
                this.isEditing = false;
            } catch (e) {
                this.error = '저장 실패: ' + e.message;
            } finally {
                this.isSaving = false;
            }
        },

        async destroy() {
            this.showConfirm = false;
            try {
                const res = await fetch(this.cfg.destroyUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '삭제 실패');

                this.hasPrompt = false;
                this.content   = '';
                this.meta      = {};
                this.version   = null;
            } catch (e) {
                this.error = '삭제 실패: ' + e.message;
            }
        },

        copyPrompt() {
            if (!this.content) return;
            navigator.clipboard.writeText(this.content).then(() => {
                this.copyToast = true;
                setTimeout(() => { this.copyToast = false; }, 2200);
            });
        },
    };
}
</script>
@endpush
