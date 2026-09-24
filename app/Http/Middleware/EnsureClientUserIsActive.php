<?php

namespace App\Http\Middleware;

use App\Models\ClientUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('client');

        if ($user && ! $user instanceof ClientUser) {
            Auth::guard('client')->logout();

            return redirect()->route('client.login');
        }

        if ($user instanceof ClientUser) {
            $user->loadMissing('client');
        }

        if ($user instanceof ClientUser && ! $user->canAccessPortal()) {
            Auth::guard('client')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('client.login')
                ->withErrors(['email' => 'Your portal account is inactive. Contact your account manager.']);
        }

        return $next($request);
    }
}
