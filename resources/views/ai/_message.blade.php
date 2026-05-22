@php
    $uid     = 'cp-' . $msg->id;
    $hasCode = $msg->html_output || $msg->css_output || $msg->js_output;
    $hasDoc  = $msg->doc_file_name && $msg->doc_status !== 'failed';
    $docIcon = match($msg->doc_file_type ?? '') {
        'pptx'  => '📊',
        'xlsx'  => '📗',
        'docx'  => '📄',
        'pdf'   => '📕',
        default => '📁',
    };
    $docLabel = match($msg->doc_file_type ?? '') {
        'pptx'  => 'PowerPoint',
        'xlsx'  => 'Excel',
        'docx'  => 'Word',
        'pdf'   => 'PDF',
        default => __('ai.document'),
    };
    $mdToHtml = function(string $text): string {
        $h = e($text);
        $h = preg_replace('/^### (.+)$/m', '<strong style="font-size:13px;color:#404040;display:block;margin:10px 0 4px;">$1</strong>', $h);
        $h = preg_replace('/^## (.+)$/m',  '<strong style="font-size:14px;color:#2E75B6;display:block;margin:14px 0 6px;padding-bottom:4px;border-bottom:1px solid #e0e7ef;">$1</strong>', $h);
        $h = preg_replace('/^# (.+)$/m',   '<strong style="font-size:16px;color:#1F3864;display:block;margin:16px 0 8px;">$1</strong>', $h);
        $h = preg_replace('/^[-*] (.+)$/m','<span style="display:block;padding-left:16px;position:relative;margin:2px 0;">&bull;&nbsp;$1</span>', $h);
        $h = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $h);
        $h = str_replace("\n", '<br>', $h);
        return $h;
    };
@endphp

<div class="ai-msg {{ $msg->role }}">
    <div class="ai-msg-av">{{ $msg->role === 'user' ? __('ai.me') : '웍스' }}</div>

    <div class="ai-msg-body" style="{{ $msg->role === 'assistant' ? 'flex:1;max-width:100%;' : '' }}">
        @if($msg->content)
        @if($msg->role === 'assistant')
        <div class="ai-bubble" style="line-height:1.75;">{!! $mdToHtml($msg->content) !!}</div>
        @else
        <div class="ai-bubble">{{ $msg->content }}</div>
        @endif
        @endif

        @if($hasDoc)
        <div class="ai-doc-card">
            <div class="ai-doc-card-icon">{{ $docIcon }}</div>
            <div class="ai-doc-card-info">
                <div class="ai-doc-card-name">{{ $msg->doc_file_name }}</div>
                <div class="ai-doc-card-meta">
                    {{ $docLabel }}
                    @if($msg->doc_status === 'processing')
                    · <span style="color:#d97706;">{{ __('ai.doc_generating') }}</span>
                    @elseif($msg->doc_status === 'completed')
                    · <span style="color:#16a34a;">{{ __('ai.doc_generated') }}</span>
                    @endif
                </div>
            </div>
            @if($msg->doc_download_url && $msg->doc_status === 'completed')
            <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0;">
                <a href="{{ $msg->doc_download_url }}" download="{{ $msg->doc_file_name ?? 'document.docx' }}" class="ai-doc-download-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('ai.download') }}
                </a>
                <button onclick="showAddToProjectPicker({{ $msg->id }}, {{ json_encode($msg->doc_file_name) }}, {{ json_encode($msg->doc_file_type) }})"
                    class="ai-doc-add-proj-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('ai.add_to_project_file') }}
                </button>
            </div>
            @endif
        </div>
        @endif

        @if($hasCode)
        <div class="ai-code-panel" id="{{ $uid }}">
            <div class="ai-code-tabs">
                @if($msg->html_output)
                <div class="ai-code-tab active" onclick="switchTab('{{ $uid }}','html',this)">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    HTML
                </div>
                @endif
                @if($msg->css_output)
                <div class="ai-code-tab {{ !$msg->html_output ? 'active' : '' }}" onclick="switchTab('{{ $uid }}','css',this)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="opacity:.8;"><path d="M3 3l1.576 17.254L12 22l7.4-1.746L21 3H3zm14.088 4.896l-.178 1.94H8.06l.18 2.016h8.487l-.537 5.985-4.2 1.161-4.193-1.161-.288-3.202h1.96l.146 1.64 2.375.641 2.378-.64.25-2.783H7.553L6.984 7.896h10.104z"/></svg>
                    CSS
                </div>
                @endif
                @if($msg->js_output)
                @php $jsFirst = !$msg->html_output && !$msg->css_output; @endphp
                <div class="ai-code-tab {{ $jsFirst ? 'active' : '' }}" onclick="switchTab('{{ $uid }}','js',this)">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="opacity:.8;"><path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.404-.601-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/></svg>
                    JS
                </div>
                @endif
                <div class="ai-code-tab" onclick="switchTab('{{ $uid }}','preview',this)">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview
                </div>
                <button class="ai-tab-copy" onclick="copyCode('{{ $uid }}')">{{ __('ai.copy') }}</button>
                <a href="{{ route('ai.message.download', $msg->id) }}" class="ai-tab-copy" style="display:flex;align-items:center;gap:4px;text-decoration:none;" title="ZIP {{ __('ai.download') }}">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    ZIP
                </a>
                <button id="atp-zip-btn-{{ $msg->id }}" class="ai-tab-copy"
                    onclick="showAddToProjectPicker({{ $msg->id }}, 'source-code.zip', 'zip', '{{ route('ai.messages.addZipToProject', $msg->id) }}')"
                    style="display:flex;align-items:center;gap:4px;color:#16a34a;border-color:#bbf7d0;" title="{{ __('ai.add_to_project_file') }}">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ __('ai.add_to_project_file') }}
                </button>
                <button class="ai-tab-copy" onclick="openPreviewFs('{{ $uid }}')" title="{{ __('ai.fullscreen') }}" style="display:flex;align-items:center;gap:4px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    {{ __('ai.fullscreen') }}
                </button>
            </div>

            @if($msg->html_output)
            <pre class="ai-code-content active" id="{{ $uid }}-html">{{ $msg->html_output }}</pre>
            @endif
            @if($msg->css_output)
            <pre class="ai-code-content {{ !$msg->html_output ? 'active' : '' }}" id="{{ $uid }}-css">{{ $msg->css_output }}</pre>
            @endif
            @if($msg->js_output)
            <pre class="ai-code-content {{ $jsFirst ? 'active' : '' }}" id="{{ $uid }}-js">{{ $msg->js_output }}</pre>
            @endif
            <iframe class="ai-preview-frame" id="{{ $uid }}-preview" sandbox="allow-scripts allow-same-origin"></iframe>
        </div>
        @endif

        <div class="ai-msg-time">{{ $msg->created_at->format(__('ai.php_date_format')) }}</div>
    </div>
</div>
