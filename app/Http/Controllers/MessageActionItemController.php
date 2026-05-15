<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class MessageActionItemController extends Controller
{
    public function store(Request $request, Message $message)
    {
        $user = auth()->user();
        abort_unless($user, 401);

        $conversation = $message->conversation()->with('participants')->firstOrFail();
        abort_if($conversation->type !== null, 404);
        abort_unless($conversation->participants->contains('id', $user->id), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        if (!empty($data['assigned_to'])) {
            $assignee = User::find($data['assigned_to']);
            abort_if($user->hasCompany() && $assignee && !$user->inSameCompany($assignee), 403);
        }

        if (!empty($data['project_id'])) {
            $projectAllowed = $user->isAdmin()
                ? Project::companyOf($user)->whereKey($data['project_id'])->exists()
                : $user->projects()->whereKey($data['project_id'])->exists();
            abort_unless($projectAllowed, 403);
        }

        $description = trim((string) ($data['description'] ?? ''));

        $item = ActionItem::create([
            'user_id' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'],
            'description' => $description,
            'due_date' => $data['due_date'] ?? null,
            'source_message_id' => $message->id,
            'source_context' => [
                'conversation_id' => $conversation->id,
                'message_excerpt' => mb_strimwidth((string) ($message->body ?: $message->file_name), 0, 180, '...', 'UTF-8'),
            ],
        ]);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => route('action-items.index'),
            ],
        ]);
    }
}
