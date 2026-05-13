<nav class="aia-breadcrumb" aria-label="breadcrumb">
    <a href="{{ route('ai-agent.dashboard') }}">웍스 Agent</a>

    @if($aiProject ?? null)
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('ai-agent.projects.home', $aiProject) }}">{{ $aiProject->name }}</a>
    @endif

    @if(!empty($stageLabel))
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span>{{ $stageLabel }}</span>
    @endif

    @if(!empty($pageTitle))
        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span style="color:#1e1b2e;font-weight:600;">{{ $pageTitle }}</span>
    @endif
</nav>
