@extends('layouts.app')

@section('title', '{{ __("work.memo_new") }}')

@section('header-actions')
<button onclick="document.getElementById('memo-add-form').classList.toggle('hidden')"
    style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:var(--t600);color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    {{ __('work.memo_new') }}
</button>
@endsection

@section('content')
@php
$colorMap = [
    'yellow' => ['bg'=>'#fef9c3','border'=>'#fde047','label'=>__('work.memo_color_yellow')],
    'green'  => ['bg'=>'#dcfce7','border'=>'#86efac','label'=>__('work.memo_color_green')],
    'blue'   => ['bg'=>'#dbeafe','border'=>'#93c5fd','label'=>__('work.memo_color_blue')],
    'pink'   => ['bg'=>'#fce7f3','border'=>'#f9a8d4','label'=>__('work.memo_color_pink')],
    'purple' => ['bg'=>'#ede9fe','border'=>'#c4b5fd','label'=>__('work.memo_color_purple')],
];
@endphp

{{-- 새 메모 작성 폼 --}}
<div id="memo-add-form" class="hidden mb-6">
    <form action="{{ route('memos.store') }}" method="POST">
        @csrf
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:18px;max-width:480px;box-shadow:0 4px 16px rgba(0,0,0,.07);">
            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:12px;">{{ __('work.memo_write_heading') }}</div>
            <input type="text" name="title" placeholder="{{ __('work.memo_title_placeholder') }}" value="{{ old('title') }}"
                style="width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;margin-bottom:8px;box-sizing:border-box;">
            <textarea name="content" rows="4" placeholder="{{ __('work.memo_content_placeholder') }}" required
                style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;resize:vertical;box-sizing:border-box;">{{ old('content') }}</textarea>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
                <div style="display:flex;gap:8px;align-items:center;">
                    <span style="font-size:12px;color:#9ca3af;">{{ __('work.memo_color_label') }}</span>
                    @foreach($colorMap as $key => $c)
                    <label style="cursor:pointer;" title="{{ $c['label'] }}">
                        <input type="radio" name="color" value="{{ $key }}" {{ $key==='yellow'?'checked':'' }} style="display:none;" class="color-radio-{{ $key }}">
                        <span class="color-swatch" data-color="{{ $key }}"
                            style="display:inline-block;width:20px;height:20px;border-radius:50%;background:{{ $c['bg'] }};border:2px solid {{ $c['border'] }};cursor:pointer;transition:transform .15s;"></span>
                    </label>
                    @endforeach
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="button" onclick="document.getElementById('memo-add-form').classList.add('hidden')"
                        style="padding:6px 12px;font-size:13px;border:1px solid #e5e7eb;border-radius:7px;background:#fff;color:#6b7280;cursor:pointer;">{{ __('common.cancel') }}</button>
                    <button type="submit"
                        style="padding:6px 16px;font-size:13px;background:var(--t600);color:#fff;border:none;border-radius:7px;cursor:pointer;font-weight:500;">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- 메모 그리드 --}}
@if($memos->isEmpty())
<div style="text-align:center;padding:60px 20px;">
    <div style="width:56px;height:56px;background:#f3f4f6;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <svg width="24" height="24" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    </div>
    <p style="font-size:14px;color:#9ca3af;">{{ __('work.memo_empty') }}</p>
    <button onclick="document.getElementById('memo-add-form').classList.remove('hidden')"
        style="margin-top:12px;padding:7px 18px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer;">{{ __('work.memo_first_write') }}</button>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
    @foreach($memos as $memo)
    @php $c = $colorMap[$memo->color] ?? $colorMap['yellow']; @endphp
    <div x-data="{ editing: false }" style="background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};border-radius:14px;padding:16px;position:relative;box-shadow:0 2px 8px rgba(0,0,0,.05);min-height:140px;display:flex;flex-direction:column;">

        {{-- 핀 + 삭제 버튼 --}}
        <div style="position:absolute;top:10px;right:10px;display:flex;gap:4px;">
            <form action="{{ route('memos.pin', $memo) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" title="{{ $memo->is_pinned ? __('work.memo_unpin') : __('work.memo_pin') }}"
                    style="width:24px;height:24px;border-radius:6px;border:none;background:rgba(0,0,0,.06);cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <svg width="12" height="12" fill="{{ $memo->is_pinned ? '#6d28d9' : 'none' }}" stroke="{{ $memo->is_pinned ? '#6d28d9' : '#9ca3af' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                </button>
            </form>
            <form action="{{ route('memos.destroy', $memo) }}" method="POST" onsubmit="return confirm('{{ __('work.memo_confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit" title="{{ __('common.delete') }}"
                    style="width:24px;height:24px;border-radius:6px;border:none;background:rgba(0,0,0,.06);cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <svg width="12" height="12" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </form>
        </div>

        {{-- 내용 보기 --}}
        <div x-show="!editing" @click="editing=true" style="flex:1;cursor:pointer;">
            @if($memo->title)
            <p style="font-size:13px;font-weight:700;color:#1f2937;margin-bottom:6px;padding-right:54px;">{{ $memo->title }}</p>
            @endif
            <p style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;word-break:break-word;">{{ $memo->content }}</p>
        </div>

        {{-- 편집 폼 --}}
        <div x-show="editing" style="flex:1;">
            <form action="{{ route('memos.update', $memo) }}" method="POST">
                @csrf @method('PATCH')
                <input type="text" name="title" value="{{ $memo->title }}" placeholder="{{ __('work.memo_title_edit_placeholder') }}"
                    style="width:100%;padding:4px 6px;border:1px solid {{ $c['border'] }};border-radius:6px;font-size:13px;background:rgba(255,255,255,.7);margin-bottom:6px;box-sizing:border-box;">
                <textarea name="content" rows="4" style="width:100%;padding:4px 6px;border:1px solid {{ $c['border'] }};border-radius:6px;font-size:13px;background:rgba(255,255,255,.7);resize:vertical;box-sizing:border-box;">{{ $memo->content }}</textarea>
                <input type="hidden" name="color" value="{{ $memo->color }}">
                <div style="display:flex;gap:8px;margin-top:6px;">
                    <button type="submit" style="flex:1;padding:5px;font-size:12px;background:var(--t600);color:#fff;border:none;border-radius:6px;cursor:pointer;">{{ __('common.save') }}</button>
                    <button type="button" @click="editing=false" style="flex:1;padding:5px;font-size:12px;background:rgba(0,0,0,.1);color:#374151;border:none;border-radius:6px;cursor:pointer;">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>

        {{-- 날짜 --}}
        <div x-show="!editing" style="margin-top:10px;font-size:11px;color:#6b7280;">
            {{ $memo->is_pinned ? '📌 ' : '' }}{{ $memo->updated_at->format('m/d H:i') }}
        </div>
    </div>
    @endforeach
</div>
@endif

<script>
document.querySelectorAll('.color-swatch').forEach(async function(swatch) {
    swatch.addEventListener('click', async function() {
        const color = this.dataset.color;
        document.querySelector('.color-radio-' + color).checked = true;
        document.querySelectorAll('.color-swatch').forEach(s => s.style.transform = 'scale(1)');
        this.style.transform = 'scale(1.3)';
    });
});
document.querySelector('.color-swatch[data-color="yellow"]').style.transform = 'scale(1.3)';
</script>
@endsection
