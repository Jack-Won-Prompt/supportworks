@extends('layouts.app')

@section('title', '새 작업 시작')

@section('header-actions')
    <a href="{{ route('wb.tasks.index') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        ← 진행 중 Task
    </a>
@endsection

@section('content')
<div x-data="{
        tab: 'new',
        projectId: '{{ old('project_id', '') }}',
        mode: '{{ old('mode', 'new') }}',
        planningDocId: '{{ old('planning_doc_id', '') }}',
        plans: @js($planningDocsByProject),
        availablePlans() { return this.projectId ? (this.plans[this.projectId] || []) : []; },
     }" class="space-y-6">

    {{-- 개요 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">새 작업 시작</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">Works Builder</span>
        </div>
        <p class="text-sm text-gray-500">웍스가 HTML을 직접 생성합니다. 완료된 Task는 불변이며, 수정은 재실행/복제로 분기됩니다.</p>

        {{-- 탭 --}}
        <div class="flex gap-1 mt-4 -mb-px border-b border-gray-100">
            <button type="button" @click="tab='new'"
                    :class="tab==='new' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">신규 Task</button>
            <button type="button" @click="tab='reopen'"
                    :class="tab==='reopen' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">완료 Task 재실행</button>
            <button type="button" @click="tab='clone'"
                    :class="tab==='clone' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors">완료 Task 복제</button>
        </div>
    </div>

    {{-- 신규 Task 탭 --}}
    <div x-show="tab==='new'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    신규 Task 정보
                </h3>
            </div>
            <form method="POST" action="{{ route('wb.tasks.start') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">프로젝트 <span class="text-red-500">*</span></label>
                    <select name="project_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            x-model="projectId" @change="planningDocId = ''">
                        <option value="">— 프로젝트 선택 —</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">작업 모드 <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 transition-colors">
                            <div class="flex items-center mb-1.5">
                                <input type="radio" name="mode" value="new" x-model="mode" class="mr-2">
                                <span class="font-semibold text-sm text-gray-800">신규 화면 (A)</span>
                                <span class="ml-auto inline-block text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">기본</span>
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed">옵션 입력 → 웍스 HTML 생성 → 검수</p>
                        </label>
                        <label class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 transition-colors">
                            <div class="flex items-center mb-1.5">
                                <input type="radio" name="mode" value="enhance" x-model="mode" class="mr-2">
                                <span class="font-semibold text-sm text-gray-800">고도화 (B)</span>
                                <span class="ml-auto inline-block text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">기획서</span>
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed">기획서 기반 옵션 보정 → 웍스 HTML 생성</p>
                        </label>
                    </div>
                </div>

                <div x-show="mode === 'enhance'" x-cloak>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">참조 기획서</label>
                    <template x-if="!projectId"><p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">먼저 프로젝트를 선택하세요.</p></template>
                    <template x-if="projectId && availablePlans().length === 0"><p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">기획서가 없습니다. 없이도 진행 가능합니다.</p></template>
                    <template x-if="projectId && availablePlans().length > 0">
                        <select name="planning_doc_id" x-model="planningDocId" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— 기획서 선택 (선택 사항) —</option>
                            <template x-for="p in availablePlans()" :key="p.id">
                                <option :value="p.id" x-text="`${p.title}  ·  v${p.version}  ·  ${p.status_label}`"></option>
                            </template>
                        </select>
                    </template>
                </div>

                <div class="pt-4 border-t border-gray-50 flex justify-end gap-2">
                    <a href="{{ route('wb.tasks.index') }}" class="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        작업 시작
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 재실행 탭 --}}
    <div x-show="tab==='reopen'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    재실행할 Task 선택
                    <span class="text-xs font-normal text-gray-400">({{ $completedTasks->count() }})</span>
                </h3>
            </div>
            <p class="text-xs text-gray-500 mb-4">원본의 옵션·HTML을 가져와 새 Task로 분기합니다. 원본은 변하지 않습니다.</p>

            @if ($completedTasks->isEmpty())
                <p class="text-xs text-gray-400 text-center py-8">완료된 Task가 없습니다.</p>
            @else
                <div class="divide-y divide-gray-50 -mx-2">
                    @foreach ($completedTasks as $ct)
                        <div class="px-2 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition-colors rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                                    {{ mb_substr($ct->project?->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate">#{{ $ct->id }} · {{ $ct->project?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                                        <span class="inline-block px-1.5 py-0.5 rounded {{ $ct->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                            {{ $ct->mode === 'new' ? '신규' : '고도화' }}
                                        </span>
                                        <span>완료 {{ $ct->completed_at?->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('wb.tasks.reopen', $ct) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" onclick="return confirm('Task #{{ $ct->id }}을(를) 재실행할까요?')"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                    재실행
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- 복제 탭 --}}
    <div x-show="tab==='clone'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    복제할 Task 선택
                    <span class="text-xs font-normal text-gray-400">({{ $completedTasks->count() }})</span>
                </h3>
            </div>
            <p class="text-xs text-gray-500 mb-4">옵션·기획서 설정만 복사해 새 화면을 만듭니다. 원본의 HTML은 사용하지 않습니다.</p>

            @if ($completedTasks->isEmpty())
                <p class="text-xs text-gray-400 text-center py-8">완료된 Task가 없습니다.</p>
            @else
                <div class="divide-y divide-gray-50 -mx-2">
                    @foreach ($completedTasks as $ct)
                        <div class="px-2 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition-colors rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-xs font-bold text-indigo-600 flex-shrink-0">
                                    {{ mb_substr($ct->project?->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate">#{{ $ct->id }} · {{ $ct->project?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                                        <span class="inline-block px-1.5 py-0.5 rounded {{ $ct->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                            {{ $ct->mode === 'new' ? '신규' : '고도화' }}
                                        </span>
                                        <span>완료 {{ $ct->completed_at?->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('wb.tasks.clone', $ct) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" onclick="return confirm('Task #{{ $ct->id }}을(를) 복제할까요?')"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                    복제
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<style>[x-cloak]{display:none !important}</style>
@endsection
