<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeRequirementsJob;
use App\Models\AnalysisSession;
use App\Models\Project;
use App\Models\Requirement;
use App\Services\Analysis\FileExtractor\FileExtractorFactory;
use Illuminate\Http\Request;

class AnalysisSessionController extends Controller
{
    private FileExtractorFactory $extractorFactory;

    public function __construct()
    {
        $this->extractorFactory = new FileExtractorFactory();
    }

    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $sessions = $project->analysisSessions()
            ->with('createdBy')
            ->withCount('files')
            ->latest()
            ->paginate(20);

        return view('requirements.analysis.index', compact('project', 'sessions'));
    }

    public function create(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $provider = $project->preferred_llm_provider ?? 'anthropic';
        $models   = \App\Models\AnalysisSession::PROVIDER_MODELS[$provider] ?? [];
        $model    = $project->preferred_llm_model ?? ($models[1] ?? 'claude-sonnet-4-6');

        return view('requirements.analysis.new', compact('project', 'provider', 'model'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'files'        => 'nullable|array|max:10',
            'files.*'      => 'file|max:20480',
            'context_note' => 'nullable|string|max:2000',
            'use_planning' => 'nullable|boolean',
        ]);

        $hasFiles    = $request->hasFile('files');
        $contextNote = trim((string) $request->input('context_note', ''));

        // 기획서 기반 분석 — 프로젝트 기획서 내용을 분석 입력에 포함
        if ($request->boolean('use_planning')) {
            $planText = $project->planningDocs()
                ->whereNotNull('content')
                ->latest()
                ->get(['title', 'content'])
                ->map(fn($p) => '### ' . ($p->title ?: '기획서') . "\n" . trim((string) $p->content))
                ->filter()
                ->implode("\n\n");
            $planText = trim($planText);
            if ($planText !== '') {
                $planText    = mb_substr($planText, 0, 12000);
                $contextNote = trim("[프로젝트 기획서]\n{$planText}"
                    . ($contextNote !== '' ? "\n\n[추가 메모]\n{$contextNote}" : ''));
            }
        }

        $hasContext = $contextNote !== '';

        if (!$hasFiles && !$hasContext) {
            $msg = '파일·기획서·메모 중 하나 이상을 입력해주세요.';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['files' => $msg]);
        }

        $session = $project->analysisSessions()->create([
            'created_by_id'         => auth()->id(),
            'status'                => 'pending',
            'input_text'            => $contextNote,
            'llm_provider'          => 'anthropic',
            'llm_model'             => 'claude-sonnet-4-6',
            'system_prompt_version' => \App\Services\Analysis\Llm\Prompts\RequirementAnalysisPrompt::VERSION,
        ]);

        if ($hasFiles) {
            foreach ($request->file('files') as $uploadedFile) {
                $extension = strtolower($uploadedFile->getClientOriginalExtension());
                $mimeType  = $uploadedFile->getMimeType() ?? 'application/octet-stream';

                if (!$this->extractorFactory->supports($mimeType, $extension)) {
                    $session->delete();
                    $msg = "지원하지 않는 파일 형식입니다: {$uploadedFile->getClientOriginalName()}";
                    if ($request->expectsJson()) {
                        return response()->json(['ok' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['files' => $msg]);
                }

                $storedPath = $uploadedFile->store('analysis/' . $session->id, 'local');

                $session->files()->create([
                    'original_filename' => $uploadedFile->getClientOriginalName(),
                    'stored_path'       => $storedPath,
                    'mime_type'         => $mimeType,
                    'size'              => $uploadedFile->getSize(),
                    'extraction_status' => 'pending',
                    'uploaded_at'       => now(),
                ]);
            }
        }

        try {
            AnalyzeRequirementsJob::dispatch($session->id);
        } catch (\Throwable) {
            // Sync queue: job already set session status to 'failed'; continue to redirect
        }

        if ($request->expectsJson()) {
            $session->refresh();
            return response()->json([
                'ok'            => true,
                'status'        => $session->status,
                'session_id'    => $session->id,
                'summary'       => $session->summary,
                'warnings'      => $session->warnings,
                'candidates'    => $session->candidates,
                'error_message' => $session->error_message,
                'approve_url'   => route('projects.requirements.analysis.approve', [$project, $session]),
                'reject_url'    => route('projects.requirements.analysis.reject', [$project, $session]),
                'redirect'      => route('projects.requirements.analysis.show', [$project, $session]),
            ]);
        }

        return redirect()->route('projects.requirements.analysis.show', [$project, $session])
            ->with('success', '웍스 분석이 시작되었습니다.');
    }

    public function show(Request $request, Project $project, AnalysisSession $session)
    {
        $this->authorizeProject($project);

        abort_if($session->project_id !== $project->id, 404);

        $session->load(['files', 'createdBy']);

        return view('requirements.analysis.show', compact('project', 'session'));
    }

    public function approve(Request $request, Project $project, AnalysisSession $session)
    {
        $this->authorizeProject($project, 'manager');

        abort_if($session->project_id !== $project->id, 404);
        abort_if(!$session->isReview(), 422, '검토 상태의 세션만 승인할 수 있습니다.');

        $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer|min:0',
        ]);

        $candidates  = $session->candidates;
        $selected    = $request->input('selected');
        $created     = 0;
        $createdIds  = [];

        foreach ($selected as $index) {
            if (!isset($candidates[$index])) continue;

            $c   = $candidates[$index];
            $req = Requirement::create([
                'project_id'        => $project->id,
                'reporter_id'       => auth()->id(),
                'title'             => $c['title'],
                'description'       => $c['description'] ?? null,
                'category'          => $c['category'] ?? 'other',
                'priority'          => $c['priority'] ?? 'medium',
                'status'            => 'draft',
                'tags'              => !empty($c['tags']) ? array_values((array) $c['tags']) : null,
                'source_type'       => 'ai_analyzed',
                'source_session_id' => $session->id,
                'ai_confidence'     => $c['confidence'] ?? null,
                'source_ref'        => $c['source_ref'] ?? null,
            ]);

            $createdIds[] = $req->id;
            $created++;
        }

        $session->update(['status' => 'approved']);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'              => true,
                'created'         => $created,
                'requirement_ids' => $createdIds,
            ]);
        }

        return redirect()->route('projects.requirements.index', $project)
            ->with('success', "{$created}개의 요구사항이 등록되었습니다.");
    }

    public function reject(Request $request, Project $project, AnalysisSession $session)
    {
        $this->authorizeProject($project, 'manager');

        abort_if($session->project_id !== $project->id, 404);

        $session->update(['status' => 'rejected']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.requirements.analysis.index', $project)
            ->with('success', '분석 세션이 거부되었습니다.');
    }

    public function retry(Request $request, Project $project, AnalysisSession $session)
    {
        $this->authorizeProject($project);

        abort_if($session->project_id !== $project->id, 404);
        abort_if(!$session->isFailed(), 422, '실패한 세션만 재시도할 수 있습니다.');

        $session->update([
            'status'        => 'pending',
            'error_message' => null,
            'started_at'    => null,
            'completed_at'  => null,
        ]);

        $session->files()->update(['extraction_status' => 'pending', 'extraction_error' => null]);

        try {
            AnalyzeRequirementsJob::dispatch($session->id);
        } catch (\Throwable) {
            // Sync queue: job already set session status to 'failed'; continue to redirect
        }

        return redirect()->route('projects.requirements.analysis.show', [$project, $session])
            ->with('success', '분석을 재시도합니다.');
    }

    private function authorizeProject(Project $project, string $minRole = 'member'): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');

        if ($minRole === 'manager') {
            abort_if($project->getMemberRole($user) !== 'manager', 403, '관리자 권한이 필요합니다.');
        }
    }
}
