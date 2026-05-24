<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Controllers\Controller;
use App\Models\Mailbox\Attachment;
use App\Models\Mailbox\Message;
use App\Models\Mailbox\Recipient;
use App\Models\User;
use App\Services\Mailbox\MailDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailboxController extends Controller
{
    /** 받은편지함 */
    public function inbox(Request $request): View
    {
        return $this->list($request, 'inbox');
    }

    /** 보낸편지함 */
    public function sent(Request $request): View
    {
        return $this->list($request, 'sent');
    }

    /** 휴지통 */
    public function trash(Request $request): View
    {
        return $this->list($request, 'trash');
    }

    /**
     * 폴더 리스트 공통 — 검색·필터 적용.
     */
    private function list(Request $request, string $folder): View
    {
        $userId = (int) Auth::id();

        $q = Recipient::query()
            ->with(['message:id,thread_id,sender_id,subject,body_text,has_attachment,sent_at', 'message.sender:id,name,email'])
            ->where('user_id', $userId)
            ->where('folder', $folder);

        // 검색 — subject·body_text LIKE
        if ($kw = trim((string) $request->input('q', ''))) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $kw) . '%';
            $q->whereHas('message', function ($m) use ($like) {
                $m->where('subject', 'like', $like)
                  ->orWhere('body_text', 'like', $like);
            });
        }

        // 읽음 여부 (inbox 만 의미 있음)
        if ($request->filled('unread') && $folder === 'inbox') {
            $q->where('is_read', false);
        }

        // 첨부 있음
        if ($request->filled('has_attachment')) {
            $q->whereHas('message', fn ($m) => $m->where('has_attachment', true));
        }

        // 기간
        if ($df = $request->date('date_from')) {
            $q->whereHas('message', fn ($m) => $m->whereDate('sent_at', '>=', $df->toDateString()));
        }
        if ($dt = $request->date('date_to')) {
            $q->whereHas('message', fn ($m) => $m->whereDate('sent_at', '<=', $dt->toDateString()));
        }

        $items = $q->latest('id')->paginate(30)->withQueryString();

        // 사이드바 카운트
        $counts = [
            'inbox'        => Recipient::where('user_id', $userId)->where('folder', 'inbox')->count(),
            'inbox_unread' => Recipient::where('user_id', $userId)->where('folder', 'inbox')->where('is_read', false)->count(),
            'sent'         => Recipient::where('user_id', $userId)->where('folder', 'sent')->count(),
            'trash'        => Recipient::where('user_id', $userId)->where('folder', 'trash')->count(),
        ];

        return view('mailbox.index', compact('items', 'folder', 'counts'));
    }

    /**
     * 메일 상세 + 스레드 보기 — 본인이 수신자(또는 발신자)일 때만.
     */
    public function show(Request $request, Message $message): View
    {
        $this->authorizeAccess($message);

        $userId  = (int) Auth::id();
        $message->load(['sender:id,name,email', 'recipients.user:id,name,email', 'attachments']);

        // 스레드 (오래된 순)
        $thread = Message::where('thread_id', $message->thread_id ?: $message->id)
            ->with(['sender:id,name,email', 'recipients.user:id,name,email', 'attachments'])
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();

        $embed = $request->boolean('embed');

        return view('mailbox.show', compact('message', 'thread', 'embed'));
    }

    /**
     * 작성 화면 — 새 메일/답장/전달 모두 GET ?reply_to=ID / ?forward=ID.
     */
    public function create(Request $request): View
    {
        $replyTo = $request->integer('reply_to');
        $forward = $request->integer('forward');
        $prefill = ['subject' => '', 'body' => '', 'recipients' => [], 'in_reply_to' => null, 'thread_id' => null, 'references_chain' => null];

        if ($replyTo) {
            $parent = Message::with('sender', 'recipients')->find($replyTo);
            if ($parent && $this->canSeeMessage($parent)) {
                $prefill['subject'] = (str_starts_with($parent->subject, 'Re:') ? '' : 'Re: ') . $parent->subject;
                $prefill['body']    = $this->quoteHtml($parent);
                $prefill['recipients'] = [['email' => $parent->sender->email, 'name' => $parent->sender->name]];
                $prefill['in_reply_to'] = $parent->message_id;
                $prefill['thread_id']   = $parent->thread_id ?: $parent->id;
                $prefill['references_chain'] = trim(($parent->references_chain ?? '') . ' ' . $parent->message_id);
            }
        } elseif ($forward) {
            $parent = Message::with('sender', 'attachments')->find($forward);
            if ($parent && $this->canSeeMessage($parent)) {
                $prefill['subject'] = (str_starts_with($parent->subject, 'Fwd:') ? '' : 'Fwd: ') . $parent->subject;
                $prefill['body']    = $this->quoteHtml($parent);
                // 전달은 새 스레드 — thread/references 안 잇음
            }
        }

        $embed = $request->boolean('embed');
        return view('mailbox.compose', compact('prefill', 'embed'));
    }

    /**
     * 메일 전송 POST — multipart 폼.
     */
    public function send(Request $request, MailDispatchService $dispatcher): RedirectResponse
    {
        $request->validate([
            'subject'           => 'required|string|max:300',
            'body'              => 'required|string|max:1000000',
            'recipients'        => 'required|array|min:1',
            'recipients.*'      => 'string|max:300',
            'cc'                => 'nullable|array',
            'cc.*'              => 'string|max:300',
            'bcc'               => 'nullable|array',
            'bcc.*'             => 'string|max:300',
            'attachments'       => 'nullable|array|max:10',
            'attachments.*'     => 'file|max:20480',
            'project_file_ids'  => 'nullable|array|max:20',
            'project_file_ids.*'=> 'integer',
            'in_reply_to'       => 'nullable|string|max:255',
            'thread_id'         => 'nullable|integer',
            'references_chain'  => 'nullable|string|max:65000',
        ]);

        $sender = Auth::user();
        $recipients = [];
        foreach (['to' => 'recipients', 'cc' => 'cc', 'bcc' => 'bcc'] as $type => $key) {
            foreach ((array) $request->input($key, []) as $raw) {
                $parsed = $this->parseAddress((string) $raw);
                if ($parsed) {
                    $parsed['type'] = $type;
                    $recipients[] = $parsed;
                }
            }
        }

        try {
            $msg = $dispatcher->send(
                sender: $sender,
                subject: trim((string) $request->input('subject')),
                bodyHtml: (string) $request->input('body'),
                recipients: $recipients,
                files: array_values(array_filter((array) $request->file('attachments', []))),
                threadCtx: array_filter([
                    'in_reply_to'      => $request->input('in_reply_to'),
                    'thread_id'        => $request->integer('thread_id') ?: null,
                    'references_chain' => $request->input('references_chain'),
                ]),
                projectFileIds: array_values(array_filter(array_map('intval', (array) $request->input('project_file_ids', [])))),
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', '발송에 실패했습니다: ' . $e->getMessage());
        }

        return redirect()->route('mailbox.sent')->with('success', "{$msg->recipient_count}건 발송 완료");
    }

    /**
     * 읽음 처리 (명시적 액션).
     */
    public function markRead(Request $request, Message $message): JsonResponse
    {
        $userId = (int) Auth::id();
        $unreadOnly = !$request->boolean('unread');
        Recipient::where('message_id', $message->id)
            ->where('user_id', $userId)
            ->where('folder', 'inbox')
            ->update($unreadOnly
                ? ['is_read' => true,  'read_at' => now()]
                : ['is_read' => false, 'read_at' => null]);
        return response()->json(['ok' => true]);
    }

    /**
     * 휴지통으로 이동 / 영구 삭제.
     */
    public function trashMove(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $userId = (int) Auth::id();
        Recipient::whereIn('message_id', $ids)
            ->where('user_id', $userId)
            ->update(['folder' => 'trash']);
        return response()->json(['ok' => true]);
    }

    public function trashRestore(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $userId = (int) Auth::id();
        // 보낸 사람(=본인)이면 sent 로, 받은 사람이면 inbox 로
        $rows = Recipient::whereIn('message_id', $ids)->where('user_id', $userId)->with('message')->get();
        $folders = [];
        foreach ($rows as $r) {
            $r->folder = ((int) $r->message->sender_id === $userId) ? 'sent' : 'inbox';
            $r->save();
            $folders[] = $r->folder;
        }
        return response()->json(['ok' => true, 'folders' => $folders]);
    }

    public function destroyForever(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);
        $userId = (int) Auth::id();
        Recipient::whereIn('message_id', $ids)
            ->where('user_id', $userId)
            ->where('folder', 'trash')
            ->delete();   // soft delete — 본인의 recipient row 만
        return response()->json(['ok' => true]);
    }

    /**
     * 첨부 다운로드 — 본인이 메시지를 볼 권한 있을 때만.
     */
    public function downloadAttachment(Attachment $attachment): StreamedResponse
    {
        $msg = Message::find($attachment->message_id);
        abort_unless($msg && $this->canSeeMessage($msg), 403);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }

    /**
     * 첨부용 프로젝트 파일 검색 — 본인이 멤버인 프로젝트의 파일.
     */
    public function projectFiles(Request $request): JsonResponse
    {
        $me = Auth::user();
        $myProjectIds = \DB::table('project_members')->where('user_id', $me->id)->pluck('project_id');
        if ($myProjectIds->isEmpty()) {
            return response()->json(['files' => []]);
        }

        $q = \App\Models\ProjectFile::query()
            ->with('project:id,name')
            ->whereIn('project_id', $myProjectIds)
            ->latest('id')
            ->limit(100);

        if ($projectId = $request->integer('project_id')) {
            $q->where('project_id', $projectId);
        }
        if ($kw = trim((string) $request->input('q'))) {
            $q->where('original_name', 'like', '%' . $kw . '%');
        }

        $files = $q->get(['id', 'project_id', 'original_name', 'path', 'mime_type', 'size'])
            ->map(fn ($f) => [
                'id'        => $f->id,
                'name'      => $f->original_name,
                'project'   => $f->project?->name,
                'size'      => (int) $f->size,
                'size_text' => $this->formatBytes((int) $f->size),
                'mime'      => $f->mime_type,
            ])->values();

        // 프로젝트 목록 (필터 옵션용)
        $projects = \App\Models\Project::whereIn('id', $myProjectIds)->orderBy('name')->get(['id', 'name']);

        return response()->json(['files' => $files, 'projects' => $projects]);
    }

    private function formatBytes(int $b): string
    {
        if ($b < 1024) return $b . 'B';
        if ($b < 1024 * 1024) return round($b / 1024, 1) . 'KB';
        return round($b / (1024 * 1024), 1) . 'MB';
    }

    /**
     * 수신자 자동완성 (헤더 팝오버 재사용 가능).
     */
    public function recipients(): JsonResponse
    {
        $me = Auth::user();
        $myProjectIds = \DB::table('project_members')->where('user_id', $me->id)->pluck('project_id');
        if ($myProjectIds->isEmpty()) {
            return response()->json(['users' => []]);
        }
        $users = User::whereIn('id', function ($q) use ($myProjectIds) {
                $q->select('user_id')->from('project_members')->whereIn('project_id', $myProjectIds);
            })
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->distinct()
            ->get(['id', 'name', 'email', 'phone', 'company']);
        return response()->json(['users' => $users]);
    }

    // ───────── helpers ─────────

    private function authorizeAccess(Message $message): void
    {
        abort_unless($this->canSeeMessage($message), 403);
    }

    private function canSeeMessage(Message $message): bool
    {
        $uid = (int) Auth::id();
        if (!$uid) return false;
        if ((int) $message->sender_id === $uid) return true;
        return Recipient::where('message_id', $message->id)->where('user_id', $uid)->exists();
    }

    private function parseAddress(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        if (str_contains($raw, '|')) {
            $parts = array_pad(explode('|', $raw), 3, '');
            return filter_var(trim($parts[1]), FILTER_VALIDATE_EMAIL)
                ? ['email' => trim($parts[1]), 'name' => trim($parts[0]) ?: null]
                : null;
        }
        if (preg_match('/^(.*?)\s*<\s*([^>]+)\s*>$/u', $raw, $m) && filter_var(trim($m[2]), FILTER_VALIDATE_EMAIL)) {
            return ['email' => trim($m[2]), 'name' => trim(trim($m[1]), '"\'') ?: null];
        }
        return filter_var($raw, FILTER_VALIDATE_EMAIL) ? ['email' => $raw, 'name' => null] : null;
    }

    private function quoteHtml(Message $parent): string
    {
        $when = optional($parent->sent_at)->format('Y-m-d H:i');
        $from = e($parent->sender?->name . ' <' . $parent->sender?->email . '>');
        $body = $parent->body_html ?: nl2br(e((string) $parent->body_text));
        return <<<HTML
<p><br></p>
<blockquote style="margin:8px 0 0;padding:10px 14px;border-left:3px solid #c4b5fd;background:#faf5ff;color:#52525b;">
    <p style="font-size:12px;color:#7c3aed;margin:0 0 6px;">{$when} · {$from}</p>
    {$body}
</blockquote>
HTML;
    }
}
