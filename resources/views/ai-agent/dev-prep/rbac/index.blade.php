@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────── */
.rbac-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.rbac-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.rbac-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.rbac-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────── */
.rbac-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.rbac-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.rbac-btn.primary:hover { background:var(--t700,#6d28d9); }
.rbac-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.rbac-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.rbac-btn.secondary:hover { background:#e2e8f0; }
.rbac-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.rbac-btn.ghost:hover { background:#f5f3ff; }
.rbac-btn.danger    { background:#fef2f2; color:#b91c1c; border:1.5px solid #fecaca; }
.rbac-btn.danger:hover { background:#fee2e2; }
.rbac-btn.sm { padding:4px 10px; font-size:12px; }
.rbac-btn.xs { padding:2px 8px; font-size:11px; }

/* ── Section cards ──────────────────────────────────────────── */
.rbac-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.rbac-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }
.rbac-section-actions { margin-left:auto; display:flex; gap:6px; }

/* ── Info grid ──────────────────────────────────────────────── */
.rbac-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px; }
.rbac-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.rbac-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.rbac-info-value { font-size:18px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Progress ───────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }

/* ── Role cards ─────────────────────────────────────────────── */
.role-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px; }
.role-card { border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; background:#fff; }
.role-card-key  { font-size:11px; font-weight:700; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:6px; padding:2px 8px; display:inline-block; margin-bottom:6px; }
.role-card-name { font-size:14px; font-weight:800; color:#1e1b2e; margin-bottom:4px; }
.role-card-desc { font-size:12px; color:#64748b; margin-bottom:10px; min-height:16px; }
.role-card-perms { font-size:11.5px; color:#94a3b8; margin-bottom:10px; }
.role-card-actions { display:flex; gap:6px; }
.role-card-edit-form { margin-top:10px; border-top:1px solid #ede8ff; padding-top:10px; }
.role-card-edit-form input,
.role-card-edit-form textarea,
.role-card-edit-form select { width:100%; border:1.5px solid #e2e8f0; border-radius:7px; padding:6px 9px; font-size:12px; color:#1e1b2e; margin-bottom:6px; }
.role-card-edit-form label { font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:3px; }
.add-role-card { border:1.5px dashed #c4b5fd; border-radius:12px; padding:14px 16px; background:#fafafe; display:flex; flex-direction:column; gap:8px; }
.add-role-card input,
.add-role-card textarea { width:100%; border:1.5px solid #e2e8f0; border-radius:7px; padding:6px 9px; font-size:12px; color:#1e1b2e; }
.add-role-card label { font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:3px; }

/* ── Matrix ─────────────────────────────────────────────────── */
.matrix-wrap { overflow-x:auto; }
.matrix-table { width:100%; border-collapse:collapse; font-size:12.5px; }
.matrix-table th { background:#f8f5ff; padding:7px 12px; text-align:center; font-weight:700; color:#7c3aed; border:1px solid #ede8ff; white-space:nowrap; }
.matrix-table th.role-col { text-align:left; min-width:120px; }
.matrix-table td { padding:7px 12px; text-align:center; border:1px solid #ede8ff; }
.matrix-table td.role-name { text-align:left; font-weight:700; color:#1e1b2e; }
.matrix-cell-check { font-size:15px; }
.matrix-table tr:hover td { background:#fafafe; }
.matrix-edit-grid { display:grid; grid-template-columns:auto repeat(7, 1fr); gap:2px; align-items:center; font-size:12.5px; }
.matrix-edit-header { font-weight:700; color:#7c3aed; padding:6px 8px; text-align:center; }
.matrix-edit-role { font-weight:700; color:#1e1b2e; padding:6px 8px; }
.matrix-edit-cell { display:flex; justify-content:center; align-items:center; padding:4px; }
.matrix-edit-cell input[type=checkbox] { width:15px; height:15px; accent-color:#7c3aed; cursor:pointer; }

/* ── Permission badge ────────────────────────────────────────── */
.perm-badge { font-size:10.5px; font-weight:600; font-family:monospace; background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; border-radius:99px; padding:1px 8px; display:inline-block; margin:1px; }
.action-badge { font-size:10px; font-weight:700; padding:1px 6px; border-radius:99px; }
.action-badge.view    { background:#dbeafe; color:#1e40af; }
.action-badge.create  { background:#d1fae5; color:#065f46; }
.action-badge.edit    { background:#fef3c7; color:#92400e; }
.action-badge.delete  { background:#fee2e2; color:#991b1b; }
.action-badge.approve { background:#ede9fe; color:#5b21b6; }
.action-badge.export  { background:#e0f2fe; color:#075985; }
.action-badge.manage  { background:#f1f5f9; color:#475569; }

/* ── Policy cards ────────────────────────────────────────────── */
.policy-card { border:1.5px solid #fde68a; border-radius:12px; padding:14px 16px; background:#fffbeb; margin-bottom:10px; }
.policy-card-header { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.policy-card-name { font-size:13px; font-weight:800; color:#92400e; font-family:monospace; }
.policy-review-badge { font-size:10.5px; font-weight:700; background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:99px; padding:2px 8px; }
.policy-method { font-size:12px; color:#78350f; padding:3px 0; }
.policy-method code { font-family:monospace; font-weight:700; }

/* ── Prereq / Banner ────────────────────────────────────────── */
.prereq-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; border:1.5px solid #e2e8f0; margin-bottom:8px; font-size:13px; }
.prereq-item.ok   { background:#f0fdf4; border-color:#bbf7d0; }
.prereq-item.miss { background:#fffbeb; border-color:#fde68a; }
.prereq-item.none { background:#f8fafc; border-color:#e2e8f0; }
.proceed-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; }
.proceed-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.proceed-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.proceed-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 22px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.proceed-start-btn:hover { background:#f5f3ff; }
.proceed-start-btn:disabled { opacity:.5; cursor:not-allowed; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Modal ───────────────────────────────────────────────────── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.modal-box { background:#fff; border-radius:16px; padding:24px; max-width:700px; width:100%; max-height:80vh; overflow-y:auto; }
.modal-title { font-size:15px; font-weight:800; color:#1e1b2e; margin:0 0 16px; }
</style>
@endpush

@section('ai-agent-content')

{{-- JSON 데이터 아일랜드 --}}
<script type="application/json" id="rbac-data">
{
    "hasErd": {{ $hasErd ? 'true' : 'false' }},
    "hasApiSpec": {{ $hasApiSpec ? 'true' : 'false' }},
    "hasRbac": {{ $hasRbac ? 'true' : 'false' }},
    "screenCount": {{ $screenCount }},
    "roles": {{ json_encode($roles) }},
    "permissions": {{ json_encode($permissions) }},
    "policies": {{ json_encode($policies) }},
    "rolesCount": {{ $rolesCount }},
    "permissionsCount": {{ $permissionsCount }},
    "policiesCount": {{ $policiesCount }},
    "startUrl": "{{ $startUrl }}",
    "sseUrlTpl": "{{ $sseUrlTpl }}",
    "saveUrl": "{{ $saveUrl }}",
    "exportUrl": "{{ $exportUrl }}",
    "regenerateUrl": "{{ $regenerateUrl }}",
    "rolesStoreUrl": "{{ $rolesStoreUrl }}",
    "rolesUpdateUrlTpl": "{{ route('ai-agent.projects.pre-dev.rbac.roles.update', [$project, 'ROLE_KEY']) }}",
    "rolesDestroyUrlTpl": "{{ route('ai-agent.projects.pre-dev.rbac.roles.destroy', [$project, 'ROLE_KEY']) }}",
    "permissionsStoreUrl": "{{ $permissionsStoreUrl }}",
    "matrixUpdateUrl": "{{ $matrixUpdateUrl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="rbacIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="rbac-header">
        <div class="rbac-header-left">
            <h1>권한 모델 — RBAC</h1>
            <p>ERD·API 명세를 분석하여 역할·권한·정책을 자동 설계하고 직접 편집할 수 있습니다.</p>
        </div>
        <div class="rbac-header-right" x-show="hasRbac">
            <div style="position:relative;" x-data="{ open:false }">
                <button class="rbac-btn secondary sm" @click="open=!open">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    내보내기 ▾
                </button>
                <div x-show="open" @click.outside="open=false"
                     style="position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;min-width:180px;z-index:20;box-shadow:0 4px 12px rgba(0,0,0,.08);">
                    <a :href="cfg.exportUrl + '?format=json'"     class="rbac-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">JSON (.json)</a>
                    <a :href="cfg.exportUrl + '?format=markdown'" class="rbac-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">Markdown (.md)</a>
                    <a :href="cfg.exportUrl + '?format=policy'"   class="rbac-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">Laravel Policy (.php)</a>
                </div>
            </div>
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="rbac-btn ghost sm">버전 이력</a>
            @endif
        </div>
    </div>

    {{-- ── 상태 A: 생성 전 ── --}}
    <template x-if="!hasRbac && !isGenerating">
        <div>
            <div class="rbac-section">
                <div class="rbac-section-title">사전 조건</div>
                <div class="prereq-item {{ $hasErd ? 'ok' : 'miss' }}">
                    <span>{{ $hasErd ? '✅' : '⚠️' }}</span>
                    <span>ERD (데이터 모델)</span>
                    @if(!$hasErd)<span style="font-size:11.5px;color:#92400e;margin-left:auto;">
                        <a href="{{ route('ai-agent.projects.pre-dev.erd', $project) }}" style="color:#92400e;font-weight:700;">→ ERD 생성하러 가기</a>
                    </span>@endif
                </div>
                <div class="prereq-item {{ $hasApiSpec ? 'ok' : 'miss' }}">
                    <span>{{ $hasApiSpec ? '✅' : '⚠️' }}</span>
                    <span>API 명세서</span>
                    @if(!$hasApiSpec)<span style="font-size:11.5px;color:#92400e;margin-left:auto;">
                        <a href="{{ route('ai-agent.projects.pre-dev.api-spec', $project) }}" style="color:#92400e;font-weight:700;">→ API 명세 생성하러 가기</a>
                    </span>@endif
                </div>
                <div class="prereq-item {{ $screenCount > 0 ? 'ok' : 'none' }}">
                    <span>{{ $screenCount > 0 ? '✅' : '⚠️' }}</span>
                    <span>화면 목록</span>
                    <span style="font-size:12px;color:#64748b;margin-left:auto;">{{ $screenCount }}건</span>
                </div>
            </div>

            <div class="proceed-banner">
                <div class="proceed-banner-text">
                    <h3>RBAC 권한 모델 자동 생성 (웍스 Tool Use)</h3>
                    <p>ERD 테이블·API 엔드포인트를 분석하여 역할·권한 매트릭스·Policy 후보를 자동 설계합니다. 웍스 초안을 사용자가 직접 편집할 수 있습니다.</p>
                </div>
                <button class="proceed-start-btn" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    RBAC 자동 생성
                </button>
            </div>
        </div>
    </template>

    {{-- ── 상태 B: 생성 중 ── --}}
    <template x-if="isGenerating">
        <div class="rbac-section">
            <div class="rbac-section-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                RBAC 권한 모델 설계 중...
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progressPct}%`"></div>
            </div>
            <div style="font-size:12.5px;color:#64748b;" x-text="statusMessage"></div>
        </div>
    </template>

    {{-- ── 상태 C: 완료 ── --}}
    <template x-if="hasRbac && !isGenerating">
        <div>
            {{-- 통계 --}}
            <div class="rbac-info-grid" style="margin-bottom:18px;">
                <div class="rbac-info-card">
                    <div class="rbac-info-label">역할 수</div>
                    <div class="rbac-info-value" x-text="roles.length"></div>
                </div>
                <div class="rbac-info-card">
                    <div class="rbac-info-label">권한 수</div>
                    <div class="rbac-info-value" x-text="permissions.length"></div>
                </div>
                <div class="rbac-info-card">
                    <div class="rbac-info-label">Policy 후보</div>
                    <div class="rbac-info-value" x-text="policies.length"></div>
                </div>
            </div>

            {{-- ── 역할 카드 ── --}}
            <div class="rbac-section">
                <div class="rbac-section-title">
                    역할 (Roles)
                    <span class="rbac-section-actions">
                        <button class="rbac-btn ghost xs" @click="showAddRole = !showAddRole">+ 역할 추가</button>
                    </span>
                </div>

                <div class="role-grid">
                    {{-- 기존 역할 카드 --}}
                    <template x-for="role in roles" :key="role.key">
                        <div class="role-card">
                            <span class="role-card-key" x-text="role.key"></span>
                            <div class="role-card-name" x-text="role.name"></div>
                            <div class="role-card-desc" x-text="role.description || '설명 없음'"></div>
                            <div class="role-card-perms" x-text="(role.permissions?.length || 0) + '개 권한'"></div>

                            {{-- 편집 폼 --}}
                            <template x-if="editingRoleKey === role.key">
                                <div class="role-card-edit-form">
                                    <label>이름</label>
                                    <input type="text" x-model="editRoleForm.name">
                                    <label>설명</label>
                                    <textarea rows="2" x-model="editRoleForm.description"></textarea>
                                    <div style="display:flex;gap:6px;margin-top:4px;">
                                        <button class="rbac-btn primary xs" @click="saveEditRole(role.key)">저장</button>
                                        <button class="rbac-btn secondary xs" @click="cancelEdit()">취소</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="editingRoleKey !== role.key">
                                <div class="role-card-actions">
                                    <button class="rbac-btn secondary xs" @click="startEditRole(role)">편집</button>
                                    <button class="rbac-btn danger xs" @click="deleteRole(role.key)">삭제</button>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- 역할 추가 카드 --}}
                    <template x-if="showAddRole">
                        <div class="add-role-card">
                            <div style="font-size:13px;font-weight:700;color:#7c3aed;margin-bottom:4px;">새 역할 추가</div>
                            <label>키 (영문 소문자)</label>
                            <input type="text" x-model="addRoleForm.key" placeholder="예: developer">
                            <label>이름</label>
                            <input type="text" x-model="addRoleForm.name" placeholder="예: 개발자">
                            <label>설명</label>
                            <textarea rows="2" x-model="addRoleForm.description" placeholder="역할 설명"></textarea>
                            <div style="display:flex;gap:6px;margin-top:4px;">
                                <button class="rbac-btn primary xs" @click="saveNewRole()">추가</button>
                                <button class="rbac-btn secondary xs" @click="cancelAddRole()">취소</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── 권한 매트릭스 ── --}}
            <div class="rbac-section">
                <div class="rbac-section-title">
                    권한 매트릭스
                    <span class="rbac-section-actions">
                        <button class="rbac-btn ghost xs" @click="showMatrixEdit = !showMatrixEdit"
                                x-text="showMatrixEdit ? '✕ 닫기' : '✎ 편집'"></button>
                    </span>
                </div>

                {{-- 읽기 전용 요약 매트릭스 --}}
                <template x-if="!showMatrixEdit">
                    <div class="matrix-wrap">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th class="role-col">역할</th>
                                    <template x-for="action in allActions" :key="action">
                                        <th x-text="action"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="role in roles" :key="role.key">
                                    <tr>
                                        <td class="role-name">
                                            <div x-text="role.name"></div>
                                            <span class="role-card-key" style="font-size:10px;" x-text="role.key"></span>
                                        </td>
                                        <template x-for="action in allActions" :key="action">
                                            <td>
                                                <span class="matrix-cell-check"
                                                      x-text="roleHasAction(role.key, action) ? '✅' : '—'"></span>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                {{-- 편집 매트릭스 --}}
                <template x-if="showMatrixEdit">
                    <div>
                        <div style="font-size:12px;color:#64748b;margin-bottom:12px;">
                            체크 표시 = 해당 action의 모든 권한을 역할에 부여/제거합니다.
                        </div>
                        <div class="matrix-wrap">
                            <table class="matrix-table">
                                <thead>
                                    <tr>
                                        <th class="role-col">역할</th>
                                        <template x-for="action in allActions" :key="action">
                                            <th x-text="action"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="role in roles" :key="role.key">
                                        <tr>
                                            <td class="role-name" x-text="role.name"></td>
                                            <template x-for="action in allActions" :key="action">
                                                <td>
                                                    <input type="checkbox"
                                                           style="width:15px;height:15px;accent-color:#7c3aed;cursor:pointer;"
                                                           :checked="matrixState[role.key]?.[action] ?? roleHasAction(role.key, action)"
                                                           @change="toggleMatrix(role.key, action, $event.target.checked)">
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex;gap:8px;margin-top:12px;">
                            <button class="rbac-btn primary sm" @click="saveMatrix()">매트릭스 저장</button>
                            <button class="rbac-btn secondary sm" @click="cancelMatrix()">취소</button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ── Policy 후보 ── --}}
            <template x-if="policies.length > 0">
                <div class="rbac-section">
                    <div class="rbac-section-title">
                        Policy 후보
                        <span style="font-size:11px;font-weight:400;color:#92400e;background:#fef3c7;border-radius:99px;padding:2px 8px;margin-left:4px;">⚠️ 웍스 추정 — 비즈니스 규칙 검토 필요</span>
                    </div>
                    <template x-for="policy in policies" :key="policy.name">
                        <div class="policy-card">
                            <div class="policy-card-header">
                                <span class="policy-card-name" x-text="policy.name"></span>
                                <span style="font-size:11.5px;color:#78350f;background:#fef3c7;border-radius:6px;padding:1px 7px;"
                                      x-text="'대상: ' + (policy.model || '')"></span>
                                <template x-if="policy.requires_review">
                                    <span class="policy-review-badge">검토 필요</span>
                                </template>
                            </div>
                            <div style="font-size:12px;color:#92400e;margin-bottom:8px;" x-text="policy.description"></div>
                            <template x-for="(condition, method) in (policy.methods || {})" :key="method">
                                <div class="policy-method">
                                    <code x-text="method + '()'"></code>
                                    <span style="color:#64748b;"> — </span>
                                    <span x-text="condition"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 권한 목록 (접이식) --}}
            <div class="rbac-section" x-data="{ showPerms: false }">
                <div class="rbac-section-title">
                    권한 목록 (<span x-text="permissions.length"></span>개)
                    <span class="rbac-section-actions">
                        <button class="rbac-btn ghost xs" @click="showPerms = !showPerms"
                                x-text="showPerms ? '접기' : '펼치기'"></button>
                    </span>
                </div>
                <template x-if="showPerms">
                    <div>
                        <template x-for="resource in uniqueResources" :key="resource">
                            <div style="margin-bottom:12px;">
                                <div style="font-size:12px;font-weight:700;color:#1e1b2e;margin-bottom:6px;" x-text="resource"></div>
                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                    <template x-for="perm in permsByResource(resource)" :key="perm.key">
                                        <span>
                                            <span class="perm-badge" x-text="perm.key"></span>
                                            <span :class="'action-badge ' + perm.action" x-text="perm.action"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- 액션 --}}
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
                <button class="rbac-btn primary" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    재생성
                </button>
                <a :href="cfg.exportUrl + '?format=markdown'" class="rbac-btn secondary">Markdown 다운로드</a>
                <a :href="cfg.exportUrl + '?format=policy'"   class="rbac-btn ghost">Laravel Policy 코드</a>
                <a :href="cfg.exportUrl + '?format=json'"     class="rbac-btn ghost">JSON 다운로드</a>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
async function rbacIndex() {
    const raw = JSON.parse(document.getElementById('rbac-data').textContent);

    return {
        cfg:            raw,
        hasRbac:        raw.hasRbac,
        isGenerating:   false,
        progressPct:    0,
        statusMessage:  '',
        roles:          raw.roles          || [],
        permissions:    raw.permissions    || [],
        policies:       raw.policies       || [],
        eventSource:    null,

        // Role editing state
        editingRoleKey: null,
        editRoleForm:   { name: '', description: '' },
        showAddRole:    false,
        addRoleForm:    { key: '', name: '', description: '' },

        // Matrix editing state
        showMatrixEdit: false,
        matrixState:    {},

        allActions: ['view', 'create', 'edit', 'delete', 'approve', 'export', 'manage'],

        init() {},

        // ── Computed helpers ─────────────────────────────────────

        get uniqueResources() {
            return [...new Set(this.permissions.map(p => p.resource).filter(Boolean))];
        },

        permsByResource(resource) {
            return this.permissions.filter(p => p.resource === resource);
        },

        roleHasAction(roleKey, action) {
            const role = this.roles.find(r => r.key === roleKey);
            if (!role) return false;
            return this.permissions.some(p => p.action === action && (role.permissions || []).includes(p.key));
        },

        // ── Generation ───────────────────────────────────────────

        async startGeneration() {
            this.isGenerating  = true;
            this.progressPct   = 0;
            this.statusMessage = '생성 준비 중...';

            try {
                const res = await fetch(this.cfg.startUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '시작 실패');
                this.connectSse(data.sessionId);
            } catch (e) {
                this.isGenerating = false;
                alert('RBAC 생성 시작 실패: ' + e.message);
            }
        },

        connectSse(sessionId) {
            const url = this.cfg.sseUrlTpl.replace('SESSION_ID', sessionId);
            this.eventSource = new EventSource(url);
            this.eventSource.addEventListener('status',   (e) => this.onStatus(JSON.parse(e.data)));
            this.eventSource.addEventListener('progress', (e) => this.onProgress(JSON.parse(e.data)));
            this.eventSource.addEventListener('complete', (e) => this.onComplete(JSON.parse(e.data)));
            this.eventSource.addEventListener('error',    (e) => {
                const d = JSON.parse(e.data || '{}');
                this.isGenerating = false;
                this.eventSource?.close();
                alert('RBAC 생성 오류: ' + (d.message || '알 수 없는 오류'));
            });
        },

        onStatus(data)   { this.progressPct = data.progress || this.progressPct; this.statusMessage = data.message || ''; },
        onProgress(data) { this.progressPct = data.progress || this.progressPct; this.statusMessage = data.message || ''; },

        onComplete(data) {
            this.eventSource?.close();
            this.isGenerating = false;
            this.progressPct  = 100;
            this.hasRbac      = true;
            this.roles        = data.roles       || [];
            this.permissions  = data.permissions || [];
            this.policies     = data.policies    || [];
            this.showMatrixEdit = false;
            this.matrixState    = {};
        },

        // ── Role CRUD ────────────────────────────────────────────

        startEditRole(role) {
            this.editingRoleKey  = role.key;
            this.editRoleForm    = { name: role.name, description: role.description || '' };
        },

        cancelEdit() { this.editingRoleKey = null; },

        async saveEditRole(roleKey) {
            const url = this.cfg.rolesUpdateUrlTpl.replace('ROLE_KEY', roleKey);
            try {
                const res  = await fetch(url, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body:    JSON.stringify(this.editRoleForm),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                this.roles         = data.roles;
                this.editingRoleKey = null;
            } catch (e) {
                alert('저장 실패: ' + e.message);
            }
        },

        async deleteRole(roleKey) {
            if (!await __confirm(`역할 '${roleKey}'를 삭제할까요?`)) return;
            const url = this.cfg.rolesDestroyUrlTpl.replace('ROLE_KEY', roleKey);
            try {
                const res  = await fetch(url, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                this.roles = data.roles;
            } catch (e) {
                alert('삭제 실패: ' + e.message);
            }
        },

        cancelAddRole() { this.showAddRole = false; this.addRoleForm = { key: '', name: '', description: '' }; },

        async saveNewRole() {
            if (!this.addRoleForm.key || !this.addRoleForm.name) {
                alert('키와 이름을 입력해주세요.');
                return;
            }
            try {
                const res  = await fetch(this.cfg.rolesStoreUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body:    JSON.stringify(this.addRoleForm),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                this.roles       = data.roles;
                this.showAddRole = false;
                this.addRoleForm = { key: '', name: '', description: '' };
            } catch (e) {
                alert('추가 실패: ' + e.message);
            }
        },

        // ── Matrix ───────────────────────────────────────────────

        toggleMatrix(roleKey, action, value) {
            if (!this.matrixState[roleKey]) this.matrixState[roleKey] = {};
            this.matrixState[roleKey][action] = value;
        },

        cancelMatrix() {
            this.showMatrixEdit = false;
            this.matrixState    = {};
        },

        async saveMatrix() {
            const matrix = {};
            for (const role of this.roles) {
                matrix[role.key] = {};
                for (const action of this.allActions) {
                    matrix[role.key][action] = this.matrixState[role.key]?.[action] ?? this.roleHasAction(role.key, action);
                }
            }
            try {
                const res  = await fetch(this.cfg.matrixUpdateUrl, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body:    JSON.stringify({ matrix }),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message);
                this.roles          = data.roles;
                this.showMatrixEdit = false;
                this.matrixState    = {};
            } catch (e) {
                alert('매트릭스 저장 실패: ' + e.message);
            }
        },
    };
}
</script>
@endpush
