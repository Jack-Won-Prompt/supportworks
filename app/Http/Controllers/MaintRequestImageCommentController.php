<?php

namespace App\Http\Controllers;

use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestImageComment;
use Illuminate\Http\Request;

class MaintRequestImageCommentController extends Controller
{
    public function index(Request $request, MaintRequest $maintRequest)
    {
        $request->validate(['image_url' => 'required|string|max:500']);

        $rows = MaintRequestImageComment::with('user:id,name')
            ->where('maint_request_id', $maintRequest->id)
            ->where('image_url', $request->string('image_url'))
            ->oldest('id')
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'user_id'    => $c->user_id,
                'user_name'  => $c->user?->name,
                'body'       => $c->body,
                'created_at' => $c->created_at?->format('Y-m-d H:i'),
                'can_delete' => (auth()->id() === $c->user_id) || (auth()->user()?->isAdmin() ?? false),
            ]);

        return response()->json(['ok' => true, 'comments' => $rows]);
    }

    public function store(Request $request, MaintRequest $maintRequest)
    {
        $data = $request->validate([
            'image_url' => 'required|string|max:500',
            'body'      => 'required|string|max:2000',
        ]);

        $c = MaintRequestImageComment::create([
            'maint_request_id' => $maintRequest->id,
            'image_url'        => $data['image_url'],
            'user_id'          => auth()->id(),
            'body'             => $data['body'],
        ]);

        return response()->json([
            'ok' => true,
            'comment' => [
                'id'         => $c->id,
                'user_id'    => $c->user_id,
                'user_name'  => auth()->user()?->name,
                'body'       => $c->body,
                'created_at' => $c->created_at?->format('Y-m-d H:i'),
                'can_delete' => true,
            ],
        ]);
    }

    public function destroy(MaintRequest $maintRequest, MaintRequestImageComment $comment)
    {
        abort_unless($comment->maint_request_id === $maintRequest->id, 404);
        $u = auth()->user();
        abort_unless($u && ($u->id === $comment->user_id || $u->isAdmin()), 403);

        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
