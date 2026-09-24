<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const GENERIC_FAILURE = 'These credentials do not match our records.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->rejectWithGenericFailure();
        }

        $user = Auth::user();

        if ($user instanceof User && $user->is_super_admin) {
            Auth::logout();
            $this->rejectWithGenericFailure();
        }

        if ($user && ! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Contact an administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function rejectWithGenericFailure(): never
    {
        RateLimiter::hit($this->throttleKey());

        app(AuditLogger::class)->record(
            action: 'login_failed',
            module: 'auth',
            newValues: ['email' => $this->string('email')->toString()],
        );

        throw ValidationException::withMessages([
            'email' => self::GENERIC_FAILURE,
        ]);
    }

    protected function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) settings('security.login_max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
