<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('notifications.index');
    }

    public function preferences(): View
    {
        $preference = NotificationPreference::for(auth()->user());

        return view('notifications.preferences', compact('preference'));
    }

    public function updatePreferences(): RedirectResponse
    {
        $preference = NotificationPreference::for(auth()->user());
        $preference->in_app_enabled = request()->boolean('in_app_enabled');
        $preference->email_enabled = request()->boolean('email_enabled');
        $preference->save();

        return back()->with('status', 'Notification preferences saved.');
    }
}
