@extends('layouts.app')

@section('title', __('plan-do-acts.title'))

@php
    $pdaNewProjectId = ($selectedProjectId && $selectedProjectId !== 'none') ? (int) $selectedProjectId : null;
@endphp

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
@if(!empty($selectedProject))
    <span>›</span>
    <a href="{{ route('projects.show', $selectedProject) }}" class="hover:text-indigo-500 transition-colors">{{ $selectedProject->name }}</a>
@endif
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('plan-do-acts.title') }}</span>
@endsection

@section('header-actions')@endsection

{{-- 프로젝트 진입 시: project-nav 의 page-actions 슬롯으로 "신규" 버튼 노출 --}}
@if(!empty($selectedProject))
@section('page-actions')
    <button onclick="pdaOpenCreate({{ $pdaNewProjectId ?? 'null' }}, {})"
            style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,var(--t600),#9b8afb);border:none;border-radius:8px;cursor:pointer;">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        {{ __('plan-do-acts.new') }}
    </button>
@endsection
@endif

@section('content')
@if(!empty($selectedProject))
    @include('partials.project-nav', ['project' => $selectedProject, 'active' => 'plan-do-acts'])
@endif

{{-- 전역 모드: 프로젝트 선택 + 신규 버튼을 별도 헤더 카드로 노출 --}}
@if(empty($selectedProject))
<div style="background:#fff;border-radius:10px;border:1px solid #f3f4f6;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <h2 style="margin:0;font-size:14px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px;">
            <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            {{ __('plan-do-acts.title') }}
        </h2>
        <span style="font-size:11.5px;color:#9ca3af;">{{ __('plan-do-acts.global_subtitle') }}</span>
        <label style="font-size:12px;color:#64748b;font-weight:600;margin-left:6px;">{{ __('plan-do-acts.field_project') }}</label>
        <select onchange="location.href=this.value"
                style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12.5px;outline:none;min-width:200px;background:#fff;color:#1f2937;">
            <option value="{{ route('plan-do-acts.index') }}" {{ !$selectedProjectId ? 'selected' : '' }}>{{ __('plan-do-acts.all_projects') }}</option>
            @foreach($projects as $p)
                <option value="{{ route('plan-do-acts.index', ['project' => $p->id]) }}" {{ (string) $selectedProjectId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <button onclick="pdaOpenCreate({{ $pdaNewProjectId ?? 'null' }}, {})"
            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:13px;font-weight:600;color:#fff;background:linear-gradient(135deg,var(--t600),#9b8afb);border:none;border-radius:8px;cursor:pointer;">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        {{ __('plan-do-acts.new') }}
    </button>
</div>
@endif

{{-- 목록 --}}
@forelse($items as $it)
    @include('plan-do-acts._card', ['it' => $it, 'projectId' => $it->project_id, 'showProject' => empty($selectedProjectId)])
@empty
    <div style="background:#fff;border-radius:14px;border:1px solid var(--color-bg-muted);padding:48px 20px;text-align:center;color:var(--color-text-tertiary);">
        <div style="font-size:30px;margin-bottom:8px;">🗂️</div>
        <div style="font-size:13px;">{{ __('plan-do-acts.empty') }}</div>
        <div style="font-size:12px;margin-top:4px;">{{ __('plan-do-acts.empty_hint_global') }}</div>
    </div>
@endforelse

@include('plan-do-acts._modal')
@endsection
