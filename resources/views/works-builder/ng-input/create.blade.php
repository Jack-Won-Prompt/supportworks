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
<div class="pt-4 max-w-3xl mx-auto">
    <p class="text-sm text-gray-500 mb-5">담당자가 지적한 미스를 적어주세요. 웍스가 이 정보를 반영하여 HTML을 다시 생성합니다.</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('wb.tasks.ng-input.store', ['task'=>$task,'session'=>$session]) }}"
          class="bg-white rounded-xl border border-gray-100 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-2">미스 항목 설명 <span class="text-red-500">*</span></label>
            <textarea name="miss_description" rows="6" required
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      placeholder="어떤 부분이 잘못되었는지 구체적으로 적어주세요.&#10;예: 헤더의 로고가 가운데 정렬되어야 하는데 좌측 정렬되어 있음.">{{ old('miss_description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">수정 지시 (담당자 명령어 박스)</label>
            <textarea name="command_box" rows="4"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      placeholder="검수 화면에서 누적한 명령어를 그대로 붙여넣으세요.">{{ old('command_box', $cmd) }}</textarea>
        </div>

        <input type="hidden" name="highlights_snapshot" value='@json($highlights)'>

        @if (!empty($highlights))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs">
                <p class="font-medium text-gray-700 mb-2">담당자가 지목한 요소 {{ count($highlights) }}개</p>
                <ul class="space-y-1 text-gray-600">
                    @foreach ($highlights as $h)
                        <li class="font-mono">
                            <span class="text-indigo-600">{{ $h['selector_path'] ?? $h['selector'] ?? '?' }}</span>
                            <span class="text-gray-400">({{ $h['tag_name'] ?? $h['tag'] ?? '?' }})</span>
                            @if (!empty($h['text_snippet'] ?? $h['text'] ?? null))
                                — {{ \Illuminate\Support\Str::limit($h['text_snippet'] ?? $h['text'], 50) }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
            <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
            <button type="submit" class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                저장 후 웍스 재생성 시작 →
            </button>
        </div>
    </form>
</div>
@endsection
