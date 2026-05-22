@extends('layouts.app')

@section('title', __('shared-folder.title'))

@section('content')
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- 헤더 --}}
    <div>
        <h1 style="font-size:19px;font-weight:800;color:var(--color-text-primary);display:flex;align-items:center;gap:8px;">
            <svg width="20" height="20" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/></svg>
            {{ __('shared-folder.title') }}
        </h1>
        <div style="font-size:13px;color:var(--color-text-tertiary);margin-top:3px;">{{ __('shared-folder.subtitle') }}</div>
    </div>

    {{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}
    @if($errors->any())
    <div style="background:var(--color-bg-danger-subtle);border:1px solid #fecaca;color:#b91c1c;border-radius:9px;padding:9px 14px;font-size:13px;">{{ $errors->first() }}</div>
    @endif

    <div style="display:flex;gap:16px;align-items:flex-start;">

        {{-- ── 폴더(카테고리) 사이드바 ── --}}
        <div style="width:210px;flex-shrink:0;background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--color-text-tertiary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;padding:0 4px;">{{ __('shared-folder.folders') }}</div>
            @php $catBase = route('shared-folder.index'); @endphp
            <a href="{{ $catBase }}" class="sf-cat {{ !$categoryId && !($scope ?? null) ? 'active' : '' }}">
                <span>📁 {{ __('shared-folder.category_all') }}</span><span class="sf-cat-n">{{ $totalCount }}</span>
            </a>
            <a href="{{ $catBase }}?scope=mine_personal" class="sf-cat {{ ($scope ?? null) === 'mine_personal' ? 'active' : '' }}">
                <span>🔒 {{ __('shared-folder.my_personal') }}</span><span class="sf-cat-n">{{ $myPersonalCount }}</span>
            </a>
            <a href="{{ $catBase }}?category=none" class="sf-cat {{ $categoryId === 'none' ? 'active' : '' }}">
                <span>🗂️ {{ __('shared-folder.category_none') }}</span><span class="sf-cat-n">{{ $uncategorizedCount }}</span>
            </a>
            @foreach($categories as $cat)
            <div style="display:flex;align-items:center;">
                <a href="{{ $catBase }}?category={{ $cat->id }}" class="sf-cat {{ (string)$categoryId === (string)$cat->id ? 'active' : '' }}" style="flex:1;min-width:0;">
                    <span style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span style="width:9px;height:9px;border-radius:3px;background:{{ $cat->color }};flex-shrink:0;"></span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cat->name }}</span>
                    </span>
                    <span class="sf-cat-n">{{ $cat->files_count }}</span>
                </a>
                <form method="POST" action="{{ route('shared-folder.categories.destroy', $cat) }}"
                      onsubmit="return confirm('{{ __('shared-folder.category_delete_confirm') }}')" style="margin:0;">
                    @csrf @method('DELETE')
                    <button type="submit" title="{{ __('shared-folder.delete') }}" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;padding:2px 4px;line-height:1;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">&times;</button>
                </form>
            </div>
            @endforeach
            {{-- 폴더 추가 --}}
            <form method="POST" action="{{ route('shared-folder.categories.store') }}" style="display:flex;gap:4px;margin-top:8px;padding-top:8px;border-top:1px solid var(--color-bg-muted);">
                @csrf
                <input type="text" name="name" maxlength="80" required placeholder="{{ __('shared-folder.category_name_ph') }}"
                       style="flex:1;min-width:0;padding:5px 8px;border:1px solid var(--color-border-default);border-radius:6px;font-size:12px;outline:none;">
                <button type="submit" style="flex-shrink:0;padding:5px 9px;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">+</button>
            </form>
        </div>

        {{-- ── 메인 ── --}}
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:16px;">

            {{-- 업로드 --}}
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;padding:16px 18px;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:10px;">{{ __('shared-folder.upload') }}</div>
                <form method="POST" action="{{ route('shared-folder.store') }}" enctype="multipart/form-data"
                      style="display:flex;flex-direction:column;gap:12px;">
                    @csrf
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        {{-- 파일 선택 (네이티브 input 숨김 + 누적 큐) --}}
                        <input type="file" id="sf-file-input" name="files[]" multiple style="display:none;">
                        <label for="sf-file-input" class="sf-file-btn">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ __('shared-folder.choose_files') }}
                        </label>
                        <span id="sf-file-empty" style="font-size:12px;color:var(--color-text-tertiary);">{{ __('shared-folder.no_file_selected') }}</span>
                        <select name="category_id" class="sf-input" style="flex-shrink:0;">
                            <option value="">{{ __('shared-folder.category_select') }}</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="description" maxlength="500" placeholder="{{ __('shared-folder.description_ph') }}"
                               class="sf-input" style="flex:1;min-width:160px;">
                        <label class="sf-personal-chk" title="{{ __('shared-folder.personal_hint') }}">
                            <input type="checkbox" name="is_personal" value="1">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            {{ __('shared-folder.personal') }}
                        </label>
                        <button type="submit" id="sf-upload-submit" class="sf-upload-btn" disabled>
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0-12l-4 4m4-4l4 4M4 20h16"/></svg>
                            {{ __('shared-folder.upload_btn') }}
                        </button>
                    </div>
                    {{-- 선택 파일 큐 (멀티 업로드 — 여러 번 선택해 누적) --}}
                    <div id="sf-file-queue" style="display:none;flex-wrap:wrap;gap:8px;"></div>
                </form>
            </div>

            {{-- 파일 목록 --}}
            <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--color-bg-muted);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:13px;font-weight:700;color:var(--color-text-primary);">{{ __('shared-folder.file_list') }}</span>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <form method="GET" action="{{ route('shared-folder.index') }}" style="display:flex;">
                            @if($categoryId)<input type="hidden" name="category" value="{{ $categoryId }}">@endif
                            <div style="position:relative;">
                                <svg width="13" height="13" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);pointer-events:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="{{ __('common.search') }}"
                                       style="padding:5px 10px 5px 27px;border:1px solid var(--color-border-default);border-radius:7px;font-size:12px;width:180px;outline:none;box-sizing:border-box;">
                            </div>
                        </form>
                        <span style="font-size:12px;color:var(--color-text-tertiary);">{{ $files->total() }}</span>
                    </div>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb;border-bottom:1px solid var(--color-bg-muted);">
                            <th style="text-align:left;padding:10px 20px;font-size:11px;font-weight:600;color:var(--color-text-secondary);">{{ __('shared-folder.col_name') }}</th>
                            <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:var(--color-text-secondary);">{{ __('shared-folder.col_category') }}</th>
                            <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:var(--color-text-secondary);">{{ __('shared-folder.col_size') }}</th>
                            <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:var(--color-text-secondary);">{{ __('shared-folder.col_uploader') }}</th>
                            <th style="text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:var(--color-text-secondary);">{{ __('shared-folder.col_date') }}</th>
                            <th style="padding:10px 20px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($files as $file)
                        <tr style="border-bottom:1px solid #f9fafb;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <td style="padding:11px 20px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:18px;flex-shrink:0;">{{ $file->icon }}</span>
                                    <div style="min-width:0;">
                                        <a href="{{ route('shared-folder.download', $file) }}" style="font-size:13px;font-weight:600;color:var(--color-text-primary);text-decoration:none;word-break:break-all;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#111827'">{{ $file->original_name }}</a>
                                        @if($file->is_personal)
                                        <span style="display:inline-flex;align-items:center;gap:4px;margin-left:6px;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:700;color:#92400e;background:#fef3c7;vertical-align:middle;" title="{{ __('shared-folder.personal_hint') }}">
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            {{ __('shared-folder.personal') }}
                                        </span>
                                        @endif
                                        @if($file->description)<div style="font-size:11px;color:var(--color-text-tertiary);margin-top:1px;">{{ $file->description }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding:11px 12px;">
                                @if($file->category)
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;background:{{ $file->category->color }};">{{ $file->category->name }}</span>
                                @else
                                <span style="font-size:11px;color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td style="padding:11px 12px;font-size:12px;color:var(--color-text-secondary);">{{ $file->formatted_size }}</td>
                            <td style="padding:11px 12px;font-size:12px;color:var(--color-text-secondary);">{{ $file->uploader?->name ?? '—' }}</td>
                            <td style="padding:11px 12px;font-size:12px;color:var(--color-text-tertiary);">{{ $file->created_at?->format('Y-m-d') }}</td>
                            <td style="padding:11px 20px;text-align:right;white-space:nowrap;">
                                <a href="{{ route('shared-folder.download', $file) }}" title="{{ __('shared-folder.download') }}"
                                   style="display:inline-flex;color:var(--t600);padding:4px;" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>

                                @if($file->uploaded_by === auth()->id() || auth()->user()->isAdmin())
                                {{-- ⋮ 드롭다운: 카테고리 이동 / 삭제 --}}
                                <span class="sf-actions" style="position:relative;display:inline-block;margin-left:4px;">
                                    <button type="button" title="{{ __('shared-folder.more_actions') }}"
                                            onclick="event.stopPropagation(); document.querySelectorAll('.sf-dd.open').forEach(function(d){if(d!==this.nextElementSibling)d.classList.remove('open');}.bind(this)); this.nextElementSibling.classList.toggle('open');"
                                            style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);padding:4px;line-height:0;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                    </button>
                                    <div class="sf-dd" style="display:none;position:absolute;right:0;top:100%;margin-top:2px;background:#fff;border:1px solid var(--color-border-default);border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.08);min-width:150px;z-index:10;padding:4px;">
                                        <button type="button" data-file-id="{{ $file->id }}" data-file-name="{{ e($file->original_name) }}" data-current-category="{{ $file->category_id ?? '' }}"
                                                onclick="openMoveCategoryModal(this)"
                                                style="display:flex;align-items:center;gap:8px;width:100%;text-align:left;padding:7px 9px;background:none;border:none;border-radius:6px;font-size:12.5px;color:var(--color-text-secondary);cursor:pointer;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">
                                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/></svg>
                                            {{ __('shared-folder.move_category') }}
                                        </button>
                                        <form method="POST" action="{{ route('shared-folder.destroy', $file) }}" style="margin:0;"
                                              onsubmit="return confirm('{{ __('shared-folder.delete_confirm') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    style="display:flex;align-items:center;gap:8px;width:100%;text-align:left;padding:7px 9px;background:none;border:none;border-radius:6px;font-size:12.5px;color:var(--color-alert-warning-500);cursor:pointer;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                {{ __('shared-folder.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" style="padding:42px 20px;text-align:center;color:var(--color-text-tertiary);">
                            <div style="font-size:26px;margin-bottom:6px;">📂</div>
                            <div style="font-size:13px;">{{ __('shared-folder.empty') }}</div>
                            <div style="font-size:12px;margin-top:3px;">{{ __('shared-folder.empty_hint') }}</div>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if($files->hasPages())
                <div style="padding:14px 20px;border-top:1px solid var(--color-bg-muted);">{{ $files->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</div>

<style>
.sf-cat { display:flex;align-items:center;justify-content:space-between;gap:6px;padding:7px 9px;border-radius:8px;font-size:12.5px;color:#374151;text-decoration:none;margin-bottom:2px; }
.sf-cat:hover { background:#f3f4f6; }
.sf-cat.active { background:#ede9fe;color:#5b21b6;font-weight:700; }
.sf-cat-n { font-size:11px;color:#9ca3af;background:#f3f4f6;border-radius:10px;padding:1px 7px;flex-shrink:0; }
.sf-cat.active .sf-cat-n { background:#ddd6fe;color:#5b21b6; }
/* 파일 업로드 영역 */
.sf-file-btn { display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border:1.5px solid #ddd6fe;background:#f5f3ff;color:#7c3aed;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;flex-shrink:0; }
.sf-file-btn:hover { background:#ede9fe; }
.sf-input { padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12.5px;outline:none;box-sizing:border-box;color:#1f2937;background:#fff; }
.sf-input:focus { border-color:#a78bfa; }
.sf-upload-btn { display:inline-flex;align-items:center;gap:5px;padding:7px 16px;background:#7c3aed;color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;flex-shrink:0; }
.sf-upload-btn:hover { background:#6d28d9; }
.sf-upload-btn:disabled { background:#c4b5fd;cursor:default; }
.sf-chip { display:inline-flex;align-items:center;gap:4px;background:#f5f3ff;border:1px solid #ece9ff;color:#4b5563;border-radius:7px;padding:4px 5px 4px 9px;font-size:11.5px;max-width:260px; }
.sf-chip span { overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.sf-chip-x { background:none;border:none;color:#b8b0d8;font-size:14px;line-height:1;cursor:pointer;padding:0 2px;flex-shrink:0; }
.sf-chip-x:hover { color:#ef4444; }
.sf-personal-chk { display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;color:#6b7280;cursor:pointer;background:#fff;user-select:none;flex-shrink:0; }
.sf-personal-chk:has(input:checked) { border-color:#f59e0b;background:#fffbeb;color:#92400e;font-weight:700; }
.sf-personal-chk input { margin:0; }
</style>
<script>
(function(){
    const inp       = document.getElementById('sf-file-input');
    const queueEl   = document.getElementById('sf-file-queue');
    const emptyEl   = document.getElementById('sf-file-empty');
    const submitBtn = document.getElementById('sf-upload-submit');
    if (!inp || !queueEl) return;

    let files = [];   // 누적된 File 객체 (멀티 업로드 큐)
    const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    function syncInput(){
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        inp.files = dt.files;
        if (submitBtn) submitBtn.disabled = files.length === 0;
        if (emptyEl)   emptyEl.style.display = files.length ? 'none' : '';
    }
    function render(){
        if (!files.length){ queueEl.style.display = 'none'; queueEl.innerHTML = ''; return; }
        queueEl.style.display = 'flex';
        queueEl.innerHTML = files.map((f, i) =>
            `<span class="sf-chip"><span>📎 ${esc(f.name)}</span><button type="button" class="sf-chip-x" data-i="${i}">&times;</button></span>`
        ).join('');
    }
    inp.addEventListener('change', function(){
        for (const f of inp.files) {
            if (!files.some(x => x.name === f.name && x.size === f.size)) files.push(f);
        }
        syncInput(); render();
    });
    queueEl.addEventListener('click', function(e){
        const btn = e.target.closest('.sf-chip-x');
        if (!btn) return;
        files.splice(parseInt(btn.dataset.i, 10), 1);
        syncInput(); render();
    });
})();

// ⋮ 드롭다운: 바깥 클릭 시 닫기
document.addEventListener('click', function(){
    document.querySelectorAll('.sf-dd.open').forEach(function(d){ d.classList.remove('open'); });
});

// 카테고리 이동 모달 열기
function openMoveCategoryModal(triggerBtn){
    const fileId   = triggerBtn.dataset.fileId;
    const fileName = triggerBtn.dataset.fileName;
    const current  = triggerBtn.dataset.currentCategory || '';

    const form = document.getElementById('sf-move-form');
    if (form) {
        form.action = '{{ url('shared-folder/files') }}/' + fileId + '/category';
        const sel = form.querySelector('select[name="category_id"]');
        if (sel) sel.value = current;
    }
    const title = document.getElementById('sf-move-target-name');
    if (title) title.textContent = fileName;

    document.getElementById('sf-move-modal').style.display = 'flex';
    // 드롭다운 닫기
    document.querySelectorAll('.sf-dd.open').forEach(function(d){ d.classList.remove('open'); });
}
function closeMoveCategoryModal(){
    document.getElementById('sf-move-modal').style.display = 'none';
}
</script>

<style>
.sf-dd.open { display:block !important; }
</style>

{{-- 카테고리 이동 모달 --}}
<div id="sf-move-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this) closeMoveCategoryModal()">
    <div style="background:#fff;border-radius:14px;padding:22px;width:380px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <h2 style="font-size:15px;font-weight:800;color:var(--color-text-primary);margin:0;">{{ __('shared-folder.move_category_title') }}</h2>
            <button type="button" onclick="closeMoveCategoryModal()" style="background:none;border:none;font-size:20px;color:var(--color-text-tertiary);cursor:pointer;line-height:1;padding:0 4px;">&times;</button>
        </div>
        <div id="sf-move-target-name" style="font-size:12px;color:var(--color-text-secondary);margin-bottom:14px;word-break:break-all;"></div>

        <form id="sf-move-form" method="POST" action="">
            @csrf @method('PATCH')
            <label style="display:block;font-size:11px;font-weight:700;color:var(--color-text-secondary);margin-bottom:6px;">{{ __('shared-folder.move_target') }}</label>
            <select name="category_id" class="sf-input" style="width:100%;margin-bottom:14px;">
                <option value="">{{ __('shared-folder.move_to_none') }}</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" onclick="closeMoveCategoryModal()"
                        style="padding:8px 16px;background:var(--color-bg-muted);color:var(--color-text-secondary);border:none;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit"
                        style="padding:8px 16px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;">{{ __('shared-folder.move_submit') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
