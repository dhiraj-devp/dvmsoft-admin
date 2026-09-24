<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Task::class);

        return view('tasks.index');
    }
}
