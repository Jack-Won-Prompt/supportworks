<?php

namespace App\Http\Controllers;

use App\Models\FileComment;
use App\Models\Message;
use App\Models\PlanDoAct;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanDoActController extends Controller
{
    /** Plan-Do-Act 목록 (좌측 메뉴 · 프로젝트 선택 필터) */
    public function globalIndex(Request $request)
    {
        $user = auth()->user();

        // 프로젝트 선택 드롭다운 목록
        $projects = $user->isAdmin()
            ? Project::orderBy('name')->get(['id', 'name'])
            : $user->projects()->orderBy('projects.name')->get(['projects.id', 'projects.name']);

        $selectedProjectId = $request->query('project');

        $query = PlanDoAct::with(['author', 'project'])
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at');

        // 상단 프로젝트 선택으로 필터
        if ($selectedProjectId === 'none') {
            $query->whereNull('project_id');
        } elseif ($selectedProjectId) {
            $query->where('project_id', $selectedProjectId);
        }

        $items = $query->get();

        // 특정 프로젝트가 선택되면 상단 프로젝트 네비게이션용 모델 로드
        $selectedProject = ($selectedProjectId && $selectedProjectId !== 'none')
            ? Project::find($selectedProjectId)
            : null;

        return view('plan-do-acts.global', compact('items', 'projects', 'selectedProjectId', 'selectedProject'));
    }

    /** 등록 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'             => 'nullable|integer|exists:projects,id',
            'title'                  => 'required|string|max:255',
            'plan'                   => 'nullable|string|max:5000',
            'do'                     => 'nullable|string|max:5000',
            'act'                    => 'nullable|string|max:5000',
            'status'                 => 'required|in:plan,do,act,done,excluded',
            'source_file_comment_id' => 'nullable|integer|exists:file_comments,id',
            'source_message_id'      => 'nullable|integer|exists:messages,id',
        ]);

        $projectId       = $data['project_id'] ?? null;
        $sourceCommentId = $data['source_file_comment_id'] ?? null;
        $sourceMessageId = $data['source_message_id'] ?? null;
        $sourceExcerpt   = null;

        // 프로젝트가 지정되면 멤버 권한 확인
        if ($projectId) {
            $this->authorizeProject(Project::findOrFail($projectId));
        }

        // 파일 의견에서 등록
        if ($sourceCommentId) {
            $comment = FileComment::with(['user', 'replies.user'])->findOrFail($sourceCommentId);
            abort_if($comment->parent_id !== null, 422, __('plan-do-acts.err_reply'));

            $commentProjectId = ProjectFile::where('id', $comment->project_file_id)->value('project_id');
            abort_if($projectId && $commentProjectId !== (int) $projectId, 422, __('plan-do-acts.err_project_mismatch'));

            if ($existing = PlanDoAct::where('source_file_comment_id', $comment->id)->first()) {
                return $this->alreadyResponse($existing);
            }
            $sourceExcerpt = $this->buildCommentExcerpt($comment);
        }

        // 채팅 메시지에서 등록
        if ($sourceMessageId) {
            $message = Message::with('conversation.participants')->findOrFail($sourceMessageId);
            abort_unless(
                $message->conversation && $message->conversation->participants->contains('id', auth()->id()),
                403, __('plan-do-acts.err_message_access')
            );

            if ($existing = PlanDoAct::where('source_message_id', $message->id)->first()) {
                return $this->alreadyResponse($existing);
            }
            $sourceExcerpt = $this->buildMessageExcerpt($message);
        }

        $pda = PlanDoAct::create([
            'project_id'             => $projectId,
            'user_id'                => auth()->id(),
            'source_file_comment_id' => $sourceCommentId,
            'source_message_id'      => $sourceMessageId,
            'title'                  => $data['title'],
            'plan'                   => $data['plan'] ?? null,
            'do'                     => $data['do'] ?? null,
            'act'                    => $data['act'] ?? null,
            'status'                 => $data['status'],
            'source_excerpt'         => $sourceExcerpt,
        ]);

        return response()->json([
            'ok'             => true,
            'plan_do_act_id' => $pda->id,
            'item'           => $this->toArray($pda->load('author', 'project')),
        ], 201);
    }

    /** 상세 (JSON) */
    public function show(PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($planDoAct);

        $planDoAct->load([
            'author',
            'project',
            'sourceFileComment.user',
            'sourceFileComment.file.project',
            'sourceMessage.conversation.participants',
            'sourceMessage.sender',
        ]);

        return response()->json($this->toArray($planDoAct));
    }

    /** 수정 */
    public function update(Request $request, PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($planDoAct);

        $data = $request->validate([
            'project_id' => 'nullable|integer|exists:projects,id',
            'title'      => 'required|string|max:255',
            'plan'       => 'nullable|string|max:5000',
            'do'         => 'nullable|string|max:5000',
            'act'        => 'nullable|string|max:5000',
            'status'     => 'required|in:plan,do,act,done,excluded',
        ]);

        if (!empty($data['project_id']) && (int) $data['project_id'] !== (int) $planDoAct->project_id) {
            $this->authorizeProject(Project::findOrFail($data['project_id']));
        }

        $planDoAct->update([
            'project_id' => $data['project_id'] ?? null,
            'title'      => $data['title'],
            'plan'       => $data['plan'] ?? null,
            'do'         => $data['do'] ?? null,
            'act'        => $data['act'] ?? null,
            'status'     => $data['status'],
        ]);

        return response()->json([
            'ok'   => true,
            'item' => $this->toArray($planDoAct->fresh('author', 'project')),
        ]);
    }

    /** 삭제 */
    public function destroy(PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($planDoAct);

        if (!$this->canManage($planDoAct)) {
            abort(403, __('plan-do-acts.err_no_delete_perm'));
        }

        $planDoAct->delete();

        return response()->json(['ok' => true]);
    }

    // ────────────────────────────────────────────────────────

    private function alreadyResponse(PlanDoAct $existing): JsonResponse
    {
        return response()->json([
            'ok'             => false,
            'already'        => true,
            'plan_do_act_id' => $existing->id,
            'message'        => __('plan-do-acts.already_registered'),
        ], 409);
    }

    private function toArray(PlanDoAct $p): array
    {
        return [
            'id'                     => $p->id,
            'project_id'             => $p->project_id,
            'project'                => $p->project ? ['id' => $p->project->id, 'name' => $p->project->name] : null,
            'title'                  => $p->title,
            'plan'                   => $p->plan,
            'do'                     => $p->do,
            'act'                    => $p->act,
            'status'                 => $p->status,
            'status_label'           => $p->status_label,
            'status_color'           => $p->statusColors(),
            'source_excerpt'         => $p->source_excerpt,
            'source_file_comment_id' => $p->source_file_comment_id,
            'source_message_id'      => $p->source_message_id,
            'source'                 => $this->buildSourceDescriptor($p),
            'author'                 => $p->author ? ['id' => $p->author->id, 'name' => $p->author->name] : null,
            'created_at'             => optional($p->created_at)->format('Y-m-d H:i'),
            'updated_at'             => optional($p->updated_at)->format('Y-m-d H:i'),
        ];
    }

    /** 원본(파일 의견·채팅 메시지) 출처 정보 + 링크 빌드 */
    private function buildSourceDescriptor(PlanDoAct $p): ?array
    {
        // 파일 의견 출처
        if ($p->source_file_comment_id && $p->sourceFileComment) {
            $comment = $p->sourceFileComment;
            $file    = $comment->file;
            $project = $file?->project;

            if (!$file || !$project) {
                return ['type' => 'file_comment', 'label' => __('plan-do-acts.src_loc_missing'), 'url' => null];
            }

            $author = $comment->user?->name ?? $comment->guest_name ?? __('plan-do-acts.reviewer_anon');

            return [
                'type'         => 'file_comment',
                'project_name' => $project->name,
                'file_name'    => $file->original_name,
                'author'       => $author,
                'created_at'   => optional($comment->created_at)->format('Y-m-d H:i'),
                'label'        => $project->name . ' / ' . $file->original_name,
                'url'          => route('projects.files.index', ['project' => $project->id, 'preview' => $file->id]),
            ];
        }

        // 채팅 메시지 출처
        if ($p->source_message_id && $p->sourceMessage) {
            $message = $p->sourceMessage;
            $conv    = $message->conversation;

            if (!$conv) {
                return ['type' => 'message', 'label' => __('plan-do-acts.src_loc_missing'), 'url' => null];
            }

            $userId = auth()->id() ?? 0;

            return [
                'type'              => 'message',
                'conversation_name' => $conv->displayName($userId),
                'sender'            => $message->sender?->name ?? __('plan-do-acts.user_unknown'),
                'created_at'        => optional($message->created_at)->format('Y-m-d H:i'),
                'label'             => $conv->displayName($userId),
                'url'               => route('messages.show', $conv->id) . '#message-' . $message->id,
            ];
        }

        return null;
    }

    /** 파일 의견 + 답글을 소스 스냅샷 텍스트로 변환 */
    private function buildCommentExcerpt(FileComment $comment): string
    {
        $anon    = __('plan-do-acts.reviewer_anon');
        $author  = $comment->user?->name ?? $comment->guest_name ?? $anon;
        $lines   = [];
        $lines[] = __('plan-do-acts.src_comment', [
            'author' => $author,
            'date'   => optional($comment->created_at)->format('Y-m-d H:i'),
        ]);
        $lines[] = (string) $comment->content;

        foreach ($comment->replies as $reply) {
            $ra = $reply->user?->name ?? $reply->guest_name ?? $anon;
            $lines[] = __('plan-do-acts.src_reply', ['author' => $ra, 'content' => (string) $reply->content]);
        }

        return implode("\n", $lines);
    }

    /** 채팅 메시지 + 답장을 소스 스냅샷 텍스트로 변환 */
    private function buildMessageExcerpt(Message $message): string
    {
        $unknown = __('plan-do-acts.user_unknown');
        $author  = $message->sender?->name ?? $unknown;
        $body    = trim((string) $message->body);
        if ($body === '' && $message->file_name) {
            $body = '📎 ' . $message->file_name;
        }

        $lines   = [];
        $lines[] = __('plan-do-acts.src_message', [
            'author' => $author,
            'date'   => optional($message->created_at)->format('Y-m-d H:i'),
        ]);
        $lines[] = $body;

        $replies = Message::where('reply_to_id', $message->id)->with('sender')->orderBy('created_at')->get();
        foreach ($replies as $reply) {
            $ra = $reply->sender?->name ?? $unknown;
            $rb = trim((string) $reply->body);
            if ($rb === '' && $reply->file_name) {
                $rb = '📎 ' . $reply->file_name;
            }
            $lines[] = __('plan-do-acts.src_reply', ['author' => $ra, 'content' => $rb]);
        }

        return implode("\n", $lines);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }

    /** Plan-Do-Act 항목 열람/수정 권한 */
    private function authorizePda(PlanDoAct $pda): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        if ($pda->project_id) {
            $project = $pda->project ?? Project::find($pda->project_id);
            if (!$project || !$project->isMember($user)) abort(403);
            return;
        }

        // 프로젝트 미지정 항목 — 작성자만
        if ($pda->user_id !== $user->id) abort(403);
    }

    private function canManage(PlanDoAct $pda): bool
    {
        $user = auth()->user();
        if ($user->isAdmin()) return true;
        if ($pda->user_id === $user->id) return true;
        if ($pda->project_id) {
            $project = $pda->project ?? Project::find($pda->project_id);
            return $project && $project->getMemberRole($user) === 'manager';
        }
        return false;
    }
}
