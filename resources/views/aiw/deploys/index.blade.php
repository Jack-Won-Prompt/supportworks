@extends('layouts.app')

@section('title', '배포 대상')

@section('header-actions')
@endsection

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">관리자 › 배포 대상</span>
@endsection

@section('content')
<div class="space-y-3">

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- 등록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-xl font-bold text-gray-900">배포 대상</h2>
        <p class="mt-1 text-sm text-gray-500">
            여기 등록한 명령이 <span class="font-medium">이 서버에서 그대로 실행</span>됩니다.
            작업 화면의 배포 버튼은 무엇을 실행할지 고르지 못하고, 이 목록에서만 고릅니다.
        </p>
        <p class="mt-1 text-xs text-amber-700">
            배포 스크립트는 대개 <code>migrate</code> 를 포함해 DB 스키마까지 바꿉니다. 되돌리기 비용이 큽니다.
        </p>

        <form method="POST" action="{{ route('settings.aiw-deploys.store') }}" class="mt-4 grid gap-3 md:grid-cols-5 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">프로젝트</label>
                <select name="project_id" required class="w-full rounded-lg border-gray-200 text-xs">
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">이름</label>
                <input type="text" name="name" required maxlength="100" value="{{ old('name') }}"
                       placeholder="운영 배포" class="w-full rounded-lg border-gray-200 text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">작업 디렉터리</label>
                <input type="text" name="working_dir" required maxlength="500" value="{{ old('working_dir') }}"
                       placeholder="/home/ubuntu/www/mangoshop" class="w-full rounded-lg border-gray-200 text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">명령</label>
                <input type="text" name="command" required maxlength="500" value="{{ old('command') }}"
                       placeholder="bash deploy.sh" class="w-full rounded-lg border-gray-200 text-xs">
            </div>
            <div class="flex gap-2">
                <input type="number" name="timeout_sec" min="30" max="3600" value="{{ old('timeout_sec', 900) }}"
                       title="제한 시간(초)" class="w-24 rounded-lg border-gray-200 text-xs">
                <button class="flex-1 rounded-lg bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-900">등록</button>
            </div>
        </form>
    </div>

    {{-- 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-2">등록된 대상</h3>

        @forelse ($targets as $target)
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-50 py-2 text-xs">
                <span class="rounded px-1.5 py-0.5 text-[11px] {{ $target->enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $target->enabled ? '활성' : '비활성' }}
                </span>
                <span class="font-semibold text-gray-800">{{ $target->name }}</span>
                <span class="text-gray-500">{{ $target->project?->name ?? '삭제된 프로젝트' }}</span>
                <code class="text-gray-500">{{ $target->working_dir }}</code>
                <code class="text-indigo-700">{{ $target->command }}</code>
                <span class="text-gray-400">{{ $target->timeout_sec }}초</span>
                <span class="text-gray-400">{{ $target->creator?->name }}</span>

                <span class="flex-1"></span>

                <form method="POST" action="{{ route('settings.aiw-deploys.toggle', $target) }}">
                    @csrf
                    <button class="rounded border border-gray-200 px-2 py-1 text-[11px] text-gray-600 hover:bg-gray-50">
                        {{ $target->enabled ? '비활성화' : '활성화' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('settings.aiw-deploys.destroy', $target) }}"
                      onsubmit="return confirm('이 배포 대상을 삭제할까요? 실행 기록도 함께 사라집니다.')">
                    @csrf @method('DELETE')
                    <button class="rounded border border-red-200 px-2 py-1 text-[11px] text-red-600 hover:bg-red-50">삭제</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-gray-400">등록된 배포 대상이 없습니다.</p>
        @endforelse
    </div>

    {{-- 최근 실행 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-2">최근 실행</h3>

        @forelse ($recent as $deploy)
            <details class="border-b border-gray-50 py-2 text-xs">
                <summary class="cursor-pointer flex flex-wrap items-center gap-2">
                    <span class="rounded px-1.5 py-0.5 text-[11px]
                        {{ $deploy->status === 'succeeded' ? 'bg-emerald-50 text-emerald-700'
                           : ($deploy->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                        {{ $deploy->statusLabel() }}
                    </span>
                    <span class="font-semibold text-gray-800">{{ $deploy->target?->name ?? '삭제됨' }}</span>
                    <span class="text-gray-400">{{ $deploy->requester?->name }}</span>
                    <span class="text-gray-400">{{ $deploy->created_at?->format('m-d H:i') }}</span>
                    @if ($deploy->duration())<span class="text-gray-400">{{ $deploy->duration() }}</span>@endif
                    @if ($deploy->exit_code !== null)
                        <span class="text-gray-400">exit {{ $deploy->exit_code }}</span>
                    @endif
                </summary>
                @if ($deploy->output)
                    <pre class="mt-1 max-h-80 overflow-auto whitespace-pre-wrap rounded bg-gray-900 p-2 text-[11px] text-gray-100">{{ $deploy->output }}</pre>
                @endif
            </details>
        @empty
            <p class="text-sm text-gray-400">실행 기록이 없습니다.</p>
        @endforelse
    </div>
</div>
@endsection
