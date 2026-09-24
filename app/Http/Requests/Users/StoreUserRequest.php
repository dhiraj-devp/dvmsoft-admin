<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => PasswordRules::forUsers(),
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:users,employee_code'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department_id' => ['nullable', 'ulid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'ulid', 'exists:users,id'],
            'date_of_joining' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'theme' => ['nullable', Rule::in(['light', 'dark', 'system'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['ulid', 'exists:roles,id'],
        ];
    }
}
