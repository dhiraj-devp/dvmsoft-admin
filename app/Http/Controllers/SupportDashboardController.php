<?php

namespace App\Http\Controllers;

use App\Services\SupportMetricsService;
use Illuminate\View\View;

class SupportDashboardController extends Controller
{
    public function __invoke(SupportMetricsService $metrics): View
    {
        $this->authorize('support.dashboard.view');

        return view('support.dashboard', $metrics->dashboard(auth()->id()));
    }
}
