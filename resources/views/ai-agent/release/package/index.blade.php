@extends('layouts.ai-agent')
@section('title', '통합 릴리즈 패키지 — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.pkg-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.pkg-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.pkg-header p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Grid ─────────────────────────────────────────────────────────── */
.pkg-grid { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media(max-width:900px) { .pkg-grid { grid-template-columns:1fr; } }

/* ── Section ─────────────────────────────────────────────────────── */
.pkg-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.pkg-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* ── Prereq list ─────────────────────────────────────────────────── */
.pkg-prereq-list { display:flex; flex-direction:column; gap:7px; }
.pkg-prereq-item { display:flex; align-items:center; gap:9px; font-size:13px; padding:8px 12px; border-radius:9px; }
.pkg-prereq-item.ok  { background:#f0fdf4; color:#15803d; }
.pkg-prereq-item.nok { background:#fef2f2; color:#b91c1c; }
.pkg-prereq-item span { font-weight:600; }

/* ── Action button ────────────────────────────────────────────────── */
.pkg-gen-btn { display:inline-flex; align-items:center; gap:7px; padding:11px 22px; background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; border:none; border-radius:10px; font-size:13.5px; font-weight:700; cursor:pointer; transition:opacity .15s; }
.pkg-gen-btn:hover { opacity:.9; }
.pkg-gen-btn:disabled { opacity:.5; cursor:not-allowed; }
.pkg-gen-btn.loading .btn-icon { display:none; }
.pkg-gen-btn.loading::after { content:''; display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Existing package card ────────────────────────────────────────── */
.pkg-card { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px; padding:16px 18px; }
.pkg-card-name { font-size:13.5px; font-weight:700; color:#1e1b2e; margin-bottom:6px; display:flex; align-items:center; gap:7px; }
.pkg-card-meta { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:#64748b; margin-bottom:12px; }
.pkg-card-meta span { display:flex; align-items:center; gap:4px; }
.pkg-actions { display:flex; gap:8px; flex-wrap:wrap; }
.pkg-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 13px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; border:1.5px solid; text-decoration:none; transition:all .15s; }
.pkg-btn.primary  { background:#7c3aed; color:#fff; border-color:#7c3aed; }
.pkg-btn.outline  { background:#fff; color:#475569; border-color:#e2e8f0; }
.pkg-btn.danger   { background:#fff; color:#b91c1c; border-color:#fca5a5; }
.pkg-btn:hover.primary { background:#6d28d9; }
.pkg-btn:hover.outline { background:#f1f5f9; }
.pkg-btn:hover.danger  { background:#fef2f2; }

/* ── Folder tree ─────────────────────────────────────────────────── */
.pkg-tree { display:flex; flex-direction:column; gap:4px; font-size:12.5px; }
.pkg-tree-folder { display:flex; align-items:center; gap:7px; padding:7px 10px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; cursor:pointer; transition:background .1s; }
.pkg-tree-folder:hover { background:#ede8ff; border-color:#c4b5fd; }
.pkg-tree-folder-name { font-weight:700; color:#1e1b2e; flex:1; }
.pkg-tree-folder-meta { font-size:11px; color:#94a3b8; }
.pkg-tree-folder-files { margin-left:20px; margin-top:4px; display:none; flex-direction:column; gap:2px; }
.pkg-tree-folder-files.open { display:flex; }
.pkg-tree-file { display:flex; align-items:center; gap:6px; padding:4px 10px; color:#475569; border-radius:6px; }
.pkg-tree-file:hover { background:#f1f5f9; }
.pkg-tree-root-file { display:flex; align-items:center; gap:7px; padding:7px 10px; border-radius:8px; color:#475569; }

/* ── Stats cards ─────────────────────────────────────────────────── */
.pkg-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
.pkg-stat-card { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 14px; text-align:center; }
.pkg-stat-value { font-size:18px; font-weight:800; color:#7c3aed; }
.pkg-stat-label { font-size:11px; color:#94a3b8; margin-top:2px; }

/* ── Phase summary ────────────────────────────────────────────────── */
.pkg-phase-list { display:flex; flex-direction:column; gap:6px; }
.pkg-phase-item { display:flex; align-items:center; gap:8px; padding:7px 12px; background:#f8fafc; border-radius:8px; font-size:12.5px; }
.pkg-phase-label { flex:1; color:#475569; }
.pkg-phase-count { font-weight:700; color:#7c3aed; font-size:11.5px; }
.pkg-phase-date  { font-size:11px; color:#94a3b8; }

/* ── Progress message ─────────────────────────────────────────────── */
.pkg-progress-msg { display:none; align-items:center; gap:8px; padding:10px 14px; background:#f0f9ff; border:1.5px solid #bae6fd; border-radius:9px; font-size:13px; color:#0369a1; margin-top:12px; }
.pkg-progress-msg.visible { display:flex; }
</style>
@endpush

@section('ai-agent-content')

<div class="pkg-header">
    <div>
        <h1>통합 릴리즈 패키지</h1>
        <p>Phase 1-4 전체 산출물을 단일 ZIP 패키지로 통합합니다. 배포팀/클라이언트 인도 시 사용합니다.</p>
    </div>
</div>

<div class="pkg-grid" x-data="releasePackage()">

    {{-- ─── 좌: 메인 영역 ──────────────────────────────────────────── --}}
    <div>

        {{-- 사전 조건 --}}
        <div class="pkg-section">
            <div class="pkg-section-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                사전 조건 ({{ count(array_filter($prereqs['items'], fn($i) => $i['approved'])) }}/{{ count($prereqs['items']) }} 완료)
            </div>
            <div class="pkg-prereq-list">
                @foreach($prereqs['items'] as $item)
                <div class="pkg-prereq-item {{ $item['approved'] ? 'ok' : 'nok' }}">
                    <span>{{ $item['approved'] ? '✅' : '❌' }}</span>
                    {{ $item['label'] }}
                    @if(!$item['approved'])
                        <span style="margin-left:auto;font-size:11px;opacity:.7;">{{ $item['status'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- 패키지 생성 버튼 --}}
        <div class="pkg-section">
            <div class="pkg-section-title">패키지 생성</div>

            @if(!$prereqs['can_build'])
            <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:9px;font-size:13px;color:#b91c1c;margin-bottom:14px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                Phase 1-4 모두 승인된 후 패키지를 생성할 수 있습니다.
            </div>
            @endif

            <p style="font-size:13px;color:#64748b;margin:0 0 14px;line-height:1.6;">
                생성 시 모든 산출물을 수집하여 ZIP 파일로 패키징합니다.
                <br>포함 내용: 기획 문서, 디자인 산출물, ERD/API 명세, 전체 소스코드, 코드 리뷰 결과
            </p>

            <button
                @click="generate()"
                :disabled="generating || !{{ $prereqs['can_build'] ? 'true' : 'false' }}"
                class="pkg-gen-btn"
                :class="{ loading: generating }">
                <span class="btn-icon">📦</span>
                <span x-text="generating ? '패키지 생성 중...' : '패키지 생성하기'"></span>
            </button>

            <div class="pkg-progress-msg" :class="{ visible: generating }">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin .8s linear infinite;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                산출물 수집 및 ZIP 패키징 중… 잠시만 기다려주세요.
            </div>

            <div x-show="genResult" x-cloak style="margin-top:12px;">
                <div :class="genResult?.success ? 'pkg-prereq-item ok' : 'pkg-prereq-item nok'" style="border-radius:9px;">
                    <span x-text="genResult?.success ? '✅' : '❌'"></span>
                    <span x-text="genResult?.message"></span>
                </div>
            </div>
        </div>

        {{-- 기존 패키지 --}}
        @if($existing)
        <div class="pkg-section">
            <div class="pkg-section-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                생성된 패키지
            </div>
            <div class="pkg-card">
                <div class="pkg-card-name">
                    <span>📦</span>
                    {{ basename($existing['path'] ?? 'package.zip') }}
                    @if(!$existing['exists'])
                        <span style="font-size:11px;background:#fee2e2;color:#b91c1c;padding:1px 7px;border-radius:99px;">파일 없음</span>
                    @endif
                </div>
                <div class="pkg-card-meta">
                    @if($existing['exists'])
                    <span>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ round($existing['size'] / 1024 / 1024, 2) }} MB
                    </span>
                    @endif
                    @if($existing['artifact'])
                    <span>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $existing['artifact']->created_at?->format('Y.m.d H:i') }}
                    </span>
                    @endif
                </div>
                <div class="pkg-actions">
                    @if($existing['exists'])
                    <a href="{{ route('ai-agent.projects.release.package.download', $project) }}"
                       class="pkg-btn primary">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        다운로드
                    </a>
                    <button @click="showPreview = !showPreview" class="pkg-btn outline">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        폴더 구조
                    </button>
                    <a href="{{ route('ai-agent.projects.release.package.manifest', $project) }}" target="_blank" class="pkg-btn outline">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Manifest
                    </a>
                    @endif
                    <button @click="deletePackage()" class="pkg-btn danger">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        삭제
                    </button>
                </div>
            </div>

            {{-- 폴더 구조 미리보기 --}}
            @if($preview)
            <div x-show="showPreview" x-cloak style="margin-top:16px;">
                <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">폴더 구조</div>
                <div class="pkg-tree">
                    @foreach($preview as $node)
                        @if($node['type'] === 'folder')
                        <div>
                            <div class="pkg-tree-folder" onclick="this.nextElementSibling.classList.toggle('open')">
                                <span>📂</span>
                                <span class="pkg-tree-folder-name">{{ $node['name'] }}</span>
                                <span class="pkg-tree-folder-meta">{{ $node['file_count'] }}개 · {{ round($node['total_size'] / 1024, 1) }} KB</span>
                                <svg width="11" height="11" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <div class="pkg-tree-folder-files">
                                @foreach($node['files'] as $file)
                                <div class="pkg-tree-file">
                                    <span>📄</span>
                                    <span style="flex:1;">{{ $file['name'] }}</span>
                                    <span style="font-size:11px;color:#94a3b8;">{{ round($file['size'] / 1024, 1) }} KB</span>
                                </div>
                                @endforeach
                                @if(count($node['files']) < $node['file_count'])
                                <div style="font-size:11px;color:#94a3b8;padding:3px 10px;">... 외 {{ $node['file_count'] - count($node['files']) }}개 더</div>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="pkg-tree-root-file">
                            <span>📄</span>
                            <span style="flex:1;font-weight:600;">{{ $node['name'] }}</span>
                            <span style="font-size:11px;color:#94a3b8;">{{ round(($node['size'] ?? 0) / 1024, 1) }} KB</span>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- ─── 우: 패키지 정보 ─────────────────────────────────────────── --}}
    <div>

        {{-- Manifest 통계 --}}
        @if($existing && !empty($existing['manifest']['stats']))
        @php $stats = $existing['manifest']['stats']; @endphp
        <div class="pkg-section">
            <div class="pkg-section-title">패키지 통계</div>
            <div class="pkg-stats">
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">{{ $stats['total_artifacts'] ?? 0 }}</div>
                    <div class="pkg-stat-label">전체 산출물</div>
                </div>
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">{{ $stats['total_screens'] ?? 0 }}</div>
                    <div class="pkg-stat-label">화면 수</div>
                </div>
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">{{ $stats['total_requirements'] ?? 0 }}</div>
                    <div class="pkg-stat-label">요구사항</div>
                </div>
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">{{ number_format($stats['total_tokens'] ?? 0) }}</div>
                    <div class="pkg-stat-label">웍스 토큰</div>
                </div>
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">${{ $stats['total_cost_usd'] ?? 0 }}</div>
                    <div class="pkg-stat-label">웍스 비용</div>
                </div>
                <div class="pkg-stat-card">
                    <div class="pkg-stat-value">{{ $stats['total_ai_calls'] ?? 0 }}</div>
                    <div class="pkg-stat-label">웍스 호출</div>
                </div>
            </div>
        </div>

        {{-- Phase 요약 --}}
        @if(!empty($existing['manifest']['phase_summaries']))
        <div class="pkg-section">
            <div class="pkg-section-title">Phase 요약</div>
            <div class="pkg-phase-list">
                @foreach($existing['manifest']['phase_summaries'] as $phase)
                <div class="pkg-phase-item">
                    <span style="font-size:11px;background:#ede8ff;color:#7c3aed;padding:1px 7px;border-radius:99px;font-weight:700;">P{{ $phase['phase'] }}</span>
                    <span class="pkg-phase-label">{{ $phase['label'] }}</span>
                    <span class="pkg-phase-count">{{ $phase['artifact_count'] }}개</span>
                    @if($phase['approved_at'])
                    <span class="pkg-phase-date">{{ date('m/d', strtotime($phase['approved_at'])) }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @endif

        {{-- 패키지 구성 가이드 --}}
        <div class="pkg-section">
            <div class="pkg-section-title">패키지 구성</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach([
                    ['01-planning/', '기획 문서', '기획서, AS-IS/TO-BE, IA'],
                    ['02-design/', '디자인 산출물', '토큰, 컴포넌트, 시스템 문서'],
                    ['03-dev-prep/', '개발 준비', 'ERD(SQL), API 명세(YAML), RBAC'],
                    ['04-frontend/', 'Frontend 코드', '화면별 SCR-XXX/ 폴더'],
                    ['05-backend/', 'Backend 코드', 'Resource별 Model/Controller'],
                    ['06-integration/', '통합 결과', 'API 연계, 코드 리뷰'],
                ] as [$folder, $label, $desc])
                <div style="display:flex;gap:8px;align-items:flex-start;padding:6px 10px;border-radius:8px;background:#f8fafc;">
                    <span style="font-size:11px;font-weight:700;background:#ede8ff;color:#7c3aed;padding:2px 7px;border-radius:6px;flex-shrink:0;margin-top:1px;">{{ $folder }}</span>
                    <div>
                        <div style="font-size:12.5px;font-weight:700;color:#1e1b2e;">{{ $label }}</div>
                        <div style="font-size:11.5px;color:#94a3b8;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CLI 안내 --}}
        <div class="pkg-section">
            <div class="pkg-section-title">CLI</div>
            <div style="background:#1e1b2e;border-radius:8px;padding:12px 14px;font-size:12px;color:#a78bfa;font-family:monospace;">
                php artisan ai-agent:release:package {{ $project->id }}
            </div>
            <div style="font-size:11.5px;color:#94a3b8;margin-top:8px;">
                <code style="font-size:11px;">--output=&lt;경로&gt;</code> 옵션으로 저장 위치 지정 가능
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
async function releasePackage() {
    return {
        generating: false,
        genResult:  null,
        showPreview: false,

        async generate() {
            if (this.generating) return;
            this.generating = true;
            this.genResult  = null;

            try {
                const res = await fetch('{{ route('ai-agent.projects.release.package.generate', $project) }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                const data = await res.json();
                this.genResult = data;
                if (data.success) {
                    // Reload to show new package info
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch (e) {
                this.genResult = { success: false, message: '네트워크 오류가 발생했습니다.' };
            } finally {
                this.generating = false;
            }
        },

        async deletePackage() {
            if (!await __confirm('패키지를 삭제하시겠습니까? 복구할 수 없습니다.')) return;

            try {
                await fetch('{{ route('ai-agent.projects.release.package.destroy', $project) }}', {
                    method:  'DELETE',
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                window.location.reload();
            } catch (e) {
                alert('삭제 실패: ' + e.message);
            }
        },
    };
}
</script>
@endpush
