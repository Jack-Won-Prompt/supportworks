{{-- agent-sessions 공통 페이지 헤더 partial --}}
{{-- 사용: @include('ai-agent.agent-sessions.partials.page-header', ['title' => '...', 'subtitle' => '...', 'badge' => '...']) --}}

@push('styles')
<style>
.ags-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: var(--t100); color: var(--t700); margin-bottom: 10px; }
.ags-title { font-size: 22px; font-weight: 800; color: #1e1b2e; margin: 0 0 6px; }
.ags-subtitle { font-size: 13.5px; color: #64748b; line-height: 1.6; margin: 0 0 22px; max-width: 720px; }

.ags-card { background: #fff; border: 1.5px solid #ede8ff; border-radius: 12px; padding: 18px 20px; }
.ags-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }

.ags-tag { display: inline-block; font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 5px; }
.ags-tag-draft       { background: #e2e8f0; color: #475569; }
.ags-tag-running     { background: #dbeafe; color: #1d4ed8; }
.ags-tag-attention   { background: #fef3c7; color: #92400e; }
.ags-tag-confirmed   { background: #d1fae5; color: #047857; }
.ags-tag-failed      { background: #fee2e2; color: #b91c1c; }

.ags-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 12.5px; font-weight: 600; border-radius: 8px; text-decoration: none; cursor: pointer; border: 1.5px solid transparent; transition: all .15s; }
.ags-btn-primary { background: var(--t600); color: #fff; }
.ags-btn-primary:hover { background: var(--t700); }
.ags-btn-ghost { background: #fff; color: var(--t700); border-color: var(--t300); }
.ags-btn-ghost:hover { background: var(--t50); }
.ags-btn-danger-ghost { background: #fff; color: #b91c1c; border-color: #fecaca; }
.ags-btn-danger-ghost:hover { background: #fee2e2; }

.ags-empty { padding: 36px 20px; text-align: center; color: #94a3b8; font-size: 13px; border: 2px dashed #ede8ff; border-radius: 12px; }
.ags-section-title { font-size: 12.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 10px; }
</style>
@endpush

@if(!empty($badge ?? null))
    <div class="ags-badge">
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
        {{ $badge }}
    </div>
@endif

<h1 class="ags-title">{{ $title }}</h1>
@if(!empty($subtitle ?? null))
    <p class="ags-subtitle">{{ $subtitle }}</p>
@endif
