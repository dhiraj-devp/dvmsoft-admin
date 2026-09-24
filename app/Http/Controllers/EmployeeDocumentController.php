<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function download(Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($document->employee_id === $employee->id, 404);
        abort_unless($document->disk === 'hr' || $document->disk === 'local', 404);

        $disk = $document->disk ?: 'hr';

        abort_unless(Storage::disk($disk)->exists($document->path), 404);

        return Storage::disk($disk)->download($document->path, $document->original_name);
    }
}
