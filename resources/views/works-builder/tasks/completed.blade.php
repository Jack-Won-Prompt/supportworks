@extends('layouts.app')

@section('title', '완료된 Task')

@section('header-actions')
    <a href="{{ route('wb.tasks.index') }}"
       class="inline-flex items-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">
        ← 진행 중 Task
    </a>
@endsection

@section('content')
<div class="pt-4">
    @if ($completed->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
            <p class="text-gray-400 text-sm">완료된 작업이 없습니다.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">프로젝트</th>
                        <th class="px-4 py-3 text-left">모드</th>
                        <th class="px-4 py-3 text-center">검수 차수</th>
                        <th class="px-4 py-3 text-left">시작일</th>
                        <th class="px-4 py-3 text-left">완료일</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($completed as $task)
                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $task->id }}</td>
                            <td class="px-4 py-3">{{ $task->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $task->mode === 'new' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $task->mode === 'new' ? '신규' : '고도화' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">{{ $task->current_review_round }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $task->started_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-xs text-emerald-700">{{ $task->completed_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('wb.tasks.show', $task) }}" class="text-indigo-600 hover:underline text-xs">상세</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $completed->links() }}</div>
    @endif
</div>
@endsection
