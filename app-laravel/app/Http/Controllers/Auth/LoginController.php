<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\LegacyPasswordUpgrader;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, LegacyPasswordUpgrader $upgrader): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // F-04: a user carried over from the legacy table has no bcrypt hash
        // yet — only an MD5 digest. Verify against it, and on success re-hash
        // with bcrypt in this same request, while the plaintext is legitimately
        // in hand. See docs/FINDINGS.md.
        if ($user && $upgrader->attempt($user, $credentials['password'])) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();   // F-06: session fixation

            return redirect()->intended('/');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Same message for "no such user" and "wrong password" — the legacy
            // app leaked which of the two it was.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();   // F-06

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
