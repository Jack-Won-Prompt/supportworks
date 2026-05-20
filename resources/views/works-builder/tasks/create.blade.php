@extends('layouts.app')

@section('title', '새 작업 시작')

@section('header-actions')
    <a href="{{ route('wb.tasks.index') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        ← 진행 중 Task
    </a>
@endsection

@section('content')
<div class="pt-4 max-w-4xl"
     x-data="{
         tab: 'new',
         projectId: '{{ old('project_id', '') }}',
         mode: '{{ old('mode', 'new') }}',
         planningDocId: '{{ old('planning_doc_id', '') }}',
         plans: @js($planningDocsByProject),
         availablePlans() { return this.projectId ? (this.plans[this.projectId] || []) : []; },
     }">
    <p class="text-sm text-gray-500 mb-6">웍스가 HTML을 직접 생성합니다. 완료된 Task는 불변이며, 수정은 재실행/복제로 분기합니다.</p>

    {{-- 탭 --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200">
        <button type="button" @click="tab='new'"
                :class="tab==='new' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'"
                class="px-5 py-2.5 text-sm font-medium border-b-2 -mb-px">신규 Task</button>
        <button type="button" @click="tab='reopen'"
                :class="tab==='reopen' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'"
                class="px-5 py-2.5 text-sm font-medium border-b-2 -mb-px">완료 Task 재실행</button>
        <button type="button" @click="tab='clone'"
                :class="tab==='clone' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'"
                class="px-5 py-2.5 text-sm font-medium border-b-2 -mb-px">완료 Task 복제</button>
    </div>

    {{-- 신규 Task 탭 --}}
    <div x-show="tab==='new'" x-cloak>
        <form method="POST" action="{{ route('wb.tasks.start') }}" class="space-y-6 bg-white rounded-xl border border-gray-100 p-8">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-2">프로젝트 <span class="text-red-500">*</span></label>
                <select name="project_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        x-model="projectId" @change="planningDocId = ''">
                    <option value="">— 프로젝트 선택 —</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-3">작업 모드 <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="border border-gray-200 rounded-lg p-5 cursor-pointer hover:border-indigo-400 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <div class="flex items-center mb-2"><input type="radio" name="mode" value="new" x-model="mode" class="mr-2"><span class="font-semibold">신규 화면 (A)</span></div>
                        <p class="text-xs text-gray-500">옵션 입력 → 웍스 HTML 생성 → 검수</p>
                    </label>
                    <label class="border border-gray-200 rounded-lg p-5 cursor-pointer hover:border-indigo-400 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                        <div class="flex items-center mb-2"><input type="radio" name="mode" value="enhance" x-model="mode" class="mr-2"><span class="font-semibold">고도화 (B)</span></div>
                        <p class="text-xs text-gray-500">기획서 기반 옵션 보정 → 웍스 HTML 생성</p>
                    </label>
                </div>
            </div>

            <div x-show="mode === 'enhance'" x-cloak>
                <label class="block text-sm font-medium mb-2">참조 기획서</label>
                <template x-if="!projectId"><p class="text-sm text-amber-700">먼저 프로젝트를 선택하세요.</p></template>
                <template x-if="projectId && availablePlans().length === 0"><p class="text-sm text-amber-700">기획서가 없습니다. 없이도 진행 가능.</p></template>
                <template x-if="projectId && availablePlans().length > 0">
                    <select name="planning_doc_id" x-model="planningDocId" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">— 기획서 선택 (선택 사항) —</option>
                        <template x-for="p in availablePlans()" :key="p.id">
                            <option :value="p.id" x-text="`${p.title}  ·  v${p.version}  ·  ${p.status_label}`"></option>
                        </template>
                    </select>
                </template>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                <a href="{{ route('wb.tasks.index') }}" class="px-5 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">취소</a>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">작업 시작</button>
            </div>
        </form>
    </div>

    {{-- 재실행 탭 --}}
    <div x-show="tab==='reopen'" x-cloak>
        <div class="bg-white rounded-xl border border-gray-100 p-8">
            <p class="text-sm text-gray-600 mb-4">완료된 Task를 재실행합니다. 원본의 옵션·HTML을 가져와 새 Task로 분기합니다. 원본은 변하지 않습니다.</p>
            @if ($completedTasks->isEmpty())
                <p class="text-sm text-gray-500">완료된 Task가 없습니다.</p>
            @else
                <ul class="divide-y divide-gray-100 border border-gray-200 rounded-lg">
                    @foreach ($completedTasks as $ct)
                        <li class="px-4 py-3 flex items-center justify-between text-sm">
                            <div>
                                <div class="font-medium">#{{ $ct->id }} · {{ $ct->project?->name }}</div>
                                <div class="text-xs text-gray-500">{{ $ct->mode === 'new' ? '신규' : '고도화' }} · 완료 {{ $ct->completed_at?->format('Y-m-d H:i') }}</div>
                            </div>
                            <form method="POST" action="{{ route('wb.tasks.reopen', $ct) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Task #{{ $ct->id }}을(를) 재실행할까요?')"
                                        class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">재실행 →</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- 복제 탭 --}}
    <div x-show="tab==='clone'" x-cloak>
        <div class="bg-white rounded-xl border border-gray-100 p-8">
            <p class="text-sm text-gray-600 mb-4">완료 Task의 옵션·기획서 설정만 복사하여 새 화면을 만듭니다. 원본의 HTML은 사용하지 않습니다.</p>
            @if ($completedTasks->isEmpty())
                <p class="text-sm text-gray-500">완료된 Task가 없습니다.</p>
            @else
                <ul class="divide-y divide-gray-100 border border-gray-200 rounded-lg">
                    @foreach ($completedTasks as $ct)
                        <li class="px-4 py-3 flex items-center justify-between text-sm">
                            <div>
                                <div class="font-medium">#{{ $ct->id }} · {{ $ct->project?->name }}</div>
                                <div class="text-xs text-gray-500">{{ $ct->mode === 'new' ? '신규' : '고도화' }} · 완료 {{ $ct->completed_at?->format('Y-m-d H:i') }}</div>
                            </div>
                            <form method="POST" action="{{ route('wb.tasks.clone', $ct) }}">
                                @csrf
                                <button type="submit" onclick="return confirm('Task #{{ $ct->id }}을(를) 복제할까요?')"
                                        class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">복제 →</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<style>[x-cloak]{display:none !important}</style>
@endsection
