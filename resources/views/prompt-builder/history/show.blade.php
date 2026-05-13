@extends('layouts.app')

@section('title', 'Prompt Builder - ' . $builder->title)

@section('content')
<div style="max-width:960px;margin:0 auto;padding:32px 16px;">

    {{-- 헤더 --}}
    <div style="margin-bottom:24px;">
        <a href="{{ route('builder.history.index', $builder->project_id) }}" style="color:#7c3aed;font-size:13px;text-decoration:none;">← 이력 목록</a>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-top:12px;gap:16px;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <h1 style="font-size:20px;font-weight:700;color:#1e1b4b;margin:0;">{{ $builder->title }}</h1>
                    <span style="font-size:12px;padding:3px 10px;border-radius:20px;font-weight:600;
                        {{ $builder->ai_type === 'cursor' ? 'background:#dbeafe;color:#2563eb;' : ($builder->ai_type === 'claude' ? 'background:#fde68a;color:#92400e;' : 'background:#d1fae5;color:#065f46;') }}">
                        {{ strtoupper($builder->ai_type) }}
                    </span>
                    <span style="font-size:12px;padding:3px 10px;background:#f3f4f6;color:#6b7280;border-radius:20px;">v{{ $builder->current_version }}</span>
                </div>
                <div style="font-size:13px;color:#9ca3af;">
                    {{ $builder->project->name }} · {{ $builder->workspace->name ?? '-' }} · {{ $builder->created_at->format('Y.m.d H:i') }}
                </div>
            </div>
            <button onclick="copyContent()" style="padding:8px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;background:#fff;cursor:pointer;">복사</button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;">
        {{-- 프롬프트 내용 --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:13px;font-weight:600;color:#374151;">프롬프트 내용</span>
                @if($builder->is_edited)
                <span style="font-size:11px;color:#9ca3af;">수동 수정됨</span>
                @endif
            </div>
            <pre id="prompt-content" style="padding:16px;font-size:13px;font-family:monospace;line-height:1.6;white-space:pre-wrap;word-break:break-word;margin:0;overflow-x:auto;max-height:600px;overflow-y:auto;">{{ $builder->content }}</pre>
        </div>

        {{-- 사이드 정보 --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- 버전 이력 --}}
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 12px;">버전 이력</h3>
                @forelse($builder->versions as $version)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                    <div>
                        <span style="font-size:13px;font-weight:600;color:#374151;">v{{ $version->version_number }}</span>
                        <span style="font-size:11px;color:#9ca3af;margin-left:6px;">{{ $version->created_at->format('m.d H:i') }}</span>
                    </div>
                    @if($version->version_number !== $builder->current_version)
                    <form method="POST" action="{{ route('builder.history.revert', [$builder, $version->version_number]) }}" onsubmit="return confirm('v{{ $version->version_number }}으로 복원하시겠습니까?')">
                        @csrf
                        <button type="submit" style="font-size:11px;padding:3px 8px;border:1px solid #d1d5db;border-radius:4px;background:#fff;cursor:pointer;color:#374151;">복원</button>
                    </form>
                    @else
                    <span style="font-size:11px;color:#7c3aed;font-weight:600;">현재</span>
                    @endif
                </div>
                @empty
                <p style="font-size:12px;color:#9ca3af;">버전 없음</p>
                @endforelse
            </div>

            {{-- 적용 표준 --}}
            @if(!empty($builder->applied_standards))
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 12px;">적용 표준</h3>
                @foreach($builder->applied_standards as $std)
                <div style="font-size:12px;padding:4px 0;color:#6b7280;">{{ $std['name'] ?? '-' }}</div>
                @endforeach
            </div>
            @endif

            {{-- 태그 --}}
            @if(!empty($builder->tags))
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 12px;">태그</h3>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($builder->tags as $tag)
                    <span style="font-size:12px;padding:3px 10px;background:#f3f4f6;color:#374151;border-radius:20px;">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
function copyContent() {
    const text = document.getElementById('prompt-content').innerText;
    navigator.clipboard.writeText(text).then(() => alert('복사되었습니다.'));
}
</script>
@endpush
@endsection
