@extends('layouts.ai-agent')
@section('title', '확정 산출물 — AI Agent')

@section('ai-agent-content')

@include('ai-agent.agent-sessions.partials.page-header', [
    'badge'    => 'AI Agent',
    'title'    => '확정 산출물',
    'subtitle' => '이 프로젝트에서 확정된 AI Agent Output 목록입니다. 다른 세션의 AI context로 참조됩니다.',
])

@if($confirmed->isEmpty())
    <div class="ags-empty">
        아직 확정된 산출물이 없습니다.<br>
        <small>세션을 끝까지 진행해 최종 승인하면 이곳에 표시됩니다.</small>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach($confirmed as $c)
            <div class="ags-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div>
                        <strong style="font-size:13.5px;">
                            @if($c->output)
                                {{ $c->output->session?->title ?? 'Output #' . $c->output->id }} — v{{ $c->output->version_no }}
                            @else
                                Output #{{ $c->output_id }}
                            @endif
                        </strong>
                        @if($c->summary)
                            <p style="font-size:12.5px;color:#475569;margin:6px 0 0;line-height:1.6;">{{ $c->summary }}</p>
                        @endif
                        <div style="font-size:11px;color:#94a3b8;margin-top:6px;">
                            확정자: {{ $c->confirmer?->name ?? '—' }} · {{ $c->confirmed_at?->format('Y-m-d H:i') }}
                        </div>
                    </div>
                    <span class="ags-tag ags-tag-confirmed">confirmed</span>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
