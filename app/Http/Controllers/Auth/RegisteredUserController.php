<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('email') && !User::where('email', $request->email)->exists()) {
            $pending = Invitation::where('email', $request->email)
                ->whereNull('accepted_at')
                ->first();
            if ($pending) {
                return redirect()->route('team.accept', $pending->token);
            }
        }

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
            'company'      => ['required', 'string', 'max:255'],
            'invite_token' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company' => $request->company,
            'role' => 'client',
        ]);

        // 초대 토큰이 있으면 수락 처리
        if ($request->filled('invite_token')) {
            Invitation::where('token', $request->invite_token)
                ->where('email', $request->email)
                ->whereNull('accepted_at')
                ->update(['accepted_at' => now()]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
