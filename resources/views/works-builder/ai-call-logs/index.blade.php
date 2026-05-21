@extends('layouts.app')

@section('title', '웍스 호출 이력')

@section('content')
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">웍스 호출 이력</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">{{ number_format($totals['count']) }}건</span>
        </div>
        <p class="text-sm text-gray-500">웍스 호출별 토큰·비용·응답 시간을 한눈에 확인합니다.</p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50">
            <div>
                <p class="text-xs text-gray-400 mb-1">전체 호출</p>
                <p class="text-sm font-medium text-gray-700">{{ number_format($totals['count']) }}회</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">누적 토큰</p>
                <p class="text-sm font-medium text-gray-700">{{ number_format($totals['tokens']) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">누적 비용</p>
                <p class="text-sm font-medium text-gray-700">${{ number_format($totals['cost'], 4) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">표시 개수</p>
                <p class="text-sm font-medium text-gray-700">{{ $logs->count() }} / {{ $logs->total() }}</p>
            </div>
        </div>
    </div>

    {{-- 필터 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                필터
            </h3>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-3 text-sm">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">상태 (전체)</option>
                <option value="success" @selected(request('status')==='success')>성공</option>
                <option value="failed"  @selected(request('status')==='failed')>실패</option>
                <option value="cancelled" @selected(request('status')==='cancelled')>취소</option>
            </select>
            <select name="provider" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Provider (전체)</option>
                <option value="claude" @selected(request('provider')==='claude')>Claude</option>
                <option value="openai" @selected(request('provider')==='openai')>OpenAI</option>
                <option value="none"   @selected(request('provider')==='none')>없음</option>
            </select>
            <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 px-3 py-2">
                <input type="checkbox" name="fallback" value="1" @checked(request('fallback')==='1') class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                폴백만
            </label>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                적용
            </button>
            @if(request()->hasAny(['status','provider','fallback']))
                <a href="{{ route('wb.ai-call-logs.index') }}" class="px-3 py-2 text-gray-500 rounded-lg text-sm hover:bg-gray-100">초기화</a>
            @endif
        </form>
    </div>

    {{-- 목록 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h6m0 0l-3-3m3 3l-3 3M3 12a9 9 0 1018 0 9 9 0 00-18 0z"/></svg>
                호출 목록
                <span class="text-xs font-normal text-gray-400">({{ $logs->total() }})</span>
            </h3>
        </div>

        @if ($logs->isEmpty())
            <p class="text-xs text-gray-400 text-center py-10">호출 이력이 없습니다.</p>
        @else
            <div class="overflow-x-auto -mx-5">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr class="border-t border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left font-medium">#</th>
                            <th class="px-5 py-2.5 text-left font-medium">Task</th>
                            <th class="px-5 py-2.5 text-left font-medium">단계</th>
                            <th class="px-5 py-2.5 text-left font-medium">상태</th>
                            <th class="px-5 py-2.5 text-left font-medium">Provider</th>
                            <th class="px-5 py-2.5 text-right font-medium">tokens</th>
                            <th class="px-5 py-2.5 text-right font-medium">cost</th>
                            <th class="px-5 py-2.5 text-right font-medium">ms</th>
                            <th class="px-5 py-2.5 text-left font-medium">시각</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $l)
                            @php
                                $sb = match($l->status) {
                                    'success' => ['bg-green-100 text-green-700','성공'],
                                    'failed'  => ['bg-red-100 text-red-700','실패'],
                                    'cancelled' => ['bg-gray-200 text-gray-700','취소'],
                                    default   => ['bg-gray-100 text-gray-700',$l->status],
                                };
                            @endphp
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-gray-400">#{{ $l->id }}</td>
                                <td class="px-5 py-3"><a href="{{ route('wb.tasks.show', $l->task) }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">#{{ $l->task_id }}</a></td>
                                <td class="px-5 py-3 text-xs text-gray-600">{{ $l->stage }}@if ($l->review_round !== null) · {{ $l->review_round }}차 @endif</td>
                                <td class="px-5 py-3">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $sb[0] }}">{{ $sb[1] }}</span>
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $l->final_provider === 'claude' ? 'bg-purple-100 text-purple-700' : ($l->final_provider === 'openai' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $l->final_provider ?: '—' }}
                                    </span>
                                    @if ($l->fallback_used)
                                        <span class="ml-1 inline-block text-[10px] text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded-full">⚡ fallback</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-mono text-gray-700">{{ number_format($l->total_tokens ?? 0) }}</td>
                                <td class="px-5 py-3 text-right text-xs font-mono text-gray-700">${{ number_format((float)($l->estimated_cost_usd ?? 0), 4) }}</td>
                                <td class="px-5 py-3 text-right text-xs font-mono text-gray-500">{{ $l->response_time_ms ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-gray-500">{{ $l->created_at?->format('m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-4">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
