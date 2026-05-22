@extends('layouts.ai-agent')
@section('title', '사용자 매뉴얼 — 웍스 Agent')

@push('styles')
<style>
.um-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.um-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.um-header p  { font-size:13.5px; color:#64748b; margin:0; }

.um-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
@media(max-width:900px){ .um-grid { grid-template-columns:1fr; } }

.um-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.um-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* Preview */
.um-preview { background:#f8f7ff; border:1.5px solid #ede8ff; border-radius:12px; padding:24px 28px; font-size:13.5px; line-height:1.8; color:#1e1b2e; overflow-x:auto; }
.um-preview h1 { font-size:20px; font-weight:800; margin:0 0 12px; color:#1e1b2e; border-bottom:2px solid #a78bfa; padding-bottom:6px; }
.um-preview h2 { font-size:16px; font-weight:700; margin:24px 0 8px; color:#2d1f5e; border-left:4px solid #a78bfa; padding-left:10px; }
.um-preview h3 { font-size:14px; font-weight:700; margin:16px 0 6px; color:#4c1d95; }
.um-preview blockquote { border-left:3px solid #a78bfa; margin:8px 0; padding:6px 14px; background:#f3f0ff; color:#4c1d95; border-radius:0 8px 8px 0; font-style:italic; }
.um-preview table { width:100%; border-collapse:collapse; margin:10px 0; font-size:12.5px; }
.um-preview th { background:#f3f0ff; padding:7px 12px; text-align:left; border:1px solid #e2d9f3; font-weight:700; }
.um-preview td { padding:7px 12px; border:1px solid #e2d9f3; }
.um-preview tr:nth-child(even) td { background:#fafbff; }
.um-preview ul { padding-left:20px; margin:6px 0; }
.um-preview li { margin:4px 0; }
.um-preview hr { border:none; border-top:1.5px dashed #ede8ff; margin:20px 0; }
.um-preview strong { color:#1e1b2e; }
.um-preview em { color:#64748b; font-size:12.5px; }
.um-preview img { max-width:100%; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.08); margin:8px 0; }
.um-preview p { margin:6px 0; }

/* Buttons */
.um-btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; transition:opacity .15s; }
.um-btn:disabled { opacity:.5; cursor:not-allowed; }
.um-btn.primary { background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; }
.um-btn.secondary { background:#f3f0ff; color:#4c1d95; border:1.5px solid #ddd6fe; }
.um-btn:hover:not(:disabled) { opacity:.88; }

/* Sidebar */
.um-stat-card { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:16px 18px; margin-bottom:12px; }
.um-stat-card-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:10px; }
.um-stat-row { display:flex; align-items:center; justify-content:space-between; font-size:12.5px; padding:4px 0; border-bottom:1px solid #f1f5f9; }
.um-stat-row:last-child { border-bottom:none; }
.um-stat-row .label { color:#64748b; }
.um-stat-row .val { font-weight:700; color:#1e1b2e; }

/* Prereq items */
.um-prereq { display:flex; flex-direction:column; gap:7px; }
.um-prereq-item { display:flex; align-items:center; gap:9px; font-size:12.5px; padding:7px 11px; border-radius:9px; }
.um-prereq-item.ok  { background:#f0fdf4; color:#15803d; }
.um-prereq-item.warn{ background:#fffbeb; color:#92400e; }
.um-prereq-item.nok { background:#fef2f2; color:#b91c1c; }

/* Editor */
.um-editor { width:100%; min-height:500px; font-family:monospace; font-size:13px; line-height:1.7; border:1.5px solid #ede8ff; border-radius:10px; padding:16px; resize:vertical; color:#1e1b2e; background:#fafbff; }

/* Empty */
.um-empty { text-align:center; padding:48px 20px; }
.um-empty-icon { font-size:40px; margin-bottom:12px; }
.um-empty p { font-size:13.5px; color:#64748b; margin:0 0 20px; }
</style>
@endpush

@section('ai-agent-content')
@php
    $screenCount    = $stats['screen_count'];
    $figmaMapped    = $stats['figma_mapped'];
    $requirementCnt = $stats['requirement_count'];
    $hasScreens     = $screenCount > 0;
    $hasRequirements= $requirementCnt > 0;
@endphp

<div x-data="userManual()" x-init="init()">

    {{-- Header --}}
    <div class="um-header">
        <div>
            <h1>사용자 매뉴얼</h1>
            <p>화면 정보와 요구사항을 바탕으로 최종 사용자용 매뉴얼을 자동 생성합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($artifact)
                <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=md"
                   class="um-btn secondary" style="text-decoration:none;">MD</a>
                <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=html"
                   class="um-btn secondary" style="text-decoration:none;">HTML</a>
                <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=zip"
                   class="um-btn secondary" style="text-decoration:none;">ZIP (이미지 포함)</a>
            @endif
            <button class="um-btn primary" :disabled="generating || !canGenerate" @click="generate()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span x-text="generating ? '생성 중…' : '{{ $artifact ? '재생성' : '매뉴얼 생성' }}'"></span>
            </button>
        </div>
    </div>

    <div class="um-grid">

        {{-- Left --}}
        <div>

            {{-- Prereq panel (artifact 없을 때만) --}}
            @if(!$artifact)
            <div class="um-section">
                <div class="um-section-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    사전 조건
                </div>
                <div class="um-prereq">
                    <div class="um-prereq-item {{ $hasScreens ? 'ok' : 'nok' }}">
                        <span>{{ $hasScreens ? '✅' : '❌' }}</span>
                        <span>화면 등록 (T16) — {{ $screenCount }}개</span>
                    </div>
                    <div class="um-prereq-item {{ $hasRequirements ? 'ok' : 'warn' }}">
                        <span>{{ $hasRequirements ? '✅' : '⚠️' }}</span>
                        <span>요구사항 (T19) — {{ $requirementCnt }}개 (선택)</span>
                    </div>
                    <div class="um-prereq-item {{ $figmaMapped > 0 ? 'ok' : 'warn' }}">
                        <span>{{ $figmaMapped > 0 ? '✅' : '⚠️' }}</span>
                        <span>Figma 매핑 (T31) — {{ $figmaMapped }}/{{ $screenCount }}개 (선택, 스크린샷)</span>
                    </div>
                </div>
                @if(!$hasScreens)
                <p style="font-size:12.5px;color:#b91c1c;margin:12px 0 0;">화면이 등록되어야 매뉴얼을 생성할 수 있습니다.</p>
                @endif
            </div>

            {{-- Empty state --}}
            <div class="um-section">
                <div class="um-empty">
                    <div class="um-empty-icon">📖</div>
                    <p>아직 매뉴얼이 생성되지 않았습니다.<br>
                       화면 정보, 요구사항, 권한 모델을 바탕으로 최종 사용자 매뉴얼을 자동 작성합니다.</p>
                    <button class="um-btn primary" :disabled="generating || !canGenerate" @click="generate()">
                        <span x-text="generating ? '생성 중…' : '매뉴얼 생성하기'"></span>
                    </button>
                </div>
            </div>
            @else

            {{-- Tab bar --}}
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <button class="um-btn secondary" :class="tab==='preview'?'primary':'secondary'"
                        @click="tab='preview'" style="padding:7px 16px;">미리보기</button>
                <button class="um-btn secondary" :class="tab==='edit'?'primary':'secondary'"
                        @click="tab='edit'" style="padding:7px 16px;">편집</button>
            </div>

            {{-- Preview panel --}}
            <div x-show="tab === 'preview'" class="um-section" style="padding:0;">
                <div class="um-preview" style="padding:24px 28px;" x-html="previewHtml"></div>
            </div>

            {{-- Edit panel --}}
            <div x-show="tab === 'edit'" class="um-section">
                <div class="um-section-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                    </svg>
                    Markdown 편집
                </div>
                <textarea class="um-editor" x-model="editContent"></textarea>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button class="um-btn primary" :disabled="saving" @click="saveEdit()">
                        <span x-text="saving ? '저장 중…' : '저장'"></span>
                    </button>
                    <button class="um-btn secondary" @click="tab='preview'">취소</button>
                </div>
            </div>
            @endif

        </div>

        {{-- Right sidebar --}}
        <div>

            {{-- 매뉴얼 정보 --}}
            <div class="um-stat-card">
                <div class="um-stat-card-title">매뉴얼 정보</div>
                <div class="um-stat-row">
                    <span class="label">화면 수</span>
                    <span class="val">{{ $screenCount }}개</span>
                </div>
                <div class="um-stat-row">
                    <span class="label">Figma 스크린샷</span>
                    <span class="val">{{ $figmaMapped }}/{{ $screenCount }}</span>
                </div>
                <div class="um-stat-row">
                    <span class="label">요구사항</span>
                    <span class="val">{{ $requirementCnt }}개</span>
                </div>
                @if(!empty($roles))
                <div class="um-stat-row">
                    <span class="label">권한 그룹</span>
                    <span class="val">{{ count($roles) }}개</span>
                </div>
                @endif
                @if($artifact)
                <div class="um-stat-row">
                    <span class="label">최종 생성</span>
                    <span class="val">{{ $artifact->created_at->format('Y-m-d H:i') }}</span>
                </div>
                @endif
            </div>

            {{-- 권한 그룹 --}}
            @if(!empty($roles))
            <div class="um-stat-card">
                <div class="um-stat-card-title">권한 그룹</div>
                @foreach($roles as $role)
                <div style="padding:5px 0;font-size:12.5px;border-bottom:1px solid #f1f5f9;">
                    <div style="font-weight:700;color:#1e1b2e;">{{ $role['name'] }}</div>
                    @if($role['description'])
                    <div style="color:#64748b;font-size:11.5px;">{{ $role['description'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- 내보내기 안내 --}}
            @if($artifact)
            <div class="um-stat-card">
                <div class="um-stat-card-title">내보내기</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=md"
                       class="um-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        📄 Markdown (.md)
                    </a>
                    <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=html"
                       class="um-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        🌐 HTML (인쇄/공유용)
                    </a>
                    <a href="{{ route('ai-agent.projects.release.user-manual.export', $aiProject) }}?format=zip"
                       class="um-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        📦 ZIP (이미지 포함)
                    </a>
                </div>
            </div>
            @endif

            {{-- CLI hint --}}
            <div class="um-stat-card">
                <div class="um-stat-card-title">CLI</div>
                <pre style="background:#1e1b2e;color:#e2d9f3;border-radius:8px;padding:10px 14px;font-size:11px;margin:0;overflow-x:auto;white-space:pre-wrap;">php artisan ai-agent:release:user-manual {{ $aiProject->id }}

# 이미지 포함 ZIP:
php artisan ai-agent:release:user-manual {{ $aiProject->id }} --with-images</pre>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function userManual() {
    return {
        tab: 'preview',
        generating: false,
        saving: false,
        canGenerate: {{ $hasScreens ? 'true' : 'false' }},
        previewHtml: '',
        editContent: '',

        init() {
            @if($artifact)
                this.previewHtml = this.markdownToHtml(@js($artifact->content ?? ''));
                this.editContent = @js($artifact->content ?? '');
            @endif
        },

        generate() {
            this.generating = true;
            fetch('{{ route('ai-agent.projects.release.user-manual.generate', $aiProject) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || '생성에 실패했습니다.');
                }
            })
            .catch(() => alert('서버 오류가 발생했습니다.'))
            .finally(() => { this.generating = false; });
        },

        saveEdit() {
            this.saving = true;
            fetch('{{ route('ai-agent.projects.release.user-manual.update', $aiProject) }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ content: this.editContent }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.previewHtml = this.markdownToHtml(this.editContent);
                    this.tab = 'preview';
                } else {
                    alert(data.error || '저장에 실패했습니다.');
                }
            })
            .catch(() => alert('서버 오류가 발생했습니다.'))
            .finally(() => { this.saving = false; });
        },

        markdownToHtml(md) {
            if (!md) return '';
            let html = md;

            // Escape HTML
            html = html.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            // Images (before code blocks)
            html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g,
                '<img src="$2" alt="$1">');

            // Code blocks
            html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (_, lang, code) =>
                `<pre><code class="language-${lang}">${code}</code></pre>`);

            // Headings
            html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
            html = html.replace(/^## (.+)$/gm,  '<h2>$1</h2>');
            html = html.replace(/^# (.+)$/gm,   '<h1>$1</h1>');

            // Blockquote
            html = html.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');

            // HR
            html = html.replace(/^---$/gm, '<hr>');

            // Bold
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Italic
            html = html.replace(/_(.+?)_/g, '<em>$1</em>');

            // Tables
            html = html.replace(/((?:\|.+\|\n)+)/g, (m) => {
                const rows = m.trim().split('\n').filter(r => !r.match(/^\|[-| ]+\|$/));
                let out = '<table>';
                rows.forEach((row, i) => {
                    const cells = row.split('|').filter((_, j, arr) => j > 0 && j < arr.length - 1).map(c => c.trim());
                    const tag = i === 0 ? 'th' : 'td';
                    out += `<tr>${cells.map(c => `<${tag}>${c}</${tag}>`).join('')}</tr>`;
                });
                return out + '</table>';
            });

            // Lists
            html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
            html = html.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');

            // Paragraphs
            html = html.replace(/\n{2,}/g, '</p><p>');
            html = '<p>' + html + '</p>';

            return html;
        },
    };
}
</script>
@endpush
