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

    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">옵션 입력</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">신규 화면 (A)</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $task->id }}</span>
        </div>
        <p class="text-sm text-gray-500">명세 v11 §1.4 — GNB 위치·탭 구조·화면 전환·메인 색상을 정하고 [옵션 확정 → 웍스 호출]을 누르면 웍스가 HTML을 생성합니다.</p>
    </div>

    {{-- 메인 그리드: 폼 + 와이어프레임 --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- 폼 --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    레이아웃 옵션
                </h3>
            </div>
            <form @submit.prevent="save()" class="space-y-4 text-sm">
                @csrf
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
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">화면 전환 방식</label>
                    <select x-model="form.transition_type" @change="schedulePreview" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="page">페이지 전환</option>
                        <option value="slide">슬라이드</option>
                        <option value="tab_switch">탭 전환</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">메인 색상</label>
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="form.main_color" @input="schedulePreview" class="w-12 h-9 border border-gray-200 rounded-lg cursor-pointer">
                        <input type="text" x-model="form.main_color" @input.debounce.300ms="schedulePreview"
                               class="flex-1 border border-gray-200 rounded-lg px-3 py-2 font-mono text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">테마</label>
                    @if (empty($themes))
                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-700">
                            등록된 테마가 없습니다. resources/wb-themes/ 하위에 테마 디렉터리를 추가하세요.
                        </div>
                    @else
                        <select x-model="form.theme_key" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach ($themes as $key => $m)
                                <option value="{{ $key }}">{{ $m['name'] ?? $key }} (v{{ $m['version'] ?? '?' }})</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">현재 옵션의 테마 자산이 출력 zip의 <code>assets/theme/</code> 에 자동 포함됩니다.</p>
                    @endif
                </div>
                <div class="pt-4 border-t border-gray-50 flex justify-end gap-2">
                    <a href="{{ route('wb.tasks.show', $task) }}" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm">취소</a>
                    <button type="submit" :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span x-show="!saving">옵션 확정 → 웍스 호출</span>
                        <span x-show="saving">저장 중...</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- 와이어프레임 --}}
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    레이아웃 와이어프레임
                </h3>
                <span class="text-xs text-gray-400" x-show="previewing">렌더 중…</span>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 aspect-[16/10] flex items-center justify-center overflow-hidden">
                <div class="w-full h-full" x-html="svg"></div>
            </div>
            <p class="text-xs text-gray-400 mt-3">⚠ 와이어프레임은 담당자 확인용입니다. 웍스 프롬프트에는 포함되지 않습니다 (명세 v11 §1.4).</p>
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
