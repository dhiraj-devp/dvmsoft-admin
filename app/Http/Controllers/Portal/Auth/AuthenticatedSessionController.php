<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::guard('client')->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        app(AuditLogger::class)->record(
            action: 'login',
            module: 'client_portal',
            auditable: $user,
            newValues: $user->auditActorValues(),
            user: $user,
        );

        return redirect()->intended(route('client.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('client')->user();

        if ($user) {
            app(AuditLogger::class)->record(
                action: 'logout',
                module: 'client_portal',
                auditable: $user,
                newValues: $user->auditActorValues(),
                user: $user,
            );
        }

        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
