<?php

namespace App\Http\Controllers\Portal;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends PortalController
{
    public function edit(): View
    {
        return view('portal.profile.edit', [
            'user' => $this->portalUser()->load('client'),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update($validated);

        $audit->record(
            action: 'updated',
            module: 'client_portal',
            auditable: $user,
            newValues: array_merge($user->auditActorValues(), ['fields' => ['name']]),
            user: $user,
        );

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:client'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $audit->record(
            action: 'password_changed',
            module: 'client_portal',
            auditable: $user,
            newValues: $user->auditActorValues(),
            user: $user,
        );

        return back()->with('status', 'Password updated.');
    }
}
