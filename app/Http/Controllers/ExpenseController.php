<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Expense::class);

        return view('expenses.index');
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        $this->authorize('view', $expense);

        abort_unless($expense->receipt_path, 404);

        return Storage::disk($expense->receipt_disk ?: 'local')->download($expense->receipt_path, 'expense-receipt');
    }
}
