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
            <div style="display:flex;align-items:center;gap:2px;">
                <a href="{{ $catBase }}?scope=mine_personal" class="sf-cat {{ ($scope ?? null) === 'mine_personal' ? 'active' : '' }}" style="flex:1;min-width:0;">
                    <span>🔒 {{ __('shared-folder.my_personal') }}</span><span class="sf-cat-n">{{ $myPersonalCount }}</span>
                </a>
                <button type="button" onclick="sfShowSubAdd('', '', true)" title="{{ __('shared-folder.add_subfolder') }}"
                        style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;padding:2px 4px;line-height:1;display:flex;align-items:center;justify-content:center;"
                        onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#d1d5db'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
            {{-- 내 개인자료 하위 폴더 트리 --}}
            @foreach($personalTree ?? [] as $node)
                @include('shared-folder._tree_node', ['node' => $node, 'catBase' => $catBase, 'categoryId' => $categoryId, 'isPersonal' => true])
            @endforeach

            <a href="{{ $catBase }}?category=none" class="sf-cat {{ $categoryId === 'none' ? 'active' : '' }}">
                <span>🗂️ {{ __('shared-folder.category_none') }}</span><span class="sf-cat-n">{{ $uncategorizedCount }}</span>
            </a>
            {{-- 회사 공유 폴더 트리 (최대 3단계) — 재귀 partial --}}
            @foreach($tree as $node)
                @include('shared-folder._tree_node', ['node' => $node, 'catBase' => $catBase, 'categoryId' => $categoryId])
            @endforeach

            {{-- 폴더 추가 (루트 또는 선택된 상위 폴더 안에) --}}
            <form method="POST" action="{{ route('shared-folder.categories.store') }}" style="display:flex;flex-direction:column;gap:6px;margin-top:8px;padding-top:8px;border-top:1px solid var(--color-bg-muted);">
                @csrf
                <input type="hidden" id="sf-parent-id"   name="parent_id"   value="">
                <input type="hidden" id="sf-is-personal" name="is_personal" value="{{ ($scope ?? null) === 'mine_personal' ? '1' : '0' }}">
                <div id="sf-parent-hint" style="display:none;font-size:11px;color:var(--t700);background:var(--t50);padding:4px 8px;border-radius:6px;display:none;align-items:center;justify-content:space-between;gap:6px;">
                    <span id="sf-parent-hint-text"></span>
                    <button type="button" onclick="sfClearSubAdd()" title="{{ __('common.cancel') }}" style="background:none;border:none;cursor:pointer;color:var(--t600);font-size:14px;line-height:1;padding:0 2px;">&times;</button>
                </div>
                <input type="text" id="sf-name-input" name="name" maxlength="80" required placeholder="{{ ($scope ?? null) === 'mine_personal' ? __('shared-folder.personal_folder_name_ph') : __('shared-folder.category_name_ph') }}"
                       style="width:100%;padding:6px 9px;border:1px solid var(--color-border-default);border-radius:6px;font-size:12px;outline:none;box-sizing:border-box;">
                <button type="submit" id="sf-submit-btn" style="width:100%;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px 9px;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;transition:background .12s;"
                        onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span id="sf-submit-label">{{ ($scope ?? null) === 'mine_personal' ? __('shared-folder.personal_folder_add') : __('shared-folder.category_add') }}</span>
                </button>
            </form>
            <script>
            const SF_DEFAULT_PERSONAL = @json(($scope ?? null) === 'mine_personal');
            // parentId='' + isPersonal=true  → 개인 폴더 루트로 새 폴더 추가
            // parentId=N  + isPersonal=true  → 개인 폴더의 하위 폴더 (서버에서 부모 검증)
            // parentId=N  + isPersonal=false → 공유 폴더의 하위 폴더
            function sfShowSubAdd(parentId, parentName, isPersonal) {
                document.getElementById('sf-parent-id').value = parentId || '';
                document.getElementById('sf-is-personal').value = isPersonal ? '1' : '0';
                if (parentId) {
                    document.getElementById('sf-parent-hint').style.display = 'flex';
                    document.getElementById('sf-parent-hint-text').textContent =
                        @json(__('shared-folder.subfolder_of', ['parent' => ':PARENT:'])).replace(':PARENT:', parentName);
                    document.getElementById('sf-name-input').placeholder = @json(__('shared-folder.subfolder_name_ph'));
                    document.getElementById('sf-submit-label').textContent = @json(__('shared-folder.add_subfolder'));
                } else if (isPersonal) {
                    document.getElementById('sf-parent-hint').style.display = 'flex';
                    document.getElementById('sf-parent-hint-text').textContent = @json(__('shared-folder.personal_root_hint'));
                    document.getElementById('sf-name-input').placeholder = @json(__('shared-folder.personal_folder_name_ph'));
                    document.getElementById('sf-submit-label').textContent = @json(__('shared-folder.personal_folder_add'));
                }
                document.getElementById('sf-name-input').focus();
            }
            function sfClearSubAdd() {
                document.getElementById('sf-parent-id').value = '';
                document.getElementById('sf-is-personal').value = SF_DEFAULT_PERSONAL ? '1' : '0';
                document.getElementById('sf-parent-hint').style.display = 'none';
                document.getElementById('sf-name-input').placeholder = SF_DEFAULT_PERSONAL
                    ? @json(__('shared-folder.personal_folder_name_ph'))
                    : @json(__('shared-folder.category_name_ph'));
                document.getElementById('sf-submit-label').textContent = SF_DEFAULT_PERSONAL
                    ? @json(__('shared-folder.personal_folder_add'))
                    : @json(__('shared-folder.category_add'));
            }
            </script>
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
            <div data-sf-card style="background:#fff;border:1px solid var(--color-border-default);border-radius:14px;overflow:hidden;">
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
                                @php $canChangeCat = $file->uploaded_by === auth()->id() || auth()->user()->isAdmin(); @endphp
                                @if($canChangeCat)
                                    <span class="sf-actions" style="position:relative;display:inline-block;">
                                        <button type="button" class="sf-cat-chip-btn {{ $file->category ? '' : 'is-empty' }}"
                                                @if($file->category) style="background:{{ $file->category->color }};" @endif
                                                onclick="sfToggleDropdown(event, this)"
                                                title="{{ __('shared-folder.move_category') }}">
                                            @if($file->category)
                                                {{ $file->category->name }}
                                            @else
                                                <span style="color:#94a3b8;">—</span>
                                            @endif
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div class="sf-dd" style="display:none;position:absolute;left:0;top:100%;margin-top:4px;background:#fff;border:1px solid var(--color-border-default);border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.08);min-width:220px;max-width:280px;max-height:340px;overflow-y:auto;z-index:20;padding:4px;">
                                            <form method="POST" action="{{ url('shared-folder/files') }}/{{ $file->id }}/category" style="margin:0;">
                                                @csrf @method('PATCH')
                                                <button type="submit" name="category_id" value=""
                                                        class="sf-dd-item {{ !$file->category_id ? 'is-active' : '' }}"
                                                        onclick="event.stopPropagation();">
                                                    <span style="width:9px;height:9px;border-radius:3px;background:#cbd5e1;flex-shrink:0;"></span>
                                                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ __('shared-folder.move_to_none') }}</span>
                                                    @if(!$file->category_id)
                                                        <svg width="13" height="13" fill="none" stroke="var(--t600)" viewBox="0 0 24 24" stroke-width="3" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </button>
                                                @foreach($categories as $cat)
                                                    @php $isCurrent = (int)$file->category_id === (int)$cat->id; @endphp
                                                    <button type="submit" name="category_id" value="{{ $cat->id }}"
                                                            class="sf-dd-item {{ $isCurrent ? 'is-active' : '' }}"
                                                            onclick="event.stopPropagation();"
                                                            @if($isCurrent) disabled @endif>
                                                        <span style="width:9px;height:9px;border-radius:3px;background:{{ $cat->color }};flex-shrink:0;"></span>
                                                        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cat->name }}</span>
                                                        @if($isCurrent)
                                                            <svg width="13" height="13" fill="none" stroke="var(--t600)" viewBox="0 0 24 24" stroke-width="3" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </form>
                                        </div>
                                    </span>
                                @else
                                    @if($file->category)
                                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;background:{{ $file->category->color }};">{{ $file->category->name }}</span>
                                    @else
                                        <span style="font-size:11px;color:#cbd5e1;">—</span>
                                    @endif
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
                                            onclick="sfToggleDropdown(event, this)"
                                            style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);padding:4px;line-height:0;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">
                                        <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                    </button>
                                    <div class="sf-dd" style="display:none;position:absolute;right:0;top:100%;margin-top:2px;background:#fff;border:1px solid var(--color-border-default);border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.08);min-width:150px;z-index:10;padding:4px;">
                                        <form method="POST" action="{{ route('shared-folder.destroy', $file) }}" style="margin:0;">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    data-confirm="{{ __('shared-folder.delete_confirm') }}"
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
/* ⋮ 드롭다운: 폴더 이동 직접 선택 */
.sf-dd.open { display:block !important; }
/* 카드 하단에서 잘리지 않도록 위로 펼치는 모드 */
.sf-dd.flip-up { top:auto !important; bottom:100% !important; margin-top:0 !important; margin-bottom:4px !important; }
.sf-dd-item {
    display:flex; align-items:center; gap:8px; width:100%; text-align:left;
    padding:7px 9px; background:none; border:none; border-radius:6px;
    font-size:12.5px; color:var(--color-text-secondary); cursor:pointer;
}
.sf-dd-item:hover:not(:disabled) { background:#f3f4f6; }
.sf-dd-item.is-active { background:#f5f3ff; color:#5b21b6; font-weight:700; cursor:default; }
.sf-dd-item:disabled { cursor:default; }
/* 카테고리 셀 chip 버튼 — 클릭으로 폴더 변경 */
.sf-cat-chip-btn {
    display:inline-flex; align-items:center; gap:4px;
    padding:2px 8px 2px 9px; border-radius:20px; border:1px solid transparent;
    font-size:11px; font-weight:600; color:#fff; cursor:pointer;
    line-height:1.5; transition:filter .12s, box-shadow .12s;
}
.sf-cat-chip-btn:hover { filter:brightness(.94); box-shadow:0 0 0 2px rgba(124,58,237,.18); }
.sf-cat-chip-btn.is-empty {
    background:#f8fafc; border-color:#e2e8f0; color:#94a3b8; font-weight:500;
}
.sf-cat-chip-btn.is-empty:hover { background:#f1f5f9; border-color:#cbd5e1; box-shadow:none; }
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

// ⋮ 드롭다운: 바깥 클릭 시 닫기 (드롭다운 내부 클릭은 유지)
document.addEventListener('click', function(e){
    document.querySelectorAll('.sf-dd.open').forEach(function(d){
        if (!d.contains(e.target)) d.classList.remove('open');
    });
});

// 공통 드롭다운 토글 — 카드 하단에서 잘리면 위쪽으로 자동 flip
window.sfToggleDropdown = function(ev, btn){
    ev.stopPropagation();
    var dd = btn.nextElementSibling;
    if (!dd) return;
    // 다른 열린 드롭다운 닫기
    document.querySelectorAll('.sf-dd.open').forEach(function(d){ if (d !== dd) d.classList.remove('open'); });
    dd.classList.remove('flip-up');
    dd.classList.toggle('open');
    if (!dd.classList.contains('open')) return;
    // 카드 하단을 벗어나면 위쪽으로 펼침
    requestAnimationFrame(function(){
        var card = btn.closest('[data-sf-card]');
        var limit = card ? card.getBoundingClientRect().bottom : window.innerHeight;
        if (dd.getBoundingClientRect().bottom > limit - 4) {
            dd.classList.add('flip-up');
        }
    });
};
</script>
@endsection
