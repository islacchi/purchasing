<?php

namespace App\Http\Middleware;

use App\Models\RoleModuleAccess;
use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Block requests to a module the user's role cannot access.
     *
     * Read requests require the View level; everything else requires Manage.
     * Administrators always pass.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'You must be logged in to do that.');
        }

        if ($user->isAdministrator()) {
            return $next($request);
        }

        $required = in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true)
            ? RoleModuleAccess::LEVEL_VIEW
            : RoleModuleAccess::LEVEL_MANAGE;

        if (! ModuleAccess::allows($user->role_id, $module, $required)) {
            abort(403, 'Your role does not have access to that module.');
        }

        return $next($request);
    }
}
