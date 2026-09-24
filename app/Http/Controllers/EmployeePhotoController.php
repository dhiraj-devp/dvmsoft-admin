<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePhotoController extends Controller
{
    public function __invoke(Employee $employee): StreamedResponse
    {
        $this->authorize('view', $employee);

        abort_unless($employee->photo_path, 404);

        $disk = $employee->photo_disk ?: 'hr';

        abort_unless($disk === 'hr' || $disk === 'local', 404);
        abort_unless(Storage::disk($disk)->exists($employee->photo_path), 404);

        return Storage::disk($disk)->response($employee->photo_path);
    }
}
