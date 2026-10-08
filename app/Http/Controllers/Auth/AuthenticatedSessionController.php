<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        Auth::guard('client')->logout();
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $request->session()->put('auth.entry', 'staff');

        app(AuditLogger::class)->record(
            action: 'login',
            module: 'auth',
            auditable: $user,
            newValues: ['entry' => 'staff'],
        );

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $superAdmin = $user instanceof User && $user->is_super_admin;

        app(AuditLogger::class)->record(
            action: 'logout',
            module: 'auth',
            auditable: $user,
            newValues: ['entry' => $superAdmin ? 'super_admin' : 'staff'],
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($superAdmin ? 'super-admin.login' : 'login');
    }
}
