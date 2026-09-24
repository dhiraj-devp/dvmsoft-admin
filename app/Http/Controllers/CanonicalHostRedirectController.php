<?php

namespace App\Http\Controllers;

use App\Support\ApplicationDomains;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CanonicalHostRedirectController extends Controller
{
    public function __invoke(Request $request, ApplicationDomains $domains, ?string $path = null): RedirectResponse
    {
        abort_unless($domains->hostsAreSeparated(), 404);
        abort_if($domains->isAdminHost($request) || $domains->isClientHost($request), 404);

        $target = $domains->adminUrl('/'.ltrim((string) $path, '/'));

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        return redirect()->away($target);
    }
}
