@extends('layouts.app')

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ── AI Agent layout ─────────────────────────────────────── */
.aia-layout { display: flex; flex-direction: column; }

/* ── Project header bar ─────────────────────────────────── */
.aia-proj-header { background: #fff; border-bottom: 1.5px solid #ede8ff; padding: 10px 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.aia-proj-header-name { font-size: 14px; font-weight: 800; color: #1e1b2e; }
.aia-proj-header-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
.aia-stack-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 5px; background: var(--t100); color: var(--t700); }
.aia-proj-status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.aia-proj-status-dot.active { background: #16a34a; }
.aia-proj-status-dot.on_hold { background: #ca8a04; }
.aia-proj-status-dot.completed { background: #94a3b8; }

/* ── Body (sidebar + main) ──────────────────────────────── */
.aia-layout-body { display: flex; min-height: calc(100vh - 110px); }

/* ── Stage sidebar ──────────────────────────────────────── */
.aia-sidebar { width: 216px; flex-shrink: 0; background: #faf5ff; border-right: 1.5px solid #ede8ff; overflow-y: auto; }
.aia-sidebar-stage { border-bottom: 1px solid #f0ebff; }
.aia-sidebar-stage-btn { width: 100%; display: flex; align-items: center; gap: 7px; padding: 10px 14px; border: none; background: none; cursor: pointer; text-align: left; transition: background .12s; }
.aia-sidebar-stage-btn:hover { background: #f3eeff; }
.aia-sidebar-stage.is-active > .aia-sidebar-stage-btn { background: #ede8ff; }
.aia-sidebar-stage.is-locked > .aia-sidebar-stage-btn { cursor: default; }
.aia-sidebar-stage-label { font-size: 12.5px; font-weight: 700; color: #1e1b2e; flex: 1; line-height: 1; }
.aia-sidebar-stage.is-locked .aia-sidebar-stage-label { color: #b0b8c9; }
.aia-sidebar-subnav { padding: 2px 0 6px; }
.aia-sidebar-item { display: block; padding: 5px 14px 5px 34px; font-size: 11.5px; color: #64748b; text-decoration: none; transition: all .1s; border-left: 2px solid transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.aia-sidebar-item:hover:not(.is-disabled) { color: var(--t600); background: #f3eeff; }
.aia-sidebar-item.is-current { color: var(--t700); font-weight: 700; border-left-color: var(--t500); background: #ede8ff; }
.aia-sidebar-item.is-disabled { pointer-events: none; color: #c8d0db; }

/* ── Main content ───────────────────────────────────────── */
.aia-main { flex: 1; min-width: 0; padding: 22px 26px; overflow-y: auto; }

/* ── Breadcrumb ─────────────────────────────────────────── */
.aia-breadcrumb { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #94a3b8; margin-bottom: 18px; flex-wrap: wrap; }
.aia-breadcrumb a { color: #94a3b8; text-decoration: none; transition: color .12s; }
.aia-breadcrumb a:hover { color: var(--t600); }

/* ── Page actions bar ───────────────────────────────────── */
.aia-page-actions { display: flex; gap: 8px; align-items: center; margin-bottom: 18px; flex-wrap: wrap; }
</style>
@endpush

@section('content')
<div class="aia-layout">

    @if($aiProject ?? null)
        @include('ai-agent.partials.project-header')
    @endif

    <div class="aia-layout-body">
        @if($aiProject ?? null)
            @include('ai-agent.partials.stage-sidebar')
        @endif

        <main class="aia-main">
            @include('ai-agent.partials.breadcrumb')

            @hasSection('page-actions')
                <div class="aia-page-actions">@yield('page-actions')</div>
            @endif

            @yield('ai-agent-content')
        </main>
    </div>

</div>
@endsection
