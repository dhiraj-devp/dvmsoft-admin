<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        return $request->user('client')?->hasVerifiedEmail()
            ? redirect()->intended(route('client.dashboard'))
            : view('portal.auth.verify-email');
    }
}
