@extends('layouts.app')

@section('title', __('projects.new_project'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.new_project') }}</span>
@endsection

@section('content')
<div class="max-w-2xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.project_name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="{{ __('projects.project_name_placeholder') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.description') }}</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="{{ __('projects.description_placeholder') }}">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.status') }} <span class="text-red-500">*</span></label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('projects.status_active') }}</option>
                    <option value="on_hold" {{ old('status') === 'on_hold' ? 'selected' : '' }}>{{ __('projects.status_on_hold') }}</option>
                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>{{ __('projects.status_completed') }}</option>
                    <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>{{ __('projects.status_cancelled') }}</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.start_date') }}</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.end_date') }}</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-sm font-medium text-gray-700 mb-3">{{ __('projects.client_info') }}</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('projects.client_name') }}</label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="{{ __('projects.client_name_placeholder') }}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('projects.client_email') }}</label>
                        <input type="email" name="client_email" value="{{ old('client_email') }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               placeholder="client@example.com">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    {{ __('projects.create_project') }}
                </button>
                <a href="{{ route('projects.index') }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
