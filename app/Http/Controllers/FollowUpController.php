<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', FollowUp::class);

        return view('follow-ups.index');
    }
}
