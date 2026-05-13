<?php

namespace App\Http\Controllers;

use App\Events\ImageCommentPosted;
use App\Models\Message;
use App\Models\MessageImageComment;
use Illuminate\Http\Request;

class MessageImageCommentController extends Controller
{
    private function authorizeConv(Message $msg): void
    {
        if (auth('admin')->check()) return;
        $conv = $msg->conversation()->with('participants')->first();
        if (!$conv->participants->contains('id', auth()->id())) abort(403);
    }

    private function isAdmin(): bool
    {
        return auth('admin')->check();
    }

    public function index(Message $message)
    {
        $this->authorizeConv($message);

        $adminId = $this->isAdmin() ? auth('admin')->id() : null;
        $userId  = $this->isAdmin() ? null : auth()->id();

        $comments = MessageImageComment::with(['user', 'adminUser'])
            ->where('message_id', $message->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'content'    => $c->content,
                'user_name'  => $c->displayName(),
                'user_id'    => $c->user_id,
                'is_mine'    => $this->isAdmin()
                                    ? ($c->admin_user_id === $adminId)
                                    : ($c->user_id === $userId),
                'created_at' => $c->created_at->format('m/d H:i'),
            ]);

        return response()->json(['ok' => true, 'comments' => $comments]);
    }

    public function store(Request $request, Message $message)
    {
        $request->validate(['content' => 'required|string|max:1000']);
        $this->authorizeConv($message);

        $admin = $this->isAdmin() ? auth('admin')->user() : null;

        $comment = MessageImageComment::create([
            'message_id'    => $message->id,
            'user_id'       => $admin ? null : auth()->id(),
            'admin_user_id' => $admin?->id,
            'admin_name'    => $admin?->name,
            'content'       => $request->content,
        ]);

        $comment->load(['user', 'adminUser']);

        broadcast(new ImageCommentPosted($comment))->toOthers();

        return response()->json(['ok' => true, 'comment' => [
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user_name'  => $comment->displayName(),
            'user_id'    => $comment->user_id,
            'is_mine'    => true,
            'created_at' => $comment->created_at->format('m/d H:i'),
        ]]);
    }

    public function destroy(Message $message, MessageImageComment $comment)
    {
        if ($this->isAdmin()) {
            if ($comment->admin_user_id !== auth('admin')->id()) abort(403);
        } else {
            if ($comment->user_id !== auth()->id()) abort(403);
        }
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
