@extends('layouts.app')

@section('title', 'Prompt Builder - 빌더 이력')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 16px;">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <a href="{{ route('builder.history.select-project') }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 프로젝트 변경</a>
            </div>
            <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:0;">빌더 이력</h1>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ $project->name }}</p>
        </div>
        <a href="{{ route('builder.new') }}" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:#7c3aed;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            새 빌더
        </a>
    </div>

    @if($builders->isEmpty())
    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:48px;text-align:center;">
        <p style="color:#6b7280;font-size:14px;">이 프로젝트에 빌더가 없습니다.</p>
        <a href="{{ route('builder.new') }}" style="display:inline-block;margin-top:12px;padding:8px 20px;background:#7c3aed;color:#fff;border-radius:8px;font-size:13px;text-decoration:none;">첫 빌더 만들기</a>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($builders as $builder)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 20px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <a href="{{ route('builder.history.show', $builder) }}" style="font-size:15px;font-weight:600;color:#1e1b4b;text-decoration:none;">{{ $builder->title }}</a>
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;
                            {{ $builder->ai_type === 'cursor' ? 'background:#dbeafe;color:#2563eb;' : ($builder->ai_type === 'claude' ? 'background:#fde68a;color:#92400e;' : 'background:#d1fae5;color:#065f46;') }}">
                            {{ strtoupper($builder->ai_type) }}
                        </span>
                        @if($builder->is_edited)
                        <span style="font-size:11px;padding:2px 8px;background:#f3f4f6;color:#6b7280;border-radius:20px;">수정됨</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:12px;font-size:12px;color:#9ca3af;">
                        <span>{{ $builder->workspace->name ?? '-' }}</span>
                        <span>v{{ $builder->current_version }}</span>
                        <span>{{ $builder->versions_count }}개 버전</span>
                        <span>{{ $builder->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('builder.history.show', $builder) }}" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#374151;text-decoration:none;">보기</a>
                    <form method="POST" action="{{ route('builder.history.duplicate', $builder) }}" style="display:inline;" onsubmit="return confirm('복제하시겠습니까?')">
                        @csrf
                        <button type="submit" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#374151;background:#fff;cursor:pointer;">복제</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top:20px;">
        {{ $builders->links() }}
    </div>
    @endif

</div>
@endsection
