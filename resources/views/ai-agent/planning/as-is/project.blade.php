@extends('layouts.ai-agent')
@section('title', 'AS-IS 분석 — 웍스 Agent')

@push('styles')
<style>
.asis-header      { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.asis-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.asis-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

.asis-section     { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.asis-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* File upload zone */
.asis-upload-zone { border:2px dashed #c4b5fd; border-radius:12px; padding:28px 20px; text-align:center; cursor:pointer; transition:all .15s; background:#faf5ff; }
.asis-upload-zone:hover, .asis-upload-zone.drag-over { border-color:var(--t600,#7c3aed); background:#f5f3ff; }
.asis-upload-icon { font-size:32px; margin-bottom:8px; }
.asis-upload-label { font-size:13.5px; color:#475569; margin-bottom:4px; }
.asis-upload-hint  { font-size:11.5px; color:#94a3b8; }
.asis-upload-input { display:none; }

/* File list */
.asis-file-list   { display:flex; flex-direction:column; gap:8px; margin-top:14px; }
.asis-file-item   { display:flex; align-items:center; gap:10px; padding:10px 14px; background:#faf5ff; border-radius:10px; border:1px solid #ede8ff; }
.asis-file-icon   { width:32px; height:32px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.asis-file-icon.text  { background:#ecfdf5; }
.asis-file-icon.excel { background:#f0fdf4; }
.asis-file-icon.pptx  { background:#fff7ed; }
.asis-file-icon.pdf   { background:#fef2f2; }
.asis-file-icon.image { background:#eff6ff; }
.asis-file-icon.other { background:#f8fafc; }

.asis-file-info   { flex:1; min-width:0; }
.asis-file-name   { font-size:13px; font-weight:600; color:#1e1b2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.asis-file-meta   { font-size:11.5px; color:#94a3b8; margin-top:1px; }

.asis-parse-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.asis-parse-badge.pending   { background:#f8fafc; color:#64748b; }
.asis-parse-badge.parsing   { background:#fffbeb; color:#d97706; }
.asis-parse-badge.completed { background:#f0fdf4; color:#166534; }
.asis-parse-badge.failed    { background:#fef2f2; color:#dc2626; }

.asis-file-del    { display:inline-flex; align-items:center; padding:5px 8px; border-radius:6px; font-size:11.5px; border:none; cursor:pointer; background:#fff; color:#94a3b8; border:1px solid #e2e8f0; transition:all .12s; flex-shrink:0; }
.asis-file-del:hover { color:#dc2626; border-color:#fca5a5; }

/* Status bar */
.asis-status-bar  { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.asis-stat        { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 16px; display:flex; align-items:center; gap:8px; }
.asis-stat-num    { font-size:20px; font-weight:800; color:var(--t600,#7c3aed); }
.asis-stat-label  { font-size:11.5px; color:#64748b; line-height:1.4; }
</style>
@endpush

@section('ai-agent-content')
<div class="asis-header">
    <div class="asis-header-left">
        <h1>AS-IS 분석 <span style="font-size:14px;font-weight:500;color:#94a3b8;">프로젝트 전체</span></h1>
        <p>현재 업무 프로세스와 시스템을 분석하기 위한 자료를 업로드하세요.</p>
    </div>
</div>

{{-- Status --}}
<div class="asis-status-bar">
    <div class="asis-stat">
        <div>
            <div class="asis-stat-num">{{ $files->count() }}</div>
            <div class="asis-stat-label">전체 파일</div>
        </div>
    </div>
    <div class="asis-stat">
        <div>
            <div class="asis-stat-num" style="color:#166534">{{ $files->where('parse_status','completed')->count() }}</div>
            <div class="asis-stat-label">파싱 완료</div>
        </div>
    </div>
    <div class="asis-stat">
        <div>
            <div class="asis-stat-num" style="color:#d97706">{{ $files->whereIn('parse_status',['pending','parsing'])->count() }}</div>
            <div class="asis-stat-label">처리 중</div>
        </div>
    </div>
    @if($files->where('parse_status','failed')->count() > 0)
    <div class="asis-stat">
        <div>
            <div class="asis-stat-num" style="color:#dc2626">{{ $files->where('parse_status','failed')->count() }}</div>
            <div class="asis-stat-label">오류</div>
        </div>
    </div>
    @endif
</div>

{{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

{{-- Upload section --}}
<div class="asis-section">
    <div class="asis-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        자료 업로드
    </div>

    <form method="POST" action="{{ route('ai-agent.projects.planning.as-is.upload', $project) }}" enctype="multipart/form-data" id="upload-form">
        @csrf
        <div class="asis-upload-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
            <div class="asis-upload-icon">📁</div>
            <div class="asis-upload-label">파일을 드래그하거나 클릭하여 업로드</div>
            <div class="asis-upload-hint">Excel, PowerPoint, PDF, 이미지, 텍스트 등 — 최대 50MB / 최대 10개</div>
        </div>
        <input type="file" id="file-input" name="files[]" multiple class="asis-upload-input"
               accept=".xlsx,.xls,.pptx,.ppt,.pdf,.txt,.csv,.json,.md,.jpg,.jpeg,.png,.gif,.webp">

        <div id="selected-files" style="display:none;margin-top:12px;"></div>

        <div style="margin-top:14px;display:flex;gap:8px;align-items:center;" id="upload-actions" style="display:none">
            <button type="submit" id="upload-btn" style="display:none;padding:9px 20px;background:var(--t600,#7c3aed);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
                업로드
            </button>
        </div>
    </form>
</div>

{{-- File list --}}
@if($files->count() > 0)
<div class="asis-section">
    <div class="asis-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
        업로드된 파일 ({{ $files->count() }})
    </div>

    <div class="asis-file-list">
        @foreach($files as $file)
        @php
            $icon = match($file->file_type) {
                'excel' => '📊', 'pptx' => '📊', 'pdf' => '📄',
                'image' => '🖼', 'text' => '📝', default => '📎',
            };
        @endphp
        <div class="asis-file-item">
            <div class="asis-file-icon {{ $file->file_type }}">{{ $icon }}</div>
            <div class="asis-file-info">
                <div class="asis-file-name" title="{{ $file->file_name }}">{{ $file->file_name }}</div>
                <div class="asis-file-meta">{{ $file->formatted_size }} · {{ $file->mime_type }} · {{ $file->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <span class="asis-parse-badge {{ $file->parse_status }}">
                @if($file->parse_status === 'completed') ✓ 파싱 완료
                @elseif($file->parse_status === 'parsing') ⟳ 파싱 중
                @elseif($file->parse_status === 'failed') ✕ 오류
                @else ○ 대기 중
                @endif
            </span>
            <form method="POST" action="{{ route('ai-agent.projects.planning.as-is.file.delete', [$project, $file]) }}" onsubmit="return confirm('파일을 삭제하시겠습니까?')">
                @csrf @method('DELETE')
                <button type="submit" class="asis-file-del" title="삭제">✕</button>
            </form>
        </div>
        @if($file->parse_status === 'failed' && $file->parse_error)
        <div style="font-size:11.5px;color:#dc2626;padding:4px 14px;background:#fef2f2;border-radius:6px;margin-top:-4px;">
            오류: {{ $file->parse_error }}
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

@push('scripts')
<script>
(async function () {
    const input    = document.getElementById('file-input');
    const dropZone = document.getElementById('drop-zone');
    const preview  = document.getElementById('selected-files');
    const btn      = document.getElementById('upload-btn');

    async function showFiles(files) {
        if (!files.length) { preview.style.display = 'none'; btn.style.display = 'none'; return; }
        preview.style.display = 'block';
        btn.style.display = 'inline-block';
        preview.innerHTML = Array.from(files).map(f =>
            `<div style="font-size:12px;color:#475569;padding:3px 0;">📎 ${f.name} (${(f.size/1024).toFixed(1)} KB)</div>`
        ).join('');
    }

    input.addEventListener('change', () => showFiles(input.files));

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        showFiles(dt.files);
    });
})();
</script>
@endpush
@endsection
