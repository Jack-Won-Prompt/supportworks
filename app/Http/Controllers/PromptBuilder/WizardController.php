<?php

namespace App\Http\Controllers\PromptBuilder;

use App\Http\Controllers\Controller;
use App\Models\PromptBuilder\Builder;
use App\Models\PromptBuilder\BuilderVersion;
use App\Models\PromptBuilder\StandardCandidate;
use App\Models\PromptBuilder\UserPreference;
use App\Models\PromptBuilder\WizardSession;
use App\Services\PromptBuilder\FigmaAnalysisService;
use App\Services\PromptBuilder\ImpactAnalysisService;
use App\Services\PromptBuilder\PromptGenerationService;
use App\Services\PromptBuilder\StandardMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WizardController extends Controller
{
    public function __construct(
        private PromptGenerationService $promptService,
        private FigmaAnalysisService $figmaService,
        private StandardMappingService $mappingService,
        private ImpactAnalysisService $impactService,
    ) {}

    public function create()
    {
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => Auth::id()],
            ['last_used_at' => now()]
        );

        return view('prompt-builder.wizard.create', compact('preferences'));
    }

    public function startSession(Request $request)
    {
        $validated = $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'workspace_id' => 'required|exists:pb_workspaces,id',
            'ai_type'      => 'required|in:cursor,claude,openai',
        ]);

        $session = WizardSession::create([
            'session_uuid'    => Str::uuid(),
            'user_id'         => Auth::id(),
            'current_step'    => 2,
            'completed_steps' => [1],
            'context'         => $validated,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->updateUserPreferences($validated);

        return response()->json([
            'session'   => $session,
            'next_step' => 2,
        ]);
    }

    public function show(WizardSession $session)
    {
        abort_if($session->user_id !== Auth::id(), 403);
        return view('prompt-builder.wizard.show', compact('session'));
    }

    public function updateStep(WizardSession $session, int $step, Request $request)
    {
        abort_if($session->user_id !== Auth::id(), 403);

        match ($step) {
            2 => $this->updateStep2Purpose($session, $request),
            3 => $this->updateStep3InputSources($session, $request),
            5 => $this->updateStep5Builder($session, $request),
            default => abort(422, "Invalid step: {$step}"),
        };

        return response()->json(['session' => $session->fresh()]);
    }

    private function updateStep2Purpose(WizardSession $session, Request $request): void
    {
        $validated = $request->validate([
            'purpose_type' => 'required|in:standard_assets,screen_generation,sequence',
            'targets'      => 'array',
            'targets.*'    => 'in:component,css,layout,js',
        ]);

        $session->update([
            'purpose'         => $validated,
            'current_step'    => 3,
            'completed_steps' => array_merge($session->completed_steps ?? [], [2]),
        ]);
    }

    private function updateStep3InputSources(WizardSession $session, Request $request): void
    {
        $validated = $request->validate([
            'figma_url'          => 'nullable|url',
            'figma_file'         => 'nullable|file|mimes:fig,json|max:10240',
            'to_be_image'        => 'nullable|image|max:5120',
            'as_is_image'        => 'nullable|image|max:5120',
            'source_files'       => 'array',
            'source_files.*'     => 'string',
            'reference_builders' => 'array',
            'reference_builders.*' => 'exists:pb_builders,id',
        ]);

        if ($request->hasFile('figma_file')) {
            $validated['figma_file_path'] = $request->file('figma_file')
                ->store('prompt-builder/figma', 'private');
        }

        if ($request->hasFile('to_be_image')) {
            $validated['to_be_image_path'] = $request->file('to_be_image')
                ->store('prompt-builder/images', 'private');
        }

        if ($request->hasFile('as_is_image')) {
            $validated['as_is_image_path'] = $request->file('as_is_image')
                ->store('prompt-builder/images', 'private');
        }

        $session->update([
            'input_sources'   => $validated,
            'current_step'    => 4,
            'completed_steps' => array_merge($session->completed_steps ?? [], [3]),
        ]);
    }

    private function updateStep5Builder(WizardSession $session, Request $request): void
    {
        $validated = $request->validate([
            'content'   => 'required|string',
            'is_edited' => 'boolean',
        ]);

        $generated = $session->generated_builders ?? [];
        $aiType = $session->context['ai_type'];
        $generated[$aiType] = array_merge($generated[$aiType] ?? [], [
            'content'   => $validated['content'],
            'is_edited' => $validated['is_edited'] ?? true,
        ]);

        $session->update([
            'generated_builders' => $generated,
            'current_step'       => 6,
            'completed_steps'    => array_merge($session->completed_steps ?? [], [5]),
        ]);
    }

    public function analyze(WizardSession $session)
    {
        abort_if($session->user_id !== Auth::id(), 403);

        $context = $session->context;
        $purpose = $session->purpose;
        $inputs  = $session->input_sources ?? [];

        $figmaAnalysis = null;
        if (!empty($inputs['figma_url']) || !empty($inputs['figma_file_path'])) {
            $figmaAnalysis = $this->figmaService->analyze(
                $inputs['figma_url'] ?? null,
                $inputs['figma_file_path'] ?? null
            );
        }

        $mapping = $this->mappingService->createMapping(
            workspaceId: $context['workspace_id'],
            figmaAnalysis: $figmaAnalysis,
            purposeType: $purpose['purpose_type'],
            targets: $purpose['targets'] ?? [],
        );

        $relatedBuilders = $this->mappingService->findRelatedBuilders(
            projectId: $context['project_id'],
            mapping: $mapping,
        );

        $impactAnalysis = $this->impactService->analyze(
            mapping: $mapping,
            relatedBuilders: $relatedBuilders,
        );

        $analysisResult = [
            'figma'            => $figmaAnalysis,
            'mapping'          => $mapping,
            'related_builders' => $relatedBuilders,
            'impact'           => $impactAnalysis,
        ];

        $session->update([
            'analysis_result' => $analysisResult,
            'current_step'    => 5,
            'completed_steps' => array_merge($session->completed_steps ?? [], [4]),
        ]);

        return response()->json(['analysis' => $analysisResult]);
    }

    public function generate(WizardSession $session)
    {
        abort_if($session->user_id !== Auth::id(), 403);

        $context = $session->context;
        $aiType  = $context['ai_type'];

        $prompt = $this->promptService->generate(
            aiType: $aiType,
            context: $context,
            purpose: $session->purpose ?? [],
            analysis: $session->analysis_result ?? [],
            inputSources: $session->input_sources ?? [],
        );

        $generated = $session->generated_builders ?? [];
        $generated[$aiType] = [
            'content'      => $prompt,
            'is_edited'    => false,
            'generated_at' => now()->toIso8601String(),
        ];

        $session->update([
            'generated_builders' => $generated,
            'current_step'       => 6,
            'completed_steps'    => array_merge($session->completed_steps ?? [], [5]),
        ]);

        return response()->json([
            'prompt'           => $prompt,
            'analysis_summary' => [
                'standards_applied' => count($session->analysis_result['mapping']['applied_standards'] ?? []),
                'candidates_found'  => count($session->analysis_result['mapping']['candidates'] ?? []),
                'character_count'   => mb_strlen($prompt),
                'estimated_tokens'  => $this->promptService->estimateTokens($prompt),
            ],
        ]);
    }

    public function previewWithDifferentAi(WizardSession $session, Request $request)
    {
        abort_if($session->user_id !== Auth::id(), 403);

        $aiType = $request->validate(['ai_type' => 'required|in:cursor,claude,openai'])['ai_type'];

        $cached = $session->generated_builders[$aiType] ?? null;
        if ($cached) {
            return response()->json(['prompt' => $cached['content']]);
        }

        $prompt = $this->promptService->generate(
            aiType: $aiType,
            context: $session->context,
            purpose: $session->purpose ?? [],
            analysis: $session->analysis_result ?? [],
            inputSources: $session->input_sources ?? [],
        );

        $generated = $session->generated_builders ?? [];
        $generated[$aiType] = [
            'content'      => $prompt,
            'is_edited'    => false,
            'generated_at' => now()->toIso8601String(),
        ];
        $session->update(['generated_builders' => $generated]);

        return response()->json(['prompt' => $prompt]);
    }

    public function complete(WizardSession $session, Request $request)
    {
        abort_if($session->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags'        => 'array',
        ]);

        $aiType        = $session->context['ai_type'];
        $promptContent = $session->generated_builders[$aiType]['content'] ?? '';
        $isEdited      = $session->generated_builders[$aiType]['is_edited'] ?? false;

        return DB::transaction(function () use ($session, $validated, $aiType, $promptContent, $isEdited) {
            $builder = Builder::create([
                'project_id'       => $session->context['project_id'],
                'workspace_id'     => $session->context['workspace_id'],
                'user_id'          => Auth::id(),
                'title'            => $validated['title'],
                'description'      => $validated['description'] ?? null,
                'ai_type'          => $aiType,
                'purpose_type'     => $session->purpose['purpose_type'],
                'purpose_targets'  => $session->purpose['targets'] ?? [],
                'figma_url'        => $session->input_sources['figma_url'] ?? null,
                'figma_file_path'  => $session->input_sources['figma_file_path'] ?? null,
                'input_source_files' => $session->input_sources['source_files'] ?? [],
                'input_images'     => [
                    'to_be' => $session->input_sources['to_be_image_path'] ?? null,
                    'as_is' => $session->input_sources['as_is_image_path'] ?? null,
                ],
                'applied_standards' => $session->analysis_result['mapping']['applied_standards'] ?? [],
                'content'          => $promptContent,
                'is_edited'        => $isEdited,
                'current_version'  => 1,
                'tags'             => $validated['tags'] ?? [],
            ]);

            BuilderVersion::create([
                'builder_id'          => $builder->id,
                'version_number'      => 1,
                'content'             => $promptContent,
                'created_by_type'     => 'user',
                'created_by_user_id'  => Auth::id(),
                'change_reason'       => 'initial',
                'change_description'  => 'Initial creation',
            ]);

            foreach ($session->analysis_result['related_builders'] ?? [] as $related) {
                $builder->dependencies()->create([
                    'to_builder_id'   => $related['id'],
                    'dependency_type' => $related['dependency_type'],
                    'strength'        => $related['strength'],
                    'auto_detected'   => true,
                    'confidence'      => $related['confidence'] ?? 0.8,
                ]);
            }

            foreach ($session->analysis_result['mapping']['candidates'] ?? [] as $candidate) {
                StandardCandidate::create([
                    'workspace_id'    => $session->context['workspace_id'],
                    'asset_type'      => $candidate['asset_type'],
                    'name'            => $candidate['name'],
                    'content'         => $candidate['content'],
                    'source'          => 'figma',
                    'source_metadata' => $candidate['metadata'] ?? null,
                ]);
            }

            $session->update(['status' => 'completed']);

            return response()->json([
                'builder'      => $builder,
                'redirect_url' => route('builder.history.show', $builder),
            ]);
        });
    }

    public function abandon(WizardSession $session)
    {
        abort_if($session->user_id !== Auth::id(), 403);
        $session->update(['status' => 'abandoned']);
        return response()->json(['message' => 'Session abandoned']);
    }

    private function updateUserPreferences(array $context): void
    {
        $existing = UserPreference::where('user_id', Auth::id())->first();
        $perProjectWorkspace = array_merge(
            $existing?->per_project_workspace ?? [],
            [$context['project_id'] => $context['workspace_id']]
        );

        UserPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'last_project_id'      => $context['project_id'],
                'last_used_at'         => now(),
                'last_ai_type'         => $context['ai_type'],
                'per_project_workspace' => $perProjectWorkspace,
            ]
        );
    }
}
