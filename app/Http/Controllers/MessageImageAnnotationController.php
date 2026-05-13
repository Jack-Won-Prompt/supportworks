<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageImageAnnotation;
use Illuminate\Http\Request;

class MessageImageAnnotationController extends Controller
{
    private function authorizeConv(Message $msg): void
    {
        if (auth('admin')->check()) return;
        $conv = $msg->conversation()->with('participants')->first();
        if (!$conv->participants->contains('id', auth()->id())) abort(403);
    }

    private function annToArray(MessageImageAnnotation $a): array
    {
        return [
            'id'         => $a->id,
            'type'       => $a->type,
            'data'       => $a->data,
            'user_name'  => $a->user->name,
            'user_id'    => $a->user_id,
            'can_delete' => !auth('admin')->check() && $a->user_id === auth()->id(),
        ];
    }

    public function index(Message $message)
    {
        $this->authorizeConv($message);

        $annotations = MessageImageAnnotation::with('user')
            ->where('message_id', $message->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($a) => $this->annToArray($a));

        return response()->json(['ok' => true, 'annotations' => $annotations]);
    }

    public function store(Request $request, Message $message)
    {
        if (auth('admin')->check()) abort(403);
        $request->validate([
            'type' => 'required|in:number,rect,circle,line,text',
            'data' => 'required|array',
        ]);
        $this->authorizeConv($message);

        $ann = MessageImageAnnotation::create([
            'message_id' => $message->id,
            'user_id'    => auth()->id(),
            'type'       => $request->type,
            'data'       => $request->data,
        ]);
        $ann->load('user');

        return response()->json(['ok' => true, 'annotation' => $this->annToArray($ann)]);
    }

    public function update(Request $request, Message $message, MessageImageAnnotation $annotation)
    {
        if (auth('admin')->check()) abort(403);
        $request->validate(['data' => 'required|array']);
        if ($annotation->user_id !== auth()->id()) abort(403);

        $annotation->update(['data' => $request->data]);
        return response()->json(['ok' => true]);
    }

    public function destroy(Message $message, MessageImageAnnotation $annotation)
    {
        if (auth('admin')->check()) abort(403);
        if ($annotation->user_id !== auth()->id()) abort(403);
        $annotation->delete();
        return response()->json(['ok' => true]);
    }
}
