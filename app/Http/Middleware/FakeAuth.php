<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that fakes authentication by checking for a `logged_in`
 * session flag set by AuthController::login.
 *
 * @TODO: Replace with real auth middleware once user records exist.
 */
class FakeAuth
{
    /**
     * Handle an incoming request.
     *
     * Redirects to the login page when the session is not authenticated,
     * otherwise passes the request through to the next middleware/handler.
     *
     * @param  Request                             $request
     * @param  Closure(Request): (Response)        $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('logged_in')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
