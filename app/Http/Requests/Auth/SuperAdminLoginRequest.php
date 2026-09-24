<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SuperAdminLoginRequest extends LoginRequest
{
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->reject();
        }

        $user = Auth::user();

        if (! $user instanceof User || ! $user->is_super_admin || ! $user->is_active) {
            Auth::logout();
            $this->reject();
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function throttleKey(): string
    {
        return Str::transliterate('super-admin-login|'.Str::lower($this->string('email')).'|'.$this->ip());
    }

    protected function reject(): never
    {
        RateLimiter::hit($this->throttleKey());

        app(AuditLogger::class)->record(
            action: 'login_failed',
            module: 'auth',
            newValues: [
                'email' => $this->string('email')->toString(),
                'entry' => 'super_admin',
            ],
        );

        throw ValidationException::withMessages([
            'email' => self::GENERIC_FAILURE,
        ]);
    }
}
