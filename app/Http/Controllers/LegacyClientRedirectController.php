<?php

namespace App\Http\Controllers;

use App\Support\ApplicationDomains;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyClientRedirectController extends Controller
{
    public function __invoke(Request $request, ApplicationDomains $domains, ?string $path = null): RedirectResponse
    {
        $canonical = $domains->canonicalClientPath($path);
        $target = $domains->clientUrl($canonical);

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        return redirect()->away($target);
    }
}
