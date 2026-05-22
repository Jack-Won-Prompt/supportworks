@props([
    'artifactId'     => null,
    'artifactTitle'  => '산출물',
    'currentVersion' => 1,
    'historyUrl'     => '',
    'versionUrlTpl'  => '',  // placeholder: {version}
    'restoreUrlTpl'  => '',  // placeholder: {version}, POST
    'allowRestore'   => false,
])

@once
@push('styles')
<style>
/* ── Version History Side Panel (.avh-*) ───────────────────────── */
.avh-trigger { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:7px; font-size:12px; font-weight:600; color:var(--t600,#7c3aed); background:var(--t50,#f5f3ff); border:1px solid var(--t100,#ede9fe); cursor:pointer; transition:all .15s; }
.avh-trigger:hover { background:var(--t100,#ede9fe); }
.avh-trigger svg { flex-shrink:0; }

.avh-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1040; }
.avh-panel { position:fixed; top:0; right:0; bottom:0; width:480px; max-width:100vw; background:#fff; z-index:1041; display:flex; flex-direction:column; box-shadow:-4px 0 24px rgba(0,0,0,.12); }
.avh-header { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1.5px solid #f1f5f9; flex-shrink:0; }
.avh-header-icon { width:32px; height:32px; background:var(--t50,#f5f3ff); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.avh-header-title { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.avh-header-sub { font-size:11px; color:#94a3b8; }
.avh-close { width:28px; height:28px; border-radius:7px; border:none; background:#f8fafc; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.avh-close:hover { background:#f1f5f9; }

.avh-body { flex:1; overflow-y:auto; padding:0; }

/* version list */
.avh-list { padding:12px 0; }
.avh-item { display:flex; align-items:flex-start; gap:12px; padding:12px 20px; cursor:pointer; transition:background .12s; position:relative; }
.avh-item:hover { background:#f8fafc; }
.avh-item.is-current { background:#faf5ff; }
.avh-timeline { display:flex; flex-direction:column; align-items:center; flex-shrink:0; padding-top:2px; }
.avh-dot { width:10px; height:10px; border-radius:50%; background:#e2e8f0; border:2px solid #cbd5e1; flex-shrink:0; }
.avh-dot.current { background:var(--t500,#8b5cf6); border-color:var(--t400,#a78bfa); }
.avh-line { width:2px; flex:1; background:#e2e8f0; min-height:20px; margin-top:4px; }
.avh-item:last-child .avh-line { display:none; }
.avh-item-body { flex:1; min-width:0; }
.avh-item-top { display:flex; align-items:center; gap:6px; margin-bottom:3px; }
.avh-ver-badge { font-size:11px; font-weight:700; color:var(--t700,#6d28d9); background:var(--t50,#f5f3ff); border:1px solid var(--t100,#ede9fe); padding:1px 7px; border-radius:10px; }
.avh-cur-badge { font-size:10px; font-weight:600; color:#059669; background:#ecfdf5; border:1px solid #a7f3d0; padding:1px 7px; border-radius:10px; }
.avh-item-summary { font-size:12.5px; color:#374151; line-height:1.5; margin-bottom:3px; }
.avh-item-meta { font-size:11px; color:#94a3b8; display:flex; gap:10px; flex-wrap:wrap; }
.avh-item-size { font-size:11px; color:#94a3b8; }
.avh-item-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; opacity:0; transition:opacity .12s; }
.avh-item:hover .avh-item-actions { opacity:1; }
.avh-action-btn { font-size:11px; font-weight:600; padding:3px 10px; border-radius:6px; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer; transition:all .12s; white-space:nowrap; }
.avh-action-btn:hover { background:#f1f5f9; }
.avh-action-btn.restore { border-color:var(--t200,#ddd6fe); color:var(--t700,#6d28d9); }
.avh-action-btn.restore:hover { background:var(--t50,#f5f3ff); }

/* diff view */
.avh-diff-header { padding:12px 20px; border-bottom:1.5px solid #f1f5f9; background:#fafafa; }
.avh-diff-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.avh-diff-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; }
.avh-diff-ver { font-size:12px; font-weight:700; color:#1e1b2e; background:#f1f5f9; padding:2px 10px; border-radius:8px; }
.avh-diff-arrow { color:#94a3b8; font-size:12px; }
.avh-diff-stats { display:flex; gap:6px; font-size:11px; }
.avh-diff-stat-add { color:#059669; background:#ecfdf5; padding:1px 8px; border-radius:8px; font-weight:600; }
.avh-diff-stat-del { color:#dc2626; background:#fef2f2; padding:1px 8px; border-radius:8px; font-weight:600; }
.avh-diff-body { padding:0; }
.avh-diff-line { display:flex; align-items:baseline; font-family:monospace; font-size:12px; line-height:1.65; min-height:22px; }
.avh-diff-line.equal   { background:#fff; }
.avh-diff-line.added   { background:#f0fdf4; }
.avh-diff-line.removed { background:#fef2f2; }
.avh-diff-sign { width:20px; flex-shrink:0; text-align:center; font-weight:700; color:#94a3b8; padding:0 2px; }
.avh-diff-line.added   .avh-diff-sign { color:#16a34a; }
.avh-diff-line.removed .avh-diff-sign { color:#dc2626; }
.avh-diff-text { flex:1; padding:0 12px; white-space:pre-wrap; word-break:break-all; color:#1e1b2e; }
.avh-diff-line.added   .avh-diff-text { color:#15803d; }
.avh-diff-line.removed .avh-diff-text { color:#b91c1c; text-decoration:line-through; }

/* footer */
.avh-footer { padding:12px 20px; border-top:1.5px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.avh-footer-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; border:none; transition:all .15s; }
.avh-footer-btn.back { background:#f1f5f9; color:#475569; }
.avh-footer-btn.back:hover { background:#e2e8f0; }
.avh-footer-btn.restore { background:var(--t600,#7c3aed); color:#fff; }
.avh-footer-btn.restore:hover { background:var(--t700,#6d28d9); }
.avh-footer-btn:disabled { opacity:.5; cursor:not-allowed; }

.avh-loading { display:flex; align-items:center; justify-content:center; gap:10px; padding:40px 20px; color:#94a3b8; font-size:13px; }
.avh-spinner { width:18px; height:18px; border:2px solid #e2e8f0; border-top-color:var(--t500,#8b5cf6); border-radius:50%; animation:avhSpin .7s linear infinite; }
@keyframes avhSpin { to { transform:rotate(360deg); } }
.avh-empty { text-align:center; padding:40px 20px; color:#94a3b8; font-size:13px; }
</style>
@endpush
@endonce

@php
$uid = 'avh_' . uniqid();
$cfg = json_encode([
    'artifactId'     => $artifactId,
    'currentVersion' => (int) $currentVersion,
    'historyUrl'     => $historyUrl,
    'versionUrlTpl'  => $versionUrlTpl,
    'restoreUrlTpl'  => $restoreUrlTpl,
    'allowRestore'   => (bool) $allowRestore,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div x-data="versionHistory({{ $cfg }})" x-ref="{{ $uid }}">
    {{-- Trigger button --}}
    <button class="avh-trigger" @click="open()" title="버전 이력 보기">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        v{{ $currentVersion }}
    </button>

    {{-- Side panel (teleported to body) --}}
    <template x-teleport="body">
        <div x-show="mode !== 'closed'" x-cloak style="display:none">
            {{-- Backdrop --}}
            <div class="avh-backdrop" @click="close()"></div>

            {{-- Panel --}}
            <div class="avh-panel">
                {{-- Header --}}
                <div class="avh-header">
                    <div class="avh-header-icon">
                        <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="avh-header-title" x-text="mode === 'diff' ? '변경 내용 비교' : '버전 이력'"></div>
                        <div class="avh-header-sub">{{ $artifactTitle }}</div>
                    </div>
                    <button class="avh-close" @click="close()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="avh-body">
                    {{-- Loading --}}
                    <template x-if="loading">
                        <div class="avh-loading">
                            <div class="avh-spinner"></div>
                            <span>불러오는 중...</span>
                        </div>
                    </template>

                    {{-- Version list --}}
                    <template x-if="!loading && mode === 'list'">
                        <div>
                            <template x-if="versions.length === 0">
                                <div class="avh-empty">버전 이력이 없습니다.</div>
                            </template>
                            <div class="avh-list" x-show="versions.length > 0">
                                <template x-for="(v, idx) in versions" :key="v.version">
                                    <div class="avh-item" :class="{ 'is-current': v.is_current }">
                                        <div class="avh-timeline">
                                            <div class="avh-dot" :class="{ 'current': v.is_current }"></div>
                                            <div class="avh-line"></div>
                                        </div>
                                        <div class="avh-item-body">
                                            <div class="avh-item-top">
                                                <span class="avh-ver-badge" x-text="'v' + v.version"></span>
                                                <span class="avh-cur-badge" x-show="v.is_current">현재</span>
                                            </div>
                                            <div class="avh-item-summary" x-text="v.change_summary || (v.is_current ? '현재 버전 (최신)' : '초안 작성')"></div>
                                            <div class="avh-item-meta">
                                                <span x-text="formatDate(v.created_at)"></span>
                                                <span x-text="formatSize(v.content_length)"></span>
                                            </div>
                                        </div>
                                        <div class="avh-item-actions" x-show="!v.is_current">
                                            <button class="avh-action-btn" @click.stop="viewDiff(v)">비교</button>
                                            @if($allowRestore)
                                            <button class="avh-action-btn restore" @click.stop="restore(v.version)">복구</button>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Diff view --}}
                    <template x-if="!loading && mode === 'diff'">
                        <div>
                            <div class="avh-diff-header">
                                <div class="avh-diff-meta">
                                    <span class="avh-diff-label">비교</span>
                                    <span class="avh-diff-ver" x-text="'v' + (selectedVersion?.version ?? '?')"></span>
                                    <span class="avh-diff-arrow">→</span>
                                    <span class="avh-diff-ver" x-text="'v' + currentVersion + ' (현재)'"></span>
                                </div>
                                <div class="avh-diff-stats" style="margin-top:6px;">
                                    <span class="avh-diff-stat-add" x-text="'+' + diffStats.added + ' 추가'"></span>
                                    <span class="avh-diff-stat-del" x-text="'-' + diffStats.removed + ' 삭제'"></span>
                                </div>
                            </div>
                            <div class="avh-diff-body">
                                <template x-for="(line, i) in diffResult" :key="i">
                                    <div class="avh-diff-line" :class="line.type">
                                        <span class="avh-diff-sign"
                                            x-text="line.type === 'added' ? '+' : (line.type === 'removed' ? '-' : ' ')">
                                        </span>
                                        <span class="avh-diff-text" x-text="line.text === '' ? ' ' : line.text"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="avh-footer" x-show="!loading && mode !== 'closed'">
                    <template x-if="mode === 'diff'">
                        <div style="display:flex;gap:8px;width:100%;justify-content:space-between;align-items:center;">
                            <button class="avh-footer-btn back" @click="backToList()">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                목록으로
                            </button>
                            @if($allowRestore)
                            <button class="avh-footer-btn restore" @click="restore(selectedVersion?.version)" :disabled="restoring">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span x-text="restoring ? '복구 중...' : '이 버전으로 복구'"></span>
                            </button>
                            @endif
                        </div>
                    </template>
                    <template x-if="mode === 'list'">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:11.5px;color:#94a3b8;" x-text="'총 ' + versions.length + '개 버전'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
async function versionHistory(cfg) {
    return {
        mode: 'closed',
        loading: false,
        versions: [],
        currentVersion: cfg.currentVersion,
        selectedVersion: null,
        diffResult: [],
        diffStats: { added: 0, removed: 0 },
        restoring: false,

        open() {
            this.mode = 'list';
            this.fetchHistory();
        },

        close() {
            this.mode = 'closed';
        },

        backToList() {
            this.mode = 'list';
            this.selectedVersion = null;
            this.diffResult = [];
        },

        fetchHistory() {
            if (!cfg.historyUrl) return;
            this.loading = true;
            fetch(cfg.historyUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.versions = data.versions || [];
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        viewDiff(version) {
            this.loading = true;
            this.selectedVersion = version;

            const currentUrl = cfg.versionUrlTpl.replace('{version}', this.currentVersion);
            const oldUrl     = cfg.versionUrlTpl.replace('{version}', version.version);

            Promise.all([
                fetch(currentUrl, { headers: { Accept: 'application/json' } }).then(r => r.json()),
                fetch(oldUrl,     { headers: { Accept: 'application/json' } }).then(r => r.json()),
            ]).then(([current, old]) => {
                this.diffResult = this._computeDiff(old.content || '', current.content || '');
                this.diffStats = {
                    added:   this.diffResult.filter(l => l.type === 'added').length,
                    removed: this.diffResult.filter(l => l.type === 'removed').length,
                };
                this.mode = 'diff';
                this.loading = false;
            }).catch(() => { this.loading = false; });
        },

        async restore(version) {
            if (!version || !cfg.restoreUrlTpl) return;
            if (!await __confirm('v' + version + '으로 복구하시겠습니까?\n현재 내용은 새 버전으로 자동 보관됩니다.')) return;
            this.restoring = true;
            const url = cfg.restoreUrlTpl.replace('{version}', version);
            fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            })
            .then(r => r.json())
            .then(data => {
                this.restoring = false;
                if (data.success) {
                    this.close();
                    window.dispatchEvent(new CustomEvent('version-restored', { detail: data }));
                }
            })
            .catch(() => { this.restoring = false; });
        },

        // LCS line diff: old → new
        _computeDiff(oldText, newText) {
            const oldLines = oldText.split('\n');
            const newLines = newText.split('\n');
            const m = oldLines.length, n = newLines.length;

            // Build LCS DP table
            const dp = Array.from({ length: m + 1 }, () => new Int32Array(n + 1));
            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    dp[i][j] = oldLines[i - 1] === newLines[j - 1]
                        ? dp[i - 1][j - 1] + 1
                        : Math.max(dp[i - 1][j], dp[i][j - 1]);
                }
            }

            // Backtrack
            const result = [];
            let i = m, j = n;
            while (i > 0 || j > 0) {
                if (i > 0 && j > 0 && oldLines[i - 1] === newLines[j - 1]) {
                    result.push({ type: 'equal', text: oldLines[i - 1] });
                    i--; j--;
                } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
                    result.push({ type: 'added', text: newLines[j - 1] });
                    j--;
                } else {
                    result.push({ type: 'removed', text: oldLines[i - 1] });
                    i--;
                }
            }
            return result.reverse();
        },

        formatDate(dt) {
            if (!dt) return '-';
            return new Date(dt).toLocaleString('ko-KR', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit',
            });
        },

        formatSize(bytes) {
            if (!bytes) return '0B';
            if (bytes < 1024) return bytes + 'B';
            return (bytes / 1024).toFixed(1) + 'KB';
        },
    };
}
</script>
@endpush
@endonce
