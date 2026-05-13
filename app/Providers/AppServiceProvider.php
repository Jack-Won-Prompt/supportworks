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
use App\Services\PromptRefiner\Llm\ClaudeProvider;
use App\Services\PromptRefiner\Llm\LlmRouter;
use App\Services\PromptRefiner\Llm\OpenAiProvider;
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
            provider: new \App\Services\Agent\AnthropicProvider(
                \App\Models\AiSetting::current()->anthropicKey()
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
    }
}
