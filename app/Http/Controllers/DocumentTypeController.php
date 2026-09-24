<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DocumentType::class);

        return view('document-types.index');
    }
}
