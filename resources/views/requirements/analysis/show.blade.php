@extends('layouts.app')

@section('title', __('requirements.analysis_result'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- 헤더 --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('projects.requirements.index', $project) }}"
               class="text-sm text-blue-600 hover:underline">&larr; {{ __('requirements.requirements_list') }}</a>
            <h1 class="text-xl font-bold mt-1">{{ __('requirements.analysis_result') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @php
                $statusColor = match($session->status) {
                    'pending','processing' => 'yellow',
                    'review'               => 'blue',
                    'approved'             => 'green',
                    'rejected'             => 'gray',
                    'failed'               => 'red',
                    default                => 'gray',
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-medium
                bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">
                {{ $session->status_label }}
            </span>
        </div>
    </div>

    {{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

    {{-- 메타 정보 --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">{{ __('requirements.analysis_creator') }}</p>
            <p class="font-medium">{{ $session->createdBy?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">{{ __('requirements.analysis_model') }}</p>
            <p class="font-medium">{{ $session->llm_model ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">{{ __('requirements.analysis_token') }}</p>
            <p class="font-medium">
                @if($session->token_input)
                    {{ number_format($session->token_input + $session->token_output) }}
                    <span class="text-gray-400 text-xs">(in: {{ number_format($session->token_input) }})</span>
                @else -
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">{{ __('requirements.analysis_cost') }}</p>
            <p class="font-medium">{{ $session->cost_estimated ? '$' . $session->cost_estimated : '-' }}</p>
        </div>
    </div>

    {{-- 업로드 파일 목록 --}}
    @if($session->files->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-gray-700 mb-2">{{ __('requirements.analysis_uploaded_files') }}</p>
        <ul class="space-y-1">
            @foreach($session->files as $file)
            <li class="flex items-center gap-2 text-xs text-gray-600">
                @php
                    $eColor = match($file->extraction_status) {
                        'done'    => 'text-green-600',
                        'failed'  => 'text-red-500',
                        'pending' => 'text-yellow-600',
                        default   => 'text-gray-400',
                    };
                @endphp
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="flex-1">{{ $file->original_filename }}</span>
                <span class="text-gray-400">{{ $file->size_human }}</span>
                <span class="{{ $eColor }} font-medium">
                    {{ match($file->extraction_status) { 'done'=>__('requirements.extraction_done'), 'failed'=>__('requirements.extraction_failed'), 'pending'=>__('requirements.extraction_pending'), default=>__('requirements.extraction_processing') } }}
                </span>
                @if($file->extraction_error)
                    <span class="text-red-400 truncate max-w-xs" title="{{ $file->extraction_error }}">
                        {{ Str::limit($file->extraction_error, 40) }}
                    </span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- 상태별 메인 영역 --}}

    @if(in_array($session->status, ['pending', 'processing']))
    {{-- 분석 중 --}}
    <div id="processing-panel" class="bg-white border border-gray-200 rounded-lg p-10 text-center">
        <div class="flex justify-center mb-4">
            <svg class="animate-spin w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
        <p class="text-lg font-semibold text-gray-700">{{ __('requirements.analysis_processing_title') }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ __('requirements.analysis_processing_hint') }}</p>
    </div>
    @endif

    @if($session->status === 'review')
    {{-- 검토 영역 --}}
    <div id="review-panel">
        @if($session->summary)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-xs font-medium text-blue-700 mb-1">{{ __('requirements.analysis_summary') }}</p>
            <p class="text-sm text-gray-700">{{ $session->summary }}</p>
        </div>
        @endif

        @if($session->warnings)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-xs font-medium text-yellow-700 mb-1">{{ __('requirements.analysis_warnings') }}</p>
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                @foreach($session->warnings as $w)
                    <li>{{ $w }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php $candidates = $session->candidates; @endphp

        <form method="POST" action="{{ route('projects.requirements.analysis.approve', [$project, $session]) }}">
            @csrf

            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-700">
                    {{ __('requirements.analysis_candidates_count') }} <span class="text-blue-600 font-bold">{{ count($candidates) }}</span>{{ __('requirements.analysis_candidates_unit') }}
                </p>
                <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="checkbox" id="select-all" class="rounded">
                    <span class="text-gray-600">{{ __('requirements.analysis_select_all') }}</span>
                </label>
            </div>

            <div class="space-y-3 mb-6">
                @foreach($candidates as $idx => $c)
                @php
                    $catLabels = ['functional'=>__('requirements.cat_functional'), 'non_functional'=>__('requirements.cat_non_functional'), 'constraint'=>__('requirements.cat_constraint'), 'ui_ux'=>__('requirements.cat_ui_ux'),
                                  'integration'=>__('requirements.cat_integration'), 'performance'=>__('requirements.cat_performance'), 'security'=>__('requirements.cat_security'), 'other'=>__('requirements.cat_other')];
                    $priColors = ['critical'=>'red', 'high'=>'orange', 'medium'=>'yellow', 'low'=>'green'];
                    $priColor  = $priColors[$c['priority'] ?? 'medium'] ?? 'gray';
                    $confidence = round(($c['confidence'] ?? 0.8) * 100);
                @endphp
                <label class="block bg-white border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-400 transition-colors has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="selected[]" value="{{ $idx }}"
                               class="mt-0.5 rounded candidate-check" checked>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                    bg-{{ $priColor }}-100 text-{{ $priColor }}-700">
                                    {{ ucfirst($c['priority'] ?? 'medium') }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                                    {{ $catLabels[$c['category'] ?? 'other'] ?? __('requirements.cat_other') }}
                                </span>
                                <span class="text-xs text-gray-400">{{ __('requirements.analysis_confidence') }} {{ $confidence }}%</span>
                                @if(!empty($c['source_ref']))
                                    <span class="text-xs text-gray-400">{{ __('requirements.analysis_source') }}: {{ $c['source_ref'] }}</span>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-gray-900 mb-1">{{ $c['title'] }}</p>
                            @if(!empty($c['description']))
                                <p class="text-xs text-gray-500 leading-relaxed">{{ $c['description'] }}</p>
                            @endif
                            @if(!empty($c['tags']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($c['tags'] as $tag)
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 text-xs">#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </label>
                @endforeach
            </div>

            <div class="flex justify-between items-center pt-4 border-t">
                <button type="button" onclick="submitReject()"
                        class="px-4 py-2 text-sm border border-gray-300 rounded text-gray-600 hover:bg-gray-50">
                    {{ __('requirements.analysis_reject') }}
                </button>
                <button type="submit"
                        class="px-6 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                    {{ __('requirements.analysis_approve') }}
                </button>
            </div>
        </form>

        {{-- 거부 폼은 approve 폼 밖에 위치 --}}
        <form id="reject-form" method="POST"
              action="{{ route('projects.requirements.analysis.reject', [$project, $session]) }}"
              style="display:none">
            @csrf
        </form>
    </div>
    @endif

    @if($session->status === 'failed')
    {{-- 실패 --}}
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-700 font-medium mb-2">{{ __('requirements.analysis_failed_title') }}</p>
        <p class="text-sm text-red-600 mb-4">{{ $session->error_message }}</p>
        <form method="POST"
              action="{{ route('projects.requirements.analysis.retry', [$project, $session]) }}">
            @csrf
            <button type="submit"
                    class="px-5 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                {{ __('requirements.analysis_retry') }}
            </button>
        </form>
    </div>
    @endif

    @if(in_array($session->status, ['approved', 'rejected']))
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-sm text-gray-500">
        @if($session->status === 'approved')
        <div class="flex items-center justify-between flex-wrap gap-3">
            <span>{{ __('requirements.analysis_registered') }}</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.requirements.index', $project) }}"
                   class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50 text-gray-700">
                    {{ __('requirements.analysis_view_list') }}
                </a>
                @php
                    $sessionReqs = \App\Models\Requirement::where('source_session_id', $session->id)->pluck('id')->all();
                @endphp
                @if(!empty($sessionReqs))
                <button onclick="openAnalysisApplyModal({{ json_encode($sessionReqs) }})"
                        style="padding:7px 14px;font-size:13px;font-weight:600;color:#7c3aed;border:1.5px solid #ddd6fe;border-radius:8px;background:#faf5ff;cursor:pointer;"
                        onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    {{ __('requirements.analysis_apply_to_plan') }}
                </button>
                @endif
            </div>
        </div>
        @else
            {{ __('requirements.analysis_rejected') }}
        @endif
    </div>
    @endif

</div>

@if(in_array($session->status, ['pending', 'processing']))
<script>
(async function () {
    const sessionId = {{ $session->id }};
    const showUrl   = '{{ route('projects.requirements.analysis.show', [$project, $session]) }}';

    if (typeof Echo === 'undefined') return;

    Echo.private('analysis-session.' + sessionId)
        .listen('.status.updated', data => {
            if (['review', 'approved', 'rejected', 'failed'].includes(data.status)) {
                window.location.reload();
            }
        });

    // 폴백: 10초마다 새로고침 시도
    setTimeout(() => window.location.reload(), 15000);
})();
</script>
@endif

<script>
async function submitReject() {
    if (await __confirm(@json(__('requirements.js_confirm_reject')))) {
        document.getElementById('reject-form').submit();
    }
}

// 전체 선택
const selectAll = document.getElementById('select-all');
if (selectAll) {
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.candidate-check').forEach(c => {
            c.checked = selectAll.checked;
        });
    });
}

// ── 분석 결과 → 기획서 적용 미니 모달 ──────────────────────────
let _analysisReqIds = [];
const CSRF2       = document.querySelector('meta[name="csrf-token"]').content;
const PLANS_URL2  = '{{ route('projects.plan-applications.plans', $project) }}';
const PREVIEW_URL2= '{{ route('projects.plan-applications.preview', $project) }}';
const APPLY_BASE2 = '{{ url("projects/{$project->id}/planning") }}';
let _planData2 = [];

async function openAnalysisApplyModal(reqIds) {
    _analysisReqIds = reqIds;

    // load plans
    const planSel = document.getElementById('aa-plan-sel');
    planSel.innerHTML = '<option value="">' + @json(__('requirements.js_preview_loading')) + '</option>';
    try {
        const res = await fetch(PLANS_URL2, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF2 } });
        const data = await res.json();
        _planData2 = data.plans || [];
        planSel.innerHTML = '<option value="">' + @json(__('requirements.apply_plan_placeholder')) + '</option>' +
            _planData2.map(p => `<option value="${p.id}">${p.title} (v${p.version})</option>`).join('');
    } catch {
        planSel.innerHTML = '<option value="">' + @json(__('requirements.js_load_failed')) + '</option>';
    }

    document.getElementById('aa-req-count').textContent = reqIds.length;
    document.getElementById('aa-error').style.display = 'none';
    document.getElementById('aa-modal').style.display = 'block';
    document.getElementById('aa-overlay').style.display = 'block';
}

async function closeAaModal() {
    document.getElementById('aa-modal').style.display = 'none';
    document.getElementById('aa-overlay').style.display = 'none';
}

async function doAaApply() {
    const planId = document.getElementById('aa-plan-sel').value;
    if (!planId) { alert(@json(__('requirements.js_select_plan'))); return; }

    const btn = document.getElementById('aa-btn');
    btn.disabled = true; btn.textContent = @json(__('requirements.js_applying'));
    document.getElementById('aa-error').style.display = 'none';

    try {
        const res = await fetch(`${APPLY_BASE2}/${planId}/apply-requirements`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF2, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ requirement_ids: _analysisReqIds.map(Number), position: 'end' }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || @json(__('requirements.js_apply_failed')));

        closeAaModal();
        const plan = _planData2.find(p => String(p.id) === planId);
        if (plan) {
            window.location.href = `${APPLY_BASE2}/${planId}`;
        }
    } catch (e) {
        const err = document.getElementById('aa-error');
        err.textContent = e.message;
        err.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = @json(__('requirements.apply_btn'));
    }
}
</script>

{{-- 미니 적용 모달 --}}
<div id="aa-overlay" onclick="closeAaModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="aa-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:440px;max-width:calc(100vw - 32px);padding:22px;">
    <div class="flex items-center justify-between mb-4">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('requirements.analysis_apply_modal_title') }}</h3>
        <button onclick="closeAaModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;line-height:1;">&times;</button>
    </div>
    <p style="font-size:13px;color:#6b7280;margin:0 0 14px;">
        {!! __('requirements.analysis_apply_desc', ['n' => '<strong id="aa-req-count">0</strong>']) !!}
    </p>
    <div class="mb-4">
        <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('requirements.analysis_target_plan') }}</label>
        <select id="aa-plan-sel" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;"></select>
    </div>
    <div id="aa-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:12px;color:#dc2626;margin-bottom:10px;"></div>
    <div style="display:flex;gap:8px;">
        <button id="aa-btn" onclick="doAaApply()"
                style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:9px;cursor:pointer;"
                onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">{{ __('requirements.apply_btn') }}</button>
        <button onclick="closeAaModal()"
                style="padding:9px 18px;font-size:13px;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;">{{ __('common.cancel') }}</button>
    </div>
</div>
@endsection
