@extends('layouts.app')

@section('title', $project->name . ' - ' . __('projects.qa_title'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('projects.qa_title') }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    <button onclick="openQuestionModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('projects.question_register') }}</button>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'qa'])
<div class="pt-4 space-y-3">
    @forelse($questions as $question)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start gap-4">
            <div class="flex flex-col items-center gap-1 text-center w-12 flex-shrink-0">
                <div class="text-lg font-bold text-gray-700">{{ $question->answers_count }}</div>
                <div class="text-xs text-gray-400">{{ __('projects.answers_count') }}</div>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                        {{ $question->status === 'open' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $question->status === 'answered' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $question->status === 'closed' ? 'bg-gray-100 text-gray-500' : '' }}">
                        {{ $question->status_label }}
                    </span>
                    @if($question->is_private)
                    <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">{{ __('projects.private_badge') }}</span>
                    @endif
                </div>
                <a href="{{ route('questions.show', $question) }}" class="text-base font-semibold text-gray-900 hover:text-indigo-600">
                    {{ $question->title }}
                </a>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $question->content }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                    <span>{{ $question->user->name }}</span>
                    <span>{{ $question->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-gray-400 text-sm">{{ __('projects.no_questions_registered') }}</p>
        <button onclick="openQuestionModal()" class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">{{ __('projects.first_question') }}</button>
    </div>
    @endforelse

    <div>{{ $questions->links() }}</div>
</div>

{{-- 질문 등록 모달 --}}
<div id="q-overlay" onclick="closeQuestionModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="q-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:520px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('projects.question_register_modal') }}</h3>
        </div>
        <button onclick="closeQuestionModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="q-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:12px;">
        @csrf
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_title_label') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="q-title" name="title" required placeholder="{{ __('projects.question_title_placeholder') }}"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_content') }} <span style="color:#ef4444;">*</span></label>
            <textarea id="q-content" name="content" rows="6" required placeholder="{{ __('projects.question_content_placeholder') }}"
                      style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
        </div>

        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="q-private" name="is_private" value="1"
                   style="width:15px;height:15px;accent-color:var(--t500);">
            <span style="font-size:13px;color:#374151;">{{ __('projects.private_question') }}</span>
        </label>

        <div id="q-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:4px;">
            <button type="submit" id="q-submit"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                {{ __('common.register') }}
            </button>
            <button type="button" onclick="closeQuestionModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const STORE_URL = '{{ route('projects.questions.store', $project) }}';
const STR = {
    saving:      '{{ __("projects.saving") }}',
    register:    '{{ __("common.register") }}',
    saveFailed:  '{{ __("projects.save_failed") }}',
    netError:    '{{ __("projects.network_error") }}',
};

function openQuestionModal() {
    document.getElementById('q-form').reset();
    document.getElementById('q-error').style.display = 'none';
    document.getElementById('q-modal').style.display = 'block';
    document.getElementById('q-overlay').style.display = 'block';
    setTimeout(() => document.getElementById('q-title').focus(), 50);
}

function closeQuestionModal() {
    document.getElementById('q-modal').style.display = 'none';
    document.getElementById('q-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQuestionModal(); });

document.getElementById('q-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('q-submit');
    const errEl = document.getElementById('q-error');
    btn.disabled = true; btn.textContent = STR.saving;
    errEl.style.display = 'none';

    const fd = new FormData(this);
    try {
        const res = await fetch(STORE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        if (res.ok) {
            closeQuestionModal();
            location.reload();
        } else {
            const data = await res.json().catch(() => ({}));
            const msgs = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : (data.message || STR.saveFailed);
            errEl.textContent = msgs;
            errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = STR.netError;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = STR.register;
    }
});
</script>
@endsection
