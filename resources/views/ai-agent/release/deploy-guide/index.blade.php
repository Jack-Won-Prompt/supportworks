@extends('layouts.ai-agent')
@section('title', '배포 가이드 — 웍스 Agent')

@push('styles')
<style>
.dg-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.dg-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.dg-header p  { font-size:13.5px; color:#64748b; margin:0; }

.dg-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
@media(max-width:900px){ .dg-grid { grid-template-columns:1fr; } }

.dg-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.dg-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* Preview */
.dg-preview { background:#f8f7ff; border:1.5px solid #ede8ff; border-radius:12px; padding:24px 28px; font-size:13.5px; line-height:1.75; color:#1e1b2e; overflow-x:auto; }
.dg-preview h1 { font-size:20px; font-weight:800; margin:0 0 12px; color:#1e1b2e; }
.dg-preview h2 { font-size:16px; font-weight:700; margin:20px 0 8px; color:#2d1f5e; border-bottom:1.5px solid #ede8ff; padding-bottom:4px; }
.dg-preview h3 { font-size:14px; font-weight:700; margin:14px 0 6px; color:#4c1d95; }
.dg-preview pre { background:#1e1b2e; color:#e2d9f3; border-radius:10px; padding:14px 18px; overflow-x:auto; font-size:12.5px; margin:8px 0; }
.dg-preview code { font-family:monospace; font-size:12.5px; background:#ede8ff; color:#4c1d95; padding:1px 5px; border-radius:4px; }
.dg-preview pre code { background:none; color:inherit; padding:0; }
.dg-preview table { width:100%; border-collapse:collapse; margin:10px 0; font-size:12.5px; }
.dg-preview th { background:#f3f0ff; padding:6px 12px; text-align:left; border:1px solid #e2d9f3; font-weight:600; }
.dg-preview td { padding:6px 12px; border:1px solid #e2d9f3; }
.dg-preview blockquote { border-left:3px solid #a78bfa; margin:8px 0; padding:6px 14px; background:#f3f0ff; color:#4c1d95; border-radius:0 8px 8px 0; font-style:italic; }
.dg-preview ul { padding-left:20px; margin:6px 0; }
.dg-preview li { margin:3px 0; }
.dg-preview hr { border:none; border-top:1.5px dashed #ede8ff; margin:20px 0; }
.dg-preview a { color:#7c3aed; }
.dg-preview input[type=checkbox] { margin-right:5px; }

/* Sidebar cards */
.dg-stat-card { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:16px 18px; margin-bottom:12px; }
.dg-stat-card-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:10px; }
.dg-stat-row { display:flex; align-items:center; justify-content:space-between; font-size:12.5px; padding:4px 0; border-bottom:1px solid #f1f5f9; }
.dg-stat-row:last-child { border-bottom:none; }
.dg-stat-row .label { color:#64748b; }
.dg-stat-row .val { font-weight:700; color:#1e1b2e; }

/* Action buttons */
.dg-btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; transition:opacity .15s; }
.dg-btn:disabled { opacity:.5; cursor:not-allowed; }
.dg-btn.primary { background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; }
.dg-btn.secondary { background:#f3f0ff; color:#4c1d95; border:1.5px solid #ddd6fe; }
.dg-btn:hover:not(:disabled) { opacity:.88; }
.dg-btn.loading .btn-text::after { content:'…'; }

/* Empty state */
.dg-empty { text-align:center; padding:48px 20px; }
.dg-empty-icon { font-size:40px; margin-bottom:12px; }
.dg-empty p { font-size:13.5px; color:#64748b; margin:0 0 20px; }

/* Badge */
.dg-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:700; }
.dg-badge.react { background:#e0f2fe; color:#0369a1; }
.dg-badge.vue   { background:#dcfce7; color:#15803d; }
.dg-badge.html  { background:#fef9c3; color:#854d0e; }

/* Editor */
.dg-editor { width:100%; min-height:500px; font-family:monospace; font-size:13px; line-height:1.7; border:1.5px solid #ede8ff; border-radius:10px; padding:16px; resize:vertical; color:#1e1b2e; background:#fafbff; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="deployGuide()" x-init="init()">

    {{-- Header --}}
    <div class="dg-header">
        <div>
            <h1>배포 가이드</h1>
            <p>릴리즈 패키지 기반으로 배포 절차를 자동 생성합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($artifact)
                <a href="{{ route('ai-agent.projects.release.deploy-guide.export', $aiProject) }}?format=md"
                   class="dg-btn secondary" style="text-decoration:none;">
                    MD 다운로드
                </a>
                <a href="{{ route('ai-agent.projects.release.deploy-guide.export', $aiProject) }}?format=html"
                   class="dg-btn secondary" style="text-decoration:none;">
                    HTML 다운로드
                </a>
            @endif
            <button class="dg-btn primary" :class="{ loading: generating }" :disabled="generating"
                    @click="generate()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span class="btn-text" x-text="generating ? '생성 중' : '{{ $artifact ? '재생성' : '가이드 생성' }}'"></span>
            </button>
        </div>
    </div>

    {{-- Main grid --}}
    <div class="dg-grid">

        {{-- Left: preview / editor --}}
        <div>

            {{-- Tab bar --}}
            @if($artifact)
            <div style="display:flex;gap:6px;margin-bottom:12px;">
                <button class="dg-btn secondary" :class="tab==='preview' ? 'primary':'secondary'" @click="tab='preview'" style="padding:7px 16px;">미리보기</button>
                <button class="dg-btn secondary" :class="tab==='edit'    ? 'primary':'secondary'" @click="tab='edit'"    style="padding:7px 16px;">편집</button>
            </div>
            @endif

            {{-- Preview panel --}}
            @if($artifact)
                <div x-show="tab === 'preview'" class="dg-section" style="padding:0;">
                    <div class="dg-preview" style="padding:24px 28px;" x-html="previewHtml"></div>
                </div>

                {{-- Edit panel --}}
                <div x-show="tab === 'edit'" class="dg-section">
                    <div class="dg-section-title">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                        </svg>
                        Markdown 편집
                    </div>
                    <textarea class="dg-editor" x-model="editContent"></textarea>
                    <div style="margin-top:10px;display:flex;gap:8px;">
                        <button class="dg-btn primary" :disabled="saving" @click="saveEdit()">
                            <span x-text="saving ? '저장 중…' : '저장'"></span>
                        </button>
                        <button class="dg-btn secondary" @click="tab='preview'">취소</button>
                    </div>
                </div>
            @else
                {{-- Empty state --}}
                <div class="dg-section">
                    <div class="dg-empty">
                        <div class="dg-empty-icon">📋</div>
                        <p>아직 배포 가이드가 생성되지 않았습니다.<br>릴리즈 패키지의 산출물을 바탕으로 가이드를 자동 생성합니다.</p>
                        <button class="dg-btn primary" :disabled="generating" @click="generate()">
                            <span x-text="generating ? '생성 중…' : '배포 가이드 생성'"></span>
                        </button>
                    </div>
                </div>
            @endif

        </div>

        {{-- Right sidebar --}}
        <div>

            {{-- Project info --}}
            <div class="dg-stat-card">
                <div class="dg-stat-card-title">프로젝트</div>
                <div class="dg-stat-row">
                    <span class="label">프로젝트</span>
                    <span class="val">{{ $context['project']['name'] }}</span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">Frontend</span>
                    <span class="val">
                        @php $stack = $context['project']['frontend_stack']; @endphp
                        <span class="dg-badge {{ $stack }}">{{ strtoupper($stack) }}</span>
                    </span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">Backend</span>
                    <span class="val">Laravel</span>
                </div>
                @if($artifact)
                <div class="dg-stat-row">
                    <span class="label">최종 생성</span>
                    <span class="val">{{ $artifact->created_at->format('Y-m-d H:i') }}</span>
                </div>
                @endif
            </div>

            {{-- Stats --}}
            <div class="dg-stat-card">
                <div class="dg-stat-card-title">산출물 현황</div>
                <div class="dg-stat-row">
                    <span class="label">DB 테이블</span>
                    <span class="val">{{ $context['database']['tables_count'] }}개</span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">API 엔드포인트</span>
                    <span class="val">{{ $context['api']['endpoints_count'] }}개</span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">화면 수</span>
                    <span class="val">{{ $context['frontend']['screens_count'] }}개</span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">Backend 리소스</span>
                    <span class="val">{{ $context['backend']['resources_count'] }}개</span>
                </div>
                @if($context['integration']['compliance_rate'] !== null)
                <div class="dg-stat-row">
                    <span class="label">API 연계율</span>
                    <span class="val">{{ $context['integration']['compliance_rate'] }}%</span>
                </div>
                @endif
                @if($context['integration']['review_score'] !== null)
                <div class="dg-stat-row">
                    <span class="label">코드 리뷰 점수</span>
                    <span class="val">{{ $context['integration']['review_score'] }}/100</span>
                </div>
                @endif
            </div>

            {{-- Package link --}}
            @if($context['package']['has_package'])
            <div class="dg-stat-card">
                <div class="dg-stat-card-title">릴리즈 패키지</div>
                <div class="dg-stat-row">
                    <span class="label">패키지 크기</span>
                    <span class="val">{{ $context['package']['size_mb'] }} MB</span>
                </div>
                <div class="dg-stat-row">
                    <span class="label">생성일</span>
                    <span class="val">{{ $context['package']['generated_at'] }}</span>
                </div>
                <div style="margin-top:10px;">
                    <a href="{{ route('ai-agent.projects.release.package.index', $aiProject) }}"
                       class="dg-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        패키지 페이지 →
                    </a>
                </div>
            </div>
            @else
            <div class="dg-stat-card">
                <div class="dg-stat-card-title">릴리즈 패키지</div>
                <p style="font-size:12.5px;color:#94a3b8;margin:0 0 10px;">패키지가 아직 생성되지 않았습니다.</p>
                <a href="{{ route('ai-agent.projects.release.package.index', $aiProject) }}"
                   class="dg-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                    패키지 생성 →
                </a>
            </div>
            @endif

            {{-- CLI hint --}}
            <div class="dg-stat-card">
                <div class="dg-stat-card-title">CLI</div>
                <pre style="background:#1e1b2e;color:#e2d9f3;border-radius:8px;padding:10px 14px;font-size:11.5px;margin:0;overflow-x:auto;">php artisan ai-agent:release:deploy-guide {{ $aiProject->id }}</pre>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function deployGuide() {
    return {
        tab: 'preview',
        generating: false,
        saving: false,
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
            fetch('{{ route('ai-agent.projects.release.deploy-guide.generate', $aiProject) }}', {
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
            fetch('{{ route('ai-agent.projects.release.deploy-guide.update', $aiProject) }}', {
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

            // Escape HTML first
            html = html.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

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

            // Bold/Inline code
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

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

            // Checkboxes
            html = html.replace(/^- \[ \] (.+)$/gm, '<li><input type="checkbox" disabled> $1</li>');
            html = html.replace(/^- \[x\] (.+)$/gm,  '<li><input type="checkbox" checked disabled> $1</li>');

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
