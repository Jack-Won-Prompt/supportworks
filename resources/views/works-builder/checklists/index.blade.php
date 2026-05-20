@extends('layouts.app')

@section('title', '표준 체크리스트 — '.$project->name)

@php
    $categories = [
        'html_structure' => 'HTML 구조', 'semantic' => '시맨틱', 'class_naming' => '클래스 네이밍',
        'design_tokens'  => '디자인 토큰','typography'=>'타이포', 'accessibility'=>'접근성',
    ];
@endphp

@section('breadcrumb')
    <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
    <span>›</span>
    <span style="color:#374151;font-weight:500;">표준 체크리스트</span>
@endsection

@section('content')
<div class="pt-4">
    <p class="text-sm text-gray-500 mb-5">모든 웍스 호출에 자동 포함됩니다 (명세 v11 §1.10).</p>

    @if (session('status'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-sm mb-4">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <aside class="md:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <h2 class="font-semibold mb-2 text-sm text-gray-900">카테고리</h2>
                <ul class="text-sm space-y-1">
                    <li>
                        <a href="{{ route('wb.checklists.index', $project) }}"
                           class="flex justify-between px-2 py-1 rounded {{ !$category ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}">
                            <span>전체</span>
                            <span class="text-xs text-gray-400">{{ $counts->sum('total_cnt') }}</span>
                        </a>
                    </li>
                    @foreach ($categories as $key => $label)
                        @php $c = $counts->get($key); @endphp
                        <li>
                            <a href="{{ route('wb.checklists.index', $project) }}?category={{ $key }}"
                               class="flex justify-between px-2 py-1 rounded {{ $category===$key ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}">
                                <span>{{ $label }}</span>
                                <span class="text-xs text-gray-400">{{ $c?->active_cnt ?? 0 }} / {{ $c?->total_cnt ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <h2 class="font-semibold mb-3 text-sm text-gray-900">새 항목 추가</h2>
                <form method="POST" action="{{ route('wb.checklists.store', $project) }}" class="space-y-2">
                    @csrf
                    <select name="category" required class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="title" required placeholder="제목" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
                    <textarea name="description" rows="2" placeholder="설명 (선택)" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm"></textarea>
                    <textarea name="check_prompt_text" rows="3" required placeholder="웍스 프롬프트에 그대로 들어가는 문장 *" class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm"></textarea>
                    <button type="submit" class="w-full px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">+ 추가</button>
                </form>
            </div>
        </aside>

        <div class="md:col-span-3 bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">카테고리</th>
                        <th class="px-4 py-3 text-left">제목 / 프롬프트</th>
                        <th class="px-4 py-3 text-center">활성</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $i)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 align-top">
                                <span class="text-xs px-2 py-0.5 bg-gray-100 rounded-full">{{ $categories[$i->category] ?? $i->category }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $i->title }}</div>
                                @if ($i->description)
                                    <div class="text-xs text-gray-500">{{ $i->description }}</div>
                                @endif
                                <div class="text-xs text-gray-600 mt-1 font-mono">{{ $i->check_prompt_text }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('wb.checklists.toggle', ['project'=>$project,'item'=>$i]) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="px-2 py-1 text-xs rounded-full {{ $i->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $i->is_active ? 'ON' : 'OFF' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">아직 등록된 체크 항목이 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
