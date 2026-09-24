<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return view('leave.index');
    }
}
