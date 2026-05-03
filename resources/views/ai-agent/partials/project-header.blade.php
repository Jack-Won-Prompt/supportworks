<div class="aia-proj-header">
    {{-- Project name + home link --}}
    <a href="{{ route('ai-agent.projects.home', $aiProject) }}"
       style="display:flex;align-items:center;gap:8px;text-decoration:none;">
        <svg width="16" height="16" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
        </svg>
        <span class="aia-proj-header-name">{{ $aiProject->name }}</span>
    </a>

    <div class="aia-proj-header-meta">
        {{-- Status --}}
        <span class="aia-proj-status-dot {{ $aiProject->status }}"></span>
        <span style="font-size:11.5px;color:#64748b;">
            @if($aiProject->status === 'active') 진행중
            @elseif($aiProject->status === 'on_hold') 보류
            @elseif($aiProject->status === 'completed') 완료
            @else {{ $aiProject->status }}
            @endif
        </span>

        {{-- Frontend stack badge --}}
        @if($aiConfig?->frontend_stack)
            <span class="aia-stack-badge">
                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                {{ $aiConfig->frontend_stack->label() }}
            </span>
        @endif

        {{-- End date --}}
        @if($aiProject->end_date)
            <span style="font-size:11px;color:#94a3b8;">
                {{ \Carbon\Carbon::parse($aiProject->end_date)->format('Y.m.d') }}
            </span>
        @endif
    </div>
</div>
