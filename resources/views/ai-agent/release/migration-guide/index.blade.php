@extends('layouts.ai-agent')
@section('title', '마이그레이션 가이드 — 웍스 Agent')

@push('styles')
<style>
.mg-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.mg-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.mg-header p  { font-size:13.5px; color:#64748b; margin:0; }

.mg-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
@media(max-width:900px){ .mg-grid { grid-template-columns:1fr; } }

.mg-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.mg-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* Preview */
.mg-preview { background:#f8f7ff; border:1.5px solid #ede8ff; border-radius:12px; padding:24px 28px; font-size:13.5px; line-height:1.8; color:#1e1b2e; overflow-x:auto; }
.mg-preview h1 { font-size:20px; font-weight:800; margin:0 0 12px; border-bottom:2px solid #a78bfa; padding-bottom:6px; }
.mg-preview h2 { font-size:16px; font-weight:700; margin:24px 0 8px; color:#2d1f5e; border-left:4px solid #a78bfa; padding-left:10px; }
.mg-preview h3 { font-size:14px; font-weight:700; margin:16px 0 6px; color:#4c1d95; }
.mg-preview pre { background:#1e1b2e; color:#e2d9f3; border-radius:10px; padding:14px 18px; overflow-x:auto; font-size:12px; margin:8px 0; }
.mg-preview code { font-family:monospace; font-size:12.5px; background:#ede8ff; color:#4c1d95; padding:1px 5px; border-radius:4px; }
.mg-preview pre code { background:none; color:inherit; padding:0; }
.mg-preview table { width:100%; border-collapse:collapse; margin:10px 0; font-size:12.5px; }
.mg-preview th { background:#f3f0ff; padding:6px 12px; text-align:left; border:1px solid #e2d9f3; font-weight:700; }
.mg-preview td { padding:6px 12px; border:1px solid #e2d9f3; }
.mg-preview tr:nth-child(even) td { background:#fafbff; }
.mg-preview blockquote { border-left:3px solid #f59e0b; margin:8px 0; padding:6px 14px; background:#fffbeb; color:#92400e; border-radius:0 8px 8px 0; }
.mg-preview ul { padding-left:20px; margin:6px 0; }
.mg-preview li { margin:3px 0; }
.mg-preview hr { border:none; border-top:1.5px dashed #ede8ff; margin:20px 0; }
.mg-preview strong { color:#1e1b2e; }
.mg-preview em { color:#64748b; font-size:12.5px; }
.mg-preview p { margin:6px 0; }

/* Buttons */
.mg-btn { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; border:none; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; transition:opacity .15s; }
.mg-btn:disabled { opacity:.5; cursor:not-allowed; }
.mg-btn.primary { background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; }
.mg-btn.secondary { background:#f3f0ff; color:#4c1d95; border:1.5px solid #ddd6fe; }
.mg-btn:hover:not(:disabled) { opacity:.88; }

/* Sidebar */
.mg-stat-card { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:16px 18px; margin-bottom:12px; }
.mg-stat-card-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:10px; }
.mg-stat-row { display:flex; align-items:center; justify-content:space-between; font-size:12.5px; padding:4px 0; border-bottom:1px solid #f1f5f9; }
.mg-stat-row:last-child { border-bottom:none; }
.mg-stat-row .label { color:#64748b; }
.mg-stat-row .val { font-weight:700; color:#1e1b2e; }

/* Prereq */
.mg-prereq { display:flex; flex-direction:column; gap:7px; }
.mg-prereq-item { display:flex; align-items:center; gap:9px; font-size:12.5px; padding:7px 11px; border-radius:9px; }
.mg-prereq-item.ok  { background:#f0fdf4; color:#15803d; }
.mg-prereq-item.warn{ background:#fffbeb; color:#92400e; }
.mg-prereq-item.nok { background:#fef2f2; color:#b91c1c; }

/* Mode badge */
.mg-mode-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; background:#dcfce7; color:#15803d; border-radius:20px; font-size:12px; font-weight:700; }
.mg-mode-future { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; background:#fef3c7; color:#92400e; border-radius:20px; font-size:12px; font-weight:600; }

/* Editor */
.mg-editor { width:100%; min-height:500px; font-family:monospace; font-size:13px; line-height:1.7; border:1.5px solid #ede8ff; border-radius:10px; padding:16px; resize:vertical; color:#1e1b2e; background:#fafbff; }

/* Empty */
.mg-empty { text-align:center; padding:48px 20px; }
.mg-empty-icon { font-size:40px; margin-bottom:12px; }
.mg-empty p { font-size:13.5px; color:#64748b; margin:0 0 20px; }
</style>
@endpush

@section('ai-agent-content')
@php
    $hasErd  = $database['has_erd'] ?? false;
    $hasRbac = $rbac['has_rbac'] ?? false;
    $tableCount = count($database['tables'] ?? []);
    $roleCount  = count($rbac['roles'] ?? []);
@endphp

<div x-data="migrationGuide()" x-init="init()">

    {{-- Header --}}
    <div class="mg-header">
        <div>
            <h1>마이그레이션 가이드</h1>
            <p>ERD와 RBAC를 바탕으로 신규 프로젝트 설치 가이드를 자동 생성합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($artifact)
                <a href="{{ route('ai-agent.projects.release.migration-guide.export', $aiProject) }}?format=md"
                   class="mg-btn secondary" style="text-decoration:none;">MD 다운로드</a>
                <a href="{{ route('ai-agent.projects.release.migration-guide.export', $aiProject) }}?format=html"
                   class="mg-btn secondary" style="text-decoration:none;">HTML 다운로드</a>
            @endif
            <button class="mg-btn primary" :disabled="generating" @click="generate()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span x-text="generating ? '생성 중…' : '{{ $artifact ? '재생성' : '가이드 생성' }}'"></span>
            </button>
        </div>
    </div>

    <div class="mg-grid">

        {{-- Left --}}
        <div>

            {{-- Mode panel --}}
            @if(!$artifact)
            <div class="mg-section">
                <div class="mg-section-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    가이드 모드
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:#1e1b2e;">신규 프로젝트 설치</div>
                            <div style="font-size:12px;color:#64748b;">빈 DB에서 시작 — 자동 생성</div>
                        </div>
                        <span class="mg-mode-badge">✅ 현재 모드</span>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:#94a3b8;">기존 시스템 전환</div>
                            <div style="font-size:12px;color:#94a3b8;">데이터 이전 / 스키마 변환</div>
                        </div>
                        <span class="mg-mode-future">⚠️ 지원 예정</span>
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <div class="mg-section-title" style="margin-bottom:10px;">사전 조건</div>
                    <div class="mg-prereq">
                        <div class="mg-prereq-item {{ $hasErd ? 'ok' : 'warn' }}">
                            <span>{{ $hasErd ? '✅' : '⚠️' }}</span>
                            <span>ERD (T36) — {{ $tableCount }}개 테이블@if(!$hasErd) 없음 (없어도 생성 가능)@endif</span>
                        </div>
                        <div class="mg-prereq-item {{ $hasRbac ? 'ok' : 'warn' }}">
                            <span>{{ $hasRbac ? '✅' : '⚠️' }}</span>
                            <span>RBAC (T38) — {{ $roleCount }}개 역할@if(!$hasRbac) 없음 (없어도 생성 가능)@endif</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mg-section">
                <div class="mg-empty">
                    <div class="mg-empty-icon">🚀</div>
                    <p>아직 마이그레이션 가이드가 생성되지 않았습니다.<br>
                       ERD와 RBAC 정보를 바탕으로 설치 가이드를 자동 작성합니다.</p>
                    <button class="mg-btn primary" :disabled="generating" @click="generate()">
                        <span x-text="generating ? '생성 중…' : '가이드 생성하기'"></span>
                    </button>
                </div>
            </div>
            @else

            {{-- Tabs --}}
            <div style="display:flex;gap:6px;margin-bottom:12px;">
                <button class="mg-btn secondary" :class="tab==='preview'?'primary':'secondary'"
                        @click="tab='preview'" style="padding:7px 16px;">미리보기</button>
                <button class="mg-btn secondary" :class="tab==='edit'?'primary':'secondary'"
                        @click="tab='edit'" style="padding:7px 16px;">편집</button>
            </div>

            {{-- Preview --}}
            <div x-show="tab === 'preview'" class="mg-section" style="padding:0;">
                <div class="mg-preview" style="padding:24px 28px;" x-html="previewHtml"></div>
            </div>

            {{-- Edit --}}
            <div x-show="tab === 'edit'" class="mg-section">
                <div class="mg-section-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                    </svg>
                    Markdown 편집
                </div>
                <textarea class="mg-editor" x-model="editContent"></textarea>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button class="mg-btn primary" :disabled="saving" @click="saveEdit()">
                        <span x-text="saving ? '저장 중…' : '저장'"></span>
                    </button>
                    <button class="mg-btn secondary" @click="tab='preview'">취소</button>
                </div>
            </div>
            @endif

        </div>

        {{-- Right sidebar --}}
        <div>

            {{-- 산출물 현황 --}}
            <div class="mg-stat-card">
                <div class="mg-stat-card-title">산출물 현황</div>
                <div class="mg-stat-row">
                    <span class="label">ERD 테이블</span>
                    <span class="val">{{ $tableCount }}개</span>
                </div>
                <div class="mg-stat-row">
                    <span class="label">RBAC 역할</span>
                    <span class="val">{{ $roleCount }}개</span>
                </div>
                @if(($rbac['admin_role_key'] ?? null))
                <div class="mg-stat-row">
                    <span class="label">관리자 역할</span>
                    <span class="val" style="font-family:monospace;font-size:11.5px;">{{ $rbac['admin_role_key'] }}</span>
                </div>
                @endif
                @if($artifact)
                <div class="mg-stat-row">
                    <span class="label">최종 생성</span>
                    <span class="val">{{ $artifact->created_at->format('Y-m-d H:i') }}</span>
                </div>
                @endif
            </div>

            {{-- 가이드 구성 --}}
            <div class="mg-stat-card">
                <div class="mg-stat-card-title">가이드 구성</div>
                @foreach([
                    '1. 가이드 안내', '2. 사전 준비', '3. DB 초기화',
                    '4. 관리자 계정 생성', '5. 초기 데이터 시딩',
                    '6. 검증', '7. 환경 변수', '8. 운영 전환',
                    '9. 롤백 계획', '10. FAQ', '11. 다음 단계',
                ] as $section)
                <div style="font-size:12px;color:#64748b;padding:3px 0;border-bottom:1px solid #f1f5f9;">{{ $section }}</div>
                @endforeach
            </div>

            {{-- 내보내기 --}}
            @if($artifact)
            <div class="mg-stat-card">
                <div class="mg-stat-card-title">내보내기</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <a href="{{ route('ai-agent.projects.release.migration-guide.export', $aiProject) }}?format=md"
                       class="mg-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        📄 Markdown (.md)
                    </a>
                    <a href="{{ route('ai-agent.projects.release.migration-guide.export', $aiProject) }}?format=html"
                       class="mg-btn secondary" style="text-decoration:none;font-size:12px;padding:7px 14px;">
                        🌐 HTML (인쇄/공유용)
                    </a>
                </div>
            </div>
            @endif

            {{-- CLI hint --}}
            <div class="mg-stat-card">
                <div class="mg-stat-card-title">CLI</div>
                <pre style="background:#1e1b2e;color:#e2d9f3;border-radius:8px;padding:10px 14px;font-size:11px;margin:0;overflow-x:auto;white-space:pre-wrap;">php artisan ai-agent:release:migration-guide {{ $aiProject->id }}</pre>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function migrationGuide() {
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
            fetch('{{ route('ai-agent.projects.release.migration-guide.generate', $aiProject) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) window.location.reload();
                else alert(data.error || '생성에 실패했습니다.');
            })
            .catch(() => alert('서버 오류가 발생했습니다.'))
            .finally(() => { this.generating = false; });
        },

        saveEdit() {
            this.saving = true;
            fetch('{{ route('ai-agent.projects.release.migration-guide.update', $aiProject) }}', {
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
                } else alert(data.error || '저장에 실패했습니다.');
            })
            .catch(() => alert('서버 오류가 발생했습니다.'))
            .finally(() => { this.saving = false; });
        },

        markdownToHtml(md) {
            if (!md) return '';
            let html = md;

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

            // Bold
            html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Inline code
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
