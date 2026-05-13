@extends('layouts.app')

@section('title', __('projects.question_edit_page'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $question->project) }}" class="hover:text-indigo-500 transition-colors">{{ $question->project->name }}</a>
<span>›</span>
<a href="{{ route('projects.questions.index', $question->project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.qa_title') }}</a>
<span>›</span>
<a href="{{ route('questions.show', $question) }}" class="hover:text-indigo-500 transition-colors">{{ $question->title }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('common.edit') }}</span>
@endsection

@section('content')
<div class="max-w-3xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('questions.update', $question) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.question_title') }}</label>
                <input type="text" name="title" value="{{ old('title', $question->title) }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.question_content') }}</label>
                <textarea name="content" rows="6" required
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('content', $question->content) }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_private" id="is_private" value="1" {{ $question->is_private ? 'checked' : '' }}
                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded">
                <label for="is_private" class="text-sm text-gray-600">{{ __('projects.private_question_short') }}</label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
                <a href="{{ route('questions.show', $question) }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('common.cancel') }}</a>
                <form method="POST" action="{{ route('questions.destroy', $question) }}" class="ml-auto"
                      onsubmit="return confirm('{{ __('projects.delete_question_confirm') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 text-red-600 text-sm rounded-lg border border-red-200 hover:bg-red-50">{{ __('common.delete') }}</button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
