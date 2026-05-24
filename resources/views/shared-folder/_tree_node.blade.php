@php
    $cat   = $node['cat'];
    $depth = $node['depth'];
    $hasChildren = !empty($node['children']);
    $isActive = (string)$categoryId === (string)$cat->id;
    $indent = ($depth - 1) * 12;
@endphp
<div data-cat-id="{{ $cat->id }}" data-cat-depth="{{ $depth }}" style="display:flex;align-items:center;gap:2px;padding-left:{{ $indent }}px;">
    <a href="{{ $catBase }}?category={{ $cat->id }}" class="sf-cat {{ $isActive ? 'active' : '' }}" style="flex:1;min-width:0;">
        <span style="display:flex;align-items:center;gap:8px;min-width:0;">
            @if($depth > 1)
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            @endif
            <span style="width:9px;height:9px;border-radius:3px;background:{{ $cat->color }};flex-shrink:0;"></span>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cat->name }}</span>
        </span>
        <span class="sf-cat-n">{{ $cat->files_count }}</span>
    </a>
    @if($depth < \App\Models\SharedFileCategory::MAX_DEPTH)
    <button type="button"
            onclick="sfShowSubAdd({{ $cat->id }}, {{ json_encode($cat->name) }})"
            title="{{ __('shared-folder.add_subfolder') }}"
            style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;padding:2px 4px;line-height:1;display:flex;align-items:center;justify-content:center;"
            onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#d1d5db'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
    @endif
    @if(!$hasChildren)
    <form method="POST" action="{{ route('shared-folder.categories.destroy', $cat) }}"
          onsubmit="return confirm('{{ __('shared-folder.category_delete_confirm') }}')" style="margin:0;">
        @csrf @method('DELETE')
        <button type="submit" title="{{ __('shared-folder.delete') }}" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:14px;padding:2px 4px;line-height:1;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">&times;</button>
    </form>
    @else
    <span title="{{ __('shared-folder.category_has_children') }}" style="color:#e5e7eb;font-size:14px;padding:2px 4px;line-height:1;cursor:not-allowed;">&times;</span>
    @endif
</div>
@foreach($node['children'] as $child)
    @include('shared-folder._tree_node', ['node' => $child, 'catBase' => $catBase, 'categoryId' => $categoryId])
@endforeach
