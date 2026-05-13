@extends('layouts.ai-agent')
@section('title', $project->name . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Mode B: Project Home ─────────────────────────────────── */

/* ── Stage pipeline ────────────────────────────────────────── */
.adsh-pipeline { display: flex; gap: 8px; margin-bottom: 28px; overflow-x: auto; padding-bottom: 4px; }
.adsh-stage-card { flex: 1; min-width: 140px; max-width: 200px; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 14px 14px 12px; text-decoration: none; transition: all .18s; display: flex; flex-direction: column; gap: 8px; position: relative; }
.adsh-stage-card:hover:not(.locked) { border-color: var(--t300, #c4b5fd); box-shadow: 0 4px 16px rgba(124,58,237,.08); transform: translateY(-1px); }
.adsh-stage-card.locked { opacity: .55; cursor: default; pointer-events: none; }
.adsh-stage-card.active { border-color: var(--t400, #a78bfa); background: var(--t50, #f5f3ff); }
.adsh-stage-card.approved { border-color: #86efac; background: #f0fdf4; }
.adsh-stage-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 14px 14px 0 0; }
.adsh-stage-card.active::before { background: linear-gradient(90deg, var(--t400), var(--t600)); }
.adsh-stage-card.approved::before { background: linear-gradient(90deg, #4ade80, #16a34a); }

.adsh-stage-icon { font-size: 20px; line-height: 1; }
.adsh-stage-name { font-size: 12.5px; font-weight: 700; color: #1e1b2e; }
.adsh-stage-status { font-size: 10.5px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.adsh-stage-status.locked     { color: #94a3b8; }
.adsh-stage-status.in_progress{ color: var(--t600, #7c3aed); }
.adsh-stage-status.pending    { color: #2563eb; }
.adsh-stage-status.approved   { color: #16a34a; }
.adsh-stage-pbar { height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.adsh-stage-pfill { height: 100%; border-radius: 10px; }
.adsh-stage-pfill.locked     { background: #e2e8f0; }
.adsh-stage-pfill.in_progress{ background: linear-gradient(90deg, var(--t400), var(--t500)); }
.adsh-stage-pfill.pending    { background: #60a5fa; }
.adsh-stage-pfill.approved   { background: #4ade80; }
.adsh-stage-pct { font-size: 10px; font-weight: 700; color: #94a3b8; text-align: right; }

/* ── Two-column body ──────────────────────────────────────── */
.adsh-body { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
@media (max-width: 900px) { .adsh-body { grid-template-columns: 1fr; } }

/* ── Timeline ─────────────────────────────────────────────── */
.adsh-timeline-card { background: #fff; border: 1.5px solid #f1f5f9; border-radius: 14px; overflow: hidden; }
.adsh-timeline-hdr { padding: 14px 18px; border-bottom: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.adsh-timeline-title { font-size: 13px; font-weight: 700; color: #1e1b2e; display: flex; align-items: center; gap: 6px; }
.adsh-timeline-body { max-height: 480px; overflow-y: auto; }
.adsh-tl-item { display: flex; gap: 12px; padding: 12px 18px; border-bottom: 1px solid #f8fafc; transition: background .1s; }
.adsh-tl-item:last-child { border-bottom: none; }
.adsh-tl-item:hover { background: #fafafa; }
.adsh-tl-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.adsh-tl-icon.document       { background: #ede9fe; }
.adsh-tl-icon.clock          { background: #dbeafe; }
.adsh-tl-icon.check          { background: #dcfce7; }
.adsh-tl-icon.x              { background: #fee2e2; }
.adsh-tl-icon.sparkle        { background: var(--t50, #f5f3ff); }
.adsh-tl-body { flex: 1; min-width: 0; }
.adsh-tl-title { font-size: 12.5px; font-weight: 600; color: #1e1b2e; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.adsh-tl-desc { font-size: 11.5px; color: #64748b; line-height: 1.4; }
.adsh-tl-meta { font-size: 11px; color: #94a3b8; margin-top: 3px; display: flex; gap: 8px; }
.adsh-tl-empty { padding: 32px 18px; text-align: center; color: #94a3b8; font-size: 13px; }

/* ── Widgets column ───────────────────────────────────────── */
.adsh-widget { background: #fff; border: 1.5px solid #f1f5f9; border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
.adsh-widget:last-child { margin-bottom: 0; }
.adsh-widget-hdr { padding: 12px 16px; border-bottom: 1.5px solid #f8fafc; display: flex; align-items: center; gap: 7px; }
.adsh-widget-title { font-size: 12.5px; font-weight: 700; color: #1e1b2e; }
.adsh-widget-body { padding: 14px 16px; }

.adsh-stat { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f8fafc; }
.adsh-stat:last-child { border-bottom: none; }
.adsh-stat-label { font-size: 12px; color: #64748b; }
.adsh-stat-val { font-size: 13px; font-weight: 700; color: #1e1b2e; }

/* Quick actions */
.adsh-action-btn { display: flex; align-items: center; gap: 8px; width: 100%; padding: 10px 12px; border: 1.5px solid #f1f5f9; border-radius: 10px; background: #fff; text-decoration: none; font-size: 12.5px; font-weight: 600; color: #374151; cursor: pointer; transition: all .13s; margin-bottom: 8px; }
.adsh-action-btn:last-child { margin-bottom: 0; }
.adsh-action-btn:hover { border-color: var(--t200, #ddd6fe); color: var(--t700, #6d28d9); background: var(--t50, #f5f3ff); }
.adsh-action-btn svg { flex-shrink: 0; }

/* Overall progress bar */
.adsh-overall { background: linear-gradient(135deg, var(--t600, #7c3aed), var(--t700, #6d28d9)); border-radius: 14px; padding: 16px 20px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; }
.adsh-overall-info { flex: 1; }
.adsh-overall-label { font-size: 12px; font-weight: 600; opacity: .8; margin-bottom: 4px; }
.adsh-overall-pct { font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 8px; }
.adsh-overall-bar { height: 6px; background: rgba(255,255,255,.25); border-radius: 10px; overflow: hidden; }
.adsh-overall-fill { height: 100%; background: rgba(255,255,255,.85); border-radius: 10px; transition: width .5s; }
.adsh-overall-stack { display: flex; flex-direction: column; align-items: flex-end; gap: 3px; flex-shrink: 0; }
.adsh-overall-stack-badge { font-size: 11px; font-weight: 700; background: rgba(255,255,255,.2); padding: 3px 10px; border-radius: 20px; letter-spacing: .03em; }
</style>
@endpush

@section('ai-agent-content')

{{-- Overall progress bar --}}
<div class="adsh-overall">
    <div class="adsh-overall-info">
        <div class="adsh-overall-label">전체 진행률</div>
        <div class="adsh-overall-pct">{{ $overall }}%</div>
        <div class="adsh-overall-bar">
            <div class="adsh-overall-fill" style="width:{{ $overall }}%"></div>
        </div>
    </div>
    @if($config->frontend_stack)
    <div class="adsh-overall-stack">
        <div class="adsh-overall-stack-badge">{{ $config->frontend_stack->label() }}</div>
        @if($activeStage)
        <div style="font-size:11px;opacity:.75;margin-top:2px;">
            현재: {{ $activeStage['stage']->name }}
        </div>
        @endif
    </div>
    @endif
</div>

{{-- 5-Stage pipeline --}}
<div class="adsh-pipeline">
    @foreach($stages as $stageInfo)
    @php
        $stage  = $stageInfo['stage'];
        $prog   = $stageInfo['progress'];
        $prefix = $stageInfo['route_prefix'];
        $icon   = $stageInfo['icon'];
        $status = $stage->status->value;
        $isLocked   = $status === 'locked';
        $isApproved = $status === 'approved';
        $isActive   = in_array($status, ['in_progress', 'pending_approval']);

        $cardClass = $isLocked ? 'locked' : ($isApproved ? 'approved' : ($isActive ? 'active' : ''));
        $pfillClass = match($status) {
            'locked'           => 'locked',
            'in_progress'      => 'in_progress',
            'pending_approval' => 'pending',
            'approved'         => 'approved',
            default            => 'locked',
        };
        $statusClass = match($status) {
            'locked'           => 'locked',
            'in_progress'      => 'in_progress',
            'pending_approval' => 'pending',
            'approved'         => 'approved',
            default            => 'locked',
        };
        $routeName = $prefix === 'release'
            ? 'ai-agent.projects.release'
            : "ai-agent.projects.{$prefix}.index";
    @endphp
    <a href="{{ $isLocked ? '#' : route($routeName, $project) }}"
       class="adsh-stage-card {{ $cardClass }}"
       @if($isLocked) tabindex="-1" aria-disabled="true" @endif>
        <div class="adsh-stage-icon">{{ $icon }}</div>
        <div class="adsh-stage-name">{{ $stage->name }}</div>
        <div class="adsh-stage-status {{ $statusClass }}">
            {!! \App\Services\Agent\ApprovalGateHelper::getStageStatusIcon($stage->status) !!}
            {{ $stage->status->label() }}
        </div>
        <div>
            <div class="adsh-stage-pbar">
                <div class="adsh-stage-pfill {{ $pfillClass }}" style="width:{{ $prog }}%"></div>
            </div>
            <div class="adsh-stage-pct">{{ $prog }}%</div>
        </div>
    </a>
    @if(!$loop->last)
    <div style="display:flex;align-items:center;flex-shrink:0;">
        <svg width="14" height="14" fill="none" stroke="#cbd5e1" viewBox="0 0 24 24" style="margin-top:10px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
    @endif
    @endforeach
</div>

{{-- Two-column body --}}
<div class="adsh-body">

    {{-- Activity Timeline --}}
    <div class="adsh-timeline-card">
        <div class="adsh-timeline-hdr">
            <div class="adsh-timeline-title">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                활동 타임라인
            </div>
            <span style="font-size:11px;color:#94a3b8;">최근 {{ count($timeline) }}건</span>
        </div>
        <div class="adsh-timeline-body">
            @forelse($timeline as $item)
            <div class="adsh-tl-item">
                <div class="adsh-tl-icon {{ $item['icon'] }}">
                    @if($item['icon'] === 'document')
                    <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @elseif($item['icon'] === 'clock')
                    <svg width="13" height="13" fill="none" stroke="#2563eb" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($item['icon'] === 'check')
                    <svg width="13" height="13" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($item['icon'] === 'x')
                    <svg width="13" height="13" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                    <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                    @endif
                </div>
                <div class="adsh-tl-body">
                    <div class="adsh-tl-title">{{ $item['title'] }}</div>
                    <div class="adsh-tl-desc">{{ $item['desc'] }}</div>
                    <div class="adsh-tl-meta">
                        <span>{{ $item['user'] }}</span>
                        <span>{{ $item['created_at']?->diffForHumans() ?? '-' }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="adsh-tl-empty">
                <svg width="28" height="28" fill="none" stroke="#e2e8f0" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                아직 활동 내역이 없습니다.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Widgets column --}}
    <div>

        {{-- 웍스 사용량 위젯 --}}
        <div class="adsh-widget">
            <div class="adsh-widget-hdr">
                <svg width="13" height="13" fill="none" stroke="var(--t500,#8b5cf6)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                <span class="adsh-widget-title">웍스 사용량</span>
            </div>
            <div class="adsh-widget-body">
                <div class="adsh-stat">
                    <span class="adsh-stat-label">누적 비용</span>
                    <span class="adsh-stat-val" style="color:var(--t600,#7c3aed);">${{ number_format($usage['cost_usd'], 3) }}</span>
                </div>
                <div class="adsh-stat">
                    <span class="adsh-stat-label">총 토큰</span>
                    <span class="adsh-stat-val">{{ number_format($usage['input_tokens'] + $usage['output_tokens']) }}</span>
                </div>
                <div class="adsh-stat">
                    <span class="adsh-stat-label">Input / Output</span>
                    <span class="adsh-stat-val" style="font-size:11.5px;">{{ number_format($usage['input_tokens']) }} / {{ number_format($usage['output_tokens']) }}</span>
                </div>
                <div class="adsh-stat">
                    <span class="adsh-stat-label">총 웍스 호출</span>
                    <span class="adsh-stat-val">{{ number_format($usage['call_count']) }}회</span>
                </div>
                @if($usage['call_count'] > 0)
                <a href="{{ route('ai-agent.projects.common.usage', $project) }}"
                   style="display:flex;align-items:center;gap:4px;font-size:11.5px;color:var(--t600,#7c3aed);margin-top:10px;text-decoration:none;">
                    상세 보기
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
            </div>
        </div>

        {{-- 승인 대기 위젯 --}}
        <div class="adsh-widget">
            <div class="adsh-widget-hdr">
                <svg width="13" height="13" fill="none" stroke="#2563eb" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="adsh-widget-title">승인 대기</span>
            </div>
            <div class="adsh-widget-body">
                <div class="adsh-stat">
                    <span class="adsh-stat-label">전체 대기 건</span>
                    <span class="adsh-stat-val"
                          style="{{ $approval['total_pending'] > 0 ? 'color:#2563eb;' : '' }}">
                        {{ $approval['total_pending'] }}건
                    </span>
                </div>
                <div class="adsh-stat">
                    <span class="adsh-stat-label">내가 요청한 건</span>
                    <span class="adsh-stat-val">{{ $approval['requested_by_me'] }}건</span>
                </div>
                @if($approval['total_pending'] > 0)
                <div style="margin-top:10px;padding:8px 10px;background:#dbeafe;border-radius:8px;font-size:12px;color:#1d4ed8;font-weight:600;text-align:center;">
                    승인이 필요한 항목이 있습니다
                </div>
                @endif
            </div>
        </div>

        {{-- 빠른 액션 --}}
        <div class="adsh-widget">
            <div class="adsh-widget-hdr">
                <svg width="13" height="13" fill="none" stroke="#374151" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="adsh-widget-title">빠른 액션</span>
            </div>
            <div class="adsh-widget-body">
                @if($activeStage)
                <a href="{{ route('ai-agent.projects.' . $activeStage['route_prefix'] . ($activeStage['route_prefix'] === 'release' ? '' : '.index'), $project) }}"
                   class="adsh-action-btn">
                    <span style="font-size:16px;">{{ $activeStage['icon'] }}</span>
                    <span style="flex:1;">{{ $activeStage['stage']->name }} 단계 계속</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endif
                <a href="{{ route('ai-agent.projects.common.traceability', $project) }}" class="adsh-action-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span style="flex:1;">추적성 매트릭스</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('ai-agent.projects.common.versions', $project) }}" class="adsh-action-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span style="flex:1;">버전 이력</span>
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
