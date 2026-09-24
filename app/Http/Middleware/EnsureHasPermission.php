<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user('web');

        if (! $user instanceof User || ! $user->hasAnyPermission($permissions)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
