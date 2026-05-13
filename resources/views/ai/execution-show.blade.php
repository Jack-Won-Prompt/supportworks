@extends('layouts.app')
@section('title', __('ai.exec_show_title'))

@push('styles')
<style>
.es-section { background:#fff; border:1.5px solid #e8e3ff; border-radius:12px; padding:20px; margin-bottom:16px; }
.es-section h3 { font-size:13px; font-weight:700; color:#7c3aed; margin:0 0 12px; display:flex; align-items:center; gap:6px; }
.es-label { font-size:11px; font-weight:600; color:#94a3b8; margin-bottom:4px; }
.es-value { font-size:13px; color:#1e293b; line-height:1.6; }
.es-code-block { position:relative; background:#1e1b2e; border-radius:10px; padding:14px 14px 14px 16px; overflow-x:auto; }
.es-code-block pre { margin:0; color:#e9d5ff; font-family:'Fira Code','Consolas',monospace; font-size:12px; line-height:1.6; white-space:pre-wrap; word-break:break-all; }
.es-copy-btn { position:absolute; top:10px; right:10px; background:rgba(124,58,237,.3); color:#e9d5ff; border:none; border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; transition:background .15s; }
.es-copy-btn:hover { background:rgba(124,58,237,.55); }
.es-tab-bar { display:flex; gap:4px; margin-bottom:12px; flex-wrap:wrap; }
.es-tab { padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #e8e3ff; background:#fff; color:#64748b; transition:all .15s; }
.es-tab.active { background:#ede9fe; color:#7c3aed; border-color:#c4b5fd; }
.es-tab-panel { display:none; }
.es-tab-panel.active { display:block; }
.es-row2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.es-file-item { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#f8f7ff; border-radius:8px; }
.es-file-icon { width:32px; height:32px; border-radius:7px; background:linear-gradient(135deg,#ede9fe,#ddd6fe); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
</style>
@endpush

@section('content')
<div style="max-width:900px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <a href="{{ route('ai.executions.index') }}" style="color:#7c3aed;font-size:12px;text-decoration:none;display:flex;align-items:center;gap:4px;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('ai.exec_back') }}
        </a>
        <span style="color:#e2e8f0;">›</span>
        <span style="font-size:13px;color:#1e293b;font-weight:600;">#{{ $execution->id }}</span>
        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $execution->status==='completed'?'#dcfce7':'#fee2e2' }};color:{{ $execution->status==='completed'?'#16a34a':'#dc2626' }};">
            {{ $execution->status === 'completed' ? __('ai.exec_status_completed') : __('ai.exec_status_failed') }}
        </span>
        <span style="margin-left:auto;font-size:12px;color:#94a3b8;">{{ $execution->created_at->format(__('ai.php_date_format')) }}</span>
    </div>

    {{-- 입력 --}}
    <div class="es-section">
        <h3>
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            {{ __('ai.exec_input_section') }}
        </h3>
        <div class="es-value" style="white-space:pre-wrap;">{{ $execution->raw_input }}</div>
        @if($execution->project)
        <div style="margin-top:8px;"><span style="font-size:11px;background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:6px;font-weight:600;">{{ $execution->project->name }}</span></div>
        @endif
    </div>

    {{-- 정제된 프롬프트 --}}
    @if($execution->refined_prompt)
    <div class="es-section">
        <h3>
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            {{ __('ai.exec_refined_prompt') }}
        </h3>
        @php $rp = $execution->refined_prompt; @endphp
        <div class="es-row2" style="margin-bottom:12px;">
            @if(!empty($rp['name']))<div><div class="es-label">{{ __('ai.exec_prompt_name') }}</div><div class="es-value">{{ $rp['name'] }}</div></div>@endif
            @if(!empty($rp['category']))<div><div class="es-label">{{ __('ai.exec_category') }}</div><div class="es-value">{{ $rp['category'] }}{{ !empty($rp['type']) ? ' · '.$rp['type'] : '' }}</div></div>@endif
            @if(!empty($rp['purpose']))<div><div class="es-label">{{ __('ai.exec_purpose') }}</div><div class="es-value">{{ $rp['purpose'] }}</div></div>@endif
            @if(!empty($rp['ai_role']))<div><div class="es-label">{{ __('ai.exec_ai_role') }}</div><div class="es-value">{{ $rp['ai_role'] }}</div></div>@endif
        </div>
        @if(!empty($rp['final_prompt']))
        <div class="es-label">{{ __('ai.exec_final_prompt') }}</div>
        <div class="es-code-block" style="margin-top:4px;">
            <pre id="refined-prompt-code">{{ $rp['final_prompt'] }}</pre>
            <button class="es-copy-btn" onclick="copyText('refined-prompt-code', this)">{{ __('ai.exec_copy_btn') }}</button>
        </div>
        @endif
    </div>
    @elseif($execution->prompt)
    <div class="es-section">
        <h3>{{ __('ai.exec_used_prompt') }}</h3>
        <div class="es-value"><strong>{{ $execution->prompt->name }}</strong></div>
        @if($execution->prompt->category)<div style="margin-top:4px;font-size:12px;color:#94a3b8;">{{ $execution->prompt->category->name }}</div>@endif
    </div>
    @endif

    {{-- 웍스 응답 --}}
    @if($execution->ai_response || $execution->html_output || $execution->css_output || $execution->js_output)
    <div class="es-section">
        <h3>{{ __('ai.exec_ai_response') }}</h3>
        <div class="es-tab-bar">
            @if($execution->ai_response) <div class="es-tab active" onclick="switchTab(this,'tab-text')">{{ __('ai.exec_response_tab') }}</div> @endif
            @if($execution->html_output) <div class="es-tab {{ !$execution->ai_response?'active':'' }}" onclick="switchTab(this,'tab-html')">HTML</div> @endif
            @if($execution->css_output)  <div class="es-tab" onclick="switchTab(this,'tab-css')">CSS</div> @endif
            @if($execution->js_output)   <div class="es-tab" onclick="switchTab(this,'tab-js')">JS</div> @endif
            @if($execution->html_output && $execution->css_output) <div class="es-tab" onclick="switchTab(this,'tab-preview')">{{ __('ai.exec_preview_tab') }}</div> @endif
        </div>
        @if($execution->ai_response)
        <div class="es-tab-panel active" id="tab-text">
            <div class="es-value" style="white-space:pre-wrap;">{{ $execution->ai_response }}</div>
        </div>
        @endif
        @if($execution->html_output)
        <div class="es-tab-panel {{ !$execution->ai_response?'active':'' }}" id="tab-html">
            <div class="es-code-block">
                <pre id="html-code">{{ $execution->html_output }}</pre>
                <button class="es-copy-btn" onclick="copyText('html-code', this)">{{ __('ai.exec_copy_btn') }}</button>
            </div>
        </div>
        @endif
        @if($execution->css_output)
        <div class="es-tab-panel" id="tab-css">
            <div class="es-code-block">
                <pre id="css-code">{{ $execution->css_output }}</pre>
                <button class="es-copy-btn" onclick="copyText('css-code', this)">{{ __('ai.exec_copy_btn') }}</button>
            </div>
        </div>
        @endif
        @if($execution->js_output)
        <div class="es-tab-panel" id="tab-js">
            <div class="es-code-block">
                <pre id="js-code">{{ $execution->js_output }}</pre>
                <button class="es-copy-btn" onclick="copyText('js-code', this)">{{ __('ai.exec_copy_btn') }}</button>
            </div>
        </div>
        @endif
        @if($execution->html_output && $execution->css_output)
        <div class="es-tab-panel" id="tab-preview">
            <iframe id="preview-iframe" sandbox="allow-scripts allow-same-origin" style="width:100%;height:420px;border:1px solid #e8e3ff;border-radius:8px;background:#fff;"></iframe>
        </div>
        @endif
    </div>
    @endif

    {{-- 파일 --}}
    @if($execution->files->count())
    <div class="es-section">
        <h3>{{ __('ai.exec_attachments') }}</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($execution->files as $file)
            <div class="es-file-item">
                <div class="es-file-icon">
                    <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $file->file_name }}</div>
                    <div style="font-size:11px;color:#94a3b8;">{{ $file->formattedSize() }} · {{ $file->type === 'input' ? __('ai.exec_uploaded') : __('ai.exec_ai_generated') }}</div>
                </div>
                <a href="{{ route('ai.executions.files.download', [$execution, $file]) }}"
                   style="padding:5px 12px;background:#f8f7ff;color:#7c3aed;border:1px solid #c4b5fd;border-radius:7px;font-size:11px;font-weight:600;text-decoration:none;">
                    {{ __('ai.download') }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
const ES_STR = {
    copy:    '{{ __('ai.exec_copy_btn') }}',
    copied:  '{{ __('ai.exec_copied') }}',
};
function switchTab(tabEl, panelId) {
    tabEl.closest('.es-section').querySelectorAll('.es-tab').forEach(t => t.classList.remove('active'));
    tabEl.closest('.es-section').querySelectorAll('.es-tab-panel').forEach(p => p.classList.remove('active'));
    tabEl.classList.add('active');
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');
    if (panelId === 'tab-preview') buildPreview();
}

function buildPreview() {
    const iframe = document.getElementById('preview-iframe');
    if (!iframe) return;
    const html = document.getElementById('html-code')?.textContent || '';
    const css  = document.getElementById('css-code')?.textContent || '';
    const js   = document.getElementById('js-code')?.textContent || '';
    const doc = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>${css}</style></head><body>${html}<script>${js}<\/script></body></html>`;
    iframe.srcdoc = doc;
}

function copyText(elId, btn) {
    const text = document.getElementById(elId)?.textContent || '';
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = ES_STR.copied;
        btn.style.background = 'rgba(16,185,129,.4)';
        setTimeout(() => { btn.textContent = ES_STR.copy; btn.style.background = ''; }, 2000);
    });
}
</script>
@endsection
