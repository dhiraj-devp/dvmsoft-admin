<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SuperAdminLoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SuperAdminAuthenticatedSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user('web');

        if ($user instanceof User) {
            abort_unless($user->is_super_admin, 404);

            return redirect()->route('dashboard');
        }

        return view('auth.super-admin-login');
    }

    public function store(SuperAdminLoginRequest $request): RedirectResponse
    {
        $user = $request->user('web');

        if ($user instanceof User && ! $user->is_super_admin) {
            abort(404);
        }

        $request->authenticate();
        Auth::guard('client')->logout();
        $request->session()->regenerate();
        $request->session()->put('auth.entry', 'super_admin');

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        app(AuditLogger::class)->record(
            action: 'login',
            module: 'auth',
            auditable: $user,
            newValues: ['entry' => 'super_admin'],
        );

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        abort_unless($user instanceof User && $user->is_super_admin, 404);

        app(AuditLogger::class)->record(
            action: 'logout',
            module: 'auth',
            auditable: $user,
            newValues: ['entry' => 'super_admin'],
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
