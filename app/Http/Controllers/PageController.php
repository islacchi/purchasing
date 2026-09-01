<?php

namespace App\Http\Controllers;

/**
 * Serves a Blade view by name (for example "Projects.index").
 *
 * Used for routes that only need a plain page render without any
 * controller-side data prep.
 */
class PageController extends Controller
{
    /**
     * Render the given page view.
     *
     * @param  string  $page  Dot-notated view name to render.
     * @return Response
     */
    public function show(string $page)
    {
        return view($page);
    }
}