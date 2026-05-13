@extends('layouts.app')

@section('title', 'Prompt Builder - 피드백 상세')

@section('content')
<div style="max-width:800px;margin:0 auto;padding:32px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('builder.feedback.index', $feedback->builder->project_id) }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 피드백 목록</a>
        <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:8px 0 0;">피드백 상세</h1>
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <span style="font-size:12px;color:#9ca3af;">빌더</span>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:4px 0 0;">{{ $feedback->builder->title ?? '-' }}</p>
            </div>
            <div>
                <span style="font-size:12px;color:#9ca3af;">버전</span>
                <p style="font-size:14px;font-weight:600;color:#111827;margin:4px 0 0;">v{{ $feedback->builder_version }}</p>
            </div>
            <div>
                <span style="font-size:12px;color:#9ca3af;">업로드</span>
                <p style="font-size:14px;color:#374151;margin:4px 0 0;">{{ $feedback->uploader->name ?? '-' }} · {{ $feedback->created_at->format('Y.m.d H:i') }}</p>
            </div>
            <div>
                <span style="font-size:12px;color:#9ca3af;">평가</span>
                <p style="font-size:14px;color:#374151;margin:4px 0 0;">{{ $feedback->user_rating ? '⭐ ' . $feedback->user_rating . '/5' : '없음' }}</p>
            </div>
        </div>

        @if($feedback->user_memo)
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <span style="font-size:12px;color:#9ca3af;display:block;margin-bottom:4px;">메모</span>
            <p style="font-size:14px;color:#374151;margin:0;">{{ $feedback->user_memo }}</p>
        </div>
        @endif
    </div>

    @if($feedback->analysis_result)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">
        <h3 style="font-size:15px;font-weight:700;color:#374151;margin:0 0 16px;">분석 결과</h3>
        <pre style="font-size:12px;background:#f9fafb;padding:12px;border-radius:8px;overflow-x:auto;">{{ json_encode($feedback->analysis_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

</div>
@endsection
