<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('roles.index');
    }
}
