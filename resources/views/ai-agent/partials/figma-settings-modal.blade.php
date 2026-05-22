<div x-data="figmaSettingsModal({
         hasToken:      {{ $aiFigmaHasToken ? 'true' : 'false' }},
         status:        {{ $aiFigmaStatus ? json_encode($aiFigmaStatus) : 'null' }},
         lastValidated: {{ $aiFigmaLastValidated ? json_encode($aiFigmaLastValidated->diffForHumans()) : 'null' }},
         maskedPat:     {{ $aiFigmaMaskedPat ? json_encode($aiFigmaMaskedPat) : 'null' }},
         saveUrl:       '{{ route('ai-agent.settings.figma.save') }}',
         validateUrl:   '{{ route('ai-agent.settings.figma.validate') }}',
         deleteUrl:     '{{ route('ai-agent.settings.figma.delete') }}',
         csrfToken:     '{{ csrf_token() }}',
     })"
     @figma-settings-open.window="open = true"
     x-show="open"
     x-cloak
     style="position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;">

    {{-- Backdrop --}}
    <div @click="open = false"
         style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);"></div>

    {{-- Modal --}}
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.18);margin:16px;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:12px;padding:20px 24px 16px;border-bottom:1.5px solid #f1f5f9;">
            <svg width="18" height="18" viewBox="0 0 38 57" fill="none" style="flex-shrink:0;">
                <path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/>
                <path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/>
                <path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/>
                <path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" fill="#F24E1E"/>
                <path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" fill="#A259FF"/>
            </svg>
            <div style="flex:1;">
                <div style="font-size:15px;font-weight:800;color:#1e1b2e;">Figma API 설정</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:1px;">Personal Access Token으로 Figma 파일에 접근합니다</div>
            </div>
            <button @click="open = false" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;border:1.5px solid #e2e8f0;background:none;cursor:pointer;color:#94a3b8;transition:all .12s;" onmouseover="this.style.borderColor='#cbd5e1';this.style.color='#374151'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#94a3b8'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div style="padding:20px 24px;">

            {{-- Token input --}}
            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">Figma Personal Access Token</label>
            <div style="position:relative;">
                <input :type="showPat ? 'text' : 'password'"
                       x-model="pat"
                       :placeholder="hasToken ? maskedPat : 'figd_xxxxxxxxxxxxxxxxxxxx...'"
                       autocomplete="off"
                       spellcheck="false"
                       style="width:100%;border:1.5px solid #ddd6fe;border-radius:10px;padding:10px 40px 10px 14px;font-size:13.5px;color:#1e1b2e;font-family:'Courier New',monospace;outline:none;box-sizing:border-box;transition:border-color .15s;"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#ddd6fe'">
                <button type="button" @click="showPat = !showPat"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px;">
                    <svg x-show="!showPat" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPat" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>

            {{-- Status --}}
            <div x-show="hasToken || status" style="display:flex;align-items:center;gap:8px;margin:12px 0;font-size:13px;">
                <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;"
                     :style="status === 'valid' ? 'background:#16a34a' : status === 'invalid' ? 'background:#dc2626' : 'background:#94a3b8'"></div>
                <span x-text="statusText()"></span>
                <span x-show="lastValidated" style="color:#94a3b8;font-size:12px;" x-text="'(' + lastValidated + ')'"></span>
            </div>

            {{-- Alert --}}
            <template x-if="alert">
                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"
                     :style="alert.type === 'success' ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d' : 'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c'">
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                    <span x-text="alert.message"></span>
                </div>
            </template>

            {{-- Actions --}}
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <button @click="save" :disabled="loading || !pat.trim()"
                        style="display:inline-flex;align-items:center;gap:4px;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:var(--t600,#7c3aed);color:#fff;transition:background .15s;"
                        onmouseover="if(!this.disabled)this.style.background='var(--t700,#6d28d9)'" onmouseout="this.style.background='var(--t600,#7c3aed)'">
                    <svg x-show="!loading" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <svg x-show="loading" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin .7s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    저장
                </button>
                <button x-show="hasToken" @click="validate" :disabled="loading || !hasToken"
                        style="display:inline-flex;align-items:center;gap:4px;padding:8px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;transition:all .15s;"
                        onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    검증
                </button>
                <button x-show="hasToken" @click="deletePat" :disabled="loading"
                        style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;background:#fef2f2;color:#dc2626;border:1.5px solid #fca5a5;transition:all .15s;margin-left:auto;"
                        onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                    삭제
                </button>
            </div>

            {{-- Guide --}}
            <div style="background:#faf5ff;border:1.5px solid #ede8ff;border-radius:12px;padding:16px 18px;">
                <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:10px;">💡 Personal Access Token 발급 방법</div>
                <ol style="margin:0;padding-left:18px;font-size:12.5px;color:#475569;line-height:2.1;">
                    <li>Figma 로그인 → 우상단 프로필 → <strong>Settings</strong></li>
                    <li><strong>Account</strong> 탭 → <em>Personal access tokens</em> 섹션</li>
                    <li><strong>Generate new token</strong> 클릭 → 이름 입력 후 생성</li>
                    <li>발급된 토큰을 복사하여 위 입력창에 붙여넣기</li>
                </ol>
                <div style="font-size:12px;color:#7c3aed;margin-top:10px;font-weight:600;">⚠️ 토큰은 발급 시 한 번만 표시됩니다. 안전하게 보관하세요.</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
<script>
async function figmaSettingsModal(cfg) {
    return {
        open: false,
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
            return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken };
        },

        statusText() {
            if (!this.hasToken) return '토큰 미설정';
            if (this.status === 'valid')   return '✅ 유효한 토큰';
            if (this.status === 'invalid') return '❌ 유효하지 않은 토큰';
            return '검증 필요';
        },

        async save() {
            if (!this.pat.trim()) return;
            this.loading = true; this.alert = null;
            try {
                const res  = await fetch(this.saveUrl, { method: 'POST', headers: this.headers(), body: JSON.stringify({ pat: this.pat }) });
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
            } catch { this.alert = { type: 'error', message: '요청 처리 중 오류가 발생했습니다.' }; }
            this.loading = false;
        },

        async validate() {
            this.loading = true; this.alert = null;
            try {
                const res  = await fetch(this.validateUrl, { method: 'POST', headers: this.headers(), body: '{}' });
                const data = await res.json();
                this.status       = data.valid ? 'valid' : 'invalid';
                this.lastValidated = '방금';
                this.alert = { type: data.valid ? 'success' : 'error', message: data.message };
            } catch { this.alert = { type: 'error', message: '검증 중 오류가 발생했습니다.' }; }
            this.loading = false;
        },

        async deletePat() {
            if (!await __confirm('Figma 토큰을 삭제하시겠습니까?')) return;
            this.loading = true; this.alert = null;
            try {
                const res  = await fetch(this.deleteUrl, { method: 'DELETE', headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.hasToken = false; this.status = null; this.lastValidated = null; this.maskedPat = null; this.pat = '';
                    this.alert = { type: 'success', message: data.message };
                }
            } catch { this.alert = { type: 'error', message: '삭제 중 오류가 발생했습니다.' }; }
            this.loading = false;
        },
    };
}
</script>
@endpush
