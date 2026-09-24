<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Client::class);

        return view('clients.index');
    }

    public function show(Client $client): View
    {
        $this->authorize('view', $client);

        $client->load(['accountManager', 'convertedFromLead', 'contacts', 'quotations', 'projects', 'activities.user']);

        return view('clients.show', compact('client'));
    }
}
