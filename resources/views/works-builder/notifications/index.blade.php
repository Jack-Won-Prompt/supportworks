@extends('layouts.app')

@section('title', 'Works Builder 알림 센터')

@section('header-actions')@endsection

@php
    $totalCount = $notifications->total();
    $readCount  = $totalCount - $unreadCount;
@endphp

@section('content')
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">알림 센터</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-rose-100 text-rose-700">읽지 않음 {{ $unreadCount }}</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">전체 {{ $totalCount }}</span>
        </div>
        <p class="text-sm text-gray-500">Works Builder 단계 전환·검수·완료 알림이 모입니다.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">전체</p>
                <p class="text-sm font-medium text-gray-700">{{ $totalCount }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">읽지 않음</p>
                <p class="text-sm font-medium {{ $unreadCount > 0 ? 'text-rose-600' : 'text-gray-700' }}">{{ $unreadCount }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">읽음</p>
                <p class="text-sm font-medium text-gray-700">{{ $readCount }}건</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">표시 개수</p>
                <p class="text-sm font-medium text-gray-700">{{ $notifications->count() }} / {{ $totalCount }}</p>
            </div>
        </div>
    </div>

    {{-- session flash 는 전역 토스트(window.appToast)로 표시됨 --}}

    {{-- 알림 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                알림 목록
                <span class="text-xs font-normal text-gray-400">({{ $totalCount }})</span>
            </h3>
            <div class="flex items-center gap-2">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('wb.notifications.mark-all-read') }}">
                        @csrf
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            전부 읽음 처리
                        </button>
                    </form>
                @endif
                @if ($readCount > 0)
                    <form method="POST" action="{{ route('wb.notifications.destroy-all') }}"
                          onsubmit="return confirm('읽은 알림을 모두 삭제하시겠습니까?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="scope" value="read">
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            읽은 알림 삭제
                        </button>
                    </form>
                @endif
                @if ($totalCount > 0)
                    <form method="POST" action="{{ route('wb.notifications.destroy-all') }}">
                        @csrf @method('DELETE')
                        <input type="hidden" name="scope" value="all">
                        <button type="submit"
                                data-confirm="모든 알림을 삭제하시겠습니까? 되돌릴 수 없습니다."
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs border border-rose-200 text-rose-600 rounded-lg hover:bg-rose-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            전체 삭제
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($notifications->isEmpty())
            <p class="text-xs text-gray-400 text-center py-10">알림이 없습니다.</p>
        @else
            <div class="divide-y divide-gray-50 -mx-5">
                @foreach ($notifications as $n)
                    <div class="relative group">
                        <form method="POST" action="{{ route('wb.notifications.read', $n) }}" class="block">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-5 py-3 pr-12 hover:bg-gray-50 flex items-start gap-3 transition-colors
                                           {{ $n->is_read ? 'opacity-60' : '' }}">
                                <span class="mt-1.5 flex-shrink-0 w-2 h-2 rounded-full
                                             {{ $n->is_read ? 'bg-gray-300' : 'bg-rose-500' }}"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline gap-2">
                                        <span class="font-medium text-sm text-gray-800 truncate">{{ $n->title }}</span>
                                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $n->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $n->message }}</p>
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-full bg-indigo-100 text-indigo-700">{{ $n->stage_code }}</span>
                                        @if ($n->task_id)
                                            <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-700">Task #{{ $n->task_id }}</span>
                                        @endif
                                        @if ($n->review_round)
                                            <span class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-full bg-amber-100 text-amber-700">{{ $n->review_round }}차수</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </form>
                        {{-- 개별 삭제 (행 우상단, 호버 시 진하게) --}}
                        <form method="POST" action="{{ route('wb.notifications.destroy', $n) }}"
                              onsubmit="return confirm('이 알림을 삭제하시겠습니까?')"
                              class="absolute top-3 right-3">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 rounded text-gray-300 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                    title="삭제">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="pt-4">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection
