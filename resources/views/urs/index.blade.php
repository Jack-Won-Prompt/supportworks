@extends('layouts.app')
@section('title', $project->name . ' — URS')

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">URS</span>
@endsection

@section('header-actions')@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'urs'])

<div style="display:flex;align-items:center;justify-content:center;padding:60px 20px;">
    <div style="background:#fff;border:1px solid #e4e4e7;border-radius:16px;padding:36px 40px;width:100%;max-width:560px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="width:56px;height:56px;background:#ede9fe;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <svg width="28" height="28" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#18181b;margin:0 0 8px;">URS 작성 시작</h2>
            <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.6;">
                기획서 내용을 기반으로 웍스와 문답을 통해<br>
                사용자 요구사항 명세서(URS)를 작성합니다.
            </p>
        </div>

        @php $planningDoc = $project->planningDocs()->first(); @endphp
        @if(!$planningDoc || !$planningDoc->content)
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>기획서가 없거나 내용이 비어 있습니다. 기획서를 먼저 작성하면 더 정확한 URS를 생성할 수 있습니다.</span>
        </div>
        @else
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#166534;display:flex;align-items:center;gap:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            기획서 "{{ $planningDoc->title }}"를 기반으로 URS를 생성합니다.
        </div>
        @endif

        <form action="{{ route('projects.urs.store', $project) }}" method="POST">
            @csrf
            <button type="submit"
                    style="width:100%;padding:12px;background:var(--t600);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;">
                URS 작성 시작하기 →
            </button>
        </form>
    </div>
</div>
@endsection
