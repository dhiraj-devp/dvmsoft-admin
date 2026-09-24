<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user('client');

        abort_unless($user, 403);
        abort_unless(hash_equals((string) $request->route('id'), (string) $user->getKey()), 403);
        abort_unless(hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));

            app(AuditLogger::class)->record(
                action: 'email_verified',
                module: 'client_portal',
                auditable: $user,
                newValues: $user->auditActorValues(),
                user: $user,
            );
        }

        return redirect()->intended(route('client.dashboard'))->with('status', 'Your email has been verified.');
    }
}
