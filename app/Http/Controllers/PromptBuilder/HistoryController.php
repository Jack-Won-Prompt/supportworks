<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PromptBuilder\Builder;
use App\Models\PromptBuilder\BuilderVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }

    public function index(Project $project)
    {
        $this->authorizeProject($project);

        $builders = Builder::where('project_id', $project->id)
            ->with(['workspace', 'user'])
            ->withCount('versions')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('prompt-builder.history.index', compact('project', 'builders'));
    }

    public function show(Builder $builder)
    {
        $this->authorizeProject($builder->project);

        $builder->load(['workspace', 'user', 'versions' => fn($q) => $q->orderByDesc('version_number')]);

        return view('prompt-builder.history.show', compact('builder'));
    }

    public function versions(Builder $builder)
    {
        $this->authorizeProject($builder->project);

        $versions = $builder->versions()->orderByDesc('version_number')->get();

        return response()->json(['versions' => $versions]);
    }

    public function duplicate(Builder $builder)
    {
        $this->authorizeProject($builder->project);

        $clone = $builder->replicate(['current_version', 'is_edited', 'sequence_id', 'sequence_step_number']);
        $clone->title           = $builder->title . ' (복사본)';
        $clone->user_id         = Auth::id();
        $clone->current_version = 1;
        $clone->is_edited       = false;
        $clone->save();

        BuilderVersion::create([
            'builder_id'         => $clone->id,
            'version_number'     => 1,
            'content'            => $clone->content,
            'created_by_type'    => 'user',
            'created_by_user_id' => Auth::id(),
            'change_reason'      => 'initial',
            'change_description' => '복제본 생성',
        ]);

        return response()->json([
            'builder'      => $clone,
            'redirect_url' => route('builder.history.show', $clone),
        ]);
    }

    public function revert(Request $request, Builder $builder, int $version)
    {
        $this->authorizeProject($builder->project);

        $targetVersion = BuilderVersion::where('builder_id', $builder->id)
            ->where('version_number', $version)
            ->firstOrFail();

        return DB::transaction(function () use ($builder, $targetVersion) {
            $newVersionNumber = $builder->current_version + 1;

            BuilderVersion::create([
                'builder_id'          => $builder->id,
                'version_number'      => $newVersionNumber,
                'content'             => $targetVersion->content,
                'created_by_type'     => 'user',
                'created_by_user_id'  => Auth::id(),
                'change_reason'       => 'manual_edit',
                'change_description'  => "v{$targetVersion->version_number}으로 복원",
                'is_reverted'         => true,
                'reverted_to_version' => $targetVersion->version_number,
            ]);

            $builder->update([
                'content'         => $targetVersion->content,
                'current_version' => $newVersionNumber,
                'is_edited'       => true,
            ]);

            return response()->json(['builder' => $builder->fresh()]);
        });
    }
}
