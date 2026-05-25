<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if ($pending = $this->pendingInvitationFor($request->email)) {
            return redirect()->route('login')
                ->with('pending_invite', [
                    'email' => $request->email,
                    'url'   => route('team.accept', $pending->token),
                ])
                ->withInput($request->only('email'));
        }

        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            UserLoginLog::create([
                'user_id'    => null,
                'email'      => $request->email,
                'ip_address' => $request->ip(),
                'result'     => 'fail',
            ]);
            throw $e;
        }

        UserLoginLog::create([
            'user_id'    => Auth::id(),
            'email'      => $request->email,
            'ip_address' => $request->ip(),
            'result'     => 'success',
        ]);

        $request->session()->regenerate();

        // JSON API 엔드포인트가 intended URL로 남아있으면 제거
        $intended = $request->session()->get('url.intended', '');
        $apiPaths = ['/collab/', '/messages/analyze', '/image-comments'];
        foreach ($apiPaths as $path) {
            if (str_contains($intended, $path)) {
                $request->session()->forget('url.intended');
                break;
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function pendingInvitationFor(?string $email): ?Invitation
    {
        if (!$email) return null;
        if (User::where('email', $email)->exists()) return null;
        return Invitation::where('email', $email)->whereNull('accepted_at')->first();
    }
}
