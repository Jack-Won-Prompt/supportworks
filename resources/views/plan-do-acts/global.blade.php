@extends('layouts.app')

@section('title', __('plan-do-acts.title'))

@section('content')
@php
    $pdaNewProjectId = ($selectedProjectId && $selectedProjectId !== 'none') ? (int) $selectedProjectId : null;
@endphp
<div style="display:flex;flex-direction:column;gap:16px;max-width:1080px;margin:0 auto;width:100%;">

    {{-- 상단: 헤더 + 프로젝트 선택 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #f3f4f6;padding:18px 22px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 4px;font-size:17px;font-weight:700;color:#18181b;display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ __('plan-do-acts.title') }}
                </h2>
                <p style="margin:0;font-size:12px;color:#6b7280;">{{ __('plan-do-acts.global_subtitle') }}</p>
            </div>
            <button onclick="pdaOpenCreate({{ $pdaNewProjectId ?? 'null' }}, {})" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(124,58,237,.3);">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('plan-do-acts.new') }}
            </button>
        </div>
        {{-- 프로젝트 선택 --}}
        <div style="margin-top:13px;display:flex;align-items:center;gap:8px;">
            <label style="font-size:12px;font-weight:700;color:#64748b;">{{ __('plan-do-acts.field_project') }}</label>
            <select onchange="location.href=this.value"
                    style="padding:7px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12.5px;outline:none;min-width:220px;background:#fff;color:#1f2937;">
                <option value="{{ route('plan-do-acts.index') }}" {{ !$selectedProjectId ? 'selected' : '' }}>{{ __('plan-do-acts.all_projects') }}</option>
                @foreach($projects as $p)
                <option value="{{ route('plan-do-acts.index', ['project' => $p->id]) }}" {{ (string) $selectedProjectId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- 목록 --}}
    @forelse($items as $it)
        @include('plan-do-acts._card', ['it' => $it, 'projectId' => $it->project_id, 'showProject' => empty($selectedProjectId)])
    @empty
        <div style="background:#fff;border-radius:14px;border:1px solid #f3f4f6;padding:48px 20px;text-align:center;color:#9ca3af;">
            <div style="font-size:30px;margin-bottom:8px;">🗂️</div>
            <div style="font-size:13px;">{{ __('plan-do-acts.empty') }}</div>
            <div style="font-size:12px;margin-top:4px;">{{ __('plan-do-acts.empty_hint_global') }}</div>
        </div>
    @endforelse

</div>

@include('plan-do-acts._modal')
@endsection
