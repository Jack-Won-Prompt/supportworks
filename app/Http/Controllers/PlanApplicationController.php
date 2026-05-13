<?php

namespace App\Http\Controllers;

use App\Models\PlanApplication;
use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\Requirement;
use App\Services\PlanApplication\MarkdownInserter;
use App\Services\PlanApplication\PlanApplicationService;
use App\Services\PlanApplication\Templates\TemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanApplicationController extends Controller
{
    private PlanApplicationService $service;

    public function __construct()
    {
        $registry      = new TemplateRegistry();
        $inserter      = new MarkdownInserter();
        $this->service = new PlanApplicationService($inserter, $registry);
    }

    /** GET /projects/{project}/plan-applications/plans — 기획서 목록 + 헤딩 */
    public function plans(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $plans = $project->planningDocs()
            ->select('id', 'title', 'content', 'version')
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'title'    => $p->title,
                'version'  => $p->version,
                'headings' => $this->service->getHeadings($p),
            ]);

        return response()->json(['plans' => $plans]);
    }

    /** POST /projects/{project}/plan-applications/preview */
    public function preview(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'requirement_ids' => 'required|array|min:1',
            'requirement_ids.*' => 'integer',
            'template' => 'nullable|string',
        ]);

        $markdown = $this->service->preview(
            $request->input('requirement_ids'),
            $request->input('template', 'default'),
            ['si_mode' => $project->si_mode_enabled]
        );

        return response()->json(['markdown' => $markdown]);
    }

    /** POST /projects/{project}/planning/{doc}/apply-requirements */
    public function apply(Request $request, Project $project, PlanningDoc $doc): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($doc->project_id !== $project->id, 404);

        $request->validate([
            'requirement_ids'    => 'required|array|min:1',
            'requirement_ids.*'  => 'integer',
            'position'           => 'required|in:end,beginning,after_section',
            'section_anchor'     => 'nullable|string|max:200',
            'template'           => 'nullable|string',
        ]);

        $result = $this->service->apply(
            requirementIds: $request->input('requirement_ids'),
            planId:         $doc->id,
            appliedById:    auth()->id(),
            position:       $request->input('position', 'end'),
            sectionAnchor:  $request->input('section_anchor'),
            templateName:   $request->input('template', 'default'),
        );

        return response()->json($result->toArray());
    }

    /** GET /projects/{project}/planning/{doc}/applied-requirements */
    public function listByPlan(Project $project, PlanningDoc $doc): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($doc->project_id !== $project->id, 404);

        $applications = PlanApplication::with(['requirement', 'appliedBy'])
            ->where('plan_id', $doc->id)
            ->whereNull('deleted_at')
            ->orderByDesc('applied_at')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'requirement_id'   => $a->requirement_id,
                'requirement_title'=> $a->requirement?->title,
                'applied_by'       => $a->appliedBy?->name,
                'applied_at'       => $a->applied_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['applications' => $applications]);
    }

    /** GET /projects/{project}/requirements/{requirement}/plan-applications */
    public function listByRequirement(Project $project, Requirement $requirement): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $applications = PlanApplication::with(['plan', 'appliedBy'])
            ->where('requirement_id', $requirement->id)
            ->whereNull('deleted_at')
            ->orderByDesc('applied_at')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'plan_id'    => $a->plan_id,
                'plan_title' => $a->plan?->title,
                'plan_url'   => $a->plan ? route('projects.planning.show', [$project, $a->plan]) : null,
                'applied_by' => $a->appliedBy?->name,
                'applied_at' => $a->applied_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['applications' => $applications]);
    }

    /** POST /projects/{project}/plan-applications/{application}/complete */
    public function toggleComplete(Project $project, PlanApplication $application): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($application->requirement?->project_id !== $project->id, 404);

        $isCompleted = !$application->is_completed;
        $application->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        return response()->json(['ok' => true, 'is_completed' => $isCompleted]);
    }

    /** DELETE /projects/{project}/plan-applications/{application} */
    public function revert(Project $project, PlanApplication $application): JsonResponse
    {
        $this->authorizeProject($project);

        // Verify the application belongs to this project
        abort_if($application->requirement?->project_id !== $project->id, 404);

        $user = auth()->user();
        if (!$user->isAdmin()
            && $application->applied_by_id !== $user->id
            && $project->getMemberRole($user) !== 'manager')
        {
            abort(403, '취소 권한이 없습니다.');
        }

        try {
            $updatedContent = $this->service->revert($application->id);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'content' => $updatedContent]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
