<?php

namespace App\Http\Controllers;

use App\Mail\AiOutputMail;
use App\Models\AiMessage;
use App\Models\AiSession;
use App\Models\AiSetting;
use App\Models\ExecutionFile;
use App\Models\FigmaFile;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Prompt;
use App\Models\PromptCategory;
use App\Models\PromptExecution;
use App\Services\ProjectNotificationService;
use App\Services\AiOrchestrator;
use App\Services\ClaudeService;
use App\Services\DocxWriter;
use App\Models\SystemErrorLog;
use App\Services\ExcelWriter;
use App\Services\FigmaService;
use App\Services\PptxWriter;
use App\Services\ManusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AiController extends Controller
{
    // ?�?� 메인 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function index(Request $request)
    {
        $user     = auth()->user();
        $settings = AiSetting::current();

        $figmaFiles = FigmaFile::where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        $sessions = AiSession::where('user_id', $user->id)
            ->with(['figmaFile', 'project:id,name'])
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $sessionId = $request->query('session');
        $session   = null;
        $messages  = collect();

        if ($sessionId) {
            $session = AiSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->with(['messages', 'figmaFile'])
                ->first();
            if ($session) {
                $messages = $session->messages;
            }
        }

        $projects = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);

        // 같�? ?�(company_group)??공유??웍스 ?�션
        $teamSharedSessions = collect();
        if ($user->company_group_id) {
            $teamSharedSessions = AiSession::where('is_shared', true)
                ->whereHas('user', fn($q) => $q
                    ->where('company_group_id', $user->company_group_id)
                    ->where('id', '!=', $user->id))
                ->with('user')
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get();
        }

        return view('ai.index', compact('settings', 'figmaFiles', 'sessions', 'session', 'messages', 'projects', 'teamSharedSessions'));
    }

    // ?�?� API ?�정 ?�???�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function saveSettings(Request $request)
    {
        $s = AiSetting::current();

        if ($request->filled('anthropic_key') && $request->anthropic_key !== '••••••••') {
            $s->anthropic_key = encrypt(trim($request->anthropic_key));
        }
        if ($request->filled('openai_key') && $request->openai_key !== '••••••••') {
            $s->openai_key = encrypt(trim($request->openai_key));
        }
        if ($request->filled('figma_token') && $request->figma_token !== '••••••••') {
            $s->figma_token = encrypt(trim($request->figma_token));
        }
        if ($request->filled('manus_key') && $request->manus_key !== '••••••••') {
            $s->manus_key = encrypt(trim($request->manus_key));
        }
        if ($request->filled('manus_endpoint')) {
            $s->manus_endpoint = trim($request->manus_endpoint);
        }
        $s->save();

        return response()->json(['ok' => true]);
    }

    // ?�?� Figma ?�일 관�??�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function addFigmaFile(Request $request)
    {
        $request->validate(['url' => 'required|url']);

        $key = FigmaFile::extractKey($request->url);
        if (!$key) {
            return response()->json(['ok' => false, 'error' => '?�효??Figma URL???�닙?�다.'], 422);
        }

        if (FigmaFile::where('user_id', auth()->id())->where('file_key', $key)->exists()) {
            return response()->json(['ok' => false, 'error' => '?��? 추�????�일?�니??'], 422);
        }

        $file = FigmaFile::create([
            'user_id'  => auth()->id(),
            'url'      => $request->url,
            'file_key' => $key,
            'name'     => 'Figma ?�일',
        ]);

        $settings = AiSetting::current();
        if ($settings->figmaToken()) {
            try {
                $svc  = new FigmaService($settings->figmaToken());
                $info = $svc->getStructure($key);
                $file->update([
                    'name'           => $info['name'],
                    'thumbnail_url'  => $svc->getThumbnail($key),
                    'last_synced_at' => now(),
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
                \Log::warning('[Figma] sync failed: ' . $e->getMessage());
            }
        }

        return response()->json(['ok' => true, 'file' => $file->fresh()]);
    }

    public function syncFigmaFile(FigmaFile $file)
    {
        abort_if($file->user_id !== auth()->id(), 403);

        $settings = AiSetting::current();
        if (!$settings->figmaToken()) {
            return response()->json(['ok' => false, 'error' => 'Figma ?�큰???�정?��? ?�았?�니??'], 422);
        }

        try {
            $svc  = new FigmaService($settings->figmaToken());
            $info = $svc->getStructure($file->file_key);
            $file->update([
                'name'           => $info['name'],
                'thumbnail_url'  => $svc->getThumbnail($file->file_key),
                'last_synced_at' => now(),
            ]);
            return response()->json(['ok' => true, 'file' => $file->fresh(), 'structure' => $info]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'warning');
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function deleteFigmaFile(FigmaFile $file)
    {
        abort_if($file->user_id !== auth()->id(), 403);
        $file->delete();
        return response()->json(['ok' => true]);
    }

    // ?�?� ?�션 관�??�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function createSession(Request $request)
    {
        $devSettings = null;
        if ($request->agent_type === 'dev' && $request->has('dev_settings')) {
            $ds = $request->dev_settings;
            $devSettings = array_filter([
                'framework'         => $ds['framework'] ?? null,
                'framework_version' => $ds['framework_version'] ?? null,
                'runtime_version'   => $ds['runtime_version'] ?? null,
                'frontend_stack'    => $ds['frontend_stack'] ?? null,
                'db_type'           => $ds['db_type'] ?? null,
                'db_version'        => $ds['db_version'] ?? null,
            ]);
        }

        if ($request->agent_type === 'figma' && $request->has('figma_settings')) {
            $fs = $request->figma_settings;
            $devSettings = array_filter([
                'figma_url'          => $fs['figma_url']          ?? null,
                'figma_node_id'      => $fs['figma_node_id']      ?? null,
                'target_path'        => $fs['target_path']        ?? null,
                'integration_level'  => $fs['integration_level']  ?? 'new',
            ]);
        }

        if ($request->agent_type === 'builder' && $request->has('dev_settings')) {
            $bs = $request->dev_settings;
            $devSettings = ['builder_step' => $bs['builder_step'] ?? 'STEP_1'];
        }

        $session = AiSession::create([
            'user_id'          => auth()->id(),
            'figma_file_id'    => $request->figma_file_id ?: null,
            'project_id'       => $request->project_id ?: null,
            'prompt_category'  => $request->prompt_category ?: null,
            'agent_type'       => $request->agent_type ?: 'general',
            'dev_settings'     => $devSettings ?: null,
            'doc_type'         => $request->doc_type ?: null,
            'output_filename'  => $request->output_filename ?: null,
            'output_extension' => $request->output_extension ?: null,
            'title'            => '새 대화',
        ]);
        return response()->json(['ok' => true, 'session' => $session->load(['figmaFile', 'project:id,name'])]);
    }

    // ?�?� ?�롬?�트 Lifecycle: ?�롬?�트 ?�성 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function generatePrompt(Request $request, AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $request->validate(['content' => 'required|string|max:8000']);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니??'], 422);
        }

        $agentType    = $session->agent_type ?? 'general';
        $systemPrompt = \App\Services\AiPrompts::promptGeneratorSystem($agentType);

        // 컨텍?�트 빌드
        $contextParts = [];
        if ($session->project) {
            $contextParts[] = "?�로?�트: {$session->project->name}";
        }
        if ($agentType === 'dev' && $session->dev_settings) {
            $ds = $session->dev_settings;
            if (!empty($ds['framework'])) $contextParts[] = "?�레?�워?? {$ds['framework']}";
            if (!empty($ds['db_type']))   $contextParts[] = "DB: {$ds['db_type']}";
        }
        if ($agentType === 'document' && $session->doc_type) {
            $contextParts[] = "문서 ?�형: {$session->doc_type}";
        }
        if ($agentType === 'figma' && $session->dev_settings) {
            $fs = $session->dev_settings;
            if (!empty($fs['figma_url']))   $contextParts[] = "Figma URL: {$fs['figma_url']}";
            if (!empty($fs['target_path'])) $contextParts[] = "출력 경로: {$fs['target_path']}";
        }

        $userContent = $request->content;
        if ($contextParts) {
            $userContent = '[?�업 컨텍?�트]' . "\n" . implode("\n", $contextParts) . "\n\n" . $userContent;
        }

        $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
        try {
            $raw = $orchestrator->chatRaw([['role' => 'user', 'content' => $userContent]], $systemPrompt);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['ok' => false, 'error' => '?�롬?�트 ?�성???�패?�습?�다.'], 500);
        }

        // JSON 추출
        $raw = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $m)) {
            $raw = $m[1];
        } elseif (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        $draft = json_decode($raw, true);
        if (!$draft) {
            return response()->json(['ok' => false, 'error' => '?�롬?�트 ?�싱???�패?�습?�다.', 'raw' => $raw], 500);
        }

        return response()->json(['ok' => true, 'draft' => $draft]);
    }

    public function getSession(AiSession $session)
    {
        $user = auth()->user();

        if ($session->user_id !== $user->id) {
            // ?�?�이 공유???�션 조회 ?�용
            abort_if(
                !$session->is_shared ||
                !$user->company_group_id ||
                $user->company_group_id !== $session->user->company_group_id,
                403
            );
        }

        return response()->json([
            'ok'       => true,
            'session'  => $session->load(['figmaFile', 'messages', 'user:id,name', 'project:id,name']),
            'is_owner' => $session->user_id === $user->id,
        ]);
    }

    public function shareSession(AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $session->update(['is_shared' => !$session->is_shared]);

        return response()->json(['ok' => true, 'shared' => $session->is_shared]);
    }

    public function forkSession(AiSession $session)
    {
        $user = auth()->user();

        abort_if($session->user_id === $user->id, 422, '본인???�?�입?�다.');
        abort_if(
            !$session->is_shared ||
            !$user->company_group_id ||
            $user->company_group_id !== $session->user->company_group_id,
            403
        );

        $fork = AiSession::create([
            'user_id' => $user->id,
            'title'   => $session->title,
        ]);

        foreach ($session->messages as $msg) {
            $fork->messages()->create([
                'role'        => $msg->role,
                'content'     => $msg->content,
                'html_output' => $msg->html_output,
                'css_output'  => $msg->css_output,
                'js_output'   => $msg->js_output,
                'ai_provider' => $msg->ai_provider,
            ]);
        }

        return response()->json([
            'ok'      => true,
            'session' => $fork->load('messages'),
        ]);
    }

    public function deleteSession(AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);
        $session->delete();
        return response()->json(['ok' => true]);
    }

    // ?�?� 웍스 메시지 ?�송 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function sendMessage(Request $request, AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $request->validate([
            'content'  => 'required|string|max:8000',
            'files.*'  => 'nullable|file|max:10240',
            'urls.*'   => 'nullable|string|max:500',
        ]);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니?? ?�정?�서 Anthropic ?�는 OpenAI API ?��? ?�록?�세??'], 422);
        }

        // ?�?� ?�인???�롬?�트가 ?�으�?content�?refined_prompt�?교체 ?�?�
        $approvedPrompt = null;
        if ($request->filled('approved_prompt')) {
            $ap = is_array($request->approved_prompt) ? $request->approved_prompt : json_decode($request->approved_prompt, true);
            if (is_array($ap) && !empty($ap['refined_prompt'])) {
                $approvedPrompt = $ap;
            }
        }

        // ?�?� 첨�? 컨텍?�트 빌드 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�
        $attachmentContext = '';
        $textExts = ['txt','md','html','htm','css','js','ts','jsx','tsx','vue','json','csv',
                     'php','py','java','xml','yaml','yml','sql','sh','log','ini','conf','env'];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $ext  = strtolower($file->getClientOriginalExtension());
                $name = $file->getClientOriginalName();
                if (in_array($ext, $textExts)) {
                    $text = mb_substr(file_get_contents($file->getRealPath()), 0, 5000);
                    $attachmentContext .= "\n\n### 첨�? ?�일: {$name}\n```{$ext}\n{$text}\n```";
                } elseif (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
                    $attachmentContext .= "\n\n### 첨�? ?��?지: {$name} (?��?지 ?�일)";
                } elseif ($ext === 'pdf') {
                    $attachmentContext .= "\n\n### 첨�? PDF: {$name} (PDF ?�일)";
                } elseif (in_array($ext, ['docx', 'doc'])) {
                    $text = $this->extractWordText($file->getRealPath());
                    $attachmentContext .= "\n\n### 첨�? Word: {$name}\n" . mb_substr($text, 0, 5000);
                } elseif (in_array($ext, ['xlsx', 'xls'])) {
                    $text = $this->extractExcelText($file->getRealPath());
                    $attachmentContext .= "\n\n### 첨�? Excel: {$name}\n" . mb_substr($text, 0, 5000);
                } elseif (in_array($ext, ['pptx', 'ppt'])) {
                    $text = $this->extractPptxText($file->getRealPath());
                    $attachmentContext .= "\n\n### 첨�? PowerPoint: {$name}\n" . mb_substr($text, 0, 5000);
                } elseif ($ext === 'zip') {
                    $zip = new \ZipArchive();
                    if ($zip->open($file->getRealPath()) === true) {
                        // ?�체 ?�일 목록 ?�집
                        $zipFiles = [];
                        for ($zi = 0; $zi < $zip->numFiles; $zi++) {
                            $stat  = $zip->statIndex($zi);
                            $ename = $stat['name'];
                            if (str_ends_with($ename, '/')) continue;
                            $eext  = strtolower(pathinfo($ename, PATHINFO_EXTENSION));
                            $econt = $zip->getFromIndex($zi);
                            if ($econt !== false) {
                                $zipFiles[$ename] = ['ext' => $eext, 'content' => $econt];
                            }
                        }
                        $zip->close();

                        // ???�로?�트 감�? (HTML ?�일 ?�함 ?��?)
                        $htmlEntries = array_filter($zipFiles, fn($f) => in_array($f['ext'], ['html', 'htm']));

                        if (!empty($htmlEntries)) {
                            // 메인 HTML ?�택 (index.html ?�선)
                            $mainKey = null;
                            foreach (array_keys($htmlEntries) as $k) {
                                if (strtolower(basename($k)) === 'index.html') { $mainKey = $k; break; }
                            }
                            if (!$mainKey) $mainKey = array_key_first($htmlEntries);

                            $htmlContent = $zipFiles[$mainKey]['content'];

                            // <link href="*.css"> ??<style> ?�라?�화
                            $htmlContent = preg_replace_callback(
                                '/<link\b[^>]+href=["\']([^"\']+\.css)["\'][^>]*\/?>/i',
                                function ($m) use ($zipFiles) {
                                    $ref = basename($m[1]);
                                    foreach ($zipFiles as $fname => $fdata) {
                                        if ($fdata['ext'] === 'css' && basename($fname) === $ref) {
                                            return "<style>\n{$fdata['content']}\n</style>";
                                        }
                                    }
                                    return $m[0];
                                },
                                $htmlContent
                            );

                            // <script src="*.js"> ??<script> ?�라?�화
                            $htmlContent = preg_replace_callback(
                                '/<script\b[^>]+src=["\']([^"\']+\.js)["\'][^>]*>\s*<\/script>/i',
                                function ($m) use ($zipFiles) {
                                    $ref = basename($m[1]);
                                    foreach ($zipFiles as $fname => $fdata) {
                                        if ($fdata['ext'] === 'js' && basename($fname) === $ref) {
                                            return "<script>\n{$fdata['content']}\n</script>";
                                        }
                                    }
                                    return $m[0];
                                },
                                $htmlContent
                            );

                            $combined = mb_substr($htmlContent, 0, 8000);
                            $attachmentContext .= "\n\n### 첨�? ZIP: {$name} (???�로?�트)"
                                . "\n?�래??CSS·JS�??�라?�으�??�친 ?�일 HTML?�니??"
                                . "\n?�용?��? ?�행·미리보기·?�정???�청?�면 ??HTML??그�?�??�는 ?�정?�여 html 출력?�로 ?�용?�세??"
                                . "\n```html\n{$combined}\n```";
                        } else {
                            // 비웹 ?�로?�트: ?�스???�일�?컨텍?�트�?추�?
                            $attachmentContext .= "\n\n### 첨�? ZIP: {$name}";
                            $zipChars = 0;
                            $zipMax   = 8000;
                            foreach ($zipFiles as $ename => $edata) {
                                if ($zipChars >= $zipMax) {
                                    $attachmentContext .= "\n(?�량 초과�??�후 ?�일 ?�략)";
                                    break;
                                }
                                if (!in_array($edata['ext'], $textExts)) continue;
                                $content   = mb_substr($edata['content'], 0, 2000);
                                $attachmentContext .= "\n\n#### {$ename}\n```{$edata['ext']}\n{$content}\n```";
                                $zipChars += mb_strlen($content);
                            }
                        }
                    }
                }
            }
        }

        foreach ((array) $request->input('urls', []) as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) continue;
            try {
                $resp = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SupportWorksBot/1.0)'])
                    ->timeout(8)->get($url);
                $html = $resp->body();
                $html = preg_replace('/<(script|style)[^>]*>.*?<\/(script|style)>/si', '', $html);
                $text = strip_tags($html);
                $text = mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 3000);
                $attachmentContext .= "\n\n### URL 참조: {$url}\n{$text}";
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
                $attachmentContext .= "\n\n### URL 참조: {$url}\n(?�용??가?�올 ???�습?�다)";
            }
        }
        // ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

        // 첨�? 컨텍?�트�?메시지 ?�용�??�쳐 DB???�??(?�션 ?�스?�리?�서 ?��??�도�?
        $savedContent = $attachmentContext
            ? $request->content . "\n\n---\n## 첨�? 컨텍?�트\n" . $attachmentContext
            : $request->content;

        // approved_prompt가 ?�으�?refined_prompt�??�제 ?�행 ?�용?�로 ?�용
        $executionContent = $savedContent;
        if ($approvedPrompt && !empty($approvedPrompt['refined_prompt'])) {
            $executionContent = $approvedPrompt['refined_prompt'];
            if ($attachmentContext) {
                $executionContent .= "\n\n---\n## 첨�? 컨텍?�트\n" . $attachmentContext;
            }
        }

        AiMessage::create([
            'session_id'     => $session->id,
            'role'           => 'user',
            'content'        => $savedContent,
            'prompt_draft'   => $approvedPrompt ?: null,
            'prompt_approved'=> !empty($approvedPrompt),
        ]);

        if ($session->title === '새 대화') {
            $session->update(['title' => mb_substr($request->content, 0, 40)]);
        }

        $figmaContext = null;
        if ($session->figmaFile && $settings->figmaToken()) {
            try {
                $svc          = new FigmaService($settings->figmaToken());
                $info         = $svc->getStructure($session->figmaFile->file_key);
                $figmaContext = "?�일�? {$info['name']}\n";
                foreach ($info['pages'] as $p) {
                    $figmaContext .= "?�이지: {$p['page']} | ?�레?? " . implode(', ', $p['frames']) . "\n";
                }
            } catch (\Throwable $e) {
                \Log::warning('[Figma context] ' . $e->getMessage());
            }
        }

        $session->load('messages');
        $claudeMessages = $session->toClaudeMessages();

        // ?�?� ?�일 ?�정 명시 ?�청 (?�론?�엔?�에??modify_file_type ?�달) ?�?�
        $modifyFileType = $request->input('modify_file_type');
        if ($modifyFileType) {
            if ($modifyFileType === 'pptx') {
                return $this->handlePptxInSendMessage($request, $session, $settings);
            } elseif (in_array($modifyFileType, ['excel', 'xlsx'])) {
                return $this->handleExcelInSendMessage($request, $session, $settings);
            } elseif ($modifyFileType === 'minutes') {
                return $this->handleMinutesInSendMessage($request, $session, $settings);
            } elseif (in_array($modifyFileType, ['word', 'docx'])) {
                return $this->handleWordDocInSendMessage($request, $session, $settings);
            }
            // ?????�는 ?�?��? 무시?�고 ?�반 웍스 처리�?fallthrough
        }

        // Agent ?�형 조기 ?�인 ??builder???�워??감�? ?�이 ??�� ?�용 ?�들?�로 처리
        $agentType = $session->agent_type ?? 'general';

        // ?�?� ?�의�??�워?�인???��?/?�드 ?�워??감�? (builder ?�외) ?�?�
        if ($agentType !== 'builder') {
            if ($this->isMinutesRequest($request->content)) {
                return $this->handleMinutesInSendMessage($request, $session, $settings);
            }
            if ($this->isPptxRequest($request->content)) {
                return $this->handlePptxInSendMessage($request, $session, $settings);
            }
            if ($this->isExcelRequest($request->content)) {
                return $this->handleExcelInSendMessage($request, $session, $settings);
            }
            if ($this->isWordDocRequest($request->content)) {
                return $this->handleWordDocInSendMessage($request, $session, $settings);
            }
        }

        // ?�?� ?�청 ?�형 분류: code / document / mixed ?�?�?�?�?�?�?�?�?�?�?�?�?�?�
        $reqType  = ManusService::classifyRequest($request->content);
        $result   = null;
        $provider = null;
        $docInfo  = null;

        $systemOverride = null;

        // ?�로?�트 ?�일 컨텍?�트 주입
        $contextMode = $request->input('context_mode', 'all');
        if ($session->project_id && $contextMode !== 'none') {
            $fileCtx = (new \App\Services\AiFileService())->buildContext(
                auth()->id(),
                $session->project_id,
                $contextMode,
                $session->id
            );
            if ($fileCtx) {
                $systemOverride = $fileCtx;
            }
        }

        if ($agentType === 'figma') {
            // Prefer request-provided figma_settings over session-stored dev_settings
            $reqFigma = $request->input('figma_settings');
            $figmaCtx = (is_array($reqFigma) && count($reqFigma))
                ? $reqFigma
                : ($session->dev_settings ?? []);

            if (!empty($figmaCtx)) {
                // Figma API ?�큰???�고 URL?�서 ?�일 ?��? 추출?????�으�??�제 ?�이??로드
                if (!empty($figmaCtx['figma_url']) && $settings->figmaToken()) {
                    $fileKey = FigmaFile::extractKey($figmaCtx['figma_url']);
                    if ($fileKey) {
                        try {
                            $svc  = new FigmaService($settings->figmaToken());
                            $nodeId = $figmaCtx['figma_node_id'] ?? null;
                            $figmaCtx['figma_api_data'] = $svc->buildAiContext($fileKey, $nodeId);
                        } catch (\Throwable $e) {
                            \Log::warning('[Figma Agent] buildAiContext ?�패: ' . $e->getMessage());
                        }
                    }
                }

                $agentPrompt    = \App\Services\AiPrompts::figmaAgentSystem($figmaCtx);
                $systemOverride = $systemOverride ? $systemOverride . "\n\n" . $agentPrompt : $agentPrompt;
            }

        } elseif ($agentType === 'dev') {
            // Prefer request-provided dev_settings over session-stored
            $reqDev = $request->input('dev_settings');
            $ctx = (is_array($reqDev) && count($reqDev)) ? $reqDev : ($session->dev_settings ?? []);
            if (!empty($ctx)) {
                $agentPrompt    = \App\Services\AiPrompts::agentSystem('dev', $ctx);
                $systemOverride = $systemOverride ? $systemOverride . "\n\n" . $agentPrompt : $agentPrompt;
            }

        } elseif ($agentType === 'document') {
            // Prefer request-provided doc_type over session-stored
            $reqDocType = $request->input('doc_type');
            $docType = $reqDocType ?: ($session->doc_type ?? '');
            if (!empty($docType)) {
                $agentPrompt    = \App\Services\AiPrompts::agentSystem('document', ['doc_type' => $docType]);
                $systemOverride = $systemOverride ? $systemOverride . "\n\n" . $agentPrompt : $agentPrompt;
            }

        } elseif ($agentType === 'builder') {
            $step = $request->input('builder_step') ?: ($session->dev_settings['builder_step'] ?? 'STEP_1');
            $agentPrompt    = \App\Services\AiPrompts::builderSystem($step);
            $systemOverride = $systemOverride ? $systemOverride . "\n\n" . $agentPrompt : $agentPrompt;
        }

        // Builder Agent: STEP�??�른 웍스 경로 ?�용
        if ($agentType === 'builder') {
            return $this->handleBuilderInSendMessage($request, $session, $settings, $claudeMessages, $systemOverride);
        }

        // 웍스 ?�답 ??�� 먼�? ?�득
        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['result' => $result, 'provider' => $provider] = $orchestrator->chat($claudeMessages, $figmaContext, $systemOverride);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        // 문서 ?�형?�고 Manus ?��? ?�정??경우 ?�일 ?�성 추�? ?�도
        if ($reqType !== 'code' && $settings->manusKey()) {
            try {
                $docType = ManusService::detectDocType($request->content);
                $manus   = new ManusService($settings->manusKey(), $settings->manusEndpoint());
                $docInfo = $manus->createDocument($request->content, $docType);
            } catch (\Throwable $e) {
                \Log::error('[ManusService] 문서 ?�성 ?�패: ' . $e->getMessage());
                // $docInfo stays null ??웍스 ?�답�??�용
            }
        }

        // ?�?� ?�답 메시지 조합 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�
        $contentText = $result['explanation'] ?? ($result['html'] ? '코드가 ?�성?�었?�니??' : '?�답???�성?�습?�다.');
        if ($docInfo && ($docInfo['status'] ?? '') !== 'failed' && !empty($docInfo['file_name'])) {
            $contentText .= "\n\n'{$docInfo['file_name']}' 문서 ?�일???�성?�었?�니?? ?�래 버튼?�로 ?�운로드?�세??";
        }

        $aiMsg = AiMessage::create([
            'session_id'     => $session->id,
            'role'           => 'assistant',
            'content'        => $contentText,
            'html_output'    => $result['html'] ?? null,
            'css_output'     => $result['css']  ?? null,
            'js_output'      => $result['js']   ?? null,
            'code_lang'      => $result['lang'] ?? null,
            'ai_provider'    => $provider ?? 'manus',
            'doc_file_name'  => $docInfo['file_name']    ?? null,
            'doc_file_type'  => $docInfo['file_type']    ?? null,
            'doc_download_url' => $docInfo['download_url'] ?? null,
            'doc_status'     => $docInfo['status']       ?? null,
            'doc_task_id'    => $docInfo['task_id']      ?? null,
        ]);

        // 프로젝트 파일 자동 저장
        if ($session->project_id && ($aiMsg->html_output || $aiMsg->css_output || $aiMsg->js_output)) {
            (new \App\Services\AiFileService())->saveFromMessage($aiMsg, $session);
        }

        PromptExecution::create([
            'user_id'        => auth()->id(),
            'session_id'     => $session->id,
            'prompt_id'      => $request->prompt_id ?: null,
            'project_id'     => $request->project_id ?: null,
            'raw_input'      => $request->content,
            'refined_prompt' => $request->refined_prompt ?: null,
            'ai_response'    => $aiMsg->content,
            'html_output'    => $result['html'] ?? null,
            'css_output'     => $result['css'] ?? null,
            'js_output'      => $result['js'] ?? null,
            'status'         => 'completed',
            'ai_provider'    => $provider,
        ]);

        $session->touch();

        $emailSent = false;
        if ($request->boolean('send_email')) {
            try {
                Mail::to($user->email)->send(new AiOutputMail($aiMsg, $user, $session->title));
                $emailSent = true;
            } catch (\Throwable $e) {
                \Log::warning('[웍스 Email] ?�송 ?�패: ' . $e->getMessage());
            }
        }

        return response()->json([
            'ok'            => true,
            'message'       => $aiMsg,
            'session_title' => $session->title,
            'email_sent'    => $emailSent,
        ]);
    }

    // ?�?� ?�??문맥 검???�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function contextCheck(Request $request, AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $newMsg = trim($request->input('content', ''));
        if (!$newMsg) {
            return response()->json(['ok' => true, 'is_new_context' => false]);
        }

        // ?�???�력???�으�???�� 같�? 맥락
        $history = $session->messages()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        if ($history->isEmpty()) {
            return response()->json(['ok' => true, 'is_new_context' => false]);
        }

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => true, 'is_new_context' => false]);
        }

        $contextLines = $history->map(fn($m) =>
            ($m->role === 'user' ? 'User' : '웍스') . ': ' . mb_substr($m->content, 0, 200)
        )->join("\n");

        $classifyPrompt = "?�재 ?�??\n{$contextLines}\n\n??메시지: {$newMsg}\n\n????메시지가 기존 ?�?��? ?�전???�른 주제/맥락?�면 '??, 같�? 맥락?�면 '?�니??로만 ?�하?�요.";

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['text' => $answer] = $orchestrator->chatRaw(
                [['role' => 'user', 'content' => $classifyPrompt]],
                "?�신?� ?�??맥락 분류 웍스?�니?? 반드??'?? ?�는 '?�니?? �??�나로만 ?�하?�요."
            );
            $isNew = str_starts_with(trim($answer), '예');
        } catch (\Throwable) {
            $isNew = false;
        }

        return response()->json(['ok' => true, 'is_new_context' => $isNew]);
    }

    // ?�?� ?�롬?�트 ?�제 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function refinePrompt(Request $request)
    {
        $request->validate([
            'input'    => 'required|string|max:5000',
            'existing' => 'nullable|array',
        ]);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니??'], 422);
        }

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['result' => $result, 'provider' => $provider] = $orchestrator->refinePrompt($request->input, $request->existing);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'data' => $result, 'provider' => $provider]);
    }

    // ?�?� ?�롬?�트 ?�이브러�??�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function promptIndex(Request $request)
    {
        $user      = auth()->user();
        $projectId = $request->query('project_id');
        $catId     = $request->query('category_id');
        $search    = $request->query('search');

        $projects = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get(['id', 'name']);

        $categories = PromptCategory::where(fn($q) => $q->where('source', 'system')->orWhere('created_by', $user->id))
            ->when($projectId, fn($q) => $q->where(fn($q2) => $q2->where('project_id', $projectId)->orWhereNull('project_id')))
            ->where('is_approved', true)
            ->orderBy('name')->get();

        $prompts = Prompt::with(['category', 'project'])
            ->where(fn($q) => $q->where('source', 'system')->orWhere('created_by', $user->id))
            ->where('status', 'approved')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($catId,     fn($q) => $q->where('category_id', $catId))
            ->when($search,    fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('final_prompt', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        if ($request->boolean('json')) {
            return response()->json([
                'ok'      => true,
                'prompts' => $prompts->map(fn($p) => [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'category_name' => $p->category?->name,
                    'final_prompt'  => $p->final_prompt,
                ]),
            ]);
        }

        return view('ai.prompts', compact('projects', 'categories', 'prompts', 'projectId', 'catId', 'search'));
    }

    public function storePrompt(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:200',
            'final_prompt'     => 'required|string',
            'category_id'      => 'nullable|exists:prompt_categories,id',
            'project_id'       => 'nullable|exists:projects,id',
            'type'             => 'nullable|string|max:100',
            'purpose'          => 'nullable|string',
            'ai_role'          => 'nullable|string',
            'input_data'       => 'nullable|string',
            'conditions'       => 'nullable|string',
            'output_format'    => 'nullable|string',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
        ]);

        $prompt = Prompt::create([
            ...$request->only([
                'name', 'category_id', 'project_id', 'type', 'purpose',
                'ai_role', 'input_data', 'conditions', 'output_format',
                'final_prompt', 'confidence_score',
            ]),
            'status'     => 'approved',
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'prompt' => $prompt->load('category')]);
    }

    public function updatePrompt(Request $request, Prompt $prompt)
    {
        abort_if($prompt->created_by !== auth()->id(), 403);

        $request->validate([
            'name'          => 'required|string|max:200',
            'final_prompt'  => 'required|string',
            'category_id'   => 'nullable|exists:prompt_categories,id',
            'project_id'    => 'nullable|exists:projects,id',
            'type'          => 'nullable|string|max:100',
            'purpose'       => 'nullable|string',
            'ai_role'       => 'nullable|string',
            'input_data'    => 'nullable|string',
            'conditions'    => 'nullable|string',
            'output_format' => 'nullable|string',
        ]);

        $prompt->update([
            ...$request->only([
                'name', 'category_id', 'project_id', 'type', 'purpose',
                'ai_role', 'input_data', 'conditions', 'output_format', 'final_prompt',
            ]),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'prompt' => $prompt->fresh()->load('category')]);
    }

    public function destroyPrompt(Prompt $prompt)
    {
        abort_if($prompt->created_by !== auth()->id(), 403);
        $prompt->delete();
        return response()->json(['ok' => true]);
    }

    // ?�?� 카테고리 관�??�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $cat = PromptCategory::create([
            'name'        => $request->name,
            'project_id'  => $request->project_id,
            'source'      => 'user',
            'is_approved' => true,
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'category' => $cat]);
    }

    public function destroyCategory(PromptCategory $category)
    {
        abort_if($category->created_by !== auth()->id(), 403);
        $category->delete();
        return response()->json(['ok' => true]);
    }

    // ?�?� ?�행 ?�력 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function executionIndex(Request $request)
    {
        $user = auth()->user();

        $executions = PromptExecution::where('user_id', $user->id)
            ->with(['prompt.category', 'project', 'files'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('ai.executions', compact('executions'));
    }

    public function executionShow(PromptExecution $execution)
    {
        abort_if($execution->user_id !== auth()->id(), 403);
        $execution->load(['prompt.category', 'project', 'session', 'files']);
        return view('ai.execution-show', compact('execution'));
    }

    // ?�?� Excel ?�청 감�? & 처리 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function isExcelRequest(string $text): bool
    {
        $lower = mb_strtolower($text);
        return str_contains($lower, '?��?')
            || str_contains($lower, 'excel')
            || str_contains($lower, 'xlsx')
            || str_contains($lower, '?�프?�드?�트')
            || str_contains($lower, 'spreadsheet')
            || (str_contains($lower, '?�로') && str_contains($lower, '?�리'))
            || str_contains($lower, '?�이?�표');
    }

    private function handleExcelInSendMessage(Request $request, AiSession $session, AiSetting $settings): \Illuminate\Http\JsonResponse
    {
        $session->load('messages');
        $messages = $session->messages;

        if ($messages->isEmpty()) {
            $msg = AiMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => '?�???�용???�습?�다.']);
            return response()->json(['ok' => true, 'message' => $msg, 'session_title' => $session->title, 'email_sent' => false]);
        }

        $transcript = $this->buildTranscript($messages);
        $user       = auth()->user();
        $company    = $user->company ?? ($user->name . ' 팀');
        $today      = now()->format('Y년m월d일');

        $systemPrompt = <<<PROMPT
?�신?� ?�이??분석 �?Excel 문서 ?�문가?�니??
주어�??�???�용??분석??Excel ?�트 구조�?JSON ?�식?�로�?출력?�세??
?��?�?JSON ?�의 ?�스?��? 출력?��? 마세??

출력 JSON ?�키�?
{
  "title": "Excel 문서 ?�목",
  "sheets": [
    {
      "name": "?�트�?짧게)",
      "title": "?�트 ?�목",
      "subtitle": "부?�목(?�택)",
      "headers": ["컬럼1", "컬럼2", "컬럼3"],
      "col_widths": [20, 15, 30],
      "rows": [
        ["?�이??", "?�이??", "?�이??"]
      ],
      "summary": ["?�계", "�?", "�?"]
    }
  ]
}

?�성 규칙:
- ?�???�용?�서 ??목록/?�이???�태�?구성 가?�한 모든 ?�용???�트�?구성
- ?�트???�용??주제별로 분리 (최�? 5�?
- ?�자 ?�이?�는 반드??JSON number ?�???�옴???�음)?�로 출력
- ?�자 ?�이?��? ?�으�?summary(?�계/?�균) ???�함
- col_widths??컬럼 ?�비(문자 ??기�?, 최소 10 ?�상)
- ?�더 ?�름?� 간결?�고 명확?�게
- company: "{$company}", ?�늘: {$today}
- ?�국?�로 ?�성
PROMPT;

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['text' => $rawText, 'provider' => $provider] = $orchestrator->chatRaw(
                [['role' => 'user', 'content' => "?�음 ?�???�용??Excel 문서�?만들?�주?�요:\n\n{$transcript}"]],
                $systemPrompt
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $rawText  = trim(preg_replace(['/^```(?:json)?\s*/m', '/```\s*$/m'], '', $rawText));
        $jsonData = json_decode($rawText, true);

        if (!$jsonData || empty($jsonData['sheets'])) {
            $jsonData = ['title' => $session->title ?: '데이터', 'sheets' => [
                ['name' => '데이터', 'title' => $session->title ?: '데이터',
                 'headers' => ['항목', '내용'], 'rows' => [['생성 실패', '다시 시도해주세요']]],
            ]];
        }

        $fileName    = 'xlsx-' . now()->format('YmdHis') . '.xlsx';
        $filePath    = storage_path('app/public/ai-downloads/' . $fileName);
        $downloadUrl = request()->getBasePath() . '/ai/excel/download?file=' . rawurlencode($fileName);
        $title       = $session->title ?: '데이터';
        $date        = now()->format('Y-m-d');

        (new ExcelWriter($jsonData['title'] ?? $title))->loadFromJson($jsonData)->save($filePath);

        $summary = "## {$title}\n\n" . collect($jsonData['sheets'])->map(fn($s) =>
            "### {$s['name']}\n" . collect($s['rows'] ?? [])->take(3)->map(fn($r) =>
                '- ' . implode(' | ', array_map('strval', $r)))->join("\n")
        )->join("\n\n");

        $aiMsg = AiMessage::create([
            'session_id'       => $session->id,
            'role'             => 'assistant',
            'content'          => $summary,
            'ai_provider'      => $provider,
            'doc_file_name'    => "{$title} ({$date}).xlsx",
            'doc_file_type'    => 'xlsx',
            'doc_download_url' => $downloadUrl,
            'doc_status'       => 'completed',
        ]);

        PromptExecution::create([
            'user_id' => auth()->id(), 'session_id' => $session->id,
            'raw_input' => $request->content, 'ai_response' => $summary,
            'status' => 'completed', 'ai_provider' => $provider,
        ]);

        $session->touch();
        return response()->json(['ok' => true, 'message' => $aiMsg, 'session_title' => $session->title, 'email_sent' => false]);
    }

    public function exportExcel(Request $request, AiSession $session): \Illuminate\Http\JsonResponse
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니??'], 422);
        }

        $session->load('messages');
        if ($session->messages->isEmpty()) {
            return response()->json(['ok' => false, 'error' => '?�???�용???�습?�다.'], 422);
        }

        // ?�시�?request content�??�정?�고 sendMessage ?�들???�사??        $fakeRequest = new \Illuminate\Http\Request();
        $fakeRequest->merge(['content' => '?��?�??�리?�주?�요']);

        return $this->handleExcelInSendMessage($fakeRequest, $session, $settings);
    }

    public function downloadExcel(Request $request)
    {
        $file = $request->query('file');
        abort_if(!$file || !preg_match('/^xlsx-[\d]+\.xlsx$/', $file), 404);

        $owned = AiMessage::whereHas('session', fn($q) => $q->where('user_id', auth()->id()))
            ->where('doc_download_url', 'like', '%' . $file . '%')
            ->exists();
        abort_if(!$owned, 403);

        $path = storage_path('app/public/ai-downloads/' . $file);
        abort_if(!file_exists($path), 404);

        return response()->download($path, '?�이??' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ?�?� Word 문서 ?�청 감�? & 처리 (?�반 보고??문서) ?�?�?�?�?�?�?�?�?�?�?�?�

    private function isWordDocRequest(string $text): bool
    {
        $lower = mb_strtolower($text);
        // ?�의록�? isMinutesRequest가 처리?��?�??�외
        $hasWord =
            str_contains($lower, '워드')        || str_contains($lower, '워드파일')   ||
            str_contains($lower, '워드 파일')   || str_contains($lower, '워드 문서')  ||
            str_contains($lower, 'word로')      || str_contains($lower, 'word파일')   ||
            str_contains($lower, 'word 파일')   || str_contains($lower, 'word 문서')  ||
            str_contains($lower, '.docx')       || str_contains($lower, 'docx')        ||
            str_contains($lower, '문서로작성')  || str_contains($lower, '문서 작성')  ||
            str_contains($lower, '보고서로')    || str_contains($lower, '리포트로');
        $notMinutes = !str_contains($lower, '회의록') && !str_contains($lower, '미팅노트');
        return $hasWord && $notMinutes;
    }

    private function handleWordDocInSendMessage(Request $request, AiSession $session, AiSetting $settings): \Illuminate\Http\JsonResponse
    {
        $session->load('messages');
        $messages = $session->messages;

        if ($messages->isEmpty()) {
            $msg = AiMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => '?�???�용???�습?�다.']);
            return response()->json(['ok' => true, 'message' => $msg, 'session_title' => $session->title, 'email_sent' => false]);
        }

        $transcript = $this->buildTranscript($messages);
        $user       = auth()->user();
        $company    = $user->company ?? ($user->name . ' 팀');
        $today      = now()->format('Y년m월d일');

        $systemPrompt = <<<PROMPT
?�신?� ?�문 문서 ?�성 ?�우미입?�다.
주어�??�???�용??바탕?�로 Word 문서 구조�?JSON ?�식?�로�?출력?�세??
?��?�?JSON ?�의 ?�스?��? 출력?��? 마세??

출력 JSON ?�키�?
{
  "title": "문서 ?�목",
  "subtitle": "부?�목(?�택)",
  "author": "?�성??,
  "date": "?�짜",
  "sections": [
    { "type": "heading", "level": 1, "text": "?�션 ?�목" },
    { "type": "paragraph", "text": "본문 ?�용", "bold": false },
    { "type": "bullets", "items": [
        { "text": "??��1", "level": 0 },
        { "text": "?��???��", "level": 1 }
    ]},
    { "type": "numbered", "items": ["?�서1", "?�서2"] },
    { "type": "table",
      "headers": ["컬럼1", "컬럼2"],
      "rows": [["?�이??", "?�이??"]],
      "col_widths": [3, 5]
    },
    { "type": "divider" }
  ]
}

?�성 규칙:
- ?�???�용???�문?�인 문서 ?�식?�로 ?�구??- 구조: 개요(heading+paragraph) ??본문(?�션�?heading+bullets/table) ??결론/?�약
- ???�태 ?�이?�는 table ?�?�으�? ?�열 ??��?� bullets�? ?�서 ?�는 ?�차??numbered�?- ?�션 구분??divider ?�극 ?�용
- �?주요 ?�션?� 최소 2�??�상???�락 ?�는 목록 ?�함
- author: "{$company}", date: "{$today}"
- ?�국?�로 ?�성
PROMPT;

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['text' => $rawText, 'provider' => $provider] = $orchestrator->chatRaw(
                [['role' => 'user', 'content' => "?�음 ?�???�용??Word 문서�??�성?�주?�요:\n\n{$transcript}"]],
                $systemPrompt
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $rawText  = trim(preg_replace(['/^```(?:json)?\s*/m', '/```\s*$/m'], '', $rawText));
        $jsonData = json_decode($rawText, true);

        $title       = $session->title ?: '문서';
        $date        = now()->format('Y-m-d');
        $fileName    = 'docx-' . now()->format('YmdHis') . '.docx';
        $filePath    = storage_path('app/public/ai-downloads/' . $fileName);
        $downloadUrl = request()->getBasePath() . '/ai/word/download?file=' . rawurlencode($fileName);

        $writer = new DocxWriter();
        if ($jsonData && !empty($jsonData['sections'])) {
            $writer->loadFromJson($jsonData);
        } else {
            $writer->addTitle($title)->addEmpty()->addMarkdown($rawText);
        }
        $writer->save($filePath);

        $contentText = $jsonData['title'] ?? $title;
        $aiMsg = AiMessage::create([
            'session_id'       => $session->id,
            'role'             => 'assistant',
            'content'          => $contentText . "\n\nWord 문서가 ?�성?�었?�니??",
            'ai_provider'      => $provider,
            'doc_file_name'    => "{$title} ({$date}).docx",
            'doc_file_type'    => 'docx',
            'doc_download_url' => $downloadUrl,
            'doc_status'       => 'completed',
        ]);

        PromptExecution::create([
            'user_id' => auth()->id(), 'session_id' => $session->id,
            'raw_input' => $request->content, 'ai_response' => $contentText,
            'status' => 'completed', 'ai_provider' => $provider,
        ]);

        $session->touch();
        return response()->json(['ok' => true, 'message' => $aiMsg, 'session_title' => $session->title, 'email_sent' => false]);
    }

    public function downloadWordDoc(Request $request)
    {
        $file = $request->query('file');
        abort_if(!$file || !preg_match('/^docx-[\d]+\.docx$/', $file), 404);

        $owned = AiMessage::whereHas('session', fn($q) => $q->where('user_id', auth()->id()))
            ->where('doc_download_url', 'like', '%' . $file . '%')
            ->exists();
        abort_if(!$owned, 403);

        $path = storage_path('app/public/ai-downloads/' . $file);
        abort_if(!file_exists($path), 404);

        return response()->download($path, '문서-' . now()->format('Y-m-d') . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    // ?�?� Office ?�일 ?�스??추출 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function extractWordText(string $path): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) return '(Word ?�일???????�습?�다)';
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) return '(document.xml??찾을 ???�습?�다)';
            $xml = preg_replace('/<w:br[^>]*\/>/i', "\n", $xml);
            $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
            $text = strip_tags($xml);
            return trim(preg_replace('/\n{3,}/', "\n\n", $text));
        } catch (\Throwable $e) {
            return '(Word ?�스??추출 ?�패: ' . $e->getMessage() . ')';
        }
    }

    private function extractExcelText(string $path): string
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $lines = [];
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $lines[] = "[ ?�트: {$sheet->getTitle()} ]";
                foreach ($sheet->toArray(null, true, true, false) as $row) {
                    $cells = array_filter(array_map('strval', $row), fn($c) => $c !== '');
                    if ($cells) $lines[] = implode("\t", $cells);
                }
            }
            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return '(Excel ?�스??추출 ?�패: ' . $e->getMessage() . ')';
        }
    }

    private function extractPptxText(string $path): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) return '(PowerPoint ?�일???????�습?�다)';
            $lines = [];
            for ($i = 1; $i <= $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i - 1);
                if (!preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) continue;
                $xml = $zip->getFromName($name);
                if ($xml === false) continue;
                preg_match_all('/<a:t>([^<]*)<\/a:t>/', $xml, $matches);
                $text = implode(' ', array_filter($matches[1]));
                if (trim($text)) $lines[] = "[ ?�라?�드 {$m[1]} ] " . trim($text);
            }
            $zip->close();
            return $lines ? implode("\n", $lines) : '(?�스???�음)';
        } catch (\Throwable $e) {
            return '(PowerPoint ?�스??추출 ?�패: ' . $e->getMessage() . ')';
        }
    }

    // ?�?� 공통 ?�퍼 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function buildTranscript($messages): string
    {
        $transcript = '';
        foreach ($messages as $m) {
            if ($m->role === 'user') {
                $transcript .= "?�용?? {$m->content}\n\n";
            } else {
                $content = $m->content ?? '';
                if ($m->html_output) $content .= ' [HTML/코드 결과�??�성??';
                $transcript .= "웍스: {$content}\n\n";
            }
        }
        return $transcript;
    }

    // ?�?� PPTX ?�청 감�? ?�퍼 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function isPptxRequest(string $text): bool
    {
        $lower = mb_strtolower($text);
        return str_contains($lower, '파워포인트')
            || str_contains($lower, 'powerpoint')
            || str_contains($lower, 'pptx')
            || str_contains($lower, '.ppt')
            || str_contains($lower, '프레젠테이션')
            || str_contains($lower, 'presentation')
            || str_contains($lower, '슬라이드로')
            || str_contains($lower, '슬라이드 만들')
            || str_contains($lower, '슬라이드 작성');
    }

    private function handleBuilderInSendMessage(
        Request    $request,
        AiSession  $session,
        AiSetting  $settings,
        array      $messages,
        ?string    $systemOverride
    ): \Illuminate\Http\JsonResponse {
        $step = $request->input('builder_step') ?: ($session->dev_settings['builder_step'] ?? 'STEP_1');

        $orchestrator = new AiOrchestrator(
            $settings->anthropicKey(),
            $settings->openaiKey(),
            $settings->manusKey(),
            $settings->manusEndpoint()
        );

        $html     = null;
        $css      = null;
        $js       = null;
        $lang     = null;
        $provider = null;

        try {
            if ($step === 'STEP_4') {
                ['result' => $result, 'provider' => $provider] = $orchestrator->chat($messages, null, $systemOverride);
                $html  = $result['html'] ?? null;
                $css   = $result['css']  ?? null;
                $js    = $result['js']   ?? null;
                $lang  = $result['lang'] ?? 'web';
                $contentText = $result['explanation'] ?? '?�스 코드가 ?�성?�었?�니??';

            } elseif ($step === 'STEP_FULL') {
                // Phase 1: Manus Max ??planning (STEP_1 + 2 + 3)
                $builderModel = env('MANUS_BUILDER_MODEL', 'manus-max');
                ['text' => $planText] = $orchestrator->chatRaw($messages, $systemOverride ?? '', $builderModel);

                // Phase 2: Claude ??code (STEP_4)
                $codeMessages = array_merge($messages, [
                    ['role' => 'assistant', 'content' => $planText],
                    ['role' => 'user', 'content' => '??기획/?�계 ?�용??기반?�로 ?�성??HTML/CSS/JS ?�스 코드�?JSON ?�식?�로 ?�성?�주?�요.'],
                ]);
                $codePrompt = \App\Services\AiPrompts::builderSystem('STEP_4');
                ['result' => $result, 'provider' => $provider] = $orchestrator->chat($codeMessages, null, $codePrompt);
                $html  = $result['html'] ?? null;
                $css   = $result['css']  ?? null;
                $js    = $result['js']   ?? null;
                $lang  = $result['lang'] ?? 'web';
                $contentText = "**[STEP_FULL 기획/?�계 결과]**\n\n" . $planText
                    . "\n\n---\n\n" . ($result['explanation'] ?? '?�스 코드가 ?�성?�었?�니??');

            } elseif ($step === 'STEP_1') {
                // Phase 1: Manus Max ??slide JSON data
                $builderModel = env('MANUS_BUILDER_MODEL', 'manus-max');
                ['text' => $slideJson] = $orchestrator->chatRaw(
                    $messages,
                    \App\Services\AiPrompts::builderStep1ManusSystem(),
                    $builderModel
                );

                // Phase 2: Claude ??Manus ??OpenAI ?�백 (chatRawLarge: 16000 tokens)
                $viewerMessages = array_merge($messages, [
                    ['role' => 'assistant', 'content' => $slideJson],
                    ['role' => 'user', 'content' => '???�라?�드 ?�이?��? 기반?�로 HTML ?�워?�인??뷰어�??�성?�주?�요.'],
                ]);
                ['text' => $htmlContent, 'provider' => $provider] = $orchestrator->chatRawLarge(
                    $viewerMessages,
                    \App\Services\AiPrompts::builderStep1ViewerSystem(),
                    $builderModel
                );

                $html        = $htmlContent ?: null;
                $css         = null;
                $js          = null;
                $lang        = 'web';
                $contentText = '기획??HTML ?�워?�인??뷰어가 ?�성?�었?�니??';

            } else {
                // STEP_2, STEP_3 ??Manus Max ??plain text (markdown)
                $builderModel = env('MANUS_BUILDER_MODEL', 'manus-max');
                ['text' => $contentText, 'provider' => $provider] = $orchestrator->chatRaw($messages, $systemOverride ?? '', $builderModel);
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $aiMsg = AiMessage::create([
            'session_id'  => $session->id,
            'role'        => 'assistant',
            'content'     => $contentText,
            'html_output' => $html,
            'css_output'  => $css,
            'js_output'   => $js,
            'code_lang'   => $lang,
            'ai_provider' => $provider,
        ]);

        if ($session->project_id && ($aiMsg->html_output || $aiMsg->css_output || $aiMsg->js_output)) {
            (new \App\Services\AiFileService())->saveFromMessage($aiMsg, $session);
        }

        PromptExecution::create([
            'user_id'     => auth()->id(),
            'session_id'  => $session->id,
            'raw_input'   => $request->content,
            'ai_response' => $aiMsg->content,
            'html_output' => $html,
            'css_output'  => $css,
            'js_output'   => $js,
            'status'      => 'completed',
            'ai_provider' => $provider,
        ]);

        $session->touch();

        return response()->json([
            'ok'            => true,
            'message'       => $aiMsg,
            'session_title' => $session->title,
        ]);
    }

    private function handlePptxInSendMessage(Request $request, AiSession $session, AiSetting $settings): \Illuminate\Http\JsonResponse
    {
        $session->load('messages');
        $messages = $session->messages;

        $lower = mb_strtolower($request->content);
        $filterToday = str_contains($lower, '?�늘') || str_contains($lower, 'today');
        if ($filterToday) {
            $messages = $messages->filter(fn($m) => $m->created_at->isToday());
        }

        if ($messages->isEmpty()) {
            $msg = AiMessage::create([
                'session_id' => $session->id, 'role' => 'assistant',
                'content'    => $filterToday ? '?�늘 ?�???�용???�습?�다.' : '?�???�용???�습?�다.',
            ]);
            return response()->json(['ok' => true, 'message' => $msg, 'session_title' => $session->title, 'email_sent' => false]);
        }

        [$jsonData, $provider] = $this->generatePptxJson($session, $messages, $settings);

        [$filePath, $fileName, $downloadUrl] = $this->writePptxFile($jsonData);

        $title    = $session->title ?: '?�???�약';
        $date     = now()->format('Y-m-d');
        $summary  = $this->jsonToSummaryText($jsonData);

        $aiMsg = AiMessage::create([
            'session_id'       => $session->id,
            'role'             => 'assistant',
            'content'          => $summary,
            'ai_provider'      => $provider,
            'doc_file_name'    => "{$title} ({$date}).pptx",
            'doc_file_type'    => 'pptx',
            'doc_download_url' => $downloadUrl,
            'doc_status'       => 'completed',
        ]);

        PromptExecution::create([
            'user_id'     => auth()->id(),
            'session_id'  => $session->id,
            'raw_input'   => $request->content,
            'ai_response' => $summary,
            'status'      => 'completed',
            'ai_provider' => $provider,
        ]);

        $session->touch();

        return response()->json(['ok' => true, 'message' => $aiMsg, 'session_title' => $session->title, 'email_sent' => false]);
    }

    public function exportPptx(Request $request, AiSession $session): \Illuminate\Http\JsonResponse
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니??'], 422);
        }

        $session->load('messages');
        if ($session->messages->isEmpty()) {
            return response()->json(['ok' => false, 'error' => '?�???�용???�습?�다.'], 422);
        }

        [$jsonData, $provider] = $this->generatePptxJson($session, $session->messages, $settings);

        [$filePath, $fileName, $downloadUrl] = $this->writePptxFile($jsonData);

        \Illuminate\Support\Facades\Cache::put('ai_dl:' . $fileName, auth()->id(), now()->addHours(2));

        $title = $session->title ?: '?�???�약';
        $date  = now()->format('Y-m-d');

        return response()->json([
            'ok'           => true,
            'download_url' => $downloadUrl,
            'file_name'    => "{$title} ({$date}).pptx",
            'ppt_text'     => $this->jsonToSummaryText($jsonData),
        ]);
    }

    // ?�?� PPTX 공통 ?�성 로직 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function generatePptxJson(AiSession $session, $messages, AiSetting $settings): array
    {
        $transcript = '';
        foreach ($messages as $m) {
            if ($m->role === 'user') {
                $transcript .= "?�용?? {$m->content}\n\n";
            } else {
                $content = $m->content ?? '';
                if ($m->html_output) $content .= ' [HTML/코드 결과�??�성??';
                $transcript .= "웍스: {$content}\n\n";
            }
        }

        $user    = auth()->user();
        $company = $user->company ?? ($user->name . ' 팀');
        $today   = now()->format('Y년m월d일');

        $systemPrompt = <<<PROMPT
?�신?� ?�레?�테?�션 ?�문 기획?�입?�다.
주어�??�???�용??분석??PowerPoint ?�라?�드 구조�?JSON ?�식?�로�?출력?�세??
?��?�?JSON ?�의 ?�스???�명, ?�사�???�?출력?��? 마세??

출력 JSON ?�키�?
{
  "title": "?�레?�테?�션 ?�목 (?�???�심 주제)",
  "subtitle": "부?�목 ?�는 발표 맥락",
  "company": "발표???��?,
  "slides": [
    { "type": "title" },
    {
      "type": "content",
      "title": "?�라?�드 ?�목",
      "bullets": [
        { "text": "?�심 ?�용 (1�??�내)", "level": 0, "bold": false },
        { "text": "강조???�용", "level": 0, "bold": true },
        { "text": "?��? ?�용 (?�여?�기)", "level": 1, "bold": false }
      ]
    },
    {
      "type": "two_column",
      "title": "비교/분석 ?�목",
      "left_title": "좌측 컬럼 ?�목",
      "left_items": ["??��1", "??��2"],
      "right_title": "?�측 컬럼 ?�목",
      "right_items": ["??��1", "??��2"]
    },
    {
      "type": "highlight",
      "title": "?�심 지??/ KPI ?�목",
      "items": [
        { "value": "92%", "label": "?�성�?, "desc": "목표 ?��??�적" },
        { "value": "1,240", "label": "처리 건수", "desc": "?�월 ?��?+18%" }
      ]
    },
    {
      "type": "table",
      "title": "???�목",
      "headers": ["구분", "?�용", "비고"],
      "rows": [
        ["??��1", "?�명1", "메모1"],
        ["??��2", "?�명2", "메모2"]
      ]
    },
    { "type": "section", "title": "?�션 구분 ?�목", "subtitle": "부???�명(?�택)" },
    { "type": "closing", "message": "마무�?메시지" }
  ]
}

?�성 규칙:
- slides 배열 �?번째??반드??{"type":"title"}
- slides 마�?막�? 반드??{"type":"closing"}
- ?�라?�드 ?? 6~12???�절??구성
- ?�용???��??�면 section?�로 ?�락 구분
- 비교/?��??�용?� two_column?�로 구성
- ?�치/KPI/지?��? ?�으�?highlight ?�라?�드 ?�용 (items 최�? 4�?
- 비교?�나 ??�� 목록?� table ?�라?�드 ?�용 (??최�? 8�?
- �?bullet text??35???�내�?간결?�게
- company??"{$company}", title?� "{$session->title}" 참고
- ?�국?�로 ?�성
PROMPT;

        $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
        ['text' => $rawText, 'provider' => $provider] = $orchestrator->chatRaw(
            [['role' => 'user', 'content' => "?�음 ?�???�용???�레?�테?�션?�로 만들?�주?�요:\n\n{$transcript}"]],
            $systemPrompt
        );

        // JSON 추출 (마크?�운 코드블록 ?�거)
        $rawText = preg_replace('/^```(?:json)?\s*/m', '', $rawText);
        $rawText = preg_replace('/```\s*$/m', '', $rawText);
        $rawText = trim($rawText);

        $jsonData = json_decode($rawText, true);
        if (!$jsonData || empty($jsonData['slides'])) {
            // JSON ?�싱 ?�패 ??기본 구조 ?�성
            $jsonData = [
                'title'    => $session->title ?: '?�레?�테?�션',
                'subtitle' => $today,
                'company'  => $company,
                'slides'   => [
                    ['type' => 'title'],
                    ['type' => 'content', 'title' => '?�용 ?�약', 'bullets' => [
                        ['text' => '?�???�용??기반?�로 ?�성?�었?�니??', 'level' => 0, 'bold' => false],
                    ]],
                    ['type' => 'closing', 'message' => '감사합니다'],
                ],
            ];
        }

        return [$jsonData, $provider];
    }

    private function writePptxFile(array $jsonData): array
    {
        $fileName = 'pptx-' . now()->format('YmdHis') . '.pptx';
        $filePath = storage_path('app/public/ai-downloads/' . $fileName);

        $writer = new PptxWriter($jsonData['title'] ?? '?�레?�테?�션');
        $writer->loadFromJson($jsonData)->save($filePath);

        $downloadUrl = request()->getBasePath() . '/ai/pptx/download?file=' . rawurlencode($fileName);

        return [$filePath, $fileName, $downloadUrl];
    }

    private function jsonToSummaryText(array $jsonData): string
    {
        $lines = ["## {$jsonData['title']}"];
        foreach ($jsonData['slides'] ?? [] as $slide) {
            $type = $slide['type'] ?? '';
            if ($type === 'title' || $type === 'closing') continue;
            $lines[] = "\n### " . ($slide['title'] ?? '');
            foreach ($slide['bullets'] ?? [] as $b) {
                $text   = is_string($b) ? $b : ($b['text'] ?? '');
                $indent = (is_array($b) && ($b['level'] ?? 0) > 0) ? '  ' : '';
                $lines[] = "{$indent}- {$text}";
            }
            foreach ($slide['left_items']  ?? [] as $item) $lines[] = "- {$item}";
            foreach ($slide['right_items'] ?? [] as $item) $lines[] = "- {$item}";
            foreach ($slide['items'] ?? [] as $item) {
                $val   = is_array($item) ? ($item['value'] ?? '') : $item;
                $label = is_array($item) ? ($item['label'] ?? '') : '';
                $lines[] = "- **{$val}** {$label}";
            }
            if (!empty($slide['headers'])) {
                $lines[] = '| ' . implode(' | ', $slide['headers']) . ' |';
                foreach ($slide['rows'] ?? [] as $row) {
                    $lines[] = '| ' . implode(' | ', array_map('strval', $row)) . ' |';
                }
            }
        }
        return implode("\n", $lines);
    }

    public function downloadPptx(Request $request)
    {
        $file = $request->query('file');
        abort_if(!$file || !preg_match('/^pptx-[\d]+\.pptx$/', $file), 404);

        $ownedViaMessage = AiMessage::whereHas('session', fn($q) => $q->where('user_id', auth()->id()))
            ->where('doc_download_url', 'like', '%' . $file . '%')
            ->exists();
        $ownedViaCache = \Illuminate\Support\Facades\Cache::get('ai_dl:' . $file) === auth()->id();
        abort_if(!$ownedViaMessage && !$ownedViaCache, 403);

        $path = storage_path('app/public/ai-downloads/' . $file);
        abort_if(!file_exists($path), 404);

        return response()->download($path, '?�레?�테?�션-' . now()->format('Y-m-d') . '.pptx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    // ?�?� ?�의�??�청 감�? ?�퍼 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    private function isMinutesRequest(string $text): bool
    {
        $lower = mb_strtolower($text);
        $hasMinutes = str_contains($lower, '회의록') || str_contains($lower, '미팅노트') || str_contains($lower, '미팅 노트');
        $hasWord    = str_contains($lower, '워드') || str_contains($lower, 'word') || str_contains($lower, 'docx') || str_contains($lower, '.doc');
        return $hasMinutes && $hasWord;
    }

    private function handleMinutesInSendMessage(Request $request, AiSession $session, AiSetting $settings): \Illuminate\Http\JsonResponse
    {
        $session->load('messages');
        $lower = mb_strtolower($request->content);

        // 명시?�으�?"???�???�늘 ?�??�?참조?�고 기존 웍스 ?�답???�을 ?�만 ?�랜?�크립트 ?�약
        $refersToConversation =
            str_contains($lower, '위의 내용')  || str_contains($lower, '위의 내용') ||
            str_contains($lower, '오늘 대화')  || str_contains($lower, '지금까지')  ||
            str_contains($lower, '이번 대화')  || str_contains($lower, '우리 대화') ||
            str_contains($lower, '이 내용')    || str_contains($lower, '이 대화');

        $hasPriorAi = $session->messages->where('role', 'assistant')->count() > 0;

        if ($refersToConversation && $hasPriorAi) {
            return $this->summarizeConversationAsMinutes($request, $session, $settings);
        }

        // �??????�션·?�반 질의): 웍스가 직접 ?��? ??결과�??�일 ?�성
        return $this->generateDocxFromDirectAnswer($request, $session, $settings, 'minutes');
    }

    private function summarizeConversationAsMinutes(Request $request, AiSession $session, AiSetting $settings): \Illuminate\Http\JsonResponse
    {
        $lower       = mb_strtolower($request->content);
        $filterToday = str_contains($lower, '?�늘') || str_contains($lower, 'today');
        $messages    = $filterToday
            ? $session->messages->filter(fn($m) => $m->created_at->isToday())
            : $session->messages;

        if ($messages->isEmpty()) {
            $msg = AiMessage::create([
                'session_id' => $session->id,
                'role'       => 'assistant',
                'content'    => $filterToday ? '?�늘 ?�???�용???�습?�다.' : '?�???�용???�습?�다.',
            ]);
            return response()->json(['ok' => true, 'message' => $msg, 'session_title' => $session->title, 'email_sent' => false]);
        }

        $transcript = '';
        foreach ($messages as $m) {
            if ($m->role === 'user') {
                $transcript .= "?�용?? {$m->content}\n\n";
            } else {
                $content = $m->content ?? '';
                if ($m->html_output) $content .= ' [HTML/코드 결과�??�성??';
                $transcript .= "웍스: {$content}\n\n";
            }
        }

        $systemPrompt =
            "?�신?� ?�문 ?�의�??�성 ?�우미입?�다. 주어�?웍스 ?�???�용??바탕?�로 ?�문?�인 ?�의록을 마크?�운 ?�식?�로 ?�성?�세??\n\n"
          . "?�식:\n# ?�의�?n## 기본 ?�보\n## 주요 ?�건\n## ?�의 ?�용\n## 결론 �?결과�?n## ?�션 ?�이??n\n"
          . "?�국?�로 ?�성?�고, 불필?�한 ?�용?� ?�략?�고 ?�심�?간결?�게 ?�약?�세??";

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            ['text' => $minutesText, 'provider' => $provider] = $orchestrator->chatRaw(
                [['role' => 'user', 'content' => "?�음 ?�?��? ?�의록으�??�성?�주?�요:\n\n{$transcript}"]],
                $systemPrompt
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $date        = now()->format('Y-m-d');
        $title       = "?�???�약 - {$session->title} ({$date})";
        $docName     = 'minutes-' . now()->format('YmdHis') . '.docx';
        $docPath     = storage_path('app/public/ai-downloads/' . $docName);
        $downloadUrl = request()->getBasePath() . '/ai/minutes/download?file=' . rawurlencode($docName);

        (new DocxWriter())->addTitle($title)->addEmpty()->addMarkdown($minutesText)->save($docPath);

        $aiMsg = AiMessage::create([
            'session_id'       => $session->id,
            'role'             => 'assistant',
            'content'          => $minutesText,
            'ai_provider'      => $provider,
            'doc_file_name'    => $title . '.docx',
            'doc_file_type'    => 'docx',
            'doc_download_url' => $downloadUrl,
            'doc_status'       => 'completed',
        ]);

        PromptExecution::create([
            'user_id'     => auth()->id(),
            'session_id'  => $session->id,
            'raw_input'   => $request->content,
            'ai_response' => $minutesText,
            'status'      => 'completed',
            'ai_provider' => $provider,
        ]);

        $session->touch();
        return response()->json(['ok' => true, 'message' => $aiMsg, 'session_title' => $session->title, 'email_sent' => false]);
    }

    // ?�?� ?�의�?Word ?�보?�기 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function exportMinutes(Request $request, AiSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $settings = AiSetting::current();
        if (!$settings->anthropicKey() && !$settings->openaiKey()) {
            return response()->json(['ok' => false, 'error' => '웍스 API Key가 ?�정?��? ?�았?�니??'], 422);
        }

        // ?�션 메시지�??�?�록 ?�태�??�리
        $session->load('messages');
        $messages = $session->messages;

        if ($messages->isEmpty()) {
            return response()->json(['ok' => false, 'error' => '?�???�용???�습?�다.'], 422);
        }

        // ?�늘 ?�짜 ?�터 ?��?
        $dateFilter = $request->input('date'); // 'today' or null
        if ($dateFilter === 'today') {
            $messages = $messages->filter(fn($m) => $m->created_at->isToday());
        }

        if ($messages->isEmpty()) {
            return response()->json(['ok' => false, 'error' => '?�늘 ?�???�용???�습?�다.'], 422);
        }

        // ?�???�용 ???�스??변??        $transcript = '';
        foreach ($messages as $msg) {
            $role = $msg->role === 'user' ? '사용자' : '웍스';
            $text = $msg->content ?? '';
            if ($msg->html_output) $text .= "\n[HTML 코드 ?�성??";
            $transcript .= "{$role}: {$text}\n\n";
        }

        // 웍스?�게 ?�의�??�성 ?�청
        $systemPrompt = <<<PROMPT
?�신?� ?�문 ?�의�??�성 ?�우미입?�다. ?�음 웍스 ?�???�용??바탕?�로 ?�문?�인 ?�의록을 ?�성?�주?�요.

?�의�??�식:
# ?�의�?## 기본 ?�보
- ?�시: (?�늘 ?�짜)
- 참석?? ?�용?? 웍스 ?�시?�턴??- ?�???�션: {$session->title}

## 주요 ?�건
(?�?�에???�의??주요 주제?�을 목록?�로)

## ?�의 ?�용
(�??�건�??�의 ?�용 ?�약)

## 결론 �?결과�?(?�출??결론, ?�성??코드/문서, ?�의???�항)

## ?�션 ?�이??(?�속 조치 ??��???�다�?

마크?�운 ?�식?�로 ?�성?�되 ?�의록답�??�문?�으�??�성?�주?�요.
PROMPT;

        try {
            $orchestrator = new AiOrchestrator($settings->anthropicKey(), $settings->openaiKey(), $settings->manusKey(), $settings->manusEndpoint());
            $aiMessages   = [
                ['role' => 'user', 'content' => "?�음 ?�???�용???�의록으�??�성?�주?�요:\n\n{$transcript}"],
            ];
            ['text' => $minutesText] = $orchestrator->chatRaw($aiMessages, $systemPrompt);
            if (empty(trim($minutesText))) {
                $minutesText = '?�의록을 ?�성?????�습?�다.';
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        // Word ?�일 ?�성
        $date    = now()->format('Y-m-d');
        $title   = "?�???�약 - {$session->title} ({$date})";
        $docName = 'minutes-' . now()->format('YmdHis') . '.docx';
        $docPath = storage_path('app/public/ai-downloads/' . $docName);

        $writer = new DocxWriter();
        $writer->addTitle($title);
        $writer->addEmpty();
        $writer->addMarkdown($minutesText);
        $writer->save($docPath);

        // ?�일 직접 ?�운로드 URL (route 방식)
        $downloadUrl = request()->getBasePath() . '/ai/minutes/download?file=' . rawurlencode($docName);

        \Illuminate\Support\Facades\Cache::put('ai_dl:' . $docName, auth()->id(), now()->addHours(2));

        return response()->json([
            'ok'           => true,
            'download_url' => $downloadUrl,
            'file_name'    => $title . '.docx',
            'minutes_text' => $minutesText,
        ]);
    }

    public function downloadMinutes(Request $request)
    {
        $file = $request->query('file');
        abort_if(!$file || !preg_match('/^minutes-[\d]+\.docx$/', $file), 404);

        $ownedViaMessage = AiMessage::whereHas('session', fn($q) => $q->where('user_id', auth()->id()))
            ->where('doc_download_url', 'like', '%' . $file . '%')
            ->exists();
        $ownedViaCache = \Illuminate\Support\Facades\Cache::get('ai_dl:' . $file) === auth()->id();
        abort_if(!$ownedViaMessage && !$ownedViaCache, 403);

        $path = storage_path('app/public/ai-downloads/' . $file);
        abort_if(!file_exists($path), 404);

        return response()->download($path, '?�?�요??' . now()->format('Y-m-d') . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    // ?�?� ZIP 코드 ?�운로드 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function downloadCode(AiMessage $message)
    {
        abort_if($message->session->user_id !== auth()->id(), 403);
        abort_if(!$message->html_output && !$message->css_output && !$message->js_output, 404);

        $zip  = new \ZipArchive();
        $dir  = storage_path('app/public/ai-downloads');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = $dir . '/code-' . $message->id . '-' . time() . '.zip';
        $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($message->html_output) $zip->addFromString('index.html',  $message->html_output);
        if ($message->css_output)  $zip->addFromString('common.css',  $message->css_output);
        if ($message->js_output)   $zip->addFromString('common.js',   $message->js_output);
        $zip->close();

        return response()->download($file, 'source-code.zip')->deleteFileAfterSend(true);
    }

    public function fileDownload(PromptExecution $execution, ExecutionFile $file)
    {
        abort_if($execution->user_id !== auth()->id(), 403);
        abort_if($file->execution_id !== $execution->id, 404);

        return Storage::download($file->file_path, $file->file_name);
    }

    // ?�?� ?�로?�트 ?�???�일 목록 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function getProjectFiles(Project $project)
    {
        abort_if(!$project->members()->where('user_id', auth()->id())->exists(), 403);
        $files = (new \App\Services\AiFileService())->getFiles(auth()->id(), $project->id);
        return response()->json(['ok' => true, 'files' => $files]);
    }

    // ?�?� ?�용???�근 가???�로?�트 목록 ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�
    public function getUserProjects()
    {
        $user = auth()->user();
        $projects = $user->projects()
            ->select('projects.id', 'projects.name', 'projects.status')
            ->where('projects.status', '!=', 'archived')
            ->orderBy('projects.name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name]);
        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    // ?�?� ZIP 코드 ???�로?�트 ?�일??추�? ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�
    public function addZipToProject(Request $request, AiMessage $message)
    {
        $session = $message->session;
        abort_if($session->user_id !== auth()->id(), 403);
        abort_if(!$message->html_output && !$message->css_output && !$message->js_output, 422);

        $request->validate(['project_id' => 'required|integer|exists:projects,id']);
        $project = Project::findOrFail($request->integer('project_id'));

        $user = auth()->user();
        if (!$user->isAdmin() && !$project->isMember($user)) {
            abort(403, '?�근 권한???�습?�다.');
        }

        // ZIP ?�성 (?�시 ?�일)
        $dir = storage_path('app/public/ai-downloads');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $tmpFile = $dir . '/code-' . $message->id . '-' . uniqid() . '.zip';

        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($message->html_output) $zip->addFromString('index.html', $message->html_output);
        if ($message->css_output)  $zip->addFromString('common.css', $message->css_output);
        if ($message->js_output)   $zip->addFromString('common.js',  $message->js_output);
        $zip->close();

        // ?�로?�트 ?�일 ?�토리�???복사 ???�시 ?�일 ??��
        $storedName   = \Illuminate\Support\Str::uuid() . '.zip';
        $destPath     = "projects/{$project->id}/{$storedName}";
        Storage::disk('local')->put($destPath, file_get_contents($tmpFile));
        @unlink($tmpFile);

        $safeName     = preg_replace('/[^A-Za-z0-9가-??\-]/', '_', $session->title ?: 'source-code');
        $originalName = $safeName . '-code.zip';

        $projectFile = $project->files()->create([
            'uploaded_by'   => auth()->id(),
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'path'          => $destPath,
            'mime_type'     => 'application/zip',
            'size'          => Storage::disk('local')->size($destPath),
            'description'   => '웍스 Agent 코드 ?�일 (ZIP)',
        ]);

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'file_uploaded',
            $projectFile->original_name,
            route('projects.files.index', $project),
        );

        return response()->json([
            'ok'           => true,
            'project_name' => $project->name,
            'file_name'    => $projectFile->original_name,
            'files_url'    => route('projects.files.index', $project),
        ]);
    }

    // ?�?� 웍스 결과 ?�일 ???�로?�트 ?�일??추�? ?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�
    public function addDocToProject(Request $request, AiMessage $message)
    {
        // 메시지 ?�유�??�인
        $session = $message->session;
        abort_if($session->user_id !== auth()->id(), 403);
        abort_if(!$message->doc_download_url || $message->doc_status !== 'completed', 422);

        $request->validate(['project_id' => 'required|integer|exists:projects,id']);
        $project = Project::findOrFail($request->integer('project_id'));

        // ?�로?�트 ?�근 권한 ?�인
        $user = auth()->user();
        if (!$user->isAdmin() && !$project->isMember($user)) {
            abort(403, '?�근 권한???�습?�다.');
        }

        // doc_download_url?�서 ?�일�?추출 (?file=xxx)
        parse_str(parse_url($message->doc_download_url, PHP_URL_QUERY) ?? '', $params);
        $fileName = $params['file'] ?? null;

        if (!$fileName || preg_match('/[\/\\\\]/', $fileName)) {
            return response()->json(['error' => '?�일 ?�보�?찾을 ???�습?�다.'], 422);
        }

        $srcPath = storage_path('app/public/ai-downloads/' . $fileName);
        if (!file_exists($srcPath)) {
            return response()->json(['error' => '?�본 ?�일??찾을 ???�습?�다.'], 404);
        }

        // ?�로?�트 ?�일 ?�토리�???복사
        $ext        = pathinfo($fileName, PATHINFO_EXTENSION);
        $storedName = \Illuminate\Support\Str::uuid() . ($ext ? '.' . $ext : '');
        $destPath   = "projects/{$project->id}/{$storedName}";

        Storage::disk('local')->put($destPath, file_get_contents($srcPath));

        $mimeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pdf'  => 'application/pdf',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        $projectFile = $project->files()->create([
            'uploaded_by'   => auth()->id(),
            'original_name' => $message->doc_file_name ?: $fileName,
            'stored_name'   => $storedName,
            'path'          => $destPath,
            'mime_type'     => $mime,
            'size'          => filesize($srcPath),
            'description'   => '웍스 Agent 결과 ?�일',
        ]);

        app(ProjectNotificationService::class)->notify(
            $project, auth()->user(), 'file_uploaded',
            $projectFile->original_name,
            route('projects.files.index', $project),
        );

        return response()->json([
            'ok'           => true,
            'project_name' => $project->name,
            'file_name'    => $projectFile->original_name,
            'files_url'    => route('projects.files.index', $project),
        ]);
    }

    // ?�?� ?�일 ?�로???�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�?�

    public function uploadFile(Request $request, PromptExecution $execution)
    {
        abort_if($execution->user_id !== auth()->id(), 403);

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store("executions/{$execution->id}", 'public');

        $efFile = ExecutionFile::create([
            'execution_id' => $execution->id,
            'type'         => 'input',
            'file_path'    => $path,
            'file_name'    => $uploaded->getClientOriginalName(),
            'file_size'    => $uploaded->getSize(),
            'mime_type'    => $uploaded->getMimeType(),
        ]);

        return response()->json(['ok' => true, 'file' => $efFile]);
    }
}
