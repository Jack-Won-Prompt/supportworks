@extends('layouts.app')

@section('title', '새 SR 요청')

@section('content')
<div class="pt-4 px-6 pb-12 max-w-4xl mx-auto">

    <form method="POST" action="{{ route('maint-requests.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-8">
                <label class="block text-sm font-medium text-gray-700 mb-1">메뉴 <span class="text-red-500">*</span></label>
                <input list="menu-list" name="menu_name" value="{{ old('menu_name') }}" required
                       placeholder="메뉴명 입력 (목록에 없으면 자동 등록)"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <datalist id="menu-list">
                    @foreach($menus as $m)<option value="{{ $m->name }}"></option>@endforeach
                </datalist>
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">우선순위 <span class="text-red-500">*</span></label>
                <select name="priority" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                    @foreach($priorityLabels as $k => $v)
                        <option value="{{ $k }}" {{ old('priority', 'normal')===$k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">요약 <span class="text-red-500">*</span></label>
            <input type="text" name="summary" value="{{ old('summary') }}" required maxlength="500"
                   placeholder="한 줄 요약"
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div x-data="srSummary({
                initialSummary: @js(old('ai_summary', '')),
                initialContextIds: [],
                endpoint: '{{ route('maint-requests.works-summary') }}',
                srId: null,
                csrf: '{{ csrf_token() }}',
             })">
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">상세 내용</label>
                {{-- 요약 없음 OR 원본 수정됨 → 버튼 표시. 요약 있고 원본 미수정 → 숨김 --}}
                <button type="button" @click="generate()" :disabled="loading"
                        x-show="!summary || dirty" x-cloak
                        :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-50 hover:border-indigo-300'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-md text-indigo-700 bg-white transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span x-show="!loading">웍스 요약 생성</span>
                    <span x-show="loading" x-cloak>생성 중...</span>
                </button>
            </div>
            <textarea name="content" rows="8" placeholder="요청 사항 상세"
                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('content') }}</textarea>

            {{-- 웍스 요약 결과 --}}
            <div x-show="summary" x-cloak class="mt-3 bg-indigo-50/40 border border-indigo-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-indigo-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        웍스 요약 (담당자용)
                    </h4>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500" x-show="contextIds.length > 0">참고 SR <span x-text="contextIds.length"></span>건</span>
                        <button type="button" @click="reset()" class="text-rose-600 hover:text-rose-800">제거</button>
                    </div>
                </div>
                <textarea x-model="summary" rows="8"
                          class="w-full px-3 py-2 border border-indigo-200 rounded-lg text-sm bg-white"
                          placeholder="AI 가 정리한 요약 — 필요하면 직접 수정하세요."></textarea>
                <p class="text-xs text-gray-500 mt-1.5">저장 시 원본과 함께 저장됩니다.</p>
            </div>

            <input type="hidden" name="ai_summary" :value="summary || ''">
            <input type="hidden" name="ai_summary_context_ids" :value="contextIds.length > 0 ? JSON.stringify(contextIds) : ''">
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">구분</label>
                <input type="text" name="category" value="{{ old('category') }}" maxlength="100"
                       placeholder="에러 / 개선 / 추가개발 등"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">콜로 담당자</label>
                <input list="colo-list" name="colo_user_name" value="{{ old('colo_user_name') }}"
                       placeholder="이름 입력"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <datalist id="colo-list">
                    @foreach($coloUsers as $u)<option value="{{ $u->name }}"></option>@endforeach
                </datalist>
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">위드웍스 담당자</label>
                <input list="dev-list" name="assignee_name" value="{{ old('assignee_name') }}"
                       placeholder="이름 입력"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <datalist id="dev-list">
                    @foreach($devUsers as $u)<option value="{{ $u->name }}"></option>@endforeach
                </datalist>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">요청일</label>
                <input type="date" name="request_date" value="{{ old('request_date', now()->toDateString()) }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">완료 예정일</label>
                <input type="date" name="eta" value="{{ old('eta') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">상태</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                    @foreach($statusLabels as $k => $v)
                        <option value="{{ $k }}" {{ old('status', 'requested')===$k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <a href="{{ route('maint-requests.index') }}" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">취소</a>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">등록</button>
        </div>
    </form>
</div>
@include('maint-requests._summary-js')
@endsection
