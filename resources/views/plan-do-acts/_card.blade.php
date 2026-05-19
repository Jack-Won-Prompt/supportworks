@php
    $sc = $it->statusColors();
    $phases = [
        ['Plan', $it->plan, '#2563eb', '#eff6ff'],
        ['Do',   $it->do,   '#b45309', '#fffbeb'],
        ['Act',  $it->act,  '#7c3aed', '#f5f3ff'],
    ];
@endphp
<div onclick="pdaOpenEdit({{ $it->id }})"
     style="background:#fff;border-radius:14px;border:1px solid #f3f4f6;padding:16px 20px;cursor:pointer;transition:box-shadow .12s,border-color .12s;"
     onmouseover="this.style.boxShadow='0 6px 20px rgba(124,58,237,.1)';this.style.borderColor='#ddd6fe'"
     onmouseout="this.style.boxShadow='none';this.style.borderColor='#f3f4f6'">
    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:3px;">
                @if(!empty($showProject) && $it->project)
                    <span style="font-size:10.5px;font-weight:700;color:#7c3aed;background:#f5f3ff;border-radius:6px;padding:2px 7px;">{{ $it->project->name }}</span>
                @endif
                @if($it->source_file_comment_id)
                    <span style="font-size:10.5px;font-weight:600;color:#6b7280;background:#f3f4f6;border-radius:6px;padding:2px 7px;display:inline-flex;align-items:center;gap:3px;">
                        <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        의견 연동
                    </span>
                @endif
            </div>
            <div style="font-size:14px;font-weight:700;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $it->title }}</div>
        </div>
        <span style="flex-shrink:0;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;background:{{ $sc['bg'] }};color:{{ $sc['fg'] }};">{{ $it->status_label }}</span>
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($phases as [$name, $text, $fg, $bg])
            <div style="flex:1;min-width:150px;background:{{ $bg }};border-radius:8px;padding:8px 10px;">
                <div style="font-size:10px;font-weight:800;color:{{ $fg }};letter-spacing:.04em;margin-bottom:2px;">{{ $name }}</div>
                <div style="font-size:11.5px;color:{{ trim((string)$text) === '' ? '#c4c4cc' : '#4b5563' }};line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ trim((string)$text) !== '' ? $text : '—' }}</div>
            </div>
        @endforeach
    </div>

    <div style="margin-top:9px;font-size:11px;color:#9ca3af;display:flex;gap:8px;flex-wrap:wrap;">
        <span>{{ $it->author?->name ?? '—' }}</span>
        <span>·</span>
        <span>{{ optional($it->updated_at)->format('Y-m-d H:i') }}</span>
    </div>
</div>
