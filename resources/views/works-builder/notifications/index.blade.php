@extends('layouts.app')

@section('title', 'Works Builder 알림 센터')

@section('header-actions')
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('wb.notifications.mark-all-read') }}">
            @csrf
            <button class="px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">전부 읽음 처리</button>
        </form>
    @endif
@endsection

@section('content')
<div class="pt-4 max-w-3xl">
    <p class="text-sm text-gray-500 mb-5">
        읽지 않은 알림: <span class="font-medium text-rose-600">{{ $unreadCount }}</span>건
    </p>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($notifications->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
            <p class="text-gray-400 text-sm">알림이 없습니다.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-100 overflow-hidden">
            @foreach ($notifications as $n)
                <form method="POST" action="{{ route('wb.notifications.read', $n) }}" class="block">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-start gap-3
                                   {{ $n->is_read ? 'opacity-60' : '' }}">
                        <span class="mt-1 flex-shrink-0 w-2 h-2 rounded-full
                                     {{ $n->is_read ? 'bg-gray-300' : 'bg-rose-500' }}"></span>
                        <div class="flex-1">
                            <div class="flex justify-between items-baseline">
                                <span class="font-medium text-sm">{{ $n->title }}</span>
                                <span class="text-[10px] text-gray-400">{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $n->message }}</p>
                            <div class="flex gap-2 mt-1 text-[10px] text-gray-400">
                                <span class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $n->stage_code }}</span>
                                @if ($n->task_id)
                                    <span>Task #{{ $n->task_id }}</span>
                                @endif
                                @if ($n->review_round)
                                    <span>{{ $n->review_round }}차수</span>
                                @endif
                            </div>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
