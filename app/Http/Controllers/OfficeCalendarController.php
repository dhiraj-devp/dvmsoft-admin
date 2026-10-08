<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OfficeCalendarController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        return view('office.calendar');
    }
}
