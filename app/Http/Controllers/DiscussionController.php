<?php

namespace App\Http\Controllers;

use App\Mail\DiscussionShareMail;
use App\Models\AiSetting;
use App\Models\Discussion;
use App\Models\DiscussionAttachment;
use App\Models\DiscussionComment;
use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\User;
use App\Services\AiOrchestrator;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class DiscussionController extends Controller
{
    public function index(Project $project)
    {
        $this->authorizeProject($project);

        $discussions = Discussion::with(['author:id,name', 'participants:id,name'])
            ->withCount(['comments', 'attachments'])
            ->where('project_id', $project->id)
            ->orderByRaw('discussion_date IS NULL, discussion_date DESC')
            ->orderByDesc('updated_at')
            ->get();

        // 공유 대상: 해당 프로젝트의 멤버만 (본인 제외)
        $shareableUsers = $project->members()
            ->where('users.id', '!=', auth()->id())
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email']);

        return view('discussions.index', compact('project', 'discussions', 'shareableUsers'));
    }

    public function show(Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $discussion->load([
            'author:id,name,email',
            'participants:id,name,email',
            'comments.user:id,name',
            'comments.attachments',
            'attachments' => fn($q) => $q->whereNull('discussion_comment_id'),
            'attachments.uploader:id,name',
        ]);

        return response()->json([
            'id'                     => $discussion->id,
            'title'                  => $discussion->title,
            'content'                => $discussion->content,
            'conclusion'             => $discussion->conclusion,
            'comments_summary'       => $discussion->comments_summary,
            'comments_summary_at'    => $discussion->comments_summary_at?->format('Y-m-d H:i'),
            'comments_summary_count' => $discussion->comments_summary_count,
            'discussion_date'        => $discussion->discussion_date?->format('Y-m-d'),
            'status'                 => $discussion->status,
            'status_label'     => $discussion->status_label,
            'status_color'     => $discussion->status_color,
            'created_at'       => $discussion->created_at?->format('Y-m-d H:i'),
            'updated_at'       => $discussion->updated_at?->format('Y-m-d H:i'),
            'author'       => ['id' => $discussion->author->id, 'name' => $discussion->author->name],
            'can_edit'     => $this->canEdit($discussion),
            'can_delete'   => $this->canDelete($discussion),
            'participants' => $discussion->participants->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'email' => $p->email])->values(),
            'attachments'  => $discussion->attachments->map(fn($a) => $this->attachmentToArray($a))->values(),
            'comments'     => $discussion->comments->map(fn($c) => [
                'id'          => $c->id,
                'content'     => $c->content,
                'user_id'     => $c->user_id,
                'user_name'   => $c->user->name ?? '알 수 없음',
                'created_at'  => $c->created_at?->format('Y-m-d H:i'),
                'can_delete'  => Auth::id() === $c->user_id || (Auth::user()?->isAdmin() ?? false),
                'share_token' => $c->share_token,
                'share_url'   => $c->share_token ? route('discussions.public-comment', $c->share_token) : null,
                'attachments' => $c->attachments->map(fn($a) => $this->attachmentToArray($a))->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'nullable|string|max:200000',
            'conclusion'      => 'nullable|string|max:200000',
            'discussion_date' => 'nullable|date',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'integer|exists:users,id',
            'files.*'         => 'nullable|file|max:51200',
        ]);

        $discussion = Discussion::create([
            'project_id'      => $project->id,
            'user_id'         => Auth::id(),
            'title'           => trim($request->title),
            'content'         => $request->content,
            'conclusion'      => $request->conclusion,
            'discussion_date' => $request->discussion_date ?: now()->toDateString(),
            'status'          => 'open',
        ]);

        $this->storeAttachmentsFromRequest($request, $discussion);

        $rawIds = collect($request->input('participant_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()->unique()->reject(fn($id) => $id === Auth::id())->values()->all();
        $memberIds      = $project->members()->pluck('users.id')->map(fn($id) => (int) $id)->all();
        $participantIds = array_values(array_intersect($rawIds, $memberIds));
        if ($participantIds) {
            $discussion->participants()->sync($participantIds);
            $this->notifyParticipants($project, $discussion, $participantIds);
        }

        return response()->json(['ok' => true, 'id' => $discussion->id]);
    }

    public function update(Request $request, Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);
        abort_unless($this->canEdit($discussion), 403);

        $request->validate([
            'title'           => 'sometimes|string|max:255',
            'content'         => 'sometimes|nullable|string|max:200000',
            'conclusion'      => 'sometimes|nullable|string|max:200000',
            'discussion_date' => 'sometimes|nullable|date',
            'status'          => 'sometimes|in:open,in_progress,resolved',
        ]);

        $discussion->update($request->only(['title', 'content', 'conclusion', 'discussion_date', 'status']));

        return response()->json(['ok' => true]);
    }

    public function destroy(Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);
        abort_unless($this->canEdit($discussion), 403);
        abort_unless($this->canDelete($discussion), 422, '진행 전 상태의 논의만 삭제할 수 있습니다.');

        // 파일 정리
        foreach ($discussion->attachments as $att) {
            Storage::disk('local')->delete($att->path);
        }
        $discussion->delete();
        return response()->json(['ok' => true]);
    }

    public function share(Request $request, Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $request->validate([
            'participant_ids'   => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
        ]);

        $rawIds = collect($request->participant_ids)
            ->map(fn($id) => (int) $id)
            ->filter()->unique()->reject(fn($id) => $id === Auth::id())->values()->all();

        // 프로젝트 멤버만 허용
        $memberIds = $project->members()->pluck('users.id')->map(fn($id) => (int) $id)->all();
        $newIds    = array_values(array_intersect($rawIds, $memberIds));

        $existing  = $discussion->participants()->pluck('users.id')->all();
        $toNotify  = array_values(array_diff($newIds, $existing));

        $discussion->participants()->syncWithoutDetaching($newIds);

        if ($toNotify) {
            $this->notifyParticipants($project, $discussion, $toNotify);
        }

        return response()->json(['ok' => true, 'notified' => count($toNotify)]);
    }

    public function storeComment(Request $request, Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $request->validate([
            'content'   => 'required|string|max:50000',
            'files.*'   => 'nullable|file|max:51200',
        ]);

        $comment = DiscussionComment::create([
            'discussion_id' => $discussion->id,
            'user_id'       => Auth::id(),
            'content'       => $request->content,
        ]);

        $this->storeAttachmentsFromRequest($request, $discussion, $comment);

        $discussion->touch();

        $comment->load(['user:id,name', 'attachments']);

        return response()->json([
            'ok'      => true,
            'comment' => [
                'id'          => $comment->id,
                'content'     => $comment->content,
                'user_id'     => $comment->user_id,
                'user_name'   => $comment->user->name ?? '',
                'created_at'  => $comment->created_at?->format('Y-m-d H:i'),
                'can_delete'  => true,
                'share_token' => null,
                'share_url'   => null,
                'attachments' => $comment->attachments->map(fn($a) => $this->attachmentToArray($a))->values(),
            ],
        ]);
    }

    /**
     * 논의 작성 중(아직 저장 전) 본문 인라인 이미지 업로드 — 프로젝트 레벨.
     * 파일을 storage/app/discussions/_inline/{project_id}/{uuid}.{ext} 에 저장 후
     * serveProjectInlineImage 경로로 접근 가능한 URL을 반환.
     */
    public function uploadProjectInlineImage(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        $file = $request->file('image');
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'png');
        $name = Str::uuid()->toString() . '.' . $ext;
        $path = 'discussions/_inline/' . $project->id . '/' . $name;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return response()->json([
            'ok'  => true,
            'url' => route('projects.discussions.inline-image', ['project' => $project, 'filename' => $name]),
        ]);
    }

    /**
     * 인라인 이미지 서빙 (프로젝트 멤버만).
     */
    public function serveProjectInlineImage(Project $project, string $filename)
    {
        $this->authorizeProject($project);

        $path = 'discussions/_inline/' . $project->id . '/' . $filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        $absPath = Storage::disk('local')->path($path);
        $mime    = mime_content_type($absPath) ?: 'application/octet-stream';

        return response()->file($absPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    /**
     * 의견 작성 중 이미지 paste 업로드.
     * DiscussionAttachment로 저장 후 다운로드 URL 반환 → Markdown ![](url)로 사용.
     */
    public function uploadInlineImage(Request $request, Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        $file = $request->file('image');
        $path = $file->store('discussions/' . $discussion->id . '/inline', 'local');

        $att = DiscussionAttachment::create([
            'discussion_id'         => $discussion->id,
            'discussion_comment_id' => null,
            'user_id'               => Auth::id(),
            'original_name'         => $file->getClientOriginalName() ?: ('image-' . now()->format('YmdHis') . '.png'),
            'path'                  => $path,
            'mime_type'             => $file->getMimeType(),
            'size'                  => $file->getSize(),
        ]);

        return response()->json([
            'ok'  => true,
            'url' => route('projects.discussions.attachments.download', [$project, $discussion, $att]),
            'id'  => $att->id,
        ]);
    }

    /**
     * 의견 정제 — 해당 논의 본문을 컨텍스트로 참고해 의견을 다듬어 반환.
     * 빠른 모델(chatRawFast) 사용.
     */
    public function refineComment(Request $request, Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $request->validate([
            'content' => 'required|string|max:50000',
        ]);

        $aiSetting = AiSetting::current();
        if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey()) {
            return response()->json(['ok' => false, 'message' => '웍스 API 키가 설정되지 않았습니다.'], 503);
        }

        $original   = trim((string) $request->content);
        $bodyPlain  = trim(strip_tags((string) $discussion->content));
        if ($bodyPlain !== '') $bodyPlain = mb_substr($bodyPlain, 0, 2500);

        $systemPrompt = implode("\n", array_filter([
            "당신은 IT 프로젝트 협업 의견 정제기입니다.",
            "사용자가 쓴 짧은 의견을, 해당 논의 글을 본 다른 사람이 이해하고 답변하기 좋게 다듬어 주세요.",
            "",
            "논의 제목: " . trim((string) $discussion->title),
            $bodyPlain ? "논의 본문(맥락):\n{$bodyPlain}" : "(논의 본문이 비어 있음)",
            "",
            "지침:",
            "- 원문 의도·주장·요청은 절대 변경 금지.",
            "- 논의 글의 맥락에 맞게 표현·근거를 보강.",
            "- 모호한 부분은 '확인 필요' 또는 '추정'으로 표시.",
            "- 한국어, 정중한 실무 문체.",
            "- 출력은 짧고 명확한 문단(2~5문장) 또는 짧은 목록. 의견이 짧으면 길게 늘리지 마세요.",
            "- 결과 텍스트만 반환 (HTML 태그·코드펜스·메타 문구 금지).",
        ]));

        $userPrompt = "다음 의견을 다듬어 주세요.\n\n원문:\n{$original}";

        $orchestrator = new AiOrchestrator(
            $aiSetting->anthropicKey(),
            $aiSetting->openaiKey(),
        );

        try {
            $result  = $orchestrator->chatRawFast(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );
            $refined = trim($result['text'] ?? '');
            $refined = preg_replace('/^```(?:[a-z]+)?\s*|\s*```$/i', '', $refined);
            $refined = trim($refined);
            if ($refined === '') {
                return response()->json(['ok' => false, 'message' => '정제 결과가 비어 있습니다.']);
            }
            return response()->json([
                'ok'       => true,
                'refined'  => $refined,
                'provider' => $result['provider'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => '웍스 정제 실패: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 의견 공유 링크 토글 — 토큰 발급/해제.
     */
    public function toggleCommentShare(Project $project, Discussion $discussion, DiscussionComment $comment): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);
        abort_if($comment->discussion_id !== $discussion->id, 404);

        if ($comment->share_token) {
            $comment->update(['share_token' => null]);
            return response()->json(['ok' => true, 'active' => false]);
        }

        // 충돌 회피
        do {
            $token = Str::random(48);
        } while (DiscussionComment::where('share_token', $token)->exists());

        $comment->update(['share_token' => $token]);

        return response()->json([
            'ok'     => true,
            'active' => true,
            'token'  => $token,
            'url'    => route('discussions.public-comment', $token),
        ]);
    }

    /**
     * 의견 전체 요약 — 모든 의견을 AI로 요약 후 DB에 자동 저장.
     * 빠른 모델(Claude Haiku → OpenAI mini 폴백) 사용.
     */
    public function summarizeComments(Project $project, Discussion $discussion): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $aiSetting = AiSetting::current();
        if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey()) {
            return response()->json(['ok' => false, 'message' => 'AI API 키가 설정되지 않았습니다.'], 503);
        }

        $discussion->load(['comments.user:id,name']);
        $comments = $discussion->comments;

        if ($comments->isEmpty()) {
            return response()->json(['ok' => false, 'message' => '요약할 의견이 없습니다.']);
        }

        // 의견 본문을 줄글로 구성
        $lines = [];
        foreach ($comments as $i => $c) {
            $name = $c->user->name ?? '익명';
            $time = $c->created_at?->format('Y-m-d H:i') ?? '';
            $body = trim(strip_tags((string) $c->content));
            $body = mb_substr($body, 0, 1500); // 의견 한 건당 최대 1500자
            $lines[] = sprintf("[%d] %s (%s)\n%s", $i + 1, $name, $time, $body);
        }
        $allText = mb_substr(implode("\n\n", $lines), 0, 15000); // 전체 입력 한도

        // 논의 컨텍스트
        $bodyExcerpt = mb_substr(trim(strip_tags((string) $discussion->content)), 0, 1500);

        $systemPrompt = implode("\n", array_filter([
            "당신은 IT 프로젝트 협업 의견 요약기입니다.",
            "여러 사람이 남긴 의견 묶음을, 의사결정에 활용할 수 있도록 핵심만 간결하게 요약하세요.",
            "",
            "프로젝트: " . ($project->name ?? ''),
            "논의 제목: " . trim((string) $discussion->title),
            $bodyExcerpt ? "논의 본문(맥락):\n{$bodyExcerpt}" : null,
            "",
            "## 요약 지침",
            "- 의견 흐름과 핵심 논점을 파악하여 다음 형식으로 정리하세요.",
            "- 의견 작성자 이름은 핵심 발화자만 필요시 언급 (모두 나열 X).",
            "- 사실/의견/요청은 명확히 구분.",
            "- 합의·결정·반대 의견·미해결 쟁점은 별도로 표시.",
            "- 한국어, 정중한 실무 문체.",
            "",
            "## 출력 형식 — Markdown 사용 (HTML 금지)",
            "```",
            "## 주요 합의 / 결정",
            "- 항목",
            "",
            "## 핵심 논점",
            "- 항목",
            "",
            "## 미해결 쟁점 / 추가 논의 필요",
            "- 항목",
            "",
            "## 액션 아이템",
            "- (담당자) 항목",
            "```",
            "",
            "응답에는 요약 결과만 포함. 코드펜스(```), 메타·안내 문구, HTML 태그 금지.",
        ]));

        $userPrompt = "## 의견 목록 (시간 순)\n{$allText}\n\n위 의견들을 요약해 주세요.";

        $orchestrator = new AiOrchestrator(
            $aiSetting->anthropicKey(),
            $aiSetting->openaiKey(),
        );

        try {
            $result = $orchestrator->chatRawFast(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );
            $summary = trim($result['text'] ?? '');
            $summary = preg_replace('/^```(?:markdown|md|html)?\s*\n?/i', '', $summary);
            $summary = preg_replace('/\n?\s*```\s*$/i', '', $summary);
            $summary = trim($summary);
            if (preg_match('/<(p|h[1-6]|ul|ol|li|strong|em|b|i|blockquote|br)\b/i', $summary)) {
                $summary = $this->htmlToMarkdown($summary);
            }
            if ($summary === '') {
                return response()->json(['ok' => false, 'message' => '요약 결과가 비어 있습니다.']);
            }

            // 자동 저장
            $discussion->update([
                'comments_summary'       => $summary,
                'comments_summary_at'    => now(),
                'comments_summary_count' => $comments->count(),
            ]);

            return response()->json([
                'ok'            => true,
                'summary'       => $summary,
                'summary_at'    => $discussion->comments_summary_at?->format('Y-m-d H:i'),
                'summary_count' => $discussion->comments_summary_count,
                'provider'      => $result['provider'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => '요약 실패: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 공유받은 의견 공개 페이지 (로그인 불필요).
     */
    public function publicShowComment(string $token)
    {
        $comment = DiscussionComment::where('share_token', $token)->firstOrFail();
        $comment->load(['user:id,name', 'discussion.author:id,name', 'discussion.project:id,name', 'attachments']);

        return view('discussions.public_share_comment', [
            'comment'    => $comment,
            'discussion' => $comment->discussion,
            'token'      => $token,
        ]);
    }

    public function destroyComment(Project $project, Discussion $discussion, DiscussionComment $comment): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);
        abort_if($comment->discussion_id !== $discussion->id, 404);
        abort_unless(Auth::id() === $comment->user_id || Auth::user()?->isAdmin(), 403);

        foreach ($comment->attachments as $att) {
            Storage::disk('local')->delete($att->path);
        }
        $comment->delete();

        return response()->json(['ok' => true]);
    }

    public function downloadAttachment(Project $project, Discussion $discussion, DiscussionAttachment $attachment)
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);
        abort_if($attachment->discussion_id !== $discussion->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $isImage     = $attachment->mime_type && str_starts_with($attachment->mime_type, 'image/');
        $disposition = $isImage ? 'inline' : 'attachment';

        return response()->file(Storage::disk('local')->path($attachment->path), [
            'Content-Type'        => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition . '; filename*=UTF-8\'\'' . rawurlencode($attachment->original_name),
            'Accept-Ranges'       => 'bytes',
        ]);
    }

    /**
     * 웍스 정제 — Claude 우선, 실패 시 OpenAI 폴백.
     */
    public function refine(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'title'   => 'nullable|string|max:255',
            'content' => 'required|string|max:200000',
        ]);

        $aiSetting = AiSetting::current();
        if (!$aiSetting->anthropicKey() && !$aiSetting->openaiKey()) {
            return response()->json(['ok' => false, 'message' => '웍스 API 키가 설정되지 않았습니다.'], 503);
        }

        $title       = trim((string) $request->title);
        $rawContent  = (string) $request->content;
        $plainBody   = trim(strip_tags($rawContent));

        // 기획서 컨텍스트 — 길이 짧게 (속도 우선)
        $planningContext = $this->collectPlanningContext($project, 2500);

        // 시스템 프롬프트 — 기획서와 동일한 Markdown 스타일 강제
        $systemPrompt = implode("\n", array_filter([
            "당신은 IT 프로젝트 협업 문서 정제기입니다.",
            "사용자의 짧은 논의 메모를, 수신자가 별도 설명 없이도 이해할 수 있는 상세한 협업 문서로 정제하세요.",
            "",
            "프로젝트: " . ($project->name ?? '') . ($project->description ? " — {$project->description}" : ''),
            $planningContext
                ? "기획서 요약(참고 — 용어·구조·스타일을 그대로 따르세요):\n{$planningContext}"
                : "(이 프로젝트에 등록된 기획서가 없습니다. 일반 상식으로 추론.)",
            "",
            "## 정제 지침",
            "- 원문 의도·결정·요청은 절대 변경 금지.",
            "- 배경/목적, 핵심 사항, 논의 포인트, 요청 사항 중 필요한 항목만 구조화.",
            "- 원문에 없는 사실 단정 금지. 추측 시 '추정' 또는 '확인 필요'로 명시.",
            "- 한국어, 정중한 실무 문체.",
            "",
            "## 출력 형식 — 반드시 순수 Markdown만 사용. HTML 태그 절대 금지.",
            "기획서와 동일한 다음 패턴을 따르세요:",
            "",
            "1. **섹션 헤딩**은 `## 1. 섹션명` 형식 (숫자 접두사 사용).",
            "2. **하위 헤딩**은 `### 1.1 소제목` 형식 (점-숫자 계층 표기).",
            "3. **항목 라벨**: 굵게 표시 후 콜론, 예 `- **배경**: ...` / `- **우선순위**: High`.",
            "4. **불릿 목록**은 `-` 사용, 들여쓰기는 4칸 공백으로 중첩.",
            "5. **강조**: `**굵게**` / `*기울임*` / `` `코드` ``.",
            "6. **인용**(맥락 인용 필요 시): `> 인용문`.",
            "7. 섹션 사이는 빈 줄로 분리.",
            "",
            "## 예시 구조 (이 형태와 비슷하게 작성)",
            "```",
            "## 1. 배경 / 목적",
            "현재 상황과 이 논의가 필요한 이유를 한두 단락으로 서술.",
            "",
            "## 2. 핵심 사항",
            "- **현황**: 지금까지 진행된 사항",
            "- **이슈**: 해결이 필요한 부분",
            "    - 세부 이슈 1",
            "    - 세부 이슈 2",
            "",
            "## 3. 논의 / 의사결정 필요 사항",
            "1. 첫 번째 안건",
            "2. 두 번째 안건",
            "",
            "## 4. 요청 / 다음 단계",
            "- **수신자 요청**: 응답해야 할 사항",
            "- **일정**: (확인 필요)",
            "```",
            "",
            "출력에는 정제 결과만 포함. 코드펜스(```)·메타·안내 문구·HTML 태그는 절대 넣지 마세요.",
        ]));

        $userPrompt = "제목: " . ($title ?: '(없음)') . "\n\n원문:\n{$plainBody}";

        $orchestrator = new AiOrchestrator(
            $aiSetting->anthropicKey(),
            $aiSetting->openaiKey(),
        );

        try {
            $result = $orchestrator->chatRawFast(
                [['role' => 'user', 'content' => $userPrompt]],
                $systemPrompt
            );
            $refined = trim($result['text'] ?? '');
            // 응답 전체가 코드펜스로 감싸진 경우 제거
            $refined = preg_replace('/^```(?:markdown|md|html)?\s*\n?/i', '', $refined);
            $refined = preg_replace('/\n?\s*```\s*$/i', '', $refined);
            $refined = trim($refined);
            // 안내성 머리말 흔히 보이는 패턴 제거
            $refined = preg_replace('/^(다듬은\s*내용|정제\s*결과|아래(는|와\s*같습니다))[:\s]*\n+/u', '', $refined);
            $refined = trim($refined);

            // 혹시 HTML 태그가 섞여 들어오면 Markdown으로 후처리 변환 (안전망)
            if (preg_match('/<(p|h[1-6]|ul|ol|li|strong|em|b|i|blockquote|br)\b/i', $refined)) {
                $refined = $this->htmlToMarkdown($refined);
            }

            if ($refined === '') {
                return response()->json(['ok' => false, 'message' => '정제 결과가 비어 있습니다.']);
            }
            return response()->json([
                'ok'       => true,
                'refined'  => $refined,
                'provider' => $result['provider'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => '웍스 정제 실패: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 간단한 HTML → Markdown 후처리 (웍스가 지시 무시하고 HTML 반환할 때 보정).
     */
    private function htmlToMarkdown(string $html): string
    {
        $s = $html;
        // 줄바꿈류
        $s = preg_replace('#<br\s*/?>#i', "\n", $s);
        $s = preg_replace('#</?(div|section|article)\b[^>]*>#i', "\n", $s);

        // 헤딩
        for ($lvl = 6; $lvl >= 1; $lvl--) {
            $hash = str_repeat('#', $lvl);
            $s = preg_replace_callback("#<h{$lvl}\b[^>]*>(.*?)</h{$lvl}>#is",
                fn($m) => "\n\n{$hash} " . trim(strip_tags($m[1])) . "\n\n", $s);
        }

        // 강조
        $s = preg_replace('#<(strong|b)\b[^>]*>(.*?)</\1>#is', '**$2**', $s);
        $s = preg_replace('#<(em|i)\b[^>]*>(.*?)</\1>#is',     '*$2*',   $s);
        $s = preg_replace('#<code\b[^>]*>(.*?)</code>#is',     '`$1`',   $s);

        // 인용
        $s = preg_replace_callback('#<blockquote\b[^>]*>(.*?)</blockquote>#is',
            fn($m) => "\n> " . trim(preg_replace('/\n+/', "\n> ", strip_tags($m[1]))) . "\n", $s);

        // 목록 항목
        $s = preg_replace_callback('#<li\b[^>]*>(.*?)</li>#is',
            fn($m) => "- " . trim(preg_replace('/\s+/', ' ', strip_tags($m[1]))) . "\n", $s);
        $s = preg_replace('#</?(ul|ol)\b[^>]*>#i', "\n", $s);

        // 단락
        $s = preg_replace_callback('#<p\b[^>]*>(.*?)</p>#is',
            fn($m) => "\n" . trim(strip_tags($m[1], '<strong><em><b><i><code><a>')) . "\n", $s);

        // 잔여 태그 제거 + 디코딩
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 연속 공백/개행 정리
        $s = preg_replace("/[ \t]+\n/", "\n", $s);
        $s = preg_replace("/\n{3,}/", "\n\n", $s);

        return trim($s);
    }

    /**
     * 논의 Word 다운로드 — Markdown → PhpWord 변환, 의견 포함.
     */
    public function downloadWord(Project $project, Discussion $discussion)
    {
        $this->authorizeProject($project);
        abort_if($discussion->project_id !== $project->id, 404);

        $discussion->load(['author:id,name', 'comments.user:id,name']);

        $phpWord  = new PhpWord();
        $phpWord->setDefaultFontName('맑은 고딕');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 1100,
            'marginBottom' => 1100,
            'marginLeft'   => 1100,
            'marginRight'  => 1100,
        ]);

        // 상단 헤더 바
        $headerTable = $section->addTable([
            'borderSize' => 0, 'cellMargin' => 0,
        ]);
        $headerTable->addRow();
        $cell = $headerTable->addCell(9746, ['bgColor' => '7C3AED']);
        $cell->addText('논의 사항', [
            'name' => '맑은 고딕', 'size' => 9, 'bold' => true, 'color' => 'FFFFFF',
        ], ['spaceBefore' => 60, 'spaceAfter' => 60, 'indentLeft' => 160]);

        $section->addTextBreak(1);

        // 제목
        $section->addText($this->wordEsc($discussion->title), [
            'name' => '맑은 고딕', 'size' => 20, 'bold' => true, 'color' => '1F2937',
        ], ['spaceAfter' => 120]);

        // 메타 (상태·작성자·작성일)
        $statusLabel = $discussion->status_label;
        $statusColor = match($discussion->status) {
            'open'        => '1D4ED8',
            'in_progress' => 'B45309',
            'resolved'    => '15803D',
            default       => '6B7280',
        };
        $metaRun = $section->addTextRun(['spaceAfter' => 60]);
        $metaRun->addText('상태  ', ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF']);
        $metaRun->addText($this->wordEsc($statusLabel), ['name' => '맑은 고딕', 'size' => 10, 'bold' => true, 'color' => $statusColor]);
        $metaRun->addText('    ', ['name' => '맑은 고딕', 'size' => 10]);
        $metaRun->addText('논의 일자  ', ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF']);
        $metaRun->addText($this->wordEsc(optional($discussion->discussion_date)->format('Y-m-d') ?? '-'), ['name' => '맑은 고딕', 'size' => 10, 'bold' => true, 'color' => '4F46E5']);
        $metaRun->addText('    ', ['name' => '맑은 고딕', 'size' => 10]);
        $metaRun->addText('작성자  ', ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF']);
        $metaRun->addText($this->wordEsc($discussion->author->name ?? '-'), ['name' => '맑은 고딕', 'size' => 10, 'bold' => true, 'color' => '1F2937']);
        $metaRun->addText('    ', ['name' => '맑은 고딕', 'size' => 10]);
        $metaRun->addText('작성일  ', ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF']);
        $metaRun->addText($this->wordEsc(optional($discussion->created_at)->format('Y-m-d H:i') ?? '-'), ['name' => '맑은 고딕', 'size' => 10, 'color' => '1F2937']);

        // 프로젝트 표기
        $projRun = $section->addTextRun(['spaceAfter' => 240]);
        $projRun->addText('프로젝트  ', ['name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF']);
        $projRun->addText($this->wordEsc($project->name), ['name' => '맑은 고딕', 'size' => 10, 'color' => '4F46E5', 'bold' => true]);

        // 본문 헤더
        $section->addText('논의 내용', [
            'name' => '맑은 고딕', 'size' => 12, 'bold' => true, 'color' => '5B21B6',
        ], ['spaceBefore' => 60, 'spaceAfter' => 80]);

        // 본문 (Markdown → Word)
        $body = (string) $discussion->content;
        if (trim($body) === '') {
            $section->addText('(내용 없음)', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
        } else {
            $this->addMarkdownToWord($section, $body);
        }

        // 의견 헤더
        $section->addTextBreak(1);
        $section->addText('의견 (' . $discussion->comments->count() . ')', [
            'name' => '맑은 고딕', 'size' => 12, 'bold' => true, 'color' => '5B21B6',
        ], ['spaceBefore' => 120, 'spaceAfter' => 80]);

        if ($discussion->comments->isEmpty()) {
            $section->addText('(의견 없음)', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
        } else {
            foreach ($discussion->comments as $c) {
                // 작성자 + 시각 라인
                $cmtMetaRun = $section->addTextRun(['spaceBefore' => 100, 'spaceAfter' => 40]);
                $cmtMetaRun->addText('● ', ['name' => '맑은 고딕', 'size' => 11, 'color' => 'A78BFA', 'bold' => true]);
                $cmtMetaRun->addText($this->wordEsc($c->user->name ?? '알 수 없음'), [
                    'name' => '맑은 고딕', 'size' => 11, 'bold' => true, 'color' => '1F2937',
                ]);
                $cmtMetaRun->addText('   ' . ($c->created_at ? $c->created_at->format('Y-m-d H:i') : ''), [
                    'name' => '맑은 고딕', 'size' => 9, 'color' => '9CA3AF',
                ]);
                // 의견 본문 (Markdown 지원)
                $cmtBody = (string) $c->content;
                if (trim($cmtBody) !== '') {
                    $this->addMarkdownToWord($section, $cmtBody);
                }
                // 구분선
                $section->addText(str_repeat('─', 60), [
                    'name' => '맑은 고딕', 'size' => 8, 'color' => 'E5E7EB',
                ], ['spaceBefore' => 40, 'spaceAfter' => 40, 'alignment' => Jc::CENTER]);
            }
        }

        // 결론 섹션
        $section->addTextBreak(1);
        $section->addText('결론', [
            'name' => '맑은 고딕', 'size' => 12, 'bold' => true, 'color' => 'B45309',
        ], ['spaceBefore' => 120, 'spaceAfter' => 80]);

        $conclusion = (string) $discussion->conclusion;
        if (trim($conclusion) === '') {
            $section->addText('(결론이 아직 작성되지 않았습니다)', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
        } else {
            $this->addMarkdownToWord($section, $conclusion);
        }

        // 푸터
        $section->addTextBreak(1);
        $section->addText('— SupportWorks · ' . now()->format('Y-m-d H:i') . ' 생성 —', [
            'name' => '맑은 고딕', 'size' => 8, 'color' => '9CA3AF', 'italic' => true,
        ], ['alignment' => Jc::CENTER]);

        // 임시 저장 후 다운로드
        $safeTitle = preg_replace('/[\\\\\\/:*?"<>|]/', '_', $discussion->title);
        $fileName  = '논의_' . $safeTitle . '_' . now()->format('YmdHi') . '.docx';
        $tmpPath   = storage_path('app/discussions/_word_tmp_' . Str::uuid()->toString() . '.docx');
        if (!is_dir(dirname($tmpPath))) {
            @mkdir(dirname($tmpPath), 0775, true);
        }

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . rawurlencode($fileName),
        ])->deleteFileAfterSend(true);
    }

    // ── 마크다운 → PhpWord 헬퍼 (DeliverableController 패턴 차용) ───────

    private function addMarkdownToWord(\PhpOffice\PhpWord\Element\Section $section, string $markdown, string $font = '맑은 고딕'): void
    {
        if (trim($markdown) === '') return;

        $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
        $i = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = rtrim($lines[$i]);

            // Markdown 이미지 한 줄 (`![alt](data:... or http://...)`) → 실제 이미지 임베드
            if (preg_match('/^\s*!\[([^\]]*)\]\(([^)]+)\)\s*$/', $line, $m)) {
                $this->addWordImage($section, $m[2]);
                $i++;
                continue;
            }

            // 헤딩
            if (preg_match('/^(#{1,6})\s+(.+)/', $line, $m)) {
                $level = strlen($m[1]);
                $text  = preg_replace('/\*{1,2}(.+?)\*{1,2}/', '$1', $m[2]);
                $size  = match(true) { $level === 1 => 14, $level === 2 => 12, default => 11 };
                $section->addText(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), [
                    'name' => $font, 'size' => $size, 'bold' => true, 'color' => $level <= 2 ? '1F2937' : '6B7280',
                ], ['spaceBefore' => 140, 'spaceAfter' => 60]);
                $i++;
                continue;
            }

            // 가로선
            if (preg_match('/^[-*_]{3,}\s*$/', $line)) {
                $section->addTextBreak(1);
                $i++;
                continue;
            }

            // 코드 블록
            if (str_starts_with($line, '```')) {
                $i++;
                $codeLines = [];
                while ($i < $total && !str_starts_with(rtrim($lines[$i]), '```')) {
                    $codeLines[] = $lines[$i];
                    $i++;
                }
                $i++;
                if ($codeLines) {
                    $section->addText(htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES, 'UTF-8'), [
                        'name' => 'Courier New', 'size' => 9, 'color' => '1F2937',
                    ], ['spaceBefore' => 40, 'spaceAfter' => 40]);
                }
                continue;
            }

            // 인용
            if (preg_match('/^>\s*(.*)/', $line, $m)) {
                $section->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), [
                    'name' => $font, 'size' => 10, 'italic' => true, 'color' => '6B7280',
                ], ['spaceBefore' => 40, 'spaceAfter' => 40, 'indentLeft' => 280]);
                $i++;
                continue;
            }

            // 불릿 리스트
            if (preg_match('/^(\s*)([-*+])\s+(.+)/', $line, $m)) {
                $depth = (int)(strlen($m[1]) / 2);
                $text  = $this->stripInlineMd($m[3]);
                $section->addListItem(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $depth, [
                    'name' => $font, 'size' => 11,
                ]);
                $i++;
                continue;
            }

            // 번호 리스트
            if (preg_match('/^(\s*)\d+\.\s+(.+)/', $line, $m)) {
                $depth = (int)(strlen($m[1]) / 2);
                $text  = $this->stripInlineMd($m[2]);
                $section->addListItem(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $depth, [
                    'name' => $font, 'size' => 11,
                ], 'listNumber');
                $i++;
                continue;
            }

            // 빈 줄
            if ($line === '') {
                $i++;
                continue;
            }

            // 일반 단락
            $run = $section->addTextRun(['spaceAfter' => 80, 'spaceBefore' => 20]);
            $segments = preg_split('/(\*\*[^*\n]+\*\*|\*[^*\n]+\*|`[^`\n]+`)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($segments as $seg) {
                if ($seg === '') continue;
                if (preg_match('/^\*\*(.+)\*\*$/s', $seg, $m)) {
                    $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11, 'bold' => true]);
                } elseif (preg_match('/^\*(.+)\*$/s', $seg, $m)) {
                    $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11, 'italic' => true]);
                } elseif (preg_match('/^`(.+)`$/s', $seg, $m)) {
                    $run->addText(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), ['name' => 'Courier New', 'size' => 10]);
                } else {
                    // Markdown 이미지 `![alt](url)` 패턴은 제외하고 텍스트만 표시 (Word에 외부 URL 이미지 임베드는 불안정)
                    $clean = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '[이미지]', $seg);
                    // 링크 `[text](url)` → text만
                    $clean = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $clean);
                    $run->addText(htmlspecialchars($clean, ENT_QUOTES, 'UTF-8'), ['name' => $font, 'size' => 11]);
                }
            }
            $i++;
        }
    }

    /**
     * Markdown 이미지를 PhpWord 이미지로 임베드.
     * - data:image/...;base64,... 형태는 디코딩 후 임시 파일로 저장 후 임베드
     * - http(s):// 형태는 다운로드 후 임베드 시도, 실패 시 [이미지] 텍스트로 대체
     */
    private function addWordImage(\PhpOffice\PhpWord\Element\Section $section, string $src): void
    {
        try {
            $tmpFile = null;

            // data URL → base64 디코드
            if (preg_match('#^data:image/([a-zA-Z0-9.+-]+);base64,(.+)$#i', trim($src), $m)) {
                $ext  = strtolower($m[1]);
                $data = base64_decode($m[2], true);
                if ($data === false) {
                    $section->addText('[이미지]', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
                    return;
                }
                $tmpFile = storage_path('app/discussions/_word_img_' . \Illuminate\Support\Str::uuid()->toString() . '.' . preg_replace('/[^a-z0-9]/', '', $ext));
                if (!is_dir(dirname($tmpFile))) @mkdir(dirname($tmpFile), 0775, true);
                file_put_contents($tmpFile, $data);
            }
            // 일반 URL → 다운로드 시도
            elseif (preg_match('#^https?://#i', $src)) {
                $bytes = @file_get_contents($src);
                if (!$bytes) {
                    $section->addText('[이미지: ' . $src . ']', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
                    return;
                }
                $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) ?: 'png';
                $tmpFile = storage_path('app/discussions/_word_img_' . \Illuminate\Support\Str::uuid()->toString() . '.' . preg_replace('/[^a-z0-9]/', '', $ext));
                if (!is_dir(dirname($tmpFile))) @mkdir(dirname($tmpFile), 0775, true);
                file_put_contents($tmpFile, $bytes);
            } else {
                $section->addText('[이미지]', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
                return;
            }

            // 이미지 크기 (인쇄 가능 너비 6.7인치 = 6.7*914400 EMU. PhpWord는 단위가 다양해 width만 px로 지정)
            $section->addImage($tmpFile, [
                'width'         => 480,           // px (A4 본문 너비에 잘 맞음)
                'wrappingStyle' => 'inline',
                'alignment'     => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);

            // Word 저장 후 정리될 임시 파일은 다운로드 응답 deleteFileAfterSend 와 별개 → 즉시 삭제하면 PhpWord가 못 읽음
            // 한 번에 워드 저장이 끝난 뒤 cleanup 디렉터리 일괄 정리
            register_shutdown_function(function () use ($tmpFile) {
                if ($tmpFile && file_exists($tmpFile)) @unlink($tmpFile);
            });
        } catch (\Throwable $e) {
            $section->addText('[이미지 삽입 실패]', ['name' => '맑은 고딕', 'size' => 10, 'italic' => true, 'color' => '9CA3AF']);
        }
    }

    private function stripInlineMd(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '[이미지]', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        return $text;
    }

    private function wordEsc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // ── 헬퍼 ─────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }

    private function canEdit(Discussion $discussion): bool
    {
        return Auth::id() === $discussion->user_id || (Auth::user()?->isAdmin() ?? false);
    }

    /**
     * 삭제 가능 여부 — 작성자/관리자 + 상태가 '진행 전(open)'인 경우에만 허용.
     */
    private function canDelete(Discussion $discussion): bool
    {
        return $this->canEdit($discussion) && $discussion->status === 'open';
    }

    private function storeAttachmentsFromRequest(Request $request, Discussion $discussion, ?DiscussionComment $comment = null): void
    {
        $files = $request->file('files', []);
        if (!is_array($files)) $files = [$files];

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;
            $path = $file->store('discussions/' . $discussion->id, 'local');
            DiscussionAttachment::create([
                'discussion_id'         => $discussion->id,
                'discussion_comment_id' => $comment?->id,
                'user_id'               => Auth::id(),
                'original_name'         => $file->getClientOriginalName(),
                'path'                  => $path,
                'mime_type'             => $file->getMimeType(),
                'size'                  => $file->getSize(),
            ]);
        }
    }

    private function attachmentToArray(DiscussionAttachment $a): array
    {
        return [
            'id'             => $a->id,
            'name'           => $a->original_name,
            'size'           => $a->size,
            'formatted_size' => $a->formattedSize(),
            'mime'           => $a->mime_type,
            'is_image'       => $a->mime_type && str_starts_with($a->mime_type, 'image/'),
            'download_url'   => route('projects.discussions.attachments.download', [
                $a->discussion->project_id, $a->discussion_id, $a->id,
            ]),
            'uploader_name'  => $a->uploader->name ?? '',
        ];
    }

    /**
     * 프로젝트의 기획서 컨텍스트를 웍스 프롬프트용 문자열로 수집.
     * - 승인된(approved) 기획서가 있으면 최신 1건 우선, 없으면 최신본
     * - $maxChars 로 본문 길이 제한 (속도/비용 절감)
     */
    private function collectPlanningContext(Project $project, int $maxChars = 2500): string
    {
        $doc = PlanningDoc::where('project_id', $project->id)
            ->where('status', 'approved')
            ->orderByDesc('updated_at')
            ->first()
            ?? PlanningDoc::where('project_id', $project->id)
                ->orderByDesc('updated_at')
                ->first();

        if (!$doc) return '';

        $plain = trim(strip_tags((string) $doc->content));
        if ($plain === '') return '';

        $plain = mb_substr($plain, 0, max(500, $maxChars));
        $title = trim((string) ($doc->title ?? ''));

        $parts = [];
        if ($title) $parts[] = "제목: {$title}";
        $parts[] = $plain;

        return implode("\n", $parts);
    }

    private function notifyParticipants(Project $project, Discussion $discussion, array $userIds): void
    {
        $author = $discussion->author ?? Auth::user();
        if (!$author) return;

        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'email', 'phone']);
        foreach ($users as $u) {
            // SMS — 이메일 성공 여부와 무관하게 휴대폰 번호가 있으면 즉시 발송 (초대 알림)
            if (!empty($u->phone)) {
                $smsMsg = "[SupportWorks] {$author->name}님이 '{$project->name}' 프로젝트의 논의 '{$discussion->title}'에 초대했습니다.";
                try { SmsService::send($u->phone, $smsMsg, $u->name); } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            }
            if (filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($u->email)->send(new DiscussionShareMail($project, $discussion, $author, $u));
                } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            }
        }
    }
}
