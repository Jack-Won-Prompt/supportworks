@extends('layouts.app')

@section('title', '웍스 호출 이력')

@section('content')
<div class="pt-4">
    <p class="text-sm text-gray-500 mb-5">웍스 호출별 토큰·비용·응답 시간을 한눈에 확인합니다.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">전체 호출</div>
            <div class="text-2xl font-semibold">{{ number_format($totals['count']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">누적 토큰</div>
            <div class="text-2xl font-semibold">{{ number_format($totals['tokens']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-xs text-gray-500 mb-1">누적 비용</div>
            <div class="text-2xl font-semibold">${{ number_format($totals['cost'], 4) }}</div>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-4 text-sm">
        <select name="status" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">상태 (전체)</option>
            <option value="success" @selected(request('status')==='success')>성공</option>
            <option value="failed"  @selected(request('status')==='failed')>실패</option>
            <option value="cancelled" @selected(request('status')==='cancelled')>취소</option>
        </select>
        <select name="provider" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">Provider (전체)</option>
            <option value="claude" @selected(request('provider')==='claude')>Claude</option>
            <option value="openai" @selected(request('provider')==='openai')>OpenAI</option>
            <option value="none"   @selected(request('provider')==='none')>없음</option>
        </select>
        <label class="flex items-center gap-1 text-sm">
            <input type="checkbox" name="fallback" value="1" @checked(request('fallback')==='1')> 폴백만
        </label>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">필터</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Task</th>
                    <th class="px-4 py-3 text-left">단계</th>
                    <th class="px-4 py-3 text-left">상태</th>
                    <th class="px-4 py-3 text-left">Provider</th>
                    <th class="px-4 py-3 text-right">tokens</th>
                    <th class="px-4 py-3 text-right">cost</th>
                    <th class="px-4 py-3 text-right">ms</th>
                    <th class="px-4 py-3 text-left">시각</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $l->id }}</td>
                        <td class="px-4 py-3"><a href="{{ route('wb.tasks.show', $l->task) }}" class="text-indigo-600 hover:underline">#{{ $l->task_id }}</a></td>
                        <td class="px-4 py-3 text-xs">{{ $l->stage }}@if ($l->review_round !== null) · {{ $l->review_round }}차수 @endif</td>
                        <td class="px-4 py-3">
                            @php $sb = match($l->status) {
                                'success' => ['bg-emerald-100 text-emerald-800','성공'],
                                'failed'  => ['bg-red-100 text-red-800','실패'],
                                'cancelled' => ['bg-gray-200 text-gray-700','취소'],
                                default   => ['bg-gray-100 text-gray-700',$l->status]};
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $sb[0] }}">{{ $sb[1] }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            {{ $l->final_provider }}
                            @if ($l->fallback_used)
                                <span class="ml-1 text-amber-600">⚡fallback</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-xs">{{ number_format($l->total_tokens ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-xs">${{ number_format((float)($l->estimated_cost_usd ?? 0), 4) }}</td>
                        <td class="px-4 py-3 text-right text-xs">{{ $l->response_time_ms ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $l->created_at?->format('m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">호출 이력이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
