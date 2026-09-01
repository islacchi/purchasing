<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Handles the fake/demo authentication flow: rendering the login page,
 * flagging the session as authenticated, and clearing that flag.
 *
 * NOTE: No credentials are validated — any submit simply sets a session
 * `logged_in` flag. Replace with real auth once user records and a proper
 * authentication library are wired up.
 */
class AuthController extends Controller
{
    /**
     * Show the login page.
     *
     * @return Response
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Log the user in by flagging the session as authenticated, then
     * redirect to the projects page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request)
    {
        $request->session()->put('logged_in', true);

        return redirect('/projects');
    }

    /**
     * Log the user out by clearing the authenticated flag, then redirect
     * back to the login page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function logout(Request $request)
    {
        $request->session()->forget('logged_in');

        return redirect('/login');
    }
}