<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use Illuminate\View\View;

class TicketCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TicketCategory::class);

        return view('ticket-categories.index');
    }
}
