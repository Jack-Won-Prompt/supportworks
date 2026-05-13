@extends('layouts.app')

@section('title', 'Prompt Builder - 프로젝트 선택')

@section('content')
<div style="max-width:800px;margin:0 auto;padding:32px 16px;">

    {{-- 헤더 --}}
    <div style="margin-bottom:28px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7z"/><path d="M14 2v4a2 2 0 002 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
            <h1 style="font-size:22px;font-weight:700;color:#1e1b4b;margin:0;">Prompt Builder</h1>
        </div>
        <p style="color:#6b7280;font-size:14px;margin:0;">{{ $contextLabel }}</p>
    </div>

    @if($projects->isEmpty())
    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:40px;text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="1.5" style="margin:0 auto 16px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
        <p style="color:#6b7280;font-size:14px;">접근 가능한 프로젝트가 없습니다.</p>
        <a href="{{ route('projects.index') }}" style="display:inline-block;margin-top:12px;padding:8px 20px;background:#7c3aed;color:#fff;border-radius:8px;font-size:13px;text-decoration:none;">프로젝트 만들기</a>
    </div>
    @else
    <div style="display:grid;gap:12px;">
        @foreach($projects as $project)
        <a href="{{ route($nextRoute, $project) }}"
           style="display:flex;align-items:center;gap:16px;padding:16px 20px;background:#fff;border:2px solid {{ $project->id == $lastProjectId ? '#7c3aed' : '#e5e7eb' }};border-radius:12px;text-decoration:none;transition:border-color .15s,box-shadow .15s;cursor:pointer;"
           onmouseover="this.style.borderColor='#7c3aed';this.style.boxShadow='0 0 0 3px #ede9fe'"
           onmouseout="this.style.borderColor='{{ $project->id == $lastProjectId ? '#7c3aed' : '#e5e7eb' }}';this.style.boxShadow='none'">

            {{-- 아이콘 --}}
            <div style="width:44px;height:44px;border-radius:10px;background:{{ $project->id == $lastProjectId ? '#ede9fe' : '#f3f4f6' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $project->id == $lastProjectId ? '#7c3aed' : '#6b7280' }}" stroke-width="2"><path d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
            </div>

            {{-- 프로젝트 정보 --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="font-size:15px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $project->name }}</span>
                    @if($project->id == $lastProjectId)
                    <span style="font-size:11px;padding:2px 8px;background:#ede9fe;color:#7c3aed;border-radius:20px;font-weight:600;white-space:nowrap;">최근 사용</span>
                    @endif
                </div>
                <div style="display:flex;gap:12px;font-size:12px;color:#9ca3af;">
                    @if($project->pb_framework)
                    <span>{{ $project->pb_framework }}</span>
                    @endif
                    <span>빌더 {{ $project->pb_builders_count }}개</span>
                    <span>시퀀스 {{ $project->pb_sequences_count }}개</span>
                    <span>{{ $project->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            {{-- 화살표 --}}
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
