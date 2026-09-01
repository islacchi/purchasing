<?php

namespace App\Http\Controllers;

/**
 * Renders the application dashboard.
 *
 * NOTE: Not currently wired up to a route (the `routes/web.php` file does
 * not reference this controller yet).
 */
class DashboardController extends Controller
{
    /**
     * Show the dashboard page.
     *
     * @return Response
     */
    public function index()
    {
        return view('main');
    }
}