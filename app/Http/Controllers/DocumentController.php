<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentMetricsService;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function dashboard(DocumentMetricsService $metrics): View
    {
        $this->authorize('viewAny', Document::class);

        return view('documents.dashboard', $metrics->dashboard());
    }

    public function index(): View
    {
        $this->authorize('viewAny', Document::class);

        return view('documents.index');
    }

    public function create(): View
    {
        $this->authorize('create', Document::class);

        return view('documents.create');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load(['type', 'owner', 'createdBy', 'approvedBy', 'client', 'project', 'employee.user', 'quotation', 'invoice']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        return view('documents.edit', compact('document'));
    }

    public function expiring(): View
    {
        $this->authorize('viewAny', Document::class);

        return view('documents.expiring');
    }
}
