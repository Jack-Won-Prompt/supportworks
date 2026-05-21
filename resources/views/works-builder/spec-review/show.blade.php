@extends('layouts.app')

@section('title', '기획서 검토 (모드 B) — Task #'.$task->id)

@php
    $opt = $task->currentOption;
    $d   = $opt?->options_data ?? [];
@endphp

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">기획서 검토</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">기획서 검토</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">고도화 (B)</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
        </div>
        <p class="text-sm text-gray-500">명세 v11 §1.3 — 기획서를 확인하고 옵션을 보정한 뒤 [확정 후 웍스 호출 →]을 누르면 웍스가 HTML을 생성합니다.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- 기획서 --}}
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    참조 기획서
                </h3>
                @if ($plan)
                    <span class="text-xs text-gray-400">v{{ $plan->version }} · {{ $plan->status_label }}</span>
                @endif
            </div>

            @if ($plan)
                <div class="grid grid-cols-3 gap-4 text-sm mb-4 pb-4 border-b border-gray-50">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">제목</p>
                        <p class="text-sm font-medium text-gray-700">{{ $plan->title }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">버전</p>
                        <p class="text-sm font-medium text-gray-700">v{{ $plan->version }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">상태</p>
                        <p class="text-sm font-medium text-gray-700">{{ $plan->status_label }}</p>
                    </div>
                </div>

                @if ($plan->ai_summary)
                    <div class="mb-4">
                        <p class="text-xs text-indigo-600 font-semibold mb-1.5 flex items-center gap-1">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            AI 요약
                        </p>
                        <p class="text-sm text-gray-700 bg-indigo-50 border border-indigo-100 rounded-lg p-3 leading-relaxed whitespace-pre-line">{{ $plan->ai_summary }}</p>
                    </div>
                @endif

                <div>
                    <p class="text-xs text-gray-500 font-semibold mb-1.5">본문</p>
                    <div class="max-h-[480px] overflow-y-auto bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs leading-relaxed whitespace-pre-line font-mono text-gray-700">{{ $plan->content ?: '(본문 없음)' }}</div>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    참조 기획서가 지정되지 않았습니다. 옵션만으로 웍스 호출이 진행됩니다.
                </div>
            @endif
        </div>

        {{-- 옵션 보정 --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    옵션 보정
                </h3>
            </div>
            <form id="wb-spec-form" method="POST" action="{{ route('wb.tasks.spec-review.confirm', $task) }}"
                  x-data="{ saving: false }" @submit.prevent="
                      saving = true;
                      const fd = new FormData($el);
                      try {
                        const res = await fetch($el.action, {
                          method: 'POST',
                          body: fd,
                          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) { alert('저장 실패: ' + res.status); return; }
                        const d = await res.json();
                        if (d.ok) {
                          window.dispatchEvent(new CustomEvent('wb-ai-start', { detail: {
                            statusUrl:  d.status_url,
                            cancelUrl:  d.cancel_url,
                            taskUrl:    d.task_url    || '',
                            previewSvg: d.preview_svg || '',
                            csrf: document.querySelector('meta[name=csrf-token]').content,
                          }}));
                        }
                      } catch (e) { alert('저장 실패: ' + e.message); }
                      finally { saving = false; }
                  ">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">GNB 위치</label>
                        <select name="gnb_position" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach (['top'=>'상단','left'=>'좌측','right'=>'우측'] as $v=>$lbl)
                                <option value="{{ $v }}" @selected(old('gnb_position', $d['gnb_position'] ?? 'top') === $v)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">탭 구조</label>
                        <select name="tab_structure" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach (['single'=>'단일','top_tabs'=>'상단 탭','left_tabs'=>'좌측 탭','sidebar_tabs'=>'사이드 + 탭','none'=>'없음'] as $v=>$lbl)
                                <option value="{{ $v }}" @selected(old('tab_structure', $d['tab_structure'] ?? 'single') === $v)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">화면 전환</label>
                        <select name="transition_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach (['page'=>'페이지 전환','slide'=>'슬라이드','tab_switch'=>'탭 전환'] as $v=>$lbl)
                                <option value="{{ $v }}" @selected(old('transition_type', $d['transition_type'] ?? 'page') === $v)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">메인 색상</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="main_color" value="{{ old('main_color', $d['main_color'] ?? '#3b82f6') }}"
                                   class="h-9 w-12 border border-gray-200 rounded-lg cursor-pointer flex-shrink-0" oninput="this.nextElementSibling.value=this.value">
                            <input type="text" value="{{ old('main_color', $d['main_color'] ?? '#3b82f6') }}"
                                   class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono" readonly>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">테마</label>
                        @if (empty($themes))
                            <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-700">
                                등록된 테마가 없습니다.
                            </div>
                        @else
                            <select name="theme_key" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach ($themes as $key => $m)
                                    <option value="{{ $key }}" @selected(old('theme_key', $d['theme_key'] ?? array_key_first($themes)) === $key)>{{ $m['name'] ?? $key }} (v{{ $m['version'] ?? '?' }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">선택한 테마의 자산이 출력 zip에 자동 포함됩니다.</p>
                        @endif
                    </div>
                </div>
                <div class="mt-5 pt-4 border-t border-gray-50 flex justify-end gap-2">
                    <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                    <button type="submit" :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span x-show="!saving">확정 후 웍스 호출</span>
                        <span x-show="saving">저장 중...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('works-builder.partials.ai-progress-modal')
@endsection
