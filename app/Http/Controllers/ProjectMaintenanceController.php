<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceFile;
use App\Models\ProjectMaintenance;
use App\Models\SrTarget;
use App\Services\ProjectNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProjectMaintenanceController extends Controller
{
    public function index(Request $request, SrTarget $srTarget)
    {
        $this->authorizeSrTarget($srTarget);

        $user = auth()->user();

        $base = fn() => $srTarget->maintenances()
            ->when(!$user->isAdmin(), fn($q) => $q->where('user_id', $user->id));

        $counts = [
            'all'         => $base()->count(),
            'pending'     => $base()->where('status', 'pending')->count(),
            'in_progress' => $base()->where('status', 'in_progress')->count(),
            'completed'   => $base()->where('status', 'completed')->count(),
            'rejected'    => $base()->where('status', 'rejected')->count(),
        ];

        $maintenances = $base()
            ->with(['user', 'replies'])
            ->withCount('replies')
            ->when($request->filled('status'),   fn($q) => $q->where('status',   $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $ganttItems = $base()
            ->when($request->filled('status'),   fn($q) => $q->where('status',   $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->with('user')
            ->orderByRaw('COALESCE(requested_date, created_at)')
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'title'          => $m->title,
                'status'         => $m->status,
                'status_label'   => $m->status_label,
                'status_color'   => $m->status_color,
                'status_bg'      => $m->status_bg,
                'priority'       => $m->priority,
                'priority_label' => $m->priority_label,
                'priority_color' => $m->priority_color,
                'user_name'      => $m->user?->name ?? '—',
                'start'          => ($m->requested_date ?? $m->created_at)->format('Y-m-d'),
                'end'            => ($m->scheduled_date ?? $m->due_date)?->format('Y-m-d'),
                'detail_url'     => route('maintenances.detail', $m),
                'update_url'     => route('maintenances.dates', $m),
                'can_edit'       => $m->user_id === auth()->id() || auth()->user()->isAdmin(),
            ]);

        $maintenanceIds = $base()->pluck('id');
        $projectFiles = MaintenanceFile::where('sr_target_id', $srTarget->id)
            ->where(fn($q) =>
                $q->whereNull('maintenance_id')
                  ->orWhereIn('maintenance_id', $maintenanceIds)
            )
            ->with(['uploader', 'category', 'maintenance'])
            ->withCount('comments')
            ->latest()
            ->get();

        $fileCategories  = $srTarget->maintenanceFileCategories()->get();
        $allMaintenances = $base()->orderBy('created_at', 'desc')->get(['id', 'title']);

        // 수정·삭제 권한: 관리자 또는 연결 프로젝트의 매니저
        $canManageSr = $this->canManageSrTarget($srTarget);

        return view('maintenance.index', compact('srTarget', 'maintenances', 'counts', 'ganttItems', 'projectFiles', 'fileCategories', 'allMaintenances', 'canManageSr'));
    }

    public function create(SrTarget $srTarget)
    {
        $this->authorizeSrTarget($srTarget);
        $fileCategories = $srTarget->maintenanceFileCategories()->get();
        return view('maintenance.create', compact('srTarget', 'fileCategories'));
    }

    public function store(Request $request, SrTarget $srTarget)
    {
        $this->authorizeSrTarget($srTarget);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string|min:1',
            'priority'       => 'required|in:low,normal,high,urgent',
            'requested_date' => 'nullable|date',
            'due_date'       => 'nullable|date',
            'attachments'           => 'nullable|array',
            'attachments.*'         => 'file|max:51200',
            'attachment_category_id'=> 'nullable|integer|exists:maintenance_file_categories,id',
        ]);

        $maintenance = $srTarget->maintenances()->create([
            'project_id'     => $srTarget->project_id,
            'title'          => $validated['title'],
            'content'        => $validated['content'],
            'priority'       => $validated['priority'],
            'requested_date' => $validated['requested_date'] ?? null,
            'due_date'       => $validated['due_date'] ?? null,
            'user_id'        => auth()->id(),
        ]);

        if ($request->hasFile('attachments')) {
            $catId = $validated['attachment_category_id'] ?? null;
            foreach ($request->file('attachments') as $uploaded) {
                $storedName = Str::uuid() . '.' . $uploaded->getClientOriginalExtension();
                $path = $uploaded->storeAs("sr-targets/{$srTarget->id}", $storedName, 'local');
                MaintenanceFile::create([
                    'project_id'             => $srTarget->project_id,
                    'sr_target_id'           => $srTarget->id,
                    'maintenance_id'         => $maintenance->id,
                    'uploaded_by'            => auth()->id(),
                    'original_name'          => $uploaded->getClientOriginalName(),
                    'stored_name'            => $storedName,
                    'path'                   => $path,
                    'mime_type'              => $uploaded->getMimeType(),
                    'size'                   => $uploaded->getSize(),
                    'maintenance_category_id'=> $catId,
                ]);
            }
        }

        if ($srTarget->project) {
            app(ProjectNotificationService::class)->notify(
                $srTarget->project, auth()->user(), 'maintenance_created',
                $maintenance->title,
                route('maintenances.show', $maintenance),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $maintenance->id, 'redirect' => route('sr-targets.maintenances.index', $srTarget)]);
        }

        return redirect()->route('sr-targets.maintenances.index', $srTarget)
            ->with('success', 'SR 접수가 등록되었습니다.');
    }

    public function show(ProjectMaintenance $maintenance)
    {
        $this->authorizeSrTarget($maintenance->srTarget);
        $this->authorizeMaintenance($maintenance);

        $maintenance->load(['srTarget', 'user', 'replies.user', 'replies.adminUser', 'files']);

        return view('maintenance.show', compact('maintenance'));
    }

    public function detail(ProjectMaintenance $maintenance)
    {
        $this->authorizeSrTarget($maintenance->srTarget);
        $this->authorizeMaintenance($maintenance);

        $maintenance->load(['srTarget', 'user', 'replies.user', 'replies.adminUser', 'files']);

        return view('maintenance.detail_partial', compact('maintenance'));
    }

    public function update(Request $request, ProjectMaintenance $maintenance)
    {
        $this->authorizeSrTarget($maintenance->srTarget);

        if (!$this->canManageSrTarget($maintenance->srTarget)) {
            abort(403);
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'priority'       => 'required|in:low,normal,high,urgent',
            'requested_date' => 'nullable|date',
            'due_date'       => 'nullable|date',
            'scheduled_date' => auth()->user()->isAdmin() ? 'nullable|date' : 'prohibited',
        ]);

        $maintenance->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'             => true,
                'title'          => $maintenance->title,
                'priority'       => $maintenance->priority,
                'priority_label' => $maintenance->priority_label,
                'priority_color' => $maintenance->priority_color,
                'start'          => ($maintenance->requested_date ?? $maintenance->created_at)->format('Y-m-d'),
                'end'            => ($maintenance->scheduled_date ?? $maintenance->due_date)?->format('Y-m-d'),
            ]);
        }

        return back()->with('success', 'SR 접수가 수정되었습니다.');
    }

    public function destroy(Request $request, ProjectMaintenance $maintenance)
    {
        $this->authorizeSrTarget($maintenance->srTarget);

        if (!$this->canManageSrTarget($maintenance->srTarget)) {
            abort(403);
        }

        $srTarget = $maintenance->srTarget;
        $title    = $maintenance->title;
        $maintenance->delete();

        if ($srTarget && $srTarget->project) {
            app(ProjectNotificationService::class)->notify(
                $srTarget->project, auth()->user(), 'maintenance_deleted',
                $title,
                route('sr-targets.maintenances.index', $srTarget),
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('sr-targets.maintenances.index', $srTarget)
            ->with('success', 'SR 접수가 삭제되었습니다.');
    }

    public function updateStatus(Request $request, ProjectMaintenance $maintenance)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate(['status' => 'required|in:pending,in_progress,completed,rejected']);
        $maintenance->update(['status' => $request->status]);

        return back()->with('success', '처리 상태가 변경되었습니다.');
    }

    public function updateDates(Request $request, ProjectMaintenance $maintenance)
    {
        $this->authorizeSrTarget($maintenance->srTarget);
        $this->authorizeMaintenance($maintenance);

        $validated = $request->validate([
            'requested_date' => 'nullable|date',
            'due_date'       => 'nullable|date',
        ]);

        $maintenance->update($validated);
        $maintenance->refresh();

        return response()->json([
            'ok'    => true,
            'start' => ($maintenance->requested_date ?? $maintenance->created_at)->format('Y-m-d'),
            'end'   => ($maintenance->scheduled_date ?? $maintenance->due_date)?->format('Y-m-d'),
        ]);
    }

    private function authorizeSrTarget(?SrTarget $srTarget): void
    {
        if (!$srTarget) {
            abort(404);
        }
        if (!$srTarget->isAccessibleBy(auth()->user())) {
            abort(403, '접근 권한이 없습니다.');
        }
    }

    /** SR 수정·삭제 권한: 관리자 또는 연결 프로젝트의 매니저 */
    private function canManageSrTarget(?SrTarget $srTarget): bool
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return true;
        }
        return $srTarget && $srTarget->project
            && $srTarget->project->getMemberRole($user) === 'manager';
    }

    private function authorizeMaintenance(ProjectMaintenance $maintenance): void
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isSrAgent()) return;
        if ($maintenance->user_id !== $user->id) abort(403, '접근 권한이 없습니다.');
    }
}
