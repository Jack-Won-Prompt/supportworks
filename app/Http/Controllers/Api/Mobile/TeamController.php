<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $members = $user->company_group_id
            ? User::where('company_group_id', $user->company_group_id)->orderBy('name')->get()
            : collect([$user]);

        $pendingInvitations = Invitation::where('invited_by', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        return response()->json([
            'members'     => $members->map(fn($m) => [
                'id'      => $m->id,
                'name'    => $m->name,
                'email'   => $m->email,
                'role'    => $m->role,
                'company' => $m->company,
            ]),
            'invitations' => $pendingInvitations->map(fn($i) => [
                'id'         => $i->id,
                'email'      => $i->email,
                'created_at' => $i->created_at,
                'expires_at' => $i->expires_at,
            ]),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'name'  => 'nullable|string|max:100',
        ]);

        $existing = User::where('email', $request->email)->first();
        if ($existing) {
            return response()->json(['message' => '이미 가입된 사용자입니다.'], 422);
        }

        $invitation = Invitation::create([
            'email'      => $request->email,
            'name'       => $request->name,
            'invited_by' => $request->user()->id,
            'token'      => Str::random(40),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
            'company_group_id' => $request->user()->company_group_id,
        ]);

        return response()->json(['message' => '초대가 발송되었습니다.', 'id' => $invitation->id], 201);
    }

    public function cancelInvite(Request $request, Invitation $invitation): JsonResponse
    {
        abort_if($invitation->invited_by !== $request->user()->id, 403);
        $invitation->delete();
        return response()->json(['message' => '초대가 취소되었습니다.']);
    }
}