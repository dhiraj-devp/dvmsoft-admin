<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OffboardingController extends Controller
{
    public function index(): View
    {
        $this->authorize('offboarding.view');

        return view('hr.offboarding');
    }
}
