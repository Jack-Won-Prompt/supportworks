@extends('layouts.app')

@section('title', __('projects.edit_project'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('common.edit') }}</span>
@endsection

@section('content')
<div class="max-w-2xl pt-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.project_name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('common.description') }}</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $project->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.status') }} <span class="text-red-500">*</span></label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="active" {{ old('status', $project->status) === 'active' ? 'selected' : '' }}>{{ __('projects.status_active') }}</option>
                    <option value="on_hold" {{ old('status', $project->status) === 'on_hold' ? 'selected' : '' }}>{{ __('projects.status_on_hold') }}</option>
                    <option value="completed" {{ old('status', $project->status) === 'completed' ? 'selected' : '' }}>{{ __('projects.status_completed') }}</option>
                    <option value="cancelled" {{ old('status', $project->status) === 'cancelled' ? 'selected' : '' }}>{{ __('projects.status_cancelled') }}</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.start_date') }}</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ __('projects.end_date') }}</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-sm font-medium text-gray-700 mb-3">{{ __('projects.client_info_edit') }}</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('projects.client_name') }}</label>
                        <input type="text" name="client_name" value="{{ old('client_name', $project->client_name) }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('projects.client_email') }}</label>
                        <input type="email" name="client_email" value="{{ old('client_email', $project->client_email) }}"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            {{-- SI 계약 모드 --}}
            <div style="border-top:1px solid #f3f4f6;padding-top:16px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="si_mode_enabled" value="1"
                           {{ old('si_mode_enabled', $project->si_mode_enabled) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--t500);">
                    <div>
                        <span style="font-size:13px;font-weight:600;color:#374151;">{{ __('projects.si_mode_title') }}</span>
                        <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">{{ __('projects.si_mode_desc') }}</p>
                    </div>
                </label>
            </div>

            {{-- SM 유지보수 모드 --}}
            <div style="border-top:1px solid #f3f4f6;padding-top:16px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="sm_mode_enabled" value="1"
                           {{ old('sm_mode_enabled', $project->sm_mode_enabled) ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--t500);">
                    <div>
                        <span style="font-size:13px;font-weight:600;color:#374151;">{{ __('projects.sm_mode_title') }}</span>
                        <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">{{ __('projects.sm_mode_desc') }}</p>
                    </div>
                </label>
            </div>

            {{-- 웍스 분석 모델 기본값 --}}
            <div style="border-top:1px solid #f3f4f6;padding-top:16px;">
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 10px;">{{ __('projects.works_model_title') }}</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:11px;color:#6b7280;margin-bottom:4px;">{{ __('projects.llm_provider') }}</label>
                        <select name="preferred_llm_provider" id="edit_llm_provider"
                                style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;"
                                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"
                                onchange="editUpdateModels(this.value)">
                            @foreach(\App\Models\AnalysisSession::PROVIDER_MODELS as $prov => $models)
                                <option value="{{ $prov }}"
                                    {{ old('preferred_llm_provider', $project->preferred_llm_provider ?? 'anthropic') === $prov ? 'selected' : '' }}>
                                    {{ ucfirst($prov) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#6b7280;margin-bottom:4px;">{{ __('projects.llm_model') }}</label>
                        <select name="preferred_llm_model" id="edit_llm_model"
                                style="width:100%;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;"
                                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                            @php
                                $editProvider   = old('preferred_llm_provider', $project->preferred_llm_provider ?? 'anthropic');
                                $editModels     = \App\Models\AnalysisSession::PROVIDER_MODELS[$editProvider] ?? [];
                                $selectedModel  = old('preferred_llm_model', $project->preferred_llm_model ?? ($editModels[1] ?? ''));
                            @endphp
                            @foreach($editModels as $m)
                                <option value="{{ $m }}" {{ $selectedModel === $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">{{ __('common.save') }}</button>
                <a href="{{ route('projects.show', $project) }}" class="px-6 py-2.5 text-gray-600 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">{{ __('common.cancel') }}</a>
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="ml-auto"
                      onsubmit="return confirm('{{ __('projects.delete_project') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2.5 text-red-600 text-sm font-medium rounded-lg border border-red-200 hover:bg-red-50">{{ __('common.delete') }}</button>
                </form>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
const EDIT_PROVIDER_MODELS = @json(\App\Models\AnalysisSession::PROVIDER_MODELS);
async function editUpdateModels(provider) {
    const sel = document.getElementById('edit_llm_model');
    sel.innerHTML = '';
    (EDIT_PROVIDER_MODELS[provider] || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = opt.textContent = m;
        sel.appendChild(opt);
    });
}
</script>
@endsection
