<?php

namespace App\Http\Controllers;

use App\Models\TicketSlaRule;
use Illuminate\View\View;

class TicketSlaController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TicketSlaRule::class);

        return view('ticket-sla.index');
    }
}
