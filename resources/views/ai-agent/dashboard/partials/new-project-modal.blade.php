{{-- New 웍스 Agent Project Modal (Alpine x-data parent: dashboardHome()) --}}
<div x-show="showModal" x-cloak
     style="position:fixed;inset:0;z-index:1050;display:flex;align-items:center;justify-content:center;padding:16px;"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">

    {{-- Backdrop --}}
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.45);" @click="closeModal()"></div>

    {{-- Modal box --}}
    <div style="position:relative;background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 24px 64px rgba(0,0,0,.2);overflow:hidden;">

        {{-- Header --}}
        <div style="padding:20px 24px 0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:17px;font-weight:800;color:#1e1b2e;margin-bottom:3px;">웍스 Agent 시작</div>
                <div style="font-size:12.5px;color:#64748b;">프로젝트와 프론트엔드 스택을 선택하면 웍스 개발 워크플로우가 시작됩니다.</div>
            </div>
            <button @click="closeModal()" style="width:28px;height:28px;border:none;background:#f8fafc;color:#64748b;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <form method="POST" action="{{ route('ai-agent.projects.store') }}"
              @submit.prevent="submitting = canSubmit() ? true : false; if(canSubmit()) $el.submit();">
            @csrf

            <div style="padding:20px 24px;">

                {{-- Step 1: Project selection --}}
                <div style="margin-bottom:20px;">
                    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:7px;">
                        1. Supportworks 프로젝트 선택
                    </label>
                    @if($selectableProjects->isNotEmpty())
                    <select name="project_id"
                            x-model="selectedProjectId"
                            style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#374151;outline:none;appearance:none;background:url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path d=%22M6 9l6 6 6-6%22/></svg>') no-repeat right 10px center/16px;">
                        <option value="">-- 프로젝트 선택 --</option>
                        @foreach($selectableProjects as $sp)
                        <option value="{{ $sp['id'] }}" x-bind:selected="selectedProjectId == {{ $sp['id'] }}">{{ $sp['name'] }}</option>
                        @endforeach
                    </select>
                    @else
                    <div style="padding:12px;background:#f8fafc;border:1.5px dashed #e2e8f0;border-radius:9px;font-size:12.5px;color:#94a3b8;text-align:center;">
                        모든 프로젝트가 이미 웍스 Agent를 사용 중입니다.
                    </div>
                    @endif
                    @error('project_id')
                    <div style="font-size:11.5px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Step 2: Stack selection --}}
                <div style="margin-bottom:20px;">
                    <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:7px;">
                        2. 프론트엔드 스택 선택
                        <span style="font-size:10.5px;font-weight:500;color:#f59e0b;margin-left:4px;">⚠️ 변경 불가</span>
                    </label>
                    <input type="hidden" name="frontend_stack" :value="selectedStack">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        @foreach([
                            ['value' => 'html',  'label' => 'HTML', 'desc' => 'Vanilla JS',   'icon' => '📄', 'color' => '#e34c26'],
                            ['value' => 'react', 'label' => 'React','desc' => 'React 18+',    'icon' => '⚛️', 'color' => '#61dafb'],
                            ['value' => 'vue',   'label' => 'Vue',  'desc' => 'Vue 3 / Vite', 'icon' => '🟢', 'color' => '#42b883'],
                        ] as $stack)
                        <button type="button"
                                @click="selectedStack = '{{ $stack['value'] }}'"
                                :class="{ 'stack-selected': selectedStack === '{{ $stack['value'] }}' }"
                                style="padding:14px 8px;border-radius:12px;border:2px solid;text-align:center;cursor:pointer;transition:all .15s;"
                                :style="selectedStack === '{{ $stack['value'] }}'
                                    ? 'border-color:var(--t500,#8b5cf6);background:var(--t50,#f5f3ff);'
                                    : 'border-color:#e2e8f0;background:#fff;'">
                            <div style="font-size:22px;margin-bottom:4px;">{{ $stack['icon'] }}</div>
                            <div style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ $stack['label'] }}</div>
                            <div style="font-size:10.5px;color:#94a3b8;margin-top:1px;">{{ $stack['desc'] }}</div>
                        </button>
                        @endforeach
                    </div>
                    @error('frontend_stack')
                    <div style="font-size:11.5px;color:#dc2626;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Warning note --}}
                <div style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;display:flex;align-items:flex-start;gap:8px;">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>한 번 선택한 스택은 프로젝트 진행 중 변경할 수 없습니다. 신중하게 선택해주세요.</span>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:14px 24px 20px;display:flex;justify-content:space-between;align-items:center;border-top:1.5px solid #f1f5f9;">
                <button type="button" @click="closeModal()"
                        style="padding:8px 18px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:13px;font-weight:600;cursor:pointer;">
                    취소
                </button>
                <button type="submit"
                        :disabled="!canSubmit() || submitting"
                        style="padding:9px 22px;border-radius:8px;border:none;font-size:13.5px;font-weight:700;cursor:pointer;transition:all .15s;"
                        :style="canSubmit() && !submitting
                            ? 'background:var(--t600,#7c3aed);color:#fff;'
                            : 'background:#e2e8f0;color:#94a3b8;cursor:not-allowed;'">
                    <span x-show="!submitting">웍스 Agent 시작</span>
                    <span x-show="submitting">처리 중...</span>
                </button>
            </div>
        </form>
    </div>
</div>
