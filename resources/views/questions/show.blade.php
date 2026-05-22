@extends('layouts.app')

@section('title', $question->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $question->project) }}" class="hover:text-indigo-500 transition-colors">{{ $question->project->name }}</a>
<span>›</span>
<a href="{{ route('projects.questions.index', $question->project) }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.qa_title') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ $question->title }}</span>
@endsection

@section('header-actions')
    <a href="{{ route('projects.questions.index', $question->project) }}" class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">← {{ __('common.list') }}</a>
    @if($question->converted_to_issue_id)
    <a href="{{ route('projects.issues.show', [$question->project, $question->converted_to_issue_id]) }}"
       style="padding:7px 14px;font-size:12px;font-weight:500;color:#059669;border:1.5px solid #a7f3d0;border-radius:8px;text-decoration:none;background:#f0fdf4;"
       onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">✓ 이슈 보기</a>
    @elseif(auth()->user()->hasFeature('issues'))
    <button onclick="openConvertModal()"
            style="padding:7px 14px;font-size:12px;font-weight:500;color:#dc2626;border:1.5px solid #fecaca;border-radius:8px;background:#fff;cursor:pointer;"
            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">이슈로 전환</button>
    @endif
    @if(auth()->id() === $question->user_id || auth()->user()->isAdmin())
    <button onclick="openEditModal()" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('common.edit') }}</button>
    @endif
@endsection

@section('content')
<div class="max-w-3xl pt-4 space-y-5">
    <!-- 질문 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-sm font-bold text-indigo-700 flex-shrink-0">
                {{ mb_substr($question->user->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-medium text-gray-900">{{ $question->user->name }}</span>
                    <span class="text-xs text-gray-400">{{ $question->created_at->diffForHumans() }}</span>
                    <span class="px-2 py-0.5 text-xs rounded-full
                        {{ $question->status === 'open' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $question->status === 'answered' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $question->status === 'closed' ? 'bg-gray-100 text-gray-500' : '' }}">
                        {{ $question->status_label }}
                    </span>
                    @if($question->is_private)
                    <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full">{{ __('projects.private_badge') }}</span>
                    @endif
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-3" id="q-title-display">{{ $question->title }}</h2>
                <p class="text-sm text-gray-700 whitespace-pre-line" id="q-content-display">{{ $question->content }}</p>
            </div>
        </div>
    </div>

    <!-- 답변 목록 -->
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">{{ __('projects.answer_count', ['count' => $question->answers->count()]) }}</h3>

        @foreach($question->answers as $answer)
        <div class="bg-white rounded-xl shadow-sm border {{ $answer->is_accepted ? 'border-green-200 bg-green-50' : 'border-gray-100' }} p-6">
            @if($answer->is_accepted)
            <div class="flex items-center gap-1.5 mb-3 text-green-600 text-sm font-medium">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('projects.accepted_answer') }}
            </div>
            @endif

            <div class="flex items-start gap-3">
                <div class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0">
                    {{ mb_substr($answer->user->name, 0, 1) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-medium text-sm text-gray-900">{{ $answer->user->name }}</span>
                        <span class="text-xs text-gray-400">{{ $answer->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-line mb-3">{{ $answer->content }}</p>

                    <div class="flex items-center gap-3">
                        @if(!$answer->is_accepted && (auth()->id() === $question->user_id || auth()->user()->isAdmin()))
                        <form method="POST" action="{{ route('answers.accept', $answer) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs text-green-600 border border-green-300 px-3 py-1 rounded-lg hover:bg-green-50">{{ __('projects.accept_btn') }}</button>
                        </form>
                        @endif
                        @if(auth()->id() === $answer->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('answers.destroy', $answer) }}"
                              onsubmit="return confirm('{{ __('projects.delete_answer_confirm') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600">{{ __('common.delete') }}</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- 답변 작성 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('projects.answer_write') }}</h3>
        <form method="POST" action="{{ route('answers.store', $question) }}" class="space-y-3">
            @csrf
            <textarea name="content" rows="4" required
                      placeholder="{{ __('projects.answer_placeholder') }}"
                      class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('projects.answer_register') }}</button>
        </form>
    </div>
</div>

@if(auth()->id() === $question->user_id || auth()->user()->isAdmin())
{{-- 수정 모달 --}}
<div id="eq-overlay" onclick="closeEditModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="eq-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:520px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $question->project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('projects.question_edit_modal') }}</h3>
        </div>
        <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="eq-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:12px;">
        @csrf
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_title_label') }} <span style="color:#ef4444;">*</span></label>
            <input type="text" id="eq-title" name="title" required
                   value="{{ $question->title }}"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('projects.question_content') }} <span style="color:#ef4444;">*</span></label>
            <textarea id="eq-content" name="content" rows="8" required
                      style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">{{ $question->content }}</textarea>
        </div>

        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" id="eq-private" name="is_private" value="1"
                   {{ $question->is_private ? 'checked' : '' }}
                   style="width:15px;height:15px;accent-color:var(--t500);">
            <span style="font-size:13px;color:#374151;">{{ __('projects.private_question') }}</span>
        </label>

        <div id="eq-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:4px;">
            <button type="submit" id="eq-submit"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                {{ __('common.save') }}
            </button>
            <button type="button" onclick="closeEditModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
            <button type="button" onclick="deleteQuestion()"
                    style="padding:9px 16px;font-size:13px;font-weight:600;color:#ef4444;background:#fff;border:1.5px solid #fecaca;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                {{ __('common.delete') }}
            </button>
        </div>
    </form>
</div>
@endif

{{-- 이슈 전환 모달 --}}
@if(!$question->converted_to_issue_id && auth()->user()->hasFeature('issues'))
<div id="convert-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:10200;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="closeConvertModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 6px;">이슈로 전환</h3>
        <p style="font-size:12px;color:#9ca3af;margin:0 0 20px;">이 Q&A를 이슈로 등록합니다.</p>
        <form id="convert-form" onsubmit="submitConvert(event)">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">제목 *</label>
                    <input id="cv-title" name="title" value="{{ $question->title }}" required
                           style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">설명</label>
                    <textarea id="cv-desc" name="description" rows="3"
                              style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">{{ $question->content }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">분류 *</label>
                        <select name="category" required style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::CATEGORY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $v==='문의' ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">우선순위 *</label>
                        <select name="priority" required style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::PRIORITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $v==='medium' ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <input type="hidden" name="question_id" value="{{ $question->id }}">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeConvertModal()"
                        style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">취소</button>
                <button type="submit" id="cv-btn"
                        style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:8px;cursor:pointer;">이슈 등록</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const CONVERT_URL = '{{ route('projects.issues.convert-from-question', $question->project) }}';
const UPDATE_URL  = '{{ route('questions.update', $question) }}';
const DESTROY_URL = '{{ route('questions.destroy', $question) }}';
const INDEX_URL   = '{{ route('projects.questions.index', $question->project) }}';
const STR = {
    saving:      '{{ __("projects.saving") }}',
    save:        '{{ __("common.save") }}',
    saveFailed:  '{{ __("projects.save_failed") }}',
    netError:    '{{ __("projects.network_error") }}',
    deleteFailed:'{{ __("projects.delete_failed") }}',
    deleteConfirm: '{{ __("projects.delete_question_confirm") }}',
};

async function openEditModal() {
    document.getElementById('eq-error').style.display = 'none';
    document.getElementById('eq-modal').style.display = 'block';
    document.getElementById('eq-overlay').style.display = 'block';
}

async function closeEditModal() {
    document.getElementById('eq-modal').style.display = 'none';
    document.getElementById('eq-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });

document.getElementById('eq-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('eq-submit');
    const errEl = document.getElementById('eq-error');
    btn.disabled = true; btn.textContent = STR.saving;
    errEl.style.display = 'none';

    const fd = new FormData(this);
    fd.append('_method', 'PATCH');

    try {
        const res = await fetch(UPDATE_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        if (res.ok) {
            closeEditModal();
            // Update title/content in-page immediately
            const title = document.getElementById('eq-title').value;
            const content = document.getElementById('eq-content').value;
            const titleEl = document.getElementById('q-title-display');
            const contentEl = document.getElementById('q-content-display');
            if (titleEl) titleEl.textContent = title;
            if (contentEl) contentEl.textContent = content;
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
        btn.disabled = false; btn.textContent = STR.save;
    }
});

async function deleteQuestion() {
    if (!await __confirm(STR.deleteConfirm)) return;
    try {
        const res = await fetch(DESTROY_URL, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) {
            location.href = INDEX_URL;
        } else {
            alert(STR.deleteFailed);
        }
    } catch {
        alert(STR.netError);
    }
}

async function openConvertModal() {
    const m = document.getElementById('convert-modal');
    if (m) m.style.display = 'flex';
}
async function closeConvertModal() {
    const m = document.getElementById('convert-modal');
    if (m) m.style.display = 'none';
}

async function submitConvert(e) {
    e.preventDefault();
    const btn = document.getElementById('cv-btn');
    btn.disabled = true; btn.textContent = '등록 중...';
    const fd = new FormData(e.target);
    const res = await fetch(CONVERT_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: fd,
    });
    const data = await res.json();
    if (data.ok) {
        window.location.href = data.url;
    } else {
        alert(data.message || '이슈 등록 실패');
        btn.disabled = false; btn.textContent = '이슈 등록';
    }
}

const cvModal = document.getElementById('convert-modal');
if (cvModal) cvModal.addEventListener('click', async function(e) { if (e.target === this) closeConvertModal(); });
</script>
@endsection
