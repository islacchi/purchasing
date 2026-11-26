<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsActive
{
    /**
     * Terminate inactive or overly old authenticated sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $now = now()->timestamp;
        $idleLifetime = max(1, (int) config('session.idle_lifetime', 20)) * 60;
        $absoluteLifetime = max(1, (int) config('session.absolute_lifetime', 480)) * 60;

        $lastActivity = (int) $request->session()->get('last_activity_at', $now);
        $authenticatedAt = (int) $request->session()->get('authenticated_at', $now);

        $message = null;

        if (($now - $lastActivity) > $idleLifetime) {
            $message = 'Your session expired due to inactivity. Please sign in again.';
        } elseif (($now - $authenticatedAt) > $absoluteLifetime) {
            $message = 'Your session reached its maximum lifetime. Please sign in again.';
        }

        if ($message !== null) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['login' => $message]);
        }

        $request->session()->put('last_activity_at', $now);
        $request->session()->put('authenticated_at', $authenticatedAt);

        return $next($request);
    }
}
