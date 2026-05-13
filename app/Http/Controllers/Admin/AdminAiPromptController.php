<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSession;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAiPromptController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $provider = $request->query('provider');
        $userId   = $request->query('user_id');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = AiSession::with(['user', 'messages' => fn($q) => $q->orderBy('id')])
            ->withCount('messages')
            ->withCount(['messages as user_messages_count' => fn($q) => $q->where('role', 'user')])
            ->latest();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) =>
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                  )
                  ->orWhereHas('messages', fn($m) =>
                      $m->where('role', 'user')->where('content', 'like', "%{$search}%")
                  );
            });
        }

        if ($provider) {
            $query->whereHas('messages', fn($m) => $m->where('ai_provider', $provider));
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sessions = $query->paginate(30)->withQueryString();

        $stats = [
            'total_sessions' => AiSession::count(),
            'total_prompts'  => AiMessage::where('role', 'user')->count(),
            'total_users'    => AiSession::distinct('user_id')->count('user_id'),
        ];

        $providers = AiMessage::whereNotNull('ai_provider')
            ->distinct('ai_provider')
            ->pluck('ai_provider');

        return view('admin.ai-prompts.index', compact(
            'sessions', 'stats', 'providers', 'search', 'provider', 'userId', 'dateFrom', 'dateTo'
        ));
    }

    public function show(AiSession $session)
    {
        $session->load(['user', 'messages' => fn($q) => $q->orderBy('id')]);

        return view('admin.ai-prompts.show', compact('session'));
    }
}
