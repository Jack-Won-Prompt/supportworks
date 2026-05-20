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
<div class="pt-4 space-y-5">
    <p class="text-sm text-gray-500">명세 v11 §1.3 — 기획서를 확인하고 옵션을 보정한 뒤 [확정 후 웍스 호출 →]을 누르면 웍스가 HTML을 생성합니다.</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-3 bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold mb-3 text-gray-900">참조 기획서</h2>
            @if ($plan)
                <div class="grid grid-cols-3 gap-3 text-sm mb-4 pb-4 border-b border-gray-100">
                    <div><dt class="text-xs text-gray-500">제목</dt><dd class="font-medium">{{ $plan->title }}</dd></div>
                    <div><dt class="text-xs text-gray-500">버전</dt><dd>v{{ $plan->version }}</dd></div>
                    <div><dt class="text-xs text-gray-500">상태</dt><dd>{{ $plan->status_label }}</dd></div>
                </div>
                @if ($plan->ai_summary)
                    <div class="mb-4">
                        <p class="text-xs text-indigo-700 font-semibold mb-1">요약</p>
                        <p class="text-sm text-gray-700 bg-indigo-50 border border-indigo-100 rounded-lg p-3 leading-relaxed whitespace-pre-line">{{ $plan->ai_summary }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs text-gray-500 font-semibold mb-1">본문</p>
                    <div class="max-h-[480px] overflow-y-auto bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs leading-relaxed whitespace-pre-line font-mono">{{ $plan->content ?: '(본문 없음)' }}</div>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-900">
                    참조 기획서가 지정되지 않았습니다. 옵션만으로 웍스 호출이 진행됩니다.
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5">
            <h2 class="font-semibold mb-3 text-gray-900">옵션 보정</h2>
            <form id="wb-spec-form" method="POST" action="{{ route('wb.tasks.spec-review.confirm', $task) }}" class="space-y-4"
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
                            statusUrl: d.status_url,
                            cancelUrl: d.cancel_url,
                            csrf: document.querySelector('meta[name=csrf-token]').content,
                          }}));
                        }
                      } catch (e) { alert('저장 실패: ' + e.message); }
                      finally { saving = false; }
                  ">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1">GNB 위치</label>
                    <select name="gnb_position" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach (['top'=>'상단','left'=>'좌측','right'=>'우측'] as $v=>$lbl)
                            <option value="{{ $v }}" @selected(old('gnb_position', $d['gnb_position'] ?? 'top') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">탭 구조</label>
                    <select name="tab_structure" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach (['single'=>'단일','top_tabs'=>'상단 탭','left_tabs'=>'좌측 탭','sidebar_tabs'=>'사이드 + 탭','none'=>'없음'] as $v=>$lbl)
                            <option value="{{ $v }}" @selected(old('tab_structure', $d['tab_structure'] ?? 'single') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">화면 전환</label>
                    <select name="transition_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach (['page'=>'페이지 전환','slide'=>'슬라이드','tab_switch'=>'탭 전환'] as $v=>$lbl)
                            <option value="{{ $v }}" @selected(old('transition_type', $d['transition_type'] ?? 'page') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">메인 색상</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="main_color" value="{{ old('main_color', $d['main_color'] ?? '#3b82f6') }}"
                               class="h-9 w-12 border border-gray-200 rounded-lg cursor-pointer" oninput="this.nextElementSibling.value=this.value">
                        <input type="text" value="{{ old('main_color', $d['main_color'] ?? '#3b82f6') }}"
                               class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono" readonly>
                    </div>
                </div>
                <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                    <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                    <button type="submit" :disabled="saving"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!saving">확정 후 웍스 호출 →</span>
                        <span x-show="saving">저장 중...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('works-builder.partials.ai-progress-modal')
@endsection
