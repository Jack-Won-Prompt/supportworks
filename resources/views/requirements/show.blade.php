@extends('layouts.app')

@section('title', $project->name . ' - ' . $requirement->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.requirements.index', $project) }}" class="hover:text-indigo-500 transition-colors">요구사항</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ Str::limit($requirement->title, 30) }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    @if($requirement->reporter_id === auth()->id() || auth()->user()->isAdmin())
    <button onclick="confirmDelete()"
            style="padding:6px 13px;font-size:12px;font-weight:500;color:#dc2626;border:1.5px solid #fecaca;border-radius:8px;background:#fff;cursor:pointer;"
            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">삭제</button>
    @endif
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'requirements'])

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- 좌측: 본문 영역 --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- 제목 카드 --}}
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:20px 24px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;background:{{ $requirement->priority_color['bg'] }};color:{{ $requirement->priority_color['text'] }};">{{ $requirement->priority_label }}</span>
                <span style="padding:2px 8px;border-radius:5px;font-size:11px;font-weight:500;background:{{ $requirement->status_color['bg'] }};color:{{ $requirement->status_color['text'] }};">{{ $requirement->status_label }}</span>
                <span style="font-size:11px;color:#9ca3af;">{{ $requirement->category_label }}</span>
            </div>
            <h1 id="req-title-display" style="font-size:20px;font-weight:700;color:#18181b;margin:0 0 4px;line-height:1.4;">{{ $requirement->title }}</h1>
            <p style="font-size:12px;color:#9ca3af;margin:0;">
                {{ $requirement->reporter?->name }} 등록 · {{ $requirement->created_at->format('Y-m-d H:i') }}
                @if($requirement->updated_at->ne($requirement->created_at))
                · 수정 {{ $requirement->updated_at->format('Y-m-d H:i') }}
                @endif
                @if($requirement->source_type === 'analysis')
                    <span style="margin-left:6px;padding:1px 7px;border-radius:4px;background:#ede9fe;color:#7c3aed;font-size:11px;font-weight:600;">
                        웍스 분석
                        @if($requirement->ai_confidence)
                            {{ round($requirement->ai_confidence * 100) }}%
                        @endif
                    </span>
                    @if($requirement->source_session_id)
                        <a href="{{ route('projects.requirements.analysis.show', [$project, $requirement->source_session_id]) }}"
                           style="margin-left:4px;font-size:11px;color:#7c3aed;text-decoration:none;hover:underline;">분석 보기</a>
                    @endif
                @endif
            </p>
        </div>

        {{-- 설명 카드 --}}
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:20px 24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <h2 style="font-size:13px;font-weight:700;color:#374151;margin:0;">설명</h2>
                <button onclick="toggleDescEdit()"
                        style="font-size:12px;color:var(--t500);background:none;border:none;cursor:pointer;padding:0;">편집</button>
            </div>
            <div id="desc-view" style="font-size:13px;color:#374151;line-height:1.7;white-space:pre-wrap;">{{ $requirement->description ?: '설명이 없습니다.' }}</div>
            <div id="desc-edit" style="display:none;">
                <textarea id="desc-input" rows="8"
                          style="width:100%;padding:10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                          onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">{{ $requirement->description }}</textarea>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button onclick="saveDesc()"
                            style="padding:6px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">저장</button>
                    <button onclick="toggleDescEdit()"
                            style="padding:6px 14px;font-size:12px;color:#6b7280;border:1.5px solid #e4e4e7;background:#fff;border-radius:7px;cursor:pointer;">취소</button>
                </div>
            </div>
        </div>

        {{-- 댓글 카드 --}}
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:20px 24px;">
            <h2 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 16px;">댓글 ({{ $requirement->comments->count() }})</h2>

            <div id="comment-list" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
                @foreach($requirement->comments as $comment)
                <div style="padding:12px 14px;background:#f9fafb;border-radius:8px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ $comment->author->name }}</span>
                        <span style="font-size:11px;color:#9ca3af;">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0;white-space:pre-wrap;">{{ $comment->content }}</p>
                </div>
                @endforeach
            </div>

            <div style="display:flex;gap:8px;align-items:flex-end;">
                <textarea id="comment-input" rows="2" placeholder="댓글을 입력하세요..."
                          style="flex:1;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:none;font-family:inherit;"
                          onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
                <button onclick="postComment()"
                        style="padding:8px 14px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;white-space:nowrap;"
                        onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">등록</button>
            </div>
        </div>

        {{-- 변경 이력 --}}
        @if($requirement->changeHistories->count())
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:20px 24px;">
            <h2 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">변경 이력</h2>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($requirement->changeHistories as $hist)
                <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#6b7280;padding:6px 0;border-bottom:1px solid #f9fafb;">
                    <span style="color:#374151;font-weight:600;">{{ $hist->changedBy?->name }}</span>
                    <span>이 <strong>{{ $hist->field_name }}</strong> 를</span>
                    <span style="background:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:4px;font-size:11px;">{{ $hist->old_value ?: '(없음)' }}</span>
                    <span>→</span>
                    <span style="background:#d1fae5;color:#065f46;padding:1px 6px;border-radius:4px;font-size:11px;">{{ $hist->new_value ?: '(없음)' }}</span>
                    <span style="margin-left:auto;white-space:nowrap;">{{ $hist->changed_at->format('Y-m-d H:i') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- 우측: 메타 사이드바 --}}
    <div style="display:flex;flex-direction:column;gap:12px;">

        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:18px;">
            <h3 style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 14px;">세부 정보</h3>

            {{-- 상태 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">상태</label>
                <select id="status-select" onchange="patchField('status', this.value)"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                    @foreach(\App\Models\Requirement::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ $requirement->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 우선순위 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">우선순위</label>
                <select id="priority-select" onchange="patchField('priority', this.value)"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                    @foreach(\App\Models\Requirement::PRIORITY_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ $requirement->priority === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 카테고리 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">카테고리</label>
                <select id="category-select" onchange="patchField('category', this.value)"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                    @foreach(\App\Models\Requirement::CATEGORY_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ $requirement->category === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 담당자 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">담당자</label>
                <select id="assignee-select" onchange="patchField('assignee_id', this.value)"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                    <option value="">담당자 없음</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ $requirement->assignee_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 등록자 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">등록자</label>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $requirement->reporter?->name ?? '-' }}</p>
            </div>

            {{-- 태그 --}}
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">태그</label>
                @if($requirement->tags)
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($requirement->tags as $tag)
                            <span style="padding:2px 8px;background:#f3f4f6;border-radius:5px;font-size:11px;color:#6b7280;">{{ $tag }}</span>
                        @endforeach
                    </div>
                @else
                    <p style="font-size:13px;color:#9ca3af;margin:0;">없음</p>
                @endif
            </div>

            {{-- 알림 구독 --}}
            <div>
                <button id="watch-btn" onclick="toggleWatch()"
                        style="width:100%;padding:7px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid {{ $isWatching ? '#c7d2fe' : '#e4e4e7' }};background:{{ $isWatching ? '#eef2ff' : '#fff' }};color:{{ $isWatching ? 'var(--t600)' : '#6b7280' }};cursor:pointer;">
                    {{ $isWatching ? '🔔 알림 구독 중' : '🔕 알림 받기' }}
                </button>
            </div>

            {{-- 일정 Task 추가 (3단계 워크플로우) --}}
            <div style="border-top:1px solid #f3f4f6;margin-top:12px;padding-top:12px;">
                @if($inGantt)
                    {{-- 3단계: Task 완료 --}}
                    <div style="width:100%;padding:7px;font-size:12px;font-weight:700;border-radius:8px;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#16a34a;text-align:center;display:flex;align-items:center;justify-content:center;gap:5px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>일정 Task 추가 완료
                    </div>
                @elseif($requirement->applied_to_plan)
                    {{-- 2단계: Task 추가 가능 --}}
                    <button onclick="openSchModal()"
                            style="width:100%;padding:7px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid #bae6fd;background:#f0f9ff;color:#0369a1;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;"
                            onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#f0f9ff'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>일정 Task 추가
                    </button>
                @else
                    {{-- 1단계: 기획서 먼저 --}}
                    <div style="width:100%;padding:7px;font-size:11px;font-weight:500;border-radius:8px;border:1.5px solid #e4e4e7;background:#f9fafb;color:#9ca3af;text-align:center;cursor:not-allowed;"
                         title="기획서에 추가된 후 Task 등록이 가능합니다">
                        📅 Task 추가 (기획서 추가 후 가능)
                    </div>
                @endif
            </div>
        </div>

        {{-- SI 모드 카드 --}}
        @if($project->si_mode_enabled)
        <div style="background:#fff;border:1px solid #ede9fe;border-radius:12px;padding:18px;">
            <h3 style="font-size:12px;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.05em;margin:0 0 14px;">SI 계약 모드</h3>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">요구사항 유형</label>
                <select onchange="patchField('requirement_type', this.value)"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                    @foreach(\App\Models\Requirement::TYPE_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ $requirement->requirement_type === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">출처 참조</label>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $requirement->source_ref ?: '-' }}</p>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">승인 상태</label>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span id="approval-badge"
                          style="padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;background:{{ $requirement->approval_color['bg'] }};color:{{ $requirement->approval_color['text'] }};">
                        {{ $requirement->approval_label }}
                    </span>
                    @if($isManager)
                    <select id="approval-select" onchange="updateApproval(this.value)"
                            style="flex:1;padding:5px 8px;border:1.5px solid #e4e4e7;border-radius:6px;font-size:12px;outline:none;background:#fff;">
                        @foreach(\App\Models\Requirement::APPROVAL_LABELS as $val => $label)
                            <option value="{{ $val }}" {{ $requirement->approval_status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @endif
                </div>
            </div>

            @if($requirement->approver)
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#9ca3af;margin-bottom:5px;">승인자 / 승인일</label>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $requirement->approver->name }} / {{ $requirement->approved_at?->format('Y-m-d') }}</p>
            </div>
            @endif
        </div>
        @endif

        {{-- 기획서 적용 이력 --}}
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:18px;">
            <h3 style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">기획서 적용 이력</h3>
            @php $apps = $requirement->planApplications; @endphp
            @if($apps->isEmpty())
                <p style="font-size:12px;color:#9ca3af;margin:0;">아직 기획서에 적용되지 않았습니다.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($apps as $app)
                    <div style="background:#f9fafb;border-radius:8px;padding:10px 12px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <a href="{{ route('projects.planning.show', [$project, $app->plan_id]) }}"
                               style="font-size:12px;font-weight:600;color:var(--t500);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                📄 {{ $app->plan?->title ?? '기획서 #' . $app->plan_id }}
                            </a>
                            <button onclick="revertApp({{ $app->id }}, this)"
                                    title="적용 취소"
                                    style="flex-shrink:0;padding:2px 8px;font-size:10px;color:#ef4444;border:1px solid #fecaca;border-radius:5px;background:#fff;cursor:pointer;"
                                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">취소</button>
                        </div>
                        <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">
                            {{ $app->appliedBy?->name }} · {{ $app->applied_at?->format('Y-m-d H:i') }}
                        </p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 관련 이슈 --}}
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h3 style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:0;">관련 이슈</h3>
                <a href="{{ route('projects.issues.index', ['project' => $project, 'search' => $requirement->title]) }}"
                   style="font-size:11px;color:var(--t600);text-decoration:none;"
                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">전체 보기</a>
            </div>
            @php $linkedIssues = $requirement->linkedIssues()->with('assignee')->latest()->take(5)->get(); @endphp
            @if($linkedIssues->isEmpty())
                <p style="font-size:12px;color:#9ca3af;margin:0;">연결된 이슈가 없습니다.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($linkedIssues as $li)
                    @php $lsc = \App\Models\Issue::STATUS_COLORS[$li->status] ?? ['bg'=>'#f3f4f6','text'=>'#6b7280']; @endphp
                    <div style="background:#f9fafb;border-radius:7px;padding:8px 10px;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;background:{{ $lsc['bg'] }};color:{{ $lsc['text'] }};">{{ $li->status }}</span>
                            <a href="{{ route('projects.issues.show', [$project, $li]) }}"
                               style="font-size:12px;font-weight:500;color:#111827;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                               onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#111827'">{{ Str::limit($li->title, 40) }}</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 구독자 목록 --}}
        @if($requirement->watchers->count())
        <div style="background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:18px;">
            <h3 style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">구독자 ({{ $requirement->watchers->count() }})</h3>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($requirement->watchers as $w)
                    <span style="padding:2px 8px;background:#f3f4f6;border-radius:5px;font-size:11px;color:#6b7280;">{{ $w->user?->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
<script>
const CSRF        = document.querySelector('meta[name="csrf-token"]').content;
const UPDATE_URL  = '{{ route('projects.requirements.update', [$project, $requirement]) }}';
const COMMENT_URL = '{{ route('projects.requirements.comments.store', [$project, $requirement]) }}';
const WATCH_URL   = '{{ route('projects.requirements.watch', [$project, $requirement]) }}';
const APPROVE_URL = '{{ route('projects.requirements.approve', [$project, $requirement]) }}';
const DELETE_URL  = '{{ route('projects.requirements.destroy', [$project, $requirement]) }}';
const INDEX_URL   = '{{ route('projects.requirements.index', $project) }}';

async function patchField(field, value) {
    const body = new FormData();
    body.append('_method', 'PATCH');
    body.append(field, value);
    await fetch(UPDATE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body,
    });
}

async function toggleDescEdit() {
    const view = document.getElementById('desc-view');
    const edit = document.getElementById('desc-edit');
    const showing = edit.style.display !== 'none';
    view.style.display = showing ? 'block' : 'none';
    edit.style.display = showing ? 'none' : 'block';
    if (!showing) document.getElementById('desc-input').focus();
}

async function saveDesc() {
    const val = document.getElementById('desc-input').value;
    const body = new FormData();
    body.append('_method', 'PATCH');
    body.append('description', val);
    const res = await fetch(UPDATE_URL, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body,
    });
    if (res.ok) {
        document.getElementById('desc-view').textContent = val || '설명이 없습니다.';
        toggleDescEdit();
    }
}

async function postComment() {
    const input = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;

    const body = new FormData();
    body.append('content', content);
    const res = await fetch(COMMENT_URL, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body,
    });
    if (!res.ok) return;
    const d = await res.json();
    const c = d.comment;

    const div = document.createElement('div');
    div.style.cssText = 'padding:12px 14px;background:#f9fafb;border-radius:8px;';
    div.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <span style="font-size:12px;font-weight:600;color:#374151;">${c.author_name}</span>
            <span style="font-size:11px;color:#9ca3af;">${c.created_at}</span>
        </div>
        <p style="font-size:13px;color:#374151;margin:0;white-space:pre-wrap;">${c.content}</p>`;

    document.getElementById('comment-list').appendChild(div);
    input.value = '';
}

async function toggleWatch() {
    const res = await fetch(WATCH_URL, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const d = await res.json();
    const btn = document.getElementById('watch-btn');
    if (d.watching) {
        btn.textContent = '🔔 알림 구독 중';
        btn.style.borderColor = '#c7d2fe';
        btn.style.background  = '#eef2ff';
        btn.style.color       = 'var(--t600)';
    } else {
        btn.textContent = '🔕 알림 받기';
        btn.style.borderColor = '#e4e4e7';
        btn.style.background  = '#fff';
        btn.style.color       = '#6b7280';
    }
}

async function updateApproval(value) {
    const res = await fetch(APPROVE_URL, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ approval_status: value }),
    });
    if (res.ok) {
        const d = await res.json();
        const badge = document.getElementById('approval-badge');
        if (badge) badge.textContent = d.label;
    }
}

async function confirmDelete() {
    if (!await __confirm('이 요구사항을 삭제하시겠습니까?')) return;
    const body = new FormData();
    body.append('_method', 'DELETE');
    const res = await fetch(DELETE_URL, {
        method:  'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body,
    });
    if (res.ok) location.href = INDEX_URL;
}

async function revertApp(appId, btn) {
    if (!await __confirm('이 기획서 적용을 취소하시겠습니까?')) return;
    const url = '{{ url("projects/{$project->id}/plan-applications") }}/' + appId;
    const res = await fetch(url, {
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
                   'Content-Type': 'application/json' },
        body: JSON.stringify({ _method: 'DELETE' }),
    });
    if (res.ok) {
        btn.closest('.revert-item, div[style]').remove();
    }
}

// ── 간트 일정 등록 모달 ────────────────────────────────────────
const SCH_STORE   = '{{ route('projects.sub-tasks.store', $project) }}';
const SCH_TREE    = '{{ route('projects.schedule-tree', $project) }}';
const SCH_MEMBERS = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]));

async function openSchModal() {
    document.getElementById('sch-title').value = '{{ addslashes($requirement->title) }}';
    document.getElementById('sch-desc').value  = '{{ addslashes($requirement->description ?? '') }}';
    document.getElementById('sch-start').value = '';
    document.getElementById('sch-end').value   = '';
    document.getElementById('sch-error').style.display = 'none';

    const assigneeSel = document.getElementById('sch-assignee');
    assigneeSel.innerHTML = '<option value="">없음</option>' +
        SCH_MEMBERS.map(m =>
            `<option value="${m.id}" ${m.id == {{ $requirement->assignee_id ?? 'null' }} ? 'selected' : ''}>${m.name}</option>`
        ).join('');

    const groupSel = document.getElementById('sch-group');
    groupSel.innerHTML = '<option value="">불러오는 중...</option>';
    try {
        const res  = await fetch(SCH_TREE, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const tree = await res.json();
        const opts = [];
        (tree.milestones ?? []).forEach(ms => {
            (ms.task_groups ?? ms.taskGroups ?? []).forEach(g => {
                opts.push(`<option value="${g.id}">[${ms.title}] ${g.title}</option>`);
            });
        });
        (tree.ungrouped ?? []).forEach(g => {
            opts.push(`<option value="${g.id}">${g.title}</option>`);
        });
        groupSel.innerHTML = opts.length
            ? '<option value="">그룹 선택...</option>' + opts.join('')
            : '<option value="">등록된 작업 그룹이 없습니다</option>';
    } catch {
        groupSel.innerHTML = '<option value="">그룹 로드 실패</option>';
    }

    document.getElementById('sch-overlay').style.display = 'block';
    document.getElementById('sch-modal').style.display   = 'block';
}

async function closeSchModal() {
    document.getElementById('sch-overlay').style.display = 'none';
    document.getElementById('sch-modal').style.display   = 'none';
}

async function submitSchModal() {
    const groupId = document.getElementById('sch-group').value;
    const title   = document.getElementById('sch-title').value.trim();
    const start   = document.getElementById('sch-start').value;
    const end     = document.getElementById('sch-end').value;
    const errEl   = document.getElementById('sch-error');
    errEl.style.display = 'none';

    if (!groupId) { errEl.textContent = '작업 그룹을 선택해주세요.'; errEl.style.display = 'block'; return; }
    if (!title)   { errEl.textContent = '제목을 입력해주세요.';       errEl.style.display = 'block'; return; }
    if (!start)   { errEl.textContent = '시작일을 선택해주세요.';     errEl.style.display = 'block'; return; }
    if (!end)     { errEl.textContent = '종료일을 선택해주세요.';     errEl.style.display = 'block'; return; }

    const btn = document.getElementById('sch-submit-btn');
    btn.disabled = true; btn.textContent = '등록 중...';

    try {
        const body = new FormData();
        body.append('task_group_id', groupId);
        body.append('title',         title);
        body.append('start_date',    start);
        body.append('end_date',      end);
        body.append('assignee_id',   document.getElementById('sch-assignee').value || '');
        body.append('description',   document.getElementById('sch-desc').value     || '');
        body.append('status',        'not_started');

        const res  = await fetch(SCH_STORE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || '등록에 실패했습니다.');

        closeSchModal();
        alert('간트 일정에 등록되었습니다.');
    } catch (e) {
        errEl.textContent = e.message;
        errEl.style.display = 'block';
    }
    btn.disabled = false; btn.textContent = '일정 등록';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSchModal(); });
</script>

{{-- 간트 일정 등록 모달 --}}
<div id="sch-overlay" onclick="closeSchModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10300;"></div>
<div id="sch-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10301;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:480px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">간트 일정에 추가</h3>
        <button onclick="closeSchModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:2px;line-height:1;">&times;</button>
    </div>
    <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
        <div id="sch-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;"></div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">작업 그룹 <span style="color:#ef4444;">*</span></label>
            <select id="sch-group" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">불러오는 중...</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">제목 <span style="color:#ef4444;">*</span></label>
            <input id="sch-title" type="text" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">시작일 <span style="color:#ef4444;">*</span></label>
                <input id="sch-start" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">종료일 <span style="color:#ef4444;">*</span></label>
                <input id="sch-end" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">담당자</label>
            <select id="sch-assignee" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">없음</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">설명</label>
            <textarea id="sch-desc" rows="3" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;resize:vertical;font-family:inherit;box-sizing:border-box;"
                      onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>
    </div>
    <div style="padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeSchModal()" style="padding:8px 18px;font-size:13px;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
        <button onclick="submitSchModal()" id="sch-submit-btn" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">일정 등록</button>
    </div>
</div>
@endsection
