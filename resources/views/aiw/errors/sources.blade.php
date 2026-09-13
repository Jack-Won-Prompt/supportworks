@extends('layouts.app')

@section('title', '오류 수집 출처')

@section('header-actions')
@endsection

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">관리자 › 오류 수집 출처</span>
@endsection

@section('content')
<div class="space-y-3">

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- 토큰 원문: 이 화면에서 한 번만 보인다. 담당자 토큰과 같은 규칙이다. --}}
    @if (session('aiw_error_token'))
        <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-4" x-data="{ copied: false }">
            <div class="text-sm font-bold text-amber-900 mb-1">수집 토큰</div>
            <p class="text-xs text-amber-800 mb-2">
                이 값은 <span class="font-semibold">지금 한 번만</span> 표시됩니다. 서버에는 해시만 저장되어 다시 볼 수 없습니다.
                운영 사이트의 <code>.env</code> 에 <code>SW_ERROR_TOKEN</code> 으로 넣으세요.
            </p>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ session('aiw_error_token') }}" x-ref="tok"
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
        <h2 class="text-xl font-bold text-gray-900">오류 수집 출처</h2>
        <p class="mt-1 text-sm text-gray-500">
            운영 사이트가 예외를 만나면 이곳으로 보냅니다.
            <span class="font-medium">어느 프로젝트인지는 토큰으로 정합니다</span> — 보내는 쪽이 적어 보내는 값은 쓰지 않습니다.
        </p>
        <p class="mt-1 text-xs text-amber-700">
            이 토큰을 가진 쪽이 그 프로젝트에 오류를 쌓을 수 있고, 그 오류는 작업 지시로 이어집니다. 담당자 토큰과 같은 급으로 다루세요.
        </p>

        <form method="POST" action="{{ route('settings.aiw-errors.store') }}" class="mt-4 grid gap-3 md:grid-cols-3 items-end">
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
                       placeholder="korsafety.co.kr" class="w-full rounded-lg border-gray-200 text-xs">
            </div>
            <div>
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                    토큰 발급
                </button>
            </div>
        </form>
    </div>

    {{-- 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if ($sources->isEmpty())
            <p class="text-sm text-gray-500">등록된 출처가 없습니다.</p>
        @else
            <div class="space-y-2">
                @foreach ($sources as $source)
                    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-100 px-3 py-2">
                        <span class="text-sm font-semibold text-gray-900">{{ $source->name }}</span>
                        <span class="text-xs text-gray-500">{{ $source->project?->name ?? '(삭제된 프로젝트)' }}</span>

                        @if ($source->enabled)
                            <span class="rounded bg-green-50 px-1.5 py-0.5 text-xs text-green-700">켜짐</span>
                        @else
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">꺼짐</span>
                        @endif

                        <span class="text-xs text-gray-400">오류 {{ $source->reports_count }}종</span>

                        {{-- 한 달째 조용하면 사이트가 보내지 않고 있는 것이고, 그것도 알아야 할 사실이다. --}}
                        <span class="text-xs text-gray-400">
                            {{ $source->last_seen_at ? '마지막 '.$source->last_seen_at->diffForHumans() : '받은 적 없음' }}
                        </span>

                        <div class="ml-auto flex gap-1.5">
                            <form method="POST" action="{{ route('settings.aiw-errors.toggle', $source) }}">
                                @csrf
                                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                                    {{ $source->enabled ? '끄기' : '켜기' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('settings.aiw-errors.reissue', $source) }}"
                                  onsubmit="return confirm('기존 토큰이 즉시 무효가 됩니다. 재발급할까요?')">
                                @csrf
                                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">토큰 재발급</button>
                            </form>
                            <form method="POST" action="{{ route('settings.aiw-errors.destroy', $source) }}"
                                  onsubmit="return confirm('이 출처와 쌓인 오류가 모두 삭제됩니다. 진행할까요?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">삭제</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- 붙이는 법. 사이트마다 사람이 다시 알아내지 않도록 여기 적어 둔다. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-900">보내는 쪽 붙이는 법</h3>
        <p class="mt-1 text-xs text-gray-500">
            예외 처리에서 아래 형태로 보냅니다. <span class="font-medium">보내기가 실패해도 사이트가 멈추면 안 됩니다</span> —
            오류 보고 때문에 사이트가 느려지는 것이 원래 오류보다 나쁩니다.
        </p>
<pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100">POST {{ url('/api/aiw/errors') }}
Authorization: Bearer &lt;토큰&gt;
Content-Type: application/json

{
  "level": "error",
  "exception": "RuntimeException",
  "message": "주문 저장 실패",
  "file": "/home/ubuntu/www/korsafety/app/Services/OrderService.php",
  "line": 42,
  "url": "https://korsafety.co.kr/orders/1",
  "trace": "#0 ...",
  "context": { "user_id": 12 }
}</pre>
        <ul class="mt-2 space-y-1 text-xs text-gray-500 list-disc pl-4">
            <li><span class="font-medium">URL 의 토큰은 가려서 보내세요.</span> 오류 기록은 오래 남고 여러 사람이 읽습니다.</li>
            <li>같은 예외·파일·줄은 서버가 한 건으로 묶어 셉니다. 보내는 쪽에서 걸러 낼 필요가 없습니다.</li>
            <li>404 와 봇 스캔은 보내지 않는 편이 낫습니다. 고칠 대상이 아닙니다.</li>
        </ul>
    </div>
</div>
@endsection
