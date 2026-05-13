@extends('layouts.app')

@section('title', __('projects.question_register_page'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.questions.index', $project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.qa_title') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('common.register') }}</span>
@endsection

@section('content')
<div class="max-w-3xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-5 pb-4 border-b border-gray-100">
            <p class="text-xs text-gray-400">{{ __('projects.project_label') }}</p>
            <p class="text-sm font-medium text-gray-700">{{ $project->name }}</p>
        </div>

        <form method="POST" action="{{ route('projects.questions.store', $project) }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.question_title') }} <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="{{ __('projects.question_title_placeholder') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.question_content') }} <span class="text-red-500">*</span></label>
                <textarea name="content" rows="6" required
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="{{ __('projects.question_content_placeholder') }}">{{ old('content') }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_private" id="is_private" value="1" {{ old('is_private') ? 'checked' : '' }}
                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                <label for="is_private" class="text-sm text-gray-600">{{ __('projects.private_question') }}</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('common.register') }}</button>
                <a href="{{ route('projects.questions.index', $project) }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
