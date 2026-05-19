@extends('layouts.app')

@section('title', __('plan-do-acts.title'))

@section('content')
<div style="display:flex;flex-direction:column;gap:16px;max-width:1080px;margin:0 auto;width:100%;">

    {{-- 상단 헤더 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #f3f4f6;padding:18px 22px;">
        <h2 style="margin:0 0 4px;font-size:17px;font-weight:700;color:#18181b;display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            {{ __('plan-do-acts.title') }}
        </h2>
        <p style="margin:0;font-size:12px;color:#6b7280;">{{ __('plan-do-acts.global_subtitle') }}</p>
    </div>

    {{-- 목록 --}}
    @forelse($items as $it)
        @include('plan-do-acts._card', ['it' => $it, 'projectId' => $it->project_id, 'showProject' => true])
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
