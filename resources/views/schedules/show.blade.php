@extends('layouts.app')

@section('title', $schedule->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $schedule->project) }}" class="hover:text-indigo-500 transition-colors">{{ $schedule->project->name }}</a>
<span>›</span>
<a href="{{ route('projects.schedules.index', $schedule->project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.schedule') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ $schedule->title }}</span>
@endsection

@section('header-actions')
    <a href="{{ route('projects.gantt', $schedule->project) }}" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← {{ __('projects.gantt') }}</a>
    <a href="{{ route('projects.schedules.index', $schedule->project) }}" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← {{ __('projects.schedule') }}</a>
    <a href="{{ route('schedules.edit', $schedule) }}" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('common.edit') }}</a>
@endsection

@section('content')
<div class="max-w-3xl pt-4 space-y-5">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-1">{{ $schedule->title }}</h2>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full
                        {{ $schedule->status === 'pending'          ? 'bg-yellow-100 text-yellow-700'  : '' }}
                        {{ $schedule->status === 'in_progress'      ? 'bg-blue-100 text-blue-700'      : '' }}
                        {{ $schedule->status === 'completed'        ? 'bg-green-100 text-green-700'    : '' }}
                        {{ $schedule->status === 'cancelled'        ? 'bg-red-100 text-red-700'        : '' }}
                        {{ $schedule->status === 'review_submitted' ? 'bg-orange-100 text-orange-700'  : '' }}
                        {{ $schedule->status === 'review_completed' ? 'bg-purple-100 text-purple-700'  : '' }}">
                        {{ $schedule->status_label }}
                    </span>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full
                        {{ $schedule->priority === 'high' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $schedule->priority === 'medium' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $schedule->priority === 'low' ? 'bg-green-100 text-green-700' : '' }}">
                        {{ $schedule->priority_label }}
                    </span>
                </div>
            </div>
        </div>

        @if($schedule->description)
        <p class="text-sm text-gray-600 mb-4 whitespace-pre-line">{{ $schedule->description }}</p>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400">{{ __('projects.project_label') }}</p>
                <a href="{{ route('projects.show', $schedule->project) }}" class="text-sm text-indigo-600 font-medium">{{ $schedule->project->name }}</a>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('projects.assignee') }}</p>
                <p class="text-sm text-gray-700">{{ $schedule->assignee?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('projects.start_label') }}</p>
                <p class="text-sm text-gray-700">{{ $schedule->start_date->format('Y.m.d H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">{{ __('projects.end_label') }}</p>
                <p class="text-sm text-gray-700">{{ $schedule->end_date?->format('Y.m.d H:i') ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- 댓글 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('projects.comments_title', ['count' => $schedule->comments->count()]) }}</h3>

        @forelse($schedule->comments as $comment)
        <div class="flex gap-3 mb-4 pb-4 border-b border-gray-50 last:border-0">
            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-bold text-indigo-700 flex-shrink-0">
                {{ mb_substr($comment->user->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-gray-800">{{ $comment->user->name }}</span>
                    <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $comment->content }}</p>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 mb-4">{{ __('projects.no_comments') }}</p>
        @endforelse

        <form method="POST" action="" class="flex gap-3">
            @csrf
            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-bold text-indigo-700 flex-shrink-0">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <textarea name="content" rows="2" placeholder="{{ __('projects.comment_placeholder') }}"
                          class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                <button type="submit" class="mt-2 px-4 py-1.5 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">{{ __('common.register') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
