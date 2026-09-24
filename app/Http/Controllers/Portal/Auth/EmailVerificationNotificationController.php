<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user('client')?->hasVerifiedEmail()) {
            return redirect()->intended(route('client.dashboard'));
        }

        $request->user('client')->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email.');
    }
}
