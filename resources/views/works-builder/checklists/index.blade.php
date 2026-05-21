@extends('layouts.app')

@section('title', '표준 체크리스트 — '.$project->name)

@php
    $categories = [
        'html_structure' => 'HTML 구조', 'semantic' => '시맨틱', 'class_naming' => '클래스 네이밍',
        'design_tokens'  => '디자인 토큰','typography'=>'타이포', 'accessibility'=>'접근성',
    ];
    $totalActive = $counts->sum('active_cnt');
    $totalAll    = $counts->sum('total_cnt');
@endphp

@section('breadcrumb')
    <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">표준 체크리스트</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- 헤더 카드 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h2 class="text-xl font-bold text-gray-900">표준 체크리스트</h2>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">{{ $project->name }}</span>
            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">활성 {{ $totalActive }} / {{ $totalAll }}</span>
        </div>
        <p class="text-sm text-gray-500">활성화된 체크 항목은 모든 웍스 호출에 자동 포함됩니다 (명세 v11 §1.10).</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- 사이드 --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- 카테고리 --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z"/></svg>
                        카테고리
                    </h3>
                </div>
                <ul class="space-y-1 text-sm">
                    <li>
                        <a href="{{ route('wb.checklists.index', $project) }}"
                           class="flex justify-between items-center px-3 py-2 rounded-lg transition-colors {{ !$category ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                            <span>전체</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $totalAll }}</span>
                        </a>
                    </li>
                    @foreach ($categories as $key => $label)
                        @php $c = $counts->get($key); @endphp
                        <li>
                            <a href="{{ route('wb.checklists.index', $project) }}?category={{ $key }}"
                               class="flex justify-between items-center px-3 py-2 rounded-lg transition-colors {{ $category===$key ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                <span>{{ $label }}</span>
                                <span class="text-xs text-gray-400 font-mono">{{ $c?->active_cnt ?? 0 }} / {{ $c?->total_cnt ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- 새 항목 --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        새 항목 추가
                    </h3>
                </div>
                <form method="POST" action="{{ route('wb.checklists.store', $project) }}" class="space-y-3">
                    @csrf
                    <select name="category" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="title" required placeholder="제목"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <textarea name="description" rows="2" placeholder="설명 (선택)"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <textarea name="check_prompt_text" rows="3" required placeholder="웍스 프롬프트에 들어갈 문장 *"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        추가
                    </button>
                </form>
            </div>
        </div>

        {{-- 항목 목록 --}}
        <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="text-indigo-500"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    체크 항목
                    <span class="text-xs font-normal text-gray-400">({{ $items->count() }}{{ $category ? ' / '.$totalAll : '' }})</span>
                </h3>
                @if ($category)
                    <a href="{{ route('wb.checklists.index', $project) }}" class="text-xs text-gray-500 hover:text-gray-700">필터 해제 ✕</a>
                @endif
            </div>

            @if ($items->isEmpty())
                <p class="text-xs text-gray-400 text-center py-10">아직 등록된 체크 항목이 없습니다.</p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs">
                            <tr class="border-t border-b border-gray-100">
                                <th class="px-5 py-2.5 text-left font-medium">카테고리</th>
                                <th class="px-5 py-2.5 text-left font-medium">제목 / 프롬프트</th>
                                <th class="px-5 py-2.5 text-center font-medium">활성</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $i)
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors align-top">
                                    <td class="px-5 py-3">
                                        <span class="inline-block text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">{{ $categories[$i->category] ?? $i->category }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-gray-800">{{ $i->title }}</div>
                                        @if ($i->description)
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $i->description }}</div>
                                        @endif
                                        <div class="text-xs text-gray-600 mt-1.5 font-mono bg-gray-50 border border-gray-100 rounded p-2">{{ $i->check_prompt_text }}</div>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <form method="POST" action="{{ route('wb.checklists.toggle', ['project'=>$project,'item'=>$i]) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full {{ $i->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                {{ $i->is_active ? 'ON' : 'OFF' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
