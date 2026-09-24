<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        return view('permissions.index');
    }
}
