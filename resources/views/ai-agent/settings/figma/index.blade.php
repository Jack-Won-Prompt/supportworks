@extends('layouts.app')
@section('title', 'Figma 설정 — 웍스 Agent')

@push('styles')
<style>
.fg-wrap { max-width: 600px; margin: 0 auto; }
.fg-header { margin-bottom: 24px; }
.fg-header h1 { font-size: 20px; font-weight: 800; color: #1e1b2e; margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
.fg-header p  { font-size: 13.5px; color: #64748b; margin: 0; }

.fg-card { background: #fff; border: 1.5px solid #ede8ff; border-radius: 16px; padding: 24px 26px; margin-bottom: 16px; }
.fg-card-title { font-size: 13px; font-weight: 700; color: #1e1b2e; margin: 0 0 16px; display: flex; align-items: center; gap: 7px; }

.fg-label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px; display: block; }
.fg-input-wrap { position: relative; }
.fg-input { width: 100%; border: 1.5px solid #ddd6fe; border-radius: 10px; padding: 10px 40px 10px 14px; font-size: 13.5px; color: #1e1b2e; font-family: 'Courier New', monospace; outline: none; box-sizing: border-box; transition: border-color .15s; }
.fg-input:focus { border-color: var(--t500, #8b5cf6); }
.fg-toggle-btn { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 2px; }
.fg-toggle-btn:hover { color: #475569; }

.fg-status-row { display: flex; align-items: center; gap: 8px; margin: 12px 0; font-size: 13px; }
.fg-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.fg-status-dot.valid   { background: #16a34a; }
.fg-status-dot.invalid { background: #dc2626; }
.fg-status-dot.unknown { background: #94a3b8; }

.fg-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
.fg-btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 16px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; }
.fg-btn.primary   { background: var(--t600, #7c3aed); color: #fff; }
.fg-btn.primary:hover { background: var(--t700, #6d28d9); }
.fg-btn.secondary { background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0; }
.fg-btn.secondary:hover { background: #e2e8f0; }
.fg-btn.danger    { background: #fef2f2; color: #dc2626; border: 1.5px solid #fca5a5; }
.fg-btn.danger:hover { background: #fee2e2; }
.fg-btn:disabled  { opacity: .4; cursor: not-allowed; }
.fg-btn.sm { padding: 5px 12px; font-size: 12px; }

.fg-alert { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-top: 12px; }
.fg-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.fg-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

.fg-guide { background: #faf5ff; border: 1.5px solid #ede8ff; border-radius: 14px; padding: 18px 20px; }
.fg-guide-title { font-size: 13px; font-weight: 700; color: #1e1b2e; margin: 0 0 12px; }
.fg-guide ol { margin: 0; padding-left: 18px; font-size: 13px; color: #475569; line-height: 2; }
.fg-guide-note { font-size: 12px; color: #7c3aed; margin-top: 10px; font-weight: 600; }

.fg-breadcrumb { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #94a3b8; margin-bottom: 20px; }
.fg-breadcrumb a { color: #94a3b8; text-decoration: none; }
.fg-breadcrumb a:hover { color: var(--t600); }
</style>
@endpush

@section('ai-agent-content')
<div class="fg-wrap">

    <div class="fg-breadcrumb">
        <a href="{{ route('ai-agent.dashboard') }}">웍스 Agent</a>
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span>설정</span>
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span style="color:#1e1b2e;font-weight:600;">Figma 연동</span>
    </div>

    <div class="fg-header">
        <h1>
            <svg width="20" height="20" viewBox="0 0 38 57" fill="none">
                <path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/>
                <path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/>
                <path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/>
                <path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" fill="#F24E1E"/>
                <path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" fill="#A259FF"/>
            </svg>
            Figma API 설정
        </h1>
        <p>웍스 Agent가 Figma 파일에서 디자인 토큰, 컴포넌트, 화면 정보를 읽으려면 Personal Access Token이 필요합니다.</p>
    </div>

    <div class="fg-card"
         x-data="figmaSettings({
             hasToken: {{ $hasToken ? 'true' : 'false' }},
             status: {{ $status ? json_encode($status) : 'null' }},
             lastValidated: {{ $lastValidated ? json_encode($lastValidated->diffForHumans()) : 'null' }},
             maskedPat: {{ $maskedPat ? json_encode($maskedPat) : 'null' }},
             saveUrl:     '{{ route('ai-agent.settings.figma.save') }}',
             validateUrl: '{{ route('ai-agent.settings.figma.validate') }}',
             deleteUrl:   '{{ route('ai-agent.settings.figma.delete') }}',
             csrfToken:   '{{ csrf_token() }}',
         })">

        <div class="fg-card-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            Personal Access Token
        </div>

        {{-- Token input --}}
        <label class="fg-label">Figma PAT</label>
        <div class="fg-input-wrap">
            <input :type="showPat ? 'text' : 'password'"
                   class="fg-input"
                   x-model="pat"
                   :placeholder="hasToken ? maskedPat : 'figd_xxxxxxxxxxxxxxxxxxxx...'"
                   autocomplete="off"
                   spellcheck="false">
            <button type="button" class="fg-toggle-btn" @click="showPat = !showPat" title="토큰 표시/숨김">
                <svg x-show="!showPat" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg x-show="showPat" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </button>
        </div>

        {{-- Status --}}
        <div class="fg-status-row" x-show="hasToken || status">
            <div class="fg-status-dot" :class="status === 'valid' ? 'valid' : status === 'invalid' ? 'invalid' : 'unknown'"></div>
            <span x-text="statusText()"></span>
            <span x-show="lastValidated" style="color:#94a3b8;font-size:12px;" x-text="'(' + lastValidated + ')'"></span>
        </div>

        {{-- Alert --}}
        <template x-if="alert">
            <div class="fg-alert" :class="alert.type">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                <span x-text="alert.message"></span>
            </div>
        </template>

        {{-- Actions --}}
        <div class="fg-actions">
            <button class="fg-btn primary" @click="save" :disabled="loading || !pat.trim()">
                <svg x-show="!loading" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <svg x-show="loading" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin .7s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                저장
            </button>
            <button class="fg-btn secondary" @click="validate" :disabled="loading || !hasToken" x-show="hasToken">
                검증
            </button>
            <button class="fg-btn danger sm" @click="deletePat" :disabled="loading" x-show="hasToken">
                삭제
            </button>
        </div>

    </div>

    {{-- Guide card --}}
    <div class="fg-guide">
        <div class="fg-guide-title">💡 Personal Access Token 발급 방법</div>
        <ol>
            <li>Figma 로그인 → 우상단 프로필 아이콘 → <strong>Settings</strong></li>
            <li><strong>Account</strong> 탭 → <em>Personal access tokens</em> 섹션으로 이동</li>
            <li><strong>Generate new token</strong> 버튼 클릭 → 이름 입력 후 생성</li>
            <li>발급된 토큰을 복사하여 위의 입력창에 붙여넣기</li>
        </ol>
        <div class="fg-guide-note">⚠️ 토큰은 발급 시 한 번만 표시됩니다. 안전하게 보관하세요.</div>
    </div>

</div>

@push('scripts')
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
<script>
async function figmaSettings(cfg) {
    return {
        hasToken:      cfg.hasToken,
        status:        cfg.status,
        lastValidated: cfg.lastValidated,
        maskedPat:     cfg.maskedPat,
        saveUrl:       cfg.saveUrl,
        validateUrl:   cfg.validateUrl,
        deleteUrl:     cfg.deleteUrl,
        csrfToken:     cfg.csrfToken,

        pat:     '',
        showPat: false,
        loading: false,
        alert:   null,

        headers() {
            return {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  this.csrfToken,
            };
        },

        statusText() {
            if (!this.hasToken) return '토큰 미설정';
            if (this.status === 'valid')   return '✅ 유효한 토큰';
            if (this.status === 'invalid') return '❌ 유효하지 않은 토큰';
            return '검증 필요';
        },

        async save() {
            if (!this.pat.trim()) return;
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ pat: this.pat }),
                });
                const data = await res.json();
                if (res.ok) {
                    this.hasToken     = true;
                    this.status       = data.valid ? 'valid' : 'invalid';
                    this.lastValidated = '방금';
                    this.maskedPat    = this.pat.substring(0, 8) + '*'.repeat(Math.max(0, this.pat.length - 8));
                    this.pat          = '';
                    this.alert = { type: data.valid ? 'success' : 'error', message: data.message };
                } else {
                    this.alert = { type: 'error', message: data.message || '저장 중 오류가 발생했습니다.' };
                }
            } catch {
                this.alert = { type: 'error', message: '요청 처리 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },

        async validate() {
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.validateUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: '{}',
                });
                const data = await res.json();
                this.status       = data.valid ? 'valid' : 'invalid';
                this.lastValidated = '방금';
                this.alert = { type: data.valid ? 'success' : 'error', message: data.message };
            } catch {
                this.alert = { type: 'error', message: '검증 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },

        async deletePat() {
            if (!await __confirm('Figma 토큰을 삭제하시겠습니까? 디자인 단계 기능을 사용할 수 없게 됩니다.')) return;
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.deleteUrl, {
                    method: 'DELETE',
                    headers: this.headers(),
                });
                const data = await res.json();
                if (data.success) {
                    this.hasToken     = false;
                    this.status       = null;
                    this.lastValidated = null;
                    this.maskedPat    = null;
                    this.pat          = '';
                    this.alert = { type: 'success', message: data.message };
                }
            } catch {
                this.alert = { type: 'error', message: '삭제 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },
    };
}
</script>
@endpush
@endsection
