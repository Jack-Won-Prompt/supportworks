@extends('layouts.app')

@section('title', '옵션 입력 — Task #'.$task->id)

@section('breadcrumb')
    <a href="{{ route('wb.tasks.index') }}" class="hover:text-indigo-500 transition-colors">진행 중 Task</a>
    <span>›</span>
    <a href="{{ route('wb.tasks.show', $task) }}" class="hover:text-indigo-500 transition-colors">Task #{{ $task->id }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">옵션 입력</span>
@endsection

@section('header-actions')
    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">옵션 v{{ $option->version }}</span>
@endsection

@section('content')
@php $data = $option->options_data ?? []; @endphp
<div x-data="wbOptions(@js([
        'previewUrl' => route('wb.tasks.options.preview', $task),
        'updateUrl'  => route('wb.tasks.options.update', $task),
        'initialSvg' => $svg,
        'initial'    => [
            'gnb_position'    => $data['gnb_position']    ?? 'top',
            'tab_structure'   => $data['tab_structure']   ?? 'single',
            'transition_type' => $data['transition_type'] ?? 'page',
            'main_color'      => $data['main_color']      ?? '#3b82f6',
            'theme_key'       => $data['theme_key']       ?? (array_key_first($themes) ?? ''),
        ],
     ]))" class="space-y-6">

    <form @submit.prevent="save()" class="space-y-6 text-sm">
        @csrf

        {{-- 헤더 + 옵션 입력 필드 (한 카드) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <h2 class="text-xl font-bold text-gray-900">옵션 입력</h2>
                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">신규 화면 (A)</span>
                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
            </div>
            <p class="text-sm text-gray-500 mb-5">명세 v11 §1.4 — 옵션을 정하고 [옵션 확정 → 웍스 호출]을 누르면 웍스가 HTML을 생성합니다.</p>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 pt-4 border-t border-gray-50">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">GNB 위치</label>
                    <select x-model="form.gnb_position" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="top">상단</option>
                        <option value="left">좌측</option>
                        <option value="right">우측</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">탭 구조</label>
                    <select x-model="form.tab_structure" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="single">단일</option><option value="top_tabs">상단 탭</option>
                        <option value="left_tabs">좌측 탭</option><option value="sidebar_tabs">사이드 + 탭</option>
                        <option value="none">없음</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">화면 전환</label>
                    <select x-model="form.transition_type" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="page">페이지 전환</option>
                        <option value="slide">슬라이드</option>
                        <option value="tab_switch">탭 전환</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">메인 색상</label>
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="form.main_color" @input="schedulePreview" class="w-9 h-9 border border-gray-200 rounded-lg cursor-pointer flex-shrink-0">
                        <input type="text" x-model="form.main_color" @input.debounce.300ms="schedulePreview"
                               class="flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-2 font-mono text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">테마</label>
                    @if (empty($themes))
                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2 py-2 text-xs text-amber-700">
                            등록된 테마 없음.
                        </div>
                    @else
                        <select x-model="form.theme_key" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach ($themes as $key => $m)
                                <option value="{{ $key }}">{{ $m['name'] ?? $key }} (v{{ $m['version'] ?? '?' }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>

        {{-- 와이어프레임 (풀폭) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
             x-data="{ full: false }"
             :class="full ? 'fixed inset-0 z-50 m-0 rounded-none flex flex-col' : 'relative'"
             @keydown.escape.window="full = false">

            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    레이아웃 와이어프레임
                    <span class="text-xs font-normal text-gray-400 ml-1">담당자 확인용 · 프롬프트엔 미포함</span>
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400" x-show="previewing">렌더 중…</span>
                    <button type="button" @click="full = !full"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md hover:bg-white text-gray-700 transition-colors">
                        <svg x-show="!full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <svg x-show="full" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span x-text="full ? '닫기 (Esc)' : '전체 화면'"></span>
                    </button>
                </div>
            </div>

            <div class="bg-gray-50 p-4"
                 :class="full ? 'flex-1 overflow-auto' : ''">
                <div class="w-full mx-auto flex items-center justify-center overflow-hidden"
                     :style="full ? 'height: 100%;' : 'aspect-ratio: 16 / 9;'">
                    <div class="w-full h-full" x-html="svg"></div>
                </div>
            </div>
        </div>

        {{-- 저장 푸터 --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-3 flex justify-end gap-2">
            <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm">취소</a>
            <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span x-show="!saving">옵션 확정 → 웍스 호출</span>
                <span x-show="saving">저장 중...</span>
            </button>
        </div>
    </form>
</div>

<style>[x-cloak]{display:none !important}</style>

@include('works-builder.partials.ai-progress-modal')

<script>
function wbOptions(cfg) {
    return {
        form: {...cfg.initial}, svg: cfg.initialSvg, previewing: false, saving: false, _timer: null,
        schedulePreview() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.refreshPreview(), 200);
        },
        async refreshPreview() {
            this.previewing = true;
            try {
                const res = await fetch(cfg.previewUrl, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
                    body: JSON.stringify(this.form),
                });
                const d = await res.json();
                if (d.svg) this.svg = d.svg;
            } finally { this.previewing = false; }
        },
        async save() {
            this.saving = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('_method', 'PUT');
                Object.entries(this.form).forEach(([k,v]) => fd.append(k, v));
                const res = await fetch(cfg.updateUrl, {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    alert('저장 실패: ' + res.status);
                    return;
                }
                const d = await res.json();
                if (d.ok) {
                    window.dispatchEvent(new CustomEvent('wb-ai-start', { detail: {
                        statusUrl:  d.status_url,
                        cancelUrl:  d.cancel_url,
                        taskUrl:    d.task_url    || '',
                        previewSvg: d.preview_svg || this.svg || '',
                        csrf,
                    }}));
                }
            } catch (e) {
                alert('저장 실패: ' + e.message);
            } finally { this.saving = false; }
        },
    };
}
</script>
@endsection
