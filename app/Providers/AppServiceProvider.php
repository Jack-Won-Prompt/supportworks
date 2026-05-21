<?php

namespace App\Providers;

use App\Services\Agent\AgentUsageLogService;
use App\Services\Agent\AiProviderFactory;
use App\Services\Agent\AsIsAnalysisAiService;
use App\Services\Agent\ToBeAnalysisAiService;
use App\Services\Agent\GapAnalysisAiService;
use App\Services\Agent\PlanningDocumentAiService;
use App\Services\Agent\PlanningDocumentDataContext;
use App\Services\Agent\PlanningTemplateService;
use App\Services\Agent\IaDiagramAiService;
use App\Services\Agent\DesignTokenService;
use App\Services\Agent\ComponentSpecService;
use App\Services\Agent\LayoutSpecService;
use App\Services\Agent\ScreenMappingService;
use App\Services\Agent\ReviewContextLoader;
use App\Services\Agent\AiDesignReviewer;
use App\Services\Agent\DesignReviewService;
use App\Services\Agent\DesignSystemDataContext;
use App\Services\Agent\DesignSystemTemplateService;
use App\Services\Agent\DesignSystemAiService;
use App\Services\Agent\DesignCompletionService;
use App\Services\Agent\DevHandoffService;
use App\Services\Agent\ApiSpecAiService;
use App\Services\Agent\RbacAiService;
use App\Services\Agent\CodeGenPromptAiService;
use App\Services\Agent\FrontendCodeAiService;
use App\Services\Agent\FrontendCodePreviewService;
use App\Services\Agent\CodeStaticAnalyzer;
use App\Services\Agent\AiCodeReviewer;
use App\Services\Agent\CodeValidationService;
use App\Services\Agent\ErdAiService;
use App\Services\Agent\Figma\FigmaClientFactory;
use App\Services\Agent\MockupAiService;
use App\Services\Agent\ScreenPromptAiService;
use App\Services\Agent\Parsers\ExcelFileParser;
use App\Services\Agent\Parsers\FallbackFileParser;
use App\Services\Agent\Parsers\FileParserResolver;
use App\Services\Agent\Parsers\ImageFileParser;
use App\Services\Agent\Parsers\PdfFileParser;
use App\Services\Agent\Parsers\PowerPointFileParser;
use App\Services\Agent\Parsers\TextFileParser;
use App\Services\Agent\PromptLibraryService;
use App\Services\Agent\CodeReviewService;
use App\Services\Agent\IssueAggregator;
use App\Services\Agent\FixOrchestrator;
use App\Services\Agent\DevCompletionService;
use App\Services\Agent\ReleasePackageService;
use App\Services\Agent\ApiCallExtractor;
use App\Services\Agent\ApiIntegrationService;
use App\Services\Agent\BackendCodeAiService;
use App\Services\Agent\BackendEndpointExtractor;
use App\Services\Agent\DeployGuideDataContext;
use App\Services\Agent\DeployGuideService;
use App\Services\Agent\DevPrepCompletionService;
use App\Services\Agent\UserManualDataContext;
use App\Services\Agent\UserManualService;
use App\Services\Agent\MigrationGuideDataContext;
use App\Services\Agent\MigrationGuideService;
use App\Services\Agent\ReleaseCompletionService;
use App\Services\Agent\TraceabilityService;
use App\Services\Llm\ClaudeProvider;
use App\Services\Llm\LlmRouter;
use App\Services\Llm\OpenAiProvider;
use App\View\Composers\AiAgentComposer;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // app_locale 쿠키는 평문으로 저장/읽기 (locale 전환용)
        EncryptCookies::except(['app_locale']);

        $this->app->singleton(ClaudeProvider::class);
        $this->app->singleton(OpenAiProvider::class);
        $this->app->singleton(LlmRouter::class);

        // AI Fix 파이프라인 (PoC: 모든 외부 의존이 stub 으로 묶임).
        // 운영 단계에서 다음을 교체:
        //   AiAnalyzer       -> ClaudeAiAnalyzer (E 단계)
        //   WorktreeManager  -> ProcessWorktreeManager (실제 git 호출)
        //   AiCodeApplier    -> ClaudeAiCodeApplier (실제 코드 수정)
        //   TestRunner       -> PhpUnitTestRunner (실제 phpunit 실행)
        //   GitHubMerger     -> GuzzleGitHubMerger (실제 GitHub PR API 호출)
        //   RemoteDeployer   -> SshRemoteDeployer (phpseclib SSH 로 deploy.sh 실행)
        // AiAnalyzer: driver=openai + AiSetting 의 openaiKey 가 있으면 OpenAiAnalyzer,
        // 아니면 휴리스틱 Stub. 운영 .env 의 AI_FIX_ANALYZER_DRIVER 로 활성화.
        $this->app->bind(\App\Services\AiFix\AiAnalyzer::class, function ($app) {
            $cfg = config('ai-fix.analyzer');
            if (($cfg['driver'] ?? 'stub') === 'openai') {
                try {
                    $apiKey = \App\Models\AiSetting::current()->openaiKey();
                } catch (\Throwable) {
                    $apiKey = null;
                }
                if (!empty($apiKey)) {
                    return new \App\Services\AiFix\OpenAiAnalyzer(
                        apiKey:        $apiKey,
                        model:         $cfg['model']          ?? 'gpt-5',
                        fallbackModel: $cfg['fallback_model'] ?? 'gpt-5-mini',
                        timeout:       (int) ($cfg['timeout'] ?? 60),
                    );
                }
            }
            return new \App\Services\AiFix\StubAiAnalyzer();
        });
        $this->app->bind(\App\Services\AiFix\AiCodeApplier::class,   \App\Services\AiFix\StubCodeApplier::class);
        // WorktreeManager: driver=process 이고 경로 두 개 셋팅돼있으면 ProcessWorktreeManager,
        // 아니면 안전한 StubWorktreeManager 로 fallback. 운영 .env 의 AI_FIX_WORKTREE_DRIVER 로 활성화.
        $this->app->bind(\App\Services\AiFix\WorktreeManager::class, function ($app) {
            $cfg = config('ai-fix.worktree');
            if (($cfg['driver'] ?? 'stub') === 'process'
                && !empty($cfg['bare_path']) && !empty($cfg['base_path'])) {
                return new \App\Services\AiFix\ProcessWorktreeManager(
                    barePath:     $cfg['bare_path'],
                    basePath:     $cfg['base_path'],
                    sourceEnv:    $cfg['source_env']    ?? base_path('.env'),
                    testDatabase: $cfg['test_database'] ?? 'supportworks_ai_test',
                );
            }
            return new \App\Services\AiFix\StubWorktreeManager();
        });
        $this->app->bind(\App\Services\AiFix\GitHubMerger::class,    \App\Services\AiFix\StubGitHubMerger::class);
        $this->app->bind(\App\Services\AiFix\RemoteDeployer::class,  \App\Services\AiFix\StubRemoteDeployer::class);

        // TestRunner: driver=phpunit 이면 PhpUnitTestRunner, 아니면 default-pass Stub.
        // 운영 .env 의 AI_FIX_TEST_RUNNER_DRIVER=phpunit 으로 활성화.
        $this->app->bind(\App\Services\AiFix\TestRunner::class, function () {
            $cfg = config('ai-fix.test_runner');
            if (($cfg['driver'] ?? 'stub') === 'phpunit') {
                return new \App\Services\AiFix\PhpUnitTestRunner(
                    timeout: (int) ($cfg['timeout'] ?? 600),
                );
            }
            return new \App\Services\AiFix\StubTestRunner(
                new \App\Services\AiFix\TestResult(passed: true, testsRun: 0)
            );
        });

        $this->app->singleton(\App\Services\AiFix\EscalationEvaluator::class,
            fn() => \App\Services\AiFix\EscalationEvaluator::fromConfig());
        $this->app->singleton(\App\Services\AiFix\AiFixNotifier::class);
        $this->app->singleton(\App\Services\AiFix\AiFixOrchestrator::class);

        // Agent Session 용 AIProvider Factory — config('ai-agent') 기반
        $this->app->singleton(AiProviderFactory::class);

        $this->app->singleton(FileParserResolver::class, fn() => new FileParserResolver([
            new TextFileParser(),
            new ExcelFileParser(),
            new PowerPointFileParser(),
            new PdfFileParser(),
            new ImageFileParser(),
            new FallbackFileParser(),
        ]));

        $this->app->singleton(AsIsAnalysisAiService::class, fn($app) => new AsIsAnalysisAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(ToBeAnalysisAiService::class, fn($app) => new ToBeAnalysisAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(GapAnalysisAiService::class, fn($app) => new GapAnalysisAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(PlanningDocumentAiService::class, fn($app) => new PlanningDocumentAiService(
            usageLog:        $app->make(AgentUsageLogService::class),
            prompts:         $app->make(PromptLibraryService::class),
            dataContext:     $app->make(PlanningDocumentDataContext::class),
            templateService: $app->make(PlanningTemplateService::class),
            traceability:    $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(IaDiagramAiService::class, fn($app) => new IaDiagramAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(ScreenPromptAiService::class, fn($app) => new ScreenPromptAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(MockupAiService::class, fn($app) => new MockupAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(FigmaClientFactory::class, fn($app) => new FigmaClientFactory(
            cache: $app->make(\Illuminate\Contracts\Cache\Repository::class),
        ));

        $this->app->singleton(DesignTokenService::class, fn($app) => new DesignTokenService(
            clientFactory: $app->make(FigmaClientFactory::class),
        ));

        $this->app->singleton(ComponentSpecService::class, fn($app) => new ComponentSpecService(
            clientFactory: $app->make(FigmaClientFactory::class),
        ));

        $this->app->singleton(LayoutSpecService::class, fn($app) => new LayoutSpecService(
            clientFactory: $app->make(FigmaClientFactory::class),
        ));

        $this->app->singleton(ScreenMappingService::class, fn($app) => new ScreenMappingService(
            clientFactory: $app->make(FigmaClientFactory::class),
            traceability:  $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(ReviewContextLoader::class, fn() => new ReviewContextLoader());

        $this->app->singleton(AiDesignReviewer::class, fn($app) => new AiDesignReviewer(
            provider: new \App\Services\Agent\AnthropicProvider(
                \App\Models\AiSetting::current()->anthropicKey()
            ),
            usageLog: $app->make(AgentUsageLogService::class),
        ));

        $this->app->singleton(DesignReviewService::class, fn($app) => new DesignReviewService(
            contextLoader: $app->make(ReviewContextLoader::class),
            aiReviewer:    $app->make(AiDesignReviewer::class),
            clientFactory: $app->make(FigmaClientFactory::class),
            traceability:  $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(DesignSystemDataContext::class, fn() => new DesignSystemDataContext());

        $this->app->singleton(DesignSystemTemplateService::class, fn() => new DesignSystemTemplateService());

        $this->app->singleton(DesignSystemAiService::class, fn($app) => new DesignSystemAiService(
            // OpenAI primary, Claude (Anthropic) secondary.
            // 사유: Anthropic 결제 이슈로 현재 Claude 사용 불가. 결제 해결 시 primary/secondary 를
            // swap (Claude primary, OpenAI secondary) 하거나, 단독으로 가려면 AnthropicProvider 만
            // 주입. FallbackAIProvider 구조는 둘 다 시도 가능하므로 한 쪽이 장애여도 계속 동작.
            provider: new \App\Services\Agent\FallbackAIProvider(
                primary:   new \App\Services\Agent\OpenAiProvider(
                    \App\Models\AiSetting::current()->openaiKey()
                ),
                secondary: new \App\Services\Agent\AnthropicProvider(
                    \App\Models\AiSetting::current()->anthropicKey()
                ),
            ),
            usageLog: $app->make(AgentUsageLogService::class),
        ));

        $this->app->singleton(DevHandoffService::class, fn($app) => new DevHandoffService(
            clientFactory: $app->make(FigmaClientFactory::class),
        ));

        $this->app->singleton(DesignCompletionService::class);

        $this->app->singleton(RbacAiService::class, fn($app) => new RbacAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(ApiSpecAiService::class, fn($app) => new ApiSpecAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(ErdAiService::class, fn($app) => new ErdAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(CodeGenPromptAiService::class, fn($app) => new CodeGenPromptAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(FrontendCodeAiService::class, fn($app) => new FrontendCodeAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(FrontendCodePreviewService::class);

        $this->app->singleton(CodeStaticAnalyzer::class);

        $this->app->singleton(AiCodeReviewer::class, fn($app) => new AiCodeReviewer(
            usageLog: $app->make(AgentUsageLogService::class),
            prompts:  $app->make(PromptLibraryService::class),
        ));

        $this->app->singleton(CodeValidationService::class, fn($app) => new CodeValidationService(
            staticAnalyzer: $app->make(CodeStaticAnalyzer::class),
            aiReviewer:     $app->make(AiCodeReviewer::class),
            traceability:   $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(DevPrepCompletionService::class);

        $this->app->singleton(ApiCallExtractor::class);
        $this->app->singleton(BackendEndpointExtractor::class);
        $this->app->singleton(ApiIntegrationService::class, fn($app) => new ApiIntegrationService(
            callExtractor:     $app->make(ApiCallExtractor::class),
            endpointExtractor: $app->make(BackendEndpointExtractor::class),
        ));

        $this->app->singleton(CodeReviewService::class, fn($app) => new CodeReviewService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(BackendCodeAiService::class, fn($app) => new BackendCodeAiService(
            usageLog:     $app->make(AgentUsageLogService::class),
            prompts:      $app->make(PromptLibraryService::class),
            traceability: $app->make(TraceabilityService::class),
        ));

        $this->app->singleton(IssueAggregator::class);

        $this->app->singleton(DeployGuideDataContext::class);

        $this->app->singleton(DeployGuideService::class, fn($app) => new DeployGuideService(
            dataContext: $app->make(DeployGuideDataContext::class),
        ));

        $this->app->singleton(UserManualDataContext::class);

        $this->app->singleton(UserManualService::class, fn($app) => new UserManualService(
            dataContext:  $app->make(UserManualDataContext::class),
            figmaFactory: $app->make(\App\Services\Agent\Figma\FigmaClientFactory::class),
        ));

        $this->app->singleton(MigrationGuideDataContext::class);

        $this->app->singleton(MigrationGuideService::class, fn($app) => new MigrationGuideService(
            dataContext: $app->make(MigrationGuideDataContext::class),
        ));

        $this->app->singleton(ReleasePackageService::class, fn($app) => new ReleasePackageService(
            deployGuideService:    $app->make(DeployGuideService::class),
            userManualService:     $app->make(UserManualService::class),
            migrationGuideService: $app->make(MigrationGuideService::class),
        ));

        $this->app->singleton(DevCompletionService::class, fn($app) => new DevCompletionService(
            issueAggregator: $app->make(IssueAggregator::class),
        ));

        $this->app->singleton(ReleaseCompletionService::class);

        $this->app->singleton(FixOrchestrator::class, fn($app) => new FixOrchestrator(
            aggregator:     $app->make(IssueAggregator::class),
            codeValidation: $app->make(CodeValidationService::class),
            codeReview:     $app->make(CodeReviewService::class),
        ));
    }

    public function boot(): void
    {
        $appUrl = config('app.url');
        if ($appUrl) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        View::composer('ai-agent.*', AiAgentComposer::class);

        // 유지보수 요청 화면 공통 라벨/색상 주입
        View::composer('maint-requests.*', function ($view) {
            $view->with([
                'priorityLabels' => [
                    'normal'   => '일반',
                    'urgent'   => '긴급',
                    'critical' => '초긴급',
                    'recheck'  => '재확인',
                ],
                'priorityStyles' => [
                    'normal'   => 'background:#f4f4f5;color:#52525b;',
                    'urgent'   => 'background:#fef3c7;color:#92400e;',
                    'critical' => 'background:#fee2e2;color:#991b1b;',
                    'recheck'  => 'background:#e0e7ff;color:#3730a3;',
                ],
                'statusLabels' => [
                    'draft'             => '작성중',
                    'requested'         => '요청',
                    'planned'           => '개발예정',
                    'in_progress'       => '진행중',
                    'pending_check'     => '확인대기',
                    'discussion_needed' => '논의필요',
                    'on_hold'           => '보류',
                    'awaiting_file'     => '파일대기',
                    'replied'           => '답변완료',
                    'review_requested'  => '검토요청',
                    'review_again'      => '재확인',
                    'completed'         => '완료',
                ],
                'statusStyles' => [
                    'draft'             => 'background:#f4f4f5;color:#71717a;',
                    'requested'         => 'background:#dbeafe;color:#1e40af;',
                    'planned'           => 'background:#e0e7ff;color:#3730a3;',
                    'in_progress'       => 'background:#fef3c7;color:#92400e;',
                    'pending_check'     => 'background:#fce7f3;color:#9d174d;',
                    'discussion_needed' => 'background:#fee2e2;color:#991b1b;',
                    'on_hold'           => 'background:#f3e8ff;color:#6b21a8;',
                    'awaiting_file'     => 'background:#e0f2fe;color:#075985;',
                    'replied'           => 'background:#dcfce7;color:#166534;',
                    'review_requested'  => 'background:#ffedd5;color:#9a3412;',
                    'review_again'      => 'background:#fee2e2;color:#991b1b;',
                    'completed'         => 'background:#d1fae5;color:#065f46;',
                ],
            ]);
        });

        // Works Builder Policy 등록 (명세 v11 §1.4.5)
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\WorksBuilder\Task::class,
            \App\Policies\WorksBuilder\TaskPolicy::class,
        );
    }
}
