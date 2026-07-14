<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->session()->put('logged_in', true);

        return redirect('/main');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('logged_in');

        return redirect('/login');
    }
}