@extends('layouts.app')

@section('title', __('maintenance.meeting_edit'))

@section('content')
<div style="max-width:780px;margin:0 auto;padding:24px;">

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
        <ul style="margin:0;padding:0 0 0 16px;font-size:13px;color:#dc2626;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('meeting-minutes.update', $meetingMinute) }}">
        @csrf
        @method('PATCH')
        @include('meeting-minutes._form', ['minute' => $meetingMinute])

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;">
            <a href="{{ route('meeting-minutes.show', $meetingMinute) }}"
               style="padding:9px 20px;border:1.5px solid #e8e3ff;border-radius:9px;font-size:13px;font-weight:600;color:#64748b;text-decoration:none;">{{ __('common.cancel') }}</a>
            <button type="submit"
                    style="padding:9px 24px;background:var(--t600);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;">
                {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
