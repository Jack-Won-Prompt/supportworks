@extends('layouts.ai-agent')
@section('title', '승인 게이트 데모 — 웍스 Agent')

@push('styles')
<style>
.demo-section { background: #fff; border: 1.5px solid #ede8ff; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
.demo-section-title { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 16px; display: flex; align-items: center; gap: 6px; }
.demo-state-label { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 5px; background: #f1f5f9; color: #475569; margin-bottom: 12px; }
</style>
@endpush

@section('ai-agent-content')

<div style="max-width:640px;">
    <h1 style="font-size:22px;font-weight:800;color:#1e1b2e;margin:0 0 6px;">승인 게이트 컴포넌트 데모</h1>
    <p style="font-size:13.5px;color:#64748b;margin:0 0 28px;line-height:1.7;">
        <code>&lt;x-ai-agent.approval-gate&gt;</code> 컴포넌트의 각 상태를 미리보기합니다.<br>
        실제 사용 시에는 <code>:gate="$gate"</code>에 DB에서 조회한 AiAgentApprovalGate 모델을 전달하세요.
    </p>

    {{-- 상태 A: 요청 전 --}}
    <div class="demo-section">
        <div class="demo-section-title">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            상태 A — 요청 전 (canRequest = true)
        </div>
        <span class="demo-state-label">gate = null</span>
        <x-ai-agent.approval-gate
            :gate="null"
            type="stage"
            :target-id="1"
            :project="$project"
            label="기획 단계"
        />
    </div>

    {{-- 상태 B: 승인 대기 (본인이 요청자) --}}
    <div class="demo-section">
        <div class="demo-section-title">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            상태 B — 승인 대기 중
        </div>
        <span class="demo-state-label">gate.status = 'pending'</span>
        @php
            use App\Enums\Agent\ApprovalStatus;
            $mockGatePending = new \App\Models\Agent\AiAgentApprovalGate([
                'project_id'      => $project->id,
                'stage_id'        => 1,
                'gate_type'       => 'stage_completion',
                'status'          => ApprovalStatus::PENDING,
                'requested_by'    => auth()->id(),
                'requested_at'    => now()->subMinutes(30),
                'request_comment' => '기획서 v1.0 작성 완료. 화면 흐름도 및 요구사항 정의서 포함. 검토 부탁드립니다.',
            ]);
            $mockGatePending->id = 999;
            $mockGatePending->setRelation('requestedBy', auth()->user());
        @endphp
        <x-ai-agent.approval-gate
            :gate="$mockGatePending"
            type="stage"
            :target-id="1"
            :project="$project"
            label="기획 단계"
        />
    </div>

    {{-- 상태 C: 승인됨 --}}
    <div class="demo-section">
        <div class="demo-section-title">
            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            상태 C — 승인됨
        </div>
        <span class="demo-state-label">gate.status = 'approved'</span>
        @php
            $mockGateApproved = new \App\Models\Agent\AiAgentApprovalGate([
                'project_id'     => $project->id,
                'stage_id'       => 1,
                'gate_type'      => 'stage_completion',
                'status'         => ApprovalStatus::APPROVED,
                'requested_by'   => auth()->id(),
                'requested_at'   => now()->subHours(2),
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now()->subHour(),
                'review_comment' => '기획서 내용 검토 완료. 요구사항 정의 및 화면 흐름이 명확합니다. 디자인 단계로 진행 가능합니다.',
            ]);
            $mockGateApproved->id = 998;
            $mockGateApproved->setRelation('requestedBy', auth()->user());
            $mockGateApproved->setRelation('reviewedBy', auth()->user());
        @endphp
        <x-ai-agent.approval-gate
            :gate="$mockGateApproved"
            type="stage"
            :target-id="1"
            :project="$project"
            label="기획 단계"
        />
    </div>

    {{-- 상태 D: 반려됨 --}}
    <div class="demo-section">
        <div class="demo-section-title">
            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            상태 D — 반려됨
        </div>
        <span class="demo-state-label">gate.status = 'rejected'</span>
        @php
            $mockGateRejected = new \App\Models\Agent\AiAgentApprovalGate([
                'project_id'     => $project->id,
                'stage_id'       => 1,
                'gate_type'      => 'stage_completion',
                'status'         => ApprovalStatus::REJECTED,
                'requested_by'   => auth()->id(),
                'requested_at'   => now()->subHours(3),
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now()->subHours(2),
                'review_comment' => 'AS-IS 분석 자료가 부족합니다. 현황 시스템의 프로세스 흐름도와 문제점 분석 자료를 보완한 후 재요청 바랍니다.',
            ]);
            $mockGateRejected->id = 997;
            $mockGateRejected->setRelation('requestedBy', auth()->user());
            $mockGateRejected->setRelation('reviewedBy', auth()->user());
        @endphp
        <x-ai-agent.approval-gate
            :gate="$mockGateRejected"
            type="stage"
            :target-id="1"
            :project="$project"
            label="기획 단계"
        />
    </div>

    {{-- 사용 예시 --}}
    <div style="margin-top:32px;padding:20px 24px;background:#faf5ff;border:1.5px solid var(--t100);border-radius:14px;">
        <div style="font-size:13px;font-weight:700;color:var(--t700);margin-bottom:12px;">사용 예시</div>
        <pre style="font-size:11.5px;color:#374151;line-height:1.7;overflow-x:auto;margin:0;white-space:pre-wrap;">{{-- 단계 승인 게이트 --}}
@php
$gate = AiAgentApprovalGate::where('stage_id', $stage->id)
    ->where('gate_type', 'stage_completion')
    ->latest()->first();
@endphp

&lt;x-ai-agent.approval-gate
    :gate="$gate"
    type="stage"
    :target-id="$stage->id"
    :project="$project"
    label="기획 단계"
/&gt;</pre>
    </div>
</div>

@endsection
