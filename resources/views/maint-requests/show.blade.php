@extends('layouts.app')

@section('title', '요청 #' . $r->id)

@section('header-actions')
    <a href="{{ route('maint-requests.index') }}" class="px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">← 목록</a>
@endsection

@section('content')
<div class="pt-4 px-6 pb-12 max-w-6xl mx-auto">
    @include('maint-requests._form', ['isEmbed' => false])
</div>
@endsection
