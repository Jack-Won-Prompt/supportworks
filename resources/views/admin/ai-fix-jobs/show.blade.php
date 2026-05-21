@extends('layouts.admin')

@section('title', '윅스 수정 #' . $job->id)

@section('content')
<div class="p-6 max-w-5xl mx-auto">

    {{-- 뒤로 --}}
    <div class="mb-5">
        <a href="{{ route('admin.ai-fix-jobs.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            목록으로
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    @include('admin.ai-fix-jobs._detail', ['job' => $job, 'error' => $error])

</div>
@endsection
