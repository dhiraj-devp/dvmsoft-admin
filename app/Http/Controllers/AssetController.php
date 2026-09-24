<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAsset;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        return view('assets.index');
    }
}
