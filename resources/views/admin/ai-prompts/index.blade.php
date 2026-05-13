@extends('layouts.admin')

@section('title', __('admin.ai_prompts'))

@section('content')
<div class="p-6 max-w-7xl mx-auto">

    {{-- 헤더 --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">{{ __('admin.ai_prompts') }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">{{ __('admin.aiprompt_all_sessions') }}</p>
    </div>

    {{-- 통계 카드 --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_sessions']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.aiprompt_total_sessions') }}</div>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_prompts']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.aiprompt_total_messages') }}</div>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center text-sky-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_users']) }}</div>
                <div class="text-xs text-slate-500">{{ __('admin.aiprompt_total_users') }}</div>
            </div>
        </div>
    </div>

    {{-- 필터 --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-52">
                <input name="search" value="{{ $search }}" placeholder="{{ __('admin.aiprompt_search_placeholder') }}"
                    class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            <select name="provider" onchange="this.form.submit()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">{{ __('admin.aiprompt_all_providers') }}</option>
                @foreach($providers as $p)
                <option value="{{ $p }}" {{ $provider === $p ? 'selected' : '' }}>
                    {{ match($p) { 'claude' => '🟠 Claude', 'openai' => '🟢 OpenAI', 'manus' => '🔵 Manus', default => $p } }}
                </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $dateFrom }}"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <input type="date" name="date_to" value="{{ $dateTo }}"
                class="text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">

            <button type="submit"
                class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">{{ __('admin.search_btn') }}</button>
            @if($search || $provider || $userId || $dateFrom || $dateTo)
            <a href="{{ route('admin.ai-prompts.index') }}"
               class="px-3 py-2 text-sm text-slate-500 border border-slate-200 rounded-lg hover:bg-slate-50 transition">{{ __('admin.aiprompt_reset_filter') }}</a>
            @endif
        </form>
    </div>

    {{-- 세션 목록 --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50 text-xs text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3 text-left font-semibold">{{ __('admin.aiprompt_user_label') }}</th>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.aiprompt_col_session') }}</th>
                    <th class="px-4 py-3 text-left font-semibold">{{ __('admin.aiprompt_col_last_prompt') }}</th>
                    <th class="px-4 py-3 text-center font-semibold w-20">{{ __('admin.aiprompt_col_msg_count') }}</th>
                    <th class="px-4 py-3 text-left font-semibold w-28">{{ __('admin.aiprompt_col_ai') }}</th>
                    <th class="px-4 py-3 text-left font-semibold w-36">{{ __('admin.aiprompt_col_date') }}</th>
                    <th class="px-4 py-3 w-16"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                @php
                    $lastUserMsg = $session->messages->where('role', 'user')->last();
                    $providers   = $session->messages->whereNotNull('ai_provider')->pluck('ai_provider')->unique();
                @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                    <td class="px-5 py-3.5">
                        @if($session->user)
                        <a href="{{ route('admin.ai-prompts.index', ['user_id' => $session->user_id]) }}"
                           class="flex items-center gap-2 group">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xs shrink-0">
                                {{ mb_substr($session->user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-slate-700 group-hover:text-indigo-600 transition text-xs">{{ $session->user->name }}</div>
                                <div class="text-slate-400 text-xs">{{ $session->user->email }}</div>
                            </div>
                        </a>
                        @else
                        <span class="text-slate-400 text-xs">{{ __('admin.aiprompt_withdrawn_user') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 max-w-[160px]">
                        <span class="text-slate-700 truncate block text-xs" title="{{ $session->title }}">{{ $session->title }}</span>
                    </td>
                    <td class="px-4 py-3.5 max-w-xs">
                        @if($lastUserMsg)
                        <span class="text-slate-600 line-clamp-2 text-xs leading-relaxed">{{ $lastUserMsg->content }}</span>
                        @else
                        <span class="text-slate-300 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center w-8 h-6 text-xs font-semibold rounded-full
                            {{ $session->user_messages_count > 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-50 text-slate-400' }}">
                            {{ $session->user_messages_count }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5">
                        <div class="flex flex-wrap gap-1">
                            @foreach($providers as $p)
                            @php
                                $badge = match($p) {
                                    'claude' => 'bg-orange-50 text-orange-600',
                                    'openai' => 'bg-green-50 text-green-600',
                                    'manus'  => 'bg-blue-50 text-blue-600',
                                    default  => 'bg-slate-50 text-slate-500',
                                };
                            @endphp
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $badge }}">
                                {{ ucfirst($p) }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-xs text-slate-400">
                        <div>{{ $session->created_at->format('Y-m-d') }}</div>
                        <div>{{ $session->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-4 py-3.5">
                        <a href="{{ route('admin.ai-prompts.show', $session) }}"
                           class="p-1.5 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition inline-flex">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-16 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="font-medium">{{ __('admin.aiprompt_no_sessions') }}</p>
                        <p class="text-sm mt-1">{{ __('admin.aiprompt_no_match') }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="mt-4">
        {{ $sessions->links() }}
    </div>
    @endif

</div>
@endsection
