<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AiController extends Controller
{
    public function overview(): View
    {
        $this->authorize('ai.overview.view');

        return view('ai.overview');
    }

    public function finance(): View
    {
        $this->authorize('ai.finance.use');

        return view('ai.finance');
    }
}
