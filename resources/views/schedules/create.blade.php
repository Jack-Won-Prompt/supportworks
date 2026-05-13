@extends('layouts.app')

@section('title', __('projects.schedule_add_page'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.schedules.index', $project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.schedule') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.schedule_add_breadcrumb') }}</span>
@endsection

@section('content')
<div class="max-w-2xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-5 pb-4 border-b border-gray-100">
            <p class="text-xs text-gray-400">{{ __('projects.project_label') }}</p>
            <p class="text-sm font-medium text-gray-700">{{ $project->name }}</p>
        </div>

        <form method="POST" action="{{ route('projects.schedules.store', $project) }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.schedule_title_required') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="{{ __('projects.schedule_title') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.group_name') }}</label>
                    <input type="text" name="group_name" value="{{ old('group_name') }}"
                           list="group-list"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="{{ __('projects.group_placeholder_ext') }}">
                    <datalist id="group-list">
                        @foreach($groupNames as $g)
                        <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.description') }}</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="{{ __('projects.detail_content') }}">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.start_datetime') }} <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="start_date" value="{{ old('start_date') }}" required
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.end_datetime') }}</label>
                    <input type="datetime-local" name="end_date" value="{{ old('end_date') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.status') }}</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="pending">{{ __('projects.sched_status_pending') }}</option>
                        <option value="in_progress">{{ __('projects.sched_status_in_progress') }}</option>
                        <option value="completed">{{ __('projects.sched_status_completed') }}</option>
                        <option value="cancelled">{{ __('projects.sched_status_cancelled') }}</option>
                        <option value="review_submitted">{{ __('projects.sched_status_review_submitted') }}</option>
                        <option value="review_completed">{{ __('projects.sched_status_review_completed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.priority') }}</label>
                    <select name="priority" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="low">{{ __('projects.priority_low') }}</option>
                        <option value="medium" selected>{{ __('projects.priority_medium') }}</option>
                        <option value="high">{{ __('projects.priority_high') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.assignee') }}</label>
                <select name="assigned_to" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('projects.no_assignee') }}</option>
                    @foreach($members as $member)
                    <option value="{{ $member->id }}" {{ old('assigned_to') == $member->id ? 'selected' : '' }}>
                        {{ $member->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('common.register') }}</button>
                <a href="{{ route('projects.schedules.index', $project) }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
