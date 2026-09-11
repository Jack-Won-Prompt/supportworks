@extends('layouts.app')

@section('title', '새 작업 지시 — '.$project->name)

@section('header-actions')
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.ai-works.index', $project) }}" class="hover:text-indigo-500 transition-colors">작업 지시</a>
<span>›</span>
<span style="color:var(--color-text-secondary);font-weight:500;">새 지시</span>
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'ai-works'])

<div class="space-y-3"
     x-data="{
        tools: @js(old('allowed_tools', $parent?->allowed_tools ?? $defaultTools)),
        agentId: @js(old('agent_id', $parent?->agent_id ?? ($agents->first()->id ?? null))),
        busy: @js($busyByPath),
        permissionMode: @js(old('permission_mode', $parent->permission_mode ?? 'acceptEdits')),
        useBranch: @js((bool) old('use_branch', $prefillUseBranch)),
        get bashSelected() { return this.tools.includes('Bash'); },
        // Bash 를 고른 채 acceptEdits 면 사람이 명령을 보는 지점이 없다.
        get bashUnattended() { return this.bashSelected && this.permissionMode === 'acceptEdits'; },
        get busyJobId() { return this.busy[this.agentId] ?? null; },
     }">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
            <div>
                <h2 class="text-xl font-bold text-gray-900">새 작업 지시</h2>
                @if ($parent)
                    <p class="text-sm text-gray-500 mt-1">
                        작업 <span class="font-medium">#{{ $parent->id }} {{ $parent->title }}</span> 의 후속 지시입니다.
                        이전 세션의 맥락을 이어받습니다. 원 작업은 수정되지 않습니다.
                    </p>
                @endif
            </div>
            <a href="{{ route('projects.ai-works.index', $project) }}"
               class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">취소</a>
        </div>

        @if ($errors->any())
            <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.ai-works.store', $project) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if ($parent)
                <input type="hidden" name="parent_job_id" value="{{ $parent->id }}">
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">제목</label>
                    <input type="text" name="title" required maxlength="191"
                           value="{{ old('title', $parent ? '후속: '.$parent->title : '') }}"
                           class="w-full rounded-lg border-gray-200 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">대상 담당자</label>
                    <select name="agent_id" x-model="agentId" required class="w-full rounded-lg border-gray-200 text-sm">
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <p x-show="busyJobId" x-cloak class="mt-1 text-xs text-amber-700">
                        현재 작업 #<span x-text="busyJobId"></span> 이(가) 실행 중입니다. 종료 후 자동으로 시작됩니다.
                    </p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">지시문 (마크다운)</label>
                <textarea name="instruction" rows="8" required
                          class="w-full rounded-lg border-gray-200 text-sm font-mono"
                          placeholder="예) README.md 맨 아래에 오늘 날짜를 한 줄 추가해 주세요.">{{ old('instruction') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">이미지 첨부 (선택)</label>
                <input type="file" name="images[]" accept="image/png,image/jpeg,image/webp,image/gif" multiple
                       class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0
                              file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-gray-700">
                <p class="mt-1 text-xs text-gray-400">
                    화면 캡처처럼 말로 설명하기 어려운 것을 붙입니다. 최대 5장·장당 10MB.
                    긴 변 1568px 로 줄여서 전달합니다 — 이미지 한 장이 컨텍스트를 1,000~1,600 토큰 정도 씁니다.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">모드</label>
                    <select name="mode" class="w-full rounded-lg border-gray-200 text-sm">
                        <option value="interactive" @selected(old('mode', $parent->mode ?? 'interactive') === 'interactive')>대화형 — 실행 중 대화 가능</option>
                        <option value="batch" @selected(old('mode', $parent->mode ?? '') === 'batch')>단발 — 한 번 실행 후 종료</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">모델</label>
                    <select name="model" class="w-full rounded-lg border-gray-200 text-sm">
                        <option value="">데몬 기본값 ({{ number_format($defaultContext) }} 토큰)</option>
                        @foreach ($contextLimits as $model => $limit)
                            <option value="{{ $model }}" @selected(old('model', $parent->model ?? '') === $model)>
                                {{ $model }} ({{ number_format($limit) }} 토큰)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">비용 상한 (USD)</label>
                    <input type="number" name="cost_limit_usd" step="0.01" min="0.01" max="1000" required
                           value="{{ old('cost_limit_usd', $parent->cost_limit_usd ?? $defaultCost) }}"
                           class="w-full rounded-lg border-gray-200 text-sm">
                    <p class="mt-1 text-xs text-gray-400">초과하면 작업이 자동 중단됩니다.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">허용 툴</label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($supportedTools as $tool)
                        <label class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="allowed_tools[]" value="{{ $tool }}"
                                   x-model="tools" class="rounded border-gray-300">
                            <span class="font-medium">{{ $tool }}</span>
                            @if (! in_array($tool, $autoApprovable, true))
                                <span class="text-[10px] text-orange-600">승인 필요</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <p x-show="bashUnattended" x-cloak
                   class="mt-2 rounded-lg bg-orange-50 border border-orange-200 px-3 py-2 text-xs text-orange-800">
                    <span class="font-semibold">이 설정에서는 명령이 확인 없이 실행됩니다.</span>
                    Bash 를 선택한 채 <span class="font-medium">파일 편집 자동 승인</span> 을 고르면
                    임의 명령까지 자동으로 실행됩니다. 명령을 하나씩 확인하려면 승인 모드를
                    <span class="font-medium">모든 툴 승인 요청</span> 으로 바꾸세요.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">승인 모드</label>
                    <select name="permission_mode" x-model="permissionMode" class="w-full rounded-lg border-gray-200 text-sm">
                        <option value="acceptEdits" @selected(old('permission_mode', $parent->permission_mode ?? 'acceptEdits') === 'acceptEdits')>
                            자동 승인 ({{ implode('/', $autoApprovable) }})
                        </option>
                        <option value="default" @selected(old('permission_mode', $parent->permission_mode ?? '') === 'default')>
                            모든 툴 승인 요청
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        {{-- 체크 해제 시 브라우저는 아무것도 보내지 않는다. 이 hidden 이 없으면
                             서버가 "값 없음"을 기본값(켬)으로 해석해 끌 방법이 사라진다. --}}
                        <input type="hidden" name="use_branch" value="0">
                        <input type="checkbox" name="use_branch" value="1" x-model="useBranch"
                               class="rounded border-gray-300">
                        별도 브랜치에서 작업 (<code class="text-xs">aiw/job-{id}</code>)
                    </label>
                </div>
            </div>

            <p x-show="! useBranch" x-cloak
               class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs text-gray-600">
                브랜치 분리를 끄면 <span class="font-medium">현재 브랜치에 직접</span> 씁니다.
                작업 폴더가 깨끗하지 않아도 진행되지만, 변경이 기존 작업물과 섞일 수 있습니다.
            </p>

            <div class="pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    지시 등록
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
