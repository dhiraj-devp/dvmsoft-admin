<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('settings.index', ['section' => 'company']);
    }

    public function show(string $section): View
    {
        $this->authorize('viewAny', Setting::class);

        $sections = ['company', 'branding', 'localization', 'security', 'email', 'notifications', 'system', 'finance', 'hr', 'documents', 'ai', 'automations', 'projects'];

        abort_unless(in_array($section, $sections, true), 404);

        return view('settings.index', ['section' => $section]);
    }
}
