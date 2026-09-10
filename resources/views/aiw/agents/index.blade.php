@extends('layouts.app')

@section('title', 'AI 작업 PC')

@section('header-actions')
@endsection

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">설정 › AI 작업 PC</span>
@endsection

@section('content')
<div class="space-y-3">

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- 발급된 토큰 원문: 이 화면에서 한 번만 보인다 --}}
    @if ($newToken)
        <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-4" x-data="{ copied: false }">
            <div class="text-sm font-bold text-amber-900 mb-1">{{ $newAgent }} 의 토큰</div>
            <p class="text-xs text-amber-800 mb-2">
                이 값은 <span class="font-semibold">지금 한 번만</span> 표시됩니다. 서버에는 해시만 저장되어 다시 볼 수 없습니다.
                데몬 <code>.env</code> 의 <code>SW_AGENT_TOKEN</code> 에 넣으세요.
            </p>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ $newToken }}" x-ref="tok"
                       class="flex-1 rounded-lg border-amber-300 bg-white text-xs font-mono">
                <button type="button" @click="navigator.clipboard.writeText($refs.tok.value); copied = true"
                        class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                    <span x-text="copied ? '복사됨' : '복사'"></span>
                </button>
            </div>
        </div>
    @endif

    {{-- 등록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-2">
            <h2 class="text-xl font-bold text-gray-900">AI 작업 PC</h2>
            <p class="text-sm text-gray-500 mt-1">
                데몬이 설치된 PC 를 등록합니다. 토큰은 그 PC 에서 명령을 실행할 수 있는 자격이므로
                <span class="font-medium">운영 서버·운영 DB 접근 권한이 있는 PC 에는 설치하지 마세요.</span>
            </p>
        </div>

        <form method="POST" action="{{ route('settings.aiw-agents.store') }}" class="grid gap-3 md:grid-cols-4 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">이름</label>
                <input type="text" name="name" required maxlength="100" class="w-full rounded-lg border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">만료 (일)</label>
                <input type="number" name="expires_days" min="1" max="730" value="{{ config('aiw.token_ttl_days', 90) }}"
                       class="w-full rounded-lg border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">허용 IP (선택)</label>
                <input type="text" name="allowed_ips" placeholder="1.2.3.4, 10.0.0.0/24"
                       class="w-full rounded-lg border-gray-200 text-sm">
            </div>
            <div>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">등록</button>
            </div>
        </form>
    </div>

    {{-- 목록 --}}
    @forelse ($agents as $agent)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-block h-2 w-2 rounded-full {{ $agent->is_online ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        <h3 class="text-sm font-bold text-gray-900">{{ $agent->name }}</h3>
                        <span class="text-xs text-gray-500">{{ $agent->owner?->name }}</span>
                        <span class="text-xs text-gray-400">
                            {{ $agent->last_seen_at ? $agent->last_seen_at->diffForHumans() : '접속 이력 없음' }}
                        </span>
                        <span class="text-xs text-gray-400">매핑 {{ $agent->agent_projects_count }}개</span>
                        @if ($agent->expires_at)
                            <span class="rounded px-1.5 py-0.5 text-[11px] {{ $agent->expires_at->isBefore(now()->addDays(7)) ? 'bg-red-50 text-red-700' : 'text-gray-400' }}">
                                만료 {{ $agent->expires_at->format('Y-m-d') }}
                            </span>
                        @endif
                    </div>
                    @if ($agent->allowed_ips)
                        <p class="mt-1 text-xs text-gray-400">허용 IP: {{ implode(', ', $agent->allowed_ips) }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('settings.aiw-agents.regenerate', $agent) }}"
                          onsubmit="return confirm('기존 토큰이 즉시 무효가 됩니다. 재발급할까요?')">
                        @csrf
                        <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">토큰 재발급</button>
                    </form>
                    <form method="POST" action="{{ route('settings.aiw-agents.destroy', $agent) }}"
                          onsubmit="return confirm('작업 PC 와 관련 지시가 모두 삭제됩니다. 진행할까요?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">삭제</button>
                    </form>
                </div>
            </div>

            {{-- 프로젝트 매핑 --}}
            <div class="mt-4 border-t border-gray-100 pt-3">
                <div class="text-xs font-semibold text-gray-700 mb-1">프로젝트 매핑</div>
                @foreach ($agent->agentProjects as $mapping)
                    <div class="flex items-center gap-2 text-xs text-gray-600 py-1">
                        <span class="font-medium">{{ $mapping->project?->name ?? '삭제된 프로젝트' }}</span>
                        <code class="text-gray-500">{{ $mapping->local_path }}</code>
                        <span class="text-gray-400">{{ $mapping->default_branch ?: '기본 브랜치' }}</span>
                        <form method="POST" action="{{ route('settings.aiw-agents.mappings.destroy', [$agent, $mapping]) }}">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-600">삭제</button>
                        </form>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('settings.aiw-agents.mappings.store', $agent) }}"
                      class="mt-2 grid gap-2 md:grid-cols-4 items-end">
                    @csrf
                    <select name="project_id" required class="rounded-lg border-gray-200 text-xs">
                        @foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                    <input type="text" name="local_path" required placeholder="E:\work\project"
                           class="rounded-lg border-gray-200 text-xs md:col-span-2">
                    <div class="flex gap-2">
                        <input type="text" name="default_branch" placeholder="master" class="flex-1 rounded-lg border-gray-200 text-xs">
                        <button class="rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900">추가</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center text-sm text-gray-400">
            등록된 작업 PC 가 없습니다.
        </div>
    @endforelse
</div>
@endsection
