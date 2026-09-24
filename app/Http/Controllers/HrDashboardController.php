<?php

namespace App\Http\Controllers;

use App\Services\HrMetricsService;
use Illuminate\View\View;

class HrDashboardController extends Controller
{
    public function __invoke(HrMetricsService $metrics): View
    {
        $this->authorize('hr.dashboard.view');

        return view('hr.dashboard', $metrics->dashboard());
    }
}
