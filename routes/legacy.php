<?php

use App\Http\Controllers\CanonicalHostRedirectController;
use App\Http\Controllers\LegacyClientRedirectController;
use Illuminate\Support\Facades\Route;

Route::any('/client/{path?}', LegacyClientRedirectController::class)
    ->where('path', '.*');

Route::any('/{path?}', CanonicalHostRedirectController::class)
    ->where('path', '.*');
