@extends('layouts.app')

@section('title', '웍스 요구사항 분석')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">

    {{-- 헤더 --}}
    <div class="mb-6">
        <a href="{{ route('projects.requirements.index', $project) }}"
           class="text-sm text-blue-600 hover:underline">&larr; 요구사항 목록</a>
        <h1 class="text-2xl font-bold mt-2">웍스 요구사항 분석</h1>
        <p class="text-sm text-gray-500 mt-1">문서를 업로드하거나 텍스트를 입력하면 웍스가 요구사항 항목을 추출합니다.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            @foreach ($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ route('projects.requirements.analysis.store', $project) }}"
          enctype="multipart/form-data"
          class="space-y-6 bg-white border border-gray-200 rounded-lg p-6">
        @csrf

        {{-- 파일 업로드 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">문서 파일 (선택)</label>
            <div id="drop-zone"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-400 transition-colors"
                 onclick="document.getElementById('file-input').click()">
                <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-sm text-gray-500">클릭하거나 파일을 드래그하세요</p>
                <p class="text-xs text-gray-400 mt-1">docx · xlsx · pptx · pdf · txt · md (최대 20MB, 10개)</p>
            </div>
            <input id="file-input" type="file" name="files[]"
                   multiple accept=".docx,.xlsx,.xls,.pptx,.ppt,.pdf,.txt,.md,.csv,.log"
                   class="hidden" onchange="updateFileList(this)">
            <ul id="file-list" class="mt-2 space-y-1 text-sm text-gray-700"></ul>
        </div>

        {{-- 컨텍스트 메모 --}}
        <div>
            <label for="context_note" class="block text-sm font-medium text-gray-700 mb-1">
                분석 컨텍스트 메모 <span class="text-gray-400">(선택)</span>
            </label>
            <textarea id="context_note" name="context_note" rows="4"
                      maxlength="2000"
                      placeholder="프로젝트 배경, 분석 목적, 제외할 내용 등을 입력하세요."
                      class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none"
            >{{ old('context_note') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">파일 없이 메모만 입력해도 분석이 가능합니다.</p>
        </div>

        {{-- LLM 설정 --}}
        <div class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-3">웍스 모델 설정</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">제공자</label>
                    <select name="llm_provider" id="llm_provider"
                            class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            onchange="updateModels(this.value)">
                        @foreach(\App\Models\AnalysisSession::PROVIDER_MODELS as $prov => $models)
                            <option value="{{ $prov }}" {{ $provider === $prov ? 'selected' : '' }}>
                                {{ ucfirst($prov) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">모델</label>
                    <select name="llm_model" id="llm_model"
                            class="w-full text-sm border border-gray-300 rounded px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        @foreach(\App\Models\AnalysisSession::PROVIDER_MODELS[$provider] as $m)
                            <option value="{{ $m }}" {{ $model === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('projects.requirements.index', $project) }}"
               class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50">취소</a>
            <button type="submit"
                    class="px-5 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                분석 시작
            </button>
        </div>
    </form>
</div>

<script>
const PROVIDER_MODELS = @json(\App\Models\AnalysisSession::PROVIDER_MODELS);

function updateModels(provider) {
    const sel = document.getElementById('llm_model');
    sel.innerHTML = '';
    (PROVIDER_MODELS[provider] || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = opt.textContent = m;
        sel.appendChild(opt);
    });
}

function updateFileList(input) {
    const ul = document.getElementById('file-list');
    ul.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-1 text-xs text-gray-600';
        li.innerHTML = `<svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>${f.name} <span class="text-gray-400">(${(f.size/1024).toFixed(0)} KB)</span>`;
        ul.appendChild(li);
    });
}

// 드래그 앤 드롭
const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-blue-500'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-500'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500');
    const input = document.getElementById('file-input');
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
    input.files = dt.files;
    updateFileList(input);
});
</script>
@endsection
