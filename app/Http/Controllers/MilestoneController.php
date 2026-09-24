<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use Illuminate\View\View;

class MilestoneController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Milestone::class);

        return view('milestones.index');
    }
}
