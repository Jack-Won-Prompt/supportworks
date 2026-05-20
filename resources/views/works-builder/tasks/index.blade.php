@extends('layouts.app')

@section('title', '진행 중 Task')

@section('header-actions')
    <a href="{{ route('wb.tasks.completed') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        완료된 Task
    </a>
    <a href="{{ route('wb.tasks.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        새 작업
    </a>
@endsection

@section('content')
<div class="pt-4">
    @if ($inProgress->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
            <p class="text-gray-400 text-sm">진행 중인 작업이 없습니다.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">프로젝트</th>
                        <th class="px-4 py-3 text-left">모드</th>
                        <th class="px-4 py-3 text-left">현재 단계</th>
                        <th class="px-4 py-3 text-center">검수 차수</th>
                        <th class="px-4 py-3 text-left">시작일</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inProgress as $task)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $task->id }}</td>
                            <td class="px-4 py-3">{{ $task->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $task->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $task->mode === 'new' ? '신규' : '고도화' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700">{{ $task->current_stage }}</td>
                            <td class="px-4 py-3 text-center">{{ $task->current_review_round }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $task->started_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('wb.tasks.show', $task) }}" class="text-indigo-600 hover:underline text-xs">상세</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $inProgress->links() }}</div>
    @endif
</div>
@endsection
