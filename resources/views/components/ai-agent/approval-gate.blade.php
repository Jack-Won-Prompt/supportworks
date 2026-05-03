{{--
  AI Agent 승인 게이트 컴포넌트
  Usage:
    <x-ai-agent.approval-gate
        :gate="$gate"
        type="stage"
        :target-id="$stage->id"
        :project="$project"
    />
  Props:
    gate      — AiAgentApprovalGate|null
    type      — 'stage' | 'artifact'
    target-id — int (stage_id or artifact_id)
    project   — Project model
    label     — string (optional, display name for the thing being approved)
--}}
@props([
    'gate'     => null,
    'type'     => 'stage',
    'targetId' => 0,
    'project'  => null,
    'label'    => null,
])

@php
use App\Enums\Agent\ApprovalStatus;
use App\Models\ProjectMember;

$userId     = auth()->id();
$isAdmin    = auth()->user()->isAdmin();
$isMember   = $project && ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists();
$isManager  = $isAdmin || ($project && ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->where('role', 'manager')->exists());
$canRequest = $project && ($isAdmin || $isMember);
$canReview  = $isManager;
$isRequester = $gate && $gate->requested_by === $userId;

$typeLabel = $type === 'stage' ? '단계' : '산출물';
$displayLabel = $label ?? $typeLabel;

$gateJs = $gate ? [
    'id'              => $gate->id,
    'status'          => $gate->status->value,
    'gate_type'       => $gate->gate_type,
    'requested_by_id' => $gate->requested_by,
    'requested_by'    => optional($gate->requestedBy)->name ?? '알 수 없음',
    'requested_at'    => $gate->requested_at?->format('Y.m.d H:i'),
    'reviewed_by'     => optional($gate->reviewedBy)->name,
    'reviewed_at'     => $gate->reviewed_at?->format('Y.m.d H:i'),
    'request_comment' => $gate->request_comment,
    'review_comment'  => $gate->review_comment,
] : null;

$requestUrl = $project ? route('ai-agent.projects.approvals.request', $project) : '#';
$approveUrl = ($gate && $project) ? route('ai-agent.projects.approvals.approve', [$project, $gate]) : '';
$rejectUrl  = ($gate && $project) ? route('ai-agent.projects.approvals.reject',  [$project, $gate]) : '';
$cancelUrl  = ($gate && $project) ? route('ai-agent.projects.approvals.cancel',  [$project, $gate]) : '';
@endphp

@push('styles')
<style>
.apg { background: #fff; border: 1.5px solid #ede8ff; border-radius: 14px; padding: 18px 20px; }
.apg-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.apg-title { font-size: 13px; font-weight: 700; color: #1e1b2e; flex: 1; }
.apg-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.apg-badge.pending  { background: #fef3c7; color: #92400e; }
.apg-badge.approved { background: #dcfce7; color: #15803d; }
.apg-badge.rejected { background: #fee2e2; color: #b91c1c; }
.apg-badge.none     { background: #f1f5f9; color: #64748b; }
.apg-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: #64748b; margin-bottom: 12px; }
.apg-meta-item { display: flex; align-items: center; gap: 4px; }
.apg-comment { background: #faf5ff; border-left: 3px solid var(--t400); border-radius: 0 8px 8px 0; padding: 8px 12px; font-size: 12.5px; color: #374151; line-height: 1.6; margin-bottom: 12px; }
.apg-comment.reject { background: #fff7f7; border-left-color: #f87171; }
.apg-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 4px; }
.apg-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; text-decoration: none; }
.apg-btn.primary   { background: var(--t600); color: #fff; }
.apg-btn.primary:hover { background: var(--t700); }
.apg-btn.success   { background: #16a34a; color: #fff; }
.apg-btn.success:hover { background: #15803d; }
.apg-btn.danger    { background: #dc2626; color: #fff; }
.apg-btn.danger:hover { background: #b91c1c; }
.apg-btn.ghost     { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.apg-btn.ghost:hover { background: #e2e8f0; }
.apg-btn:disabled  { opacity: .5; cursor: not-allowed; }
.apg-alert { display: flex; align-items: flex-start; gap: 8px; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 12px; }
.apg-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.apg-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.apg-divider { border: none; border-top: 1px solid #f3eeff; margin: 14px 0; }
/* Modal overlay */
.apg-overlay { position: fixed; inset: 0; background: rgba(30,27,46,.45); z-index: 9990; display: flex; align-items: center; justify-content: center; padding: 20px; }
.apg-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(30,27,46,.2); padding: 24px; }
.apg-modal-title { font-size: 15px; font-weight: 800; color: #1e1b2e; margin: 0 0 16px; }
.apg-textarea { width: 100%; border: 1.5px solid #ddd6fe; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #1e1b2e; resize: vertical; min-height: 80px; font-family: inherit; outline: none; box-sizing: border-box; }
.apg-textarea:focus { border-color: var(--t500); }
.apg-label { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; display: block; }
.apg-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
.apg-next-hint { margin-top: 12px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 12px; color: #15803d; display: flex; align-items: center; gap: 6px; }
</style>
@endpush

<div class="apg"
     x-data="approvalGate(@json([
         'gate'       => $gateJs,
         'type'       => $type,
         'targetId'   => $targetId,
         'userId'     => $userId,
         'canRequest' => $canRequest,
         'canReview'  => $canReview,
         'isRequester'=> $isRequester,
         'isAdmin'    => $isAdmin,
         'displayLabel' => $displayLabel,
         'requestUrl' => $requestUrl,
         'approveUrl' => $approveUrl,
         'rejectUrl'  => $rejectUrl,
         'cancelUrl'  => $cancelUrl,
         'csrfToken'  => csrf_token(),
     ]))">

    {{-- 헤더 --}}
    <div class="apg-header">
        <svg width="16" height="16" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
        </svg>
        <span class="apg-title">HITL 승인 게이트</span>

        {{-- Status badge --}}
        <template x-if="!gate">
            <span class="apg-badge none">요청 전</span>
        </template>
        <template x-if="gate && gate.status === 'pending'">
            <span class="apg-badge pending">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                승인 대기
            </span>
        </template>
        <template x-if="gate && gate.status === 'approved'">
            <span class="apg-badge approved">
                <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                승인됨
            </span>
        </template>
        <template x-if="gate && gate.status === 'rejected'">
            <span class="apg-badge rejected">
                <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                반려됨
            </span>
        </template>
    </div>

    {{-- Alert --}}
    <template x-if="alert">
        <div class="apg-alert" :class="alert.type">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            <span x-text="alert.message"></span>
        </div>
    </template>

    {{-- ─── 상태별 콘텐츠 ─────────────────────────────── --}}

    {{-- 요청 전 --}}
    <template x-if="!gate">
        <div>
            <p style="font-size:13px;color:#64748b;margin:0 0 14px;line-height:1.6;">
                <span x-text="displayLabel"></span> 검토가 완료되면 승인을 요청하세요.
                승인 권한자(매니저)의 확인 후 다음 단계로 진행할 수 있습니다.
            </p>
            <div class="apg-actions">
                <template x-if="canRequest">
                    <button class="apg-btn primary" @click="showRequestModal = true" :disabled="loading">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        승인 요청
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- 승인 대기 (PENDING) --}}
    <template x-if="gate && gate.status === 'pending'">
        <div>
            <div class="apg-meta">
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>요청자: </span><strong x-text="gate.requested_by"></strong>
                </span>
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="gate.requested_at"></span>
                </span>
            </div>
            <template x-if="gate.request_comment">
                <div class="apg-comment" x-text="gate.request_comment"></div>
            </template>
            <div class="apg-actions">
                {{-- 승인자: 승인/반려 --}}
                <template x-if="canReview">
                    <button class="apg-btn success" @click="showApproveModal = true" :disabled="loading">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        승인
                    </button>
                </template>
                <template x-if="canReview">
                    <button class="apg-btn danger" @click="showRejectModal = true" :disabled="loading">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        반려
                    </button>
                </template>
                {{-- 요청자: 취소 --}}
                <template x-if="isRequester || isAdmin">
                    <button class="apg-btn ghost" @click="submitCancel" :disabled="loading">
                        요청 취소
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- 승인됨 (APPROVED) --}}
    <template x-if="gate && gate.status === 'approved'">
        <div>
            <div class="apg-meta">
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>승인자: </span><strong x-text="gate.reviewed_by"></strong>
                </span>
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="gate.reviewed_at"></span>
                </span>
            </div>
            <template x-if="gate.review_comment">
                <div class="apg-comment" x-text="gate.review_comment"></div>
            </template>
            <div class="apg-next-hint" x-show="gate.gate_type === 'stage_completion'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                다음 단계가 활성화되었습니다.
            </div>
        </div>
    </template>

    {{-- 반려됨 (REJECTED) --}}
    <template x-if="gate && gate.status === 'rejected'">
        <div>
            <div class="apg-meta">
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>반려자: </span><strong x-text="gate.reviewed_by"></strong>
                </span>
                <span class="apg-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="gate.reviewed_at"></span>
                </span>
            </div>
            <template x-if="gate.review_comment">
                <div class="apg-comment reject">
                    <strong style="display:block;margin-bottom:4px;font-size:11px;color:#b91c1c;">반려 사유</strong>
                    <span x-text="gate.review_comment"></span>
                </div>
            </template>
            <div class="apg-actions">
                <template x-if="canRequest && (isRequester || isAdmin)">
                    <button class="apg-btn primary" @click="showRequestModal = true" :disabled="loading">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        수정 후 재요청
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- ─── 승인 요청 모달 ────────────────────────────── --}}
    <template x-teleport="body">
        <div class="apg-overlay" x-show="showRequestModal" x-cloak @click.self="showRequestModal = false">
            <div class="apg-modal" @click.stop>
                <h3 class="apg-modal-title">
                    <template x-if="gate && gate.status === 'rejected'">수정 후 재요청</template>
                    <template x-if="!gate || gate.status !== 'rejected'">승인 요청</template>
                </h3>
                <p style="font-size:12.5px;color:#64748b;margin:0 0 16px;line-height:1.6;">
                    <span x-text="displayLabel"></span> 승인을 요청합니다. 승인 권한자(매니저)에게 알림이 전달됩니다.
                </p>
                <label class="apg-label">요청 메모 (선택)</label>
                <textarea class="apg-textarea" x-model="requestComment" placeholder="검토 포인트나 특이사항을 입력하세요..." rows="3"></textarea>
                <div class="apg-modal-actions">
                    <button class="apg-btn ghost" @click="showRequestModal = false" :disabled="loading">취소</button>
                    <button class="apg-btn primary" @click="submitRequest" :disabled="loading">
                        <span x-show="!loading">요청 제출</span>
                        <span x-show="loading">처리 중...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ─── 승인 확인 모달 ────────────────────────────── --}}
    <template x-teleport="body">
        <div class="apg-overlay" x-show="showApproveModal" x-cloak @click.self="showApproveModal = false">
            <div class="apg-modal" @click.stop>
                <h3 class="apg-modal-title">승인 확인</h3>
                <p style="font-size:12.5px;color:#64748b;margin:0 0 16px;line-height:1.6;">
                    <strong x-text="gate && gate.requested_by"></strong>님의 승인 요청을 승인합니다.<br>
                    승인 후에는 취소할 수 없습니다.
                </p>
                <label class="apg-label">승인 코멘트 (선택)</label>
                <textarea class="apg-textarea" x-model="approveComment" placeholder="승인 의견을 입력하세요..." rows="3"></textarea>
                <div class="apg-modal-actions">
                    <button class="apg-btn ghost" @click="showApproveModal = false" :disabled="loading">취소</button>
                    <button class="apg-btn success" @click="submitApprove" :disabled="loading">
                        <span x-show="!loading">승인 확정</span>
                        <span x-show="loading">처리 중...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ─── 반려 모달 ─────────────────────────────────── --}}
    <template x-teleport="body">
        <div class="apg-overlay" x-show="showRejectModal" x-cloak @click.self="showRejectModal = false">
            <div class="apg-modal" @click.stop>
                <h3 class="apg-modal-title" style="color:#b91c1c;">반려</h3>
                <p style="font-size:12.5px;color:#64748b;margin:0 0 16px;line-height:1.6;">
                    반려 사유를 작성하면 요청자에게 표시됩니다. 수정 후 재요청 시 이 사유가 가이드로 활용됩니다.
                </p>
                <label class="apg-label">반려 사유 <span style="color:#dc2626;">*</span></label>
                <textarea class="apg-textarea" x-model="rejectComment" placeholder="반려 사유를 구체적으로 입력해주세요..." rows="4"
                          :style="rejectCommentError ? 'border-color:#dc2626;' : ''"></textarea>
                <p x-show="rejectCommentError" style="font-size:12px;color:#dc2626;margin:4px 0 0;">반려 사유는 필수입니다.</p>
                <div class="apg-modal-actions">
                    <button class="apg-btn ghost" @click="showRejectModal = false; rejectComment = ''; rejectCommentError = false;" :disabled="loading">취소</button>
                    <button class="apg-btn danger" @click="submitReject" :disabled="loading">
                        <span x-show="!loading">반려 확정</span>
                        <span x-show="loading">처리 중...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>

@once
@push('scripts')
<script>
function approvalGate(cfg) {
    return {
        gate: cfg.gate,
        type: cfg.type,
        targetId: cfg.targetId,
        userId: cfg.userId,
        canRequest: cfg.canRequest,
        canReview: cfg.canReview,
        isRequester: cfg.isRequester,
        isAdmin: cfg.isAdmin,
        displayLabel: cfg.displayLabel,
        requestUrl: cfg.requestUrl,
        approveUrl: cfg.approveUrl,
        rejectUrl: cfg.rejectUrl,
        cancelUrl: cfg.cancelUrl,
        csrfToken: cfg.csrfToken,

        showRequestModal: false,
        showApproveModal: false,
        showRejectModal: false,
        requestComment: '',
        approveComment: '',
        rejectComment: '',
        rejectCommentError: false,
        loading: false,
        alert: null,

        headers() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            };
        },

        updateUrls(gateId) {
            const base = this.requestUrl.replace('/approvals/request', `/approvals/${gateId}`);
            this.approveUrl = base + '/approve';
            this.rejectUrl  = base + '/reject';
            this.cancelUrl  = base + '/cancel';
        },

        async submitRequest() {
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.requestUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ type: this.type, target_id: this.targetId, comment: this.requestComment }),
                });
                const data = await res.json();
                if (data.success) {
                    this.gate = data.gate;
                    this.isRequester = (data.gate.requested_by_id === this.userId);
                    this.updateUrls(data.gate.id);
                    this.showRequestModal = false;
                    this.requestComment   = '';
                    this.alert = { type: 'success', message: data.message };
                } else {
                    this.alert = { type: 'error', message: data.message };
                }
            } catch {
                this.alert = { type: 'error', message: '요청 처리 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },

        async submitApprove() {
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.approveUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ comment: this.approveComment }),
                });
                const data = await res.json();
                if (data.success) {
                    this.gate = data.gate;
                    this.showApproveModal = false;
                    this.approveComment   = '';
                    this.alert = { type: 'success', message: data.message + ' 페이지를 갱신합니다...' };
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.alert = { type: 'error', message: data.message };
                    this.loading = false;
                }
            } catch {
                this.alert = { type: 'error', message: '처리 중 오류가 발생했습니다.' };
                this.loading = false;
            }
        },

        async submitReject() {
            if (!this.rejectComment.trim()) {
                this.rejectCommentError = true;
                return;
            }
            this.rejectCommentError = false;
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.rejectUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({ comment: this.rejectComment }),
                });
                const data = await res.json();
                if (data.success) {
                    this.gate = data.gate;
                    this.showRejectModal  = false;
                    this.rejectComment    = '';
                    this.alert = { type: 'success', message: data.message };
                } else {
                    this.alert = { type: 'error', message: data.message };
                }
            } catch {
                this.alert = { type: 'error', message: '처리 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },

        async submitCancel() {
            if (!confirm('승인 요청을 취소하시겠습니까?')) return;
            this.loading = true;
            this.alert   = null;
            try {
                const res  = await fetch(this.cancelUrl, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (data.success) {
                    this.gate        = null;
                    this.approveUrl  = '';
                    this.rejectUrl   = '';
                    this.cancelUrl   = '';
                    this.alert = { type: 'success', message: data.message };
                } else {
                    this.alert = { type: 'error', message: data.message };
                }
            } catch {
                this.alert = { type: 'error', message: '처리 중 오류가 발생했습니다.' };
            }
            this.loading = false;
        },
    };
}
</script>
@endpush
@endonce
