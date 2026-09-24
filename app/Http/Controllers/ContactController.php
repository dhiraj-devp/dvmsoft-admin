<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Contact::class);

        return view('contacts.index');
    }
}
