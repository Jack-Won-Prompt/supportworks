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
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">웍스 생성 결과 1차 확인</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">결과 검토</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
        </div>
        <p class="text-sm text-gray-500">명세 v11 §1.5 — 웍스가 생성한 HTML이 의도와 맞는지 확인 후 검수 단계로 진행하거나 재생성합니다.</p>
    </div>

    @if (!$latest)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 text-sm text-amber-900">
            <p class="font-semibold mb-1">아직 생성된 HTML이 없습니다.</p>
            <p class="text-xs">옵션 입력 화면에서 [확정 후 웍스 호출 →]을 눌러 생성을 시작하세요.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- 좌측: 메타 + 의사결정 --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            메타
                        </h3>
                    </div>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-xs text-gray-400">버전</dt><dd class="font-medium text-gray-700">v{{ $latest->version }}</dd></div>
                        <div class="flex justify-between"><dt class="text-xs text-gray-400">차수</dt><dd class="font-medium text-gray-700">{{ $latest->review_round }}차</dd></div>
                        <div class="flex justify-between"><dt class="text-xs text-gray-400">생성 엔진</dt><dd><span class="inline-block text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full">{{ $latest->generated_by }}</span></dd></div>
                        <div class="flex justify-between"><dt class="text-xs text-gray-400">크기</dt><dd class="font-medium text-gray-700">{{ number_format(strlen($latest->html_content)) }} B</dd></div>
                        <div class="flex justify-between"><dt class="text-xs text-gray-400">생성</dt><dd class="text-xs text-gray-600">{{ $latest->created_at?->format('Y-m-d H:i') }}</dd></div>
                        <div class="pt-2 border-t border-gray-50">
                            <dt class="text-xs text-gray-400 mb-1">SHA-256</dt>
                            <dd class="font-mono text-xs text-gray-600 break-all">{{ $latest->html_hash }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            의사 결정
                        </h3>
                    </div>
                    <form method="POST" action="{{ route('wb.tasks.result-confirm.decide', ['task' => $task, 'html' => $latest]) }}" class="space-y-3">
                        @csrf
                        <textarea name="note" rows="2" placeholder="메모 (선택)"
                                  class="w-full border border-gray-200 rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        <button type="submit" name="decision" value="regenerate"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            재생성 요청
                        </button>
                        <button type="submit" name="decision" value="proceed_to_review"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            검수 진행
                        </button>
                    </form>
                </div>
            </div>

            {{-- 우측: 미리보기 --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                 x-data="{ full: false }"
                 :class="full ? 'fixed inset-0 z-50 m-0 rounded-none' : 'relative'"
                 @keydown.escape.window="full = false">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <div class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        웍스 결과 미리보기
                        <span class="text-xs font-normal text-gray-400">sandbox · 스크립트 차단</span>
                    </div>
                    <button type="button" @click="full = !full"
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-gray-200 rounded-md hover:bg-white text-gray-700">
                        <svg x-show="!full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <svg x-show="full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span x-text="full ? '닫기 (Esc)' : '전체 화면'"></span>
                    </button>
                </div>
                <iframe sandbox="" srcdoc="{{ \App\Services\WorksBuilder\Preview\PreviewHtmlSanitizer::prepareForIframe($latest->html_content) }}"
                        class="w-full bg-white block"
                        :style="full ? 'height: calc(100vh - 50px);' : 'height: 720px;'"></iframe>
            </div>
        </div>
    @endif
</div>
@endsection
