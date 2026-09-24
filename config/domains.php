<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staff / Admin origin
    |--------------------------------------------------------------------------
    |
    | Canonical URL for Dvmsoft Admin OS (staff and Super Admin). APP_URL
    | should match this value. Do not point this at the Client Portal host.
    |
    */

    'admin_url' => env('ADMIN_URL', env('APP_URL', 'http://localhost')),

    /*
    |--------------------------------------------------------------------------
    | Client Portal origin
    |--------------------------------------------------------------------------
    |
    | Canonical URL for the Client Portal. When this host differs from the
    | admin host, client routes are served on this domain without a /client
    | prefix. When it is empty or shares the admin host, /client/* is used.
    |
    */

    'client_url' => env('CLIENT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Session cookies
    |--------------------------------------------------------------------------
    |
    | Separate cookie names so staff and client sessions cannot be reused
    | across hosts even if SESSION_DOMAIN is misconfigured. Keep
    | SESSION_DOMAIN null so cookies stay host-only.
    |
    */

    'admin_session_cookie' => env('ADMIN_SESSION_COOKIE', 'dvmsoft-admin-session'),
    'client_session_cookie' => env('CLIENT_SESSION_COOKIE', 'dvmsoft-client-session'),

];
