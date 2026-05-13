@extends('layouts.app')

@section('title', 'Prompt Builder - 빌더 시퀀스')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 16px;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <a href="{{ route('builder.sequences.select-project') }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 프로젝트 변경</a>
            <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:8px 0 0;">빌더 시퀀스</h1>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ $project->name }}</p>
        </div>
    </div>

    @if($sequences->isEmpty())
    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:48px;text-align:center;">
        <p style="color:#6b7280;font-size:14px;">생성된 시퀀스가 없습니다.</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($sequences as $sequence)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <a href="{{ route('builder.sequences.show', $sequence) }}" style="font-size:15px;font-weight:600;color:#1e1b4b;text-decoration:none;">{{ $sequence->name }}</a>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">
                        {{ strtoupper($sequence->ai_type) }} · {{ $sequence->builders_count }}개 스텝 · {{ ucfirst($sequence->status) }}
                    </div>
                </div>
                <a href="{{ route('builder.sequences.show', $sequence) }}" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#374151;text-decoration:none;">보기</a>
            </div>
        </div>
        @endforeach
    </div>
    {{ $sequences->links() }}
    @endif

</div>
@endsection
