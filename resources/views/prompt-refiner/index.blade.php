@extends('layouts.app')

@section('title', '프롬프트 정제하기')

@push('styles')
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
    .pr-fade { animation: fadeIn .25s ease; }

    .pr-mode-btn {
        flex: 1;
        padding: 9px 0;
        border: 1.5px solid #ddd6fe;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        color: #7c3aed;
        background: #fff;
        cursor: pointer;
        transition: all .13s;
        text-align: center;
    }
    .pr-mode-btn.active {
        background: linear-gradient(135deg, var(--t100), var(--t200));
        border-color: var(--t400);
        color: var(--tText);
    }
    .pr-mode-btn:hover:not(.active) { background: #f5f3ff; }

    .q-suggestion-btn {
        padding: 5px 10px;
        border: 1px solid #ddd6fe;
        border-radius: 6px;
        font-size: 12px;
        color: #6d5ce7;
        background: #f5f3ff;
        cursor: pointer;
        transition: all .12s;
        line-height: 1.4;
    }
    .q-suggestion-btn:hover { background: #ede9fe; border-color: #a78bfa; }

    .hist-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid #ede9fe;
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .13s, background .13s;
        background: #fff;
    }
    .hist-item:hover { border-color: #a78bfa; background: #faf8ff; }
</style>
@endpush

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px 24px 48px;">

    {{-- 헤더 --}}
    <div style="margin-bottom:24px;">
        <h1 style="font-size:20px;font-weight:800;color:#1e1b2e;margin:0 0 4px;">프롬프트 정제하기</h1>
        <p style="font-size:13.5px;color:#71717a;margin:0;">자연어로 입력하면 웍스에 바로 사용할 수 있는 구조화된 프롬프트로 정제해드립니다.</p>
    </div>

    {{-- 입력 카드 --}}
    <div id="input-card" style="background:#fff;border:1.5px solid #ede9fe;border-radius:14px;padding:20px;">

        {{-- 모드 선택 --}}
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;font-weight:700;color:#6d5ce7;letter-spacing:.04em;text-transform:uppercase;display:block;margin-bottom:8px;">모드 선택</label>
            <div style="display:flex;gap:8px;">
                <button class="pr-mode-btn active" id="mode-btn-general" onclick="promptRefiner.setMode('general')">
                    일반
                </button>
                <button class="pr-mode-btn" id="mode-btn-project" onclick="promptRefiner.setMode('project')">
                    프로젝트 연계
                </button>
            </div>
        </div>

        {{-- 프로젝트 선택 (project 모드일 때만) --}}
        <div id="project-select-area" style="display:none;margin-bottom:16px;">
            <label for="project-select" style="font-size:12px;font-weight:700;color:#6d5ce7;letter-spacing:.04em;text-transform:uppercase;display:block;margin-bottom:8px;">프로젝트</label>
            <select id="project-select"
                onchange="promptRefiner.onProjectChange()"
                style="width:100%;border:1.5px solid #ddd6fe;border-radius:9px;padding:9px 12px;font-size:13.5px;color:#3f3f46;background:#faf8ff;outline:none;cursor:pointer;">
                <option value="">프로젝트를 선택하세요</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Task 선택 (project 모드 + 프로젝트 선택 시) --}}
        <div id="task-select-area" style="display:none;margin-bottom:16px;">
            <label for="task-select" style="font-size:12px;font-weight:700;color:#6d5ce7;letter-spacing:.04em;text-transform:uppercase;display:block;margin-bottom:8px;">
                Task
                <span style="font-weight:400;color:#a1a1aa;text-transform:none;font-size:11px;">(선택 시 해당 Task의 이력을 우선 참고합니다)</span>
            </label>
            <select id="task-select"
                onchange="promptRefiner.onTaskChange()"
                style="width:100%;border:1.5px solid #ddd6fe;border-radius:9px;padding:9px 12px;font-size:13.5px;color:#3f3f46;background:#faf8ff;outline:none;cursor:pointer;">
                <option value="">전체 (Task 미선택)</option>
            </select>
            <div id="task-loading-indicator" style="display:none;margin-top:6px;font-size:12px;color:#a78bfa;">
                Task 목록 로딩 중...
            </div>
            {{-- 컨텍스트 강도 표시 --}}
            <div id="context-strength-area" style="display:none;margin-top:8px;align-items:center;gap:6px;">
                <span id="context-dot" style="width:8px;height:8px;border-radius:50%;background:#d1d5db;display:inline-block;flex-shrink:0;"></span>
                <span id="context-strength-label" style="font-size:11.5px;color:#71717a;"></span>
            </div>
        </div>

        {{-- 사용자 입력 --}}
        <div style="margin-bottom:16px;">
            <label for="user-input" style="font-size:12px;font-weight:700;color:#6d5ce7;letter-spacing:.04em;text-transform:uppercase;display:block;margin-bottom:8px;">
                요청 내용
                <span id="char-count" style="font-weight:400;color:#a1a1aa;margin-left:6px;text-transform:none;font-size:11px;"></span>
            </label>
            <textarea id="user-input"
                rows="5"
                maxlength="5000"
                placeholder="예: 로그인 API 만들어줘. JWT 사용하고, 비밀번호는 BCrypt로 해싱해줘."
                oninput="promptRefiner.updateCharCount()"
                style="width:100%;border:1.5px solid #ddd6fe;border-radius:9px;padding:11px 14px;font-size:13.5px;color:#1e1b2e;background:#faf8ff;resize:vertical;outline:none;transition:border-color .13s;line-height:1.6;font-family:inherit;box-sizing:border-box;"
                onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#ddd6fe'"></textarea>
        </div>

        {{-- 에러 메시지 --}}
        <div id="error-msg" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;margin-bottom:14px;"></div>

        {{-- 제출 버튼 --}}
        <div style="display:flex;justify-content:flex-end;">
            <button id="refine-btn" onclick="promptRefiner.submit()"
                style="display:flex;align-items:center;gap:8px;padding:10px 22px;background:linear-gradient(135deg,var(--t600),var(--t700));color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .15s;box-shadow:0 4px 14px rgba(109,92,231,.25);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                <span id="refine-btn-text">정제하기</span>
            </button>
        </div>
    </div>

    {{-- 로딩 --}}
    <div id="loading-area" style="display:none;margin-top:16px;padding:24px;text-align:center;background:#fff;border:1.5px solid #ede9fe;border-radius:14px;">
        <div style="display:inline-block;width:28px;height:28px;border:3px solid #ddd6fe;border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite;margin-bottom:12px;"></div>
        <p style="font-size:13.5px;color:#7c3aed;font-weight:600;margin:0;">웍스가 분석 중입니다...</p>
    </div>

    {{-- 명확화 질문 카드 --}}
    @include('prompt-refiner.partials.clarification-card')

    {{-- 결과 카드 --}}
    @include('prompt-refiner.partials.result-card')

    {{-- 이력 목록 --}}
    @include('prompt-refiner.partials.history-list')

</div>
@endsection

@push('scripts')
<script>
    window.promptRefinerRoutes = {
        refine:           '{{ route("prompt-refiner.refine") }}',
        history:          '{{ route("prompt-refiner.history") }}',
        historyShow:      '{{ url("/prompt-refiner/history") }}',
        projectTasksBase: '{{ url("/prompt-refiner/projects") }}',
        csrfToken:        '{{ csrf_token() }}',
    };
</script>
<script src="{{ asset('js/prompt-refiner.js') }}?v={{ filemtime(public_path('js/prompt-refiner.js')) }}"></script>
@endpush
