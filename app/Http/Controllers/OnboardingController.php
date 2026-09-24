<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(): View
    {
        $this->authorize('onboarding.view');

        return view('hr.onboarding');
    }
}
