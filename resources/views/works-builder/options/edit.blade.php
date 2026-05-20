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
    <span class="text-xs text-gray-400 self-center">현재 옵션 v{{ $option->version }}</span>
@endsection

@section('content')
@php $data = $option->options_data ?? []; @endphp
<div class="pt-4"
     x-data="wbOptions(@js([
        'previewUrl' => route('wb.tasks.options.preview', $task),
        'updateUrl'  => route('wb.tasks.options.update', $task),
        'initialSvg' => $svg,
        'initial'    => [
            'gnb_position'    => $data['gnb_position']    ?? 'top',
            'tab_structure'   => $data['tab_structure']   ?? 'single',
            'transition_type' => $data['transition_type'] ?? 'page',
            'main_color'      => $data['main_color']      ?? '#3b82f6',
        ],
     ]))">
    <p class="text-sm text-gray-500 mb-5">명세 v11 §1.4 — 확정 시 웍스가 직접 HTML을 생성합니다.</p>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <form @submit.prevent="save()" class="md:col-span-2 bg-white rounded-xl border border-gray-100 p-5 space-y-4 text-sm">
            @csrf
            <div>
                <label class="block font-medium mb-1">GNB 위치</label>
                <select x-model="form.gnb_position" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="top">상단</option>
                    <option value="left">좌측</option>
                    <option value="right">우측</option>
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">탭 구조</label>
                <select x-model="form.tab_structure" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="single">단일</option><option value="top_tabs">상단 탭</option>
                    <option value="left_tabs">좌측 탭</option><option value="sidebar_tabs">사이드 + 탭</option>
                    <option value="none">없음</option>
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">화면 전환 방식</label>
                <select x-model="form.transition_type" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="page">페이지 전환</option>
                    <option value="slide">슬라이드</option>
                    <option value="tab_switch">탭 전환</option>
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">메인 색상</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="form.main_color" @input="schedulePreview" class="w-12 h-9 border border-gray-200 rounded-lg">
                    <input type="text" x-model="form.main_color" @input.debounce.300ms="schedulePreview"
                           class="flex-1 border border-gray-200 rounded-lg px-3 py-2 font-mono text-xs">
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                <a href="{{ route('wb.tasks.show', $task) }}" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                <button type="submit" :disabled="saving"
                        class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!saving">옵션 확정 → 웍스 호출</span>
                    <span x-show="saving">저장 중...</span>
                </button>
            </div>
        </form>

        <div class="md:col-span-3 bg-white rounded-xl border border-gray-100 p-5">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-semibold text-sm text-gray-900">레이아웃 와이어프레임</h2>
                <span class="text-xs text-gray-400" x-show="previewing">렌더 중…</span>
            </div>
            <div class="bg-gray-100 border border-gray-200 rounded-lg p-3 aspect-[16/10] flex items-center justify-center overflow-hidden">
                <div class="w-full h-full" x-html="svg"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2">⚠ 와이어프레임은 담당자 확인용. 웍스 프롬프트엔 포함되지 않습니다 (명세 v11 §1.4).</p>
        </div>
    </div>
</div>

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
                        statusUrl: d.status_url,
                        cancelUrl: d.cancel_url,
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
