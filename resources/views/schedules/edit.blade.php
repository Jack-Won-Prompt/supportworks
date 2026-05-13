@extends('layouts.app')

@section('title', __('projects.schedule_edit_page'))

@section('header-actions')
    <a href="{{ route('projects.gantt', $schedule->project) }}" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← {{ __('projects.gantt') }}</a>
    <a href="{{ route('projects.schedules.index', $schedule->project) }}" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← {{ __('projects.schedule') }}</a>
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $schedule->project) }}" class="hover:text-indigo-500 transition-colors">{{ $schedule->project->name }}</a>
<span>›</span>
<a href="{{ route('projects.schedules.index', $schedule->project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.schedule') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.schedule_edit_breadcrumb') }}</span>
@endsection

@section('content')
<div class="max-w-2xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('schedules.update', $schedule) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.schedule_title_required') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $schedule->title) }}" required
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.group_name') }}</label>
                    <input type="text" name="group_name" value="{{ old('group_name', $schedule->group_name) }}"
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
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $schedule->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.start_datetime') }}</label>
                    <input type="datetime-local" name="start_date" value="{{ old('start_date', $schedule->start_date->format('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.end_datetime') }}</label>
                    <input type="datetime-local" name="end_date" value="{{ old('end_date', $schedule->end_date?->format('Y-m-d\TH:i')) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.status') }}</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach([
                            'pending'          => __('projects.sched_status_pending'),
                            'in_progress'      => __('projects.sched_status_in_progress'),
                            'completed'        => __('projects.sched_status_completed'),
                            'cancelled'        => __('projects.sched_status_cancelled'),
                            'review_submitted' => __('projects.sched_status_review_submitted'),
                            'review_completed' => __('projects.sched_status_review_completed'),
                        ] as $val => $label)
                        <option value="{{ $val }}" {{ old('status', $schedule->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.priority') }}</label>
                    <select name="priority" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach([
                            'low'    => __('projects.priority_low'),
                            'medium' => __('projects.priority_medium'),
                            'high'   => __('projects.priority_high'),
                        ] as $val => $label)
                        <option value="{{ $val }}" {{ old('priority', $schedule->priority) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.assignee') }}</label>
                <select name="assigned_to" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('projects.no_assignee') }}</option>
                    @foreach($members as $member)
                    <option value="{{ $member->id }}" {{ old('assigned_to', $schedule->assigned_to) == $member->id ? 'selected' : '' }}>
                        {{ $member->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
                <a href="{{ route('schedules.show', $schedule) }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('common.cancel') }}</a>
                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" class="ml-auto"
                      onsubmit="return confirm('{{ __('projects.delete_confirm') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 text-red-600 text-sm rounded-lg border border-red-200 hover:bg-red-50">{{ __('common.delete') }}</button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
