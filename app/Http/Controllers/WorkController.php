<?php

namespace App\Http\Controllers;

use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use App\Models\WorkReview;
use Illuminate\View\View;

class WorkController extends Controller
{
    public function my(): View
    {
        $this->authorize('viewAny', WorkDailyUpdate::class);

        return view('work.my');
    }

    public function updates(): View
    {
        $this->authorize('viewAny', WorkDailyUpdate::class);

        return view('work.updates');
    }

    public function goals(): View
    {
        $this->authorize('viewAny', WorkGoal::class);

        return view('work.goals');
    }

    public function team(): View
    {
        abort_unless(auth()->user()?->canViewWorkTeam(), 403);

        return view('work.team');
    }

    public function reviews(): View
    {
        $this->authorize('viewAny', WorkReview::class);

        return view('work.reviews');
    }

    public function reports(): View
    {
        abort_unless(auth()->user()?->hasPermission('work.reports.view'), 403);

        return view('work.reports');
    }
}
