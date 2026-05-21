<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Jobs\WorksBuilder\GenerateHtmlJob;
use App\Models\WorksBuilder\Task;
use App\Models\WorksBuilder\TaskOption;
use App\Services\WorksBuilder\Preview\LayoutPreviewBuilder;
use App\Services\WorksBuilder\TaskActions\OptionRevisionService;
use App\Services\WorksBuilder\Theme\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OptionController extends Controller
{
    public function __construct(
        private LayoutPreviewBuilder $previewBuilder,
        private OptionRevisionService $revision,
        private ThemeRegistry $themes,
    ) {}

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $option = $task->options()->where('is_current', true)->first();
        if (!$option) {
            $option = TaskOption::create([
                'task_id'      => $task->id,
                'options_data' => $this->defaults(),
                'version'      => 1,
                'is_current'   => true,
                'changed_by'   => Auth::id(),
                'changed_at'   => now(),
            ]);
        }

        $svg = $this->previewBuilder->build($option);
        $themes = $this->themes->list();

        return view('works-builder.options.edit', compact('task', 'option', 'svg', 'themes'));
    }

    public function update(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $data = $this->validated($request);
        $this->revision->revise($task, $data, Auth::user());

        // 폴링이 옛 result_confirm 상태를 보고 즉시 리다이렉트하는 race를 막기 위해 동기 전환
        $task->update([
            'status'        => Task::STATUS_AI_CALLING,
            'current_stage' => 'ai_calling',
        ]);

        // 옵션 확정 → AI HTML 생성 Job 큐
        GenerateHtmlJob::dispatch($task->id);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'          => true,
                'status_url'  => route('wb.tasks.ai-progress.status', $task),
                'cancel_url'  => route('wb.tasks.ai-progress.cancel', $task),
                'task_url'    => route('wb.tasks.show', $task),
                'preview_svg' => $this->previewBuilder->build($data),
            ]);
        }

        return redirect()->route('wb.tasks.ai-progress.show', $task);
    }

    public function previewJson(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $this->validated($request);
        $svg  = $this->previewBuilder->build($data);
        return response()->json(['svg' => $svg]);
    }

    private function validated(Request $request): array
    {
        $themeKeys = array_keys($this->themes->list());
        $defaultTheme = $this->themes->defaultKey();

        $data = $request->validate([
            'gnb_position'    => 'required|in:top,left,right',
            'tab_structure'   => 'required|in:single,top_tabs,left_tabs,sidebar_tabs,none',
            'transition_type' => 'required|in:page,slide,tab_switch',
            'main_color'      => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_key'       => ['nullable', Rule::in($themeKeys)],
        ]);

        // theme_key 비어있으면 기본 테마 채움 (테마가 1개라도 항상 명시되도록)
        $data['theme_key'] = $data['theme_key'] ?: $defaultTheme;

        return $data;
    }

    private function defaults(): array
    {
        return [
            'gnb_position'    => 'top',
            'tab_structure'   => 'single',
            'transition_type' => 'page',
            'main_color'      => '#3b82f6',
            'theme_key'       => $this->themes->defaultKey(),
        ];
    }
}
