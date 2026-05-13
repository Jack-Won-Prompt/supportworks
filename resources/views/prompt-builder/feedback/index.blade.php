@extends('layouts.app')

@section('title', 'Prompt Builder - 결과 피드백')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 16px;">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <div>
            <a href="{{ route('builder.feedback.select-project') }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 프로젝트 변경</a>
            <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:8px 0 0;">결과 피드백</h1>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ $project->name }}</p>
        </div>
        <a href="{{ route('builder.feedback.upload', $project) }}" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:#7c3aed;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
            결과 업로드
        </a>
    </div>

    @if($feedbacks->isEmpty())
    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:48px;text-align:center;">
        <p style="color:#6b7280;font-size:14px;">업로드된 피드백이 없습니다.</p>
        <a href="{{ route('builder.feedback.upload', $project) }}" style="display:inline-block;margin-top:12px;padding:8px 20px;background:#7c3aed;color:#fff;border-radius:8px;font-size:13px;text-decoration:none;">첫 피드백 업로드</a>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($feedbacks as $feedback)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <a href="{{ route('builder.feedback.show', $feedback) }}" style="font-size:14px;font-weight:600;color:#1e1b4b;text-decoration:none;">{{ $feedback->builder->title ?? '-' }}</a>
                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">
                        v{{ $feedback->builder_version }} · {{ $feedback->uploader->name ?? '-' }} · {{ $feedback->created_at->diffForHumans() }}
                        @if($feedback->user_rating)
                        · ⭐ {{ $feedback->user_rating }}/5
                        @endif
                    </div>
                </div>
                <span style="font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;
                    {{ match($feedback->status) { 'applied' => 'background:#d1fae5;color:#065f46;', 'analyzed' => 'background:#dbeafe;color:#1d4ed8;', 'analyzing' => 'background:#fde68a;color:#92400e;', default => 'background:#f3f4f6;color:#6b7280;' } }}">
                    {{ match($feedback->status) { 'uploaded' => '업로드됨', 'analyzing' => '분석 중', 'analyzed' => '분석 완료', 'applied' => '적용됨', 'rejected' => '거절됨', default => $feedback->status } }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    {{ $feedbacks->links() }}
    @endif

</div>
@endsection
