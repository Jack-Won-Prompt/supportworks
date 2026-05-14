@extends('layouts.ai-agent')
@section('title', 'Output 버전 — ' . $session->title)

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent · 세션',
    'title'    => 'Output 생성 / 버전 관리',
    'subtitle' => '생성된 ' . $session->output_type->label() . ' Output을 버전별로 확인합니다.',
])

<div style="display:flex;gap:20px;align-items:flex-start;">
    @include('ai-agent.agent-sessions.partials.session-nav', ['active' => 'outputs'])

    <div style="flex:1;">
        @if($outputs->isEmpty())
            <div class="ags-empty">
                생성된 Output이 없습니다.<br>
                <small>Phase 7 이후 OutputGenerationService가 활성화되면 생성 버튼이 노출됩니다.</small>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($outputs as $o)
                    <a href="{{ route('ai-agent.projects.agent-sessions.outputs.show', [$project, $session, $o]) }}" class="ags-card" style="text-decoration:none;color:inherit;display:block;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                            <div>
                                <strong style="font-size:13.5px;">v{{ $o->version_no }} · {{ $o->output_type->label() }}</strong>
                                @if($o->change_summary)
                                    <p style="font-size:12px;color:#64748b;margin:4px 0 0;line-height:1.5;">{{ \Illuminate\Support\Str::limit($o->change_summary, 140) }}</p>
                                @endif
                            </div>
                            <span class="ags-tag ags-tag-{{ match($o->status->value) {
                                'confirmed' => 'confirmed',
                                'failed'    => 'failed',
                                'generating'=> 'running',
                                'reviewing' => 'attention',
                                default     => 'draft',
                            } }}">{{ $o->status->label() }}</span>
                        </div>
                        @if($o->feedbacks->isNotEmpty())
                            <div style="margin-top:8px;font-size:11.5px;color:#94a3b8;">피드백 {{ $o->feedbacks->count() }}건</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
