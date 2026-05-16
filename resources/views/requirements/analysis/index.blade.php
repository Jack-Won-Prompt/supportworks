@extends('layouts.app')

@section('title', __('requirements.analysis_history'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('projects.requirements.index', $project) }}"
               class="text-sm text-blue-600 hover:underline">&larr; {{ __('requirements.requirements_list') }}</a>
            <h1 class="text-xl font-bold mt-1">{{ __('requirements.analysis_history') }}</h1>
        </div>
        <a href="{{ route('projects.requirements.analysis.create', $project) }}"
           class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
            {{ __('requirements.analysis_new') }}
        </a>
    </div>

    @if($sessions->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-12 text-center text-gray-400 text-sm">
            {{ __('requirements.analysis_no_history') }}
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-100">
            @foreach($sessions as $s)
            @php
                $statusColor = match($s->status) {
                    'pending','processing' => 'yellow',
                    'review'               => 'blue',
                    'approved'             => 'green',
                    'rejected'             => 'gray',
                    'failed'               => 'red',
                    default                => 'gray',
                };
            @endphp
            <a href="{{ route('projects.requirements.analysis.show', [$project, $s]) }}"
               class="flex items-center gap-4 px-4 py-3 hover:bg-gray-50 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">
                            {{ $s->status_label }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $s->llm_provider }} / {{ $s->llm_model }}</span>
                        <span class="text-xs text-gray-400">{{ __('requirements.analysis_files_count', ['n' => $s->files_count]) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $s->createdBy?->name ?? '-' }} &middot;
                        {{ $s->created_at->format('Y-m-d H:i') }}
                        @if($s->token_input)
                            &middot; {{ number_format($s->token_input + $s->token_output) }} tokens
                        @endif
                    </p>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    @endif

</div>
@endsection
