@extends('layouts.ai-agent')
@section('title', 'Output v' . $output->version_no . ' — ' . $session->title)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · Output',
    'title'    => 'Output v' . $output->version_no,
    'subtitle' => $output->output_type->label() . ' · ' . $output->status->label(),
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'outputs'])

    <div style="flex:1;display:flex;flex-direction:column;gap:14px;">
        <div class="ags-card">
            <div class="ags-section-title">생성된 파일</div>
            @if(empty($files))
                <div style="font-size:12.5px;color:#94a3b8;padding:14px 0;">files_json이 비어 있습니다. (Phase 7 이후 채워짐)</div>
            @else
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;">
                    @foreach($files as $file)
                        <li style="font-size:12.5px;display:flex;justify-content:space-between;gap:10px;padding:8px 10px;border:1px solid #f1f5f9;border-radius:7px;">
                            <code style="background:none;color:#1e1b2e;">{{ $file['path'] ?? '(no path)' }}</code>
                            <span style="color:#94a3b8;">{{ $file['type'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="ags-card">
            <div class="ags-section-title">메타</div>
            <dl style="display:grid;grid-template-columns:140px 1fr;gap:6px 18px;font-size:12.5px;color:#475569;margin:0;">
                <dt>모델</dt><dd style="margin:0;color:#1e1b2e;">{{ $output->model_used ?? '—' }}</dd>
                <dt>Provider</dt><dd style="margin:0;color:#1e1b2e;">{{ $output->generated_by ?? '—' }}</dd>
                <dt>토큰</dt><dd style="margin:0;color:#1e1b2e;">in {{ $output->input_tokens ?? 0 }} / out {{ $output->output_tokens ?? 0 }}</dd>
                <dt>ZIP 경로</dt><dd style="margin:0;color:#1e1b2e;">{{ $output->zip_path ?? '미생성' }}</dd>
                <dt>생성 시각</dt><dd style="margin:0;color:#1e1b2e;">{{ $output->generated_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </dl>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('ai-agent.projects.agent-sessions.outputs.feedback', [$project, $session, $output]) }}" class="ags-btn ags-btn-ghost">검수 / 피드백</a>
        </div>
    </div>
</div>

@endsection
