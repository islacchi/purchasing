<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles authentication using Laravel's session guard.
 */
class AuthController extends Controller
{
    /**
     * Show the login page.
     *
     * The first app screen should always be the login page, even if a
     * browser still carries a previous application session.
     *
     * @return Response
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user with their username (or email) and password.
     *
     * @return RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'name';

        $user = User::where($field, $credentials['login'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => __('No account exists for that username or email.'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => __('This account is inactive or disabled. Please contact an administrator to activate your account.'),
            ]);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The password you entered is incorrect.'),
            ]);
        }

        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put('authenticated_at', now()->timestamp);
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()->intended(route('projects'));
    }

    /**
     * Log the user out and invalidate the session.
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
