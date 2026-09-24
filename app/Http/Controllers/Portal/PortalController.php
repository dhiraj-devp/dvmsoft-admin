<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use App\Services\ClientPortal\ClientAccess;
use Illuminate\Support\Facades\Auth;

abstract class PortalController extends Controller
{
    protected function portalUser(): ClientUser
    {
        $user = Auth::guard('client')->user();

        abort_unless($user instanceof ClientUser, 403);

        return $user;
    }

    protected function access(): ClientAccess
    {
        return app(ClientAccess::class);
    }
}
