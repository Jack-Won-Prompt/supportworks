<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Jobs\WorksBuilder\BuildOutputPackageJob;
use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OutputPackageController extends Controller
{
    /**
     * 최종 HTML 단독 다운로드 (zip 풀지 않아도 됨).
     * 검수 OK된 HTML 우선, 없으면 최신 버전.
     */
    public function downloadHtml(Task $task): Response|RedirectResponse
    {
        $this->authorize('view', $task);

        $html = $this->resolveFinalHtml($task);
        if (!$html) {
            return back()->with('status', '아직 생성된 HTML이 없습니다.');
        }

        $filename = sprintf('task-%d_v%d.html', $task->id, $html->version);

        return response($html->html_content, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Wb-Html-Sha256'    => $html->html_hash,
        ]);
    }

    private function resolveFinalHtml(Task $task): ?GeneratedHtml
    {
        // OK 결정된 검수 세션의 HTML 우선
        $okSession = $task->reviewSessions()
            ->where('decision', 'ok')
            ->orderByDesc('review_round')
            ->first();
        if ($okSession) {
            return $okSession->html;
        }
        return $task->generatedHtml()->orderByDesc('version')->first();
    }

    public function download(Task $task): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('view', $task);

        $pkg = $task->outputPackages()->latest('built_at')->first();
        if (!$pkg) {
            return back()->with('status', '패키지가 아직 빌드되지 않았습니다.');
        }

        $path = storage_path('app/'.$pkg->file_path);
        if (!file_exists($path)) {
            return back()->with('status', '패키지 파일을 찾을 수 없습니다.');
        }

        $filename = basename($pkg->file_path);
        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function rebuild(Task $task): RedirectResponse
    {
        $this->authorize('view', $task);

        if (!$task->isCompleted()) {
            return back()->with('status', '완료된 Task만 재패키징할 수 있습니다.');
        }

        BuildOutputPackageJob::dispatch($task->id);
        return back()->with('status', '패키지 재빌드를 시작했습니다.');
    }
}
