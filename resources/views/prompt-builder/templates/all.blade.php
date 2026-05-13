@extends('layouts.app')

@section('title', 'Prompt Builder - 전체 템플릿')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 16px;">

    <div style="margin-bottom:24px;">
        <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:0 0 4px;">전체 템플릿</h1>
        <p style="font-size:13px;color:#6b7280;margin:0;">공개 템플릿 및 내 템플릿 모음</p>
    </div>

    @if($templates->isEmpty())
    <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:48px;text-align:center;">
        <p style="color:#6b7280;font-size:14px;">템플릿이 없습니다.</p>
    </div>
    @else
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
        @foreach($templates as $template)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                <span style="font-size:14px;font-weight:600;color:#1e1b4b;">{{ $template->name }}</span>
                <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                    {{ $template->share_scope === 'public' ? 'background:#d1fae5;color:#065f46;' : 'background:#f3f4f6;color:#6b7280;' }}">
                    {{ $template->share_scope }}
                </span>
            </div>
            @if($template->description)
            <p style="font-size:12px;color:#6b7280;margin:0 0 8px;">{{ Str::limit($template->description, 80) }}</p>
            @endif
            <span style="font-size:11px;color:#9ca3af;">{{ $template->owner->name ?? '-' }} · 사용 {{ $template->usage_count }}회</span>
        </div>
        @endforeach
    </div>
    {{ $templates->links() }}
    @endif

</div>
@endsection
