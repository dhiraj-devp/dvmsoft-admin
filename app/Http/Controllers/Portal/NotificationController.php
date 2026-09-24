<?php

namespace App\Http\Controllers\Portal;

use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends PortalController
{
    public function index(): View
    {
        return view('portal.notifications.index');
    }

    public function preferences(): View
    {
        $preference = NotificationPreference::for($this->portalUser());

        return view('portal.notifications.preferences', compact('preference'));
    }

    public function updatePreferences(): RedirectResponse
    {
        $preference = NotificationPreference::for($this->portalUser());
        $preference->in_app_enabled = request()->boolean('in_app_enabled');
        $preference->email_enabled = request()->boolean('email_enabled');
        $preference->save();

        return back()->with('status', 'Notification preferences saved.');
    }
}
