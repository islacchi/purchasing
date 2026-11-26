<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsActive
{
    /**
     * Terminate authenticated sessions that exceed their maximum lifetime.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $now = now()->timestamp;
        $absoluteLifetime = max(1, (int) config('session.absolute_lifetime', 480)) * 60;

        $authenticatedAt = (int) $request->session()->get('authenticated_at', $now);

        if (($now - $authenticatedAt) > $absoluteLifetime) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Your session reached its maximum lifetime. Please sign in again.']);
        }

        return $next($request);
    }
}
