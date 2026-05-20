@extends('layouts.app')

@section('title', '웍스 생성 결과 1차 확인 — Task #'.$task->id)

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">1차 확인</span>
@endsection

@section('content')
<div class="pt-4 space-y-4">
    <p class="text-sm text-gray-500">명세 v11 §1.5 — 웍스가 생성한 HTML이 의도와 맞는지 확인 후 검수 단계로 진행하거나 재생성하세요.</p>

    @if (!$latest)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-sm">
            <p class="font-medium mb-1">아직 생성된 HTML이 없습니다.</p>
            <p class="text-xs text-amber-800">옵션 입력 화면에서 [확정 후 웍스 호출 →]을 눌러 생성을 시작하세요.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 p-4 text-sm space-y-2">
                    <h3 class="font-semibold mb-2 text-gray-900">메타</h3>
                    <div class="flex justify-between"><dt class="text-gray-500">버전</dt><dd>v{{ $latest->version }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">차수</dt><dd>{{ $latest->review_round }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">생성 엔진</dt><dd>{{ $latest->generated_by }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">크기</dt><dd>{{ number_format(strlen($latest->html_content)) }} B</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">생성</dt><dd>{{ $latest->created_at?->format('Y-m-d H:i') }}</dd></div>
                    <div class="text-xs"><dt class="text-gray-500">SHA-256</dt><dd class="font-mono break-all">{{ $latest->html_hash }}</dd></div>
                </div>

                <form method="POST" action="{{ route('wb.tasks.result-confirm.decide', ['task' => $task, 'html' => $latest]) }}"
                      class="bg-white rounded-xl border border-gray-100 p-4 text-sm space-y-3">
                    @csrf
                    <h3 class="font-semibold text-gray-900">의사 결정</h3>
                    <textarea name="note" rows="2" placeholder="메모 (선택)" class="w-full border border-gray-200 rounded-lg p-2 text-xs"></textarea>
                    <div class="space-y-2">
                        <button type="submit" name="decision" value="regenerate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
                            🔄 재생성 요청
                        </button>
                        <button type="submit" name="decision" value="proceed_to_review"
                                class="w-full px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            ✓ 검수 진행
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 overflow-hidden"
                 x-data="{ full: false }"
                 :class="full ? 'fixed inset-0 z-50 m-0 rounded-none' : 'relative'"
                 @keydown.escape.window="full = false">
                <div class="px-3 py-2 border-b border-gray-100 bg-gray-50 text-xs text-gray-600 flex justify-between items-center">
                    <span>웍스 결과 미리보기 <span class="text-gray-400 ml-2">(sandbox · 스크립트 차단)</span></span>
                    <button type="button" @click="full = !full"
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-gray-200 rounded-md hover:bg-gray-100">
                        <svg x-show="!full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <svg x-show="full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span x-text="full ? '닫기 (Esc)' : '전체 화면'"></span>
                    </button>
                </div>
                <iframe sandbox="" srcdoc="{{ \App\Services\WorksBuilder\Preview\PreviewHtmlSanitizer::prepareForIframe($latest->html_content) }}"
                        class="w-full bg-white block"
                        :style="full ? 'height: calc(100vh - 38px);' : 'height: 700px;'"></iframe>
            </div>
        </div>
    @endif
</div>
@endsection
