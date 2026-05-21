@extends('layouts.app')

@section('title', 'NG 미스 입력 — Task #'.$task->id.' · '.$session->review_round.'차수')

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">NG 미스 입력 · {{ $session->review_round }}차수</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">NG 미스 입력</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-rose-100 text-rose-700">{{ $session->review_round }}차수 NG</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
        </div>
        <p class="text-sm text-gray-500">담당자가 지적한 미스를 적어주세요. 웍스가 이 정보를 반영하여 HTML을 다시 생성합니다.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 폼 --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    미스 항목 작성
                </h3>
            </div>
            <form method="POST" action="{{ route('wb.tasks.ng-input.store', ['task'=>$task,'session'=>$session]) }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">미스 항목 설명 <span class="text-red-500">*</span></label>
                    <textarea name="miss_description" rows="7" required
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              placeholder="어떤 부분이 잘못되었는지 구체적으로 적어주세요.&#10;예: 헤더의 로고가 가운데 정렬되어야 하는데 좌측 정렬되어 있음.">{{ old('miss_description') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">수정 지시 (담당자 명령어 박스)</label>
                    <textarea name="command_box" rows="5"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              placeholder="검수 화면에서 누적한 명령어를 그대로 붙여넣으세요.">{{ old('command_box', $cmd) }}</textarea>
                </div>

                <input type="hidden" name="highlights_snapshot" value='@json($highlights)'>

                <div class="pt-4 border-t border-gray-50 flex justify-end gap-2">
                    <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        저장 후 웍스 재생성 시작
                    </button>
                </div>
            </form>
        </div>

        {{-- 사이드: 지목 요소 --}}
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    지목된 요소
                    <span class="text-xs font-normal text-gray-400">({{ count($highlights ?? []) }})</span>
                </h3>
            </div>
            @if (!empty($highlights))
                <ul class="space-y-2 text-xs">
                    @foreach ($highlights as $h)
                        <li class="border border-gray-100 rounded-lg p-2.5 bg-gray-50">
                            <div class="font-mono text-indigo-600 break-all">{{ $h['selector_path'] ?? $h['selector'] ?? '?' }}</div>
                            <div class="text-gray-400 mt-1">
                                <span class="inline-block px-1.5 py-0.5 bg-white border border-gray-200 rounded text-[10px]">{{ $h['tag_name'] ?? $h['tag'] ?? '?' }}</span>
                                @if (!empty($h['text_snippet'] ?? $h['text'] ?? null))
                                    <span class="text-gray-600 italic">— {{ \Illuminate\Support\Str::limit($h['text_snippet'] ?? $h['text'], 50) }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-gray-400 text-center py-8">지목된 요소가 없습니다.<br>검수 화면에서 [+ 명령어]로 누적 후 다시 진행하세요.</p>
            @endif
        </div>
    </div>
</div>
@endsection
