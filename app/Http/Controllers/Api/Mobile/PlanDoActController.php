<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\PlanDoAct;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 Plan-Do-Act API — 채팅 메시지에서 PDCA 항목을 등록/조회/수정한다.
 * 웹 PlanDoActController 와 동일한 plan_do_acts 테이블을 사용.
 */
class PlanDoActController extends Controller
{
    /** 등록 — 채팅 메시지에서 Plan-Do-Act 생성 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'        => 'nullable|integer|exists:projects,id',
            'title'             => 'required|string|max:255',
            'plan'              => 'nullable|string|max:5000',
            'do'                => 'nullable|string|max:5000',
            'act'               => 'nullable|string|max:5000',
            'status'            => 'required|in:plan,do,act,done,excluded',
            'source_message_id' => 'nullable|integer|exists:messages,id',
        ]);

        $user            = $request->user();
        $sourceMessageId = $data['source_message_id'] ?? null;
        $sourceExcerpt   = null;

        if ($sourceMessageId) {
            $message = Message::with('conversation.participants', 'sender')->findOrFail($sourceMessageId);
            abort_unless(
                $message->conversation
                    && $message->conversation->participants->contains('id', $user->id),
                403,
                '메시지에 접근할 수 없습니다.'
            );

            // 동일 메시지 중복 등록 방지
            if ($existing = PlanDoAct::where('source_message_id', $message->id)->first()) {
                return response()->json([
                    'ok'             => false,
                    'already'        => true,
                    'plan_do_act_id' => $existing->id,
                    'item'           => $this->toArray($existing->load('author')),
                ], 409);
            }
            $sourceExcerpt = $this->buildMessageExcerpt($message);
        }

        $pda = PlanDoAct::create([
            'project_id'        => $data['project_id'] ?? null,
            'user_id'           => $user->id,
            'source_message_id' => $sourceMessageId,
            'title'             => $data['title'],
            'plan'              => $data['plan'] ?? null,
            'do'                => $data['do'] ?? null,
            'act'               => $data['act'] ?? null,
            'status'            => $data['status'],
            'source_excerpt'    => $sourceExcerpt,
        ]);

        return response()->json([
            'ok'             => true,
            'plan_do_act_id' => $pda->id,
            'item'           => $this->toArray($pda->load('author')),
        ], 201);
    }

    /** 상세 */
    public function show(Request $request, PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($request, $planDoAct);

        return response()->json($this->toArray($planDoAct->load('author')));
    }

    /** 수정 */
    public function update(Request $request, PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($request, $planDoAct);

        $data = $request->validate([
            'title'  => 'required|string|max:255',
            'plan'   => 'nullable|string|max:5000',
            'do'     => 'nullable|string|max:5000',
            'act'    => 'nullable|string|max:5000',
            'status' => 'required|in:plan,do,act,done,excluded',
        ]);

        $planDoAct->update([
            'title'  => $data['title'],
            'plan'   => $data['plan'] ?? null,
            'do'     => $data['do'] ?? null,
            'act'    => $data['act'] ?? null,
            'status' => $data['status'],
        ]);

        return response()->json([
            'ok'   => true,
            'item' => $this->toArray($planDoAct->fresh('author')),
        ]);
    }

    /** 삭제 — 작성자만 */
    public function destroy(Request $request, PlanDoAct $planDoAct): JsonResponse
    {
        $this->authorizePda($request, $planDoAct);
        abort_unless($planDoAct->user_id === $request->user()->id, 403, '삭제 권한이 없습니다.');

        $planDoAct->delete();

        return response()->json(['ok' => true]);
    }

    // ────────────────────────────────────────────────────────

    private function toArray(PlanDoAct $p): array
    {
        return [
            'id'                => $p->id,
            'project_id'        => $p->project_id,
            'title'             => $p->title,
            'plan'              => $p->plan,
            'do'                => $p->do,
            'act'               => $p->act,
            'status'            => $p->status,
            'source_excerpt'    => $p->source_excerpt,
            'source_message_id' => $p->source_message_id,
            'author'            => $p->author ? ['id' => $p->author->id, 'name' => $p->author->name] : null,
            'created_at'        => optional($p->created_at)->toIso8601String(),
            'updated_at'        => optional($p->updated_at)->toIso8601String(),
        ];
    }

    /** 채팅 메시지 + 답장을 소스 스냅샷 텍스트로 변환 */
    private function buildMessageExcerpt(Message $message): string
    {
        $author = $message->sender?->name ?? '알 수 없음';
        $body   = trim((string) $message->body);
        if ($body === '' && $message->file_name) {
            $body = '📎 ' . $message->file_name;
        }

        $lines   = [];
        $lines[] = '[참조 메시지] ' . $author . ' (' . optional($message->created_at)->format('Y-m-d H:i') . ')';
        $lines[] = $body;

        $replies = Message::where('reply_to_id', $message->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();
        foreach ($replies as $reply) {
            $ra = $reply->sender?->name ?? '알 수 없음';
            $rb = trim((string) $reply->body);
            if ($rb === '' && $reply->file_name) {
                $rb = '📎 ' . $reply->file_name;
            }
            $lines[] = '↳ ' . $ra . ': ' . $rb;
        }

        return implode("\n", $lines);
    }

    /** 열람/수정 권한 — 작성자 · 프로젝트 멤버 · 대화 참여자 허용 */
    private function authorizePda(Request $request, PlanDoAct $pda): void
    {
        $user = $request->user();

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return;
        }
        if ($pda->user_id === $user->id) {
            return;
        }
        if ($pda->project_id) {
            $project = Project::find($pda->project_id);
            if ($project && $project->isMember($user)) {
                return;
            }
        }
        if ($pda->source_message_id) {
            $message = Message::with('conversation.participants')->find($pda->source_message_id);
            if ($message && $message->conversation
                && $message->conversation->participants->contains('id', $user->id)) {
                return;
            }
        }

        abort(403);
    }
}