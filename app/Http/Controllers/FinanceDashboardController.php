<?php

namespace App\Http\Controllers;

use App\Services\FinanceMetricsService;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __invoke(FinanceMetricsService $metrics): View
    {
        $this->authorize('finance.dashboard.view');

        return view('finance.dashboard', $metrics->dashboard());
    }
}
